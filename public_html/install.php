<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ROOT          = __DIR__;
$CONFIG_DIR    = $ROOT . '/config';
$LOCAL_CONFIG  = $CONFIG_DIR . '/config.local.php';
$LOCK_FILE     = $CONFIG_DIR . '/installed.lock';
$IP_LOCK_FILE  = $CONFIG_DIR . '/.install_ip_lock';
$IP_LOCK_TTL   = 30 * 60;

$SCHEMA_FILE = $ROOT . '/../database/schema.sql';
if (!is_file($SCHEMA_FILE)) {
    $SCHEMA_FILE = $ROOT . '/database/schema.sql';
}

function inst_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            return explode(',', $_SERVER[$k])[0];
        }
    }
    return '0.0.0.0';
}

$ip_hozirgi = inst_ip();

if (!is_file($LOCK_FILE)) {
    if (is_file($IP_LOCK_FILE)) {
        $lock = json_decode(@file_get_contents($IP_LOCK_FILE) ?: '', true) ?: [];
        $vaqt_oldin = $lock['vaqt'] ?? 0;
        $ip_lock = $lock['ip'] ?? '';

        if (time() - $vaqt_oldin > $IP_LOCK_TTL) {
            @unlink($IP_LOCK_FILE);
        } elseif ($ip_lock !== $ip_hozirgi) {
            http_response_code(403);
            $qolgan = $IP_LOCK_TTL - (time() - $vaqt_oldin);
            ?>
            <!DOCTYPE html>
            <html lang="uz">
            <head>
                <meta charset="UTF-8">
                <title>Forbidden</title>
                <style>
                    body { font-family: system-ui, sans-serif; background: #070B14; color: #F1F5F9; padding: 4rem; text-align: center; }
                    .box { max-width: 500px; margin: 0 auto; padding: 2rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 1rem; }
                    h1 { color: #FCA5A5; margin: 0 0 1rem; }
                </style>
            </head>
            <body>
                <div class="box">
                    <h1>🚫 403 Forbidden</h1>
                    <p>O'rnatuvchi boshqa IP manzilidan ochilgan.</p>
                    <p>Boshqa qurilmadan o'rnatilayotgan bo'lsa, kuting yoki <strong><?= floor($qolgan / 60) ?>:<?= str_pad($qolgan % 60, 2, '0', STR_PAD_LEFT) ?></strong> dan keyin urinib ko'ring.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    } else {
        @file_put_contents($IP_LOCK_FILE, json_encode([
            'ip'   => $ip_hozirgi,
            'vaqt' => time(),
        ]));
        @chmod($IP_LOCK_FILE, 0600);
    }
}

$bosqich = max(1, min(5, (int) ($_GET['bosqich'] ?? $_POST['bosqich'] ?? 1)));
$xato = '';
$muvaffaqiyat = '';

if (is_file($LOCK_FILE) && empty($_GET['developer_qayta_ornatish'])) {
    $bosqich = 99;
}

$KEREKLI_KENGAYTMALAR = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'curl', 'json', 'openssl', 'fileinfo'];
$KEREKLI_PHP = '8.0.0';

function tekshir_kengaytmalar(): array {
    global $KEREKLI_KENGAYTMALAR;
    $natija = [];
    foreach ($KEREKLI_KENGAYTMALAR as $k) {
        $natija[$k] = extension_loaded($k);
    }
    return $natija;
}

function tekshir_yozish(): array {
    global $ROOT;
    $papkalar = [
        'kesh'              => $ROOT . '/kesh',
        'uploads'           => $ROOT . '/uploads',
        'uploads/avatars'   => $ROOT . '/uploads/avatars',
        'uploads/savollar'  => $ROOT . '/uploads/savollar',
        'zaxira_nusxalari'  => $ROOT . '/zaxira_nusxalari',
        'config'            => $ROOT . '/config',
    ];
    $natija = [];
    foreach ($papkalar as $nom => $yol) {
        if (!is_dir($yol)) {
            @mkdir($yol, 0755, true);
        }
        $natija[$nom] = is_dir($yol) && is_writable($yol);
    }
    return $natija;
}

function db_test(string $host, string $name, string $user, string $pass): array {
    try {
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $bor = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($name))->fetchColumn();
        if (!$bor) {
            try {
                $pdo->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                return ['ok' => false, 'xato' => "Baza mavjud emas va yaratib bo'lmadi: {$e->getMessage()}"];
            }
        }
        $pdo->exec("USE `{$name}`");
        return ['ok' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['ok' => false, 'xato' => $e->getMessage()];
    }
}

function konfig_yarat(array $data): bool {
    global $LOCAL_CONFIG;
    $tana = "<?php\n\n";
    $tana .= "define('REJIM',     " . var_export($data['rejim'], true) . ");\n";
    $tana .= "define('SAYT_URL',  " . var_export(rtrim($data['sayt_url'], '/'), true) . ");\n\n";
    $tana .= "define('DB_HOST',   " . var_export($data['db_host'], true) . ");\n";
    $tana .= "define('DB_NAME',   " . var_export($data['db_name'], true) . ");\n";
    $tana .= "define('DB_USER',   " . var_export($data['db_user'], true) . ");\n";
    $tana .= "define('DB_PASS',   " . var_export($data['db_pass'], true) . ");\n";
    $tana .= "define('DB_CHARSET','utf8mb4');\n";
    return (bool) file_put_contents($LOCAL_CONFIG, $tana);
}

function schema_ishga_tushir(PDO $pdo, string $schema_yol): array {
    if (!is_file($schema_yol)) {
        return ['ok' => false, 'xato' => "schema.sql topilmadi: {$schema_yol}"];
    }
    $sql = file_get_contents($schema_yol);
    if (!$sql) {
        return ['ok' => false, 'xato' => 'schema.sql bo\'sh yoki o\'qib bo\'lmadi'];
    }
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    try {
        $pdo->exec($sql);
        return ['ok' => true];
    } catch (PDOException $e) {
        $statementlar = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statementlar as $s) {
            if ($s === '') continue;
            try {
                $pdo->exec($s);
            } catch (PDOException $e2) {
                if (!str_contains($e2->getMessage(), 'already exists') &&
                    !str_contains($e2->getMessage(), 'Duplicate')) {
                    return ['ok' => false, 'xato' => $e2->getMessage() . "\n\n" . substr($s, 0, 200)];
                }
            }
        }
        return ['ok' => true];
    }
}

function migrations_ishga_tushir(PDO $pdo, string $migrations_dir): array {
    if (!is_dir($migrations_dir)) {
        return ['ok' => true, 'bajarilgan' => []];
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS migratsiyalar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) UNIQUE NOT NULL,
        bajarilgan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $bajarilgan = $pdo->query('SELECT nom FROM migratsiyalar')->fetchAll(PDO::FETCH_COLUMN);
    $fayllar = glob($migrations_dir . '/*.sql') ?: [];
    sort($fayllar);

    $ishlangan = [];
    foreach ($fayllar as $f) {
        $nom = basename($f);
        if (in_array($nom, $bajarilgan, true)) continue;

        try {
            $sql = file_get_contents($f);
            $pdo->exec($sql);
            $st = $pdo->prepare('INSERT INTO migratsiyalar (nom) VALUES (?)');
            $st->execute([$nom]);
            $ishlangan[] = $nom;
        } catch (PDOException $e) {
            return ['ok' => false, 'xato' => "Migration {$nom}: " . $e->getMessage()];
        }
    }
    return ['ok' => true, 'bajarilgan' => $ishlangan];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['action'] ?? '') === 'ochirish' && is_file($LOCK_FILE)) {
        @unlink(__FILE__);
        @unlink($IP_LOCK_FILE);
        $sayt_url = '';
        if (is_file($LOCAL_CONFIG)) {
            $tana = @file_get_contents($LOCAL_CONFIG);
            if (preg_match("/SAYT_URL.*?'([^']+)'/", $tana, $m)) $sayt_url = $m[1];
        }
        header('Location: ' . ($sayt_url ?: '') . '/');
        exit;
    }

    if ($bosqich === 2) {
        $kengaytmalar = tekshir_kengaytmalar();
        $yozish       = tekshir_yozish();
        if (in_array(false, $kengaytmalar, true) || in_array(false, $yozish, true)) {
            $xato = 'Avval barcha kerakli shartlarni bajaring.';
        } elseif (version_compare(PHP_VERSION, $KEREKLI_PHP, '<')) {
            $xato = 'PHP ' . $KEREKLI_PHP . '+ kerak (joriy: ' . PHP_VERSION . ')';
        } else {
            $bosqich = 2;
        }
    }

    if ($bosqich === 3) {
        $db_host = trim($_POST['db_host'] ?? 'localhost');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = (string) ($_POST['db_pass'] ?? '');

        if (!$db_host || !$db_name || !$db_user) {
            $xato = 'DB ma\'lumotlarini to\'liq kiriting.';
        } else {
            $test = db_test($db_host, $db_name, $db_user, $db_pass);
            if (!$test['ok']) {
                $xato = 'DB ulanish xatosi: ' . $test['xato'];
            } else {
                $_SESSION['install'] = [
                    'db_host' => $db_host,
                    'db_name' => $db_name,
                    'db_user' => $db_user,
                    'db_pass' => $db_pass,
                ];
                $bosqich = 3;
            }
        }
    }

    if ($bosqich === 4) {
        if (empty($_SESSION['install'])) {
            $xato = 'Sessiya tugagan, qaytadan boshlang.';
            $bosqich = 1;
        } else {
            $admin_ism      = trim($_POST['admin_ism'] ?? '');
            $admin_familiya = trim($_POST['admin_familiya'] ?? '');
            $admin_telefon  = preg_replace('/\D+/', '', $_POST['admin_telefon'] ?? '');
            $admin_parol    = (string) ($_POST['admin_parol'] ?? '');

            if (strlen($admin_telefon) === 9 && preg_match('/^9[0-9]{8}$/', $admin_telefon)) {
                $admin_telefon = '998' . $admin_telefon;
            }
            if (strlen($admin_telefon) === 12 && preg_match('/^998[0-9]{9}$/', $admin_telefon)) {
                $admin_telefon = '+' . $admin_telefon;
            } else {
                $admin_telefon = '';
            }

            $parol_murakkab = strlen($admin_parol) >= 8
                && preg_match('/[A-Za-z]/', $admin_parol)
                && preg_match('/[0-9]/', $admin_parol);

            if (!$admin_ism || !$admin_telefon) {
                $xato = 'Ism va telefon majburiy.';
            } elseif (!$parol_murakkab) {
                $xato = 'Parol kamida 8 belgi, harf va raqamdan iborat bo\'lishi kerak.';
            } else {
                $_SESSION['install']['admin_ism']      = $admin_ism;
                $_SESSION['install']['admin_familiya'] = $admin_familiya;
                $_SESSION['install']['admin_telefon']  = $admin_telefon;
                $_SESSION['install']['admin_parol']    = $admin_parol;
                $bosqich = 4;
            }
        }
    }

    if ($bosqich === 5) {
        if (empty($_SESSION['install']['admin_telefon'])) {
            $xato = 'Sessiya tugagan, qaytadan boshlang.';
            $bosqich = 1;
        } else {
            $sayt_nomi  = trim($_POST['sayt_nomi'] ?? 'VatanParvar Yaypan');
            $aloqa_tel  = trim($_POST['aloqa_telefon'] ?? '');
            $aloqa_eml  = trim($_POST['aloqa_email'] ?? '');
            $bot_token  = trim($_POST['bot_token'] ?? '');
            $bot_user   = trim($_POST['bot_username'] ?? '');
            $bot_admin  = trim($_POST['bot_admin_id'] ?? '');
            $tg_kanal   = trim($_POST['telegram_kanal'] ?? '');

            $sd = $_SESSION['install'];
            $sayt_url = isset($_POST['sayt_url'])
                ? rtrim(trim($_POST['sayt_url']), '/')
                : 'https://vatanparvaryaypan.uz';

            $konfig_ok = konfig_yarat([
                'rejim'    => 'production',
                'sayt_url' => $sayt_url,
                'db_host'  => $sd['db_host'],
                'db_name'  => $sd['db_name'],
                'db_user'  => $sd['db_user'],
                'db_pass'  => $sd['db_pass'],
            ]);

            if (!$konfig_ok) {
                $xato = 'config.local.php yarata olmadi. config/ papkasiga yozish ruxsatini tekshiring.';
            } else {
                require_once $LOCAL_CONFIG;

                try {
                    $pdo = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER, DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                } catch (PDOException $e) {
                    @unlink($LOCAL_CONFIG);
                    $xato = 'DB ulanish: ' . $e->getMessage();
                }

                if (!$xato) {
                    $sxema = schema_ishga_tushir($pdo, $SCHEMA_FILE);
                    if (!$sxema['ok']) {
                        $xato = 'Schema xatosi: ' . $sxema['xato'];
                    }
                }

                if (!$xato) {
                    $migrations_dir = $ROOT . '/../database/migrations';
                    if (!is_dir($migrations_dir)) $migrations_dir = $ROOT . '/database/migrations';
                    $mig = migrations_ishga_tushir($pdo, $migrations_dir);
                    if (!$mig['ok']) {
                        $xato = 'Migration xatosi: ' . $mig['xato'];
                    }
                }

                if (!$xato) {
                    $hash = password_hash($sd['admin_parol'], PASSWORD_BCRYPT);
                    $referal_kod = strtoupper(bin2hex(random_bytes(4)));

                    $bor_admin = $pdo->prepare("SELECT id FROM foydalanuvchilar WHERE telefon = ?");
                    $bor_admin->execute([$sd['admin_telefon']]);
                    $admin_id = $bor_admin->fetchColumn();

                    if ($admin_id) {
                        $st = $pdo->prepare(
                            "UPDATE foydalanuvchilar
                             SET ism = ?, familiya = ?, parol_hash = ?, rol = 'developer', holat = 'faol'
                             WHERE id = ?"
                        );
                        $st->execute([$sd['admin_ism'], $sd['admin_familiya'], $hash, $admin_id]);
                    } else {
                        $pdo->exec("DELETE FROM foydalanuvchilar WHERE telefon = '+998900000000'");
                        $st = $pdo->prepare(
                            "INSERT INTO foydalanuvchilar
                             (ism, familiya, telefon, parol_hash, rol, referal_kod, holat)
                             VALUES (?, ?, ?, ?, 'developer', ?, 'faol')"
                        );
                        $st->execute([
                            $sd['admin_ism'], $sd['admin_familiya'], $sd['admin_telefon'],
                            $hash, $referal_kod,
                        ]);
                    }

                    $sozlamalar = [
                        'sayt_nomi'             => $sayt_nomi,
                        'aloqa_telefon'         => $aloqa_tel,
                        'aloqa_email'           => $aloqa_eml,
                        'telegram_kanal'        => $tg_kanal,
                        'telegram_bot_token'    => $bot_token,
                        'telegram_bot_username' => $bot_user,
                        'telegram_admin_id'     => $bot_admin,
                        'cron_kalit'            => bin2hex(random_bytes(16)),
                    ];
                    $sst = $pdo->prepare(
                        "INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)"
                    );
                    foreach ($sozlamalar as $k => $v) {
                        if ($v !== '') $sst->execute([$k, $v]);
                    }

                    file_put_contents($LOCK_FILE,
                        "O'rnatildi: " . date('Y-m-d H:i:s') . "\n" .
                        "Sayt: {$sayt_url}\n" .
                        "IP: {$ip_hozirgi}\n"
                    );
                    @chmod($LOCK_FILE, 0600);

                    foreach (['kesh', 'uploads/avatars', 'uploads/savollar', 'zaxira_nusxalari'] as $p) {
                        @mkdir($ROOT . '/' . $p, 0755, true);
                    }

                    @unlink($IP_LOCK_FILE);
                    unset($_SESSION['install']);
                    $bosqich = 5;
                    $muvaffaqiyat = 'O\'rnatish muvaffaqiyatli yakunlandi!';
                }
            }
        }
    }
}

