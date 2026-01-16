<?php
/**
 * Website Analytics
 */

/**
 * Verifica daca cererea curenta ar trebui sa fie logata
 * Exclude resurse statice (CSS, JS, imagini, favicon, etc.)
 * 
 * @return bool True daca cererea ar trebui sa fie logata
 */
function shouldLogPageView() {
    $path = $_SERVER['REQUEST_URI'] ?? '';
    
    // Nu loga resurse statice
    $staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
    $pathLower = strtolower($path);
    
    foreach ($staticExtensions as $ext) {
        if (strpos($pathLower, $ext) !== false) {
            return false;
        }
    }
    if (strpos($pathLower, 'favicon') !== false) {
        return false;
    }
    
    return true;
}

/**
 * Obține adresa IP client
 * 
 * @return string adresa IP
 */
function getClientIp() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Hash adresa IP pentru confidentialitate
 * 
 * @param string $ip adresa IP
 * @param string $salt salt din config
 * @return string Hash SHA256
 */
function hashIpAddress($ip, $salt) {
    return hash('sha256', $ip . $salt);
}

/**
 * logheaza o vizualizare de pagina
 * Ar trebui să fie apelata la fiecare incarcare de pagina
 */
function logPageView() {
    // verifica daca trebuie logata cererea
    if (!shouldLogPageView()) {
        return;
    }
    
    try {
        require_once __DIR__ . '/db.php';
        require_once __DIR__ . '/auth.php';
        require_once __DIR__ . '/helpers.php';
        
        $pdo = getDbConnection();
        
        // obtine config
        $configPath = __DIR__ . '/../config/local.php';
        if (!file_exists($configPath)) {
            return; // nu se poate loga fara config
        }
        
        $config = require $configPath;
        $salt = $config['analytics']['ip_salt'] ?? 'default_salt_change_me';
        
        // obtine id sesiune
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionId = session_id();
        
        // obtine id utilizator
        $userId = isLoggedIn() ? getCurrentUserId() : null;
        
        // obtine cale si metoda cerere
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // limiteaza lungimea caii
        if (strlen($path) > 255) {
            $path = substr($path, 0, 255);
        }
        
        // obtine user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if ($userAgent && strlen($userAgent) > 65535) {
            $userAgent = substr($userAgent, 0, 65535);
        }
        
        // obtine si hash ip
        $ip = getClientIp();
        $ipHash = hashIpAddress($ip, $salt);
        
        // insert vizualizare pagina
        $sql = "INSERT INTO analytics_page_views (user_id, session_id, path, method, user_agent, ip_hash) VALUES (?, ?, ?, ?, ?, ?)";
        dbInsert($pdo, $sql, [$userId, $sessionId, $path, $method, $userAgent, $ipHash]);
        
    } catch (Exception $e) {
        // nu intrerupe aplicatia daca analytics esuata
        error_log("Eroare analytics: " . $e->getMessage());
    }
}
