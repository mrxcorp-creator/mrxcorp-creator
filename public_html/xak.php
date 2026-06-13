<?php
/**
 * VatanParvar Yaypan — Xavfsizlik va Monitoring paneli
 * ================================================================
 *  URL:  /xak.php
 *
 *  Kim kira oladi:
 *   - "developer" yoki "admin" rolidagi foydalanuvchi
 *   - VA install.php tomonidan o'rnatilgan `xak_parol`ni bilishi shart
 *
 *  Tab'lar:
 *   1) Umumiy        — server, PHP, DB, disk, statistik
 *   2) Loglar        — xato / kirish / xavfsizlik / info loglari
 *   3) Hujum urinishlari
 *   4) Bloklangan IP'lar (qo'lda blok / yechish)
 *   5) Yuklama       — grafiklar
 *   6) Fayl integriteti
 *
 *  Bu sahifa indekslanmaydi (X-Robots-Tag) va NEVER kashlanmaydi.
 */

require_once __DIR__ . '/config/auth.php';

// ---------- Avtorizatsiya ----------
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$f = kirgan_bolish_kerak();
if (!in_array($f['rol'], ['developer', 'admin'], true)) {
    http_response_code(403);
    exit('Bu sahifaga ruxsat yo\'q.');
}

// xak_parol — bir martalik tekshiruv (sessiyada saqlanadi 1 soat)
$_SESSION['xak_kirgan'] = $_SESSION['xak_kirgan'] ?? 0;
$xak_kirgan_yaroqli = $_SESSION['xak_kirgan'] > time() - 3600;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xak_parol'])) {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $hash = (string) sozlama('xak_parol', '');
        if ($hash && password_verify($_POST['xak_parol'], $hash)) {
            $_SESSION['xak_kirgan'] = time();
            log_xavfsizlik('XAK_KIRDI', ['user_id' => $f['id']]);
            yonaltir(SAYT_URL . '/xak.php');
        } else {
            log_xavfsizlik('XAK_PAROL_XATO', ['user_id' => $f['id']]);
            $xato = 'Parol noto\'g\'ri.';
        }
    }
}

if (!$xak_kirgan_yaroqli) {
    // Parol so'rash sahifasi
    ?><!DOCTYPE html><html lang="uz"><head><?php xak_head_html('Kirish') ?></head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center px-4">
        <form method="POST" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-8 space-y-4">
            <div class="text-center">
                <div class="text-5xl mb-3">🔐</div>
                <h1 class="text-xl font-bold">Monitoring paneli</h1>
                <p class="text-slate-500 text-sm mt-1">Xavfsizlik uchun parol kerak</p>
            </div>
            <?php if (!empty($xato)): ?>
                <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>
            <?= csrf_input() ?>
            <input type="password" name="xak_parol" required autofocus
                   placeholder="xak.php paroli"
                   class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 focus:border-blue-500 focus:outline-none">
            <button type="submit" class="w-full px-4 py-3 rounded-xl bg-blue-500 hover:bg-blue-400 font-semibold">
                Kirish
            </button>
            <p class="text-xs text-slate-500 text-center">
                Foydalanuvchi: <?= e($f['ism']) ?> · Rol: <?= e($f['rol']) ?>
            </p>
        </form>
    </body></html><?php
    exit;
}

// ---------- POST amallar (blok yech, qo'lda blok, log tozalash) ----------
$muvaffaqiyat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_tekshir(post('csrf_token'))) {
    if (!empty($_POST['amal']) && $f['rol'] === 'developer') {
        $amal = $_POST['amal'];
        if ($amal === 'blokni_yech' && !empty($_POST['ip'])) {
            ip_blokni_yech($_POST['ip']);
            log_xavfsizlik('IP_BLOK_YECHILDI', ['ip' => $_POST['ip'], 'admin' => $f['id']]);
            $muvaffaqiyat = "IP {$_POST['ip']} blokdan ozod qilindi.";
        } elseif ($amal === 'qo_lda_blok' && !empty($_POST['ip'])) {
            ip_blokla(
                trim($_POST['ip']),
                'Qo\'lda bloklandi (' . $f['ism'] . ')',
                (int) ($_POST['daqiqa'] ?? 1440)
            );
            log_xavfsizlik('IP_QOLDA_BLOK', ['ip' => $_POST['ip'], 'admin' => $f['id']]);
            $muvaffaqiyat = "IP {$_POST['ip']} bloklandi.";
        } elseif ($amal === 'log_tozala') {
            $kun = (int) ($_POST['kun'] ?? 30);
            $n = log_tozalash($kun);
            $muvaffaqiyat = "$kun kundan eski $n ta log fayli o'chirildi.";
        }
    }
}

