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

// Preia activitati cu filtre
$activities = getFilteredActivities($pdo, $filters);
$filterDescription = getFilterDescription($filters, $pdo);

// Preia departamentele pentru filtru
$departments = dbSelectAll($pdo, "SELECT id, name FROM departments ORDER BY name");

// Preia utilizatorii pentru filtru (doar admin)
$users = [];
if (hasRole('admin')) {
    $users = dbSelectAll($pdo, "
        SELECT id, first_name, last_name, username 
        FROM users 
        ORDER BY first_name, last_name, username
    ");
}

// Construieste URL-uri de export cu filtre
$exportParams = http_build_query(array_filter($filters));
$pdfUrl = 'activities_pdf.php?' . $exportParams;

$pageTitle = 'Rapoarte Activități - Activități Spital';
ob_start();
?>
<h1>Rapoarte Activități</h1>
<p class="subtitle">Generează și exportă rapoarte de activități</p>

<div class="form-section" style="margin-bottom: 20px;">
    <h2 style="margin-bottom: 15px;">Filtre</h2>
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
        <div style="flex: 1; min-width: 150px;">
            <label for="department_id">Departament</label>
            <select id="department_id" name="department_id">
                <option value="">Toate Departamentele</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo $filters['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo h($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Toate Statusurile</option>
                <option value="planned" <?php echo $filters['status'] === 'planned' ? 'selected' : ''; ?>>Planificat</option>
                <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>În Progres</option>
                <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completat</option>
                <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Anulat</option>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <label for="date_from">De la Data</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo h($filters['date_from']); ?>">
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <label for="date_to">Până la Data</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo h($filters['date_to']); ?>">
        </div>
        
        <?php if (hasRole('admin') && !empty($users)): ?>
            <div style="flex: 1; min-width: 150px;">
                <label for="created_by">Creator</label>
                <select id="created_by" name="created_by">
                    <option value="">Toți Creatorii</option>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                        $displayName = $name ?: $user['username'];
                        ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filters['created_by'] == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo h($displayName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div>
            <button type="submit">Aplică Filtre</button>
            <a href="activities.php" class="btn btn-secondary" style="margin-left: 10px;">Șterge</a>
        </div>
    </form>
</div>

<div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #1976d2;">
    <strong>Opțiuni Export:</strong>
    <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo h($pdfUrl); ?>" class="btn" target="_blank">📄 Exportă ca PDF</a>
        <a href="activities_csv.php?<?php echo h($exportParams); ?>" class="btn btn-secondary" target="_blank">📋 Exportă ca CSV</a>
    </div>
    <p style="margin-top: 10px; font-size: 13px; color: #666;">
        Filtre curente: <strong><?php echo h($filterDescription); ?></strong> | 
        Rezultate: <strong><?php echo count($activities); ?> activități</strong>
    </p>
</div>

<div class="form-section">
    <h2>Previzualizare (<?php echo count($activities); ?> activități)</h2>
    <?php if (empty($activities)): ?>
        <div class="empty-state">Nicio activitate găsită care să corespundă filtrelor selectate.</div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Titlu</th>
                    <th style="padding: 12px; text-align: left;">Departament</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                    <th style="padding: 12px; text-align: left;">Creată de</th>
                    <th style="padding: 12px; text-align: left;">Creată</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?php echo h($activity['title']); ?></td>
                        <td style="padding: 12px;"><?php echo h($activity['department_name'] ?? 'N/A'); ?></td>
                        <td style="padding: 12px;">
                            <span class="activity-status status-<?php echo h($activity['status']); ?>">
                                <?php echo h(translateStatus($activity['status'])); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <?php 
                            $creator = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
                            echo h($creator ?: $activity['created_by_username'] ?? 'Necunoscut');
                            ?>
                        </td>
                        <td style="padding: 12px;"><?php echo formatDateTime($activity['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($activities) > 10): ?>
            <p style="margin-top: 15px; color: #666; font-size: 13px;">
                Se afișează primele 10 din <?php echo count($activities); ?> activități. Folosește exportul pentru a vedea toate rezultatele.
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div style="margin-top: 20px;">
    <a href="../index.php" class="btn btn-secondary">← Înapoi la Dashboard</a>
</div>

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
