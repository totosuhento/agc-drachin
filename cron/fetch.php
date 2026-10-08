<?php
declare(strict_types=1);
/**
 * Ambil & perbarui video dari channel resmi.
 * Jalankan via cron tiap jam:  php /var/www/agc-drama-yt/cron/fetch.php
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';
require ROOT . '/app/YouTube.php';
require ROOT . '/app/Fetcher.php';

$lock = fopen(ROOT . '/storage/fetch.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Proses fetch lain masih berjalan.\n");
}

$yt = new YouTube((string)cfg('youtube.api_key', ''));
try {
    (new Fetcher($yt))->run();
} catch (QuotaExceeded $e) {
    logmsg('Berhenti, kuota habis: ' . $e->getMessage());
} catch (Throwable $e) {
    logmsg('ERROR: ' . $e->getMessage());
}
cache_clear();
logmsg('Selesai. Unit API terpakai run ini: ' . $yt->units() . ' | total hari ini: ' . quota_used());
