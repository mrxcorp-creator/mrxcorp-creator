<?php
/**
 * AvtoTest Pro — Obuna tugashidan oldin eslatma
 *
 * Cron (har kuni 09:00 da):
 *   0 9 * * * /usr/bin/php /home/USER/public_html/cron/obuna_eslatma.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (php_sapi_name() !== 'cli') {
    $kalit  = sozlama('cron_kalit', '');
    $kelgan = $_GET['kalit'] ?? '';
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// 1. Muddati o'tgan obunalarni "tugagan" ga o'tkazish
$tugangan = db_bajar(
    'UPDATE obunalar SET holat = "tugagan"
     WHERE holat = "faol" AND tugash <= NOW()'
);

// 2. 3 kun qolganlarga Telegram eslatma
$yaqin = db_barcha(
    'SELECT o.*, fo.telegram_id, fo.ism, t.nomi AS tarif_nomi
     FROM obunalar o
     JOIN foydalanuvchilar fo ON o.foydalanuvchi_id = fo.id
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.holat = "faol"
       AND o.tugash > NOW()
       AND o.tugash <= DATE_ADD(NOW(), INTERVAL 3 DAY)
       AND fo.telegram_id IS NOT NULL'
);

$yuborildi = 0;
foreach ($yaqin as $o) {
    $kun = max(0, (int) ((strtotime($o['tugash']) - time()) / 86400));
    $ism = htmlspecialchars($o['ism'], ENT_QUOTES, 'UTF-8');

    $xabar = "⏰ <b>Obuna tugayapti!</b>\n\n"
           . "Salom, <b>{$ism}</b>!\n"
           . "Tarifingiz <b>{$o['tarif_nomi']}</b> "
           . ($kun === 0 ? "bugun tugaydi" : "{$kun} kun ichida tugaydi")
           . " (" . date('d.m.Y', strtotime($o['tugash'])) . ").\n\n"
           . "Yangilash uchun: " . SAYT_URL . "/tolov";

    if (telegram_yubor((int)$o['telegram_id'], $xabar)) {
        $yuborildi++;
    }
}

echo "✅ Tugagan obunalar: {$tugangan} ta\n";
echo "📱 Yuborilgan eslatmalar: {$yuborildi} ta\n";
