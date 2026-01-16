<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

// redirectioneaza daca este autentificat
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pdo = getDbConnection();
$errors = [];
$success = null;

// gestioneaza POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validare
    if (empty($first_name)) {
        $errors[] = 'Prenumele este obligatoriu.';
    } elseif (strlen($first_name) > 50) {
        $errors[] = 'Prenumele trebuie să aibă maximum 50 de caractere.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Numele este obligatoriu.';
    } elseif (strlen($last_name) > 50) {
        $errors[] = 'Numele trebuie să aibă maximum 50 de caractere.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email-ul este obligatoriu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email invalid.';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email-ul trebuie să aibă maximum 100 de caractere.';
    } else {
        // verifica daca email exista
        $existing = dbSelectOne($pdo, "SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'Email-ul este deja înregistrat.';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'Parola este obligatorie.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Parola trebuie să aibă minimum 6 caractere.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Parolele nu se potrivesc.';
    }
    
    // fara erori => creeaza utilizator
    if (empty($errors)) {
        // obtine rol implicit (staff) pentru inregistrari noi
        $defaultRole = dbSelectOne($pdo, "SELECT id FROM roles WHERE name = 'staff' LIMIT 1");
        if (!$defaultRole) {
            $errors[] = 'Eroare sistem: Rolul implicit nu a fost găsit.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '.', $first_name . '.' . $last_name));
            
            // asigura username unic
            $baseUsername = $username;
            $counter = 1;
            while (dbSelectOne($pdo, "SELECT id FROM users WHERE username = ?", [$username])) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            $sql = "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)";
            $userId = dbInsert($pdo, $sql, [$username, $email, $password_hash, $defaultRole['id'], $first_name, $last_name]);
            
            if ($userId) {
                // Auto-autentificare după inregistrare
                if (loginUser($pdo, $userId)) {
                    // redirectioneaza la pagina principala (PRG)
                    header('Location: index.php?registered=1');
                    exit;
                } else {
                    // inregistrare reusita dar autentificare esuata - redirectioneaza la login
                    header('Location: login.php?registered=1');
                    exit;
                }
            } else {
                $errors[] = 'Înregistrare eșuată. Te rugăm să încerci din nou.';
            }
        }
    }
}

$pageTitle = 'Înregistrare - Activități Spital';
ob_start();
?>
<h1>Înregistrare</h1>
<p class="subtitle">Creează un cont nou pentru a gestiona activitățile spitalului</p>

<?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Te rugăm să corectezi următoarele erori:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="form-section">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo h(generateCsrfToken()); ?>">
        <div class="form-group">
            <label for="first_name">Prenume *</label>
            <input type="text" id="first_name" name="first_name" required maxlength="50" 
                   value="<?php echo h($_POST['first_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="last_name">Nume *</label>
            <input type="text" id="last_name" name="last_name" required maxlength="50" 
                   value="<?php echo h($_POST['last_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required maxlength="100" 
                   value="<?php echo h($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Parolă *</label>
            <input type="password" id="password" name="password" required minlength="6">
            <small style="color: #666; font-size: 12px;">Minimum 6 caractere</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmă Parola *</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <button type="submit">Înregistrare</button>
        <a href="login.php" class="btn btn-secondary" style="margin-left: 10px;">Ai deja cont? Autentifică-te</a>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
