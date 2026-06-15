<?php
/**
 * VatanParvar Yaypan — Veb o'rnatuvchi (Installer)
 * ============================================
 * Foydalanish:
 *   1. Faylni serverga yuklang
 *   2. https://sayt.uz/install.php ga o'ting
 *   3. Ko'rsatmalarni bajaring
 *   4. O'rnatish tugagach fayl avtomatik o'chiriladi
 *
 * XAVFSIZLIK: O'rnatish tugagach bu fayl avtomatik o'chiriladi.
 *             Agar o'chmas — uni qo'lda o'chiring!
 */

// Allaqachon o'rnatilganmi?
$lock_file = __DIR__ . '/kesh/installed.lock';
if (file_exists($lock_file)) {
    header('Location: /login');
    exit;
}

// Sessiya
if (session_status() === PHP_SESSION_NONE) {
    session_name('INSTALL_SESS');
    session_start();
}

define('BASE_DIR',   dirname(__DIR__));
define('SCHEMA_SQL', BASE_DIR . '/database/schema.sql');
define('CONFIG_DB',  __DIR__ . '/config/database.php');
define('CONFIG_APP', __DIR__ . '/config/config.php');

$step   = (int) ($_GET['step'] ?? 1);
$errors = [];
$info   = [];

/* ──────────────────────────────────────────────────────────
   1-QADAM: Talablar tekshiruvi
   ────────────────────────────────────────────────────────── */
function tekshir_talablar(): array
{
    $r = [];

    // PHP versiyasi
    $r[] = [
        'nom'    => 'PHP versiyasi (8.0+)',
        'holat'  => version_compare(PHP_VERSION, '8.0.0', '>='),
        'qiymat' => PHP_VERSION,
    ];

    // Kengaytmalar
    $ext = ['pdo', 'pdo_mysql', 'gd', 'curl', 'mbstring', 'openssl', 'json', 'session'];
    foreach ($ext as $e) {
        $r[] = [
            'nom'   => "PHP {$e} kengaytmasi",
            'holat' => extension_loaded($e),
            'qiymat'=> extension_loaded($e) ? 'Yoqilgan' : 'O\'chirilgan',
        ];
    }

    // WebP qo'llab-quvvatlash
    $gd = gd_info();
    $r[] = [
        'nom'   => 'GD WebP qo\'llab-quvvatlash',
        'holat' => !empty($gd['WebP Support']),
        'qiymat'=> !empty($gd['WebP Support']) ? 'Ha' : 'Yo\'q',
    ];

    // Yozish huquqlari
    $dirs = ['uploads', 'kesh', 'zaxira_nusxalari'];
    foreach ($dirs as $d) {
        $yol  = __DIR__ . '/' . $d;
        $mumkin = is_writable($yol) || (!is_dir($yol) && is_writable(__DIR__));
        $r[] = [
            'nom'   => "{$d}/ papkasi yozish huquqi",
            'holat' => $mumkin,
            'qiymat'=> $mumkin ? 'OK' : 'Ruxsat yo\'q',
        ];
    }

    // Schema fayli
    $r[] = [
        'nom'   => 'database/schema.sql mavjud',
        'holat' => file_exists(SCHEMA_SQL),
        'qiymat'=> file_exists(SCHEMA_SQL) ? 'Topildi' : 'Topilmadi',
    ];

    // config/ yozish
    $r[] = [
        'nom'   => 'config/ papkasi yozish huquqi',
        'holat' => is_writable(__DIR__ . '/config'),
        'qiymat'=> is_writable(__DIR__ . '/config') ? 'OK' : 'Ruxsat yo\'q',
    ];

    return $r;
}

function barcha_ok(array $t): bool
{
    foreach ($t as $i) {
        if (!$i['holat']) return false;
    }
    return true;
}

