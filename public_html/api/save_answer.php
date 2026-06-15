<?php
/**
 * VatanParvar Yaypan — Test javoblarini saqlash (AJAX API)
 *
 * BUG FIX (timer): qolgan_vaqt endi har bir javobda yangilanmaydi.
 * Qolgan vaqt her doim: max(0, natija.qolgan_vaqt - (time() - boshlangan))
 * Natija.qolgan_vaqt = test boshlanganidagi to'liq vaqt (o'zgarmas).
 *
 * Ikki rejim:
 *   1) Bitta javobni saqlash: natija_id + savol_id + variant
 *   2) Testni tugatish:       natija_id + tugatish=1
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

// Faqat POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}

// CSRF tekshiruvi
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

// Auth
$f = joriy_foydalanuvchi();
if (!$f) {
    json_javob(['ok' => false, 'xato' => t('avval_kiring')], 401);
}

$natija_id = (int) post('natija_id');

$natija = db_qator(
    'SELECT * FROM natijalar WHERE id = ? AND foydalanuvchi_id = ?',
    [$natija_id, $f['id']]
);

if (!$natija) {
    json_javob(['ok' => false, 'xato' => t('malumot_yoq')], 404);
}

if ($natija['holat'] !== 'davom') {
    json_javob(['ok' => false, 'xato' => 'Test allaqachon yakunlangan'], 400);
}

$javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];

// BUG FIX: Qolgan vaqtni boshlangan dan hisoblash (qolgan_vaqt = initial duration)
$qolgan = max(0, (int)$natija['qolgan_vaqt'] - (time() - strtotime($natija['boshlangan'])));

// ================================================================
// REJIM 1: Testni tugatish
// ================================================================
if (post('tugatish') === '1' || $qolgan === 0) {
    $savollar = db_barcha(
        'SELECT id, togri_javob FROM savollar WHERE bilet_id = ?',
        [$natija['bilet_id']]
    );

    $togri = 0;
    $xato  = 0;
    foreach ($savollar as $s) {
        if (!empty($javoblar[$s['id']])) {
            if ($javoblar[$s['id']] === $s['togri_javob']) {
                $togri++;
            } else {
                $xato++;
            }
        }
    }

    $vaqt_tugadi = post('vaqt_tugadi') === '1' || $qolgan === 0;
    $holat       = $vaqt_tugadi ? 'vaqt_tugadi' : 'tugagan';

    db_bajar(
        'UPDATE natijalar
         SET togri_son = ?, xato_son = ?, umumiy_son = ?,
             holat = ?, tugagan = NOW()
         WHERE id = ?',
        [$togri, $xato, count($savollar), $holat, $natija_id]
    );

    json_javob([
        'ok'     => true,
        'tugadi' => true,
        'togri'  => $togri,
        'xato'   => $xato,
        'umumiy' => count($savollar),
        'foiz'   => count($savollar) > 0 ? round($togri / count($savollar) * 100) : 0,
    ]);
}

// ================================================================
// REJIM 2: Bitta javobni saqlash
// ================================================================
$savol_id = (int) post('savol_id');
$variant  = post('variant');

if (!$savol_id || !in_array($variant, ['a','b','c','d'], true)) {
    json_javob(['ok' => false, 'xato' => "Noto'g'ri parametrlar"], 400);
}

// Savol ushbu biletga tegishlimi?
$tegishli = db_qiymat(
    'SELECT 1 FROM savollar WHERE id = ? AND bilet_id = ?',
    [$savol_id, $natija['bilet_id']]
);
if (!$tegishli) {
    json_javob(['ok' => false, 'xato' => "Noto'g'ri savol"], 400);
}

$javoblar[$savol_id] = $variant;

// BUG FIX: faqat javoblar_json yangilanadi, qolgan_vaqt O'ZGARTIRILMAYDI
db_bajar(
    'UPDATE natijalar SET javoblar_json = ? WHERE id = ?',
    [json_encode($javoblar, JSON_UNESCAPED_UNICODE), $natija_id]
);

json_javob([
    'ok'           => true,
    'qolgan_vaqt'  => $qolgan,
    'javoblar_soni'=> count($javoblar),
]);
