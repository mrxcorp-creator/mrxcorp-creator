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



/**
 * ============================================================
 *  YUTUQLAR (Achievements) — avtomatik berish
 * ============================================================
 */

/**
 * Foydalanuvchi shartlari bo'yicha yutuq olishni tekshiradi.
 * Test tugatganda, to'lov qilganda va h.k. chaqiriladi.
 *
 * @param int $foydalanuvchi_id
 * @return array yangi olingan yutuqlar
 */
function yutuqlar_tekshir(int $foydalanuvchi_id): array {
    $yangilar = [];

    // Foydalanuvchi statistikasi
    $jami_test = (int) db_qiymat(
        'SELECT COUNT(*) FROM natijalar WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
        [$foydalanuvchi_id]
    );

    // Streak hisobi
    $kunlar = db_barcha(
        'SELECT DISTINCT DATE(tugagan) AS sana FROM natijalar
         WHERE foydalanuvchi_id = ? AND holat = "tugagan"
         ORDER BY sana DESC LIMIT 60',
        [$foydalanuvchi_id]
    );
    $streak = 0;
    $bugun = strtotime('today');
    foreach ($kunlar as $i => $k) {
        $kun_ts = strtotime($k['sana']);
        $farq = (int) (($bugun - $kun_ts) / 86400);
        if ($farq === $streak) $streak++;
        else break;
    }

    // Mukammal natija
    $mukammal_son = (int) db_qiymat(
        'SELECT COUNT(*) FROM natijalar
         WHERE foydalanuvchi_id = ? AND holat = "tugagan"
           AND umumiy_son > 0 AND togri_son = umumiy_son',
        [$foydalanuvchi_id]
    );

    // Imtihondan o'tish
    $imtihon_pass = (int) db_qiymat(
        'SELECT COUNT(*) FROM natijalar
         WHERE foydalanuvchi_id = ? AND holat = "tugagan"
           AND tur = "imtihon" AND otdimi = 1',
        [$foydalanuvchi_id]
    );

    // Referallar
    $referal_son = (int) db_qiymat(
        'SELECT COUNT(*) FROM referallar WHERE referer_id = ? AND holat = "tasdiq"',
        [$foydalanuvchi_id]
    );

    // Obunalar
    $obuna_son = (int) db_qiymat(
        'SELECT COUNT(*) FROM tolovlar WHERE foydalanuvchi_id = ? AND holat = "muvaffaqiyatli"',
        [$foydalanuvchi_id]
    );

    $statistika = [
        'jami_test'    => $jami_test,
        'streak'       => $streak,
        'mukammal'     => $mukammal_son,
        'imtihon_pass' => $imtihon_pass,
        'referal'      => $referal_son,
        'obuna'        => $obuna_son,
    ];

    // Hali olinmagan yutuqlarni topamiz
    $barcha_yutuqlar = db_barcha(
        'SELECT y.* FROM yutuqlar y
         WHERE y.id NOT IN (
             SELECT yutuq_id FROM foydalanuvchi_yutuqlar WHERE foydalanuvchi_id = ?
         )',
        [$foydalanuvchi_id]
    );

    foreach ($barcha_yutuqlar as $y) {
        $tur = $y['shart_turi'];
        $qiymat = (int) $y['shart_qiymati'];
        if (!$tur || !isset($statistika[$tur])) continue;

        if ($statistika[$tur] >= $qiymat) {
            // Yutuq beriladi
            db_bajar(
                'INSERT IGNORE INTO foydalanuvchi_yutuqlar (foydalanuvchi_id, yutuq_id) VALUES (?, ?)',
                [$foydalanuvchi_id, $y['id']]
            );
            // Bonus balansga XP qo'shamiz (XP miqdori — 100 XP = 1000 so'm bonus konvertatsiyasi yo'q,
            // hozircha shunchaki XP bo'lib turadi, lekin keyinroq foydalansa bo'ladi)

            // Bildirishnoma
            bildirishnoma_yarat(
                $foydalanuvchi_id,
                "Yangi yutuq: {$y['nomi']}!",
                "{$y['ikon']} {$y['tavsif']}\nSizning kollektsiyangizga qo'shildi.",
                '/profil#yutuqlar',
                'muvaffaqiyat',
                $y['ikon']
            );

            $yangilar[] = $y;
        }
    }

    return $yangilar;
}

/**
 * Foydalanuvchining barcha yutuqlari.
 */
function foydalanuvchi_yutuqlari(int $foydalanuvchi_id): array {
    return db_barcha(
        'SELECT y.*, fy.olingan FROM yutuqlar y
         JOIN foydalanuvchi_yutuqlar fy ON y.id = fy.yutuq_id
         WHERE fy.foydalanuvchi_id = ?
         ORDER BY fy.olingan DESC',
        [$foydalanuvchi_id]
    );
}

/**
 * Foydalanuvchining jami XP'si.
 */
function foydalanuvchi_xp(int $foydalanuvchi_id): int {
    return (int) db_qiymat(
        'SELECT COALESCE(SUM(y.xp), 0) FROM foydalanuvchi_yutuqlar fy
         JOIN yutuqlar y ON fy.yutuq_id = y.id
         WHERE fy.foydalanuvchi_id = ?',
        [$foydalanuvchi_id]
    );
}

