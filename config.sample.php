<?php
/**
 * AGC Drama YT — konfigurasi.
 * Salin file ini menjadi config.php lalu isi sesuai situs Anda.
 */
return [
    'site' => [
        'name'          => 'ShortDrama Hub',
        'tagline'       => 'Free short dramas from official channels',
        'url'           => 'https://example.com',   // tanpa garis miring di akhir
        'lang'          => 'en',                     // 'en' (CPM tinggi, target US/UK) atau 'id'
        'timezone'      => 'UTC',
        'contact_email' => 'admin@example.com',
        'per_page'      => 24,
        'cache_ttl'     => 600,                      // detik; 0 = matikan cache halaman
    ],

    'youtube' => [
        // Buat API key: console.cloud.google.com → Enable "YouTube Data API v3" → Credentials
        'api_key'  => '',

        // Hanya channel RESMI (centang verifikasi). Boleh @handle atau ID channel (UC...).
        // Cek dulu di YouTube bahwa videonya bisa di-embed.
        'channels' => [
            '@reelshortapp',
            '@dramaboxapp',
        ],

        'min_duration'          => 120,   // detik; video lebih pendek (Shorts/teaser) disembunyikan. 0 = tampilkan semua
        'initial_pages'         => 20,    // halaman (x50 video) saat pertama kali ambil channel
        'pages_per_run'         => 2,     // halaman per cron untuk video baru & backfill video lama
        'series_per_run'        => 20,    // playlist yang disinkronkan per cron
        'playlist_refresh_hours'=> 24,
        'refresh_per_run'       => 1000,  // video lama yang di-refresh per cron (wajib < 30 hari menurut kebijakan YouTube API)
        'daily_quota'           => 9000,  // batas aman (kuota default Google 10.000 unit/hari)
    ],

    // Deskripsi unik buatan AI (opsional, sangat disarankan untuk SEO & approval AdSense)
    'ai' => [
        'enabled'  => false,
        'provider' => 'anthropic',              // 'anthropic' atau 'openai' (endpoint OpenAI-compatible)
        'api_key'  => '',
        'model'    => 'claude-haiku-5-5',       // contoh openai: 'gpt-4o-mini'
        'endpoint' => '',                        // kosongkan untuk default; isi untuk provider OpenAI-compatible lain
        'per_run'  => 30,
    ],

    // Tempel kode iklan (AdSense / Adsterra / dll). Kosongkan slot yang tidak dipakai.
    'ads' => [
        'head'         => '',   // script di <head>, mis. kode auto-ads AdSense
        'top'          => '',
        'below_player' => '',
        'in_list'      => '',   // muncul di tengah grid video
        'sidebar'      => '',
        'footer'       => '',
    ],

    'analytics' => '',          // kode Google Analytics / lainnya (dimasukkan ke <head>)
];
