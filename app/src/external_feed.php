<?php
/**
 * Integrare Feed Extern
 * Preia si analizeaza stiri
 */

/**
 * Preia feed RSS si analizeaza
 * 
 * @param string $url URL feed RSS
 * @return array|false Elemente feed analizate sau false la eroare
 */
function fetchRssFeed($url) {
    // foloseste file_get_contents cu context pentru gestionare erori
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Hospital Activities Management System/1.0',
            'follow_redirects' => true,
            'max_redirects' => 3
        ]
    ]);
    
    $xmlContent = @file_get_contents($url, false, $context);
    
    if ($xmlContent === false) {
        return false;
    }
    
    // supress erori si analizeaza
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($xmlContent);
    
    if ($xml === false) {
        return false;
    }
    
    $items = [];
    $sourceName = (string)($xml->channel->title ?? 'Sursă Necunoscută');
    
    // analiza elemente rss
    foreach ($xml->channel->item as $item) {
        $title = (string)($item->title ?? '');
        $link = (string)($item->link ?? '');
        $description = (string)($item->description ?? '');
        $pubDate = (string)($item->pubDate ?? '');
        
        // analiza data
        $date = null;
        if ($pubDate) {
            $timestamp = strtotime($pubDate);
            if ($timestamp !== false) {
                $date = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        // curatare descriere (elimina tag-uri HTML, limiteaza lungimea)
        $summary = strip_tags($description);
        $summary = html_entity_decode($summary, ENT_QUOTES, 'UTF-8');
        if (strlen($summary) > 300) {
            $summary = substr($summary, 0, 300) . '...';
        }
        
        if (!empty($title)) {
            $items[] = [
                'title' => $title,
                'date' => $date,
                'summary' => $summary,
                'source_name' => $sourceName,
                'link' => $link
            ];
        }
    }
    
    return $items;
}

/**
 * obtine date feed cache
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $feedSource sursa
 * @return array|false Date din cache sau false daca nu sunt gasite/expirate
 */
function getCachedFeed($pdo, $feedSource) {
    require_once __DIR__ . '/helpers.php';
    
    $cache = dbSelectOne($pdo, "
        SELECT cached_data, expires_at, cached_at 
        FROM external_feed_cache 
        WHERE feed_source = ? AND expires_at > NOW()
    ", [$feedSource]);
    
    if ($cache) {
        $data = json_decode($cache['cached_data'], true);
        if ($data !== null) {
            return [
                'items' => $data,
                'cached_at' => $cache['cached_at'],
                'is_cached' => true
            ];
        }
    }
    
    return false;
}

/**
 * salveaza in cache
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $feedSource sursa
 * @param string $feedUrl URL feed
 * @param array $items Elemente feed
 * @param int $cacheMinutes Durata cache in minute - implicit am pus 45 minute
 * @return bool Succes
 */
function saveFeedCache($pdo, $feedSource, $feedUrl, $items, $cacheMinutes = 45) {
    require_once __DIR__ . '/helpers.php';
    
    $cachedData = json_encode($items);
    $expiresAt = date('Y-m-d H:i:s', time() + ($cacheMinutes * 60));
    
    $sql = "INSERT INTO external_feed_cache (feed_source, feed_url, cached_data, expires_at) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                feed_url = VALUES(feed_url),
                cached_data = VALUES(cached_data),
                cached_at = NOW(),
                expires_at = VALUES(expires_at)";
    
    return dbExecute($pdo, $sql, [$feedSource, $feedUrl, $cachedData, $expiresAt]) !== false;
}

/**
 * obtine feed stiri (cu cache)
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $feedUrl URL feed
 * @param string $feedSource sursa
 * @param int $cacheMinutes durata cache minute
 * @return array Date feed cu elemente si status cache
 */
function getHealthNewsFeed($pdo, $feedUrl, $feedSource = 'who_news', $cacheMinutes = 45) {
    // incearca sa obtina din cache mai intai
    $cached = getCachedFeed($pdo, $feedSource);
    if ($cached !== false) {
        return $cached;
    }
    
    // Preia date noi
    $items = fetchRssFeed($feedUrl);
    
    if ($items === false || empty($items)) {
        // daca preluarea a esuat, incearca sa returneze cache expirat ca fallback
        require_once __DIR__ . '/helpers.php';
        $expiredCache = dbSelectOne($pdo, "
            SELECT cached_data, cached_at 
            FROM external_feed_cache 
            WHERE feed_source = ?
            ORDER BY cached_at DESC
            LIMIT 1
        ", [$feedSource]);
        
        if ($expiredCache) {
            $data = json_decode($expiredCache['cached_data'], true);
            if ($data !== null) {
                return [
                    'items' => $data,
                    'cached_at' => $expiredCache['cached_at'],
                    'is_cached' => true,
                    'is_stale' => true, // indica folosirea cache-ului expirat
                    'fetch_failed' => true
                ];
            }
        }
        
        // Nu este disponibil cache, returnează gol
        return [
            'items' => [],
            'is_cached' => false,
            'fetch_failed' => true
        ];
    }
    
    // limiteaza la ultimele 10 elemente
    $items = array_slice($items, 0, 10);
    
    // salveaza în cache
    saveFeedCache($pdo, $feedSource, $feedUrl, $items, $cacheMinutes);
    
    return [
        'items' => $items,
        'is_cached' => false,
        'fetch_failed' => false
    ];
}

/**
 * sterge cache feed (pentru manual refresh)
 * 
 * @param PDO $pdo Conexiune la baza de date
 * @param string $feedSource sursa 
 * @return bool Succes
 */
function clearFeedCache($pdo, $feedSource = null) {
    require_once __DIR__ . '/helpers.php';
    
    if ($feedSource) {
        return dbExecute($pdo, "DELETE FROM external_feed_cache WHERE feed_source = ?", [$feedSource]) !== false;
    } else {
        return dbExecute($pdo, "DELETE FROM external_feed_cache", []) !== false;
    }
}
