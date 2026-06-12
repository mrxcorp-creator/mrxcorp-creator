<?php
/**
 * VatanParvar Yaypan — Xavfsizlik funksiyalari
 * ------------------------------------------------------------
 *  - CSRF token
 *  - XSS himoya (htmlspecialchars)
 *  - Rate limiting
 *  - Telefon raqami tozalash
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sessiyani xavfsiz ishga tushirish.
 */
function sessiya_boshla(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NOMI);
        session_set_cookie_params([
            'lifetime' => SESSION_VAQTI,
            'path'     => '/',
            'secure'   => REJIM === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
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
    return !empty($_SESSION[CSRF_KALITI]) &&
           !empty($token) &&
           hash_equals($_SESSION[CSRF_KALITI], $token);
}

/**
 * Form CSRF input HTML.
 */
function csrf_input(): string {
    return '<input type="hidden" name="' . CSRF_KALITI .
           '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
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
 * IP manzilni olish.
 */
function ip_olish(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $kalit) {
        if (!empty($_SERVER[$kalit])) {
            return explode(',', $_SERVER[$kalit])[0];
        }
    }
    return '0.0.0.0';
}

/**
 * Rate limit: oxirgi LIMIT_VAQT soniyada xato urinishlar soni.
 */
function rate_limit_tekshir(string $telefon = ''): bool {
    $ip = ip_olish();
    $son = (int) db_qiymat(
        'SELECT COUNT(*) FROM kirish_urinishlar
         WHERE ip = ? AND muvaffaqiyat = 0
           AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
        [$ip, LIMIT_VAQT]
    );
    return $son < LIMIT_SON;
}

/**
 * Kirish urinishini qayd qilish.
 */
function kirish_qayd(string $telefon, bool $muvaffaqiyat): void {
    db_bajar(
        'INSERT INTO kirish_urinishlar (ip, telefon, muvaffaqiyat) VALUES (?, ?, ?)',
        [ip_olish(), $telefon, $muvaffaqiyat ? 1 : 0]
    );
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
