<?php
/**
 * AvtoTest Pro — Xavfsizlik funksiyalari
 * ------------------------------------------------------------
 *  - CSRF token (per-session, rotate on login)
 *  - XSS himoya
 *  - Rate limiting (DB-based)
 *  - Telefon tozalash
 *  - Sessiya boshqaruvi
 *  - Flash xabarlar
 *  - Yo'naltirish
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

    // Sessiya hijacking oldini olish: ID ni 30 daqiqada regenerate qilamiz
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
    if (empty($_SESSION[CSRF_KALITI]) || strlen($_SESSION[CSRF_KALITI]) < 32) {
        $_SESSION[CSRF_KALITI] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_KALITI];
}

/**
 * CSRF tokenni tekshirish.
 */
function csrf_tekshir(?string $token): bool
{
    sessiya_boshla();
    return !empty($_SESSION[CSRF_KALITI])
        && !empty($token)
        && hash_equals($_SESSION[CSRF_KALITI], $token);
}

/**
 * HTML shaklidagi CSRF hidden input.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="' . CSRF_KALITI
        . '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * XSS himoyasi — chiqish uchun.
 */
function e(mixed $matn): string
{
    return htmlspecialchars((string) $matn, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Telefon raqamini tozalab +998XXXXXXXXX formatiga o'tkazish.
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
 * Mijozning haqiqiy IP manzilini olish.
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
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Rate limit tekshiruvi.
 * Oxirgi LIMIT_VAQT soniyada LIMIT_SON dan ortiq xato bo'lsa false.
 */
function rate_limit_tekshir(): bool
{
    $ip = ip_olish();
    try {
        $son = (int) db_qiymat(
            'SELECT COUNT(*) FROM kirish_urinishlar
             WHERE ip = ?
               AND muvaffaqiyat = 0
               AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, LIMIT_VAQT]
        );
        return $son < LIMIT_SON;
    } catch (Throwable) {
        return true; // DB xatosi bo'lsa ruxsat beramiz
    }
}

/**
 * Kirish urinishini qayd qilish.
 */
function kirish_qayd(string $telefon, bool $muvaffaqiyat): void
{
    try {
        db_bajar(
            'INSERT INTO kirish_urinishlar (ip, telefon, muvaffaqiyat) VALUES (?, ?, ?)',
            [ip_olish(), $telefon, $muvaffaqiyat ? 1 : 0]
        );
    } catch (Throwable) {
        // Yozish muvaffaqiyatsiz bo'lsa jimgina o'tkazib yuboramiz
    }
}

/**
 * Unikal referal kod generatsiya qilish.
 */
function referal_kod_unikal(): string
{
    $belgilar = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $uzunlik  = 8;
    $urinish  = 0;

    while ($urinish < 20) {
        $kod = '';
        for ($i = 0; $i < $uzunlik; $i++) {
            $kod .= $belgilar[random_int(0, strlen($belgilar) - 1)];
        }
        if (!db_qiymat('SELECT 1 FROM foydalanuvchilar WHERE referal_kod = ?', [$kod])) {
            return $kod;
        }
        $urinish++;
    }

    // Fallback: timestamp bilan
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
 * Barcha kesh fayllarini tozalash (3 til uchun).
 */
function kesh_tozala(string $naqsh = '*'): void
{
    if (!is_dir(CACHE_PATH)) {
        return;
    }
    foreach (glob(CACHE_PATH . '/' . $naqsh . '.html') ?: [] as $f) {
        @unlink($f);
    }
}
