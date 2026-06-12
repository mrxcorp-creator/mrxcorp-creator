<?php
/**
 * VatanParvar Yaypan — Lotin ↔ Kirill transliterator
 * ------------------------------------------------------------
 * O'zbek tilining lotin va kirill yozuvlari orasida
 * avtomatik konvertatsiya qiladi.
 *
 * Asosiy qoidalar:
 *   - Apostrof variantlari ('ʻ`'ʼ’) → universal
 *   - O' / G' → Ў / Ғ
 *   - sh ch yo yu ya ye ts → ш ч ё ю я е ц
 *   - so'z boshidagi "e" → "э", aks holda → "е"
 *   - Bosh harflar saqlanadi
 *
 * Ishlatish:
 *   $kirill = lotin_dan_kirill("Salom, dunyo!");      // "Салом, дунё!"
 *   $lotin  = kirill_dan_lotin("Салом, дунё!");      // "Salom, dunyo!"
 *   $skript = matn_skripti("Салом");                  // "cyrl"
 *   [$lat, $cyr] = ikkita_versiya("Salom");           // ["Salom","Салом"]
 */

/**
 * Lotin yozuvidagi matnni kirill yozuviga o'giradi.
 *
 * Algoritm 4 bosqichli:
 *   1. Apostrof variantlarini bitta standartga keltirish (')
 *   2. Apostrof bilan bog'liq grafikalar: O' G' → Ў Ғ
 *   3. Ko'p harfli grafikalar: Sh Ch Yo Yu Ya Ye Ts → Ш Ч Ё Ю Я Е Ц
 *   4. So'z boshidagi "e" → "э" (qolganlari "е" bo'ladi)
 *   5. Bir harfli almashtirishlar
 */
function lotin_dan_kirill(string $matn): string {
    if ($matn === '') return '';

    // 1. Apostrof variantlarini standartlashtirish
    //    ʻ (U+02BB), ʼ (U+02BC), ’ (U+2019), ` (grave), ' (apostrophe)
    $matn = str_replace(["\u{02BB}", "\u{02BC}", "\u{2019}", '`'], "'", $matn);

    // 2. Apostrof grafikalari (eng spetsifik — birinchi)
    $apostrof_xarita = [
        "O'" => 'Ў', "o'" => 'ў',
        "G'" => 'Ғ', "g'" => 'ғ',
    ];
    $matn = strtr($matn, $apostrof_xarita);

    // 3. Ko'p harfli grafikalar (uzunidan boshlab)
    //    Eslatma: "ch", "sh", "ts" so'z boshidagi katta harfli holatlarni ham
    //    qamrab oladi (CHIROY, SHAHAR, TSEX) — bu uchun har biriga 3 ta variant beramiz.
    $kop_xarita = [
        // Uppercase digraph (CH, SH, ...)
        'CH' => 'Ч',  'SH' => 'Ш',  'YO' => 'Ё',  'YU' => 'Ю',
        'YA' => 'Я',  'YE' => 'Е',  'TS' => 'Ц',
        // Title case (Ch, Sh, ...)
        'Ch' => 'Ч',  'Sh' => 'Ш',  'Yo' => 'Ё',  'Yu' => 'Ю',
        'Ya' => 'Я',  'Ye' => 'Е',  'Ts' => 'Ц',
        // Lowercase
        'ch' => 'ч',  'sh' => 'ш',  'yo' => 'ё',  'yu' => 'ю',
        'ya' => 'я',  'ye' => 'е',  'ts' => 'ц',
    ];
    $matn = strtr($matn, $kop_xarita);

    // 4. So'z boshidagi "e" → "э"
    //    \b — so'z chegarasi (Unicode-aware u flag bilan)
    $matn = preg_replace('/\bE/u', 'Э', $matn);
    $matn = preg_replace('/\be/u', 'э', $matn);

    // 5. Bir harfli xarita
    $bir_xarita = [
        'A' => 'А', 'a' => 'а',
        'B' => 'Б', 'b' => 'б',
        'D' => 'Д', 'd' => 'д',
        'E' => 'Е', 'e' => 'е', // qolganlari (so'z o'rtasi/oxiri)
        'F' => 'Ф', 'f' => 'ф',
        'G' => 'Г', 'g' => 'г',
        'H' => 'Ҳ', 'h' => 'ҳ',
        'I' => 'И', 'i' => 'и',
        'J' => 'Ж', 'j' => 'ж',
        'K' => 'К', 'k' => 'к',
        'L' => 'Л', 'l' => 'л',
        'M' => 'М', 'm' => 'м',
        'N' => 'Н', 'n' => 'н',
        'O' => 'О', 'o' => 'о',
        'P' => 'П', 'p' => 'п',
        'Q' => 'Қ', 'q' => 'қ',
        'R' => 'Р', 'r' => 'р',
        'S' => 'С', 's' => 'с',
        'T' => 'Т', 't' => 'т',
        'U' => 'У', 'u' => 'у',
        'V' => 'В', 'v' => 'в',
        'W' => 'В', 'w' => 'в',
        'X' => 'Х', 'x' => 'х',
        'Y' => 'Й', 'y' => 'й',
        'Z' => 'З', 'z' => 'з',
        'C' => 'С', 'c' => 'с', // mustaqil "c" o'zbek tilida kam, "С" ga
    ];
    return strtr($matn, $bir_xarita);
}

/**
 * Kirill yozuvidagi matnni lotin yozuviga o'giradi.
 *
 * Algoritm:
 *   1. So'z boshidagi "е" → "Ye" / "ye"
 *   2. Ikki harfli almashtirishlar (Ё → Yo, Ю → Yu, Я → Ya, Ц → Ts, Ч → Ch, Ш → Sh, Ў → O', Ғ → G')
 *   3. Bir harfli almashtirishlar
 */