/**
 * ============================================================
 *  AUDIT LOG — admin amallarini yozib qo'yish
 * ============================================================
 */
function audit_yoz(int $foydalanuvchi_id, string $amal, ?string $obyekt = null,
                   ?int $obyekt_id = null, ?string $tafsilot = null): void {
    db_bajar(
        'INSERT INTO audit_log (foydalanuvchi_id, amal, obyekt, obyekt_id, tafsilot, ip, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $foydalanuvchi_id, $amal, $obyekt, $obyekt_id, $tafsilot,
            ip_olish(),
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
        ]
    );
}



/**
 * ============================================================
 *  TRANSLITERATSIYA (Lotin → Kirill)
 * ============================================================
 *
 *  Sayt kontenti faqat O'zbek lotin alifbosida yoziladi.
 *  Foydalanuvchi kirill alifbosini tanlasa — output buffer
 *  orqali avtomatik konvertatsiya qilinadi.
 */

/**
 * O'zbek lotin matnni kirill alifbosiga o'tkazish.
 */
function lotin_kirill(string $matn): string {
    // Apostrof variantlari: '  ʻ  ' '
    $matn = strtr($matn, [
        "ʻ" => "'", "ʼ" => "'", "‘" => "'", "’" => "'", "`" => "'",
    ]);

    // Multi-char (uzunroq kalitlar avval mos keladi)
    static $jadval = null;
    if ($jadval === null) {
        $jadval = [
            "Sh"  => "Ш",  "SH"  => "Ш",  "sh"  => "ш",
            "Ch"  => "Ч",  "CH"  => "Ч",  "ch"  => "ч",
            "Yo"  => "Ё",  "YO"  => "Ё",  "yo"  => "ё",
            "Ya"  => "Я",  "YA"  => "Я",  "ya"  => "я",
            "Yu"  => "Ю",  "YU"  => "Ю",  "yu"  => "ю",
            "Ts"  => "Ц",  "TS"  => "Ц",  "ts"  => "ц",
            "O'"  => "Ў",  "o'"  => "ў",
            "G'"  => "Ғ",  "g'"  => "ғ",
            "A"=>"А","a"=>"а", "B"=>"Б","b"=>"б", "V"=>"В","v"=>"в",
            "G"=>"Г","g"=>"г", "D"=>"Д","d"=>"д", "E"=>"Е","e"=>"е",
            "J"=>"Ж","j"=>"ж", "Z"=>"З","z"=>"з", "I"=>"И","i"=>"и",
            "Y"=>"Й","y"=>"й", "K"=>"К","k"=>"к", "L"=>"Л","l"=>"л",
            "M"=>"М","m"=>"м", "N"=>"Н","n"=>"н", "O"=>"О","o"=>"о",
            "P"=>"П","p"=>"п", "R"=>"Р","r"=>"р", "S"=>"С","s"=>"с",
            "T"=>"Т","t"=>"т", "U"=>"У","u"=>"у", "F"=>"Ф","f"=>"ф",
            "X"=>"Х","x"=>"х", "H"=>"Ҳ","h"=>"ҳ", "Q"=>"Қ","q"=>"қ",
        ];
    }
    return strtr($matn, $jadval);
}

/**
 * HTML kontentni transliteratsiya qilish — tag/atribut/script/style/url'larga tegmaydi.
 */
function lotin_kirill_html(string $html): string {
    $tilim = $_SESSION['til'] ?? 'uz_latn';
    if ($tilim !== 'uz_cyrl') {
        return $html;
    }

    $saqlash = [];
    $i = 0;
    $marker = function ($m) use (&$saqlash, &$i) {
        // Marker faqat raqamlar va boshqaruv belgilaridan iborat — transliteratsiyaga uchramaydi
        $key = "\x02" . $i . "\x03";
        $i++;
        $saqlash[$key] = $m[0];
        return $key;
    };

    // 1) Saqlanadigan elementlar (script, style, code, pre va h.k.)
    $html = preg_replace_callback(
        '#<(script|style|noscript|code|pre|kbd|samp)\b[^>]*>.*?</\1>#is',
        $marker, $html
    );
    // 2) HTML komentlar
    $html = preg_replace_callback('/<!--.*?-->/s', $marker, $html);
    // 3) HTML tag'lari (atributlar bilan)
    $html = preg_replace_callback('/<[^>]+>/i', $marker, $html);
    // 4) HTML entity'lari
    $html = preg_replace_callback('/&(?:[a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);/', $marker, $html);
    // 5) URL'lar oddiy matnda
    $html = preg_replace_callback('#(?:https?://|mailto:|tel:)[^\s<]+#i', $marker, $html);

    // 6) Qolgan matnni transliteratsiya
    $html = lotin_kirill($html);

    // 7) Saqlanganlarni qaytarish
    if (!empty($saqlash)) {
        $html = strtr($html, $saqlash);
    }
    return $html;
}

/**
 * Output buffer callback — header.php boshida ishlatiladi.
 */
function transliteratsiya_filtri(string $buffer): string {
    return lotin_kirill_html($buffer);
}