/* ──────────────────────────────────────────────────────────
   2-QADAM: POST — O'rnatish
   ────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {

    $db_host = trim($_POST['db_host']  ?? 'localhost');
    $db_name = trim($_POST['db_name']  ?? '');
    $db_user = trim($_POST['db_user']  ?? '');
    $db_pass = trim($_POST['db_pass']  ?? '');
    $sayt_url  = rtrim(trim($_POST['sayt_url']  ?? ''), '/');
    $sayt_nomi = trim($_POST['sayt_nomi'] ?? 'VatanParvar Yaypan');
    $admin_ism  = trim($_POST['admin_ism']  ?? '');
    $admin_tel  = trim($_POST['admin_tel']  ?? '');
    $admin_pass = trim($_POST['admin_pass'] ?? '');

    // Validatsiya
    if (!$db_name)     $errors[] = 'Ma\'lumotlar bazasi nomi kiritilmadi';
    if (!$db_user)     $errors[] = 'DB foydalanuvchi nomi kiritilmadi';
    if (!$sayt_url)    $errors[] = 'Sayt URL kiritilmadi';
    if (!$admin_ism)   $errors[] = 'Admin ismi kiritilmadi';
    if (strlen($admin_pass) < 6) $errors[] = 'Admin paroli kamida 6 belgi bo\'lishi kerak';

    // Telefon format
    $tel = preg_replace('/\D+/', '', $admin_tel);
    if (strlen($tel) === 9)  $tel = '998' . $tel;
    if (strlen($tel) !== 12 || !str_starts_with($tel, '998')) {
        $errors[] = 'Admin telefon raqami noto\'g\'ri (+998XXXXXXXXX)';
    } else {
        $admin_tel = '+' . $tel;
    }

    if (empty($errors)) {
        // DB ulanish tekshiruvi
        try {
            $dsn = "mysql:host={$db_host};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $info[] = '✅ DB serverga ulanish muvaffaqiyatli';
        } catch (PDOException $e) {
            $errors[] = 'DB ulanish xatosi: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        // DB yaratish (agar mavjud bo'lmasa)
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db_name}`");
            $info[] = "✅ Ma'lumotlar bazasi: {$db_name}";
        } catch (PDOException $e) {
            $errors[] = 'DB yaratish xatosi: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        // Schema import
        $sql_content = file_get_contents(SCHEMA_SQL);
        if ($sql_content === false) {
            $errors[] = 'schema.sql o\'qib bo\'lmadi';
        } else {
            // Komentlar va bo'sh satrlarni tozalash
            $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
            // PREPARE/EXECUTE/DEALLOCATE bloklarini olib tashlash (PDO qo'llab-quvvatlamaydi)
            $sql_content = preg_replace('/SET @[^;]+;/s', '', $sql_content);
            $sql_content = preg_replace('/PREPARE[^;]+;/s', '', $sql_content);
            $sql_content = preg_replace('/EXECUTE[^;]+;/s', '', $sql_content);
            $sql_content = preg_replace('/DEALLOCATE[^;]+;/s', '', $sql_content);

            $statements = array_filter(
                array_map('trim', explode(';', $sql_content)),
                fn($s) => strlen($s) > 5
            );

            $jadvallar = 0;
            foreach ($statements as $stmt) {
                try {
                    $pdo->exec($stmt);
                    if (stripos($stmt, 'CREATE TABLE') !== false) {
                        $jadvallar++;
                    }
                } catch (PDOException $e) {
                    // Table/column already exists — o'tkazib yuboramiz
                    $code = $e->getCode();
                    if (!in_array($code, ['42S01', '42S21', '23000', '1060', '1061', '1050'], true)
                        && !str_contains($e->getMessage(), 'already exists')
                        && !str_contains($e->getMessage(), 'Duplicate')
                    ) {
                        $errors[] = 'SQL xato: ' . $e->getMessage();
                        break;
                    }
                }
            }

            // fikrlar.fikr_ip ustunini qo'shish (xavfsiz)
            try {
                $col = $pdo->query(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = '{$db_name}'
                       AND TABLE_NAME = 'fikrlar'
                       AND COLUMN_NAME = 'fikr_ip'"
                )->fetchColumn();
                if (!$col) {
                    $pdo->exec("ALTER TABLE `fikrlar` ADD COLUMN `fikr_ip` VARCHAR(45) DEFAULT NULL AFTER `tasdiq`");
                }
            } catch (Throwable) {}

            if (empty($errors)) {
                $info[] = "✅ Schema import: {$jadvallar} ta jadval yaratildi";
            }
        }
    }

    if (empty($errors)) {
        // Admin akkaunt yaratish yoki yangilash
        try {
            $existing = $pdo->prepare('SELECT id FROM foydalanuvchilar WHERE telefon = ?');
            $existing->execute([$admin_tel]);
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT, ['cost' => 12]);

            if ($existing->fetchColumn()) {
                $pdo->prepare(
                    'UPDATE foydalanuvchilar SET ism = ?, parol_hash = ?, rol = "developer" WHERE telefon = ?'
                )->execute([$admin_ism, $hash, $admin_tel]);
            } else {
                // Referal kod
                $kod = strtoupper(substr(md5(uniqid('', true)), 0, 8));
                $pdo->prepare(
                    'INSERT INTO foydalanuvchilar (ism, telefon, parol_hash, rol, referal_kod)
                     VALUES (?, ?, ?, "developer", ?)'
                )->execute([$admin_ism, $admin_tel, $hash, $kod]);
            }
            $info[] = "✅ Admin akkaunt: {$admin_ism} ({$admin_tel})";
        } catch (PDOException $e) {
            $errors[] = 'Admin akkaunt xatosi: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        // config/database.php ni yangilash
        $db_content = <<<PHP
<?php
/**
 * VatanParvar Yaypan — Ma'lumotlar bazasi (PDO)
 * O'rnatuvchi tomonidan yaratildi: <?= date('Y-m-d H:i:s') ?>
 */

