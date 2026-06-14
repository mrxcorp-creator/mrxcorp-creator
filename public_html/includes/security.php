<?php
/**
 * VatanParvar Yaypan — Xavfsizlik funksiyalari
 * ------------------------------------------------------------
 *  - Sessiya
 *  - CSRF token
 *  - XSS himoya (htmlspecialchars)
 *  - Rate limiting (IP + telefon)
 *  - Honeypot
 *  - Security headers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/log.php';

/**
 * Sessiyani xavfsiz ishga tushirish.
 */
function sessiya_boshla(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Session sozlamalari
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        if (defined('REJIM') && REJIM === 'production') {
            ini_set('session.cookie_secure', '1');
        }

        session_name(SESSION_NOMI);
        session_set_cookie_params([
            'lifetime' => SESSION_VAQTI,
            'path'     => '/',
            'secure'   => defined('REJIM') && REJIM === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Sessiya o'g'irlash himoyasi: User-Agent va IP'ga bog'lash
        $imzo = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . _sessiya_ip_kalit());
        if (empty($_SESSION['_imzo'])) {
            $_SESSION['_imzo'] = $imzo;
        } elseif ($_SESSION['_imzo'] !== $imzo) {
            // Anomaliya — sessiya tozalanadi
            log_xavfsizlik('SESSIYA_HIJACK_URINISH', [
                'eski_imzo'   => mb_substr((string) $_SESSION['_imzo'], 0, 16),
                'yangi_imzo'  => mb_substr($imzo, 0, 16),
            ]);
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['_imzo'] = $imzo;
        }
    }
}

/**
 * IPning birinchi 3 oktetini olamiz (mobil tarmoq IPsi tez-tez o'zgaradi).
 */
function _sessiya_ip_kalit(): string {
    $ip = ip_olish();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $b = explode('.', $ip);
        return $b[0] . '.' . $b[1] . '.' . $b[2];
    }
    return $ip;
}

/**
 * CSRF tokenni olish (yoki yaratish).
 */
function csrf_token(): string {
    sessiya_boshla();
    if (empty($_SESSION[CSRF_KALITI])) {
        $_SESSION[CSRF_KALITI] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_KALITI];
}

/**
 * CSRF tokenni tekshirish.
 */
function csrf_tekshir(?string $token): bool {
    sessiya_boshla();
    $ok = !empty($_SESSION[CSRF_KALITI]) &&
           !empty($token) &&
           hash_equals($_SESSION[CSRF_KALITI], $token);
    if (!$ok) {
        log_xavfsizlik('CSRF_XATO', [
            'kelgan_token' => mb_substr((string) $token, 0, 16),
        ]);
    }
    return $ok;
}

/**
 * Form CSRF + honeypot HTML.
 */
function csrf_input(): string {
    return '<input type="hidden" name="' . CSRF_KALITI .
           '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">' .
           // Honeypot — bot bu maydonni to'ldiradi, real foydalanuvchi yo'q
           '<div style="position:absolute;left:-9999px" aria-hidden="true">' .
             '<label>Saytga link kiriting (qoldiring bo\'sh):</label>' .
             '<input type="text" name="website_url" tabindex="-1" autocomplete="off">' .
           '</div>';
}

/**
 * Honeypot to'ldirilganmi? (bot belgisi)
 */
function honeypot_tushdimi(): bool {
    if (!empty($_POST['website_url'])) {
        log_xavfsizlik('HONEYPOT_TUSHDI', [
            'qiymat' => mb_substr((string) $_POST['website_url'], 0, 100),
        ]);
        return true;
    }
    return false;
}

/**
 * XSS himoya — chiqish uchun matnni tozalash.
 */
function e($matn): string {
    return htmlspecialchars((string) $matn, ENT_QUOTES, 'UTF-8');
}

/**
 * Telefon raqami formatini tozalash. +998901234567 ko'rinishi.
 */
