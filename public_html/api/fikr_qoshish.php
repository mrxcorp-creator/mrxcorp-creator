<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi fikr qoldirish API
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

$f = joriy_foydalanuvchi();
$ism = $f ? ($f['ism'] . ' ' . ($f['familiya'] ?? '')) : trim(post('ism'));
$matn = trim(post('matn'));
$baho = max(1, min(5, (int) post('baho') ?: 5));

if (!$ism || mb_strlen($matn) < 5) {
    json_javob(['ok' => false, 'xato' => t('kerakli_maydon')], 400);
}

db_bajar(
    'INSERT INTO fikrlar (foydalanuvchi_id, ism, matn, baho, tasdiq) VALUES (?, ?, ?, ?, 0)',
    [$f['id'] ?? null, $ism, $matn, $baho]
);

json_javob([
    'ok' => true,
    'xabar' => 'Fikringiz uchun rahmat! Admin tasdiqlagandan so\'ng paydo bo\'ladi.',
]);