$tab = $_GET['tab'] ?? 'umumiy';

// ---------- Ma'lumot yig'ish ----------
$umumiy = xak_umumiy_malumot();
$server_stat = xak_server_stat();

?><!DOCTYPE html>
<html lang="uz">
<head><?php xak_head_html('Monitoring') ?></head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-20">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🛡️</span>
            <div>
                <h1 class="font-bold leading-tight">VatanParvar — Monitoring</h1>
                <p class="text-xs text-slate-500"><?= e($f['ism']) ?> (<?= e($f['rol']) ?>) · <?= date('d.m.Y H:i') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/" class="text-xs px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700">Admin panel</a>
            <a href="/" class="text-xs px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700">Sayt</a>
            <a href="/logout" class="text-xs px-3 py-2 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300">Chiqish</a>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 pb-3">
        <nav class="flex flex-wrap gap-1 text-sm">
            <?php foreach ([
                'umumiy'    => '📊 Umumiy',
                'loglar'    => '📜 Loglar',
                'hujumlar'  => '⚠️ Hujumlar',
                'bloklar'   => '🚫 Bloklangan IP',
                'yuklama'   => '📈 Yuklama',
                'fayllar'   => '🗂️ Fayllar',
                'sozlamalar'=> '⚙️ Sozlamalar',
            ] as $kalit => $nom):
                $faol = $tab === $kalit; ?>
                <a href="?tab=<?= $kalit ?>"
                   class="px-3 py-2 rounded-lg <?= $faol ? 'bg-blue-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' ?>">
                   <?= $nom ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">

<?php if ($muvaffaqiyat): ?>
    <div class="mb-5 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
        ✓ <?= e($muvaffaqiyat) ?>
    </div>
<?php endif; ?>

<?php
switch ($tab) {
    case 'loglar':     xak_tab_loglar();     break;
    case 'hujumlar':   xak_tab_hujumlar();   break;
    case 'bloklar':    xak_tab_bloklar();    break;
    case 'yuklama':    xak_tab_yuklama();    break;
    case 'fayllar':    xak_tab_fayllar();    break;
    case 'sozlamalar': xak_tab_sozlamalar(); break;
    default:           xak_tab_umumiy($umumiy, $server_stat);
}
?>

</main>
</body>
</html>
<?php exit;

// ================================================================
//                       TAB-LAR (UI)
// ================================================================