$kengaytmalar = tekshir_kengaytmalar();
$yozish_holati = tekshir_yozish();
$php_ok = version_compare(PHP_VERSION, $KEREKLI_PHP, '>=');
$tayyor = $php_ok && !in_array(false, $kengaytmalar, true) && !in_array(false, $yozish_holati, true);

$session = $_SESSION['install'] ?? [];

function e_inst($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$css_yol = $ROOT . '/assets/css/style.css';
$style_lokal = is_file($css_yol);
?>
<!DOCTYPE html>
<html lang="uz" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B1024">
    <meta name="robots" content="noindex, nofollow">
    <title>O'rnatish — VatanParvar Yaypan</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo-mark.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&display=swap" rel="stylesheet">
    <?php if ($style_lokal): ?>
        <link rel="stylesheet" href="/assets/css/style.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    <style>
        body { background: #070B14; color: #F1F5F9; font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; letter-spacing: -0.02em; }
        .grad-text {
            background: linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .grad-bg { background: linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%); }
        .glass {
            background: rgba(255,255,255,0.04); backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.10); border-radius: 1.25rem;
        }
        .ring-grad {
            background: linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%);
            padding: 1px; border-radius: 1.25rem;
        }
        .ring-grad > div { background: #0F1626; border-radius: calc(1.25rem - 1px); }
        .field {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.10);
            border-radius: .85rem; padding: .85rem 1rem; color: white; width: 100%;
        }
        .field:focus {
            outline: none; border-color: #8B5CF6;
            background: rgba(139,92,246,0.08);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.20);
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            font-weight: 600; padding: .85rem 1.75rem; border-radius: .9rem; cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #06B6D4, #8B5CF6, #EC4899); color: white;
            box-shadow: 0 6px 20px -8px rgba(139,92,246,.6);
        }
        .btn-ghost { background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.12); }
        .step-bar { display: flex; gap: 8px; }
        .step { flex: 1; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.08); }
        .step.active { background: linear-gradient(90deg, #06B6D4, #8B5CF6, #EC4899); }
        .step.done { background: #10B981; }
    </style>
</head>
<body>

<main class="max-w-3xl mx-auto px-4 py-10">

    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-3 mb-3">
            <div class="w-14 h-14 grad-bg rounded-2xl flex items-center justify-center text-3xl font-extrabold">VP</div>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-2">
            VatanParvar <span class="grad-text">Yaypan</span>
        </h1>
        <p class="text-slate-400">Avtomatik o'rnatuvchi · IP: <code class="text-slate-500"><?= e_inst($ip_hozirgi) ?></code></p>
    </div>

    <?php if ($bosqich !== 99): ?>
        <div class="step-bar mb-6">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="step <?= $i < $bosqich ? 'done' : ($i === $bosqich ? 'active' : '') ?>"></div>
            <?php endfor; ?>
        </div>
        <p class="text-center text-sm text-slate-400 mb-6">
            Bosqich <strong class="text-white"><?= $bosqich ?></strong> / 5
        </p>
    <?php endif; ?>

    <?php if ($xato): ?>
        <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <strong>Xato:</strong> <?= e_inst($xato) ?>
        </div>
    <?php endif; ?>

    <?php if ($muvaffaqiyat && $bosqich === 5): ?>
        <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            ✓ <?= e_inst($muvaffaqiyat) ?>
        </div>
    <?php endif; ?>

    <?php if ($bosqich === 99): ?>

        <div class="ring-grad">
            <div class="p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-amber-500/15 text-amber-300 flex items-center justify-center text-4xl">⚠️</div>
                <h2 class="text-2xl font-bold mb-2">O'rnatish allaqachon yakunlangan</h2>
                <p class="text-slate-400 mb-6">
                    Sayt ishga tayyor. Xavfsizlik uchun <code class="text-pink-400">install.php</code> faylini darhol o'chiring.
                </p>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="ochirish">
                    <button type="submit" class="btn btn-primary">
                        🗑️ install.php ni o'chirish
                    </button>
                </form>

                <p class="text-xs text-slate-500">
                    Yoki FTP/SSH orqali qo'lda o'chiring va <a href="/" class="grad-text font-bold">saytga qayting</a>.
                </p>
            </div>
        </div>

    <?php elseif ($bosqich === 1): ?>

        <div class="glass p-8">
            <h2 class="text-2xl font-bold mb-2">1. Server tekshiruvi</h2>
            <p class="text-slate-400 mb-6 text-sm">Saytning ishlashi uchun quyidagi shartlar bajarilishi kerak.</p>

            <div class="space-y-2 mb-6">
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                    <span>PHP versiyasi <code class="text-slate-500"><?= PHP_VERSION ?></code></span>
                    <span class="<?= $php_ok ? 'text-emerald-400' : 'text-red-400' ?> font-bold">
                        <?= $php_ok ? '✓' : '✗' ?> <?= $KEREKLI_PHP ?>+
                    </span>
                </div>
                <?php foreach ($kengaytmalar as $nom => $bor): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                        <span>PHP kengaytmasi: <code class="text-slate-500"><?= $nom ?></code></span>
                        <span class="<?= $bor ? 'text-emerald-400' : 'text-red-400' ?> font-bold">
                            <?= $bor ? '✓ Bor' : '✗ Yo\'q' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="text-lg font-bold mb-3">Yozish ruxsati</h3>
            <div class="space-y-2 mb-6">
                <?php foreach ($yozish_holati as $nom => $ok): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                        <span>Papka: <code class="text-slate-500"><?= e_inst($nom) ?></code></span>
                        <span class="<?= $ok ? 'text-emerald-400' : 'text-red-400' ?> font-bold">
                            <?= $ok ? '✓ Yoziladi' : '✗ Ruxsat yo\'q' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$tayyor): ?>
                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm mb-4">
                    Yozish ruxsati yo'q bo'lsa, cPanel File Manager → Permissions → 0755 qo'ying.
                    PHP kengaytmalari yo'q bo'lsa hosting xizmatiga murojaat qiling.
                </div>
            <?php endif; ?>

            <form method="POST" action="?bosqich=2">
                <input type="hidden" name="bosqich" value="2">
                <button type="submit" class="btn btn-primary w-full" <?= !$tayyor ? 'disabled' : '' ?>>
                    Davom etish →
                </button>
            </form>
        </div>

    <?php elseif ($bosqich === 2): ?>

        <div class="glass p-8">
            <h2 class="text-2xl font-bold mb-2">2. Ma'lumotlar bazasi</h2>
            <p class="text-slate-400 mb-6 text-sm">cPanel'da yaratilgan baza ma'lumotlarini kiriting.</p>

            <form method="POST" action="?bosqich=3" class="space-y-4">
                <input type="hidden" name="bosqich" value="3">

                <div>
                    <label class="block text-sm text-slate-400 mb-1">DB host</label>
                    <input name="db_host" required value="<?= e_inst($_POST['db_host'] ?? 'localhost') ?>" class="field">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">DB nomi *</label>
                    <input name="db_name" required value="<?= e_inst($_POST['db_name'] ?? 'wbefkccz_avtomaktab') ?>" class="field">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">DB foydalanuvchi *</label>
                    <input name="db_user" required value="<?= e_inst($_POST['db_user'] ?? 'wbefkccz_avtomaktab') ?>" class="field">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">DB paroli *</label>
                    <input name="db_pass" type="password" required class="field" placeholder="••••••••">
                </div>

                <div class="text-xs text-slate-500 p-3 rounded-xl bg-cyan-500/5 border border-cyan-500/20">
                    💡 <strong>Maslahat:</strong> cPanel → MySQL Databases bo'limidan baza va foydalanuvchi yarating, foydalanuvchini bazaga ALL PRIVILEGES bilan ulang.
                </div>

                <div class="flex gap-3">
                    <a href="?bosqich=1" class="btn btn-ghost flex-1">← Orqaga</a>
                    <button type="submit" class="btn btn-primary flex-1">Tekshirish va davom etish →</button>
                </div>
            </form>
        </div>

    <?php elseif ($bosqich === 3): ?>

        <div class="glass p-8">
            <h2 class="text-2xl font-bold mb-2">3. Admin akkaunt</h2>
            <p class="text-slate-400 mb-6 text-sm">Bosh dasturchi (developer) akkauntini yaratish.</p>

            <form method="POST" action="?bosqich=4" class="space-y-4">
                <input type="hidden" name="bosqich" value="4">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Ism *</label>
                        <input name="admin_ism" required value="<?= e_inst($_POST['admin_ism'] ?? '') ?>" class="field">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Familiya</label>
                        <input name="admin_familiya" value="<?= e_inst($_POST['admin_familiya'] ?? '') ?>" class="field">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Telefon * (+998 90 XXX XX XX)</label>
                    <input name="admin_telefon" type="tel" required value="<?= e_inst($_POST['admin_telefon'] ?? '') ?>" placeholder="+998901234567" class="field">
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Parol * (kamida 8 belgi, harf+raqam)</label>
                    <input name="admin_parol" type="password" required minlength="8" class="field" placeholder="••••••••">
                </div>

                <div class="text-xs text-slate-500 p-3 rounded-xl bg-pink-500/5 border border-pink-500/20">
                    🔒 Bu akkaunt <strong class="text-pink-400">developer</strong> roliga ega bo'ladi va to'liq boshqaruv huquqi beradi. Parolni saqlab qo'ying.
                </div>

                <div class="flex gap-3">
                    <a href="?bosqich=2" class="btn btn-ghost flex-1">← Orqaga</a>
                    <button type="submit" class="btn btn-primary flex-1">Davom etish →</button>
                </div>
            </form>
        </div>

    <?php elseif ($bosqich === 4): ?>

        <div class="glass p-8">
            <h2 class="text-2xl font-bold mb-2">4. Sayt sozlamalari</h2>
            <p class="text-slate-400 mb-6 text-sm">Asosiy sayt ma'lumotlari (keyin Admin panel orqali ham o'zgartirsa bo'ladi).</p>

            <form method="POST" action="?bosqich=5" class="space-y-4">
                <input type="hidden" name="bosqich" value="5">

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Sayt URL *</label>
                    <input name="sayt_url" required class="field" value="<?= e_inst($_POST['sayt_url'] ?? 'https://vatanparvaryaypan.uz') ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Sayt nomi</label>
                    <input name="sayt_nomi" class="field" value="<?= e_inst($_POST['sayt_nomi'] ?? 'VatanParvar Yaypan') ?>">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Aloqa telefoni</label>
                        <input name="aloqa_telefon" class="field" value="<?= e_inst($_POST['aloqa_telefon'] ?? '+998 90 123 45 67') ?>">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Aloqa email</label>
                        <input name="aloqa_email" type="email" class="field" value="<?= e_inst($_POST['aloqa_email'] ?? 'info@vatanparvaryaypan.uz') ?>">
                    </div>
                </div>

                <details>
                    <summary class="cursor-pointer text-slate-400 hover:text-white text-sm py-2">
                        Telegram (ixtiyoriy, keyin ham qo'shish mumkin)
                    </summary>
                    <div class="space-y-3 mt-3">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Telegram kanal URL</label>
                            <input name="telegram_kanal" class="field" placeholder="https://t.me/vatanparvaryaypan" value="<?= e_inst($_POST['telegram_kanal'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Bot tokeni (BotFather'dan)</label>
                            <input name="bot_token" class="field" placeholder="1234567890:AAH..." value="<?= e_inst($_POST['bot_token'] ?? '') ?>">
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Bot username</label>
                                <input name="bot_username" class="field" placeholder="vatanparvaryaypan_bot" value="<?= e_inst($_POST['bot_username'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Admin Telegram ID</label>
                                <input name="bot_admin_id" class="field" placeholder="123456789" value="<?= e_inst($_POST['bot_admin_id'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </details>

                <div class="flex gap-3">
                    <a href="?bosqich=3" class="btn btn-ghost flex-1">← Orqaga</a>
                    <button type="submit" class="btn btn-primary flex-1">O'rnatishni yakunlash ✓</button>
                </div>
            </form>
        </div>

    <?php elseif ($bosqich === 5): ?>

        <div class="ring-grad">
            <div class="p-10 text-center">
                <div class="w-24 h-24 mx-auto mb-5 rounded-3xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center text-5xl">✓</div>
                <h2 class="text-3xl font-extrabold mb-2 grad-text">Tabriklayman!</h2>
                <p class="text-slate-300 mb-8">VatanParvar Yaypan muvaffaqiyatli o'rnatildi va ishlashga tayyor.</p>

                <div class="text-left space-y-3 mb-8 max-w-md mx-auto">
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-sm">
                        ✓ Ma'lumotlar bazasi yaratildi va to'ldirildi
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-sm">
                        ✓ Migratsiyalar bajarildi
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-sm">
                        ✓ Developer akkaunt yaratildi
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-sm">
                        ✓ <code class="text-slate-400">config.local.php</code> yaratildi
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-sm">
                        ✓ <code class="text-slate-400">installed.lock</code> qo'yildi
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm mb-6 text-left">
                    <strong>⚠️ MUHIM:</strong> Xavfsizlik uchun <code>install.php</code> faylini darhol o'chiring.
                </div>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="ochirish">
                    <button type="submit" class="btn btn-primary">
                        🗑️ install.php ni o'chirish va saytga o'tish
                    </button>
                </form>

                <p class="text-xs text-slate-500 mt-4">
                    Yoki <a href="/" class="grad-text font-bold">saytni ochish</a>
                    · <a href="/admin/" class="grad-text font-bold">Admin panel</a>
                </p>
            </div>
        </div>

    <?php endif; ?>

    <p class="text-center text-xs text-slate-600 mt-8">
        © <?= date('Y') ?> VatanParvar Yaypan · O'rnatuvchi v2.0 · IP-lock himoyasi yoqilgan
    </p>

</main>

</body>
</html>