require_once __DIR__ . '/config.php';

define('DB_HOST',    '{$db_host}');
define('DB_NAME',    '{$db_name}');
define('DB_USER',    '{$db_user}');
define('DB_PASS',    '{$db_pass}');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static \$pdo = null;
    if (\$pdo !== null) return \$pdo;
    \$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (PDOException \$e) {
        error_log('[VPY] DB xato: ' . \$e->getMessage());
        http_response_code(503);
        exit('Server vaqtinchalik mavjud emas.');
    }
    return \$pdo;
}

function db_qator(string \$sql, array \$p = []): ?array { \$st = db()->prepare(\$sql); \$st->execute(\$p); return \$st->fetch() ?: null; }
function db_barcha(string \$sql, array \$p = []): array  { \$st = db()->prepare(\$sql); \$st->execute(\$p); return \$st->fetchAll(); }
function db_bajar(string \$sql, array \$p = []): int { \$st = db()->prepare(\$sql); \$st->execute(\$p); return stripos(ltrim(\$sql),'INSERT')===0 ? (int)db()->lastInsertId() : \$st->rowCount(); }
function db_qiymat(string \$sql, array \$p = []): mixed { \$st = db()->prepare(\$sql); \$st->execute(\$p); return \$st->fetchColumn(); }

function sozlama(string \$k, mixed \$s = null): mixed {
    static \$c = null;
    if (\$c === null) { \$c = []; try { foreach (db_barcha('SELECT kalit,qiymat FROM sozlamalar') as \$r) \$c[\$r['kalit']] = \$r['qiymat']; } catch (Throwable) {} }
    return array_key_exists(\$k, \$c) ? \$c[\$k] : \$s;
}
function sozlama_saqla(string \$k, mixed \$v): void {
    db_bajar('INSERT INTO sozlamalar (kalit,qiymat) VALUES (?,?) ON DUPLICATE KEY UPDATE qiymat=VALUES(qiymat)', [\$k, (string)\$v]);
}
PHP;

        if (file_put_contents(CONFIG_DB, $db_content) === false) {
            $errors[] = 'config/database.php yozib bo\'lmadi';
        } else {
            $info[] = '✅ config/database.php yangilandi';
        }
    }

    if (empty($errors)) {
        // config/config.php da SAYT_URL va SAYT_NOMI ni yangilash
        $config_content = file_get_contents(CONFIG_APP);
        $config_content = preg_replace(
            "/define\('SAYT_URL',\s*'[^']*'\)/",
            "define('SAYT_URL',  '{$sayt_url}')",
            $config_content
        );
        $config_content = preg_replace(
            "/define\('SAYT_NOMI',\s*'[^']*'\)/",
            "define('SAYT_NOMI', '{$sayt_nomi}')",
            $config_content
        );
        if (file_put_contents(CONFIG_APP, $config_content) === false) {
            $errors[] = 'config/config.php yozib bo\'lmadi';
        } else {
            $info[] = "✅ config/config.php yangilandi ({$sayt_nomi})";
        }
    }

    if (empty($errors)) {
        // Sozlamalarni DBga yozish
        try {
            $updates = [
                'sayt_nomi'    => $sayt_nomi,
                'aloqa_telefon'=> trim($_POST['aloqa_tel'] ?? ''),
                'aloqa_email'  => trim($_POST['aloqa_email'] ?? ''),
            ];
            foreach ($updates as $k => $v) {
                if ($v) {
                    $pdo->prepare(
                        'INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)'
                    )->execute([$k, $v]);
                }
            }
        } catch (Throwable) {}

        // Papkalar yaratish
        foreach (['uploads/avatars', 'uploads/savollar', 'kesh', 'zaxira_nusxalari'] as $d) {
            $p = __DIR__ . '/' . $d;
            if (!is_dir($p)) @mkdir($p, 0755, true);
        }
        $info[] = '✅ Papkalar yaratildi';

        // Lock fayli yozish
        $lock_dir = __DIR__ . '/kesh';
        if (!is_dir($lock_dir)) @mkdir($lock_dir, 0755, true);
        file_put_contents($lock_file, date('Y-m-d H:i:s') . ' - ' . $sayt_nomi);
        $info[] = '✅ O\'rnatish yakunlandi!';

        // Sessiyaga saqlash
        $_SESSION['install_ok']       = true;
        $_SESSION['install_info']     = $info;
        $_SESSION['install_sayt_url'] = $sayt_url;
        $_SESSION['install_admin_tel']= $admin_tel;

        // install.php ni o'chirish urinishi
        @unlink(__FILE__);

        header('Location: install.php?step=3');
        exit;
    }

    // Xato bo'lsa formani qayta ko'rsatish
    $_SESSION['install_errors'] = $errors;
    $_SESSION['install_info']   = $info;
    $_SESSION['install_form']   = $_POST;
    header('Location: install.php?step=2');
    exit;
}

