<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';

requireLogin();

$pdo = getDbConnection();
$currentUser = getCurrentUser();

// Obtine parametrii filtrare
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

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

// Preia departamentele pentru filtru
$departments = dbSelectAll($pdo, "SELECT id, name FROM departments ORDER BY name");

// Afiseaza mesaje
$success = null;
$error = null;
if (isset($_GET['deleted'])) {
    $success = 'Activitate ștearsă cu succes!';
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error = 'Nu ai permisiunea de a accesa această pagină.';
    }
}

$pageTitle = 'Activități - Managementul Activitatilor Spitalului';
ob_start();
?>
<h1>Activități</h1>
<p class="subtitle">Vizualizează și gestionează activitățile spitalului</p>

<?php if ($success): ?>
    <div class="success"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<?php if (canCreateActivity()): ?>
    <div style="margin-bottom: 20px;">
        <a href="create.php" class="btn">Creează o Activitate Nouă</a>
    </div>
<?php endif; ?>

<div class="form-section" style="margin-bottom: 20px;">
    <h2 style="margin-bottom: 15px;">Filtre</h2>
    <form method="GET">
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-lg-3">
                <label for="department_id" class="form-label">Departament</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">Toate Departamentele</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo h($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-md-6 col-lg-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Toate Statusurile</option>
                    <option value="planned" <?php echo $status === 'planned' ? 'selected' : ''; ?>>Planificat</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>În Progres</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completat</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Anulat</option>
                </select>
            </div>
            
            <div class="col-12 col-md-6 col-lg-3">
                <label for="date_from" class="form-label">De la Data</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo h($date_from); ?>" class="form-control">
            </div>
            
            <div class="col-12 col-md-6 col-lg-3">
                <label for="date_to" class="form-label">Până la Data</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo h($date_to); ?>" class="form-control">
            </div>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Filtrează</button>
            <a href="index.php" class="btn btn-secondary">Șterge</a>
        </div>
    </form>
</div>

<div class="activities-section">
    <h2>Toate Activitățile (<?php echo count($activities); ?>)</h2>
    <?php if (empty($activities)): ?>
        <div class="empty-state">Nicio activitate găsită. <?php if (canCreateActivity()): ?><a href="create.php">Creează una acum</a><?php endif; ?></div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-striped table-hover" style="margin-top: 20px;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Titlu</th>
                    <th style="padding: 12px; text-align: left;">Departament</th>
                    <th style="padding: 12px; text-align: left; min-width: 120px;">Status</th>
                    <th style="padding: 12px; text-align: left; min-width: 140px;">Creată de</th>
                    <th style="padding: 12px; text-align: left; min-width: 160px;">Creată</th>
                    <th style="padding: 12px; text-align: left; min-width: 120px;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <a href="show.php?id=<?php echo $activity['id']; ?>" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                <?php echo h($activity['title']); ?>
                            </a>
                        </td>
                        <td style="padding: 12px;"><?php echo h($activity['department_name'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px;" class="text-nowrap">
                            <span class="activity-status status-<?php echo h($activity['status']); ?>">
                                <?php echo h(translateStatus($activity['status'])); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;" class="text-nowrap">
                            <?php 
                            $creator = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
                            echo h($creator ?: $activity['created_by_username'] ?? 'Necunoscut');
                            ?>
                        </td>
                        <td style="padding: 12px;" class="text-nowrap"><?php echo formatDateTime($activity['created_at']); ?></td>
                        <td style="padding: 12px;" class="text-nowrap">
                            <div class="d-flex flex-wrap gap-1">
                                <a href="show.php?id=<?php echo $activity['id']; ?>" class="btn btn-secondary btn-sm">Vezi</a>
                                <?php if (canEditActivity($activity['id'], $pdo)): ?>
                                    <a href="edit.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary btn-sm">Editează</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .activities-section h2 {
        color: #2c3e50;
        margin-bottom: 20px;
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
    .empty-state {
        text-align: center;
        color: #999;
        padding: 40px;
        background: #f9f9f9;
        border-radius: 6px;
    }
    table tbody tr:hover {
        background: #f9f9f9;
    }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
