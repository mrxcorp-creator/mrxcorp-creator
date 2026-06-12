<?php
/**
 * VatanParvar Yaypan — Umumiy yordamchi funksiyalar
 * ------------------------------------------------------------
 *  - Pul formati
 *  - Sana formati
 *  - Rasmni WebP ga konvertatsiya qilish
 *  - Telegramga xabar yuborish
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Summani UZS formatida chiqarish: 25 000 so'm
 */
function pul(int|float $summa, string $valyuta = "so'm"): string {
    return number_format($summa, 0, '.', ' ') . ' ' . $valyuta;
}

/**
 * Sana formati.
 */
function sana(?string $vaqt, string $format = 'd.m.Y H:i'): string {
    if (!$vaqt) return '—';
    return date($format, strtotime($vaqt));
}

/**
 * Necha vaqt oldin bo'lganini chiqarish.
 */
function vaqt_oldin(string $vaqt): string {
    $diff = time() - strtotime($vaqt);
    if ($diff < 60)         return $diff . ' soniya oldin';
    if ($diff < 3600)       return floor($diff / 60) . ' daqiqa oldin';
    if ($diff < 86400)      return floor($diff / 3600) . ' soat oldin';
    if ($diff < 2592000)    return floor($diff / 86400) . ' kun oldin';
    return date('d.m.Y', strtotime($vaqt));
}

/**
 * Rasmni WebP ga konvertatsiya qilib saqlash.
 */
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

/**
 * Telegram bot orqali xabar yuborish.
 */
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

/**
 * Telegramga fayl yuborish.
 */
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

/**
 * JSON javob qaytarish va to'xtatish.
 */
function json_javob(array $data, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * URL'dagi qiymatni xavfsiz olish.
 */
function olish(string $kalit, string $standart = ''): string {
    return isset($_GET[$kalit]) ? trim((string) $_GET[$kalit]) : $standart;
}

/**
 * POST'dagi qiymatni xavfsiz olish.
 */
function post(string $kalit, string $standart = ''): string {
    return isset($_POST[$kalit]) ? trim((string) $_POST[$kalit]) : $standart;
}

/**
 * Foydalanuvchi avatarini olish (yoki bosh harflar).
 */
function avatar_url(?array $f): string {
    if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])) {
        return SAYT_URL . '/uploads/' . $f['avatar'];
    }
    return ''; // bosh harflar bilan ko'rsatamiz
}

/**
 * Bosh harflarni olish (avatar uchun).
 */
function bosh_harflar(?array $f): string {
    if (!$f) return '?';
    $i = mb_substr($f['ism'] ?? '?', 0, 1);
    $fa = mb_substr($f['familiya'] ?? '', 0, 1);
    return mb_strtoupper($i . $fa);
}



/**
 * ============================================================
 *  AI YORDAMCHI (Gemini)
 * ============================================================
 *
 *  Foydalanuvchi xabariga AI javob qaytaradi.
 *  Suhbat tarixi (oxirgi N xabar) kontekst sifatida yuboriladi.
 *
 * @param string $foydalanuvchi_xabari  yangi xabar
 * @param array  $tarix                 oxirgi xabarlar [['kimdan'=>'user','matn'=>'...']]
 * @return string|null                  AI javobi yoki null (xato bo'lsa)
 */
function ai_javob_olish(string $foydalanuvchi_xabari, array $tarix = []): ?string {
    if (!defined('GEMINI_API_KEY') || !GEMINI_API_KEY || !defined('AI_AKTIV') || !AI_AKTIV) {
        return null;
    }

    // Tarixni Gemini formatiga o'tkazamiz
    $contents = [];
    foreach ($tarix as $x) {
        $rol = $x['kimdan'] === 'user' ? 'user' : 'model';
        $contents[] = ['role' => $rol, 'parts' => [['text' => (string) $x['matn']]]];
    }
    // Joriy xabar
    $contents[] = ['role' => 'user', 'parts' => [['text' => $foydalanuvchi_xabari]]];

    $payload = [
        'systemInstruction' => [
            'parts' => [['text' => AI_SYSTEM_PROMPT]],
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature'     => 0.7,
            'maxOutputTokens' => 800,
            'topP'            => 0.9,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',         'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',        'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',  'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',  'threshold' => 'BLOCK_ONLY_HIGH'],
        ],
    ];

    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $javob = curl_exec($ch);
    $kod   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($kod !== 200 || !$javob) {
        error_log("Gemini API xato: kod={$kod}, javob=" . substr((string) $javob, 0, 500));
        return null;
    }

    $j = json_decode($javob, true);
    $matn = $j['candidates'][0]['content']['parts'][0]['text'] ?? null;
    return $matn ? trim($matn) : null;
}

/**
 * Foydalanuvchi uchun bildirishnoma yaratish.
 */
function bildirishnoma_yarat(
    int $foydalanuvchi_id,
    string $sarlavha,
    string $matn = '',
    string $link = '',
    string $tur = 'info',
    string $ikon = ''
): int {
    if (!$ikon) {
        $ikon = match ($tur) {
            'muvaffaqiyat'    => '✅',
            'ogohlantirish'   => '⚠️',
            'xato'            => '❌',
            default           => '🔔',
        };
    }
    return db_bajar(
        'INSERT INTO bildirishnomalar (foydalanuvchi_id, sarlavha, matn, link, tur, ikon)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$foydalanuvchi_id, $sarlavha, $matn, $link, $tur, $ikon]
    );
}

/**
 * Foydalanuvchining o'qilmagan bildirishnomalari soni.
 */
function bildirishnoma_son(int $foydalanuvchi_id): int {
    return (int) db_qiymat(
        'SELECT COUNT(*) FROM bildirishnomalar WHERE foydalanuvchi_id = ? AND oqilgan = 0',
        [$foydalanuvchi_id]
    );
}

/**
 * Foydalanuvchining o'qilmagan chat xabarlari soni
 * (admin/AI tomonidan yuborilganlar).
 */
function chat_oqilmagan_son(int $foydalanuvchi_id): int {
    return (int) db_qiymat(
        'SELECT COUNT(*) FROM chat_xabarlar
         WHERE foydalanuvchi_id = ? AND kimdan IN ("admin","ai") AND oqilgan = 0',
        [$foydalanuvchi_id]
    );
}

/**
 * Suhbatda admin oxirgi N daqiqada yozganmi? (AI ni o'chirish uchun)
 */
function chat_admin_aktivmi(int $foydalanuvchi_id, int $daqiqa = 30): bool {
    return (bool) db_qiymat(
        'SELECT COUNT(*) FROM chat_xabarlar
         WHERE foydalanuvchi_id = ? AND kimdan = "admin"
           AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
        [$foydalanuvchi_id, $daqiqa]
    );
}
