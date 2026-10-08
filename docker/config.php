<?php
/**
 * Konfigurasi versi Docker: semua diisi lewat Environment Variables di panel hosting.
 * Ingin kontrol penuh? Buat storage/config.php (format sama dengan config.sample.php) — file itu yang akan dipakai.
 * Kode iklan bisa diisi lewat env (ADS_TOP, dst.) atau file storage/ads/top.html, below_player.html, dst.
 */
if (is_file(__DIR__ . '/storage/config.php')) {
    return require __DIR__ . '/storage/config.php';
}

$env = static function (string $key, $default = '') {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};
$flag = static fn(string $key, bool $default): bool =>
    in_array(strtolower((string)$env($key, $default ? '1' : '0')), ['1', 'true', 'yes', 'on'], true);
$snippet = static function (string $key, string $file) use ($env): string {
    $path = __DIR__ . '/storage/ads/' . $file;
    return (string)$env($key, is_file($path) ? (string)file_get_contents($path) : '');
};

return [
    'site' => [
        'name'          => $env('SITE_NAME', 'ShortDrama Hub'),
        'tagline'       => $env('SITE_TAGLINE', ''),
        'url'           => rtrim((string)$env('SITE_URL', 'http://localhost'), '/'),
        'lang'          => $env('SITE_LANG', 'en'),
        'niche'         => $env('SITE_NICHE', ''),
        'item'          => $env('SITE_ITEM', ''),
        'timezone'      => $env('SITE_TIMEZONE', 'UTC'),
        'contact_email' => $env('CONTACT_EMAIL', 'admin@example.com'),
        'per_page'      => (int)$env('PER_PAGE', 24),
        'cache_ttl'     => (int)$env('CACHE_TTL', 600),
    ],
    'youtube' => [
        'api_key'                => $env('YOUTUBE_API_KEY'),
        'channels'               => array_values(array_filter(array_map('trim',
                                        explode(',', (string)$env('YOUTUBE_CHANNELS', '@reelshortapp,@dramaboxapp'))))),
        'include_keywords'       => array_values(array_filter(array_map('trim', explode(',', (string)$env('YOUTUBE_INCLUDE', ''))))),
        'exclude_keywords'       => array_values(array_filter(array_map('trim', explode(',', (string)$env('YOUTUBE_EXCLUDE', ''))))),
        'min_duration'           => (int)$env('MIN_DURATION', 120),
        'initial_pages'          => (int)$env('INITIAL_PAGES', 20),
        'pages_per_run'          => 2,
        'series_per_run'         => 20,
        'playlist_refresh_hours' => 24,
        'refresh_per_run'        => (int)$env('REFRESH_PER_RUN', 1000),
        'daily_quota'            => (int)$env('DAILY_QUOTA', 9000),
    ],
    'ai' => [
        'enabled'  => $flag('AI_ENABLED', false),
        'provider' => $env('AI_PROVIDER', 'anthropic'),
        'api_key'  => $env('AI_API_KEY'),
        'model'    => $env('AI_MODEL', 'claude-haiku-5-5'),
        'endpoint' => $env('AI_ENDPOINT'),
        'per_run'  => (int)$env('AI_PER_RUN', 30),
    ],
    'ads' => [
        'head'         => $snippet('ADS_HEAD', 'head.html'),
        'top'          => $snippet('ADS_TOP', 'top.html'),
        'below_player' => $snippet('ADS_BELOW_PLAYER', 'below_player.html'),
        'in_list'      => $snippet('ADS_IN_LIST', 'in_list.html'),
        'sidebar'      => $snippet('ADS_SIDEBAR', 'sidebar.html'),
        'footer'       => $snippet('ADS_FOOTER', 'footer.html'),
    ],
    'analytics' => $snippet('ANALYTICS', 'analytics.html'),
    'legal' => [
        'name'    => $env('LEGAL_NAME', ''),
        'address' => $env('LEGAL_ADDRESS', ''),
    ],
];
