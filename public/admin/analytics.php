<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/auth.php';

requireLogin();
requireRole('admin');

$pdo = getDbConnection();

// Obtine statistici
$stats = [];

// Total vizualizari pagini azi
$stats['views_today'] = dbSelectOne($pdo, "
    SELECT COUNT(*) as count 
    FROM analytics_page_views 
    WHERE DATE(created_at) = CURDATE()
")['count'] ?? 0;

// Total vizualizari pagini last 7 days
$stats['views_7days'] = dbSelectOne($pdo, "
    SELECT COUNT(*) as count 
    FROM analytics_page_views 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")['count'] ?? 0;

// Vizitatori unici azi (ip_hash unic)
$stats['visitors_today'] = dbSelectOne($pdo, "
    SELECT COUNT(DISTINCT ip_hash) as count 
    FROM analytics_page_views 
    WHERE DATE(created_at) = CURDATE()
")['count'] ?? 0;

// Vizitatori unici last 7 days
$stats['visitors_7days'] = dbSelectOne($pdo, "
    SELECT COUNT(DISTINCT ip_hash) as count 
    FROM analytics_page_views 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")['count'] ?? 0;

// Top 10 cele mai vizitate pagini (last 7 days)
$topPages = dbSelectAll($pdo, "
    SELECT 
        path,
        COUNT(*) as views,
        COUNT(DISTINCT ip_hash) as unique_visitors
    FROM analytics_page_views 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY path
    ORDER BY views DESC
    LIMIT 10
");

$pageTitle = 'Analitică - Panou Administrator';
ob_start();
?>
<h1>Website Analytics</h1>
<p class="subtitle">Statistici vizualizări pagini și vizitatori</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="form-section" style="text-align: center;">
        <h2 style="font-size: 36px; margin: 0; color: #3498db;"><?php echo number_format($stats['views_today']); ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Vizualizări Pagini Azi</p>
    </div>
    
    <div class="form-section" style="text-align: center;">
        <h2 style="font-size: 36px; margin: 0; color: #2ecc71;"><?php echo number_format($stats['views_7days']); ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Vizualizări Pagini (Ultimele 7 Zile)</p>
    </div>
    
    <div class="form-section" style="text-align: center;">
        <h2 style="font-size: 36px; margin: 0; color: #e74c3c;"><?php echo number_format($stats['visitors_today']); ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Vizitatori Unici Azi</p>
    </div>
    
    <div class="form-section" style="text-align: center;">
        <h2 style="font-size: 36px; margin: 0; color: #f39c12;"><?php echo number_format($stats['visitors_7days']); ?></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Vizitatori Unici (Ultimele 7 Zile)</p>
    </div>
</div>

<div class="form-section">
    <h2>Top 10 Cele Mai Vizitate Pagini (Ultimele 7 Zile)</h2>
    <?php if (empty($topPages)): ?>
        <div class="empty-state">Nicio vizualizare de pagină înregistrată încă.</div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Rank</th>
                    <th style="padding: 12px; text-align: left;">Path</th>
                    <th style="padding: 12px; text-align: right;">Total Vizualizări</th>
                    <th style="padding: 12px; text-align: right;">Vizitatori Unici</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($topPages as $page): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; font-weight: 600; color: #666;"><?php echo $rank++; ?></td>
                        <td style="padding: 12px;">
                            <code style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-size: 13px;">
                                <?php echo h($page['path']); ?>
                            </code>
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: 500;">
                            <?php echo number_format($page['views']); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; color: #666;">
                            <?php echo number_format($page['unique_visitors']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="margin-top: 20px;">
    <a href="../index.php" class="btn btn-secondary">← Înapoi la Dashboard</a>
</div>

<style>
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
