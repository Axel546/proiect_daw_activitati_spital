<?php
/**
 * protectie CSRF
 */

/**
 * proneste sesiunea daca nu este deja pornita
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * genereaza un token CSRF si stocheaza in sesiune
 * 
 * @return string Token CSRF
 */
function generateCsrfToken() {
    ensureSession();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * obtine token CSRF curent
 * 
 * @return string|null Token CSRF sau null daca nu este setat
 */
function getCsrfToken() {
    ensureSession();
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * valideaza un token CSRF
 * 
 * @param string $token Token de validat
 * @return bool True daca token-ul este valid
 */
function validateCsrfToken($token) {
    ensureSession();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * genereaza un câmp input ascuns pentru token CSRF
 * 
 * @return string camp input HTML
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Cere un token CSRF valid pentru cereri POST
 * stop executie cu mesaj de eroare daca token invalid
 */
function requireCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return; // Valideaza doar cereri POST
    }
    
    $token = $_POST['csrf_token'] ?? '';
    
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('
            <html>
            <head><title>Token CSRF Invalid</title></head>
            <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
                <h1>Eroare de Securitate</h1>
                <p>Token CSRF invalid sau lipsă. Această cerere a fost blocată din motive de securitate.</p>
                <p><a href="javascript:history.back()">Înapoi</a></p>
            </body>
            </html>
        ');
    }
}
