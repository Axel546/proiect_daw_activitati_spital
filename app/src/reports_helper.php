<?php
/**
 * Functii helper pentru rapoarte
 * Functii partajate pentru construirea interogarilor de activitati cu filtre
 */

/**
 * Construieste interogare activitati cu filtre
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param array $filters Parametri filtrare
 * @return array Date activitati
 */
function getFilteredActivities($pdo, $filters) {
    require_once __DIR__ . '/helpers.php';
    
    $department_id = isset($filters['department_id']) ? (int)$filters['department_id'] : 0;
    $status = isset($filters['status']) ? trim($filters['status']) : '';
    $date_from = isset($filters['date_from']) ? trim($filters['date_from']) : '';
    $date_to = isset($filters['date_to']) ? trim($filters['date_to']) : '';
    $created_by = isset($filters['created_by']) ? (int)$filters['created_by'] : 0;
    
    // Construieste interogare cu filtre
    $where = [];
    $params = [];
    
    if ($department_id > 0) {
        $where[] = "a.department_id = ?";
        $params[] = $department_id;
    }
    
    if ($status && in_array($status, ['planned', 'in_progress', 'completed', 'cancelled'])) {
        $where[] = "a.status = ?";
        $params[] = $status;
    }
    
    if ($date_from) {
        $where[] = "DATE(a.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where[] = "DATE(a.created_at) <= ?";
        $params[] = $date_to;
    }
    
    if ($created_by > 0) {
        $where[] = "a.created_by = ?";
        $params[] = $created_by;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Preia activitati
    $activities = dbSelectAll($pdo, "
        SELECT 
            a.id,
            a.title,
            a.description,
            a.status,
            a.scheduled_date,
            a.created_at,
            a.updated_at,
            d.name AS department_name,
            u.username AS created_by_username,
            u.first_name,
            u.last_name,
            u.id AS created_by_id
        FROM activities a
        LEFT JOIN departments d ON a.department_id = d.id
        LEFT JOIN users u ON a.created_by = u.id
        $whereClause
        ORDER BY a.created_at DESC
    ", $params);
    
    return $activities;
}

/**
 * obtine descriere filtre pentru rapoarte
 * 
 * @param array $filters Parametri filtrare
 * @param PDO $pdo Conexiune la baza de date (pentru cautarea numelor)
 * @return string Descriere filtre
 */
function getFilterDescription($filters, $pdo) {
    require_once __DIR__ . '/helpers.php';
    
    $parts = [];
    
    if (!empty($filters['department_id'])) {
        $dept = dbSelectOne($pdo, "SELECT name FROM departments WHERE id = ?", [$filters['department_id']]);
        if ($dept) {
            $parts[] = "Departament: " . $dept['name'];
        }
    }
    
    if (!empty($filters['status'])) {
        $parts[] = "Status: " . ucfirst(str_replace('_', ' ', $filters['status']));
    }
    
    if (!empty($filters['date_from'])) {
        $parts[] = "De la: " . $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $parts[] = "Până la: " . $filters['date_to'];
    }
    
    if (!empty($filters['created_by'])) {
        $user = dbSelectOne($pdo, "SELECT first_name, last_name, username FROM users WHERE id = ?", [$filters['created_by']]);
        if ($user) {
            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $parts[] = "Creator: " . ($name ?: $user['username']);
        }
    }
    
    return !empty($parts) ? implode(', ', $parts) : 'Toate activitatile';
}
