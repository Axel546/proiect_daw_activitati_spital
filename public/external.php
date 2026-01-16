<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/external_feed.php';

requireLogin();

$pdo = getDbConnection();

// Feed RSS stiro WHO
$feedUrl = 'https://www.who.int/rss-feeds/news-english.xml';
$feedSource = 'who_news';

// gestioneaza refresh cache
$cacheRefreshed = false;
if (isset($_GET['refresh']) && hasRole('admin')) {
    clearFeedCache($pdo, $feedSource);
    $cacheRefreshed = true;
}

// obtine datele feed
$feedData = getHealthNewsFeed($pdo, $feedUrl, $feedSource, 45);
$items = $feedData['items'] ?? [];
$isCached = $feedData['is_cached'] ?? false;
$isStale = $feedData['is_stale'] ?? false;
$fetchFailed = $feedData['fetch_failed'] ?? false;
$cachedAt = $feedData['cached_at'] ?? null;

$pageTitle = 'Știri & Alarme Sănătate - Activități Spital';
ob_start();
?>
<h1>Știri & Alarme Sănătate</h1>
<p class="subtitle">Ultimele știri și alarme de sănătate de la WHO (Organizația Mondială a Sănătății)</p>

<?php if ($cacheRefreshed): ?>
    <div class="success">Cache reîmprospătat cu succes!</div>
<?php endif; ?>

<?php if ($isStale || $fetchFailed): ?>
    <div class="error">
        <strong>Notă:</strong> Nu s-au putut prelua ultimele actualizări. Se afișează date din cache de la 
        <?php echo $cachedAt ? formatDateTime($cachedAt) : 'sesiunea anterioară'; ?>.
    </div>
<?php elseif ($isCached): ?>
    <div class="info" style="margin-bottom: 20px;">
        <strong>Ultima actualizare:</strong> <?php echo $cachedAt ? formatDateTime($cachedAt) : 'Recent'; ?>
    </div>
<?php endif; ?>

<?php if (hasRole('admin')): ?>
    <div style="margin-bottom: 20px;">
        <a href="?refresh=1" class="btn" style="font-size: 14px; padding: 8px 16px;">🔄 Reîmprospătează Cache</a>
    </div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="info">
        <p>Nicio știre de sănătate disponibilă în acest moment. Te rugăm să încerci din nou mai târziu.</p>
    </div>
<?php else: ?>
    <div class="news-items">
        <?php foreach ($items as $item): ?>
            <div class="news-item">
                <div class="news-header">
                    <h3 class="news-title">
                        <a href="<?php echo h($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo h($item['title']); ?>
                        </a>
                    </h3>
                    <?php if ($item['date']): ?>
                        <div class="news-date"><?php echo formatDateTime($item['date']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($item['summary'])): ?>
                    <div class="news-summary">
                        <?php echo nl2br(h($item['summary'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="news-footer">
                    <span class="news-source">Sursă: <?php echo h($item['source_name']); ?></span>
                    <a href="<?php echo h($item['link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="padding: 5px 15px; font-size: 13px;">
                        Citește Mai Mult →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div style="margin-top: 30px;">
    <a href="index.php" class="btn btn-secondary">← Înapoi la Dashboard</a>
</div>

<style>
    .news-items {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .news-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 20px;
        transition: box-shadow 0.2s;
    }
    .news-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .news-header {
        margin-bottom: 15px;
    }
    .news-title {
        font-size: 20px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 8px 0;
    }
    .news-title a {
        color: #2c3e50;
        text-decoration: none;
        transition: color 0.2s;
    }
    .news-title a:hover {
        color: #3498db;
    }
    .news-date {
        color: #999;
        font-size: 13px;
    }
    .news-summary {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    .news-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }
    .news-source {
        color: #999;
        font-size: 12px;
        font-style: italic;
    }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
