<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';

requireLogin();
requireRole('admin');

$pdo = getDbConnection();

// Obtine ID mesaj pentru detalii
$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($messageId > 0) {
    // Afiseaza detalii 1 mesaj
    $message = dbSelectOne($pdo, "
        SELECT 
            cm.id,
            cm.name,
            cm.email,
            cm.subject,
            cm.message,
            cm.created_at,
            cm.user_id,
            u.username,
            u.first_name,
            u.last_name
        FROM contact_messages cm
        LEFT JOIN users u ON cm.user_id = u.id
        WHERE cm.id = ?
    ", [$messageId]);
    
    if (!$message) {
        header('Location: messages.php');
        exit;
    }
    
    $pageTitle = 'Mesaj Contact - Panou Administrator';
    ob_start();
    ?>
    <h1>Detalii Mesaj Contact</h1>
    <p class="subtitle">Vizualizează trimiterea din formularul de contact</p>
    
    <div style="margin-bottom: 20px;">
        <a href="messages.php" class="btn btn-secondary">← Înapoi la Mesaje</a>
    </div>
    
    <div class="form-section">
        <div class="form-group">
            <label>Nume</label>
            <div style="padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <?php echo h($message['name']); ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <div style="padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <a href="mailto:<?php echo h($message['email']); ?>"><?php echo h($message['email']); ?></a>
            </div>
        </div>
        
        <div class="form-group">
            <label>Subiect</label>
            <div style="padding: 10px; background: #f9f9f9; border-radius: 4px; font-weight: 500;">
                <?php echo h($message['subject']); ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Mesaj</label>
            <div style="padding: 15px; background: #f9f9f9; border-radius: 4px; white-space: pre-wrap; line-height: 1.6;">
                <?php echo h($message['message']); ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Trimis</label>
            <div style="padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <?php echo formatDateTime($message['created_at'], 'Y-m-d H:i:s'); ?>
            </div>
        </div>
        
        <?php if ($message['user_id']): ?>
            <div class="form-group">
                <label>Cont Utilizator</label>
                <div style="padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <?php 
                    $userName = trim(($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? ''));
                    echo h($userName ?: $message['username'] ?? 'Necunoscut');
                    ?>
                    (ID: <?php echo $message['user_id']; ?>)
                </div>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Cont Utilizator</label>
                <div style="padding: 10px; background: #f9f9f9; border-radius: 4px; color: #999;">
                    Vizitator (neautentificat)
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../../views/layout.php';
    exit;
}

// Listeaza toate mesajele
$messages = dbSelectAll($pdo, "
    SELECT 
        cm.id,
        cm.name,
        cm.email,
        cm.subject,
        LEFT(cm.message, 100) as message_preview,
        cm.created_at,
        cm.user_id,
        u.username,
        u.first_name,
        u.last_name
    FROM contact_messages cm
    LEFT JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC
");

$pageTitle = 'Mesaje Contact - Panou Administrator';
ob_start();
?>
<h1>Mesaje Contact</h1>
<p class="subtitle">Vizualizează trimiterile din formularul de contact</p>

<div style="margin-bottom: 20px;">
    <a href="../index.php" class="btn btn-secondary">← Înapoi la Dashboard</a>
</div>

<?php if (empty($messages)): ?>
    <div class="info">
        <p>Niciun mesaj de contact încă.</p>
    </div>
<?php else: ?>
    <div class="form-section">
        <h2>Toate Mesajele (<?php echo count($messages); ?>)</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Data</th>
                    <th style="padding: 12px; text-align: left;">Nume</th>
                    <th style="padding: 12px; text-align: left;">Email</th>
                    <th style="padding: 12px; text-align: left;">Subiect</th>
                    <th style="padding: 12px; text-align: left;">Previzualizare</th>
                    <th style="padding: 12px; text-align: left;">Utilizator</th>
                    <th style="padding: 12px; text-align: left;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; font-size: 12px; color: #666;">
                            <?php echo formatDateTime($msg['created_at']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo h($msg['name']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <a href="mailto:<?php echo h($msg['email']); ?>" style="color: #3498db;">
                                <?php echo h($msg['email']); ?>
                            </a>
                        </td>
                        <td style="padding: 12px; font-weight: 500;">
                            <?php echo h($msg['subject']); ?>
                        </td>
                        <td style="padding: 12px; color: #666; font-size: 13px; max-width: 200px;">
                            <?php echo h($msg['message_preview']); ?><?php echo strlen($msg['message_preview']) >= 100 ? '...' : ''; ?>
                        </td>
                        <td style="padding: 12px; font-size: 12px; color: #666;">
                            <?php if ($msg['user_id']): ?>
                                <?php 
                                $userName = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                                echo h($userName ?: $msg['username'] ?? 'Utilizator');
                                ?>
                            <?php else: ?>
                                <span style="color: #999;">Vizitator</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <a href="messages.php?id=<?php echo $msg['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Vezi</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
    table tbody tr:hover {
        background: #f9f9f9;
    }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
