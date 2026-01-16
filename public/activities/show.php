<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';

requireLogin();

$pdo = getDbConnection();
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($activityId <= 0) {
    header('Location: index.php');
    exit;
}

// Preia detaliile activității
$activity = dbSelectOne($pdo, "
    SELECT 
        a.id,
        a.title,
        a.description,
        a.status,
        a.scheduled_date,
        a.created_at,
        a.updated_at,
        a.created_by,
        d.name AS department_name,
        d.id AS department_id,
        u.username AS created_by_username,
        u.first_name,
        u.last_name,
        u.id AS created_by_user_id
    FROM activities a
    LEFT JOIN departments d ON a.department_id = d.id
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.id = ?
", [$activityId]);

if (!$activity) {
    header('Location: index.php');
    exit;
}

$canEdit = canEditActivity($activityId, $pdo);
$canDelete = canDeleteActivity($activityId, $pdo);

// Afiseaza mesaje
$success = null;
$error = null;
if (isset($_GET['updated'])) {
    $success = 'Activitate actualizată cu succes!';
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error = 'Nu ai permisiunea de a efectua această acțiune.';
    } elseif ($_GET['error'] === 'delete_failed') {
        $error = 'Ștergerea activității a eșuat. Te rugăm să încerci din nou.';
    }
}

$pageTitle = h($activity['title']) . ' - Activități';
ob_start();
?>
<h1><?php echo h($activity['title']); ?></h1>
<p class="subtitle">Detalii Activitate</p>

<?php if ($success): ?>
    <div class="success"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">← Înapoi la Activități</a>
    <?php if ($canEdit): ?>
        <a href="edit.php?id=<?php echo $activity['id']; ?>" class="btn" style="margin-left: 10px;">Editează</a>
    <?php endif; ?>
</div>

<div class="activity">
    <div class="activity-header">
        <div>
            <div class="activity-title"><?php echo h($activity['title']); ?></div>
        </div>
        <span class="activity-status status-<?php echo h($activity['status']); ?>">
            <?php echo h(translateStatus($activity['status'])); ?>
        </span>
    </div>
    
    <div class="activity-body">
        <?php echo nl2br(h($activity['description'])); ?>
    </div>
    
    <div class="activity-meta">
        <div class="meta-item">
            <strong>Departament:</strong> <?php echo h($activity['department_name'] ?? 'N/A'); ?>
        </div>
        <div class="meta-item">
            <strong>Creată de:</strong> 
            <?php 
            $creator = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
            echo h($creator ?: $activity['created_by_username'] ?? 'Necunoscut');
            ?>
        </div>
        <?php if ($activity['scheduled_date']): ?>
            <div class="meta-item">
                <strong>Programată:</strong> <?php echo formatDateTime($activity['scheduled_date']); ?>
            </div>
        <?php endif; ?>
        <div class="meta-item">
            <strong>Creată:</strong> <?php echo formatDateTime($activity['created_at']); ?>
        </div>
        <?php if ($activity['updated_at'] && $activity['updated_at'] !== $activity['created_at']): ?>
            <div class="meta-item">
                <strong>Ultima Actualizare:</strong> <?php echo formatDateTime($activity['updated_at']); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canDelete): ?>
    <div class="form-section" style="margin-top: 30px; border: 2px solid #f8d7da;">
        <h2 style="color: #721c24;">Danger Zone</h2>
        <p>Odată ștearsă, această activitate nu poate fi recuperată.</p>
        <form method="POST" action="delete.php" onsubmit="return confirm('Ești sigur că vrei să ștergi această activitate? Această acțiune nu poate fi anulată.');">
            <?php
            require_once __DIR__ . '/../../src/csrf.php';
            echo csrfField();
            ?>
            <input type="hidden" name="id" value="<?php echo $activity['id']; ?>">
            <button type="submit" style="background: #dc3545;">Șterge Activitate</button>
        </form>
    </div>
<?php endif; ?>

<style>
    .activity {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
    }
    .activity-title {
        font-size: 24px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    .activity-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    .status-planned {
        background: #fff3cd;
        color: #856404;
    }
    .status-in_progress {
        background: #d1ecf1;
        color: #0c5460;
    }
    .status-completed {
        background: #d4edda;
        color: #155724;
    }
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    .activity-body {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
        font-size: 15px;
    }
    .activity-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        color: #999;
        font-size: 12px;
        padding-top: 10px;
        border-top: 1px solid #f0f0f0;
    }
    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
