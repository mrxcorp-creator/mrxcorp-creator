<?php
/**
 * AvtoTest Pro — Obuna eslatma + DB cleanup
 *
 * YANGI: kirish_urinishlar jadvali 30 kunlik tozalash.
 * MySQL EVENT_SCHEDULER yoqilmagan hosting'lar uchun
 * bu cron muqobil yechim hisoblanadi.
 *
 * Cron (har kuni 09:00):
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

$boshlangan = microtime(true);

// ── 1. Muddati o'tgan obunalarni "tugagan" ga o'tkazish ────
$tugangan = db_bajar(
    'UPDATE obunalar SET holat = "tugagan"
     WHERE holat = "faol" AND tugash <= NOW()'
);
echo "✅ Tugangan obunalar: {$tugangan} ta\n";

// ── 2. 3 kun qolganlarga Telegram eslatma ──────────────────
$yaqin = db_barcha(
    'SELECT o.id, o.tugash, o.tarif_id,
            fo.telegram_id, fo.ism,
            t.nomi AS tarif_nomi
     FROM obunalar o
     JOIN foydalanuvchilar fo ON o.foydalanuvchi_id = fo.id
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.holat = "faol"
       AND o.tugash > NOW()
       AND o.tugash <= DATE_ADD(NOW(), INTERVAL 3 DAY)
       AND fo.telegram_id IS NOT NULL'
);

$yuborildi = 0;
foreach ($yaqin as $ob) {
    $kun  = max(0, (int) ((strtotime($ob['tugash']) - time()) / 86400));
    $ism  = htmlspecialchars($ob['ism'], ENT_QUOTES, 'UTF-8');
    $rang = $kun === 0 ? '🔴' : ($kun <= 1 ? '🟠' : '🟡');

    $xabar = "⏰ <b>Obuna tugayapti!</b>\n\n"
           . "Salom, <b>{$ism}</b>!\n"
           . "Tarifingiz <b>{$ob['tarif_nomi']}</b> "
           . ($kun === 0 ? "bugun tugaydi!" : "{$rang} <b>{$kun} kun</b> ichida tugaydi.")
           . "\n📅 " . date('d.m.Y', strtotime($ob['tugash']))
           . "\n\n♻️ Yangilash: " . SAYT_URL . '/tolov';

    if (telegram_yubor((int)$ob['telegram_id'], $xabar)) {
        $yuborildi++;
    }

    // So'rovlar orasida kichik pauza (Telegram rate limit)
    usleep(100_000); // 0.1 soniya
}
echo "📱 Yuborilgan eslatmalar: {$yuborildi} ta\n";

// ── 3. kirish_urinishlar tozalash ──────────────────────────
// MySQL EVENT_SCHEDULER yoqilmagan bo'lsa bu muhim!
$tozalangan = db_bajar(
    'DELETE FROM kirish_urinishlar
     WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 30 DAY)'
);
echo "🗑️  Eski kirish urinishlari o'chirildi: {$tozalangan} ta\n";

// ── 4. Eski kesh fayllarni tozalash ────────────────────────
if (is_dir(CACHE_PATH)) {
    $eski_kesh = 0;
    foreach (glob(CACHE_PATH . '/*.html') ?: [] as $fayl) {
        // 2 soatdan eski kesh fayllarni o'chirish
        if (filemtime($fayl) < time() - 7200) {
            @unlink($fayl);
            $eski_kesh++;
        }
    }
    if ($eski_kesh > 0) {
        echo "🧹 Eski kesh fayllar: {$eski_kesh} ta o'chirildi\n";
    }
}

$vaqt = round(microtime(true) - $boshlangan, 3);
echo "\n⏱️  Jami vaqt: {$vaqt}s\n";
