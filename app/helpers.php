<?php
declare(strict_types=1);

function cfg(string $path, mixed $default = null): mixed
{
    $v = $GLOBALS['CFG'];
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim((string)cfg('site.url'), '/') . '/' . ltrim($path, '/');
}

function t(string $key, mixed ...$args): string
{
    static $L = null;
    if ($L === null) {
        $en = require ROOT . '/app/lang/en.php';
        $f = ROOT . '/app/lang/' . preg_replace('/[^a-z]/', '', (string)cfg('site.lang', 'en')) . '.php';
        $L = is_file($f) ? array_merge($en, require $f) : $en;
    }
    $s = $L[$key] ?? $key;
    return $args ? vsprintf($s, $args) : $s;
}

function slugify(string $text, int $max = 80): string
{
    $t = $text;
    if (function_exists('transliterator_transliterate')) {
        $t = (string)transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $t);
    } else {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        $t = strtolower($conv !== false ? $conv : $t);
    }
    $t = trim((string)preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    if (strlen($t) > $max) {
        $t = rtrim(substr($t, 0, $max), '-');
    }
    return $t !== '' ? $t : 'video';
}

/** Slug unik & stabil: judul + 6 karakter hash ID. */
function make_slug(string $title, string $id): string
{
    return slugify($title) . '-' . substr(md5($id), 0, 6);
}

function iso_duration_to_seconds(string $iso): int
{
    if (!preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $iso, $m)) {
        return 0;
    }
    return (int)($m[1] ?? 0) * 86400 + (int)($m[2] ?? 0) * 3600 + (int)($m[3] ?? 0) * 60 + (int)($m[4] ?? 0);
}

function seconds_to_iso(int $s): string
{
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    return 'PT' . ($h ? $h . 'H' : '') . ($m ? $m . 'M' : '') . ($s % 60) . 'S';
}

function fmt_duration(int $s): string
{
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    return $h ? sprintf('%d:%02d:%02d', $h, $m, $s % 60) : sprintf('%d:%02d', $m, $s % 60);
}

function fmt_number(int $n): string
{
    return match (true) {
        $n >= 1_000_000_000 => round($n / 1e9, 1) . 'B',
        $n >= 1_000_000     => round($n / 1e6, 1) . 'M',
        $n >= 1_000         => round($n / 1e3, 1) . 'K',
        default             => (string)$n,
    };
}

function fmt_date(?string $d): string
{
    if (!$d || ($ts = strtotime($d)) === false) {
        return '';
    }
    $months = explode(',', t('months'));
    return date('j', $ts) . ' ' . ($months[(int)date('n', $ts) - 1] ?? date('M', $ts)) . ' ' . date('Y', $ts);
}

/** Bersihkan deskripsi YouTube: hapus link, hashtag, dan baris promosi kosong. */
function clean_description(?string $d): string
{
    $lines = [];
    foreach (preg_split('/\R/u', (string)$d) ?: [] as $line) {
        // Baris promosi pendek yang berisi link ("Download app: https://…") dibuang seluruhnya
        if (preg_match('~https?://~u', $line) && mb_strlen(trim((string)preg_replace('~https?://\S+~u', '', $line))) < 40) {
            continue;
        }
        $lines[] = $line;
    }
    $d = implode("\n", $lines);
    $d = preg_replace('~https?://\S+~u', '', $d);
    $d = preg_replace('/(^|\s)#[\p{L}\p{N}_]+/u', '$1', (string)$d);
    $d = preg_replace("/[ \t]+/u", ' ', (string)$d);
    $d = preg_replace("/\n{3,}/u", "\n\n", (string)$d);
    return trim((string)$d);
}

function excerpt(?string $text, int $len = 160): string
{
    $t = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

function paragraphs(string $text): string
{
    $out = '';
    foreach (preg_split("/\n\s*\n/u", trim($text)) ?: [] as $p) {
        if (trim($p) !== '') {
            $out .= '<p>' . nl2br(e(trim($p))) . '</p>';
        }
    }
    return $out;
}

function yt_thumb(string $id, string $quality = 'hqdefault'): string
{
    return 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/' . $quality . '.jpg';
}

function view(string $__view, array $__data = []): string
{
    extract($__data, EXTR_SKIP);
    ob_start();
    require ROOT . '/app/views/' . $__view . '.php';
    return (string)ob_get_clean();
}

function ad(string $slot): string
{
    $code = (string)cfg('ads.' . $slot, '');
    return $code !== '' ? '<div class="ad ad-' . e($slot) . '">' . $code . '</div>' : '';
}

function json_ld(array $data): string
{
    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        . '</script>';
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function logmsg(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    if (PHP_SAPI === 'cli') {
        echo $line;
    }
    @mkdir(ROOT . '/storage/logs', 0775, true);
    @file_put_contents(ROOT . '/storage/logs/cron.log', $line, FILE_APPEND);
}

/* ---------- Cache halaman (file) ---------- */

function cache_path(string $key): string
{
    return ROOT . '/storage/cache/' . substr($key, 0, 2) . '/' . $key . '.cache';
}

/** @return array{type:string,body:string}|null */
function cache_get(string $key): ?array
{
    $ttl = (int)cfg('site.cache_ttl', 0);
    $f = cache_path($key);
    if ($ttl <= 0 || !is_file($f) || filemtime($f) < time() - $ttl) {
        return null;
    }
    $raw = (string)@file_get_contents($f);
    $pos = strpos($raw, "\n");
    return $pos === false ? null : ['type' => substr($raw, 0, $pos), 'body' => substr($raw, $pos + 1)];
}

function cache_put(string $key, string $type, string $body): void
{
    if ((int)cfg('site.cache_ttl', 0) <= 0) {
        return;
    }
    $f = cache_path($key);
    @mkdir(dirname($f), 0775, true);
    @file_put_contents($f . '.tmp', $type . "\n" . $body);
    @rename($f . '.tmp', $f);
}

function cache_clear(): void
{
    $dir = ROOT . '/storage/cache';
    foreach (glob($dir . '/*/*.cache') ?: [] as $f) {
        @unlink($f);
    }
}
