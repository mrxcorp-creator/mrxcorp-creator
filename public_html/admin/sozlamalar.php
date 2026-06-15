<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$ruxsatli_kalitlar = [
    'sayt_nomi', 'sayt_shior', 'aloqa_telefon', 'aloqa_email', 'telegram_kanal',
    'telegram_bot_token', 'telegram_bot_username', 'telegram_admin_id',
    'click_merchant_id', 'click_secret', 'click_service_id',
    'payme_merchant_id', 'payme_key',
    'test_vaqti_minut', 'savol_soni_test', 'referal_bonus', 'cron_kalit',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_form_tekshir('admin_sozlamalar', post('csrf_forma_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/sozlamalar.php');
    }
    $ozgarganlar = [];
    foreach ($ruxsatli_kalitlar as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        $qiymat = (string) $_POST[$k];
        $eski = (string) (sozlama($k) ?? '');
        $maxfiy = str_contains($k, 'token') || str_contains($k, 'secret') || str_contains($k, 'key') || str_contains($k, 'kalit');
        if ($maxfiy && $qiymat === '') {
            continue;
        }
        if ($qiymat !== $eski) {
            sozlama_saqla($k, $qiymat);
            $ozgarganlar[] = $maxfiy ? $k . ' (maxfiy)' : $k;
        }
    }
    foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $f_kesh) @unlink($f_kesh);
    if ($ozgarganlar) {
        audit_yoz('sozlamalar_yangilandi', 'sozlamalar', null, ['ozgarganlar' => $ozgarganlar]);
    }
    flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    yonaltir(SAYT_URL . '/admin/sozlamalar.php');
}

$barcha = db_barcha('SELECT * FROM sozlamalar');
$kesh = [];
foreach ($barcha as $s) $kesh[$s['kalit']] = $s;

$guruhlar = [
    'Sayt'      => [
        'sayt_nomi'             => 'Sayt nomi',
        'sayt_shior'            => 'Sayt shiori',
        'aloqa_telefon'         => 'Aloqa telefoni',
        'aloqa_email'           => 'Aloqa email',
        'telegram_kanal'        => 'Telegram kanal',
    ],
    'Telegram'  => [
        'telegram_bot_token'    => 'Bot tokeni',
        'telegram_bot_username' => 'Bot username',
        'telegram_admin_id'     => 'Admin Telegram ID',
    ],
    "To'lov"    => [
        'click_merchant_id'     => 'Click merchant ID',
        'click_service_id'      => 'Click service ID',
        'click_secret'          => 'Click secret',
        'payme_merchant_id'     => 'Payme merchant ID',
        'payme_key'             => 'Payme key',
    ],
    'Test'      => [
        'test_vaqti_minut'      => 'Test vaqti (daqiqa)',
        'savol_soni_test'       => 'Savol soni',
    ],
    'Boshqa'    => [
        'referal_bonus'         => 'Referal bonus (so\'m)',
        'cron_kalit'            => 'Cron kaliti',
    ],
];

$admin_sahifa = 'sozlamalar';
$sahifa_sarlavha = 'Sozlamalar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="space-y-6">
    <?= csrf_form_input('admin_sozlamalar') ?>

    <?php foreach ($guruhlar as $guruh => $kalitlar): ?>
        <div class="glass p-5 fade-up">
            <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg grad-bg-soft flex items-center justify-center text-sm">⚙️</span>
                <?= e($guruh) ?>
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($kalitlar as $k => $tavsif):
                    $qiymat = $kesh[$k]['qiymat'] ?? '';
                    $maxfiy = str_contains($k, 'token') || str_contains($k, 'secret') || str_contains($k, 'key') || str_contains($k, 'kalit');
                ?>
                    <div>
                        <label class="field-label"><?= e($tavsif) ?> <span class="text-muted text-xs font-mono">(<?= e($k) ?>)</span></label>
                        <?php if ($k === 'sayt_shior'): ?>
                            <textarea name="<?= e($k) ?>" rows="2" class="field"><?= e($qiymat) ?></textarea>
                        <?php elseif ($maxfiy): ?>
                            <input name="<?= e($k) ?>" type="password"
                                   placeholder="<?= $qiymat ? '••••••••' : '' ?>"
                                   class="field" autocomplete="off">
                            <?php if ($qiymat): ?>
                                <p class="text-xs text-muted mt-1">Qiymat o'rnatilgan. O'zgartirmaslik uchun bo'sh qoldiring.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <input name="<?= e($k) ?>" type="text" value="<?= e($qiymat) ?>" class="field" autocomplete="off">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="glass p-5 fade-up">
        <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg grad-bg-soft flex items-center justify-center text-sm">🔗</span>
            Vebhuk URL'lari
        </h2>
        <p class="text-sm text-muted mb-3">To'lov tizimlarini sozlash uchun ushbu URL'larni merchant kabinetiga kiriting.</p>
        <div class="space-y-2 text-sm font-mono">
            <div class="p-3 rounded-lg bg-cyan/5 border border-cyan/20">
                <span class="text-cyan font-bold">Click:</span> <?= e(SAYT_URL) ?>/api/click_webhook.php
            </div>
            <div class="p-3 rounded-lg bg-pink/5 border border-pink/20">
                <span class="text-pink font-bold">Payme:</span> <?= e(SAYT_URL) ?>/api/payme_webhook.php
            </div>
            <div class="p-3 rounded-lg bg-violet/5 border border-violet/20">
                <span class="text-violet font-bold">Telegram bot:</span> <?= e(SAYT_URL) ?>/bot.php
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
