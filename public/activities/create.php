<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/csrf.php';

requireLogin();

if (!canCreateActivity()) {
    header('Location: index.php?error=unauthorized');
    exit;
}

$pdo = getDbConnection();
$error = null;

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
                $error = 'Format-ul pentru data este invalid.';
            } else {
                $scheduled_date_value = $date->format('Y-m-d H:i:s');
            }
        }
        
        if (!$error) {
            $created_by = getCurrentUserId();
            
            $sql = "INSERT INTO activities (title, description, department_id, created_by, status, scheduled_date) VALUES (?, ?, ?, ?, ?, ?)";
            $result = dbInsert($pdo, $sql, [$title, $description, $department_id, $created_by, $status, $scheduled_date_value]);
            
            if ($result !== false) {
                // Redirectioneaza pentru a preveni retrimitere formular (PRG)
                header('Location: show.php?id=' . $result);
                exit;
            } else {
                $error = 'Eroare la crearea activității. Te rugăm să încerci din nou.';
            }
        }
    }
}

// Departamente pentru formular
$departments = dbSelectAll($pdo, "SELECT id, name FROM departments ORDER BY name");

$pageTitle = 'Creează Activitate - Activități Spital';
ob_start();
?>
<h1>Creează Activitate Nouă</h1>
<p class="subtitle">Adaugă o activitate nouă în spital</p>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">← Înapoi la Activități</a>
</div>

<?php if ($error): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="form-section">
    <form method="POST">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="title">Titlu *</label>
            <input type="text" id="title" name="title" required maxlength="200" 
                   value="<?php echo h($_POST['title'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Descriere *</label>
            <textarea id="description" name="description" required><?php echo h($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="department_id">Departament *</label>
            <select id="department_id" name="department_id" required>
                <option value="">Selectează un departament</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" 
                            <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo h($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="planned" <?php echo (($_POST['status'] ?? 'planned') === 'planned') ? 'selected' : ''; ?>>Planificat</option>
                <option value="in_progress" <?php echo (($_POST['status'] ?? '') === 'in_progress') ? 'selected' : ''; ?>>În Progres</option>
                <option value="completed" <?php echo (($_POST['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completat</option>
                <option value="cancelled" <?php echo (($_POST['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Anulat</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="scheduled_date">Data și Ora Programată (Opțional)</label>
            <input type="datetime-local" id="scheduled_date" name="scheduled_date" 
                   value="<?php echo h($_POST['scheduled_date'] ?? ''); ?>">
        </div>
        
        <button type="submit">Creează Activitate</button>
        <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">Anulează</a>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
