<?php
/**
 * Admin chat API
 *
 * GET ?action=ruyhat — barcha suhbatlar (faolligi bo'yicha)
 * GET ?action=xabarlar&fid=X — bitta foydalanuvchi xabarlari
 * POST action=yuborish, fid=X, matn=... — admin xabar yuboradi
 * POST action=oqildi, fid=X — foydalanuvchi xabarlarini oqilgan deb belgilash
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$admin = admin_bolish_kerak();

$action = $_REQUEST['action'] ?? '';

// ============================================================
// 1) Suhbatlar ro'yxati
// ============================================================
if ($action === 'ruyhat') {
    $qidiruv = trim($_GET['q'] ?? '');
    $params = [];
    $where = '';
    if ($qidiruv) {
        $where = 'WHERE fo.ism LIKE ? OR fo.familiya LIKE ? OR fo.telefon LIKE ?';
        $params = ["%$qidiruv%", "%$qidiruv%", "%$qidiruv%"];
    }

    $suhbatlar = db_barcha(
        "SELECT fo.id, fo.ism, fo.familiya, fo.telefon, fo.avatar,
                (SELECT matn FROM chat_xabarlar WHERE foydalanuvchi_id = fo.id ORDER BY id DESC LIMIT 1) AS oxirgi_xabar,
                (SELECT kimdan FROM chat_xabarlar WHERE foydalanuvchi_id = fo.id ORDER BY id DESC LIMIT 1) AS oxirgi_kimdan,
                (SELECT yaratilgan FROM chat_xabarlar WHERE foydalanuvchi_id = fo.id ORDER BY id DESC LIMIT 1) AS oxirgi_vaqt,
                (SELECT COUNT(*) FROM chat_xabarlar WHERE foydalanuvchi_id = fo.id AND kimdan = 'user' AND oqilgan = 0) AS oqilmagan
         FROM foydalanuvchilar fo
         INNER JOIN (SELECT DISTINCT foydalanuvchi_id FROM chat_xabarlar) cx ON cx.foydalanuvchi_id = fo.id
         $where
         ORDER BY oxirgi_vaqt DESC
         LIMIT 100",
        $params
    );

    $natija = [];
    foreach ($suhbatlar as $s) {
        $natija[] = [
            'id'        => (int) $s['id'],
            'ism'       => trim($s['ism'] . ' ' . ($s['familiya'] ?? '')),
            'telefon'   => $s['telefon'],
            'avatar'    => $s['avatar'],
            'oxirgi'    => mb_substr((string) $s['oxirgi_xabar'], 0, 80),
            'oxirgi_kimdan' => $s['oxirgi_kimdan'],
            'vaqt'      => $s['oxirgi_vaqt'] ? date('H:i', strtotime($s['oxirgi_vaqt'])) : '',
            'kun'       => $s['oxirgi_vaqt'] ? date('d.m', strtotime($s['oxirgi_vaqt'])) : '',
            'oqilmagan' => (int) $s['oqilmagan'],
        ];
    }
    json_javob(['ok' => true, 'suhbatlar' => $natija]);
}

// ============================================================
// 2) Bitta foydalanuvchi xabarlari
// ============================================================
if ($action === 'xabarlar') {
    $fid = (int) ($_GET['fid'] ?? 0);
    if (!$fid) json_javob(['ok' => false, 'xato' => 'fid kerak'], 400);

    $foydalanuvchi = db_qator(
        'SELECT id, ism, familiya, telefon, avatar, oxirgi_kirish, yaratilgan
         FROM foydalanuvchilar WHERE id = ?',
        [$fid]
    );
    if (!$foydalanuvchi) json_javob(['ok' => false, 'xato' => 'Topilmadi'], 404);

    $xabarlar = db_barcha(
        'SELECT cx.id, cx.kimdan, cx.matn, cx.yaratilgan, cx.admin_id,
                a.ism AS admin_ism, a.familiya AS admin_familiya
         FROM chat_xabarlar cx
         LEFT JOIN foydalanuvchilar a ON cx.admin_id = a.id
         WHERE cx.foydalanuvchi_id = ?
         ORDER BY cx.id ASC',
        [$fid]
    );

    $natija = [];
    foreach ($xabarlar as $x) {
        $natija[] = [
            'id'      => (int) $x['id'],
            'kimdan'  => $x['kimdan'],
            'matn'    => $x['matn'],
            'vaqt'    => date('H:i', strtotime($x['yaratilgan'])),
            'sana'    => date('d.m.Y H:i', strtotime($x['yaratilgan'])),
            'admin'   => $x['kimdan'] === 'admin' ? trim($x['admin_ism'] . ' ' . ($x['admin_familiya'] ?? '')) : null,
        ];
    }

    // Foydalanuvchining obuna holati
    $obuna = db_qator(
        'SELECT t.nomi, o.tugash FROM obunalar o
         JOIN tariflar t ON o.tarif_id = t.id
         WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
         ORDER BY o.tugash DESC LIMIT 1',
        [$fid]
    );

    json_javob([
        'ok'       => true,
        'foydalanuvchi' => [
            'id'       => (int) $foydalanuvchi['id'],
            'ism'      => trim($foydalanuvchi['ism'] . ' ' . ($foydalanuvchi['familiya'] ?? '')),
            'telefon'  => $foydalanuvchi['telefon'],
            'avatar'   => $foydalanuvchi['avatar'],
            'oxirgi'   => $foydalanuvchi['oxirgi_kirish'] ? date('d.m.Y H:i', strtotime($foydalanuvchi['oxirgi_kirish'])) : '—',
            'royxat'   => date('d.m.Y', strtotime($foydalanuvchi['yaratilgan'])),
            'obuna'    => $obuna ? [
                'nomi'  => $obuna['nomi'],
                'tugash' => date('d.m.Y', strtotime($obuna['tugash'])),
            ] : null,
        ],
        'xabarlar' => $natija,
    ]);
}

// ============================================================
// 3) Admin xabar yuborish
// ============================================================
if ($action === 'yuborish') {
    if (!csrf_tekshir(post('csrf_token'))) {
        json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
    }
    $fid  = (int) post('fid');
    $matn = trim(post('matn'));
    if (!$fid || mb_strlen($matn) < 1 || mb_strlen($matn) > 2000) {
        json_javob(['ok' => false, 'xato' => 'Maydonlar to\'g\'ri to\'ldirilmagan'], 400);
    }

    $foyd_bor = db_qiymat('SELECT 1 FROM foydalanuvchilar WHERE id = ?', [$fid]);
    if (!$foyd_bor) json_javob(['ok' => false, 'xato' => 'Foydalanuvchi topilmadi'], 404);

    $xabar_id = db_bajar(
        'INSERT INTO chat_xabarlar (foydalanuvchi_id, admin_id, kimdan, matn, oqilgan)
         VALUES (?, ?, "admin", ?, 0)',
        [$fid, $admin['id'], $matn]
    );

    // Foydalanuvchiga bildirishnoma
    bildirishnoma_yarat(
        $fid,
        'Adminimizdan yangi xabar',
        mb_substr($matn, 0, 100),
        '/chat',
        'info',
        '💬'
    );

    // Foydalanuvchi Telegramga ulangan bo'lsa, xabar yuborish
    $foyd = db_qator('SELECT telegram_id, ism FROM foydalanuvchilar WHERE id = ?', [$fid]);
    if ($foyd && $foyd['telegram_id']) {
        $tg_matn  = "💬 <b>Adminimizdan xabar (" . htmlspecialchars(trim($admin['ism'] . ' ' . ($admin['familiya'] ?? '')), ENT_QUOTES) . "):</b>\n\n";
        $tg_matn .= htmlspecialchars($matn, ENT_QUOTES);
        $tg_matn .= "\n\n🔗 " . SAYT_URL . "/chat";
        telegram_yubor($foyd['telegram_id'], $tg_matn);
    }

    json_javob([
        'ok'    => true,
        'xabar' => [
            'id'     => $xabar_id,
            'kimdan' => 'admin',
            'matn'   => $matn,
            'vaqt'   => date('H:i'),
            'admin'  => trim($admin['ism'] . ' ' . ($admin['familiya'] ?? '')),
        ],
    ]);
}

// ============================================================
// 4) Foydalanuvchi xabarlarini oqilgan deb belgilash
// ============================================================
if ($action === 'oqildi') {
    if (!csrf_tekshir(post('csrf_token'))) {
        json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
    }
    $fid = (int) post('fid');
    db_bajar(
        'UPDATE chat_xabarlar SET oqilgan = 1
         WHERE foydalanuvchi_id = ? AND kimdan = "user" AND oqilgan = 0',
        [$fid]
    );
    json_javob(['ok' => true]);
}

json_javob(['ok' => false, 'xato' => 'Noma\'lum action'], 400);
