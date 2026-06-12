<?php
/**
 * VatanParvar Yaypan — Payme (Paycom) webhook
 *
 * Payme JSON-RPC 2.0 protokolidan foydalanadi va Basic Auth orqali
 * autentifikatsiya qiladi. Quyidagi metodlar amalga oshiriladi:
 *   - CheckPerformTransaction
 *   - CreateTransaction
 *   - PerformTransaction
 *   - CancelTransaction
 *   - CheckTransaction
 *   - GetStatement
 *
 * Rasmiy hujjat: https://developer.help.paycom.uz/
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

// ============================================================
// XATO KODLARI
// ============================================================
const PAYME_XATO_AUTH       = -32504;
const PAYME_XATO_HISOB      = -31050;
const PAYME_XATO_SUMMA      = -31001;
const PAYME_XATO_TOPILMADI  = -31003;
const PAYME_XATO_HOLATI     = -31008;

function payme_xato(int $kod, string $matn, $qoshimcha = null, ?int $id = null): never {
    $res = ['error' => ['code' => $kod, 'message' => $matn]];
    if ($qoshimcha !== null) $res['error']['data'] = $qoshimcha;
    if ($id !== null) $res['id'] = $id;
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

function payme_javob(array $natija, ?int $id = null): never {
    $r = ['result' => $natija];
    if ($id !== null) $r['id'] = $id;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// AUTH
// ============================================================
$kerakli_kalit = sozlama('payme_key', '');
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$kerakli_kalit || !str_starts_with($auth, 'Basic ')) {
    payme_xato(PAYME_XATO_AUTH, 'Auth talab qilinadi');
}
$dekod = base64_decode(substr($auth, 6)) ?: '';
$qism = explode(':', $dekod, 2);
if (count($qism) !== 2 || $qism[0] !== 'Paycom' || !hash_equals($kerakli_kalit, $qism[1])) {
    payme_xato(PAYME_XATO_AUTH, 'Avtorizatsiya xato');
}

// ============================================================
// JSON-RPC tahlili
// ============================================================
$tana = file_get_contents('php://input');
$so_rov = json_decode($tana, true);
if (!$so_rov || !isset($so_rov['method'])) {
    payme_xato(-32700, 'Parse error');
}
$id = $so_rov['id'] ?? null;
$method = $so_rov['method'];
$params = $so_rov['params'] ?? [];

// ============================================================
// Yordamchi: hisobni tekshirish
// ============================================================
function tolov_topish(array $hisob): ?array {
    if (empty($hisob['tolov_id'])) return null;
    return db_qator('SELECT * FROM tolovlar WHERE id = ?', [(int) $hisob['tolov_id']]);
}

// ============================================================
// METHODLAR
// ============================================================
switch ($method) {

    case 'CheckPerformTransaction': {
        $hisob = $params['account'] ?? [];
        $summa = (int) ($params['amount'] ?? 0);

        $tolov = tolov_topish($hisob);
        if (!$tolov) payme_xato(PAYME_XATO_HISOB, 'Hisob topilmadi', 'tolov_id', $id);
        if ($tolov['holat'] !== 'kutilmoqda') payme_xato(PAYME_XATO_HOLATI, 'Holat noto\'g\'ri', null, $id);
        if ((int) ($tolov['summa'] * 100) !== $summa) payme_xato(PAYME_XATO_SUMMA, 'Summa mos emas', null, $id);

        payme_javob(['allow' => true], $id);
    }

    case 'CreateTransaction': {
        $hisob = $params['account'] ?? [];
        $summa = (int) ($params['amount'] ?? 0);
        $payme_id = $params['id'] ?? '';
        $vaqt = (int) ($params['time'] ?? 0);

        $tolov = tolov_topish($hisob);
        if (!$tolov) payme_xato(PAYME_XATO_HISOB, 'Hisob topilmadi', 'tolov_id', $id);
        if ((int) ($tolov['summa'] * 100) !== $summa) payme_xato(PAYME_XATO_SUMMA, 'Summa mos emas', null, $id);

        // Allaqachon mavjudmi?
        if ($tolov['tashqi_id'] === $payme_id) {
            payme_javob([
                'create_time' => $vaqt,
                'transaction' => (string) $tolov['id'],
                'state'       => 1,
            ], $id);
        }

        if ($tolov['holat'] !== 'kutilmoqda') {
            payme_xato(PAYME_XATO_HOLATI, 'Boshqa tranzaksiya bor', null, $id);
        }

        db_bajar('UPDATE tolovlar SET tashqi_id = ? WHERE id = ?', [$payme_id, $tolov['id']]);

        payme_javob([
            'create_time' => $vaqt,
            'transaction' => (string) $tolov['id'],
            'state'       => 1,
        ], $id);
    }

    case 'PerformTransaction': {
        $payme_id = $params['id'] ?? '';
        $tolov = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) payme_xato(PAYME_XATO_TOPILMADI, 'Tranzaksiya topilmadi', null, $id);

        if ($tolov['holat'] === 'muvaffaqiyatli') {
            payme_javob([
                'transaction'  => (string) $tolov['id'],
                'perform_time' => strtotime($tolov['yangilangan']) * 1000,
                'state'        => 2,
            ], $id);
        }
        if ($tolov['holat'] !== 'kutilmoqda') {
            payme_xato(PAYME_XATO_HOLATI, 'Holat noto\'g\'ri', null, $id);
        }

        // Obuna ochamiz
        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
        $kun = match ($tarif['tur']) {
            'kun' => $tarif['qiymat'],
            'oy'  => $tarif['qiymat'] * 30,
            default => 365,
        };

        db()->beginTransaction();
        try {
            db_bajar('UPDATE tolovlar SET holat = "muvaffaqiyatli" WHERE id = ?', [$tolov['id']]);
            db_bajar(
                'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                [$tolov['foydalanuvchi_id'], $tarif['id'], $kun]
            );

            $foydalanuvchi = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
            if (!empty($foydalanuvchi['referal_orqali'])) {
                $bonus = (float) sozlama('referal_bonus', 5000);
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                         [$bonus, $foydalanuvchi['referal_orqali']]);
                db_bajar('UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                         [$bonus, $foydalanuvchi['id']]);
            }

            if ($foydalanuvchi['telegram_id']) {
                telegram_yubor($foydalanuvchi['telegram_id'],
                    "✅ <b>To'lov muvaffaqiyatli!</b>\nTarif: <b>" . $tarif['nomi'] . "</b>");
            }

            db()->commit();
        } catch (Exception $exc) {
            db()->rollBack();
            payme_xato(-31099, 'Server xatosi', null, $id);
        }

        payme_javob([
            'transaction'  => (string) $tolov['id'],
            'perform_time' => time() * 1000,
            'state'        => 2,
        ], $id);
    }

    case 'CancelTransaction': {
        $payme_id = $params['id'] ?? '';
        $tolov = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) payme_xato(PAYME_XATO_TOPILMADI, 'Tranzaksiya topilmadi', null, $id);

        $vaqt = time() * 1000;
        if ($tolov['holat'] === 'kutilmoqda' || $tolov['holat'] === 'muvaffaqiyatli') {
            db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$tolov['id']]);
            // Faol obunani ham bekor qilish
            db_bajar('UPDATE obunalar SET holat = "bekor" WHERE foydalanuvchi_id = ? AND tarif_id = ? AND yaratilgan >= ? ORDER BY id DESC LIMIT 1',
                     [$tolov['foydalanuvchi_id'], $tolov['tarif_id'], $tolov['yaratilgan']]);
        }
        payme_javob([
            'transaction' => (string) $tolov['id'],
            'cancel_time' => $vaqt,
            'state'       => -1,
        ], $id);
    }

    case 'CheckTransaction': {
        $payme_id = $params['id'] ?? '';
        $tolov = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) payme_xato(PAYME_XATO_TOPILMADI, 'Tranzaksiya topilmadi', null, $id);

        $state = match ($tolov['holat']) {
            'muvaffaqiyatli' => 2,
            'bekor'          => -1,
            default          => 1,
        };

        payme_javob([
            'create_time'  => strtotime($tolov['yaratilgan']) * 1000,
            'perform_time' => $state === 2 ? strtotime($tolov['yangilangan']) * 1000 : 0,
            'cancel_time'  => $state === -1 ? strtotime($tolov['yangilangan']) * 1000 : 0,
            'transaction'  => (string) $tolov['id'],
            'state'        => $state,
            'reason'       => null,
        ], $id);
    }

    case 'GetStatement': {
        $boshlanish = (int) ($params['from'] ?? 0);
        $tugash     = (int) ($params['to']   ?? 0);

        $tolovlar = db_barcha(
            'SELECT * FROM tolovlar
             WHERE tolov_turi = "payme"
               AND yaratilgan >= FROM_UNIXTIME(?)
               AND yaratilgan <= FROM_UNIXTIME(?)',
            [$boshlanish / 1000, $tugash / 1000]
        );

        $natija = [];
        foreach ($tolovlar as $t) {
            $natija[] = [
                'id'           => $t['tashqi_id'],
                'time'         => strtotime($t['yaratilgan']) * 1000,
                'amount'       => (int) ($t['summa'] * 100),
                'account'      => ['tolov_id' => (string) $t['id']],
                'create_time'  => strtotime($t['yaratilgan']) * 1000,
                'perform_time' => $t['holat'] === 'muvaffaqiyatli' ? strtotime($t['yangilangan']) * 1000 : 0,
                'cancel_time'  => $t['holat'] === 'bekor' ? strtotime($t['yangilangan']) * 1000 : 0,
                'transaction'  => (string) $t['id'],
                'state'        => match ($t['holat']) {
                    'muvaffaqiyatli' => 2,
                    'bekor'          => -1,
                    default          => 1,
                },
            ];
        }
        payme_javob(['transactions' => $natija], $id);
    }

    default:
        payme_xato(-32601, 'Method topilmadi', null, $id);
}
