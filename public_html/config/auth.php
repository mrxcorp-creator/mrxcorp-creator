<?php
/**
 * AvtoTest Pro — Sessiya va auth middleware
 * ------------------------------------------------------------
 * Har bir sahifa boshida require_once qilinadi.
 * Sessiyani ochadi, tilni yuklaydi, foydalanuvchini qaytaradi.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/security.php';

sessiya_boshla();

// ----- Til tanlash -----
if (isset($_GET['til']) && in_array($_GET['til'], TILLAR, true)) {
    $_SESSION['til'] = $_GET['til'];
    setcookie('til', $_GET['til'], time() + 60 * 60 * 24 * 365, '/', '', REJIM === 'production', true);
}
$TIL = $_SESSION['til'] ?? $_COOKIE['til'] ?? TIL_DEFAULT;
if (!in_array($TIL, TILLAR, true)) {
    $TIL = TIL_DEFAULT;
}
$_SESSION['til'] = $TIL;

// Tarjimalar massivi (global)
global $tarjimalar;
$tarjima_fayl = __DIR__ . '/../lang/' . $TIL . '.php';
$tarjimalar   = file_exists($tarjima_fayl) ? require $tarjima_fayl : [];

/**
 * Tarjima funksiyasi.
 */
function t(string $kalit, array $almashtir = []): string
{
    global $tarjimalar;
    $matn = $tarjimalar[$kalit] ?? $kalit;
    foreach ($almashtir as $k => $v) {
        $matn = str_replace('{' . $k . '}', (string) $v, $matn);
    }
    return $matn;
}

/**
 * Hozirgi foydalanuvchi (kirgan bo'lsa array, aks holda null).
 * $yangilash = true bo'lsa, static keshni e'tiborsiz qoldirib qayta o'qiydi.
 */
function joriy_foydalanuvchi(bool $yangilash = false): ?array
{
    static $kesh = false; // false = hali tekshirilmagan

    if ($yangilash) {
        $kesh = false;
    }

    if ($kesh !== false) {
        return $kesh ?: null;
    }

    if (empty($_SESSION['foydalanuvchi_id'])) {
        $kesh = null;
        return null;
    }

    $kesh = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE id = ? AND holat = "faol"',
        [(int) $_SESSION['foydalanuvchi_id']]
    );

    if (!$kesh) {
        unset($_SESSION['foydalanuvchi_id']);
        $kesh = null;
    }

    return $kesh ?: null;
}

/**
 * Kirganligini tekshiradi, kirmaganda /login ga yo'naltiradi.
 */
function kirgan_bolish_kerak(): array
{
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
function admin_bolish_kerak(): array
{
    $f = kirgan_bolish_kerak();
    if (!in_array($f['rol'], ['admin', 'developer'], true)) {
        http_response_code(403);
        include ROOT_PATH . '/403.php';
        exit;
    }
    return $f;
}

/**
 * Faqat developer.
 */
function developer_bolish_kerak(): array
{
    $f = kirgan_bolish_kerak();
    if ($f['rol'] !== 'developer') {
        http_response_code(403);
        include ROOT_PATH . '/403.php';
        exit;
    }
    return $f;
}

/**
 * Tizimga kirgan deb belgilash.
 */
function tizimga_kirgan(int $foydalanuvchi_id): void
{
    sessiya_boshla();
    session_regenerate_id(true);
    $_SESSION['foydalanuvchi_id'] = $foydalanuvchi_id;
    db_bajar(
        'UPDATE foydalanuvchilar SET oxirgi_kirish = NOW() WHERE id = ?',
        [$foydalanuvchi_id]
    );
}

/**
 * Tizimdan chiqish.
 */
function tizimdan_chiqish(): void
{
    sessiya_boshla();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $p['path'], $p['domain'],
            $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

/**
 * Foydalanuvchining faol obunasi bormi?
 */
function obuna_faolmi(int $foydalanuvchi_id): bool
{
    return (bool) db_qiymat(
        'SELECT COUNT(*) FROM obunalar
         WHERE foydalanuvchi_id = ? AND holat = "faol" AND tugash > NOW()',
        [$foydalanuvchi_id]
    );
}
