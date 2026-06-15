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

$tugagan = db_bajar('UPDATE obunalar SET holat = "tugagan" WHERE holat = "faol" AND tugash <= NOW()');

$obunalar = db_barcha(
    'SELECT o.*, fo.telegram_id, fo.ism, t.nomi
     FROM obunalar o
     JOIN foydalanuvchilar fo ON o.foydalanuvchi_id = fo.id
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.holat = "faol"
       AND o.tugash > NOW()
       AND o.tugash <= DATE_ADD(NOW(), INTERVAL 3 DAY)
       AND fo.telegram_id IS NOT NULL'
);

$jonatildi = 0;
foreach ($obunalar as $o) {
    $kun = (int) ((strtotime($o['tugash']) - time()) / 86400);
    if ($kun < 0) continue;

    telegram_yubor($o['telegram_id'],
        "⏰ <b>Obuna tugayapti!</b>\n\n" .
        "Salom, " . htmlspecialchars($o['ism'], ENT_QUOTES) . "!\n" .
        "Tarifingiz <b>{$o['nomi']}</b> {$kun} kun ichida tugaydi (" . date('d.m.Y', strtotime($o['tugash'])) . ").\n\n" .
        "Yangilash uchun: " . SAYT_URL . "/tolov"
    );
    $jonatildi++;
}

echo "Tugatildi: {$tugagan} ta obuna\n";
echo "Yuborildi: {$jonatildi} ta eslatma\n";
