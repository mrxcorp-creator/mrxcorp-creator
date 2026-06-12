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
require_once __DIR__ . '/transliterator.php';

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
 * Tarjima kontent — DB qatoridan tilga mos maydon qiymatini olish.
 *
 * Misol:
 *   $savol = db_qator('SELECT * FROM savollar WHERE id=?', [5]);
 *   echo tk($savol, 'matn');           // joriy tilga qarab matn yoki matn_cyrl
 *   echo tk($savol, 'variant_a');      // variant_a yoki variant_a_cyrl
 *
 * Qoidalar:
 *   - $_SESSION['til'] === 'uz_cyrl' va `<maydon>_cyrl` mavjud bo'lsa — uni qaytaradi.
 *   - Kirill maydoni bo'sh bo'lsa — lotin maydonini avtomatik kirillga o'giradi.
 *   - Aks holda lotin maydonini qaytaradi.
 *   - Qator yoki maydon bo'lmasa — bo'sh string qaytaradi.
 *
 * @param array<string,mixed>|null $qator   DB-dan kelgan qator
 * @param string                   $maydon  Maydon nomi (lotin)
 * @return string
 */
function tk(?array $qator, string $maydon): string {
    if (!$qator) return '';

    $til = $_SESSION['til'] ?? 'uz_latn';
    $latn = (string) ($qator[$maydon] ?? '');

    if ($til === 'uz_cyrl') {
        $cyrl = (string) ($qator[$maydon . '_cyrl'] ?? '');
        if ($cyrl !== '') return $cyrl;
        // Kirill versiyasi yo'q — runtime'da konvertatsiya
        return $latn === '' ? '' : lotin_dan_kirill($latn);
    }

    return $latn;
}

/**
 * Sozlamalar uchun tilga mos qiymatni olish.
 *
 * Misol:
 *   echo ts('sayt_nomi');   // 'sayt_nomi' yoki 'sayt_nomi_cyrl' (tilga qarab)
 */
function ts(string $kalit, string $standart = ''): string {
    $til = $_SESSION['til'] ?? 'uz_latn';
    if ($til === 'uz_cyrl') {
        $cyrl = sozlama($kalit . '_cyrl', '');
        if ($cyrl !== '') return $cyrl;
        $latn = sozlama($kalit, $standart);
        return $latn === '' ? '' : lotin_dan_kirill($latn);
    }
    return sozlama($kalit, $standart);
}

/**
 * Foydalanuvchi ismini tilga mos ko'rsatish (ism + familiya).
 * Foydalanuvchi ma'lumoti _cyrl ustunsiz, shu sababli runtime konvertatsiya.
 */
function fu_ism(?array $f): string {
    if (!$f) return '';
    $matn = trim(($f['ism'] ?? '') . ' ' . ($f['familiya'] ?? ''));
    if (($_SESSION['til'] ?? 'uz_latn') === 'uz_cyrl') {
        return lotin_dan_kirill($matn);
    }
    return $matn;
}


/**
 * Sayt logosini HTML ko'rinishida qaytaradi.
 *
 * Agar admin logo yuklagan bo'lsa — rasm sifatida ko'rinadi.
 * Aks holda — gradient "V" harf (default).
 *
 * @param string $olcham 'sm' (32px) | 'md' (40px) | 'lg' (56px)
 * @param bool   $faqat_ikona  true bo'lsa, "VatanParvar" matni qo'shilmaydi
 * @return string HTML
 */
function logo_html(string $olcham = 'md', bool $faqat_ikona = true): string {
    $olchamlar = [
        'sm' => ['box' => '32', 'rounded' => 'rounded-lg',  'matn' => 'text-base', 'gap' => '2'],
        'md' => ['box' => '40', 'rounded' => 'rounded-xl',  'matn' => 'text-lg',   'gap' => '2.5'],
        'lg' => ['box' => '56', 'rounded' => 'rounded-2xl', 'matn' => 'text-2xl',  'gap' => '2.5'],
    ];
    $o = $olchamlar[$olcham] ?? $olchamlar['md'];

    $logo_fayl = sozlama('sayt_logo', '');
    $bor = $logo_fayl && is_file(UPLOAD_PATH . '/dizayn/' . $logo_fayl);

    $w = $o['box']; $h = $o['box'];

    if ($bor) {
        $rasm = '<img src="' . htmlspecialchars(SAYT_URL . '/uploads/dizayn/' . $logo_fayl, ENT_QUOTES) . '" '
              . 'alt="Logo" class="' . $o['rounded'] . ' object-cover shadow-md" '
              . 'style="width:' . $w . 'px;height:' . $h . 'px;">';
    } else {
        $rasm = '<span class="' . $o['rounded'] . ' flex items-center justify-center font-display font-bold text-white shadow-md '
              . $o['matn']
              . '" style="width:' . $w . 'px;height:' . $h . 'px;'
              . 'background: var(--gradient-primary); box-shadow: 0 8px 24px var(--accent-glow);">V</span>';
    }

    if ($faqat_ikona) return $rasm;

    return '<span class="flex items-center gap-' . $o['gap'] . '">'
         . $rasm
         . '<span class="font-display font-bold text-app ' . $o['matn'] . '">VatanParvar</span>'
         . '</span>';
}

