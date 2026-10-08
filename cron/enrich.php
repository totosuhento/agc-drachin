<?php
declare(strict_types=1);
/**
 * Tulis deskripsi unik dengan AI untuk video & series yang belum punya.
 * Jalankan via cron tiap 30 menit:  php /var/www/agc-drama-yt/cron/enrich.php
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';
require ROOT . '/app/AI.php';

if (!cfg('ai.enabled')) {
    exit("AI nonaktif (ai.enabled = false di config.php).\n");
}
$lock = fopen(ROOT . '/storage/enrich.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Proses enrich lain masih berjalan.\n");
}

$limit = (int)cfg('ai.per_run', 30);
$done = 0;

$series = q("SELECT s.id, s.title, s.description, c.title AS channel FROM series s LEFT JOIN channels c ON c.id = s.channel_id
             WHERE s.is_active = 1 AND s.video_count > 1 AND (s.ai_summary IS NULL OR s.ai_summary = '')
             ORDER BY s.video_count DESC LIMIT ?", [max(1, intdiv($limit, 3))])->fetchAll();
foreach ($series as $s) {
    try {
        $txt = AI::describe('series', $s['title'], (string)$s['description'], (string)$s['channel']);
        if ($txt !== '') {
            q('UPDATE series SET ai_summary = ? WHERE id = ?', [$txt, $s['id']]);
            $done++;
        }
    } catch (Throwable $e) {
        logmsg('AI series gagal: ' . $e->getMessage());
        break;
    }
}

$videos = q("SELECT id, title, description, channel_title FROM videos
             WHERE is_active = 1 AND (ai_summary IS NULL OR ai_summary = '')
             ORDER BY views DESC LIMIT ?", [max(0, $limit - $done)])->fetchAll();
foreach ($videos as $v) {
    try {
        $txt = AI::describe('video', $v['title'], (string)$v['description'], (string)$v['channel_title']);
        if ($txt !== '') {
            q('UPDATE videos SET ai_summary = ? WHERE id = ?', [$txt, $v['id']]);
            $done++;
        }
    } catch (Throwable $e) {
        logmsg('AI video gagal: ' . $e->getMessage());
        break;
    }
}

if ($done) {
    cache_clear();
}
logmsg("AI enrich: $done deskripsi ditulis.");
