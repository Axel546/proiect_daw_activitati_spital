<?php
require_once __DIR__ . '/../app/src/db.php';
require_once __DIR__ . '/../app/src/helpers.php';
require_once __DIR__ . '/../app/src/auth.php';
require_once __DIR__ . '/../app/src/csrf.php';

requireLogin();

$pdo = getDbConnection();
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($activityId <= 0) {
    header('Location: index.php');
    exit;
}

// Verifica autorizarea
if (!canEditActivity($activityId, $pdo)) {
    header('Location: show.php?id=' . $activityId . '&error=unauthorized');
    exit;
}

$error = null;
$activity = null;

// Preia activitatea
$activity = dbSelectOne($pdo, "
    SELECT 
        a.id,
        a.title,
        a.description,
        a.status,
        a.scheduled_date,
        a.department_id
    FROM activities a
    WHERE a.id = ?
", [$activityId]);

if (!$activity) {
    header('Location: index.php');
    exit;
}

// Gestioneaza POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'planned');
    $scheduled_date = trim($_POST['scheduled_date'] ?? '');
    
    // Validare
    if (empty($title)) {
        $error = 'Titlul este obligatoriu.';
    } elseif (strlen($title) > 200) {
        $error = 'Titlul trebuie să aibă maximum 200 de caractere.';
    } elseif (empty($description)) {
        $error = 'Descrierea este obligatorie.';
    } elseif ($department_id <= 0) {
        $error = 'Te rugăm să selectezi un departament.';
    } elseif (!in_array($status, ['planned', 'in_progress', 'completed', 'cancelled'])) {
        $error = 'Status invalid selectat.';
    } else {
        // Valideaza scheduled_date daca este furnizat
        $scheduled_date_value = null;
        if (!empty($scheduled_date)) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $scheduled_date);
            if ($date === false) {
                $error = 'Format dată programată invalid.';
            } else {
                $scheduled_date_value = $date->format('Y-m-d H:i:s');
            }
        }
        
        if (!$error) {
            $sql = "UPDATE activities SET title = ?, description = ?, department_id = ?, status = ?, scheduled_date = ?, updated_at = NOW() WHERE id = ?";
            $result = dbExecute($pdo, $sql, [$title, $description, $department_id, $status, $scheduled_date_value, $activityId]);
            
            if ($result !== false) {
                // Redirectioneaza pentru a preveni retrimitere formular (pattern PRG)
                header('Location: show.php?id=' . $activityId . '&updated=1');
                exit;
            } else {
                $error = 'Eroare la actualizarea activității. Te rugăm să încerci din nou.';
            }
        }
    }
    
    // Actualiazeaza array-ul cu datele din POST pentru afisare formular
    $activity['title'] = $title;
    $activity['description'] = $description;
    $activity['department_id'] = $department_id;
    $activity['status'] = $status;
    $activity['scheduled_date'] = $scheduled_date;
}

// Formateaza scheduled_date pentru input datetime-local
$scheduled_date_formatted = '';
if ($activity['scheduled_date']) {
    $dt = new DateTime($activity['scheduled_date']);
    $scheduled_date_formatted = $dt->format('Y-m-d\TH:i');
}

// Preia departamentele pentru formular
$departments = dbSelectAll($pdo, "SELECT id, name FROM departments ORDER BY name");

$pageTitle = 'Editează Activitate - Activități Spital';
ob_start();
?>
<h1>Editează Activitate</h1>
<p class="subtitle">Actualizează detaliile activității</p>

<div style="margin-bottom: 20px;">
    <a href="show.php?id=<?php echo $activityId; ?>" class="btn btn-secondary">← Înapoi la Activitate</a>
</div>

<?php if ($error): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="form-section">
    <form method="POST">
        <?php echo csrfField(); ?>
        
        <div class="row g-3">
            <div class="col-12">
                <label for="title" class="form-label">Titlu *</label>
                <input type="text" id="title" name="title" required maxlength="200" 
                       value="<?php echo h($activity['title']); ?>" class="form-control">
            </div>
            
            <div class="col-12">
                <label for="description" class="form-label">Descriere *</label>
                <textarea id="description" name="description" required class="form-control"><?php echo h($activity['description']); ?></textarea>
            </div>
            
            <div class="col-12 col-md-6">
                <label for="department_id" class="form-label">Departament *</label>
                <select id="department_id" name="department_id" required class="form-select">
                    <option value="">Selectează un departament</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" 
                                <?php echo $activity['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo h($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 col-md-6">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="planned" <?php echo $activity['status'] === 'planned' ? 'selected' : ''; ?>>Planificat</option>
                    <option value="in_progress" <?php echo $activity['status'] === 'in_progress' ? 'selected' : ''; ?>>În Progres</option>
                    <option value="completed" <?php echo $activity['status'] === 'completed' ? 'selected' : ''; ?>>Completat</option>
                    <option value="cancelled" <?php echo $activity['status'] === 'cancelled' ? 'selected' : ''; ?>>Anulat</option>
                </select>
            </div>
            
            <div class="col-12">
                <label for="scheduled_date" class="form-label">Data și Ora Programată (Opțional)</label>
                <input type="datetime-local" id="scheduled_date" name="scheduled_date" 
                       value="<?php echo h($scheduled_date_formatted); ?>" class="form-control">
            </div>
        </div>
        
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Actualizează Activitate</button>
            <a href="show.php?id=<?php echo $activityId; ?>" class="btn btn-secondary">Anulează</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../app/views/layout.php';
?>
