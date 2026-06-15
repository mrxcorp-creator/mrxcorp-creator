<?php
/**
 * AvtoTest Pro — Payme (Paycom) JSON-RPC 2.0 webhook
 *
 * Autentifikatsiya: Basic Auth (login=Paycom, password=payme_key)
 * Metodlar: CheckPerformTransaction, CreateTransaction,
 *           PerformTransaction, CancelTransaction,
 *           CheckTransaction, GetStatement
 *
 * Rasmiy: https://developer.help.paycom.uz/
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

header('Content-Type: application/json; charset=utf-8');

// Payme xato kodlari
const PM_AUTH_FAILED  = -32504;
const PM_HISOB        = -31050;
const PM_AMOUNT       = -31001;
const PM_NOT_FOUND    = -31003;
const PM_HOLAT        = -31008;
const PM_PARSE_ERROR  = -32700;
const PM_METHOD       = -32601;

function pm_xato(int $kod, string $matn, mixed $data = null, mixed $id = null): never
{
    $r = ['error' => ['code' => $kod, 'message' => $matn]];
    if ($data !== null) $r['error']['data'] = $data;
    if ($id   !== null) $r['id'] = $id;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

function pm_javob(array $result, mixed $id = null): never
{
    $r = ['result' => $result];
    if ($id !== null) $r['id'] = $id;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

// Basic Auth tekshiruvi
$kalit = sozlama('payme_key', '');
$auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!$kalit || !str_starts_with($auth, 'Basic ')) {
    pm_xato(PM_AUTH_FAILED, 'Autentifikatsiya talab qilinadi');
}

$decoded = base64_decode(substr($auth, 6)) ?: '';
$parts   = explode(':', $decoded, 2);
if (count($parts) !== 2 || $parts[0] !== 'Paycom' || !hash_equals($kalit, $parts[1])) {
    pm_xato(PM_AUTH_FAILED, 'Avtorizatsiya xato');
}

// JSON so'rovni tahlil qilish
$body  = file_get_contents('php://input');
$req   = json_decode($body, true);
if (!$req || !isset($req['method'])) {
    pm_xato(PM_PARSE_ERROR, 'JSON parse xato');
}

$id     = $req['id']     ?? null;
$method = $req['method'] ?? '';
$params = $req['params'] ?? [];

// Yordamchi: to'lovni hisob bo'yicha topish
function _tolov_topish(array $hisob): ?array
{
    if (empty($hisob['tolov_id'])) return null;
    return db_qator('SELECT * FROM tolovlar WHERE id = ?', [(int)$hisob['tolov_id']]);
}

// Obuna ochish yordamchisi
function _obuna_ochish_payme(array $tolov, array $tarif): void
{
    $kun = match ($tarif['tur']) {
        'kun'   => (int) $tarif['qiymat'],
        'oy'    => (int) $tarif['qiymat'] * 30,
        default => 365,
    };
    db_bajar(
        'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
         VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
        [$tolov['foydalanuvchi_id'], $tarif['id'], $kun]
    );
}

switch ($method) {

    // ----------------------------------------------------------------
    case 'CheckPerformTransaction':
        $hisob = $params['account'] ?? [];
        $summa = (int)($params['amount'] ?? 0);

        $tolov = _tolov_topish($hisob);
        if (!$tolov) pm_xato(PM_HISOB, 'Hisob topilmadi', 'tolov_id', $id);
        if ($tolov['holat'] !== 'kutilmoqda') pm_xato(PM_HOLAT, "Holat noto'g'ri", null, $id);
        if ((int)((float)$tolov['summa'] * 100) !== $summa) pm_xato(PM_AMOUNT, 'Summa mos emas', null, $id);

        pm_javob(['allow' => true], $id);

    // ----------------------------------------------------------------
    case 'CreateTransaction':
        $hisob    = $params['account'] ?? [];
        $summa    = (int)($params['amount'] ?? 0);
        $payme_id = (string)($params['id'] ?? '');
        $vaqt     = (int)($params['time'] ?? 0);

        $tolov = _tolov_topish($hisob);
        if (!$tolov) pm_xato(PM_HISOB, 'Hisob topilmadi', 'tolov_id', $id);
        if ((int)((float)$tolov['summa'] * 100) !== $summa) pm_xato(PM_AMOUNT, 'Summa mos emas', null, $id);

        // Mavjud tranzaksiya?
        if ($tolov['tashqi_id'] === $payme_id) {
            pm_javob([
                'create_time' => $vaqt,
                'transaction' => (string)$tolov['id'],
                'state'       => 1,
            ], $id);
        }

        if ($tolov['holat'] !== 'kutilmoqda') pm_xato(PM_HOLAT, 'Boshqa tranzaksiya mavjud', null, $id);

        db_bajar('UPDATE tolovlar SET tashqi_id = ? WHERE id = ?', [$payme_id, $tolov['id']]);

        pm_javob([
            'create_time' => $vaqt,
            'transaction' => (string)$tolov['id'],
            'state'       => 1,
        ], $id);

    // ----------------------------------------------------------------
    case 'PerformTransaction':
        $payme_id = (string)($params['id'] ?? '');
        $tolov    = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) pm_xato(PM_NOT_FOUND, 'Tranzaksiya topilmadi', null, $id);

        if ($tolov['holat'] === 'muvaffaqiyatli') {
            pm_javob([
                'transaction'  => (string)$tolov['id'],
                'perform_time' => strtotime($tolov['yangilangan']) * 1000,
                'state'        => 2,
            ], $id);
        }
        if ($tolov['holat'] !== 'kutilmoqda') pm_xato(PM_HOLAT, "Holat noto'g'ri", null, $id);

        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
        if (!$tarif) pm_xato(-100, 'Tarif topilmadi', null, $id);

        db()->beginTransaction();
        try {
            db_bajar('UPDATE tolovlar SET holat = "muvaffaqiyatli" WHERE id = ?', [$tolov['id']]);
            _obuna_ochish_payme($tolov, $tarif);

            $fo = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
            if ($fo && !empty($fo['referal_orqali'])) {
                $bonus = (float) sozlama('referal_bonus', 5000);
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                         [$bonus, (int)$fo['referal_orqali']]);
                db_bajar('UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                         [$bonus, $fo['id']]);
            }

            if ($fo && $fo['telegram_id']) {
                telegram_yubor((int)$fo['telegram_id'],
                    "✅ <b>To'lov muvaffaqiyatli!</b>\nTarif: <b>{$tarif['nomi']}</b>");
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            error_log('Payme perform xato: ' . $e->getMessage());
            pm_xato(-31099, 'Server xatosi', null, $id);
        }

        pm_javob([
            'transaction'  => (string)$tolov['id'],
            'perform_time' => time() * 1000,
            'state'        => 2,
        ], $id);

    // ----------------------------------------------------------------
    case 'CancelTransaction':
        $payme_id = (string)($params['id'] ?? '');
        $tolov    = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) pm_xato(PM_NOT_FOUND, 'Tranzaksiya topilmadi', null, $id);

        if (in_array($tolov['holat'], ['kutilmoqda', 'muvaffaqiyatli'], true)) {
            db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$tolov['id']]);
            if ($tolov['holat'] === 'muvaffaqiyatli') {
                // Obunani ham bekor qilish
                db_bajar(
                    'UPDATE obunalar SET holat = "bekor"
                     WHERE foydalanuvchi_id = ? AND tarif_id = ?
                       AND holat = "faol"
                     ORDER BY id DESC LIMIT 1',
                    [$tolov['foydalanuvchi_id'], $tolov['tarif_id']]
                );
            }
        }

        pm_javob([
            'transaction' => (string)$tolov['id'],
            'cancel_time' => time() * 1000,
            'state'       => -1,
        ], $id);

    // ----------------------------------------------------------------
    case 'CheckTransaction':
        $payme_id = (string)($params['id'] ?? '');
        $tolov    = db_qator('SELECT * FROM tolovlar WHERE tashqi_id = ?', [$payme_id]);
        if (!$tolov) pm_xato(PM_NOT_FOUND, 'Tranzaksiya topilmadi', null, $id);

        $state = match ($tolov['holat']) {
            'muvaffaqiyatli' => 2,
            'bekor'          => -1,
            default          => 1,
        };

        pm_javob([
            'create_time'  => strtotime($tolov['yaratilgan']) * 1000,
            'perform_time' => $state === 2  ? strtotime($tolov['yangilangan']) * 1000 : 0,
            'cancel_time'  => $state === -1 ? strtotime($tolov['yangilangan']) * 1000 : 0,
            'transaction'  => (string)$tolov['id'],
            'state'        => $state,
            'reason'       => null,
        ], $id);

    // ----------------------------------------------------------------
    case 'GetStatement':
        $dan  = (int)($params['from'] ?? 0);
        $gacha= (int)($params['to']   ?? 0);

        $royxat = db_barcha(
            'SELECT * FROM tolovlar
             WHERE tolov_turi = "payme"
               AND yaratilgan >= FROM_UNIXTIME(?)
               AND yaratilgan <= FROM_UNIXTIME(?)',
            [(int)($dan / 1000), (int)($gacha / 1000)]
        );

        $natija = array_map(fn($t) => [
            'id'           => $t['tashqi_id'],
            'time'         => strtotime($t['yaratilgan']) * 1000,
            'amount'       => (int)((float)$t['summa'] * 100),
            'account'      => ['tolov_id' => (string)$t['id']],
            'create_time'  => strtotime($t['yaratilgan']) * 1000,
            'perform_time' => $t['holat'] === 'muvaffaqiyatli' ? strtotime($t['yangilangan']) * 1000 : 0,
            'cancel_time'  => $t['holat'] === 'bekor'          ? strtotime($t['yangilangan']) * 1000 : 0,
            'transaction'  => (string)$t['id'],
            'state'        => match($t['holat']) { 'muvaffaqiyatli'=>2,'bekor'=>-1,default=>1 },
        ], $royxat);

        pm_javob(['transactions' => $natija], $id);

    // ----------------------------------------------------------------
    default:
        pm_xato(PM_METHOD, 'Metod topilmadi', null, $id);
}
