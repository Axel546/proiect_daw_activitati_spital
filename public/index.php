<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = getDbConnection();
$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();

//afiseaza mesaje login/logout
$success = null;
if (isset($_GET['logged_in'])) {
    $success = 'Te-ai autentificat cu succes!';
}
if (isset($_GET['logged_out'])) {
    $success = 'Te-ai deconectat cu succes.';
}
if (isset($_GET['registered'])) {
    $success = 'Înregistrare reușită! Ești acum autentificat.';
}

// obtine statistici dashboard
$stats = [];
if ($isLoggedIn) {
    $stats['total_activities'] = dbSelectOne($pdo, "SELECT COUNT(*) as count FROM activities")['count'] ?? 0;
    $stats['my_activities'] = dbSelectOne($pdo, "SELECT COUNT(*) as count FROM activities WHERE created_by = ?", [getCurrentUserId()])['count'] ?? 0;
    $stats['departments'] = dbSelectOne($pdo, "SELECT COUNT(*) as count FROM departments")['count'] ?? 0;
    
    // activitati recente
    $recent_activities = dbSelectAll($pdo, "
        SELECT 
            a.id,
            a.title,
            a.status,
            a.created_at,
            d.name AS department_name
        FROM activities a
        LEFT JOIN departments d ON a.department_id = d.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
} else {
    $recent_activities = [];
}

$pageTitle = 'Dashboard - Managementul Activitatilor Spitalului';
ob_start();
?>
<h1>Managementul Activitatilor Spitalului</h1>
<p class="subtitle">Bun venit<?php echo $isLoggedIn ? ', ' . h($currentUser['first_name'] ?? '') : ''; ?>! Gestionează și urmărește activitățile spitalului pe departamente.</p>

<?php if ($success): ?>
    <div class="success"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($isLoggedIn): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="form-section" style="text-align: center;">
            <h2 style="font-size: 36px; margin: 0; color: #3498db;"><?php echo $stats['total_activities']; ?></h2>
            <p style="margin: 5px 0 0 0; color: #666;">Total Activități</p>
        </div>
        <div class="form-section" style="text-align: center;">
            <h2 style="font-size: 36px; margin: 0; color: #2ecc71;"><?php echo $stats['my_activities']; ?></h2>
            <p style="margin: 5px 0 0 0; color: #666;">Activitățile Mele</p>
        </div>
        <div class="form-section" style="text-align: center;">
            <h2 style="font-size: 36px; margin: 0; color: #e74c3c;"><?php echo $stats['departments']; ?></h2>
            <p style="margin: 5px 0 0 0; color: #666;">Departamente</p>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="activities/index.php" class="btn" style="font-size: 16px; padding: 12px 24px;">Vezi Toate Activitățile</a>
        <?php if (canCreateActivity()): ?>
            <a href="activities/create.php" class="btn" style="font-size: 16px; padding: 12px 24px; margin-left: 10px;">Creează Activitate Nouă</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($recent_activities)): ?>
        <div class="form-section">
            <h2>Activități Recente</h2>
            <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px; text-align: left;">Titlu</th>
                        <th style="padding: 12px; text-align: left;">Departament</th>
                        <th style="padding: 12px; text-align: left; min-width: 120px;">Status</th>
                        <th style="padding: 12px; text-align: left; min-width: 160px;">Creată</th>
                        <th style="padding: 12px; text-align: left; min-width: 100px;">Acțiune</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activities as $activity): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <a href="activities/show.php?id=<?php echo $activity['id']; ?>" style="color: #3498db; text-decoration: none;">
                                    <?php echo h($activity['title']); ?>
                                </a>
                            </td>
                            <td style="padding: 12px;"><?php echo h($activity['department_name'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px;" class="text-nowrap">
                                <span class="activity-status status-<?php echo h($activity['status']); ?>">
                                    <?php echo h(translateStatus($activity['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 12px;" class="text-nowrap"><?php echo formatDateTime($activity['created_at']); ?></td>
                            <td style="padding: 12px;" class="text-nowrap">
                                <a href="activities/show.php?id=<?php echo $activity['id']; ?>" class="btn btn-secondary btn-sm">Vezi</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div style="margin-top: 15px; text-align: center;">
                <a href="activities/index.php" class="btn btn-secondary">Vezi Toate Activitățile →</a>
            </div>
        </div>
    <?php else: ?>
        <div class="info">
            <strong>Nicio activitate încă.</strong> 
            <?php if (canCreateActivity()): ?>
                <a href="activities/create.php" style="color: #1976d2; text-decoration: underline;">Creează prima ta activitate</a>
            <?php else: ?>
                Activitățile vor apărea aici odată ce sunt create.
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="info">
        <strong>Bun venit!</strong> Te rugăm să te <a href="login.php" style="color: #1976d2; text-decoration: underline;">autentifici</a> sau să te 
        <a href="register.php" style="color: #1976d2; text-decoration: underline;">înregistrezi</a> pentru a accesa sistemul de management al activităților spitalului.
    </div>
<?php endif; ?>

<style>
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
    table tbody tr:hover {
        background: #f9f9f9;
    }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