// Flash xabarlar sessiyadan
if (!empty($_SESSION['install_errors'])) {
    $errors = $_SESSION['install_errors'];
    unset($_SESSION['install_errors']);
}
if (!empty($_SESSION['install_info'])) {
    $info = $_SESSION['install_info'];
}
$form_data = $_SESSION['install_form'] ?? [];

// Talablar
$talablar = tekshir_talablar();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>O'rnatish — VatanParvar Yaypan</title>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#070C1A;--surface:rgba(255,255,255,.05);--border:rgba(255,255,255,.1);
            --blue:#3B82F6;--green:#10B981;--red:#EF4444;--amber:#F59E0B;
            --text:#F1F5FF;--muted:#6B7CA8;
        }
        html,body{background:var(--bg);color:var(--text);font-family:'Inter',system-ui,sans-serif;min-height:100vh;line-height:1.6}
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800;900&display=swap');
        h1,h2,h3{font-family:'Manrope',system-ui,sans-serif;letter-spacing:-.02em}

        /* Aurora */
        body::before{content:'';position:fixed;inset:0;z-index:-1;
            background:radial-gradient(ellipse at 20% 20%,rgba(59,130,246,.18) 0,transparent 55%),
                        radial-gradient(ellipse at 80% 80%,rgba(124,58,237,.14) 0,transparent 55%)}

        .wrap{max-width:640px;margin:0 auto;padding:3rem 1.25rem 5rem}

        /* Logo */
        .logo{display:flex;align-items:center;gap:.75rem;margin-bottom:2rem}
        .logo-icon{width:44px;height:44px;border-radius:12px;
            background:linear-gradient(135deg,#2563EB,#7C3AED);
            display:flex;align-items:center;justify-content:center;
            font-family:'Manrope',sans-serif;font-weight:900;color:#fff;font-size:1.2rem;
            box-shadow:0 6px 20px rgba(59,130,246,.4)}
        .logo-text{font-family:'Manrope',sans-serif;font-weight:800;font-size:1.25rem;color:#fff}
        .logo-text span{color:#60A5FA}

        /* Steps indicator */
        .steps{display:flex;align-items:center;gap:.5rem;margin-bottom:2rem;padding:.75rem 1rem;
            background:var(--surface);border:1px solid var(--border);border-radius:12px}
        .step-item{display:flex;align-items:center;gap:.5rem;flex:1;position:relative}
        .step-item:not(:last-child)::after{content:'';position:absolute;right:-.5rem;top:50%;
            transform:translateY(-50%);width:1px;height:60%;background:var(--border)}
        .step-num{width:26px;height:26px;border-radius:50%;border:2px solid var(--border);
            display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;
            flex-shrink:0;transition:all .2s}
        .step-num.active{background:var(--blue);border-color:var(--blue);color:#fff}
        .step-num.done{background:var(--green);border-color:var(--green);color:#fff}
        .step-label{font-size:.78rem;color:var(--muted)}
        .step-label.active{color:#fff;font-weight:600}

        /* Card */
        .card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem}

        /* Alert */
        .alert{padding:.875rem 1rem;border-radius:10px;font-size:.875rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.625rem}
        .alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#FCA5A5}
        .alert-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#6EE7B7}
        .alert-warn{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#FCD34D}

        /* Requirement row */
        .req-row{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:8px;font-size:.875rem}
        .req-row:nth-child(odd){background:rgba(255,255,255,.02)}
        .req-badge{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0}
        .req-badge.ok{background:rgba(16,185,129,.2);color:#10B981}
        .req-badge.fail{background:rgba(239,68,68,.2);color:#EF4444}
        .req-val{margin-left:auto;font-size:.78rem;font-family:monospace}
        .req-val.ok{color:#6EE7B7}.req-val.fail{color:#FCA5A5}

        /* Form */
        .field-group{margin-bottom:1.25rem}
        label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:.35rem;font-weight:500}
        input[type=text],input[type=password],input[type=url],input[type=tel],input[type=email]{
            width:100%;padding:.75rem 1rem;
            background:rgba(255,255,255,.04);
            border:1.5px solid rgba(255,255,255,.1);
            border-radius:9px;color:#F1F5FF;font-size:.9375rem;
            transition:border-color .2s,box-shadow .2s;outline:none}
        input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
        input::placeholder{color:rgba(255,255,255,.3)}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        @media(max-width:480px){.grid-2{grid-template-columns:1fr}}
        .section-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
            color:var(--muted);padding:.5rem 0;border-bottom:1px solid var(--border);margin-bottom:1rem}

        /* Button */
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;
            font-weight:600;border-radius:10px;border:none;cursor:pointer;
            padding:.8rem 1.75rem;font-size:.9375rem;transition:all .2s;font-family:'Inter',sans-serif}
        .btn-primary{background:linear-gradient(135deg,#2563EB,#4F46E5);color:#fff;
            box-shadow:0 4px 16px rgba(59,130,246,.4)}
        .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(59,130,246,.5)}
        .btn-ghost{background:rgba(255,255,255,.06);color:#fff;border:1px solid rgba(255,255,255,.12)}
        .btn-ghost:hover{background:rgba(255,255,255,.10)}
        .btn-full{width:100%;margin-top:1.5rem}
        .btn-success{background:linear-gradient(135deg,#059669,#10B981);color:#fff;
            box-shadow:0 4px 14px rgba(16,185,129,.4)}

        /* Info list */
        .info-list{list-style:none;space-y:.5rem}
        .info-list li{padding:.4rem 0;font-size:.875rem;border-bottom:1px solid rgba(255,255,255,.05);color:#6EE7B7}
        .info-list li:last-child{border-bottom:none}

        /* Success */
        .success-icon{text-align:center;font-size:4rem;margin:1.5rem 0 1rem}
        .creds{background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.1);
            border-radius:10px;padding:1.25rem;font-family:monospace;font-size:.9rem;
            margin:1.25rem 0;line-height:2}
        .creds b{color:#60A5FA}
        .creds .val{color:#6EE7B7;font-weight:700}

        .hint{font-size:.82rem;color:var(--muted);margin-top:.35rem}
        hr{border:none;border-top:1px solid var(--border);margin:1.75rem 0}
        @keyframes spin{to{transform:rotate(360deg)}}
        .spin{animation:spin 1s linear infinite;display:inline-block}
    </style>
</head>
<body>
<div class="wrap">

    <!-- Logo -->
    <div class="logo">
        <div class="logo-icon">A</div>
        <div>
            <div class="logo-text">VatanParvar <span>Yaypan</span></div>
            <div style="font-size:.78rem;color:var(--muted)">Veb o'rnatuvchi</div>
        </div>
    </div>

    <!-- Steps indicator -->
    <div class="steps">
        <?php
        $steps_data = [
            1 => 'Talablar',
            2 => 'Sozlash',
            3 => 'Tayyor!',
        ];
        foreach ($steps_data as $n => $label):
            $cls_num   = $n < $step ? 'done' : ($n === $step ? 'active' : '');
            $cls_label = ($n === $step ? 'active' : '');
        ?>
        <div class="step-item">
            <div class="step-num <?= $cls_num ?>">
                <?= $n < $step ? '✓' : $n ?>
            </div>
            <span class="step-label <?= $cls_label ?>"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php /* ══════════ STEP 1 — Talablar ══════════ */ if ($step === 1): ?>

    <div class="card">
        <h2 style="margin-bottom:1.25rem;font-size:1.25rem">⚙️ Tizim talablari</h2>

        <?php if (!barcha_ok($talablar)): ?>
        <div class="alert alert-warn">
            ⚠️ Ba'zi talablar bajarilmagan. Serveringiz konfiguratsiyasini tekshiring.
        </div>
        <?php endif; ?>

        <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:1.5rem">
            <?php foreach ($talablar as $t): ?>
            <div class="req-row">
                <div class="req-badge <?= $t['holat'] ? 'ok' : 'fail' ?>">
                    <?= $t['holat'] ? '✓' : '✗' ?>
                </div>
                <span><?= htmlspecialchars($t['nom']) ?></span>
                <span class="req-val <?= $t['holat'] ? 'ok' : 'fail' ?>">
                    <?= htmlspecialchars($t['qiymat']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (barcha_ok($talablar)): ?>
        <a href="install.php?step=2" class="btn btn-primary btn-full">
            Davom etish →
        </a>
        <?php else: ?>
        <button class="btn btn-ghost btn-full" disabled style="opacity:.5;cursor:not-allowed">
            Barcha talablar bajarilishi kerak
        </button>
        <div style="text-align:center;margin-top:.75rem">
            <a href="install.php?step=1" style="font-size:.82rem;color:var(--blue)">↻ Qayta tekshirish</a>
        </div>
        <?php endif; ?>
    </div>

    <?php /* ══════════ STEP 2 — Sozlash ══════════ */ elseif ($step === 2): ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-err">
        <div>
            <b>⚠️ Xatolar topildi:</b>
            <ul style="margin-top:.5rem;padding-left:1.25rem">
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
    <div class="alert alert-ok">
        <ul class="info-list">
            <?php foreach ($info as $i): ?>
            <li><?= htmlspecialchars($i) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="install.php?step=2" autocomplete="off"
          x-data="{loading:false}" @submit="loading=true">

        <!-- ── DB sozlamalari ── -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="section-title">🗄️ Ma'lumotlar bazasi</div>

            <div class="grid-2">
                <div class="field-group">
                    <label>DB Server (host)</label>
                    <input type="text" name="db_host" required
                           value="<?= htmlspecialchars($form_data['db_host'] ?? 'localhost') ?>"
                           placeholder="localhost">
                </div>
                <div class="field-group">
                    <label>DB nomi *</label>
                    <input type="text" name="db_name" required
                           value="<?= htmlspecialchars($form_data['db_name'] ?? '') ?>"
                           placeholder="vatanparvar_db">
                </div>
                <div class="field-group">
                    <label>DB foydalanuvchisi *</label>
                    <input type="text" name="db_user" required
                           value="<?= htmlspecialchars($form_data['db_user'] ?? '') ?>"
                           placeholder="vatanparvar_user">
                </div>
                <div class="field-group">
                    <label>DB paroli</label>
                    <input type="password" name="db_pass"
                           value="<?= htmlspecialchars($form_data['db_pass'] ?? '') ?>"
                           placeholder="••••••••">
                </div>
            </div>
        </div>

        <!-- ── Sayt sozlamalari ── -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="section-title">🌐 Sayt ma'lumotlari</div>

            <div class="field-group">
                <label>Sayt URL (oxirida / bo'lmasin) *</label>
                <input type="url" name="sayt_url" required
                       value="<?= htmlspecialchars($form_data['sayt_url'] ?? 'https://') ?>"
                       placeholder="https://vatanparvaryaypan.uz">
                <div class="hint">Misol: https://vatanparvaryaypan.uz</div>
            </div>

            <div class="grid-2">
                <div class="field-group">
                    <label>Sayt nomi</label>
                    <input type="text" name="sayt_nomi"
                           value="<?= htmlspecialchars($form_data['sayt_nomi'] ?? 'VatanParvar Yaypan') ?>"
                           placeholder="VatanParvar Yaypan">
                </div>
                <div class="field-group">
                    <label>Aloqa telefoni</label>
                    <input type="tel" name="aloqa_tel"
                           value="<?= htmlspecialchars($form_data['aloqa_tel'] ?? '') ?>"
                           placeholder="+998 90 123 45 67">
                </div>
                <div class="field-group">
                    <label>Aloqa email</label>
                    <input type="email" name="aloqa_email"
                           value="<?= htmlspecialchars($form_data['aloqa_email'] ?? '') ?>"
                           placeholder="info@sayt.uz">
                </div>
            </div>
        </div>

        <!-- ── Admin akkaunt ── -->
        <div class="card">
            <div class="section-title">👤 Admin akkaunt</div>

            <div class="grid-2">
                <div class="field-group">
                    <label>Ism *</label>
                    <input type="text" name="admin_ism" required
                           value="<?= htmlspecialchars($form_data['admin_ism'] ?? '') ?>"
                           placeholder="Admin">
                </div>
                <div class="field-group">
                    <label>Telefon raqami *</label>
                    <input type="tel" name="admin_tel" required
                           value="<?= htmlspecialchars($form_data['admin_tel'] ?? '') ?>"
                           placeholder="+998 90 000 00 00">
                    <div class="hint">Login uchun ishlatiladi</div>
                </div>
                <div class="field-group">
                    <label>Parol * (kamida 6 belgi)</label>
                    <input type="password" name="admin_pass" required minlength="6"
                           placeholder="••••••••••">
                </div>
                <div class="field-group">
                    <label>Parolni tasdiqlang *</label>
                    <input type="password" name="admin_pass2" required minlength="6"
                           placeholder="••••••••••">
                </div>
            </div>

            <div class="alert alert-warn" style="margin-top:1rem">
                🔒 O'rnatishdan so'ng parolni albatta o'zgartiring!
            </div>

            <button type="submit" class="btn btn-primary btn-full"
                    id="installBtn">
                🚀 O'rnatishni boshlash
            </button>

            <div id="loadingMsg" style="display:none;text-align:center;margin-top:1rem;color:var(--muted)">
                <span class="spin">⚙️</span> O'rnatilmoqda, iltimos kuting...
            </div>
        </div>
    </form>

    <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var p1 = this.admin_pass.value, p2 = this.admin_pass2.value;
        if (p1 !== p2) {
            e.preventDefault();
            alert('Parollar mos kelmadi!');
            return;
        }
        document.getElementById('installBtn').disabled = true;
        document.getElementById('loadingMsg').style.display = 'block';
    });
    </script>

    <?php /* ══════════ STEP 3 — Muvaffaqiyat ══════════ */ elseif ($step === 3): ?>

    <?php
    $ok_info   = $_SESSION['install_info']      ?? [];
    $sayt_url  = $_SESSION['install_sayt_url']  ?? '#';
    $admin_tel = $_SESSION['install_admin_tel'] ?? '';
    unset($_SESSION['install_info'], $_SESSION['install_sayt_url'],
          $_SESSION['install_admin_tel'], $_SESSION['install_ok'], $_SESSION['install_form']);
    ?>

    <div class="card" style="text-align:center">
        <div class="success-icon">🎉</div>
        <h2 style="font-size:1.5rem;margin-bottom:.5rem">O'rnatish yakunlandi!</h2>
        <p style="color:var(--muted);margin-bottom:1.5rem">
            VatanParvar Yaypan muvaffaqiyatli o'rnatildi.
        </p>

        <?php if (!empty($ok_info)): ?>
        <div style="text-align:left;margin-bottom:1.5rem">
            <?php foreach ($ok_info as $i): ?>
            <div style="padding:.35rem 0;font-size:.875rem;color:#6EE7B7;border-bottom:1px solid rgba(255,255,255,.05)">
                <?= htmlspecialchars($i) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <hr>

        <div class="creds" style="text-align:left">
            <div><b>🌐 Sayt:</b> <span class="val"><?= htmlspecialchars($sayt_url) ?></span></div>
            <div><b>📞 Login:</b> <span class="val"><?= htmlspecialchars($admin_tel) ?></span></div>
            <div><b>🔑 Parol:</b> <span style="color:var(--amber)">Siz kiritgan parol</span></div>
            <div style="margin-top:.5rem;font-size:.8rem;color:var(--muted)">
                ⚠️ Parolni darhol o'zgartiring: Profil → Parolni o'zgartirish
            </div>
        </div>

        <div class="alert alert-warn" style="text-align:left">
            <div>
                <b>Keyingi muhim qadamlar:</b>
                <ol style="margin-top:.5rem;padding-left:1.25rem;line-height:2">
                    <li>Admin panel → <b>Sozlamalar</b> → Telegram bot tokeni</li>
                    <li>Admin panel → Sozlamalar → Click/Payme sozlamalari</li>
                    <li>Admin panel → <b>Biletlar</b> → Savollar qo'shish</li>
                    <li>Telegram webhook: <code style="background:rgba(255,255,255,.1);padding:.1rem .3rem;border-radius:4px">cron/sozlash.php</code></li>
                </ol>
            </div>
        </div>

        <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap">
            <a href="<?= htmlspecialchars($sayt_url) ?>/login"
               class="btn btn-primary">
                Tizimga kirish →
            </a>
            <a href="<?= htmlspecialchars($sayt_url) ?>/admin/"
               class="btn btn-ghost">
                Admin panel
            </a>
        </div>

        <p style="margin-top:1.5rem;font-size:.82rem;color:var(--muted)">
            install.php fayli avtomatik o'chirildi.
            Agar o'chmas bo'lsa — uni qo'lda o'chiring!
        </p>
    </div>

    <?php endif; ?>

    <div style="text-align:center;margin-top:2rem;font-size:.78rem;color:var(--muted)">
        VatanParvar Yaypan © <?= date('Y') ?> — O'rnatuvchi
    </div>
</div>
</body>
</html>