function telefon_tozala(string $tel): string {
    $tel = preg_replace('/\D+/', '', $tel);
    if (strlen($tel) === 9) {
        $tel = '998' . $tel;
    }
    if (strlen($tel) === 12 && str_starts_with($tel, '998')) {
        return '+' . $tel;
    }
    return '';
}

/**
 * IP manzilni olish (proxy/CF orqali ham to'g'ri).
 */
function ip_olish(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $kalit) {
        if (!empty($_SERVER[$kalit])) {
            $ip = trim(explode(',', $_SERVER[$kalit])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Rate limit tekshiruvi: oxirgi LIMIT_VAQT soniyada xato urinishlar.
 *  IP bo'yicha + telefon bo'yicha (telefon berilgan bo'lsa).
 */
function rate_limit_tekshir(string $telefon = ''): bool {
    $ip = ip_olish();
    try {
        $ip_son = (int) db_qiymat(
            'SELECT COUNT(*) FROM kirish_urinishlar
             WHERE ip = ? AND muvaffaqiyat = 0
               AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, LIMIT_VAQT]
        );
        if ($ip_son >= LIMIT_SON) {
            // 5dan ortiq xato — IPni 30 daqiqaga vaqtinchalik bloklaymiz
            if ($ip_son >= LIMIT_SON * 2 && function_exists('ip_blokla')) {
                ip_blokla($ip, 'Brute-force urinishlari (' . $ip_son . ')', 30);
                log_xavfsizlik('BRUTE_FORCE_BLOK', ['ip' => $ip, 'urinishlar' => $ip_son]);
            }
            return false;
        }
        if ($telefon) {
            $tel_son = (int) db_qiymat(
                'SELECT COUNT(*) FROM kirish_urinishlar
                 WHERE telefon = ? AND muvaffaqiyat = 0
                   AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
                [$telefon, LIMIT_VAQT]
            );
            if ($tel_son >= LIMIT_SON) {
                return false;
            }
        }
    } catch (Throwable $e) {
        // DB hali tayyor bo'lmasa, ruxsat beramiz
        return true;
    }
    return true;
}

/**
 * Kirish urinishini qayd qilish (DB + log fayl).
 */
function kirish_qayd(string $telefon, bool $muvaffaqiyat): void {
    try {
        db_bajar(
            'INSERT INTO kirish_urinishlar (ip, telefon, muvaffaqiyat) VALUES (?, ?, ?)',
            [ip_olish(), $telefon, $muvaffaqiyat ? 1 : 0]
        );
    } catch (Throwable $e) {}
    log_kirish($telefon, $muvaffaqiyat);
}

/**
 * Tasodifiy referal kod yaratish.
 */
function referal_kod_yarat(int $uzunlik = 8): string {
    $belgilar = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $kod = '';
    for ($i = 0; $i < $uzunlik; $i++) {
        $kod .= $belgilar[random_int(0, strlen($belgilar) - 1)];
    }
    return $kod;
}

/**
 * Foydalanuvchi uchun unikal referal kod.
 */
function referal_kod_unikal(): string {
    while (true) {
        $kod = referal_kod_yarat();
        $bor = db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE referal_kod = ?', [$kod]);
        if (!$bor) {
            return $kod;
        }
    }
}

/**
 * Yo'naltirish (header) yordamchisi.
 */
function yonaltir(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash xabar saqlash.
 */
function flash_qoy(string $tur, string $matn): void {
    sessiya_boshla();
    $_SESSION['flash'] = ['tur' => $tur, 'matn' => $matn];
}

/**
 * Flash xabarni o'qish va tozalash.
 */
function flash_ol(): ?array {
    sessiya_boshla();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Xavfsizlik HTTP sarlavhalarini chiqarish.
 */
function xavfsizlik_sarlavhalar(): void {
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)');
    if (defined('REJIM') && REJIM === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    // CSP — Tailwind CDN va Alpine ishlatilgani uchun unsafe-inline qoldirildi
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com data:; " .
        "img-src 'self' data: https:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'self'; " .
        "base-uri 'self'; " .
        "form-action 'self';"
    );
}
