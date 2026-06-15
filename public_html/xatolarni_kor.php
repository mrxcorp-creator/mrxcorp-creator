<?php

require_once __DIR__ . '/config/auth.php';
$f = developer_bolish_kerak();

$log_yollari = [
    LOG_PATH . '/php_errors.log',
    BACKUP_PATH . '/php_errors.log',
    __DIR__ . '/error_log',
    __DIR__ . '/../logs/error.log',
    '/home/' . get_current_user() . '/logs/vatanparvaryaypan.uz.error.log',
];

$xatolar = [];
foreach ($log_yollari as $yol) {
    if (is_file($yol) && is_readable($yol)) {
        $hajm = filesize($yol);
        $boshlanish = max(0, $hajm - 50000);
        $fp = fopen($yol, 'r');
        if ($fp) {
            fseek($fp, $boshlanish);
            $matn = fread($fp, 50000);
            fclose($fp);
            $xatolar[$yol] = $matn;
        }
    }
}

$sahifa_sarlavha = 'Xato loglari';
require_once __DIR__ . '/admin/_layout.php';
?>

<div class="ring-grad mb-6">
    <div class="p-5">
        <h2 class="text-xl font-display font-bold mb-2">🐛 PHP xato loglari</h2>
        <p class="text-sm text-muted">Eng so'nggi 50 KB xato yozuvlari ko'rsatiladi.</p>
    </div>
</div>

<?php if (empty($xatolar)): ?>
    <div class="glass p-12 text-center">
        <div class="text-5xl mb-3">✓</div>
        <p class="text-muted">Xato loglari topilmadi yoki bo'sh.</p>
        <p class="text-xs text-muted mt-3">
            Tekshirilgan joylar:<br>
            <?php foreach ($log_yollari as $y): ?>
                <code class="text-xs"><?= e($y) ?></code><br>
            <?php endforeach; ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($xatolar as $yol => $matn): ?>
        <div class="glass p-5 mb-4">
            <div class="flex items-center justify-between mb-3">
                <code class="text-xs text-muted"><?= e($yol) ?></code>
                <span class="text-xs text-muted"><?= number_format(strlen($matn)) ?> belgi</span>
            </div>
            <pre class="text-xs bg-black/30 rounded-lg p-3 overflow-auto max-h-96 whitespace-pre-wrap"><?= e($matn) ?></pre>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="glass p-5 mt-4">
    <h3 class="font-display font-bold mb-3">📊 Server holati</h3>
    <div class="grid sm:grid-cols-2 gap-2 text-sm">
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">PHP versiya</span>
            <span class="font-mono"><?= PHP_VERSION ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">MySQL versiya</span>
            <span class="font-mono"><?= e(db()->query('SELECT VERSION()')->fetchColumn()) ?></span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">DB jadvallari</span>
            <span class="font-mono"><?= count(db()->query('SHOW TABLES')->fetchAll()) ?> ta</span>
        </div>
        <div class="flex justify-between p-2 rounded bg-white/3">
            <span class="text-muted">Bajarilgan migrationlar</span>
            <span class="font-mono">
                <?php try {
                    echo (int) db_qiymat('SELECT COUNT(*) FROM migratsiyalar');
                } catch (Throwable $e) {
                    echo 'jadval yo\'q';
                } ?>
            </span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin/_layout_end.php'; ?>
