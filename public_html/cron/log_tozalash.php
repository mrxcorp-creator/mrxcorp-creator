<?php
/**
 * Cron: kundalik log va eski yozuvlarni tozalash.
 *
 * cPanel cron jobiga qo'shing (kuniga 1 marta, soat 03:00):
 *
 *   /usr/local/bin/php  /home/USERNAME/public_html/cron/log_tozalash.php
 *
 * Yoki HTTP orqali (himoya kalit bilan):
 *
 *   GET /cron/log_tozalash.php?kalit=SOZLAMALARDAGI_CRON_KALIT
 */

// CLI yoki HTTP — ikkala variantda ham ishlaydi
$cli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/log.php';

if (!$cli) {
    // HTTP orqali kelgan bo'lsa, kalitni tekshiramiz
    $kelgan = $_GET['kalit'] ?? '';
    $kalit  = (string) sozlama('cron_kalit', '');
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$boshla = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Tozalash boshlandi\n";

// ---------- 1. Eski log fayllar (30 kun) ----------
try {
    $n = log_tozalash(30);
    echo "  ✓ Log fayllar: $n ta o'chirildi (30+ kun)\n";
} catch (Throwable $e) {
    echo "  ✗ Log fayllar tozalashda xato: " . $e->getMessage() . "\n";
}

// ---------- 2. kirish_urinishlar (90 kun) ----------
try {
    $n = db_bajar('DELETE FROM kirish_urinishlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 90 DAY)');
    echo "  ✓ kirish_urinishlar: $n ta yozuv o'chirildi (90+ kun)\n";
} catch (Throwable $e) {
    echo "  ✗ kirish_urinishlar tozalashda xato: " . $e->getMessage() . "\n";
}

// ---------- 3. xavfsizlik_hodisalar (180 kun) ----------
try {
    $n = db_bajar('DELETE FROM xavfsizlik_hodisalar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 180 DAY)');
    echo "  ✓ xavfsizlik_hodisalar: $n ta yozuv o'chirildi (180+ kun)\n";
} catch (Throwable $e) {
    // Jadval bo'lmasligi mumkin (eski install) — sukut
}

// ---------- 4. bloklangan_iplar — muddati o'tganlari ----------
try {
    $n = db_bajar('DELETE FROM bloklangan_iplar WHERE tugash IS NOT NULL AND tugash < NOW()');
    echo "  ✓ bloklangan_iplar: $n ta muddati o'tgan blok o'chirildi\n";
} catch (Throwable $e) {}

// ---------- 5. Tugagan obunalar holati yangilanishi ----------
try {
    $n = db_bajar('UPDATE obunalar SET holat = "tugagan" WHERE holat = "faol" AND tugash < NOW()');
    echo "  ✓ obunalar: $n ta obuna 'tugagan' qilindi\n";
} catch (Throwable $e) {}

// ---------- 6. Eski hujum sayqal fayllari ----------
$sayqal_papka = __DIR__ . '/../loglar/sayqallar';
if (is_dir($sayqal_papka)) {
    $och = 0;
    foreach (glob($sayqal_papka . '/*.json') ?: [] as $f) {
        if (filemtime($f) < time() - 7 * 86400) {
            @unlink($f) && $och++;
        }
    }
    echo "  ✓ Sayqal fayllari: $och ta eski fayl o'chirildi (7+ kun)\n";
}

$vaqt = round((microtime(true) - $boshla) * 1000);
echo "[" . date('Y-m-d H:i:s') . "] Tozalash tugadi ({$vaqt}ms)\n";
log_info('cron/log_tozalash', 'Tozalash bajarildi', ['vaqt_ms' => $vaqt]);
