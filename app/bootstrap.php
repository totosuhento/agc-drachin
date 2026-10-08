<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$cfgFile = getenv('AGC_CONFIG') ?: ROOT . '/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit("config.php belum ada. Salin config.sample.php menjadi config.php lalu isi.\n");
}
$GLOBALS['CFG'] = require $cfgFile;

date_default_timezone_set($GLOBALS['CFG']['site']['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

require ROOT . '/app/helpers.php';
require ROOT . '/app/db.php';
require ROOT . '/app/repo.php';
