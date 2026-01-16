<?php
/**
 * Funcții helper generice pentru baza de date
 * Funcții reutilizabile pentru operații comune cu baza de date
 */

/**
 * Execută o interogare SELECT și returnează toate rezultatele
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $sql Interogare SQL cu placeholder-uri
 * @param array $params Parametri pentru prepared statement
 * @return array Array de rezultate
 */
function dbSelectAll($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Eroare selectare baza de date: " . $e->getMessage());
        return [];
    }
}

/**
 * Execută o interogare SELECT și returnează un singur rând
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $sql Interogare SQL cu placeholder-uri
 * @param array $params Parametri pentru prepared statement
 * @return array|false Un singur rând sau false dacă nu este găsit
 */
function dbSelectOne($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Eroare selectare baza de date: " . $e->getMessage());
        return false;
    }
}

/**
 * Execută o interogare INSERT și returnează ultimul ID inserat
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $sql Interogare SQL cu placeholder-uri
 * @param array $params Parametri pentru prepared statement
 * @return int|false Ultimul ID inserat sau false la eșec
 */
function dbInsert($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Eroare inserare baza de date: " . $e->getMessage());
        return false;
    }
}

/**
 * Execută o interogare UPDATE sau DELETE
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $sql Interogare SQL cu placeholder-uri
 * @param array $params Parametri pentru prepared statement
 * @return int|false Numărul de rânduri afectate sau false la eșec
 */
function dbExecute($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Eroare executare baza de date: " . $e->getMessage());
        return false;
    }
}

/**
 * sanitizeaza output pentru afisare html - blochez xss (cross-site scripting)
 * de exemplu daca cineva introduce <script>alert('hacked')</script> in formular sa nu ruleze codul si doar sa afiseze textul
 * 
 * @param string $string String de sanitizat
 * @return string String sanitizat
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * formateaza datetime pentru afisare
 * 
 * @param string $datetime String datetime
 * @param string $format Format output (implicit: 'Y-m-d H:i')
 * @return string Datetime formatat
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i') {
    if (empty($datetime)) {
        return '';
    }
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Traduce status activitate pentru afisare
 * 
 * @param string $status Status din baza de date (planned, in_progress, completed, cancelled)
 * @return string Status tradus
 */
function translateStatus($status) {
    $translations = [
        'planned' => 'Planificat',
        'in_progress' => 'In Progres',
        'completed' => 'Completat',
        'cancelled' => 'Anulat'
    ];
    
    return $translations[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
