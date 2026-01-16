<?php
// Layout reutilizabil cu navigare
// Utilizare: include acest fisier si definește $pageTitle inainte de includere
if (!isset($pageTitle)) {
    $pageTitle = 'Managementul Activitatilor Spitalului';
}

// Logheaza vizualizarea paginii pentru analitica
require_once __DIR__ . '/../src/analytics.php';
logPageView();

require_once __DIR__ . '/../src/auth.php';
$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();

// Determină calea relativă către public/ bazat pe locația fișierului curent
$currentFile = $_SERVER['PHP_SELF'] ?? '';
$relativePath = '';
if (strpos($currentFile, '/activities/') !== false || strpos($currentFile, '/reports/') !== false || strpos($currentFile, '/admin/') !== false) {
    $relativePath = '../';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 0;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-brand {
            font-size: 20px;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        .user-info {
            color: #ecf0f1;
            font-size: 14px;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        select {
            cursor: pointer;
        }
        button, .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #c33;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #3c3;
        }
        .info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #1976d2;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $relativePath; ?>index.php" class="nav-brand">Activități Spital</a>
            <div class="nav-links">
                <a href="<?php echo $relativePath; ?>index.php">Acasă</a>
                <a href="<?php echo $relativePath; ?>contact.php">Contact</a>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $relativePath; ?>external.php">WHO news</a>
                    <a href="<?php echo $relativePath; ?>reports/activities.php">Rapoarte</a>
                    <?php if (hasRole('admin')): ?>
                        <a href="<?php echo $relativePath; ?>admin/analytics.php">Analytics</a>
                        <a href="<?php echo $relativePath; ?>admin/messages.php">Mesaje</a>
                    <?php endif; ?>
                    <span class="user-info">
                        <?php echo h($currentUser['first_name'] ?? ''); ?> <?php echo h($currentUser['last_name'] ?? ''); ?>
                        (<?php echo h($currentUser['role_name'] ?? 'Utilizator'); ?>)
                    </span>
                    <a href="<?php echo $relativePath; ?>logout.php">Deconectare</a>
                <?php else: ?>
                    <a href="<?php echo $relativePath; ?>login.php">Autentificare</a>
                    <a href="<?php echo $relativePath; ?>register.php">Înregistrare</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">
        <?php if (isset($pageSubtitle)): ?>
            <p class="subtitle"><?php echo h($pageSubtitle); ?></p>
        <?php endif; ?>
        
        <?php echo $content ?? ''; ?>
    </div>
</body>
</html>
