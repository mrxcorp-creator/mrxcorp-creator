<?php
/**
 * VatanParvar Yaypan — Click to'lov tizimi webhook
 *
 * Click Prepare (action=0) + Complete (action=1) protokoli.
 * Rasmiy hujjat: https://docs.click.uz/
 *
 * Imzo (sign) tekshiruvi:
 *   Prepare:  MD5(click_trans_id + service_id + secret + merchant_trans_id + amount + action + sign_time)
 *   Complete: MD5(click_trans_id + service_id + secret + merchant_trans_id + merchant_prepare_id + amount + action + sign_time)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

// Click javob kodlari
const C_OK         =  0;
const C_SIGN       = -1;
const C_AMOUNT     = -2;
const C_DONE       = -4;
const C_NO_TRANS   = -5;
const C_NOT_FOUND  = -6;
const C_CANCELED   = -9;
const C_BAD_PARAMS = -8;

function click_javob(int $kod, string $matn, array $extra = []): never
{
    echo json_encode(
        array_merge(['error' => $kod, 'error_note' => $matn], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// POST parametrlar
$click_trans_id  = (string) ($_POST['click_trans_id']    ?? '');
$service_id      = (string) ($_POST['service_id']        ?? '');
$click_paydoc_id = (string) ($_POST['click_paydoc_id']   ?? '');
$merchant_trans  = (string) ($_POST['merchant_trans_id'] ?? '');
$amount          = (float)  ($_POST['amount']            ?? 0);
$action          = (int)    ($_POST['action']            ?? -1);
$sign_time       = (string) ($_POST['sign_time']         ?? '');
$sign_string     = (string) ($_POST['sign_string']       ?? '');
$error           = (int)    ($_POST['error']             ?? 0);
$merchant_prep   = (string) ($_POST['merchant_prepare_id'] ?? '');

// Imzo kaliti
$secret = sozlama('click_secret', '');

if (!$secret) {
    click_javob(C_SIGN, 'Click secret sozlanmagan');
}

// Imzo tekshiruvi
$kutilgan = $action === 0
    ? md5("{$click_trans_id}{$service_id}{$secret}{$merchant_trans}{$amount}{$action}{$sign_time}")
    : md5("{$click_trans_id}{$service_id}{$secret}{$merchant_trans}{$merchant_prep}{$amount}{$action}{$sign_time}");

if (!hash_equals($kutilgan, $sign_string)) {
    click_javob(C_SIGN, "Imzo noto'g'ri");
}

// To'lovni topish
$tolov = db_qator('SELECT * FROM tolovlar WHERE id = ?', [(int) $merchant_trans]);
if (!$tolov) {
    click_javob(C_NO_TRANS, 'Tranzaksiya topilmadi');
}

// Summa tekshiruvi (0.01 farq tolerant)
if (abs((float)$tolov['summa'] - $amount) > 0.01) {
    click_javob(C_AMOUNT, 'Summa mos emas');
}

// ==============================================================
// PREPARE (action=0)
// ==============================================================
if ($action === 0) {
    if ($tolov['holat'] === 'muvaffaqiyatli') {
        click_javob(C_DONE, 'Allaqachon bajarilgan');
    }
    if ($tolov['holat'] === 'bekor') {
        click_javob(C_CANCELED, 'Bekor qilingan');
    }

    db_bajar(
        'UPDATE tolovlar SET tashqi_id = ? WHERE id = ?',
        [$click_trans_id, $tolov['id']]
    );

    click_javob(C_OK, 'Success', [
        'click_trans_id'      => $click_trans_id,
        'merchant_trans_id'   => $merchant_trans,
        'merchant_prepare_id' => $tolov['id'],
    ]);
}

// ==============================================================
// COMPLETE (action=1)
// ==============================================================
if ($action === 1) {
    if ($tolov['holat'] === 'muvaffaqiyatli') {
        click_javob(C_DONE, 'Allaqachon bajarilgan');
    }

    if ($error < 0) {
        db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$tolov['id']]);
        click_javob(C_CANCELED, 'Bekor qilindi');
    }

    $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
    if (!$tarif) {
        click_javob(-100, 'Tarif topilmadi');
    }

    $kun = match ($tarif['tur']) {
        'kun'   => (int) $tarif['qiymat'],
        'oy'    => (int) $tarif['qiymat'] * 30,
        default => 365,
    };

    db()->beginTransaction();
    try {
        db_bajar(
            'UPDATE tolovlar SET holat = "muvaffaqiyatli", tashqi_id = ? WHERE id = ?',
            [$click_paydoc_id ?: $click_trans_id, $tolov['id']]
        );
        db_bajar(
            'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
            [$tolov['foydalanuvchi_id'], $tarif['id'], $kun]
        );

        // Referal bonus
        $fo = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
        if ($fo && !empty($fo['referal_orqali'])) {
            $bonus = (float) sozlama('referal_bonus', 5000);
            db_bajar(
                'UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                [$bonus, (int)$fo['referal_orqali']]
            );
            db_bajar(
                'UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                [$bonus, $fo['id']]
            );
        }

        // Telegram bildirishnoma
        if ($fo && $fo['telegram_id']) {
            telegram_yubor(
                (int) $fo['telegram_id'],
                "✅ <b>To'lov muvaffaqiyatli!</b>\n"
                . "Tarif: <b>{$tarif['nomi']}</b>\n"
                . "Summa: <b>" . pul($amount) . "</b>"
            );
        }

        db()->commit();

        click_javob(C_OK, 'Success', [
            'click_trans_id'      => $click_trans_id,
            'merchant_trans_id'   => $merchant_trans,
            'merchant_confirm_id' => $tolov['id'],
        ]);
    } catch (Throwable $e) {
        db()->rollBack();
        error_log('Click complete xato: ' . $e->getMessage());
        click_javob(-100, 'Server xatosi');
    }
}

click_javob(C_BAD_PARAMS, "Noma'lum action");
