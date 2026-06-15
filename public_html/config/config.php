<?php

$lokal = __DIR__ . '/config.local.php';
if (is_file($lokal)) {
    require_once $lokal;
}

if (!defined('REJIM'))    define('REJIM', getenv('APP_REJIM') ?: 'production');
if (!defined('SAYT_URL')) define('SAYT_URL', getenv('SAYT_URL') ?: 'https://vatanparvaryaypan.uz');
if (!defined('SAYT_NOMI'))define('SAYT_NOMI', 'VatanParvar Yaypan');

define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH',  ROOT_PATH . '/kesh');
define('BACKUP_PATH', ROOT_PATH . '/zaxira_nusxalari');
define('LOG_PATH',    ROOT_PATH . '/zaxira_nusxalari');

define('SESSION_NOMI', 'VATANPARVAR_SESS');
define('SESSION_VAQTI', 60 * 60 * 24 * 7);

define('CSRF_KALITI', 'csrf_token');

define('LIMIT_VAQT', 60 * 15);
define('LIMIT_SON', 5);

define('TEST_VAQT_DEFAULT', 25 * 60);
define('TEST_SAVOL_SONI', 20);

define('BOT_WEBHOOK_URL', SAYT_URL . '/bot.php');

if (REJIM === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (is_dir(LOG_PATH) && is_writable(LOG_PATH)) {
        ini_set('error_log', LOG_PATH . '/php_errors.log');
    }
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tashkent');
