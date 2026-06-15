<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

sessiya_boshla();

$TILLAR = ['uz_latn', 'uz_cyrl'];

if (isset($_GET['til']) && in_array($_GET['til'], $TILLAR, true)) {
    $_SESSION['til'] = $_GET['til'];
    setcookie('til', $_GET['til'], time() + 60 * 60 * 24 * 365, '/');
}
$TIL = $_SESSION['til'] ?? $_COOKIE['til'] ?? 'uz_latn';
if (!in_array($TIL, $TILLAR, true)) {
    $TIL = 'uz_latn';
}
$_SESSION['til'] = $TIL;

global $tarjimalar;
$tarjima_fayl = __DIR__ . '/../lang/' . $TIL . '.php';
$tarjimalar = file_exists($tarjima_fayl) ? require $tarjima_fayl : [];

function t(string $kalit, array $almashtir = []): string {
    global $tarjimalar;
    $matn = $tarjimalar[$kalit] ?? $kalit;
    foreach ($almashtir as $k => $v) {
        $matn = str_replace('{' . $k . '}', (string) $v, $matn);
    }
    return $matn;
}

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

function kirgan_bolish_kerak(): array {
    $f = joriy_foydalanuvchi();
    if (!$f) {
        flash_qoy('xato', t('avval_kiring'));
        yonaltir(SAYT_URL . '/login');
    }
    return $f;
}

function admin_bolish_kerak(): array {
    $f = kirgan_bolish_kerak();
    if (!in_array($f['rol'], ['admin', 'developer'], true)) {
        http_response_code(403);
        exit(t('ruxsat_yoq'));
    }
    return $f;
}

function developer_bolish_kerak(): array {
    $f = kirgan_bolish_kerak();
    if ($f['rol'] !== 'developer') {
        http_response_code(403);
        exit(t('ruxsat_yoq'));
    }
    return $f;
}

function tizimga_kirgan(int $foydalanuvchi_id): void {
    sessiya_boshla();
    session_regenerate_id(true);
    $_SESSION['foydalanuvchi_id'] = $foydalanuvchi_id;

    db_bajar('UPDATE foydalanuvchilar SET oxirgi_kirish = NOW() WHERE id = ?', [$foydalanuvchi_id]);

    $ip = ip_olish();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $qurilma_nomi = function_exists('qurilma_aniqla') ? qurilma_aniqla($ua) : 'Boshqa';
    $brauzer = function_exists('brauzer_aniqla') ? brauzer_aniqla($ua) : '';

    try {
        db_bajar(
            'INSERT INTO kirish_qaydlar (foydalanuvchi_id, ip, user_agent, qurilma) VALUES (?, ?, ?, ?)',
            [$foydalanuvchi_id, $ip, $ua, $qurilma_nomi]
        );
    } catch (Throwable $e) {
    }

    $f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$foydalanuvchi_id]);
    if ($f && !empty($f['telegram_id']) && !empty($f['kirish_bildirish'])) {
        $oxirgi = db_qator(
            'SELECT * FROM kirish_qaydlar
             WHERE foydalanuvchi_id = ? AND id < (SELECT MAX(id) FROM kirish_qaydlar WHERE foydalanuvchi_id = ?)
             ORDER BY id DESC LIMIT 1',
            [$foydalanuvchi_id, $foydalanuvchi_id]
        );
        $bildir = !$oxirgi || $oxirgi['ip'] !== $ip || $oxirgi['qurilma'] !== $qurilma_nomi;

        if ($bildir && function_exists('telegram_yubor')) {
            $matn = "🔐 <b>Yangi kirish aniqlandi</b>\n\n"
                  . "📱 Qurilma: <b>" . htmlspecialchars($qurilma_nomi) . "</b>"
                  . ($brauzer ? " (" . htmlspecialchars($brauzer) . ")" : "") . "\n"
                  . "🌐 IP: <code>" . htmlspecialchars($ip) . "</code>\n"
                  . "🕐 Vaqt: " . date('d.m.Y H:i') . "\n\n"
                  . "Agar bu siz bo'lmasangiz, darhol parolni o'zgartiring:\n"
                  . SAYT_URL . "/profil";
            telegram_yubor($f['telegram_id'], $matn);
        }
    }
}

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

function obuna_faolmi(int $foydalanuvchi_id): bool {
    return (bool) db_qiymat(
        'SELECT COUNT(*) FROM obunalar
         WHERE foydalanuvchi_id = ? AND holat = "faol" AND tugash > NOW()',
        [$foydalanuvchi_id]
    );
}
