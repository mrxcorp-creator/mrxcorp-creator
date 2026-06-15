<?php
/**
 * AvtoTest Pro — Foydalanuvchi fikr qoldirish AJAX API
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

// Faqat POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}

// CSRF
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

$f    = joriy_foydalanuvchi();
$matn = trim(post('matn'));
$baho = max(1, min(5, (int) post('baho') ?: 5));

// Ism: kirgan foydalanuvchi uchun avtomatik, mehmon uchun POST dan
if ($f) {
    $ism = trim($f['ism'] . ' ' . ($f['familiya'] ?? ''));
} else {
    $ism = trim(post('ism'));
}

// Validatsiya
if (!$ism || mb_strlen($ism) < 2) {
    json_javob(['ok' => false, 'xato' => t('kerakli_maydon')], 400);
}
if (mb_strlen($matn) < 5 || mb_strlen($matn) > 1000) {
    json_javob(['ok' => false, 'xato' => "Fikr 5–1000 belgi orasida bo'lishi kerak"], 400);
}

// Rate limit: bir foydalanuvchi/IP 24 soatda 3 ta fikr
$ip = ip_olish();
$kun_ichida = (int) db_qiymat(
    'SELECT COUNT(*) FROM fikrlar
     WHERE (foydalanuvchi_id = ? OR fikr_ip = ?)
       AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)',
    [$f['id'] ?? 0, $ip]
);

if ($kun_ichida >= 3) {
    json_javob(['ok' => false, 'xato' => '24 soat ichida faqat 3 ta fikr qoldirish mumkin'], 429);
}

db_bajar(
    'INSERT INTO fikrlar (foydalanuvchi_id, ism, matn, baho, tasdiq, fikr_ip)
     VALUES (?, ?, ?, ?, 0, ?)',
    [$f['id'] ?? null, $ism, $matn, $baho, $ip]
);

json_javob([
    'ok'    => true,
    'xabar' => "Fikringiz uchun rahmat! Admin tasdiqlagandan so'ng paydo bo'ladi.",
]);
