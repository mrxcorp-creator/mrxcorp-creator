<?php
require_once __DIR__ . '/config/auth.php';
$f = developer_bolish_kerak();

$KEREKLI_KENGAYTMALAR = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'curl', 'json', 'openssl', 'fileinfo', 'zlib'];
$IXTIYORIY = ['opcache', 'apcu', 'imagick'];

$tekshiruvlar = [];

$tekshiruvlar['php'] = [
    'nom' => 'PHP versiyasi',
    'qiymat' => PHP_VERSION,
    'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'kerakli' => '>= 8.0.0',
];

foreach ($KEREKLI_KENGAYTMALAR as $k) {
    $tekshiruvlar["ext_{$k}"] = [
        'nom' => "Kengaytma: {$k}",
        'qiymat' => extension_loaded($k) ? 'O\'rnatilgan' : 'YO\'Q',
        'ok' => extension_loaded($k),
    ];
}

foreach ($IXTIYORIY as $k) {
    $tekshiruvlar["opt_{$k}"] = [
        'nom' => "Ixtiyoriy: {$k}",
        'qiymat' => extension_loaded($k) ? 'O\'rnatilgan ✓' : 'O\'rnatilmagan',
        'ok' => true,
        'ogoh' => !extension_loaded($k),
    ];
}

$opcache_holati = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
$tekshiruvlar['opcache_yoq'] = [
    'nom' => 'OPcache yoqilgan',
    'qiymat' => $opcache_holati && !empty($opcache_holati['opcache_enabled']) ? 'Yoqilgan ✓' : 'O\'chiq',
    'ok' => $opcache_holati && !empty($opcache_holati['opcache_enabled']),
    'ogoh' => !$opcache_holati || empty($opcache_holati['opcache_enabled']),
];

$papkalar = [
    'kesh'              => CACHE_PATH,
    'uploads'           => UPLOAD_PATH,
    'uploads/avatars'   => UPLOAD_PATH . '/avatars',
    'uploads/savollar'  => UPLOAD_PATH . '/savollar',
    'zaxira_nusxalari'  => BACKUP_PATH,
];
foreach ($papkalar as $nom => $yol) {
    $bor = is_dir($yol);
    $yoziladi = $bor && is_writable($yol);
    $tekshiruvlar["dir_{$nom}"] = [
        'nom' => "Papka: {$nom}",
        'qiymat' => $bor ? ($yoziladi ? 'Yoziladi ✓' : 'Faqat o\'qish') : 'Yo\'q',
        'ok' => $yoziladi,
    ];
}

try {
    $pdo = db();
    $tekshiruvlar['db'] = ['nom' => 'DB ulanish', 'qiymat' => 'OK', 'ok' => true];
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $tekshiruvlar['db_version'] = ['nom' => 'MySQL versiyasi', 'qiymat' => $version, 'ok' => true];

    $jadvallar = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $tekshiruvlar['db_tables'] = [
        'nom' => 'Jadvallar soni',
        'qiymat' => count($jadvallar) . ' ta',
        'ok' => count($jadvallar) >= 12,
    ];
} catch (Exception $e) {
    $tekshiruvlar['db'] = ['nom' => 'DB ulanish', 'qiymat' => 'XATO: ' . $e->getMessage(), 'ok' => false];
}

$tekshiruvlar['installed'] = [
    'nom' => 'O\'rnatuvchi yopilgan',
    'qiymat' => is_file(__DIR__ . '/install.php') ? 'install.php hali bor!' : 'install.php olib tashlangan ✓',
    'ok' => !is_file(__DIR__ . '/install.php'),
    'ogoh' => is_file(__DIR__ . '/install.php'),
];

$tekshiruvlar['lock'] = [
    'nom' => 'O\'rnatish lock fayli',
    'qiymat' => is_file(__DIR__ . '/config/installed.lock') ? 'Mavjud ✓' : 'Yo\'q',
    'ok' => is_file(__DIR__ . '/config/installed.lock'),
];

