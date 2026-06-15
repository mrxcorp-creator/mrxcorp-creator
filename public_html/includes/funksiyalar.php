<?php
/**
 * AvtoTest Pro — Umumiy yordamchi funksiyalar
 * ------------------------------------------------------------
 *  - Pul formati
 *  - Sana formati
 *  - Rasm WebP ga konvertatsiya
 *  - Telegramga xabar/fayl yuborish
 *  - JSON javob
 *  - Avatar / bosh harflar
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Summani formatlangan UZS ko'rinishida qaytarish.
 * Misol: 25000 → "25 000 so'm"
 */
function pul(int|float $summa, string $valyuta = "so'm"): string
{
    return number_format((float) $summa, 0, '.', ' ') . ' ' . $valyuta;
}

/**
 * Sanani formatlash.
 */
function sana(?string $vaqt, string $format = 'd.m.Y H:i'): string
{
    if (!$vaqt) {
        return '—';
    }
    $ts = strtotime($vaqt);
    return $ts ? date($format, $ts) : '—';
}

/**
 * "N vaqt oldin" ko'rinishida chiqarish.
 */
function vaqt_oldin(string $vaqt): string
{
    $diff = time() - strtotime($vaqt);
    if ($diff < 0)        return 'Hozirgina';
    if ($diff < 60)       return $diff . ' soniya oldin';
    if ($diff < 3600)     return floor($diff / 60) . ' daqiqa oldin';
    if ($diff < 86400)    return floor($diff / 3600) . ' soat oldin';
    if ($diff < 2592000)  return floor($diff / 86400) . ' kun oldin';
    return date('d.m.Y', strtotime($vaqt));
}

/**
 * Rasmni WebP ga konvertatsiya qilib saqlash.
 * Muvaffaqiyatli bo'lsa nisbiy yo'lni qaytaradi (masalan: savollar/img_xxx.webp).
 */
function rasm_saqla(array $fayl, string $papka = 'savollar', int $maks = 800): ?string
{
    if (empty($fayl['tmp_name']) || !is_uploaded_file($fayl['tmp_name'])) {
        return null;
    }

    // Fayl hajmini tekshirish (maks 5 MB)
    if ($fayl['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $papka_yoli = UPLOAD_PATH . '/' . $papka;
    if (!is_dir($papka_yoli)) {
        mkdir($papka_yoli, 0755, true);
    }

    $info = @getimagesize($fayl['tmp_name']);
    if (!$info) {
        return null;
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
        $yangi_w = (int) round($w * $nisbat);
        $yangi_h = (int) round($h * $nisbat);
        $yangi   = imagecreatetruecolor($yangi_w, $yangi_h);

        // PNG/GIF shaffofligini saqlash
        if (in_array($info['mime'], ['image/png', 'image/gif'], true)) {
            imagealphablending($yangi, false);
            imagesavealpha($yangi, true);
        }

        imagecopyresampled($yangi, $manba, 0, 0, 0, 0, $yangi_w, $yangi_h, $w, $h);
        imagedestroy($manba);
        $manba = $yangi;
    }

    $nom = uniqid('img_', true) . '.webp';
    $yol = $papka_yoli . '/' . $nom;
    imagewebp($manba, $yol, 82);
    imagedestroy($manba);

    return $papka . '/' . $nom;
}

/**
 * Telegram bot orqali matn xabar yuborish.
 */
function telegram_yubor(int|string $chat_id, string $matn, array $qoshimcha = []): bool
{
    $token = sozlama('telegram_bot_token');
    if (!$token || !$chat_id) {
        return false;
    }

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
    $javob = curl_exec($ch);
    $xato  = curl_error($ch);
    curl_close($ch);

    if ($xato) {
        error_log("Telegram CURL xatosi: $xato");
        return false;
    }

    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

/**
 * Telegram bot orqali fayl (hujjat) yuborish.
 */
function telegram_fayl_yubor(int|string $chat_id, string $fayl_yoli, string $izoh = ''): bool
{
    $token = sozlama('telegram_bot_token');
    if (!$token || !is_file($fayl_yoli)) {
        return false;
    }

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
    $javob = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

/**
 * JSON javob chiqarib dasturni to'xtatish.
 */
function json_javob(array $data, int $kod = 200): never
{
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * GET parametrini xavfsiz olish.
 */
function olish(string $kalit, string $standart = ''): string
{
    return isset($_GET[$kalit]) ? trim((string) $_GET[$kalit]) : $standart;
}

/**
 * POST parametrini xavfsiz olish.
 */
function post(string $kalit, string $standart = ''): string
{
    return isset($_POST[$kalit]) ? trim((string) $_POST[$kalit]) : $standart;
}

/**
 * Foydalanuvchi avatar URL'ini qaytarish.
 */
function avatar_url(?array $f): string
{
    if (!$f) return '';
    if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])) {
        return SAYT_URL . '/uploads/' . $f['avatar'];
    }
    return '';
}

/**
 * Foydalanuvchining bosh harflari (avatar o'rniga).
 * Misol: "Akbar Yusupov" → "AY"
 */
function bosh_harflar(?array $f): string
{
    if (!$f) return '?';
    $i  = mb_substr(trim($f['ism'] ?? '?'), 0, 1);
    $fa = mb_substr(trim($f['familiya'] ?? ''), 0, 1);
    return mb_strtoupper($i . $fa);
}

/**
 * Tasodifiy quvvatli parol generatsiya qilish.
 */
function parol_generat(int $uzunlik = 10): string
{
    $belgilar = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
    $parol = '';
    for ($i = 0; $i < $uzunlik; $i++) {
        $parol .= $belgilar[random_int(0, strlen($belgilar) - 1)];
    }
    return $parol;
}

/**
 * Testni tugatilgandan so'ng bilet reyting rangini qaytarish.
 */
function natija_rang(int $foiz): string
{
    return match(true) {
        $foiz >= 90 => 'green',
        $foiz >= 70 => 'blue',
        $foiz >= 50 => 'yellow',
        default     => 'red',
    };
}

/**
 * Natija emojilarini qaytarish.
 */
function natija_emoji(int $foiz): string
{
    return match(true) {
        $foiz >= 90 => '🟢',
        $foiz >= 70 => '🔵',
        $foiz >= 50 => '🟡',
        default     => '🔴',
    };
}