function xak_tab_umumiy(array $u, array $s): void { ?>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?= xak_kart('Foydalanuvchilar', $u['foydalanuvchi_son'], '👥', 'blue') ?>
    <?= xak_kart('Faol obunalar',   $u['faol_obuna_son'],    '✅', 'emerald') ?>
    <?= xak_kart('Bugungi to\'lov', pul((float)$u['bugun_tolov']), '💰', 'amber') ?>
    <?= xak_kart('Bloklangan IP',   $u['bloklangan_ip_son'],   '🚫', 'red') ?>
    <?= xak_kart('Bugun xato',      $u['bugun_xato'],        '🐞', 'rose') ?>
    <?= xak_kart('Hujum urinishi (24s)', $u['hujum_24'],     '⚠️', 'orange') ?>
    <?= xak_kart('Disk band',       $s['disk'],              '💾', 'purple') ?>
    <?= xak_kart('PHP Memory',      $s['memory_use'],        '🧠', 'cyan') ?>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">🖥️ Server holati</h3>
        <dl class="text-sm space-y-2">
            <?php foreach ($s['malumot'] as $k => $v): ?>
                <div class="flex justify-between gap-3 border-b border-slate-800/50 py-1">
                    <dt class="text-slate-400"><?= e($k) ?></dt>
                    <dd class="font-mono text-slate-200 text-right"><?= e((string)$v) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">🛡️ Xavfsizlik holati</h3>
        <ul class="text-sm space-y-2">
            <?php foreach (xak_xavfsizlik_holati() as $h): ?>
                <li class="flex items-start gap-2 py-1 border-b border-slate-800/50">
                    <span class="<?= $h['ok'] ? 'text-emerald-400' : 'text-red-400' ?>">
                        <?= $h['ok'] ? '✓' : '✗' ?>
                    </span>
                    <div>
                        <div class="text-slate-200"><?= e($h['nom']) ?></div>
                        <?php if (!$h['ok']): ?>
                            <div class="text-xs text-red-300/80"><?= e($h['izoh']) ?></div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 mt-4">
    <h3 class="font-semibold mb-3">📜 Oxirgi 10 ta xavfsizlik hodisasi</h3>
    <?php $oxirgilar = log_oq('xavfsizlik', '', 10); ?>
    <?php if (!$oxirgilar): ?>
        <p class="text-slate-500 text-sm">Hech qanday hodisa yo'q.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-slate-400 border-b border-slate-800">
                    <tr><th class="text-left py-2">Vaqt</th><th class="text-left py-2">Tur</th>
                        <th class="text-left py-2">IP</th><th class="text-left py-2">URL</th></tr>
                </thead>
                <tbody>
                <?php foreach ($oxirgilar as $h): ?>
                    <tr class="border-b border-slate-800/30">
                        <td class="py-2 font-mono text-xs text-slate-400"><?= e($h['vaqt'] ?? '') ?></td>
                        <td class="py-2"><span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-xs"><?= e($h['tur'] ?? '') ?></span></td>
                        <td class="py-2 font-mono text-xs"><?= e($h['ip'] ?? '') ?></td>
                        <td class="py-2 truncate max-w-[300px] text-slate-400 text-xs"><?= e(mb_substr($h['url'] ?? '', 0, 60)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php }

function xak_tab_loglar(): void {
    $kategoriya = $_GET['kat'] ?? 'xato';
    $sana       = $_GET['sana'] ?? date('Y-m-d');
    $izlash     = trim($_GET['q'] ?? '');
    $loglar = log_oq($kategoriya, $sana, 1000);
    if ($izlash) {
        $loglar = array_filter($loglar, fn($r) => stripos(json_encode($r, JSON_UNESCAPED_UNICODE), $izlash) !== false);
    }
    ?>
<form method="GET" class="flex flex-wrap gap-2 mb-5 items-center">
    <input type="hidden" name="tab" value="loglar">
    <select name="kat" class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
        <?php foreach (['xato', 'kirish', 'xavfsizlik', 'sorov', 'info'] as $k): ?>
            <option value="<?= $k ?>" <?= $kategoriya === $k ? 'selected' : '' ?>>
                <?= ucfirst($k) ?> (<?= count(log_oq($k, $sana, 100000)) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="sana" value="<?= e($sana) ?>"
           class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
    <input type="text" name="q" value="<?= e($izlash) ?>" placeholder="🔎 Izlash..."
           class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm flex-1 min-w-[160px]">
    <button class="px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-400 text-sm font-medium">Ko'rsat</button>
</form>

<div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
    <?php if (!$loglar): ?>
        <p class="p-6 text-center text-slate-500">Bu kategoriyada loglar yo'q.</p>
    <?php else: ?>
    <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-800 text-slate-400 sticky top-0">
                <tr>
                    <th class="text-left py-2 px-3">Vaqt</th>
                    <th class="text-left py-2 px-3">IP</th>
                    <th class="text-left py-2 px-3">Manba/Tur</th>
                    <th class="text-left py-2 px-3">Xabar</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($loglar as $l):
                $key   = $l['matn'] ?? $l['tur'] ?? $l['izoh'] ?? '';
                $manba = $l['manba'] ?? $l['tur'] ?? '';
                ?>
                <tr class="border-b border-slate-800/40 hover:bg-slate-800/30 align-top">
                    <td class="py-1.5 px-3 font-mono text-slate-400 whitespace-nowrap"><?= e(substr($l['vaqt'] ?? '', 11, 8)) ?></td>
                    <td class="py-1.5 px-3 font-mono text-slate-400"><?= e($l['ip'] ?? '') ?></td>
                    <td class="py-1.5 px-3 text-slate-300"><?= e($manba) ?></td>
                    <td class="py-1.5 px-3 text-slate-200">
                        <details>
                            <summary class="cursor-pointer"><?= e(mb_substr($key, 0, 100)) ?></summary>
                            <pre class="mt-2 p-2 bg-slate-950 rounded text-xs text-slate-400 overflow-auto"><?= e(json_encode($l, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<form method="POST" class="mt-4 flex items-center gap-2 text-sm" onsubmit="return confirm('Eski log fayllarini o\\'chirib tashlaymizmi?')">
    <?= csrf_input() ?>
    <input type="hidden" name="amal" value="log_tozala">
    <input type="number" name="kun" value="30" min="1" max="365"
           class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 w-24">
    <span class="text-slate-400">kundan eski loglarni tozalash</span>
    <button class="px-4 py-2 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300">🗑 Tozalash</button>
</form>
<?php }

function xak_tab_hujumlar(): void {
    // Kategoriya bo'yicha agregatsiya
    $loglar = log_oq('xavfsizlik', date('Y-m-d'), 5000);
    $turlar = [];
    $iplar  = [];
    foreach ($loglar as $l) {
        $turlar[$l['tur'] ?? '?'] = ($turlar[$l['tur'] ?? '?'] ?? 0) + 1;
        $ip = $l['ip'] ?? '';
        if ($ip) $iplar[$ip] = ($iplar[$ip] ?? 0) + 1;
    }
    arsort($turlar);
    arsort($iplar);
    ?>

<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">⚠️ Bugungi hujum turlari</h3>
        <?php if (!$turlar): ?>
            <p class="text-slate-500 text-sm">Bugun hujum urinishlari qayd qilinmagan ✓</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php $maks = max($turlar); foreach ($turlar as $tur => $son):
                    $foiz = round(($son / $maks) * 100); ?>
                    <li>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-300"><?= e($tur) ?></span>
                            <span class="text-slate-400 font-mono"><?= $son ?></span>
                        </div>
                        <div class="h-2 bg-slate-800 rounded overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-amber-500 to-red-500" style="width:<?= $foiz ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">🌐 Eng faol hujum IP'lari (top 10)</h3>
        <?php if (!$iplar): ?>
            <p class="text-slate-500 text-sm">Yo'q</p>
        <?php else: ?>
            <table class="w-full text-sm">
                <?php foreach (array_slice($iplar, 0, 10, true) as $ip => $son): ?>
                    <tr class="border-b border-slate-800/40">
                        <td class="py-2 font-mono text-slate-300"><?= e($ip) ?></td>
                        <td class="py-2 text-right text-slate-400"><?= $son ?> ta</td>
                        <td class="py-2 text-right">
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="amal" value="qo_lda_blok">
                                <input type="hidden" name="ip" value="<?= e($ip) ?>">
                                <input type="hidden" name="daqiqa" value="1440">
                                <button class="text-xs px-2 py-1 rounded bg-red-500/20 hover:bg-red-500/30 text-red-300">Bloklash</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
    <h3 class="font-semibold mb-3">📜 Bugungi hujum urinishlari (eng oxirgi 100)</h3>
    <?php $oxirgi100 = array_slice($loglar, 0, 100); ?>
    <?php if (!$oxirgi100): ?>
        <p class="text-slate-500 text-sm">Yo'q</p>
    <?php else: ?>
    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-800 text-slate-400 sticky top-0">
                <tr>
                    <th class="text-left py-2 px-3">Vaqt</th>
                    <th class="text-left py-2 px-3">Tur</th>
                    <th class="text-left py-2 px-3">IP</th>
                    <th class="text-left py-2 px-3">URL</th>
                    <th class="text-left py-2 px-3">Tafsilotlar</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($oxirgi100 as $l): ?>
                <tr class="border-b border-slate-800/40 align-top">
                    <td class="py-1.5 px-3 font-mono text-slate-400"><?= e(substr($l['vaqt'] ?? '', 11, 8)) ?></td>
                    <td class="py-1.5 px-3"><span class="px-2 py-0.5 rounded bg-red-500/20 text-red-300"><?= e($l['tur'] ?? '?') ?></span></td>
                    <td class="py-1.5 px-3 font-mono"><?= e($l['ip'] ?? '') ?></td>
                    <td class="py-1.5 px-3 text-slate-400 truncate max-w-[200px]"><?= e(mb_substr($l['url'] ?? '', 0, 60)) ?></td>
                    <td class="py-1.5 px-3">
                        <details><summary class="cursor-pointer text-slate-400">batafsil</summary>
                        <pre class="mt-2 p-2 bg-slate-950 rounded text-slate-400 overflow-auto max-w-md"><?= e(json_encode($l['kontekst'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php }

function xak_tab_bloklar(): void {
    $bloklar = bloklangan_iplar_royxat(500);
    ?>
<div class="grid lg:grid-cols-3 gap-4 mb-6">
    <div class="lg:col-span-1 rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">➕ Qo'lda IP bloklash</h3>
        <form method="POST" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="amal" value="qo_lda_blok">
            <input type="text" name="ip" required placeholder="192.168.1.1"
                   class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm font-mono">
            <select name="daqiqa" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-sm">
                <option value="60">1 soat</option>
                <option value="1440" selected>1 kun</option>
                <option value="10080">1 hafta</option>
                <option value="43200">1 oy</option>
                <option value="0">Abadiy</option>
            </select>
            <button class="w-full px-4 py-2 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300 text-sm font-medium">
                🚫 Bloklash
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <h3 class="font-semibold p-5 pb-3">🚫 Bloklangan IP'lar (<?= count($bloklar) ?>)</h3>
        <?php if (!$bloklar): ?>
            <p class="px-5 pb-5 text-slate-500 text-sm">Bloklangan IP'lar yo'q.</p>
        <?php else: ?>
        <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-slate-400 sticky top-0">
                    <tr>
                        <th class="text-left py-2 px-4">IP</th>
                        <th class="text-left py-2 px-4">Sabab</th>
                        <th class="text-left py-2 px-4">Tugaydi</th>
                        <th class="text-left py-2 px-4">Yaratilgan</th>
                        <th class="py-2 px-4"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bloklar as $b): ?>
                    <tr class="border-b border-slate-800/40">
                        <td class="py-2 px-4 font-mono"><?= e($b['ip']) ?></td>
                        <td class="py-2 px-4 text-slate-300 text-xs"><?= e(mb_substr((string)$b['sabab'], 0, 60)) ?></td>
                        <td class="py-2 px-4 text-xs text-slate-400">
                            <?= $b['tugash'] ? e(date('d.m.Y H:i', strtotime($b['tugash']))) : '<span class="text-red-400">Abadiy</span>' ?>
                        </td>
                        <td class="py-2 px-4 text-xs text-slate-400 font-mono"><?= e(date('d.m H:i', strtotime($b['yaratilgan']))) ?></td>
                        <td class="py-2 px-4 text-right">
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="amal" value="blokni_yech">
                                <input type="hidden" name="ip" value="<?= e($b['ip']) ?>">
                                <button class="text-xs px-2 py-1 rounded bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300">✓ Yech</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php }

function xak_tab_yuklama(): void {
    // Oxirgi 24 soatdagi soatlar bo'yicha kirish urinishlari
    $kirishlar = db_barcha(
        "SELECT DATE_FORMAT(yaratilgan, '%Y-%m-%d %H:00') AS soat,
                SUM(muvaffaqiyat=1) AS yaxshi,
                SUM(muvaffaqiyat=0) AS yomon
         FROM kirish_urinishlar
         WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY soat ORDER BY soat"
    );
    $maks = 1;
    foreach ($kirishlar as $r) {
        $maks = max($maks, (int)$r['yaxshi'] + (int)$r['yomon']);
    }
    ?>
<div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 mb-4">
    <h3 class="font-semibold mb-4">📈 Oxirgi 24 soat kirish urinishlari</h3>
    <?php if (!$kirishlar): ?>
        <p class="text-slate-500 text-sm">Ma'lumot yo'q.</p>
    <?php else: ?>
    <div class="flex items-end gap-1 h-48 border-b border-l border-slate-800 pl-2 pb-2 overflow-x-auto">
        <?php foreach ($kirishlar as $r):
            $jami = (int)$r['yaxshi'] + (int)$r['yomon'];
            $balandlik = round(($jami / $maks) * 100);
            $yomon_b   = $jami > 0 ? round(((int)$r['yomon'] / $jami) * $balandlik) : 0;
            ?>
            <div class="flex flex-col-reverse min-w-[20px]" title="<?= e($r['soat']) ?> · <?= $jami ?>">
                <div class="bg-emerald-500" style="height:<?= $balandlik - $yomon_b ?>%"></div>
                <div class="bg-red-500" style="height:<?= $yomon_b ?>%"></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="flex gap-4 mt-3 text-xs text-slate-400">
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-emerald-500"></span> Muvaffaqiyatli</span>
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-red-500"></span> Xato urinish</span>
    </div>
    <?php endif; ?>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">📊 Log fayllar statistikasi</h3>
        <?php $stat = log_statistika(); ?>
        <table class="w-full text-sm">
            <thead><tr class="text-slate-400 border-b border-slate-800">
                <th class="text-left py-2">Kategoriya</th>
                <th class="text-right py-2">Fayllar</th>
                <th class="text-right py-2">Yozuvlar</th>
                <th class="text-right py-2">O'lcham</th>
            </tr></thead>
            <tbody>
            <?php foreach ($stat as $kat => $s): ?>
                <tr class="border-b border-slate-800/40">
                    <td class="py-2"><?= e(ucfirst($kat)) ?></td>
                    <td class="py-2 text-right text-slate-400"><?= number_format($s['fayllar']) ?></td>
                    <td class="py-2 text-right text-slate-300"><?= number_format($s['satrlar']) ?></td>
                    <td class="py-2 text-right text-slate-400 font-mono"><?= xak_olcham($s['olcham']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5">
        <h3 class="font-semibold mb-3">⏱️ Eng sekin so'rovlar (oxirgi 24s)</h3>
        <?php $sekinlar = xak_sekin_soriqlar(); ?>
        <?php if (!$sekinlar): ?>
            <p class="text-slate-500 text-sm">Sekin so'rovlar qayd qilinmagan.</p>
        <?php else: ?>
        <ul class="text-sm space-y-2">
            <?php foreach (array_slice($sekinlar, 0, 10) as $s): ?>
                <li class="flex justify-between gap-3 border-b border-slate-800/40 py-1">
                    <span class="text-slate-300 truncate"><?= e($s['url'] ?? '') ?></span>
                    <span class="text-amber-400 font-mono whitespace-nowrap"><?= $s['vaqt_ms'] ?>ms</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php }

function xak_tab_fayllar(): void {
    $kuzatilgan = [
        'config/database.php',
        'config/config.php',
        'config/auth.php',
        'includes/security.php',
        'includes/log.php',
        'includes/xato_kuzatuv.php',
        'includes/xujum_himoya.php',
        'includes/funksiyalar.php',
        '.htaccess',
        'index.php',
        'install.php',
        'xak.php',
    ];
    $hash_fayl = LOG_PATH . '/fayl_hashlari.json';
    $eski = is_file($hash_fayl) ? json_decode((string) @file_get_contents($hash_fayl), true) : [];
    $eski = is_array($eski) ? $eski : [];

    $yangi = [];
    $natija = [];
    foreach ($kuzatilgan as $rel) {
        $abs = __DIR__ . '/' . $rel;
        if (!is_file($abs)) {
            $natija[] = ['fayl' => $rel, 'holat' => 'YO\'Q', 'sinf' => 'red'];
            continue;
        }
        $h = md5_file($abs);
        $yangi[$rel] = $h;
        $olcham = filesize($abs);
        if (!isset($eski[$rel])) {
            $natija[] = ['fayl' => $rel, 'holat' => 'YANGI (kuzatilmoqda)', 'sinf' => 'amber',
                'olcham' => $olcham, 'hash' => $h];
        } elseif ($eski[$rel] === $h) {
            $natija[] = ['fayl' => $rel, 'holat' => 'O\'ZGARMAGAN ✓', 'sinf' => 'emerald',
                'olcham' => $olcham, 'hash' => $h];
        } else {
            $natija[] = ['fayl' => $rel, 'holat' => 'O\'ZGARTIRILGAN ⚠', 'sinf' => 'red',
                'olcham' => $olcham, 'hash' => $h, 'eski' => $eski[$rel]];
        }
    }

    // Yangi hashlarni saqlab qo'yamiz (faqat birinchi marta yoki "qabul qilish" bilan)
    if (isset($_POST['fayl_hashlarini_saqla']) && csrf_tekshir(post('csrf_token'))) {
        @file_put_contents($hash_fayl, json_encode($yangi, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        echo '<div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">✓ Yangi hash to\'plami saqlandi.</div>';
        $eski = $yangi;
    }
    ?>
<div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-slate-800">
        <h3 class="font-semibold">🗂️ Muhim fayllar integriteti</h3>
        <form method="POST">
            <?= csrf_input() ?>
            <button name="fayl_hashlarini_saqla" value="1"
                    class="text-xs px-3 py-1.5 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-300"
                    onclick="return confirm('Hozirgi hashlarni etalon sifatida saqlaymizmi?')">
                💾 Etalon yangilash
            </button>
        </form>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-800 text-slate-400">
            <tr>
                <th class="text-left py-2 px-4">Fayl</th>
                <th class="text-left py-2 px-4">Holat</th>
                <th class="text-right py-2 px-4">O'lcham</th>
                <th class="text-left py-2 px-4">Hash</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($natija as $r): ?>
            <tr class="border-b border-slate-800/40">
                <td class="py-2 px-4 font-mono text-slate-300"><?= e($r['fayl']) ?></td>
                <td class="py-2 px-4">
                    <span class="px-2 py-0.5 rounded text-xs bg-<?= $r['sinf'] ?>-500/20 text-<?= $r['sinf'] ?>-300">
                        <?= e($r['holat']) ?>
                    </span>
                </td>
                <td class="py-2 px-4 text-right text-slate-400 font-mono"><?= isset($r['olcham']) ? xak_olcham($r['olcham']) : '—' ?></td>
                <td class="py-2 px-4 font-mono text-xs text-slate-500"><?= e(substr($r['hash'] ?? '', 0, 16)) ?>…</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="p-4 text-xs text-slate-500 border-t border-slate-800">
        Ushbu fayllar buzg'unchilik bo'lganini aniqlash uchun kuzatib turiladi.
        Birinchi marta "Etalon yangilash" tugmasini bosing.
    </p>
</div>
<?php }

function xak_tab_sozlamalar(): void {
    $maxfiy = ['xak_parol', 'click_secret', 'payme_key', 'telegram_bot_token'];
    $hammasi = db_barcha('SELECT * FROM sozlamalar ORDER BY kalit');
    ?>
<div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
    <h3 class="font-semibold p-5 pb-3">⚙️ Sayt sozlamalari (<?= count($hammasi) ?>)</h3>
    <p class="px-5 pb-3 text-xs text-slate-500">Faqat o'qish uchun. O'zgartirish uchun admin paneldan foydalaning.</p>
    <table class="w-full text-sm">
        <thead class="bg-slate-800 text-slate-400">
            <tr>
                <th class="text-left py-2 px-4">Kalit</th>
                <th class="text-left py-2 px-4">Qiymat</th>
                <th class="text-left py-2 px-4">Tavsif</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($hammasi as $s):
            $maxfiymi = in_array($s['kalit'], $maxfiy, true);
            $val = $s['qiymat'] ?? '';
            $korin = $maxfiymi
                ? ($val ? '••••• (yashirin)' : '<em class="text-slate-500">bo\'sh</em>')
                : ($val !== '' ? mb_substr($val, 0, 80) . (mb_strlen($val) > 80 ? '…' : '') : '<em class="text-slate-500">bo\'sh</em>');
            ?>
            <tr class="border-b border-slate-800/40">
                <td class="py-2 px-4 font-mono text-blue-400"><?= e($s['kalit']) ?></td>
                <td class="py-2 px-4 text-slate-300"><?= $korin ?></td>
                <td class="py-2 px-4 text-slate-500 text-xs"><?= e($s['tavsif'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php }

// ================================================================
//                   YORDAMCHI FUNKSIYALAR
// ================================================================

function xak_kart(string $nom, $qiymat, string $emoji, string $rang): string {
    return '<div class="rounded-xl bg-' . $rang . '-500/10 border border-' . $rang . '-500/30 p-4">
                <div class="flex items-start justify-between">
                    <div class="text-2xl">' . $emoji . '</div>
                </div>
                <div class="mt-2 text-2xl font-bold text-' . $rang . '-300">' .
                    htmlspecialchars((string)$qiymat, ENT_QUOTES) . '</div>
                <div class="text-xs text-slate-400">' . htmlspecialchars($nom, ENT_QUOTES) . '</div>
            </div>';
}

function xak_olcham(int $bayt): string {
    foreach (['B','KB','MB','GB','TB'] as $b) {
        if ($bayt < 1024) return round($bayt, 1) . ' ' . $b;
        $bayt /= 1024;
    }
    return round($bayt, 1) . ' PB';
}

function xak_umumiy_malumot(): array {
    return [
        'foydalanuvchi_son' => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar'),
        'faol_obuna_son'    => (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat="faol" AND tugash>NOW()'),
        'bugun_tolov'       => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar
                                                  WHERE holat="muvaffaqiyatli" AND DATE(yaratilgan)=CURDATE()'),
        'bloklangan_ip_son' => (int) db_qiymat('SELECT COUNT(*) FROM bloklangan_iplar
                                                WHERE tugash IS NULL OR tugash > NOW()'),
        'bugun_xato'        => count(log_oq('xato', date('Y-m-d'), 100000)),
        'hujum_24'          => count(log_oq('xavfsizlik', date('Y-m-d'), 100000)),
    ];
}

function xak_server_stat(): array {
    $disk_jami  = @disk_total_space(__DIR__) ?: 0;
    $disk_bosh  = @disk_free_space(__DIR__)  ?: 0;
    $disk_band  = $disk_jami - $disk_bosh;
    $foiz       = $disk_jami > 0 ? round(($disk_band / $disk_jami) * 100) : 0;

    return [
        'memory_use' => xak_olcham(memory_get_usage(true)),
        'disk'       => xak_olcham($disk_band) . ' / ' . xak_olcham($disk_jami) . " ($foiz%)",
        'malumot' => [
            'PHP versiya'         => PHP_VERSION,
            'Server'              => $_SERVER['SERVER_SOFTWARE'] ?? '?',
            'Hostname'            => gethostname(),
            'Vaqt zonasi'         => date_default_timezone_get(),
            'Server vaqti'        => date('Y-m-d H:i:s'),
            'memory_limit'        => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'max_execution_time'  => ini_get('max_execution_time') . ' s',
            'OpenSSL'             => extension_loaded('openssl') ? 'mavjud' : 'YO\'Q',
            'OPcache'             => function_exists('opcache_get_status') && @opcache_get_status() ? 'faol' : 'o\'chiq',
            'HTTPS'               => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'ha' : 'YO\'Q',
            'Loyiha versiyasi'    => '1.0.0',
            'Install lock'        => file_exists(INSTALL_LOCK) ? 'qulflangan ✓' : 'OCHIQ',
        ],
    ];
}

function xak_xavfsizlik_holati(): array {
    $h = [];
    $h[] = ['nom' => 'install.lock fayli mavjud',
            'ok'  => file_exists(INSTALL_LOCK),
            'izoh'=> 'install.php sahifasi qulflangan bo\'lishi kerak'];
    $h[] = ['nom' => 'HTTPS yoqilgan',
            'ok'  => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'izoh'=> 'Sayt HTTPS orqali xizmat qilishi kerak'];
    $h[] = ['nom' => 'config/ ga to\'g\'ridan-to\'g\'ri kirish bloklangan',
            'ok'  => is_file(__DIR__ . '/config/.htaccess'),
            'izoh'=> 'config/.htaccess fayli bo\'lishi kerak'];
    $h[] = ['nom' => 'loglar/ ga to\'g\'ridan-to\'g\'ri kirish bloklangan',
            'ok'  => is_file(__DIR__ . '/loglar/.htaccess'),
            'izoh'=> 'loglar/.htaccess fayli bo\'lishi kerak'];
    $h[] = ['nom' => 'PHP display_errors o\'chirilgan',
            'ok'  => ini_get('display_errors') == '0' || ini_get('display_errors') === '',
            'izoh'=> 'Production\'da xatolar foydalanuvchiga ko\'rsatilmasin'];
    $h[] = ['nom' => 'X-Frame-Options yuborilmoqda',
            'ok'  => in_array('X-Frame-Options: SAMEORIGIN', headers_list(), true)
                  || function_exists('xavfsizlik_sarlavhalar'),
            'izoh'=> 'Clickjacking himoyasi'];
    $h[] = ['nom' => 'Sessiya cookie HttpOnly + SameSite',
            'ok'  => ini_get('session.cookie_httponly') == '1',
            'izoh'=> 'Sessiya o\'g\'irlashga qarshi'];
    $h[] = ['nom' => 'PHP versiyasi 8.0+',
            'ok'  => version_compare(PHP_VERSION, '8.0.0', '>='),
            'izoh'=> 'PHP 8.0 dan eski versiyalar xavfli'];
    return $h;
}

function xak_sekin_soriqlar(): array {
    // Bizda alohida slow-query log yo'q, lekin xato logidan
    // "vaqt_ms" maydoni bo'lganlarini olishga harakat qilamiz
    $loglar = log_oq('info', date('Y-m-d'), 5000);
    $sekin = [];
    foreach ($loglar as $l) {
        if (!empty($l['kontekst']['vaqt_ms']) && $l['kontekst']['vaqt_ms'] > 500) {
            $sekin[] = [
                'url'     => $l['url']     ?? '',
                'vaqt_ms' => (int)$l['kontekst']['vaqt_ms'],
            ];
        }
    }
    usort($sekin, fn($a,$b) => $b['vaqt_ms'] <=> $a['vaqt_ms']);
    return $sekin;
}

function xak_head_html(string $titul): void { ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= htmlspecialchars($titul, ENT_QUOTES) ?> · VatanParvar Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>code,pre{font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
           details>summary{list-style:none}details>summary::-webkit-details-marker{display:none}</style>
<?php }
