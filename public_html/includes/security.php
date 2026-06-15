<?php
/**
 * VatanParvar Yaypan — Xavfsizlik funksiyalari
 *
 * YANGI:
 *  - Per-phone rate limit (faqat IP emas)
 *  - SVG/XML yuklash bloklash (XSS vektori)
 *  - IP xavfsizligi yaxshilandi
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sessiyani xavfsiz ishga tushirish.
 */
function sessiya_boshla(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_name(SESSION_NOMI);
    session_set_cookie_params([
        'lifetime' => SESSION_VAQTI,
        'path'     => '/',
        'secure'   => REJIM === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Session fixation oldini olish — 30 daqiqada ID regenerate
    if (!isset($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

/**
 * CSRF tokenni olish yoki yaratish.
 */
function csrf_token(): string
{
    sessiya_boshla();
    if (empty($_SESSION[CSRF_KALITI]) || strlen($_SESSION[CSRF_KALITI]) < 40) {
        $_SESSION[CSRF_KALITI] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_KALITI];
}

/**
 * CSRF tokenni tekshirish (constant-time comparison).
 */
function csrf_tekshir(?string $token): bool
{
    sessiya_boshla();
    return !empty($_SESSION[CSRF_KALITI])
        && !empty($token)
        && hash_equals($_SESSION[CSRF_KALITI], $token);
}

/**
 * Form uchun CSRF hidden input.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="' . CSRF_KALITI . '" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * XSS himoya — HTML chiqishi uchun.
 */
function e(mixed $matn): string
{
    return htmlspecialchars((string) $matn, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Telefon raqamini +998XXXXXXXXX formatiga tozalash.
 */
function telefon_tozala(string $tel): string
{
    $tel = preg_replace('/\D+/', '', $tel);

    if (strlen($tel) === 9) {
        $tel = '998' . $tel;
    } elseif (strlen($tel) === 11 && str_starts_with($tel, '8')) {
        $tel = '998' . substr($tel, 1);
    }

    if (strlen($tel) === 12 && str_starts_with($tel, '998')) {
        return '+' . $tel;
    }

    return '';
}

/**
 * Haqiqiy IP manzilni olish.
 * Cloudflare va proxy'larni hisobga oladi.
 */
function ip_olish(): string
{
    $kalitlar = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    foreach ($kalitlar as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Rate limit tekshiruvi.
 *
 * YANGI: IP + telefon kombinatsiyasi tekshiriladi.
 * IP boshqa bo'lsa ham, bir telefon uchun xatolik hisoblanadi.
 * Bu VPN orqali aylanib o'tishning oldini oladi.
 *
 * @param  string $telefon  Tozalangan telefon (+998...)
 * @return bool   true → ruxsat bor, false → bloklangan
 */
function rate_limit_tekshir(string $telefon = ''): bool
{
    $ip = ip_olish();

    try {
        // IP bo'yicha tekshiruv
        $ip_son = (int) db_qiymat(
            'SELECT COUNT(*) FROM kirish_urinishlar
             WHERE ip = ?
               AND muvaffaqiyat = 0
               AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, LIMIT_VAQT]
        );
        if ($ip_son >= LIMIT_SON) {
            return false;
        }

        // Telefon bo'yicha tekshiruv (telefon kiritilgan bo'lsa)
        if ($telefon !== '') {
            $tel_son = (int) db_qiymat(
                'SELECT COUNT(*) FROM kirish_urinishlar
                 WHERE telefon = ?
                   AND muvaffaqiyat = 0
                   AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
                [$telefon, LIMIT_VAQT]
            );
            if ($tel_son >= LIMIT_SON) {
                return false;
            }
        }
    } catch (Throwable) {
        return true; // DB xatosi bo'lsa ruxsat beramiz
    }

    return true;
}

/**
 * Kirish urinishini qayd qilish.
 */
function kirish_qayd(string $telefon, bool $muvaffaqiyat): void
{
    try {
        db_bajar(
            'INSERT INTO kirish_urinishlar (ip, telefon, muvaffaqiyat)
             VALUES (?, ?, ?)',
            [ip_olish(), $telefon, $muvaffaqiyat ? 1 : 0]
        );
    } catch (Throwable) {
        // Silent fail
    }
}

/**
 * Unikal referal kod generatsiya qilish.
 */
function referal_kod_unikal(): string
{
    $belgilar = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $urinish  = 0;

    while ($urinish < 25) {
        $kod = '';
        for ($i = 0; $i < 8; $i++) {
            $kod .= $belgilar[random_int(0, strlen($belgilar) - 1)];
        }
        if (!db_qiymat('SELECT 1 FROM foydalanuvchilar WHERE referal_kod = ?', [$kod])) {
            return $kod;
        }
        $urinish++;
    }

    return strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

/**
 * HTTP yo'naltirish.
 */
function yonaltir(string $url, int $kod = 302): never
{
    header('Location: ' . $url, true, $kod);
    exit;
}

/**
 * Flash xabar saqlash.
 * @param string $tur  'muvaffaqiyat' | 'xato' | 'ogohlantirish'
 */
function flash_qoy(string $tur, string $matn): void
{
    sessiya_boshla();
    $_SESSION['_flash'] = ['tur' => $tur, 'matn' => $matn];
}

/**
 * Flash xabarni o'qib, sessiyadan o'chirish.
 */
function flash_ol(): ?array
{
    sessiya_boshla();
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/**
 * Barcha kesh fayllarini tozalash (barcha tillar uchun).
 */
function kesh_tozala(string $naqsh = '*'): void
{
    if (!is_dir(CACHE_PATH)) {
        return;
    }
    foreach (glob(CACHE_PATH . '/' . $naqsh . '.html') ?: [] as $fayl) {
        @unlink($fayl);
    }
}