$sozlamalar_kalitlari = [
    'sayt_nomi', 'aloqa_telefon', 'aloqa_email',
    'telegram_bot_token', 'telegram_admin_id',
    'click_merchant_id', 'click_secret', 'click_service_id',
    'payme_merchant_id', 'payme_key',
    'cron_kalit',
];
foreach ($sozlamalar_kalitlari as $k) {
    $v = sozlama($k);
    $tekshiruvlar["sozlama_{$k}"] = [
        'nom' => "Sozlama: {$k}",
        'qiymat' => $v ? '✓ Belgilangan' : 'Bo\'sh',
        'ok' => true,
        'ogoh' => empty($v),
    ];
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
       || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$tekshiruvlar['https'] = [
    'nom' => 'HTTPS ishlatilmoqda',
    'qiymat' => $https ? 'Ha ✓' : 'Yo\'q (xavfli!)',
    'ok' => $https,
];

$disk_bosh = @disk_free_space(__DIR__);
$tekshiruvlar['disk'] = [
    'nom' => 'Bo\'sh disk maydoni',
    'qiymat' => $disk_bosh ? round($disk_bosh / 1024 / 1024 / 1024, 2) . ' GB' : 'Aniq emas',
    'ok' => !$disk_bosh || $disk_bosh > 100 * 1024 * 1024,
];

$umumiy_ok = !in_array(false, array_column($tekshiruvlar, 'ok'), true);
$ogohlar = array_filter($tekshiruvlar, fn($t) => !empty($t['ogoh']));

$sahifa_sarlavha = 'Tizim diagnostikasi';
$admin_sahifa = '';
require_once __DIR__ . '/admin/_layout.php';
?>

<div class="<?= $umumiy_ok ? 'ring-grad' : 'glass !border-danger/40' ?> mb-6">
    <div class="p-6 flex items-center gap-4">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl flex-shrink-0
                    <?= $umumiy_ok ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' ?>">
            <?= $umumiy_ok ? '✓' : '⚠' ?>
        </div>
        <div>
            <h2 class="text-xl font-display font-bold">
                <?= $umumiy_ok ? 'Hammasi joyida!' : 'Muammolar topildi' ?>
            </h2>
            <p class="text-sm text-muted">
                <?= count(array_filter($tekshiruvlar, fn($t) => $t['ok'])) ?> / <?= count($tekshiruvlar) ?>
                ta tekshiruv muvaffaqiyatli
                <?php if (count($ogohlar) > 0): ?>
                    · <span class="text-amber"><?= count($ogohlar) ?> ta ogohlantirish</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="glass p-6 mb-6">
    <h3 class="font-display font-bold mb-4">⚙️ Server muhiti</h3>
    <div class="space-y-2">
        <?php foreach ($tekshiruvlar as $k => $t):
            if (!str_starts_with($k, 'sozlama_')):
        ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-white/3">
                <span class="text-sm"><?= e($t['nom']) ?></span>
                <span class="text-sm font-mono <?= $t['ok'] ? (!empty($t['ogoh']) ? 'text-amber' : 'text-success') : 'text-danger' ?>">
                    <?= e($t['qiymat']) ?>
                </span>
            </div>
        <?php endif; endforeach; ?>
    </div>
</div>

<div class="glass p-6 mb-6">
    <h3 class="font-display font-bold mb-4">🔧 Sozlamalar to'ldirilganligi</h3>
    <div class="grid sm:grid-cols-2 gap-2">
        <?php foreach ($tekshiruvlar as $k => $t):
            if (str_starts_with($k, 'sozlama_')):
        ?>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/3 text-sm">
                <span class="text-muted truncate"><?= e(str_replace('Sozlama: ', '', $t['nom'])) ?></span>
                <span class="<?= !empty($t['ogoh']) ? 'text-amber' : 'text-success' ?> ml-2 flex-shrink-0">
                    <?= e($t['qiymat']) ?>
                </span>
            </div>
        <?php endif; endforeach; ?>
    </div>
    <p class="text-xs text-muted mt-3">
        Bo'sh qolgan sozlamalarni
        <a href="<?= e(SAYT_URL) ?>/admin/sozlamalar.php" class="grad-text font-bold">Sozlamalar</a>
        bo'limidan to'ldirishingiz kerak.
    </p>
</div>

<div class="glass p-6">
    <h3 class="font-display font-bold mb-4">📋 Server ma'lumotlari</h3>
    <div class="grid sm:grid-cols-2 gap-2 text-sm">
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Server Software</span>
            <span class="font-mono"><?= e($_SERVER['SERVER_SOFTWARE'] ?? '?') ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Document Root</span>
            <span class="font-mono text-xs truncate ml-2" title="<?= e($_SERVER['DOCUMENT_ROOT'] ?? '') ?>"><?= e(basename($_SERVER['DOCUMENT_ROOT'] ?? '')) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Memory Limit</span>
            <span class="font-mono"><?= e(ini_get('memory_limit')) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Max Upload Size</span>
            <span class="font-mono"><?= e(ini_get('upload_max_filesize')) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Post Max Size</span>
            <span class="font-mono"><?= e(ini_get('post_max_size')) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Max Execution Time</span>
            <span class="font-mono"><?= e(ini_get('max_execution_time')) ?>s</span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Timezone</span>
            <span class="font-mono"><?= e(date_default_timezone_get()) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">SAYT_URL</span>
            <span class="font-mono text-xs truncate ml-2"><?= e(SAYT_URL) ?></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin/_layout_end.php'; ?>
