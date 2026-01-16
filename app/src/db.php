<?php
function getDbConnection() {
    // incarca config
    $configPath = __DIR__ . '/../config/local.php';
    if (!file_exists($configPath)) {
        die('
            <html>
            <head><title>Configurare Necesară</title></head>
            <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
                <h1>Configurare Necesară</h1>
                <p>Te rugăm să copiezi <code>config/local.php.example</code> în <code>config/local.php</code> și să îl actualizezi cu credențialele tale pentru baza de date.</p>
                <p><strong>Pași:</strong></p>
                <ol>
                    <li>Copiază <code>config/local.php.example</code> în <code>config/local.php</code></li>
                    <li>Editează <code>config/local.php</code> cu setările tale pentru baza de date</li>
                    <li>Reîmprospătează această pagină</li>
                </ol>
            </body>
            </html>
        ');
    }

    $config = require $configPath;
    $dbConfig = $config['db'];

    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        return $pdo;
    } catch (PDOException $e) {
        die('
            <html>
            <head><title>Eroare Conexiune Baza de Date</title></head>
            <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
                <h1>Eroare Conexiune Baza de Date</h1>
                <p>Nu s-a putut conecta la baza de date. Te rugăm să verifici configurația din <code>config/local.php</code>.</p>
                <p><strong>Eroare:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            </body>
            </html>
        ');
    }
}
