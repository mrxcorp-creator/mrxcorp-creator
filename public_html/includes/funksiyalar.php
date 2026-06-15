<?php
require_once __DIR__ . '/../config/database.php';

function pul(int|float $summa, string $valyuta = "so'm"): string {
    return number_format($summa, 0, '.', ' ') . ' ' . $valyuta;
}

function sana(?string $vaqt, string $format = 'd.m.Y H:i'): string {
    if (!$vaqt) return '—';
    return date($format, strtotime($vaqt));
}

function vaqt_oldin(string $vaqt): string {
    $diff = time() - strtotime($vaqt);
    if ($diff < 60)         return $diff . ' soniya oldin';
    if ($diff < 3600)       return floor($diff / 60) . ' daqiqa oldin';
    if ($diff < 86400)      return floor($diff / 3600) . ' soat oldin';
    if ($diff < 2592000)    return floor($diff / 86400) . ' kun oldin';
    return date('d.m.Y', strtotime($vaqt));
}

function rasm_saqla(array $fayl, string $papka = 'savollar', int $maks = 800): ?string {
    if (empty($fayl['tmp_name']) || !is_uploaded_file($fayl['tmp_name'])) {
        return null;
    }
    $papka_yoli = UPLOAD_PATH . '/' . $papka;
    if (!is_dir($papka_yoli)) {
        mkdir($papka_yoli, 0755, true);
    }
    $info = @getimagesize($fayl['tmp_name']);
    if (!$info) return null;

    $manba = match ($info['mime']) {
        'image/jpeg' => imagecreatefromjpeg($fayl['tmp_name']),
        'image/png'  => imagecreatefrompng($fayl['tmp_name']),
        'image/webp' => imagecreatefromwebp($fayl['tmp_name']),
        default      => null,
    };
    if (!$manba) return null;

    [$w, $h] = [$info[0], $info[1]];
    if ($w > $maks || $h > $maks) {
        $nisbat = $maks / max($w, $h);
        $yangi_w = (int) ($w * $nisbat);
        $yangi_h = (int) ($h * $nisbat);
        $yangi = imagecreatetruecolor($yangi_w, $yangi_h);
        imagecopyresampled($yangi, $manba, 0, 0, 0, 0, $yangi_w, $yangi_h, $w, $h);
        imagedestroy($manba);
        $manba = $yangi;
    }
    $nom = uniqid('img_', true) . '.webp';
    $yol = $papka_yoli . '/' . $nom;
    imagewebp($manba, $yol, 80);
    imagedestroy($manba);

    return $papka . '/' . $nom;
}

function telegram_yubor(int|string $chat_id, string $matn, array $qoshimcha = []): bool {
    $token = sozlama('telegram_bot_token');
    if (!$token || !$chat_id) return false;

    $data = array_merge([
        'chat_id'    => $chat_id,
        'text'       => $matn,
        'parse_mode' => 'HTML',
    ], $qoshimcha);

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $javob = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

function telegram_fayl_yubor(int|string $chat_id, string $fayl_yoli, string $izoh = ''): bool {
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
    ]);
    $javob = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

function json_javob(array $data, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function olish(string $kalit, string $standart = ''): string {
    return isset($_GET[$kalit]) ? trim((string) $_GET[$kalit]) : $standart;
}

function post(string $kalit, string $standart = ''): string {
    return isset($_POST[$kalit]) ? trim((string) $_POST[$kalit]) : $standart;
}

function avatar_url(?array $f): string {
    if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])) {
        return SAYT_URL . '/uploads/' . $f['avatar'];
    }
    return '';
}

function bosh_harflar(?array $f): string {
    if (!$f) return '?';
    $i = mb_substr($f['ism'] ?? '?', 0, 1);
    $fa = mb_substr($f['familiya'] ?? '', 0, 1);
    return mb_strtoupper($i . $fa);
}
