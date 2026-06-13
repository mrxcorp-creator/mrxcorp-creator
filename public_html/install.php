<?php
/**
 * VatanParvar Yaypan — O'rnatish ustasi (Wizard)
 * ================================================================
 *  Bu sahifa loyihani yangi serverga (cPanel/VPS) joylash vaqtida
 *  bir marta ishlatiladi. Tugagandan so'ng `install.lock` fayli
 *  yaratiladi va sahifa qulflanadi.
 *
 *  Qadamlar:
 *    1) Tizim talablari (PHP, ext, fayllar, papkalar)
 *    2) Ma'lumotlar bazasi sozlamalari (host/name/user/pass)
 *    3) Schema import (avto yoki .sql yuklash)
 *    4) Admin akkaunt yaratish
 *    5) Sayt sozlamalari (SAYT_URL, telegram, click, payme...)
 *    6) Yakunlash — lock yaratish va redirect
 *
 *  Xavfsizlik:
 *    - install.lock bo'lsa => 403
 *    - CSRF token har formada
 *    - Brute-force rate limit (15 daq. ichida 10 dan ortiq xato urinishlar)
 *    - Faqat HTTPS yoki localhost'dan ishlaydi (production'da)
 * ================================================================
 */

// O'rnatish jarayonidagi xatolar UI'da ko'rinishi uchun
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('html_errors', '1');

// ---------------------------------------------------------------- yo'llar
define('IN_INSTALL', true);
define('I_ROOT',         __DIR__);
define('I_CONFIG',       __DIR__ . '/config');
define('I_LOG',          __DIR__ . '/loglar');
define('I_DB_FAYL',      __DIR__ . '/config/database.php');
define('I_LOCK',         __DIR__ . '/install.lock');
define('I_SCHEMA',       dirname(__DIR__) . '/database/schema.sql');
define('I_SCHEMA_ALT',   __DIR__ . '/database/schema.sql'); // cPanel'ga butun loyiha public_html ichiga ko'chirilganda