/**
 * Bosh sahifa hero banner — agar admin yuklagan bo'lsa va faollashtirgan bo'lsa.
 *
 * @return array{rasm: string, havola: string}|null
 */
function banner_olish(): ?array {
    if (sozlama('bosh_banner_aktiv', '0') !== '1') return null;
    $rasm = sozlama('bosh_banner', '');
    if (!$rasm || !is_file(UPLOAD_PATH . '/dizayn/' . $rasm)) return null;
    return [
        'rasm'   => SAYT_URL . '/uploads/dizayn/' . $rasm,
        'havola' => sozlama('bosh_banner_havola', ''),
    ];
}

/**
 * Reklama chiqarish — joylashuvga qarab eng mos reklamani topadi.
 *
 * Agar bir nechta reklama bo'lsa, tartib bo'yicha (kichikdan kattaga) yoki
 * tasodifiy tanlanadi. Ko'rish soni avtomatik oshiriladi.
 *
 * @param string $joylashuv  'bosh_yuqori' | 'bosh_pastki' | 'user_yon' | 'test_oraligi' | 'sidebar'
 * @return string HTML (yoki bo'sh string agar reklama yo'q bo'lsa)
 */
function reklama_chiqar(string $joylashuv): string {
    $joriy = db_qator(
        'SELECT * FROM reklamalar
         WHERE joylashuv = ? AND holat = "faol"
           AND (boshlanish IS NULL OR boshlanish <= CURDATE())
           AND (tugash IS NULL OR tugash >= CURDATE())
         ORDER BY tartib ASC, RAND()
         LIMIT 1',
        [$joylashuv]
    );
    if (!$joriy) return '';

    // Ko'rish sonini oshirish (silently)
    @db_bajar('UPDATE reklamalar SET korish_soni = korish_soni + 1 WHERE id = ?', [$joriy['id']]);

    $rasm_url = SAYT_URL . '/uploads/dizayn/' . $joriy['rasm'];
    if (!is_file(UPLOAD_PATH . '/dizayn/' . $joriy['rasm'])) return '';

    $havola = $joriy['havola'] ?: '';
    $hedef  = $joriy['havola_yangi_oyna'] ? '_blank' : '_self';
    $rel    = $joriy['havola_yangi_oyna'] ? 'noopener sponsored' : 'sponsored';

    $bosish_url = SAYT_URL . '/api/reklama_bosish.php?id=' . (int)$joriy['id'];
    $cls = 'reklama-blok';

    $img = '<img src="' . htmlspecialchars($rasm_url, ENT_QUOTES) . '" '
         . 'alt="' . htmlspecialchars($joriy['nomi'], ENT_QUOTES) . '" '
         . 'class="w-full h-auto rounded-2xl shadow-md transition hover:scale-[1.02]" loading="lazy">';

    $badge = '<span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 rounded-full bg-black/40 text-white/80 font-medium tracking-wider">REKLAMA</span>';

    if ($havola) {
        return '<a href="' . htmlspecialchars($bosish_url . '&u=' . urlencode($havola), ENT_QUOTES) . '" '
             . 'target="' . $hedef . '" rel="' . $rel . '" '
             . 'class="' . $cls . ' block relative my-6 group">'
             . $img . $badge
             . '</a>';
    }

    return '<div class="' . $cls . ' relative my-6">' . $img . $badge . '</div>';
}

/**
 * Reklama bosildi — click count'ni oshiradi.
 * (api/reklama_bosish.php tomonidan chaqiriladi)
 */
function reklama_bosildi(int $reklama_id): void {
    @db_bajar('UPDATE reklamalar SET bosish_soni = bosish_soni + 1 WHERE id = ?', [$reklama_id]);
}
