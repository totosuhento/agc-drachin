<?php
declare(strict_types=1);
/** Ringkasan isi database & pemakaian kuota:  php cron/status.php */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$row = fn(string $sql) => (int)q($sql)->fetchColumn();
printf("Channel            : %d\n", $row('SELECT COUNT(*) FROM channels'));
printf("Video tampil       : %d\n", $row('SELECT COUNT(*) FROM videos WHERE is_active = 1'));
printf("Video disembunyikan: %d (Shorts/tidak bisa di-embed/dihapus)\n", $row('SELECT COUNT(*) FROM videos WHERE is_active = 0'));
printf("Series tampil      : %d\n", $row('SELECT COUNT(*) FROM series WHERE is_active = 1 AND video_count > 1'));
printf("Deskripsi AI       : %d video, %d series\n",
    $row("SELECT COUNT(*) FROM videos WHERE is_active = 1 AND ai_summary <> ''"),
    $row("SELECT COUNT(*) FROM series WHERE is_active = 1 AND ai_summary <> ''"));
printf("Kuota API hari ini : %d / %d unit\n", quota_used(), (int)cfg('youtube.daily_quota', 9000));
foreach (q('SELECT title, backfill_state FROM channels')->fetchAll() as $c) {
    printf("  - %s: %s\n", $c['title'], ['belum diambil', 'backfill berjalan', 'lengkap'][(int)$c['backfill_state']] ?? '?');
}
