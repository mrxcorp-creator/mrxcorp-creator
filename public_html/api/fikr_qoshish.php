<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi fikr qoldirish AJAX API
 * BUG FIX: fikr_ip ustuni mavjud bo'lmasa graceful fallback
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

$f    = joriy_foydalanuvchi();
$matn = trim(post('matn'));
$baho = max(1, min(5, (int) post('baho') ?: 5));
$ip   = ip_olish();

$ism = $f
    ? trim($f['ism'] . ' ' . ($f['familiya'] ?? ''))
    : trim(post('ism'));

if (!$ism || mb_strlen($ism) < 2) {
    json_javob(['ok' => false, 'xato' => t('kerakli_maydon')], 400);
}
if (mb_strlen($matn) < 5 || mb_strlen($matn) > 1000) {
    json_javob(['ok' => false, 'xato' => 'Fikr 5–1000 belgi orasida bo\'lishi kerak'], 400);
}

// Rate limit — 24 soatda 3 ta fikr
try {
    // fikr_ip ustuni bor yoki yo'q — ikkalasini sinab ko'ramiz
    $kun_ichida = (int) db_qiymat(
        'SELECT COUNT(*) FROM fikrlar
         WHERE (foydalanuvchi_id = ? OR fikr_ip = ?)
           AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)',
        [$f['id'] ?? 0, $ip]
    );
} catch (Throwable) {
    // Eski schema — fikr_ip ustuni yo'q
    $kun_ichida = (int) db_qiymat(
        'SELECT COUNT(*) FROM fikrlar
         WHERE foydalanuvchi_id = ?
           AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)',
        [$f['id'] ?? 0]
    );
}

if ($kun_ichida >= 3) {
    json_javob(['ok' => false, 'xato' => '24 soat ichida faqat 3 ta fikr qoldirish mumkin'], 429);
}

// Fikrni yozish — fikr_ip ustuniga graceful fallback
try {
    db_bajar(
        'INSERT INTO fikrlar (foydalanuvchi_id, ism, matn, baho, tasdiq, fikr_ip) VALUES (?, ?, ?, ?, 0, ?)',
        [$f['id'] ?? null, $ism, $matn, $baho, $ip]
    );
} catch (Throwable) {
    // fikr_ip ustuni yo'q — eskicha usul
    db_bajar(
        'INSERT INTO fikrlar (foydalanuvchi_id, ism, matn, baho, tasdiq) VALUES (?, ?, ?, ?, 0)',
        [$f['id'] ?? null, $ism, $matn, $baho]
    );
}

json_javob([
    'ok'    => true,
    'xabar' => "Fikringiz uchun rahmat! Admin tasdiqlagandan so'ng ko'rinadi.",
]);