// O'rnatish allaqachon qilingan bo'lsa — qulflangan
if (file_exists(I_LOCK)) {
    http_response_code(403);
    exit(html_xato("O'rnatish allaqachon yakunlangan",
        "Tizim allaqachon o'rnatilgan. Agar qaytadan o'rnatmoqchi bo'lsangiz,
         <code>install.lock</code> faylini o'chiring va <code>config/database.php</code>'ni qayta yarating."));
}

// Sessiya — qadamlar oralig'idagi state uchun
session_name('VATANPARVAR_INSTALL');
session_start();

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function i_csrf(): string { return $_SESSION['csrf']; }
function i_csrf_input(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(i_csrf(), ENT_QUOTES) . '">';
}
function i_csrf_ok(): bool {
    return !empty($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Joriy qadam (0..6)
$qadam = isset($_GET['qadam']) ? max(0, min(6, (int) $_GET['qadam'])) : 0;

// Ish boshlanganda telegramdek qildi: agar oldingi qadam to'liq bajarilmagan bo'lsa, ortga qaytariladi
$_SESSION['oxirgi_qadam'] = $_SESSION['oxirgi_qadam'] ?? 0;
if ($qadam > $_SESSION['oxirgi_qadam'] + 1) {
    $qadam = $_SESSION['oxirgi_qadam'];
}

// Brute-force himoyasi: 15 daqiqada 10 dan ortiq POST xatolari
function i_rate_limit(): bool {
    $f = sys_get_temp_dir() . '/vp_install_' . md5($_SERVER['REMOTE_ADDR'] ?? '0');
    $j = is_file($f) ? json_decode(@file_get_contents($f) ?: '[]', true) : [];
    $j = array_filter((array) $j, fn($t) => $t > time() - 900);
    $ok = count($j) < 10;
    @file_put_contents($f, json_encode($j));
    return $ok;
}
function i_xato_qayd(): void {
    $f = sys_get_temp_dir() . '/vp_install_' . md5($_SERVER['REMOTE_ADDR'] ?? '0');
    $j = is_file($f) ? json_decode(@file_get_contents($f) ?: '[]', true) : [];
    $j[] = time();
    @file_put_contents($f, json_encode($j));
}

// ================================================================
// QADAM HANDLER'LARI
// ================================================================
$xato = '';
$muvaffaqiyat = '';

// ---------- QADAM 1: TIZIM TALABLARI ----------
$talablar = i_talablar_tekshir();

// ---------- QADAM 2: DB SOZLAMALARI ----------
if ($qadam === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!i_csrf_ok()) {
        $xato = 'CSRF token noto\'g\'ri.';
    } elseif (!i_rate_limit()) {
        $xato = 'Juda ko\'p urinishlar. 15 daqiqadan keyin qayta urinib ko\'ring.';
    } else {
        $db = [
            'host'    => trim($_POST['db_host'] ?? 'localhost'),
            'name'    => trim($_POST['db_name'] ?? ''),
            'user'    => trim($_POST['db_user'] ?? ''),
            'pass'    =>       $_POST['db_pass'] ?? '',
            'charset' => 'utf8mb4',
        ];
        if (!$db['name'] || !$db['user']) {
            $xato = 'DB nomi va foydalanuvchi nomi majburiy.';
        } else {
            // Ulanishni sinash
            try {
                $dsn = "mysql:host={$db['host']};charset={$db['charset']}";
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                // Bazani tanlash (mavjud bo'lsa)
                $bor = (bool) $pdo->query(
                    "SELECT 1 FROM information_schema.schemata WHERE schema_name = "
                    . $pdo->quote($db['name'])
                )->fetchColumn();
                if (!$bor) {
                    $pdo->exec("CREATE DATABASE `{$db['name']}`
                                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
                $pdo->exec("USE `{$db['name']}`");
                $_SESSION['db'] = $db;
                $_SESSION['oxirgi_qadam'] = 2;
                header('Location: ?qadam=3');
                exit;
            } catch (PDOException $e) {
                i_xato_qayd();
                $xato = 'DB ulanish xatosi: ' . $e->getMessage();
            }
        }
    }
}

// ---------- QADAM 3: SCHEMA IMPORT ----------
if ($qadam === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SESSION['schema_import_qildim'])) {
    if (!i_csrf_ok()) {
        $xato = 'CSRF token noto\'g\'ri.';
    } elseif (empty($_SESSION['db'])) {
        header('Location: ?qadam=2'); exit;
    } else {
        try {
            $db = $_SESSION['db'];
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
                $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $sql = '';
            // 1) Foydalanuvchi yuklagan fayl
            if (!empty($_FILES['sql_fayl']['tmp_name']) && is_uploaded_file($_FILES['sql_fayl']['tmp_name'])) {
                $tur = mime_content_type($_FILES['sql_fayl']['tmp_name']);
                if (!in_array($tur, ['text/plain', 'text/x-sql', 'application/sql', 'application/octet-stream'], true)) {
                    $xato = 'Faqat .sql faylini yuklang.';
                } elseif ($_FILES['sql_fayl']['size'] > 50 * 1024 * 1024) {
                    $xato = 'Fayl o\'lchami 50MB dan oshmasligi kerak.';
                } else {
                    $sql = file_get_contents($_FILES['sql_fayl']['tmp_name']);
                }
            }
            // 2) Standart schema.sql
            if (!$sql) {
                foreach ([I_SCHEMA, I_SCHEMA_ALT] as $f) {
                    if (is_file($f)) { $sql = file_get_contents($f); break; }
                }
            }
            if (!$xato) {
                if (!$sql) {
                    $xato = 'Schema fayli topilmadi. Iltimos, .sql faylni yuklang.';
                } else {
                    $natija = i_sql_import($pdo, $sql);
                    if ($natija['xato']) {
                        $xato = 'Importda xato: ' . $natija['xato'];
                    } else {
                        $_SESSION['schema_import_qildim'] = true;
                        $_SESSION['oxirgi_qadam'] = 3;
                        $_SESSION['import_stat'] = $natija;
                        header('Location: ?qadam=4'); exit;
                    }
                }
            }
        } catch (Throwable $e) {
            $xato = 'Xato: ' . $e->getMessage();
        }
    }
}

// ---------- QADAM 4: ADMIN AKKAUNT ----------
if ($qadam === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!i_csrf_ok()) {
        $xato = 'CSRF token noto\'g\'ri.';
    } elseif (empty($_SESSION['db'])) {
        header('Location: ?qadam=2'); exit;
    } else {
        $ism      = trim($_POST['ism'] ?? '');
        $familiya = trim($_POST['familiya'] ?? '');
        $telefon  = preg_replace('/\D+/', '', $_POST['telefon'] ?? '');
        if (strlen($telefon) === 9)               { $telefon = '998' . $telefon; }
        if (strlen($telefon) === 12 && str_starts_with($telefon, '998')) {
            $telefon = '+' . $telefon;
        } else { $telefon = ''; }
        $parol  = $_POST['parol'] ?? '';
        $parol2 = $_POST['parol2'] ?? '';

        if (!$ism)               $xato = 'Ism majburiy.';
        elseif (!$telefon)       $xato = 'Telefon noto\'g\'ri formatda.';
        elseif (mb_strlen($parol) < 8)
                                 $xato = 'Parol kamida 8 ta belgidan iborat bo\'lishi kerak.';
        elseif ($parol !== $parol2)
                                 $xato = 'Parollar mos kelmadi.';
        else {
            try {
                $db = $_SESSION['db'];
                $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
                    $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $hash = password_hash($parol, PASSWORD_BCRYPT);
                $kod  = i_random_kod();
                // Ehtimol seedda foydalanuvchi bor (DEV0000) — uni o'chirib, yangi developer yaratamiz
                $pdo->prepare('DELETE FROM foydalanuvchilar WHERE telefon = ? OR referal_kod = ?')
                    ->execute([$telefon, 'DEV0000']);
                $st = $pdo->prepare(
                    'INSERT INTO foydalanuvchilar
                     (ism, familiya, telefon, parol_hash, rol, referal_kod, holat)
                     VALUES (?, ?, ?, ?, "developer", ?, "faol")'
                );
                $st->execute([$ism, $familiya, $telefon, $hash, $kod]);
                $_SESSION['admin'] = ['ism' => $ism, 'telefon' => $telefon];
                $_SESSION['oxirgi_qadam'] = 4;
                header('Location: ?qadam=5'); exit;
            } catch (Throwable $e) {
                $xato = 'Admin yaratilmadi: ' . $e->getMessage();
            }
        }
    }
}

// ---------- QADAM 5: SAYT SOZLAMALARI ----------
if ($qadam === 5 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!i_csrf_ok()) {
        $xato = 'CSRF token noto\'g\'ri.';
    } elseif (empty($_SESSION['db'])) {
        header('Location: ?qadam=2'); exit;
    } else {
        $sayt_url = trim($_POST['sayt_url'] ?? '');
        $sayt_url = rtrim($sayt_url, '/');
        if (!filter_var($sayt_url, FILTER_VALIDATE_URL)) {
            $xato = 'Sayt URL noto\'g\'ri (https://example.uz formatida bo\'lsin).';
        } else {
            try {
                $db = $_SESSION['db'];
                $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
                    $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $juftlar = [
                    'sayt_url'           => $sayt_url,
                    'sayt_nomi'          => trim($_POST['sayt_nomi']     ?? 'VatanParvar Yaypan'),
                    'aloqa_telefon'      => trim($_POST['aloqa_telefon'] ?? ''),
                    'aloqa_email'        => trim($_POST['aloqa_email']   ?? ''),
                    'telegram_kanal'     => trim($_POST['tg_kanal']      ?? ''),
                    'telegram_bot_token' => trim($_POST['tg_token']      ?? ''),
                    'telegram_admin_id'  => trim($_POST['tg_admin']      ?? ''),
                    'click_merchant_id'  => trim($_POST['click_id']      ?? ''),
                    'click_secret'       => trim($_POST['click_secret']  ?? ''),
                    'payme_merchant_id'  => trim($_POST['payme_id']      ?? ''),
                    'payme_key'          => trim($_POST['payme_key']     ?? ''),
                    'xak_parol'          => password_hash(trim($_POST['xak_parol'] ?? bin2hex(random_bytes(8))), PASSWORD_BCRYPT),
                ];
                $st = $pdo->prepare(
                    'INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)'
                );
                foreach ($juftlar as $k => $v) {
                    $st->execute([$k, $v]);
                }

                // database.php ni yozish
                i_database_php_yoz($_SESSION['db']);

                // config.php'da SAYT_URL ni yangilash
                i_config_php_yangila($sayt_url);

                $_SESSION['xak_parol_oddiy'] = trim($_POST['xak_parol'] ?? '');
                $_SESSION['oxirgi_qadam'] = 5;
                header('Location: ?qadam=6'); exit;
            } catch (Throwable $e) {
                $xato = 'Sozlamalarni saqlash xatosi: ' . $e->getMessage();
            }
        }
    }
}

// ---------- QADAM 6: TUGALLASH (lock) ----------
if ($qadam === 6 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!i_csrf_ok()) {
        $xato = 'CSRF token noto\'g\'ri.';
    } else {
        // install.lock yaratish
        @file_put_contents(I_LOCK, json_encode([
            'vaqt'    => date('c'),
            'admin'   => $_SESSION['admin']['telefon'] ?? '',
            'sayt'    => $_SESSION['db']['name'] ?? '',
            'versiya' => '1.0.0',
        ], JSON_UNESCAPED_UNICODE));

        $xak_parol = $_SESSION['xak_parol_oddiy'] ?? '';
        $admin_tel = $_SESSION['admin']['telefon'] ?? '';
        // Sessiyani tozalash
        $_SESSION = [];
        session_destroy();

        ?><!DOCTYPE html><html lang="uz"><head><?= i_head_html('Tugadi') ?></head><body class="bg-slate-950 text-slate-100">
        <?php i_header_html(6) ?>
        <div class="max-w-2xl mx-auto px-4 py-12">
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-8 text-center">
                <div class="text-6xl mb-4">✅</div>
                <h2 class="text-2xl font-bold mb-2">O'rnatish muvaffaqiyatli yakunlandi!</h2>
                <p class="text-slate-300 mb-6">
                    <code class="px-2 py-1 bg-slate-800 rounded text-sm">install.lock</code> fayli yaratildi va sahifa qulflandi.
                </p>
                <div class="text-left bg-slate-900 rounded-xl p-5 mb-6 space-y-2 text-sm">
                    <div><span class="text-slate-400">Admin telefon:</span> <code><?= htmlspecialchars($admin_tel, ENT_QUOTES) ?></code></div>
                    <?php if ($xak_parol): ?>
                    <div><span class="text-slate-400">xak.php paroli:</span> <code><?= htmlspecialchars($xak_parol, ENT_QUOTES) ?></code></div>
                    <div class="text-amber-300">⚠️ Bu parolni xavfsiz joyda saqlang — boshqa ko'rsatilmaydi.</div>
                    <?php endif; ?>
                </div>
                <div class="flex gap-3 justify-center flex-wrap">
                    <a href="/" class="inline-block px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Bosh sahifa</a>
                    <a href="/login" class="inline-block px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">Tizimga kirish</a>
                    <a href="/xak.php" class="inline-block px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">Monitoring (xak.php)</a>
                </div>
                <p class="text-xs text-slate-500 mt-6">
                    Xavfsizlik uchun: <code>install.php</code> faylini serverdan o'chirib tashlang.
                </p>
            </div>
        </div>
        </body></html><?php
        exit;
    }
}

// ================================================================
// UI RENDERING
// ================================================================
?><!DOCTYPE html>
<html lang="uz">
<head>
<?= i_head_html('Qadam ' . $qadam) ?>
</head>
<body class="bg-slate-950 text-slate-100">
<?php i_header_html($qadam) ?>

<div class="max-w-3xl mx-auto px-4 py-8">

<?php if ($xato): ?>
<div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300">
    <strong>Xato:</strong> <?= htmlspecialchars($xato, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<?php
switch ($qadam) {
    case 0: i_qadam_0_xush_kelibsiz();      break;
    case 1: i_qadam_1_talablar($talablar);  break;
    case 2: i_qadam_2_db();                 break;
    case 3: i_qadam_3_schema();             break;
    case 4: i_qadam_4_admin();              break;
    case 5: i_qadam_5_sozlamalar();         break;
    default: header('Location: ?qadam=0');  break;
}
?>

</div>
</body>
</html>

<?php
// ================================================================
//                    QADAMLAR (UI)
// ================================================================

function i_qadam_0_xush_kelibsiz(): void { ?>
<div class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8">
    <h2 class="text-3xl font-bold mb-3">VatanParvar Yaypan o'rnatish ustasi</h2>
    <p class="text-slate-400 mb-6">
        Bu sehrgar tizimni serveringizga o'rnatishga yordam beradi. Jami 6 ta qadam:
    </p>
    <ol class="space-y-2 text-slate-300 mb-8 list-decimal list-inside">
        <li>Tizim talablari tekshiruvi (PHP, kengaytmalar, papkalar)</li>
        <li>Ma'lumotlar bazasi sozlamalari</li>
        <li>SQL schema import qilish</li>
        <li>Birinchi admin akkaunt yaratish</li>
        <li>Sayt sozlamalari (URL, telegram, to'lov...)</li>
        <li>Yakunlash va qulflash</li>
    </ol>
    <p class="text-amber-300 text-sm mb-6">
        ⚠️ O'rnatish davomida xato bo'lmasin uchun cPanel'dan <strong>MySQL bazasi va foydalanuvchisini</strong>
        oldindan yarating va parolni eslab qoling.
    </p>
    <a href="?qadam=1" class="inline-block px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">
        Boshlash →
    </a>
</div>
<?php }

function i_qadam_1_talablar(array $t): void {
    $hammasi_ok = !in_array(false, array_column($t, 'ok'), true);
    ?>
<div class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8">
    <h2 class="text-2xl font-bold mb-4">1. Tizim talablari</h2>
    <div class="divide-y divide-slate-800">
        <?php foreach ($t as $r): ?>
        <div class="py-3 flex items-center gap-3">
            <span class="<?= $r['ok'] ? 'text-emerald-400' : 'text-red-400' ?> text-xl">
                <?= $r['ok'] ? '✓' : '✗' ?>
            </span>
            <div class="flex-1">
                <div class="font-medium"><?= htmlspecialchars($r['nom'], ENT_QUOTES) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['izoh'], ENT_QUOTES) ?></div>
            </div>
            <div class="text-sm text-slate-400"><?= htmlspecialchars($r['qiymat'], ENT_QUOTES) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6 flex justify-between gap-3">
        <a href="?qadam=0" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">← Orqaga</a>
        <?php if ($hammasi_ok): ?>
            <a href="?qadam=2" class="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Davom etish →</a>
        <?php else: ?>
            <button disabled class="px-6 py-3 rounded-xl bg-slate-700 text-slate-400 font-semibold cursor-not-allowed">Davom etish →</button>
        <?php endif; ?>
    </div>
    <?php if (!$hammasi_ok): ?>
        <p class="mt-4 text-amber-300 text-sm">
            ⚠️ Yuqoridagi muammolarni hal qiling va sahifani yangilang. cPanel'da PHP versiyasi
            va kengaytmalarini "PHP Selector" yoki "Select PHP Version" orqali boshqarishingiz mumkin.
        </p>
    <?php endif; ?>
</div>
<?php }

function i_qadam_2_db(): void {
    $eski = $_SESSION['db'] ?? ['host' => 'localhost', 'name' => '', 'user' => '', 'pass' => ''];
    ?>
<form method="POST" action="?qadam=2" class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8 space-y-5">
    <h2 class="text-2xl font-bold">2. Ma'lumotlar bazasi</h2>
    <p class="text-sm text-slate-400 -mt-3">cPanel'dan oldindan yaratilgan baza va foydalanuvchini kiriting.</p>
    <?= i_csrf_input() ?>
    <div>
        <label class="block text-sm text-slate-400 mb-1">DB Host</label>
        <input type="text" name="db_host" required value="<?= htmlspecialchars($eski['host'], ENT_QUOTES) ?>"
               class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        <p class="text-xs text-slate-500 mt-1">Odatda <code>localhost</code></p>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-400 mb-1">Baza nomi</label>
            <input type="text" name="db_name" required value="<?= htmlspecialchars($eski['name'], ENT_QUOTES) ?>"
                   placeholder="cpaneluser_dbname"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Foydalanuvchi</label>
            <input type="text" name="db_user" required value="<?= htmlspecialchars($eski['user'], ENT_QUOTES) ?>"
                   placeholder="cpaneluser_dbuser"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-400 mb-1">Parol</label>
        <input type="password" name="db_pass" required
               class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
    </div>
    <div class="flex justify-between gap-3 pt-3">
        <a href="?qadam=1" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">← Orqaga</a>
        <button type="submit" class="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Tekshirib davom etish →</button>
    </div>
</form>
<?php }

function i_qadam_3_schema(): void {
    $schema_bor = is_file(I_SCHEMA) || is_file(I_SCHEMA_ALT);
    ?>
<form method="POST" action="?qadam=3" enctype="multipart/form-data" class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8 space-y-5">
    <h2 class="text-2xl font-bold">3. SQL schema import</h2>
    <p class="text-sm text-slate-400 -mt-3">Bazaga jadvallar va boshlang'ich ma'lumotlar yuklanadi.</p>
    <?= i_csrf_input() ?>

    <?php if ($schema_bor): ?>
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-200">
        ✓ Standart <code>database/schema.sql</code> fayli topildi va avto-import qilinadi.
    </div>
    <?php else: ?>
    <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-200">
        ⚠️ Standart schema fayli topilmadi. O'z .sql faylingizni yuklang.
    </div>
    <?php endif; ?>

    <details class="rounded-xl bg-slate-800/40 border border-slate-700 p-4">
        <summary class="cursor-pointer text-sm text-slate-300">Yoki o'zingizning .sql dump faylingizni yuklang (ixtiyoriy)</summary>
        <input type="file" name="sql_fayl" accept=".sql,.txt"
               class="mt-3 block w-full text-sm text-slate-300
                      file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                      file:bg-blue-500 file:text-white hover:file:bg-blue-400">
        <p class="text-xs text-slate-500 mt-2">Maks. 50MB</p>
    </details>

    <div class="flex justify-between gap-3 pt-3">
        <a href="?qadam=2" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">← Orqaga</a>
        <button type="submit" class="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Import qilish →</button>
    </div>
</form>
<?php }

function i_qadam_4_admin(): void { ?>
<form method="POST" action="?qadam=4" class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8 space-y-5">
    <h2 class="text-2xl font-bold">4. Birinchi admin akkaunt</h2>
    <p class="text-sm text-slate-400 -mt-3">Bu akkaunt <strong>developer</strong> roliga ega bo'ladi va to'liq kirishga ega.</p>
    <?= i_csrf_input() ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-400 mb-1">Ism</label>
            <input type="text" name="ism" required
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Familiya</label>
            <input type="text" name="familiya"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
    </div>
    <div>
        <label class="block text-sm text-slate-400 mb-1">Telefon</label>
        <input type="tel" name="telefon" required placeholder="+998 90 123 45 67"
               class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-400 mb-1">Parol (kamida 8 ta belgi)</label>
            <input type="password" name="parol" required minlength="8"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Parolni takrorlang</label>
            <input type="password" name="parol2" required minlength="8"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
    </div>
    <div class="flex justify-between gap-3 pt-3">
        <a href="?qadam=3" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">← Orqaga</a>
        <button type="submit" class="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Akkauntni yaratish →</button>
    </div>
</form>
<?php }

function i_qadam_5_sozlamalar(): void {
    $oddiy_xak = bin2hex(random_bytes(6));
    $sayt = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.uz');
    ?>
<form method="POST" action="?qadam=5" class="rounded-2xl bg-slate-900/70 border border-slate-800 p-8 space-y-5">
    <h2 class="text-2xl font-bold">5. Sayt sozlamalari</h2>
    <p class="text-sm text-slate-400 -mt-3">Asosiy sayt parametrlari. Keyin admin paneldan o'zgartirsa ham bo'ladi.</p>
    <?= i_csrf_input() ?>

    <div>
        <label class="block text-sm text-slate-400 mb-1">Sayt URL <span class="text-red-400">*</span></label>
        <input type="url" name="sayt_url" required value="<?= htmlspecialchars($sayt, ENT_QUOTES) ?>"
               class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        <p class="text-xs text-slate-500 mt-1">Slash siz, masalan <code>https://vatanparvaryaypan.uz</code></p>
    </div>
    <div>
        <label class="block text-sm text-slate-400 mb-1">Sayt nomi</label>
        <input type="text" name="sayt_nomi" value="VatanParvar Yaypan"
               class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-slate-400 mb-1">Aloqa telefoni</label>
            <input type="tel" name="aloqa_telefon" placeholder="+998 90 123 45 67"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Aloqa email</label>
            <input type="email" name="aloqa_email" placeholder="info@example.uz"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
        </div>
    </div>

    <details class="rounded-xl bg-slate-800/40 border border-slate-700 p-4 space-y-3">
        <summary class="cursor-pointer text-sm font-medium">Telegram (ixtiyoriy)</summary>
        <input type="text" name="tg_kanal"  placeholder="https://t.me/kanal"
               class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 mt-3 text-sm">
        <input type="text" name="tg_token"  placeholder="Bot token (123456:ABC...)"
               class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
        <input type="text" name="tg_admin"  placeholder="Admin Telegram ID"
               class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
    </details>

    <details class="rounded-xl bg-slate-800/40 border border-slate-700 p-4 space-y-3">
        <summary class="cursor-pointer text-sm font-medium">To'lov tizimlari (ixtiyoriy)</summary>
        <div class="grid sm:grid-cols-2 gap-3 mt-3">
            <input type="text" name="click_id"     placeholder="Click Merchant ID"
                   class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
            <input type="text" name="click_secret" placeholder="Click Secret"
                   class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
            <input type="text" name="payme_id"     placeholder="Payme Merchant ID"
                   class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
            <input type="text" name="payme_key"    placeholder="Payme Key"
                   class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
        </div>
    </details>

    <div class="rounded-xl bg-amber-500/10 border border-amber-500/30 p-4">
        <label class="block text-sm font-medium text-amber-200 mb-2">
            🔐 Monitoring paneli (xak.php) paroli
        </label>
        <input type="text" name="xak_parol" required value="<?= htmlspecialchars($oddiy_xak, ENT_QUOTES) ?>"
               class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm font-mono">
        <p class="text-xs text-amber-200/70 mt-2">
            Bu parol bilan <code>/xak.php</code> sahifasiga kirish mumkin (developer roli + parol).
            Avtomatik yaratildi — istasangiz o'zgartiring.
        </p>
    </div>

    <div class="flex justify-between gap-3 pt-3">
        <a href="?qadam=4" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700">← Orqaga</a>
        <button type="submit" class="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">Saqlash va davom etish →</button>
    </div>
</form>
<?php
}

// ================================================================
//                 YORDAMCHI FUNKSIYALAR
// ================================================================

/**
 * Tizim talablarini tekshirish.
 */
function i_talablar_tekshir(): array {
    $papka_yoz = function (string $yol): array {
        $bor = is_dir($yol);
        if (!$bor) {
            $bor = @mkdir($yol, 0755, true);
        }
        $yozila = $bor && is_writable($yol);
        return [
            'nom'    => "Papka: " . str_replace(__DIR__, '', $yol),
            'qiymat' => $yozila ? 'yoziladi' : (is_dir($yol) ? 'faqat o\'qish' : 'topilmadi'),
            'ok'     => $yozila,
            'izoh'   => 'Papka mavjud va yozish mumkin bo\'lishi kerak (chmod 755)',
        ];
    };

    return [
        [
            'nom'    => 'PHP versiya',
            'qiymat' => PHP_VERSION,
            'ok'     => version_compare(PHP_VERSION, '8.0.0', '>='),
            'izoh'   => 'PHP 8.0 yoki undan yuqori talab qilinadi',
        ],
        [
            'nom'    => 'PDO + pdo_mysql',
            'qiymat' => extension_loaded('pdo') && extension_loaded('pdo_mysql') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
            'izoh'   => 'MySQL bilan ishlash uchun zarur',
        ],
        [
            'nom'    => 'mbstring',
            'qiymat' => extension_loaded('mbstring') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('mbstring'),
            'izoh'   => 'UTF-8 bilan ishlash uchun zarur',
        ],
        [
            'nom'    => 'gd (rasm bilan ishlash)',
            'qiymat' => extension_loaded('gd') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('gd'),
            'izoh'   => 'Rasmlarni WebP ga o\'girish uchun',
        ],
        [
            'nom'    => 'curl',
            'qiymat' => extension_loaded('curl') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('curl'),
            'izoh'   => 'Telegram va to\'lov API\'lari uchun',
        ],
        [
            'nom'    => 'openssl',
            'qiymat' => extension_loaded('openssl') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('openssl'),
            'izoh'   => 'Xavfsiz tasodifiy sonlar uchun',
        ],
        [
            'nom'    => 'JSON',
            'qiymat' => extension_loaded('json') ? 'mavjud' : 'YO\'Q',
            'ok'     => extension_loaded('json'),
            'izoh'   => 'Loglar va API uchun',
        ],
        $papka_yoz(I_CONFIG),
        $papka_yoz(I_LOG),
        $papka_yoz(__DIR__ . '/uploads'),
        $papka_yoz(__DIR__ . '/kesh'),
        $papka_yoz(__DIR__ . '/zaxira_nusxalari'),
        [
            'nom'    => 'config/database.php yozilishi mumkin',
            'qiymat' => is_writable(I_CONFIG) ? 'ha' : 'yo\'q',
            'ok'     => is_writable(I_CONFIG),
            'izoh'   => 'O\'rnatish vaqtida fayl yangilanadi',
        ],
    ];
}

/**
 * SQL faylni qatorma-qator import qilish.
 *  Multi-statement, --, /* * /, va string ichidagi ; lar e'tiborga olinadi.
 */
function i_sql_import(PDO $pdo, string $sql): array {
    // BOM-ni olib tashlash
    $sql = preg_replace("/^\xEF\xBB\xBF/", '', $sql);

    $statements = i_sql_bo_lish($sql);
    $jami = 0; $bajarildi = 0;
    foreach ($statements as $st) {
        $st = trim($st);
        if ($st === '' || preg_match('/^(--|#|\/\*)/', $st)) continue;
        $jami++;
        try {
            $pdo->exec($st);
            $bajarildi++;
        } catch (PDOException $e) {
            // Duplicate key (1062) yoki "table exists" (1050) — kechirimli
            if (in_array((int) $e->errorInfo[1] ?? 0, [1050, 1062, 1061], true)) {
                continue;
            }
            return [
                'xato' => $e->getMessage() . " | SQL: " . mb_substr($st, 0, 150),
                'jami' => $jami,
                'bajarildi' => $bajarildi,
            ];
        }
    }
    return ['xato' => '', 'jami' => $jami, 'bajarildi' => $bajarildi];
}

/**
 * SQL matnni alohida statementlarga bo'lish.
 *  String ichidagi ; va commentlarni hisobga oladi.
 */
function i_sql_bo_lish(string $sql): array {
    $natija = [];
    $hozirgi = '';
    $qator = ''; // string delimiter (' yoki " yoki `)
    $kommentda = false;
    $satr_kommentda = false;
    $uz = strlen($sql);
    for ($i = 0; $i < $uz; $i++) {
        $b = $sql[$i];
        $kel = $sql[$i + 1] ?? '';

        if ($satr_kommentda) {
            if ($b === "\n") $satr_kommentda = false;
            $hozirgi .= $b;
            continue;
        }
        if ($kommentda) {
            if ($b === '*' && $kel === '/') { $kommentda = false; $hozirgi .= $b; $hozirgi .= $kel; $i++; continue; }
            $hozirgi .= $b;
            continue;
        }
        if ($qator) {
            if ($b === '\\' && $kel) { $hozirgi .= $b . $kel; $i++; continue; }
            if ($b === $qator) $qator = '';
            $hozirgi .= $b;
            continue;
        }
        // Tashqarida
        if ($b === '-' && $kel === '-') { $satr_kommentda = true; $hozirgi .= $b; continue; }
        if ($b === '#') { $satr_kommentda = true; $hozirgi .= $b; continue; }
        if ($b === '/' && $kel === '*') { $kommentda = true; $hozirgi .= $b; continue; }
        if ($b === "'" || $b === '"' || $b === '`') { $qator = $b; $hozirgi .= $b; continue; }
        if ($b === ';') {
            $natija[] = $hozirgi;
            $hozirgi = '';
            continue;
        }
        $hozirgi .= $b;
    }
    if (trim($hozirgi) !== '') $natija[] = $hozirgi;
    return $natija;
}

/**
 * config/database.php ni yangi DB credentials bilan yozish.
 */
function i_database_php_yoz(array $db): void {
    $h = addslashes($db['host']);
    $n = addslashes($db['name']);
    $u = addslashes($db['user']);
    $p = addslashes($db['pass']);
    $eski = is_file(I_DB_FAYL) ? file_get_contents(I_DB_FAYL) : '';

    if ($eski && preg_match('/define\(\s*[\'"]DB_HOST[\'"]/', $eski)) {
        $yangi = preg_replace('/define\(\s*[\'"]DB_HOST[\'"]\s*,.*?\)\s*;/',  "define('DB_HOST', '$h');", $eski);
        $yangi = preg_replace('/define\(\s*[\'"]DB_NAME[\'"]\s*,.*?\)\s*;/',  "define('DB_NAME', '$n');", $yangi);
        $yangi = preg_replace('/define\(\s*[\'"]DB_USER[\'"]\s*,.*?\)\s*;/',  "define('DB_USER', '$u');", $yangi);
        $yangi = preg_replace('/define\(\s*[\'"]DB_PASS[\'"]\s*,.*?\)\s*;/',  "define('DB_PASS', '$p');", $yangi);
        @file_put_contents(I_DB_FAYL, $yangi);
        return;
    }

    // Eski fayl yo'q — to'liq qaytadan yozamiz
    $shablon = <<<PHP
<?php
/**
 * VatanParvar Yaypan — Ma'lumotlar bazasi (PDO)
 * O'rnatish ustasi tomonidan {$h} uchun yaratildi.
 */

require_once __DIR__ . '/config.php';

define('DB_HOST', '$h');
define('DB_NAME', '$n');
define('DB_USER', '$u');
define('DB_PASS', '$p');
define('DB_CHARSET', 'utf8mb4');

PHP;
    $eski_funksiyalar = '';
    if ($eski && preg_match('/function\s+db\s*\(/', $eski)) {
        // Faqat funksiya qismini saqlab qolamiz
        $boshla = strpos($eski, 'function db');
        $eski_funksiyalar = "\n" . substr($eski, $boshla);
    } else {
        // Default funksiyalarni qo'shamiz
        $eski_funksiyalar = <<<'PHP'

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    }
    return $pdo;
}
function db_qator(string $sql, array $p = []): ?array { $s=db()->prepare($sql);$s->execute($p);$r=$s->fetch();return $r?:null; }
function db_barcha(string $sql, array $p = []): array { $s=db()->prepare($sql);$s->execute($p);return $s->fetchAll(); }
function db_bajar(string $sql, array $p = []): int { $s=db()->prepare($sql);$s->execute($p);
    if (stripos(trim($sql),'INSERT')===0) return (int)db()->lastInsertId();
    return $s->rowCount(); }
function db_qiymat(string $sql, array $p = []) { $s=db()->prepare($sql);$s->execute($p);return $s->fetchColumn(); }
function sozlama(string $k, $standart=null) { static $kesh=null;
    if ($kesh===null) { $kesh=[]; foreach(db_barcha('SELECT kalit,qiymat FROM sozlamalar') as $r) $kesh[$r['kalit']]=$r['qiymat']; }
    return $kesh[$k] ?? $standart; }
function sozlama_saqla(string $k, $v): void {
    db_bajar('INSERT INTO sozlamalar (kalit,qiymat) VALUES (?,?) ON DUPLICATE KEY UPDATE qiymat=VALUES(qiymat)', [$k,(string)$v]); }
PHP;
    }

    @file_put_contents(I_DB_FAYL, $shablon . $eski_funksiyalar . "\n");
}

