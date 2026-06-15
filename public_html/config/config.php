<?php

define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH',  ROOT_PATH . '/kesh');
define('BACKUP_PATH', ROOT_PATH . '/zaxira_nusxalari');
define('LOG_PATH',    ROOT_PATH . '/zaxira_nusxalari');

define('INSTALLED_LOCK', __DIR__ . '/installed.lock');
define('INSTALLED', is_file(INSTALLED_LOCK) && is_file(__DIR__ . '/config.local.php'));

$lokal = __DIR__ . '/config.local.php';
if (is_file($lokal)) {
    require_once $lokal;
}

if (!defined('REJIM')) {
    define('REJIM', getenv('APP_REJIM') ?: 'production');
}

if (!defined('SAYT_URL')) {
    $env_url = getenv('SAYT_URL');
    if ($env_url) {
        define('SAYT_URL', rtrim($env_url, '/'));
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                  || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
                  ? 'https' : 'http';
        $host = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
        define('SAYT_URL', $protocol . '://' . $host);
    } else {
        define('SAYT_URL', 'https://vatanparvaryaypan.uz');
    }
}

if (!defined('SAYT_NOMI')) define('SAYT_NOMI', 'VatanParvar Yaypan');

define('SESSION_NOMI',   'VATANPARVAR_SESS');
define('SESSION_VAQTI',  60 * 60 * 24 * 7);
define('CSRF_KALITI',    'csrf_token');
define('LIMIT_VAQT',     60 * 15);
define('LIMIT_SON',      5);
define('TEST_VAQT_DEFAULT', 25 * 60);
define('TEST_SAVOL_SONI',   20);
define('BOT_WEBHOOK_URL',   SAYT_URL . '/bot.php');

if (REJIM === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (is_dir(LOG_PATH) && is_writable(LOG_PATH)) {
        ini_set('error_log', LOG_PATH . '/php_errors.log');
    }
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tashkent');

if (!INSTALLED) {
    $bilan_olinadi = $_SERVER['REQUEST_URI'] ?? '';
    $rooli = parse_url($bilan_olinadi, PHP_URL_PATH) ?: '';
    $ruxsat_etilgan = ['/install.php', '/assets/'];
    $ochiq = false;
    foreach ($ruxsat_etilgan as $r) {
        if (str_starts_with($rooli, $r)) {
            $ochiq = true;
            break;
        }
    }
    if (!$ochiq && PHP_SAPI !== 'cli') {
        if (is_file(__DIR__ . '/../install.php')) {
            header('Location: ' . SAYT_URL . '/install.php');
            exit;
        }
    }
}
