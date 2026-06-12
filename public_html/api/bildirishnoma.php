<?php
/**
 * Bildirishnomalar API.
 *
 * GET  ?action=ruyhat   — oxirgi 30 ta
 * POST action=oqildi    — barchasini oqilgan deb belgilash
 * POST action=ochirish, id=X — bittasini o'chirish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$f = joriy_foydalanuvchi();
if (!$f) {
    json_javob(['ok' => false, 'xato' => t('avval_kiring')], 401);
}

$action = $_REQUEST['action'] ?? 'ruyhat';

// ----- Ro'yxat -----
if ($action === 'ruyhat') {
    $r = db_barcha(
        'SELECT * FROM bildirishnomalar
         WHERE foydalanuvchi_id = ?
         ORDER BY yaratilgan DESC LIMIT 30',
        [$f['id']]
    );
    $natija = [];
    foreach ($r as $b) {
        $natija[] = [
            'id'       => (int) $b['id'],
            'sarlavha' => $b['sarlavha'],
            'matn'     => $b['matn'],
            'link'     => $b['link'],
            'tur'      => $b['tur'],
            'ikon'     => $b['ikon'] ?: '🔔',
            'oqilgan'  => (int) $b['oqilgan'],
            'vaqt'     => vaqt_oldin($b['yaratilgan']),
        ];
    }
    json_javob([
        'ok'        => true,
        'ruyhat'    => $natija,
        'oqilmagan' => bildirishnoma_son($f['id']),
        'chat_oqilmagan' => chat_oqilmagan_son($f['id']),
    ]);
}

// CSRF talab qilinadi
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

// ----- Hammasini oqilgan deb belgilash -----
if ($action === 'oqildi') {
    $id = (int) post('id');
    if ($id) {
        db_bajar(
            'UPDATE bildirishnomalar SET oqilgan = 1 WHERE id = ? AND foydalanuvchi_id = ?',
            [$id, $f['id']]
        );
    } else {
        db_bajar(
            'UPDATE bildirishnomalar SET oqilgan = 1 WHERE foydalanuvchi_id = ?',
            [$f['id']]
        );
    }
    json_javob(['ok' => true]);
}

// ----- O'chirish -----
if ($action === 'ochirish') {
    $id = (int) post('id');
    if ($id) {
        db_bajar(
            'DELETE FROM bildirishnomalar WHERE id = ? AND foydalanuvchi_id = ?',
            [$id, $f['id']]
        );
    } else {
        db_bajar(
            'DELETE FROM bildirishnomalar WHERE foydalanuvchi_id = ? AND oqilgan = 1',
            [$f['id']]
        );
    }
    json_javob(['ok' => true]);
}

json_javob(['ok' => false, 'xato' => 'Noma\'lum action'], 400);
