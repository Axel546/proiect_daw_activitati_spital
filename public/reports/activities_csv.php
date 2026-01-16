<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/reports_helper.php';

requireLogin();

$pdo = getDbConnection();

// Obtine parametrii filtrare
$filters = [
    'department_id' => isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0,
    'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
    'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : '',
    'created_by' => isset($_GET['created_by']) ? (int)$_GET['created_by'] : 0
];

// Valideaza status daca este furnizat
if ($filters['status'] && !in_array($filters['status'], ['planned', 'in_progress', 'completed', 'cancelled'])) {
    $filters['status'] = '';
}

// Preia activitati
$activities = getFilteredActivities($pdo, $filters);
$filterDescription = getFilterDescription($filters, $pdo);

// Seteaza antete pentru CSV
$filename = 'raport_activitati_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Deschide output
$output = fopen('php://output', 'w');

// BOM UTF-8 compatibilitate Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Scrie antet
fputcsv($output, [
    'ID',
    'Titlu',
    'Descriere',
    'Departament',
    'Status',
    'Data Programată',
    'Creată de',
    'Data Creării',
    'Ultima Actualizare'
]);

// Scrie date
foreach ($activities as $activity) {
    $creator = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
    $creatorName = $creator ?: $activity['created_by_username'] ?? 'Necunoscut';
    
    fputcsv($output, [
        $activity['id'],
        $activity['title'],
        $activity['description'],
        $activity['department_name'] ?? 'N/A',
        translateStatus($activity['status']),
        $activity['scheduled_date'] ? date('Y-m-d H:i', strtotime($activity['scheduled_date'])) : '',
        $creatorName,
        date('Y-m-d H:i:s', strtotime($activity['created_at'])),
        $activity['updated_at'] ? date('Y-m-d H:i:s', strtotime($activity['updated_at'])) : ''
    ]);
}

fclose($output);
exit;
