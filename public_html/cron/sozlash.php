<?php
/**
 * Telegram bot vebhukini o'rnatish (faqat developer ishga tushiradi)
 *
 * URL: https://vatanparvaryaypan.uz/cron/sozlash.php?kalit=...
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
require_once __DIR__ . '/../includes/transliterator.php';

$kalit = sozlama('cron_kalit', '');
$kelgan = $_GET['kalit'] ?? '';
if (!$kalit || !hash_equals($kalit, $kelgan)) {
    http_response_code(403);
    exit('Forbidden');
}

$harakat = $_GET['harakat'] ?? '';

// ----- Til migratsiyasi: mavjud qatorlardagi _cyrl ustunlarni to'ldirish -----
// (Bot tokeniga bog'liq emas, shu sababli yuqorida)
if ($harakat === 'til_migratsiya') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🔤 Til migratsiyasi — _cyrl ustunlarini to'ldirish</h1><pre>";

    $jadvallar = [
        // [jadval, [maydon_lat => maydon_cyrl, ...], where shart]
        ['biletlar', ['nomi' => 'nomi_cyrl', 'tavsif' => 'tavsif_cyrl'], '(nomi_cyrl IS NULL OR nomi_cyrl = "")'],
        ['savollar', [
            'matn' => 'matn_cyrl',
            'variant_a' => 'variant_a_cyrl',
            'variant_b' => 'variant_b_cyrl',
            'variant_c' => 'variant_c_cyrl',
            'variant_d' => 'variant_d_cyrl',
            'izoh' => 'izoh_cyrl',
        ], '(matn_cyrl IS NULL OR matn_cyrl = "")'],
        ['tariflar', ['nomi' => 'nomi_cyrl', 'tavsif' => 'tavsif_cyrl'], '(nomi_cyrl IS NULL OR nomi_cyrl = "")'],
        ['fikrlar',  ['ism' => 'ism_cyrl', 'matn' => 'matn_cyrl'], '(matn_cyrl IS NULL OR matn_cyrl = "")'],
    ];

    $jami_yangilangan = 0;
    foreach ($jadvallar as [$jadval, $xarita, $shart]) {
        $maydonlar_lat = array_keys($xarita);
        $maydon_ro_yxat = implode(', ', array_map(fn($m) => "`$m`", ['id', ...$maydonlar_lat]));
        $qatorlar = db_barcha("SELECT $maydon_ro_yxat FROM `$jadval` WHERE $shart");
        echo "📋 [$jadval] — " . count($qatorlar) . " ta qator topildi\n";

        foreach ($qatorlar as $q) {
            $set = [];
            $params = [];
            foreach ($xarita as $lat => $cyrl) {
                $qiymat = (string) ($q[$lat] ?? '');
                $set[] = "`$cyrl` = ?";
                $params[] = $qiymat === '' ? null : lotin_dan_kirill($qiymat);
            }
            $params[] = $q['id'];
            db_bajar(
                "UPDATE `$jadval` SET " . implode(', ', $set) . " WHERE id = ?",
                $params
            );
            $jami_yangilangan++;
        }
    }

    // sozlamalar — sayt_nomi va sayt_shior uchun maxsus
    foreach (['sayt_nomi', 'sayt_shior'] as $kalit_nom) {
        $latn = sozlama($kalit_nom, '');
        $cyrl_kalit = $kalit_nom . '_cyrl';
        $cyrl_mavjud = sozlama($cyrl_kalit, '');
        if ($latn && !$cyrl_mavjud) {
            sozlama_saqla($cyrl_kalit, lotin_dan_kirill($latn));
            echo "⚙️ sozlamalar.$cyrl_kalit yangilandi\n";
            $jami_yangilangan++;
        }
    }

    echo "\n✅ Migratsiya tugadi. Jami $jami_yangilangan ta qator yangilandi.";
    echo "</pre>";
    exit;
}

$token = sozlama('telegram_bot_token');

if (!$token) exit('Telegram bot tokeni belgilanmagan');

// ----- Vebhukni o'rnatish -----
if ($harakat === 'webhook_set') {
    $url = SAYT_URL . '/bot.php';
    $j = file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url));
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

// ----- Vebhukni o'chirish -----
if ($harakat === 'webhook_del') {
    $j = file_get_contents("https://api.telegram.org/bot{$token}/deleteWebhook");
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

// ----- Bot info -----
if ($harakat === 'me') {
    $j = file_get_contents("https://api.telegram.org/bot{$token}/getMe");
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

echo "<h1>Bot sozlash</h1>";
echo "<ul>";
echo "<li><a href='?kalit={$kelgan}&harakat=webhook_set'>Vebhukni o'rnatish</a></li>";
echo "<li><a href='?kalit={$kelgan}&harakat=webhook_del'>Vebhukni o'chirish</a></li>";
echo "<li><a href='?kalit={$kelgan}&harakat=me'>Bot info</a></li>";
echo "<li><a href='?kalit={$kelgan}&harakat=til_migratsiya'>🔤 Til migratsiyasi (mavjud qatorlarning kirill versiyasini yaratish)</a></li>";
echo "</ul>";
