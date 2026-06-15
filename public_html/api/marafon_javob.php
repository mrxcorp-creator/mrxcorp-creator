<?php
require_once __DIR__ . '/../config/auth.php';

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

$id = (int) post('id');
$marafon = db_qator('SELECT * FROM marafonlar WHERE id = ? AND foydalanuvchi_id = ?', [$id, $f['id']]);
if (!$marafon || $marafon['holat'] !== 'davom') {
    json_javob(['ok' => false, 'xato' => 'Marafon topilmadi'], 404);
}

$boshlangan_t = strtotime($marafon['boshlangan']);
$qolgan = 50 * 60 - (time() - $boshlangan_t);

if (post('yakunla') === '1' || $qolgan <= 0) {
    $javoblar = json_decode($marafon['javoblar_json'] ?? '{}', true) ?: [];
    $savol_idlar = json_decode($marafon['savollar_json'] ?? '[]', true) ?: [];

    $togri = 0;
    $xato = 0;
    if (!empty($savol_idlar)) {
        $placeholder = implode(',', array_fill(0, count($savol_idlar), '?'));
        $togri_javoblar = db_barcha(
            "SELECT id, togri_javob FROM savollar WHERE id IN ($placeholder)",
            $savol_idlar
        );
        $tj_xarita = [];
        foreach ($togri_javoblar as $tj) $tj_xarita[(int) $tj['id']] = $tj['togri_javob'];

        foreach ($savol_idlar as $sid) {
            if (!isset($javoblar[$sid])) continue;
            if ($javoblar[$sid] === ($tj_xarita[$sid] ?? null)) {
                $togri++;
            } else {
                $xato++;
            }
        }
    }

    $holat = $qolgan <= 0 ? 'vaqt_tugadi' : 'tugagan';
    db_bajar(
        "UPDATE marafonlar SET togri_son = ?, xato_son = ?, holat = ?, tugagan = NOW()
         WHERE id = ? AND holat = 'davom'",
        [$togri, $xato, $holat, $id]
    );

    audit_yoz('marafon_yakunlandi', 'marafon', $id, [
        'togri' => $togri, 'xato' => $xato, 'foiz' => round($togri / 50 * 100),
    ]);

    json_javob([
        'ok' => true,
        'tugadi' => true,
        'togri' => $togri,
        'xato' => $xato,
    ]);
}

$savol_id = (int) post('savol_id');
$variant = post('variant');
if (!$savol_id || !in_array($variant, ['a', 'b', 'c', 'd'], true)) {
    json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri parametrlar'], 400);
}

$savol_idlar = json_decode($marafon['savollar_json'] ?? '[]', true) ?: [];
if (!in_array($savol_id, $savol_idlar)) {
    json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri savol'], 400);
}

$javoblar = json_decode($marafon['javoblar_json'] ?? '{}', true) ?: [];
$javoblar[$savol_id] = $variant;

db_bajar(
    'UPDATE marafonlar SET javoblar_json = ? WHERE id = ?',
    [json_encode($javoblar, JSON_UNESCAPED_UNICODE), $id]
);

json_javob(['ok' => true, 'qolgan' => $qolgan]);