/**
 * config/config.php'da SAYT_URL va SAYT_NOMI ni yangilash.
 */
function i_config_php_yangila(string $sayt_url): void {
    $f = I_CONFIG . '/config.php';
    if (!is_file($f)) return;
    $kontent = file_get_contents($f);
    $u = addslashes($sayt_url);
    $yangi = preg_replace('/define\(\s*[\'"]SAYT_URL[\'"]\s*,.*?\)\s*;/', "define('SAYT_URL', '$u');", $kontent);
    @file_put_contents($f, $yangi);
}

/**
 * Tasodifiy referal kod.
 */
function i_random_kod(): string {
    $belgilar = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $kod = '';
    for ($i = 0; $i < 8; $i++) $kod .= $belgilar[random_int(0, strlen($belgilar)-1)];
    return $kod;
}

/**
 * Sahifa <head>i.
 */
function i_head_html(string $titul = ''): string {
    return '<meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>O\'rnatish — ' . htmlspecialchars($titul, ENT_QUOTES) . '</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:.9em}</style>';
}

/**
 * Yuqori navigatsiya (qadamlar bar).
 */
function i_header_html(int $hozirgi): void {
    $qadamlar = [
        0 => 'Boshlash',
        1 => 'Talablar',
        2 => 'Baza',
        3 => 'Schema',
        4 => 'Admin',
        5 => 'Sozlamalar',
        6 => 'Tugadi',
    ];
    ?>
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-10">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <h1 class="text-lg font-bold text-blue-400 mb-3">VatanParvar Yaypan — O'rnatish</h1>
            <ol class="flex flex-wrap gap-1 text-xs">
                <?php foreach ($qadamlar as $i => $nom):
                    $faol = $i === $hozirgi;
                    $tugadi = $i < $hozirgi;
                    $sinf = $faol ? 'bg-blue-500 text-white' :
                            ($tugadi ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-500');
                ?>
                <li class="px-2.5 py-1 rounded-md <?= $sinf ?>"><?= $i ?>. <?= $nom ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </header>
    <?php
}

/**
 * Oddiy xato sahifasi (lock paytida).
 */
function html_xato(string $sarlavha, string $matn): string {
    return '<!DOCTYPE html><html lang="uz"><head><meta charset="utf-8"><title>403</title>
        <script src="https://cdn.tailwindcss.com"></script></head>
        <body class="bg-slate-950 text-slate-100">
        <div class="max-w-xl mx-auto px-4 py-20 text-center">
            <div class="text-6xl mb-4">🔒</div>
            <h1 class="text-2xl font-bold mb-3">' . htmlspecialchars($sarlavha) . '</h1>
            <p class="text-slate-400">' . $matn . '</p>
        </div></body></html>';
}
