<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (php_sapi_name() !== 'cli') {
    $kalit = sozlama('cron_kalit', '');
    $kelgan = $_GET['kalit'] ?? '';
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

define('TELEGRAM_SYNC', true);

$boshlangan = microtime(true);
$natija = telegram_navbatni_jonat(50);
$davomiyligi = round((microtime(true) - $boshlangan) * 1000);

echo "Telegram navbat: {$natija['jonatildi']} jonatildi, {$natija['xato']} xato ({$davomiyligi}ms)\n";

$tozalandi = db_bajar(
    'DELETE FROM kirish_urinishlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 6 HOUR)'
);
echo "kirish_urinishlar: {$tozalandi} ta yozuv tozalandi\n";
