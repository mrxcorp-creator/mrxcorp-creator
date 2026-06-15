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

db()->beginTransaction();
try {
    $natija = db_qator(
        'SELECT * FROM natijalar WHERE id = ? AND foydalanuvchi_id = ? FOR UPDATE',
        [$natija_id, $f['id']]
    );

    if (!$natija) {
        db()->rollBack();
        json_javob(['ok' => false, 'xato' => t('malumot_yoq')], 404);
    }

    if ($natija['holat'] !== 'davom') {
        db()->rollBack();
        json_javob(['ok' => false, 'xato' => 'Test allaqachon yakunlangan'], 400);
    }

    $boshlangan = strtotime($natija['boshlangan']);
    $test_vaqt = (int) sozlama('test_vaqti_minut', 25) * 60;
    $qolgan = $test_vaqt - (time() - $boshlangan);
    if ($qolgan < 0) $qolgan = 0;

    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];

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

        $holat = ($qolgan === 0 || post('vaqt_tugadi') === '1') ? 'vaqt_tugadi' : 'tugagan';

        db_bajar(
            'UPDATE natijalar
             SET togri_son = ?, xato_son = ?, umumiy_son = ?, qolgan_vaqt = 0,
                 holat = ?, tugagan = NOW(), versiya = versiya + 1
             WHERE id = ? AND holat = "davom"',
            [$togri, $xato, count($savollar), $holat, $natija_id]
        );

        db()->commit();
        json_javob([
            'ok' => true,
            'tugadi' => true,
            'togri' => $togri,
            'xato' => $xato,
            'umumiy' => count($savollar),
        ]);
    }

    $savol_id = (int) post('savol_id');
    $variant  = post('variant');
    $kelgan_versiya = (int) post('versiya');

    if (!$savol_id || !in_array($variant, ['a', 'b', 'c', 'd'], true)) {
        db()->rollBack();
        json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri parametrlar'], 400);
    }

    $tegishli = db_qiymat(
        'SELECT 1 FROM savollar WHERE id = ? AND bilet_id = ?',
        [$savol_id, $natija['bilet_id']]
    );
    if (!$tegishli) {
        db()->rollBack();
        json_javob(['ok' => false, 'xato' => 'Noto\'g\'ri savol'], 400);
    }

    if ($kelgan_versiya > 0 && $kelgan_versiya < (int) $natija['versiya']) {
        db()->rollBack();
        json_javob([
            'ok' => false,
            'xato' => 'Versiya eski',
            'serverdagi_versiya' => (int) $natija['versiya'],
            'serverdagi_javoblar' => $javoblar,
        ], 409);
    }

    $javoblar[$savol_id] = $variant;

    db_bajar(
        'UPDATE natijalar
         SET javoblar_json = ?, qolgan_vaqt = ?, versiya = versiya + 1
         WHERE id = ? AND versiya = ?',
        [
            json_encode($javoblar, JSON_UNESCAPED_UNICODE),
            $qolgan,
            $natija_id,
            (int) $natija['versiya'],
        ]
    );

    db()->commit();

    json_javob([
        'ok' => true,
        'qolgan_vaqt' => $qolgan,
        'javoblar_soni' => count($javoblar),
        'versiya' => (int) $natija['versiya'] + 1,
    ]);

} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    error_log('save_answer xato: ' . $e->getMessage());
    json_javob(['ok' => false, 'xato' => 'Server xatosi'], 500);
}
