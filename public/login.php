<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

// redirectioneaza dacă este deja autentificat
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pdo = getDbConnection();
$error = null;
$success = null;

// afiseaza succes la redirectionare dupa inregistrare
if (isset($_GET['registered'])) {
    $success = 'Înregistrare reușită! Ai fost autentificat.';
}

// gestioneaza POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validare
    if (empty($email) || empty($password)) {
        $error = 'Te rugăm să introduci atât email-ul cât și parola.';
    } else {
        // cauta utilizator dupa email
        $user = dbSelectOne($pdo, "SELECT id, password_hash, is_active FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            $error = 'Email sau parolă invalidă.';
        } elseif (!$user['is_active']) {
            $error = 'Contul tău a fost dezactivat. Te rugăm să contactezi un administrator.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Email sau parolă invalidă.';
        } else {
            // Autentificare reusita
            if (loginUser($pdo, $user['id'])) {
                // redirectioneaza la pagina principala (PRG)
                header('Location: index.php?logged_in=1');
                exit;
            } else {
                $error = 'Autentificare eșuată. Te rugăm să încerci din nou.';
            }
        }
    }
}

$pageTitle = 'Autentificare - Activități Spital';
ob_start();
?>
<h1>Autentificare</h1>
<p class="subtitle">Conectează-te la contul tău</p>

<?php if ($error): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success"><?php echo h($success); ?></div>
<?php endif; ?>

<div class="form-section">
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="email">Email *</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo h($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Parolă *</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Autentificare</button>
        <a href="register.php" class="btn btn-secondary" style="margin-left: 10px;">Nu ai cont? Înregistrează-te</a>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
