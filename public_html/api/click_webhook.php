<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

$KOD = [
    'OK'                    => 0,
    'SIGN_XATO'             => -1,
    'INVALID_AMOUNT'        => -2,
    'ALLAQACHON_BAJARILGAN' => -4,
    'TRANSAKSIYA_TOPILMADI' => -5,
    'TOPILMADI'             => -6,
    'OLDIN_TOPILGAN'        => -7,
    'XATO_PARAMETRLAR'      => -8,
    'BEKOR_QILINGAN'        => -9,
];

function click_javob(int $kod, string $matn, array $qoshimcha = []): never {
    echo json_encode(array_merge([
        'error' => $kod,
        'error_note' => $matn,
    ], $qoshimcha), JSON_UNESCAPED_UNICODE);
    exit;
}

$click_trans_id = $_POST['click_trans_id']    ?? '';
$service_id     = $_POST['service_id']        ?? '';
$click_paydoc_id= $_POST['click_paydoc_id']   ?? '';
$merchant_trans = $_POST['merchant_trans_id'] ?? '';
$amount         = (float) ($_POST['amount']   ?? 0);
$action         = (int)   ($_POST['action']   ?? -1);
$sign_time      = $_POST['sign_time']         ?? '';
$sign_string    = $_POST['sign_string']       ?? '';
$error          = (int)   ($_POST['error']    ?? 0);
$merchant_prep  = $_POST['merchant_prepare_id'] ?? '';

$secret = maxfiy_qiymat('CLICK_SECRET', 'click_secret');
$kutilgan = $action === 0
    ? md5("$click_trans_id$service_id$secret$merchant_trans$amount$action$sign_time")
    : md5("$click_trans_id$service_id$secret$merchant_trans$merchant_prep$amount$action$sign_time");

if (!$secret || !hash_equals($kutilgan, $sign_string)) {
    click_javob($KOD['SIGN_XATO'], 'Imzo noto\'g\'ri');
}

$tolov = db_qator('SELECT * FROM tolovlar WHERE id = ?', [$merchant_trans]);
if (!$tolov) {
    click_javob($KOD['TRANSAKSIYA_TOPILMADI'], 'Tranzaksiya topilmadi');
}

if (abs((float) $tolov['summa'] - $amount) > 0.01) {
    click_javob($KOD['INVALID_AMOUNT'], 'Summa mos emas');
}

if ($action === 0) {
    if ($tolov['holat'] === 'muvaffaqiyatli') {
        click_javob($KOD['ALLAQACHON_BAJARILGAN'], 'Allaqachon bajarilgan');
    }
    if ($tolov['holat'] === 'bekor') {
        click_javob($KOD['BEKOR_QILINGAN'], 'Bekor qilingan');
    }
    db_bajar('UPDATE tolovlar SET tashqi_id = ? WHERE id = ?', [$click_trans_id, $tolov['id']]);

    click_javob($KOD['OK'], 'Success', [
        'click_trans_id'      => $click_trans_id,
        'merchant_trans_id'   => $merchant_trans,
        'merchant_prepare_id' => $tolov['id'],
    ]);
}

if ($action === 1) {
    if ($tolov['holat'] === 'muvaffaqiyatli') {
        click_javob($KOD['ALLAQACHON_BAJARILGAN'], 'Allaqachon bajarilgan');
    }
    if ($error < 0) {
        db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$tolov['id']]);
        click_javob($KOD['BEKOR_QILINGAN'], 'Bekor qilindi');
    }

    $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
    $kun = match ($tarif['tur']) {
        'kun' => $tarif['qiymat'],
        'oy'  => $tarif['qiymat'] * 30,
        default => 365,
    };

    db()->beginTransaction();
    try {
        $yangilandi = db_bajar(
            'UPDATE tolovlar SET holat = "muvaffaqiyatli", tashqi_id = ?
             WHERE id = ? AND holat = "kutilmoqda"',
            [$click_paydoc_id ?: $click_trans_id, $tolov['id']]
        );
        if ($yangilandi === 0) {
            db()->rollBack();
            click_javob($KOD['ALLAQACHON_BAJARILGAN'], 'Allaqachon bajarilgan');
        }

        try {
            db_bajar(
                'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, tolov_id, boshlanish, tugash, holat)
                 VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                [$tolov['foydalanuvchi_id'], $tarif['id'], $tolov['id'], $kun]
            );
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'Duplicate') && !str_contains($e->getMessage(), '1062')) {
                throw $e;
            }
        }

        $foydalanuvchi = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
        if (!empty($foydalanuvchi['referal_orqali'])) {
            $bonus = (float) sozlama('referal_bonus', 5000);
            $allaqachon_berildi = db_qiymat(
                'SELECT 1 FROM referallar WHERE referal_id = ? AND holat = "tasdiq"',
                [$foydalanuvchi['id']]
            );
            if (!$allaqachon_berildi) {
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                         [$bonus, $foydalanuvchi['referal_orqali']]);
                db_bajar('UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                         [$bonus, $foydalanuvchi['id']]);
                bonus_yoz(
                    (int) $foydalanuvchi['referal_orqali'],
                    $bonus,
                    'referal',
                    'Click: do\'st to\'lov qildi (#' . (int) $foydalanuvchi['id'] . ')',
                    (int) $foydalanuvchi['id']
                );
            }
        }

        db()->commit();

        if ($foydalanuvchi['telegram_id']) {
            telegram_yubor($foydalanuvchi['telegram_id'],
                "✅ <b>To'lov muvaffaqiyatli!</b>\nTarif: <b>" . $tarif['nomi'] . "</b>\nSumma: <b>" . pul($amount) . "</b>");
        }

        audit_yoz('tolov_tasdiqlandi', 'tolov', (int) $tolov['id'], [
            'tolov_turi' => 'click',
            'summa' => $amount,
            'tarif' => $tarif['nomi'],
        ]);

        click_javob($KOD['OK'], 'Success', [
            'click_trans_id'      => $click_trans_id,
            'merchant_trans_id'   => $merchant_trans,
            'merchant_confirm_id' => $tolov['id'],
        ]);
    } catch (Exception $exc) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('Click webhook xato: ' . $exc->getMessage());
        click_javob(-100, 'Server xatosi');
    }
}

click_javob($KOD['XATO_PARAMETRLAR'], 'Noma\'lum action');
