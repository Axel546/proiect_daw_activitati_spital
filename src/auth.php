<?php
/**
 * helper pentru auth
 */

/**
 * porneste sesiune daca nu este pornita
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Vverifica daca utilizatorul este autentificat
 * 
 * @return bool True daca utilizatorul este autentificat
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * obtine ID utilizator curent din sesiune
 * 
 * @return int|null ID utilizator sau null daca nu este autentificat
 */
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * obtine date utilizator curent din sesiune
 * 
 * @return array|null Date utilizator sau null daca nu este autentificat
 */
function getCurrentUser() {
    startSession();
    return $_SESSION['user'] ?? null;
}

/**
 * verifica dacă utilizatorul curent are un rol specific
 * 
 * @param string $roleName Numele rolului de verificat
 * @return bool True dacă utilizatorul are rolul
 */
function hasRole($roleName) {
    $user = getCurrentUser();
    return $user && isset($user['role_name']) && $user['role_name'] === $roleName;
}

/**
 * Cere ca utilizatorul sa fie autentificat
 * redirectioneaza la pagina de login daca nu este autentificat
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Determină calea relativă către login.php
        $currentFile = $_SERVER['PHP_SELF'] ?? '';
        $relativePath = '';
        if (strpos($currentFile, '/activities/') !== false || strpos($currentFile, '/reports/') !== false || strpos($currentFile, '/admin/') !== false) {
            $relativePath = '../';
        }
        header('Location: ' . $relativePath . 'login.php');
        exit;
    }
}

/**
 * Cere ca utilizatorul sa aiba un rol specific
 * 
 * @param string|array $roleName Numele rolului/rolurilor de verificat
 */
function requireRole($roleName) {
    requireLogin();
    $roles = is_array($roleName) ? $roleName : [$roleName];
    $user = getCurrentUser();
    
    if (!$user || !in_array($user['role_name'] ?? '', $roles)) {
        // Determină calea relativă către unauthorized.php
        $currentFile = $_SERVER['PHP_SELF'] ?? '';
        $relativePath = '';
        if (strpos($currentFile, '/activities/') !== false || strpos($currentFile, '/reports/') !== false || strpos($currentFile, '/admin/') !== false) {
            $relativePath = '../';
        }
        header('Location: ' . $relativePath . 'unauthorized.php');
        exit;
    }
}

/**
 * verifica daca utilizatorul poate crea activitati
 * 
 * @return bool True daca utilizatorul poate crea activitati
 */
function canCreateActivity() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser();
    $role = $user['role_name'] ?? '';
    // admin, staff, nurse pot crea
    return in_array($role, ['admin', 'staff', 'nurse']);
}

/**
 * verifica dacă utilizatorul poate edita o activitate
 * 
 * @param int $activityId ID activitate
 * @param PDO $pdo Conexiune la baza de date
 * @return bool True daca utilizatorul poate edita activitatea
 */
function canEditActivity($activityId, $pdo) {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once __DIR__ . '/helpers.php';
    $user = getCurrentUser();
    $role = $user['role_name'] ?? '';
    $userId = getCurrentUserId();
    
    // Admin poate edita orice activitate
    if ($role === 'admin') {
        return true;
    }
    
    // Staff și nurse pot edita doar activitățile proprii
    if (in_array($role, ['staff', 'nurse'])) {
        $activity = dbSelectOne($pdo, "SELECT created_by FROM activities WHERE id = ?", [$activityId]);
        return $activity && $activity['created_by'] == $userId;
    }
    
    return false;
}

/**
 * verifica daca utilizatorul poate sterge o activitate
 * 
 * @param int $activityId ID activitate
 * @param PDO $pdo Conexiune la baza de date
 * @return bool True daca utilizatorul poate sterge activitatea
 */
function canDeleteActivity($activityId, $pdo) {
    // Aceleași reguli ca pentru editare
    return canEditActivity($activityId, $pdo);
}

/**
 * verifica daca utilizatorul poate vizualiza activitati (doar citire)
 * 
 * @return bool True daca utilizatorul poate vizualiza activitati
 */
function canViewActivities() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser();
    $role = $user['role_name'] ?? '';
    // toti utilizatorii autentificati pot vizualiza
    return true;
}

/**
 * autentifica un utilizator si steaza datele sesiunii
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param int $userId ID utilizator
 * @return bool True la succes
 */
function loginUser($pdo, $userId) {
    require_once __DIR__ . '/helpers.php';
    
    // Preia datele utilizatorului cu info despre rol si departament
    $user = dbSelectOne($pdo, "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.role_id,
            u.department_id,
            r.name AS role_name,
            d.name AS department_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ? AND u.is_active = 1
    ", [$userId]);
    
    if (!$user) {
        return false;
    }
    
    startSession();
    
    // Regenerare ID sesiune la login pentru securitate
    session_regenerate_id(true);
    
    // stocheaza datele utilizatorului in sesiune
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    
    return true;
}

/**
 * deconecteaza utilizatorul curent
 */
function logoutUser() {
    startSession();
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
