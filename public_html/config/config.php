<?php
/**
 * VatanParvar Yaypan — Global konstantalar
 * ------------------------------------------------------------
 * Barcha global sozlamalar shu yerda joylashadi.
 */

// Ishga tushirish rejimi: 'production' yoki 'development'
define('REJIM', 'production');

// Sayt asosiy URL'i
define('SAYT_URL', 'https://vatanparvaryaypan.uz');
define('SAYT_NOMI', 'VatanParvar Yaypan');

// Asosiy yo'llar
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/kesh');
define('BACKUP_PATH', ROOT_PATH . '/zaxira_nusxalari');

// Sessiya parametrlari
define('SESSION_NOMI', 'VATANPARVAR_SESS');
define('SESSION_VAQTI', 60 * 60 * 24 * 7); // 7 kun

// CSRF token
define('CSRF_KALITI', 'csrf_token');

// Rate limit (15 daqiqada 5 ta xato urinish)
define('LIMIT_VAQT', 60 * 15);
define('LIMIT_SON', 5);

// Test parametrlari
define('TEST_VAQT_DEFAULT', 25 * 60); // 25 daqiqa
define('TEST_SAVOL_SONI', 20);

// Bot
define('BOT_WEBHOOK_URL', SAYT_URL . '/bot.php');

// Xato sozlamalari
if (REJIM === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL); // Hammasini qo'lga olamiz, lekin display=0
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/loglar/php_errors.log');
}

// Loglar papkasi
if (!defined('LOG_PATH')) {
    define('LOG_PATH', ROOT_PATH . '/loglar');
}

// Install holati — install.lock fayli bo'lmasa, install.php ga yo'naltiriladi
define('INSTALL_LOCK', ROOT_PATH . '/install.lock');
define('TIZIM_ORNATILGAN', file_exists(INSTALL_LOCK));

// UTF-8 majburiy
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tashkent');
