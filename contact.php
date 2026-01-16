<?php
require_once __DIR__ . '/app/src/db.php';
require_once __DIR__ . '/app/src/helpers.php';
require_once __DIR__ . '/app/src/auth.php';
require_once __DIR__ . '/app/src/csrf.php';
require_once __DIR__ . '/app/src/analytics.php';

$pdo = getDbConnection();
$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();
$error = null;
$errors = [];
$success = null;

// Obtine config
$configPath = __DIR__ . '/app/config/local.php';
$config = file_exists($configPath) ? require $configPath : [];
$adminEmail = $config['contact']['admin_email'] ?? 'admin@hospital.local';
$analyticsConfig = $config['analytics'] ?? [];
$ipSalt = $analyticsConfig['ip_salt'] ?? 'default_salt_change_me';

// Gestioneaza POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $honeypot = trim($_POST['website'] ?? ''); //  honeypot
    
    // Verif honeypot - completat => bot
    if (!empty($honeypot)) {
        // mesaj chiar daca este bot
        $success = 'Mesaj trimis cu succes!';
        $pageTitle = 'Contact - Activități Spital';
        ob_start();
        ?>
        <h1>Contact</h1>
        <p class="subtitle">Ia legătura cu noi</p>
        <div class="success"><?php echo h($success); ?></div>
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">Înapoi la Acasă</a>
        </div>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/app/views/layout.php';
        exit;
    }
    
    // Verificare limitare rate
    $ip = getClientIp();
    $ipHash = hashIpAddress($ip, $ipSalt);
    
    $recentMessages = dbSelectOne($pdo, "
        SELECT COUNT(*) as count 
        FROM contact_messages 
        WHERE ip_hash = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ", [$ipHash]);
    
    if ($recentMessages && $recentMessages['count'] >= 3) {
        $errors[] = 'Prea multe mesaje trimise recent. Te rugăm să aștepți câteva minute înainte de a încerca din nou.';
    }
    
    // Validare
    if (empty($name)) {
        $errors[] = 'Numele este obligatoriu.';
    } elseif (strlen($name) > 120) {
        $errors[] = 'Numele trebuie să aibă maximum 120 de caractere.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email-ul este obligatoriu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email invalid.';
    } elseif (strlen($email) > 190) {
        $errors[] = 'Email-ul trebuie să aibă maximum 190 de caractere.';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subiectul este obligatoriu.';
    } elseif (strlen($subject) > 190) {
        $errors[] = 'Subiectul trebuie să aibă maximum 190 de caractere.';
    }
    
    if (empty($message)) {
        $errors[] = 'Mesajul este obligatoriu.';
    } elseif (strlen($message) > 10000) {
        $errors[] = 'Mesajul trebuie să aibă maximum 10.000 de caractere.';
    }
    
    // daca nu sunt erori, salveaza mesaj + trimite mail
    if (empty($errors)) {
        $userId = $isLoggedIn ? getCurrentUserId() : null;
        
        // Salveaza in baza de date
        $sql = "INSERT INTO contact_messages (name, email, subject, message, ip_hash, user_id) VALUES (?, ?, ?, ?, ?, ?)";
        $messageId = dbInsert($pdo, $sql, [$name, $email, $subject, $message, $ipHash, $userId]);
        
        if ($messageId) {
            // Incearca email
            $emailSent = false;
            $emailError = null;
            
            try {
                $emailSubject = "Formular Contact: " . $subject;
                $emailBody = "Ai primit un nou mesaj din formularul de contact:\n\n";
                $emailBody .= "Nume: " . $name . "\n";
                $emailBody .= "Email: " . $email . "\n";
                if ($userId) {
                    $emailBody .= "ID Utilizator: " . $userId . "\n";
                }
                $emailBody .= "\nSubiect: " . $subject . "\n\n";
                $emailBody .= "Mesaj:\n" . $message . "\n";
                
                $headers = "From: " . $email . "\r\n";
                $headers .= "Reply-To: " . $email . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                $emailSent = @mail($adminEmail, $emailSubject, $emailBody, $headers);
                
                if (!$emailSent) {
                    $emailError = error_get_last();
                }
            } catch (Exception $e) {
                $emailError = $e->getMessage();
            }
            
            // Redirectioneaza pentru a preveni retrimitere formular (PRG)
            if ($emailSent) {
                header('Location: contact.php?success=1');
            } else {
                header('Location: contact.php?success=1&email_delayed=1');
            }
            exit;
        } else {
            $errors[] = 'Salvarea mesajului a eșuat. Te rugăm să încerci din nou.';
        }
    }
}

// mesaj de succes la redirectionare
if (isset($_GET['success'])) {
    $success = 'Mesaj trimis cu succes!';
    if (isset($_GET['email_delayed'])) {
        $success = 'Mesaj salvat cu succes. Livrarea email-ului poate fi întârziată.';
    }
}

$pageTitle = 'Contact - Activități Spital';
ob_start();
?>
<h1>Contact</h1>
<p class="subtitle">Ia legătura cu noi</p>

<?php if ($success): ?>
    <div class="success"><?php echo h($success); ?></div>
    <div style="margin-top: 20px;">
        <a href="index.php" class="btn">Înapoi la Acasă</a>
    </div>
<?php else: ?>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Te rugăm să corectezi următoarele erori:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo h($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-section">
        <form method="POST">
            <?php echo csrfField(); ?>
            
            <!-- camp honeypot ascuns de utilizatori dar vizibil pentru boti -->
            <div style="position: absolute; left: -9999px; opacity: 0;">
                <label for="website">Website (lasă gol)</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label">Nume *</label>
                    <input type="text" id="name" name="name" required maxlength="120" 
                           value="<?php echo h($_POST['name'] ?? ($isLoggedIn ? ($currentUser['first_name'] . ' ' . $currentUser['last_name']) : '')); ?>" class="form-control">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" required maxlength="190" 
                           value="<?php echo h($_POST['email'] ?? ($isLoggedIn ? ($currentUser['email'] ?? '') : '')); ?>" class="form-control">
                </div>
                
                <div class="col-12">
                    <label for="subject" class="form-label">Subiect *</label>
                    <input type="text" id="subject" name="subject" required maxlength="190" 
                           value="<?php echo h($_POST['subject'] ?? ''); ?>" class="form-control">
                </div>
                
                <div class="col-12">
                    <label for="message" class="form-label">Mesaj *</label>
                    <textarea id="message" name="message" required maxlength="10000" rows="8" class="form-control"><?php echo h($_POST['message'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">Maximum 10.000 caractere</small>
                </div>
            </div>
            
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Trimite Mesaj</button>
                <a href="index.php" class="btn btn-secondary">Anulează</a>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/app/views/layout.php';
?>