function kirill_dan_lotin(string $matn): string {
    if ($matn === '') return '';

    // 1. So'z boshidagi "е/Е" → "ye/Ye" (Ехтиром emas, balki Yetti uchun)
    //    O'zbek tilida bu harf so'z boshida "ye" deb yoziladi.
    $matn = preg_replace('/\bЕ/u', 'Ye', $matn);
    $matn = preg_replace('/\bе/u', 'ye', $matn);

    // 2 + 3. Bitta katta xarita (PHP strtr eng uzun matchni avtomatik tanlaydi)
    $xarita = [
        // Ko'p harfli (avval keladi)
        'Ё' => 'Yo', 'ё' => 'yo',
        'Ю' => 'Yu', 'ю' => 'yu',
        'Я' => 'Ya', 'я' => 'ya',
        'Ц' => 'Ts', 'ц' => 'ts',
        'Ч' => 'Ch', 'ч' => 'ch',
        'Ш' => 'Sh', 'ш' => 'sh',
        'Щ' => 'Sh', 'щ' => 'sh', // ruscha "щ" → "sh"
        'Ў' => "O'", 'ў' => "o'",
        'Ғ' => "G'", 'ғ' => "g'",

        // Bir harfli
        'А' => 'A', 'а' => 'a',
        'Б' => 'B', 'б' => 'b',
        'В' => 'V', 'в' => 'v',
        'Г' => 'G', 'г' => 'g',
        'Д' => 'D', 'д' => 'd',
        'Е' => 'E', 'е' => 'e',     // so'z o'rtasidagi е (boshidagi yuqorida ye-ga aylangan)
        'Ж' => 'J', 'ж' => 'j',
        'З' => 'Z', 'з' => 'z',
        'И' => 'I', 'и' => 'i',
        'Й' => 'Y', 'й' => 'y',
        'К' => 'K', 'к' => 'k',
        'Л' => 'L', 'л' => 'l',
        'М' => 'M', 'м' => 'm',
        'Н' => 'N', 'н' => 'n',
        'О' => 'O', 'о' => 'o',
        'П' => 'P', 'п' => 'p',
        'Р' => 'R', 'р' => 'r',
        'С' => 'S', 'с' => 's',
        'Т' => 'T', 'т' => 't',
        'У' => 'U', 'у' => 'u',
        'Ф' => 'F', 'ф' => 'f',
        'Х' => 'X', 'х' => 'x',
        'Ъ' => "'", 'ъ' => "'",
        'Ы' => 'I', 'ы' => 'i',
        'Ь' => '',  'ь' => '',
        'Э' => 'E', 'э' => 'e',
        'Қ' => 'Q', 'қ' => 'q',
        'Ҳ' => 'H', 'ҳ' => 'h',
        'Ҷ' => 'J', 'ҷ' => 'j', // tojik j ham bor bo'lishi mumkin
    ];
    return strtr($matn, $xarita);
}

/**
 * Matnning qaysi yozuvda yozilganini aniqlaydi.
 *
 * @return string  'cyrl' | 'latn' | 'mixed' | 'empty'
 */
function matn_skripti(string $matn): string {
    if (trim($matn) === '') return 'empty';

    // Cyrillic Unicode bloki: U+0400 — U+04FF (asosiy) + U+0500 — U+052F (qo'shimcha)
    $cyr = preg_match_all('/[\x{0400}-\x{04FF}]/u', $matn);
    $lat = preg_match_all('/[A-Za-z]/u', $matn);

    if ($cyr > 0 && $lat === 0) return 'cyrl';
    if ($lat > 0 && $cyr === 0) return 'latn';
    if ($cyr > 0 && $lat > 0)   return 'mixed';
    return 'empty'; // raqam/punktuatsiyadan iborat
}

/**
 * Bir matnning ikki versiyasini ham olib qaytaradi.
 *
 * Agar matn lotin bo'lsa  — kirillga o'giradi.
 * Agar matn kirill bo'lsa — lotinga o'giradi.
 * Bo'sh bo'lsa — ['', ''].
 *
 * @return array{0:string,1:string} [lotin, kirill]
 */
function ikkita_versiya(?string $matn): array {
    $matn = (string) $matn;
    if ($matn === '') return ['', ''];

    $skript = matn_skripti($matn);
    if ($skript === 'cyrl') {
        return [kirill_dan_lotin($matn), $matn];
    }
    if ($skript === 'mixed') {
        // Aralash bo'lsa — har ikkalasiga o'tkazib qaytaramiz
        return [kirill_dan_lotin($matn), lotin_dan_kirill($matn)];
    }
    // latn yoki empty (raqam/punktuatsiya)
    return [$matn, lotin_dan_kirill($matn)];
}

/**
 * Bir necha maydonni avtomatik kirill versiyasiga aylantiradi.
 *
 * Misol:
 *   $maydonlar = ['matn', 'variant_a', 'variant_b'];
 *   $kirill = avto_kirill($_POST, $maydonlar);
 *   // ['matn_cyrl' => '...', 'variant_a_cyrl' => '...', ...]
 *
 * @param array<string,mixed> $manba   Manba massiv (POST yoki array)
 * @param array<int,string>   $maydonlar  Maydon nomlari ro'yxati
 * @param string              $qoshimcha   Suffiks (default '_cyrl')
 * @return array<string,string>
 */
function avto_kirill(array $manba, array $maydonlar, string $qoshimcha = '_cyrl'): array {
    $natija = [];
    foreach ($maydonlar as $maydon) {
        $qiymat = (string) ($manba[$maydon] ?? '');
        $natija[$maydon . $qoshimcha] = $qiymat === '' ? '' : lotin_dan_kirill($qiymat);
    }
    return $natija;
}
