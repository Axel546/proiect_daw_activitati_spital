<?php
require_once __DIR__ . '/../app/src/db.php';
require_once __DIR__ . '/../app/src/helpers.php';
require_once __DIR__ . '/../app/src/auth.php';
require_once __DIR__ . '/../app/src/csrf.php';

requireLogin();

// Permite DOAR cereri POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$pdo = getDbConnection();
$activityId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($activityId <= 0) {
    header('Location: index.php?error=invalid');
    exit;
}

// Verifica autorizare
if (!canDeleteActivity($activityId, $pdo)) {
    header('Location: show.php?id=' . $activityId . '&error=unauthorized');
    exit;
}

// Verifica daca activitatea exista
$activity = dbSelectOne($pdo, "SELECT id FROM activities WHERE id = ?", [$activityId]);
if (!$activity) {
    header('Location: index.php?error=notfound');
    exit;
}

// Sterge activitatea
$result = dbExecute($pdo, "DELETE FROM activities WHERE id = ?", [$activityId]);

if ($result !== false) {
    // Redirectioneaza pentru a preveni retrimitere formular (PRG)
    header('Location: index.php?deleted=1');
    exit;
} else {
    header('Location: show.php?id=' . $activityId . '&error=delete_failed');
    exit;
}
