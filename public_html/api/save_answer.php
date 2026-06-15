<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}

if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

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

$boshlangan = strtotime($natija['boshlangan']);
$test_vaqt = (int) sozlama('test_vaqti_minut', 25) * 60;
$qolgan = $test_vaqt - (time() - $boshlangan);
if ($qolgan < 0) $qolgan = 0;

if (post('tugatish') === '1' || $qolgan === 0) {
    $savollar = db_barcha(
        'SELECT id, togri_javob FROM savollar WHERE bilet_id = ?',
        [$natija['bilet_id']]
    );

    $togri = 0;
    $xato = 0;
    foreach ($savollar as $s) {
        if (isset($javoblar[$s['id']])) {
            if ($javoblar[$s['id']] === $s['togri_javob']) {
                $togri++;
            } else {
                $xato++;
            }
        }
    }

    $holat = $qolgan === 0 || post('vaqt_tugadi') === '1' ? 'vaqt_tugadi' : 'tugagan';

    db_bajar(
        'UPDATE natijalar
         SET togri_son = ?, xato_son = ?, umumiy_son = ?, qolgan_vaqt = 0, holat = ?, tugagan = NOW()
         WHERE id = ?',
        [$togri, $xato, count($savollar), $holat, $natija_id]
    );

    json_javob([
        'ok' => true,
        'tugadi' => true,
        'togri' => $togri,
        'xato' => $xato,
        'umumiy' => count($savollar),
    ]);
}

$savol_id = (int) post('savol_id');
$variant = post('variant');

if (!$savol_id || !in_array($variant, ['a', 'b', 'c', 'd'], true)) {
    json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri parametrlar'], 400);
}

$tegishli = db_qiymat(
    'SELECT 1 FROM savollar WHERE id = ? AND bilet_id = ?',
    [$savol_id, $natija['bilet_id']]
);
if (!$tegishli) {
    json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri savol'], 400);
}

$javoblar[$savol_id] = $variant;

db_bajar(
    'UPDATE natijalar SET javoblar_json = ?, qolgan_vaqt = ? WHERE id = ?',
    [json_encode($javoblar, JSON_UNESCAPED_UNICODE), $qolgan, $natija_id]
);

json_javob([
    'ok' => true,
    'qolgan_vaqt' => $qolgan,
    'javoblar_soni' => count($javoblar),
]);
