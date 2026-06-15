<?php
/**
 * AvtoTest Pro — Yordamchi funksiyalar
 *
 * YANGI: SVG/XML yuklash bloklandi (XSS vektori edi)
 */

require_once __DIR__ . '/../config/database.php';

/** Summani formatlash: 25 000 so'm */
function pul(int|float $summa, string $valyuta = "so'm"): string
{
    return number_format((float) $summa, 0, '.', ' ') . ' ' . $valyuta;
}

/** Sanani formatlash. */
function sana(?string $vaqt, string $format = 'd.m.Y H:i'): string
{
    if (!$vaqt) return '—';
    $ts = strtotime($vaqt);
    return $ts ? date($format, $ts) : '—';
}

/** "N vaqt oldin" ko'rinishi. */
function vaqt_oldin(string $vaqt): string
{
    $d = time() - strtotime($vaqt);
    if ($d < 0)       return 'Hozirgina';
    if ($d < 60)      return $d . ' soniya oldin';
    if ($d < 3600)    return floor($d / 60) . ' daqiqa oldin';
    if ($d < 86400)   return floor($d / 3600) . ' soat oldin';
    if ($d < 2592000) return floor($d / 86400) . ' kun oldin';
    return date('d.m.Y', strtotime($vaqt));
}

/**
 * Rasmni WebP ga konvertatsiya qilib saqlash.
 *
 * SECURITY: SVG va XML fayllari BLOKLANADI — XSS vektori bo'lishi mumkin.
 * Faqat JPEG, PNG, WebP, GIF qabul qilinadi.
 */
function rasm_saqla(array $fayl, string $papka = 'savollar', int $maks = 800): ?string
{
    if (empty($fayl['tmp_name']) || !is_uploaded_file($fayl['tmp_name'])) {
        return null;
    }

    // Hajm tekshiruvi (5 MB)
    if ($fayl['size'] > 5 * 1024 * 1024) {
        return null;
    }

    // Extension tekshiruvi (qo'shimcha himoya)
    $ext = strtolower(pathinfo($fayl['name'] ?? '', PATHINFO_EXTENSION));
    if (in_array($ext, ['svg','xml','html','htm','js','php'], true)) {
        return null; // SECURITY: dangerous file types blocked
    }

    $info = @getimagesize($fayl['tmp_name']);
    if (!$info) {
        return null;
    }

    // SECURITY: Faqat ruxsat etilgan MIME turlari
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($info['mime'], $allowed, true)) {
        return null;
    }

    $papka_yoli = UPLOAD_PATH . '/' . $papka;
    if (!is_dir($papka_yoli)) {
        mkdir($papka_yoli, 0755, true);
    }

    $manba = match ($info['mime']) {
        'image/jpeg' => imagecreatefromjpeg($fayl['tmp_name']),
        'image/png'  => imagecreatefrompng($fayl['tmp_name']),
        'image/webp' => imagecreatefromwebp($fayl['tmp_name']),
        'image/gif'  => imagecreatefromgif($fayl['tmp_name']),
        default      => null,
    };
    if (!$manba) {
        return null;
    }

    [$w, $h] = [$info[0], $info[1]];
    if ($w > $maks || $h > $maks) {
        $nisbat  = $maks / max($w, $h);
        $yw      = (int) round($w * $nisbat);
        $yh      = (int) round($h * $nisbat);
        $yangi   = imagecreatetruecolor($yw, $yh);

        // Shaffoflikni saqlash
        if (in_array($info['mime'], ['image/png', 'image/gif'], true)) {
            imagealphablending($yangi, false);
            imagesavealpha($yangi, true);
        }

        imagecopyresampled($yangi, $manba, 0, 0, 0, 0, $yw, $yh, $w, $h);
        imagedestroy($manba);
        $manba = $yangi;
    }

    $nom = uniqid('img_', true) . '.webp';
    $yol = $papka_yoli . '/' . $nom;
    imagewebp($manba, $yol, 82);
    imagedestroy($manba);

    return $papka . '/' . $nom;
}

/** Telegram matn xabar yuborish. */
function telegram_yubor(int|string $chat_id, string $matn, array $qoshimcha = []): bool
{
    $token = sozlama('telegram_bot_token');
    if (!$token || !$chat_id) return false;

    $data = array_merge([
        'chat_id'                  => $chat_id,
        'text'                     => $matn,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ], $qoshimcha);

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res  = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        error_log("[AvtoTest] Telegram CURL xato: {$curl_err}");
        return false;
    }

    return !empty(json_decode($res, true)['ok']);
}

/** Telegram fayl (hujjat) yuborish. */
function telegram_fayl_yubor(int|string $chat_id, string $fayl_yoli, string $izoh = ''): bool
{
    $token = sozlama('telegram_bot_token');
    if (!$token || !is_file($fayl_yoli)) return false;

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendDocument");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'chat_id'  => $chat_id,
            'caption'  => $izoh,
            'document' => new CURLFile($fayl_yoli),
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    return !empty(json_decode($res, true)['ok']);
}

/** JSON javob va to'xtatish. */
function json_javob(array $data, int $kod = 200): never
{
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** GET parametrini xavfsiz olish. */
function olish(string $kalit, string $standart = ''): string
{
    return isset($_GET[$kalit]) ? trim((string) $_GET[$kalit]) : $standart;
}

/** POST parametrini xavfsiz olish. */
function post(string $kalit, string $standart = ''): string
{
    return isset($_POST[$kalit]) ? trim((string) $_POST[$kalit]) : $standart;
}

/** Foydalanuvchi bosh harflari (avatar uchun). */
function bosh_harflar(?array $f): string
{
    if (!$f) return '?';
    $i  = mb_substr(trim($f['ism']      ?? '?'), 0, 1);
    $fa = mb_substr(trim($f['familiya'] ?? ''),  0, 1);
    return mb_strtoupper($i . $fa);
}

/** Natija rangini aniqlash. */
function natija_rang(int $foiz): string
{
    return match(true) {
        $foiz >= 90 => 'green',
        $foiz >= 70 => 'blue',
        $foiz >= 50 => 'yellow',
        default     => 'red',
    };
}

/** Natija emojisi. */
function natija_emoji(int $foiz): string
{
    return match(true) {
        $foiz >= 90 => '🟢',
        $foiz >= 70 => '🔵',
        $foiz >= 50 => '🟡',
        default     => '🔴',
    };
}

/** Kuchli tasodifiy parol generatsiya. */
function parol_generat(int $uzunlik = 10): string
{
    $belgilar = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
    $parol    = '';
    for ($i = 0; $i < $uzunlik; $i++) {
        $parol .= $belgilar[random_int(0, strlen($belgilar) - 1)];
    }
    return $parol;
}
