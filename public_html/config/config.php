<?php
/**
 * AvtoTest Pro — Global konstantalar
 * ------------------------------------------------------------
 * Barcha global sozlamalar shu yerda joylashadi.
 */

// Ishga tushirish rejimi: 'production' | 'development'
define('REJIM', 'production');

// Sayt asosiy URL (oxirida / bo'lmasin)
define('SAYT_URL',  'https://avtotestpro.uz');
define('SAYT_NOMI', 'AvtoTest Pro');
define('SAYT_TAVSIF', "O'zbekistonda avto maktab nazariyasiga eng tezkor onlayn tayyorgarlik platformasi");

// Asosiy yo'llar
define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH',  ROOT_PATH . '/kesh');
define('BACKUP_PATH', ROOT_PATH . '/zaxira_nusxalari');

// Sessiya parametrlari
define('SESSION_NOMI', 'AVTOTESTPRO_SESS');
define('SESSION_VAQTI', 60 * 60 * 24 * 14); // 14 kun

// CSRF token sessiya kaliti
define('CSRF_KALITI', '_csrf_token');

// Rate limit — 15 daqiqada 5 ta xato
define('LIMIT_VAQT', 60 * 15);
define('LIMIT_SON',  5);

// Test standart parametrlari (DB sozlamalardan ham o'qiladi)
define('TEST_VAQT_DEFAULT', 25 * 60); // 25 daqiqa (soniyada)
define('TEST_SAVOL_SONI',   20);

// Qo'llab-quvvatlanadigan tillar
define('TILLAR', ['uz_latn', 'uz_cyrl', 'ru']);
define('TIL_DEFAULT', 'uz_latn');

// Xato sozlamalari
if (REJIM === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/zaxira_nusxalari/php_errors.log');
}

// UTF-8 va vaqt zonasi
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tashkent');
