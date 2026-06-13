<?php
/**
 * VatanParvar Yaypan — Sessiya va auth middleware
 * ------------------------------------------------------------
 * Har bir sahifa boshida `require_once 'config/auth.php'` qilinadi.
 * Bu fayl sessiyani ochadi, til fayllarini yuklaydi va
 * kirgan foydalanuvchini qaytaradi.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/log.php';
require_once __DIR__ . '/../includes/xato_kuzatuv.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/xujum_himoya.php';

// Xavfsizlik HTTP sarlavhalarini chiqarish
xavfsizlik_sarlavhalar();

// WAF — har bir so'rovni shubhali pattern uchun tekshiradi
xujum_himoya_ishga_tushir();

sessiya_boshla();

// ----- Til tanlash -----
if (isset($_GET['til']) && in_array($_GET['til'], ['uz_latn', 'uz_cyrl', 'ru'], true)) {
    $_SESSION['til'] = $_GET['til'];
    setcookie('til', $_GET['til'], time() + 60 * 60 * 24 * 365, '/');
}
$TIL = $_SESSION['til'] ?? $_COOKIE['til'] ?? 'uz_latn';
if (!in_array($TIL, ['uz_latn', 'uz_cyrl', 'ru'], true)) {
    $TIL = 'uz_latn';
}
$_SESSION['til'] = $TIL;

// Tarjimalar massivi (global)
global $tarjimalar;
$tarjima_fayl = __DIR__ . '/../lang/' . $TIL . '.php';
$tarjimalar = file_exists($tarjima_fayl) ? require $tarjima_fayl : [];

/**
 * Tarjima funksiyasi.
 */
function t(string $kalit, array $almashtir = []): string {
    global $tarjimalar;
    $matn = $tarjimalar[$kalit] ?? $kalit;
    foreach ($almashtir as $k => $v) {
        $matn = str_replace('{' . $k . '}', (string) $v, $matn);
    }
    return $matn;
}

/**
 * Hozirgi foydalanuvchi (kirgan bo'lsa).
 */
function joriy_foydalanuvchi(): ?array {
    static $kesh = null;
    if ($kesh !== null) {
        return $kesh ?: null;
    }
    if (empty($_SESSION['foydalanuvchi_id'])) {
        $kesh = false;
        return null;
    }
    $kesh = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE id = ? AND holat = "faol"',
        [$_SESSION['foydalanuvchi_id']]
    );
    if (!$kesh) {
        unset($_SESSION['foydalanuvchi_id']);
        $kesh = false;
        return null;
    }
    return $kesh;
}

/**
 * Foydalanuvchi kirganligini tekshirish, kirmagan bo'lsa /login ga.
 */
function kirgan_bolish_kerak(): array {
    $f = joriy_foydalanuvchi();
    if (!$f) {
        flash_qoy('xato', t('avval_kiring'));
        yonaltir(SAYT_URL . '/login');
    }
    return $f;
}

/**
 * Admin yoki developer bo'lish kerak.
 */
function admin_bolish_kerak(): array {
    $f = kirgan_bolish_kerak();
    if (!in_array($f['rol'], ['admin', 'developer'], true)) {
        http_response_code(403);
        exit(t('ruxsat_yoq'));
    }
    return $f;
}

/**
 * Faqat developer.
 */
function developer_bolish_kerak(): array {
    $f = kirgan_bolish_kerak();
    if ($f['rol'] !== 'developer') {
        http_response_code(403);
        exit(t('ruxsat_yoq'));
    }
    return $f;
}

/**
 * Tizimga kirgan deb belgilash.
 */
function tizimga_kirgan(int $foydalanuvchi_id): void {
    sessiya_boshla();
    session_regenerate_id(true);
    $_SESSION['foydalanuvchi_id'] = $foydalanuvchi_id;
    db_bajar('UPDATE foydalanuvchilar SET oxirgi_kirish = NOW() WHERE id = ?', [$foydalanuvchi_id]);
}

/**
 * Tizimdan chiqish.
 */
function tizimdan_chiqish(): void {
    sessiya_boshla();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Foydalanuvchining faol obunasi bormi?
 */
function obuna_faolmi(int $foydalanuvchi_id): bool {
    return (bool) db_qiymat(
        'SELECT COUNT(*) FROM obunalar
         WHERE foydalanuvchi_id = ? AND holat = "faol" AND tugash > NOW()',
        [$foydalanuvchi_id]
    );
}
