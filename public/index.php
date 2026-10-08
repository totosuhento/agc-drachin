<?php
declare(strict_types=1);

// Server bawaan PHP (php -S) untuk uji lokal: biarkan file statis dilayani langsung
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';
require ROOT . '/app/controllers.php';

$path = '/' . trim(rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')), '/');
$isGet = in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true);
$cacheable = $isGet && $path !== '/search';
$key = md5($path . '?' . ($_SERVER['QUERY_STRING'] ?? ''));

if ($cacheable && ($hit = cache_get($key)) !== null) {
    header('Content-Type: ' . $hit['type']);
    header('X-Cache: HIT');
    echo $hit['body'];
    exit;
}

$routes = [
    '#^/$#'                                  => 'page_home',
    '#^/video/([a-z0-9-]{1,120})$#'          => 'page_video',
    '#^/series/([a-z0-9-]{1,120})$#'         => 'page_series',
    '#^/series$#'                            => 'page_series_list',
    '#^/channel/([a-z0-9-]{1,120})$#'        => 'page_channel',
    '#^/(latest|popular)$#'                  => 'page_listing',
    '#^/search$#'                            => 'page_search',
    '#^/(about|privacy|disclaimer|contact)$#' => 'page_static',
    '#^/sitemap\.xml$#'                      => 'sitemap_index',
    '#^/sitemap-(pages|series|videos)-(\d+)\.xml$#' => 'sitemap_part',
    '#^/robots\.txt$#'                       => 'robots_txt',
];

$res = null;
try {
    foreach ($routes as $re => $handler) {
        if (preg_match($re, $path, $m)) {
            $res = $handler(...array_slice($m, 1));
            break;
        }
    }
    $res ??= not_found();
} catch (Throwable $e) {
    error_log('[agc] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $res = ['status' => 500, 'type' => 'text/html; charset=utf-8', 'body' => view('layout', [
        'title' => t('error_title'), 'content' => '<div class="empty"><h1>' . e(t('error_title')) . '</h1></div>', 'noindex' => true,
    ])];
}

http_response_code($res['status'] ?? 200);
header('Content-Type: ' . $res['type']);
if (($res['status'] ?? 200) === 200 && $cacheable) {
    cache_put($key, $res['type'], $res['body']);
}
echo $res['body'];
