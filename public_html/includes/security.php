<?php
require_once __DIR__ . '/../config/database.php';

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

function csrf_token(): string {
    sessiya_boshla();
    if (empty($_SESSION[CSRF_KALITI])) {
        $_SESSION[CSRF_KALITI] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_KALITI];
}

function csrf_tekshir(?string $token): bool {
    sessiya_boshla();
    return !empty($_SESSION[CSRF_KALITI]) &&
           !empty($token) &&
           hash_equals($_SESSION[CSRF_KALITI], $token);
}

function csrf_input(): string {
    return '<input type="hidden" name="' . CSRF_KALITI .
           '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function e($matn): string {
    return htmlspecialchars((string) $matn, ENT_QUOTES, 'UTF-8');
}

function telefon_tozala(string $tel): string {
    $tel = preg_replace('/\D+/', '', $tel);
    if ($tel === '') return '';

    if (strlen($tel) === 9 && preg_match('/^9[0-9]{8}$/', $tel)) {
        $tel = '998' . $tel;
    }
    if (strlen($tel) === 12 && preg_match('/^998[0-9]{9}$/', $tel)) {
        return '+' . $tel;
    }
    return '';
}

function ip_olish(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $kalit) {
        if (!empty($_SERVER[$kalit])) {
            return explode(',', $_SERVER[$kalit])[0];
        }
    }
    return '0.0.0.0';
}

function rate_limit_tekshir(string $telefon = ''): bool {
    try {
        $ip = ip_olish();
        $son = (int) db_qiymat(
            'SELECT COUNT(*) FROM kirish_urinishlar
             WHERE ip = ? AND muvaffaqiyat = 0
               AND yaratilgan > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, LIMIT_VAQT]
        );
        return $son < LIMIT_SON;
    } catch (Throwable $e) {
        error_log('rate_limit_tekshir xato: ' . $e->getMessage());
        return true;
    }
}

function kirish_qayd(string $telefon, bool $muvaffaqiyat): void {
    try {
        db_bajar(
            'INSERT INTO kirish_urinishlar (ip, telefon, muvaffaqiyat) VALUES (?, ?, ?)',
            [ip_olish(), $telefon, $muvaffaqiyat ? 1 : 0]
        );
    } catch (Throwable $e) {
        error_log('kirish_qayd xato: ' . $e->getMessage());
    }
}

function referal_kod_yarat(int $uzunlik = 8): string {
    $belgilar = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $kod = '';
    for ($i = 0; $i < $uzunlik; $i++) {
        $kod .= $belgilar[random_int(0, strlen($belgilar) - 1)];
    }
    return $kod;
}

function referal_kod_unikal(): string {
    while (true) {
        $kod = referal_kod_yarat();
        $bor = db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE referal_kod = ?', [$kod]);
        if (!$bor) {
            return $kod;
        }
    }
}

function yonaltir(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash_qoy(string $tur, string $matn): void {
    sessiya_boshla();
    $_SESSION['flash'] = ['tur' => $tur, 'matn' => $matn];
}

function flash_ol(): ?array {
    sessiya_boshla();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
