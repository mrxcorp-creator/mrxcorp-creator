<?php
/**
 * Chat xabarlarni olish (foydalanuvchi tomonidan polling).
 *
 * GET parametrlar:
 *   - oxirgi_id: oxirgi olingan xabar ID (faqat shundan keyingilarni qaytarish)
 *   - kor: 1 bo'lsa, kelgan xabarlarni "oqilgan" deb belgilash
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$f = joriy_foydalanuvchi();
if (!$f) {
    json_javob(['ok' => false, 'xato' => t('avval_kiring')], 401);
}

$oxirgi_id = (int) ($_GET['oxirgi_id'] ?? 0);
$kor = (int) ($_GET['kor'] ?? 0);

// Oxirgi 50 ta yoki oxirgi_id'dan keyingi xabarlar
if ($oxirgi_id > 0) {
    $xabarlar = db_barcha(
        'SELECT cx.id, cx.kimdan, cx.matn, cx.yaratilgan, cx.admin_id,
                a.ism AS admin_ism, a.familiya AS admin_familiya, a.avatar AS admin_avatar
         FROM chat_xabarlar cx
         LEFT JOIN foydalanuvchilar a ON cx.admin_id = a.id
         WHERE cx.foydalanuvchi_id = ? AND cx.id > ?
         ORDER BY cx.id ASC',
        [$f['id'], $oxirgi_id]
    );
} else {
    $xabarlar = db_barcha(
        'SELECT cx.id, cx.kimdan, cx.matn, cx.yaratilgan, cx.admin_id,
                a.ism AS admin_ism, a.familiya AS admin_familiya, a.avatar AS admin_avatar
         FROM chat_xabarlar cx
         LEFT JOIN foydalanuvchilar a ON cx.admin_id = a.id
         WHERE cx.foydalanuvchi_id = ?
         ORDER BY cx.id DESC LIMIT 50',
        [$f['id']]
    );
    $xabarlar = array_reverse($xabarlar);
}

// "oqilgan" deb belgilash
if ($kor === 1) {
    db_bajar(
        'UPDATE chat_xabarlar
         SET oqilgan = 1
         WHERE foydalanuvchi_id = ? AND kimdan IN ("admin","ai") AND oqilgan = 0',
        [$f['id']]
    );
}

// Format
$natija = [];
foreach ($xabarlar as $x) {
    $natija[] = [
        'id'      => (int) $x['id'],
        'kimdan'  => $x['kimdan'],
        'matn'    => $x['matn'],
        'vaqt'    => date('H:i', strtotime($x['yaratilgan'])),
        'sana'    => date('d.m.Y H:i', strtotime($x['yaratilgan'])),
        'admin'   => $x['kimdan'] === 'admin' ? [
            'ism'    => $x['admin_ism'],
            'tolaq'  => trim($x['admin_ism'] . ' ' . ($x['admin_familiya'] ?? '')),
            'avatar' => $x['admin_avatar'],
        ] : null,
    ];
}

json_javob([
    'ok'           => true,
    'xabarlar'     => $natija,
    'oqilmagan'    => chat_oqilmagan_son($f['id']),
]);
