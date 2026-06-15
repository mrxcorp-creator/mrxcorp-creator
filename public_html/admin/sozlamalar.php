<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/sozlamalar.php'); }
    $yozildi = 0;
    foreach ($_POST as $kalit => $qiymat) {
        if ($kalit === 'csrf_token' || !is_string($qiymat)) continue;
        if (!preg_match('/^[a-z0-9_]{2,100}$/', $kalit)) continue;
        sozlama_saqla($kalit, trim($qiymat));
        $yozildi++;
    }
    kesh_tozala();
    flash_qoy('muvaffaqiyat', "Saqlandi ({$yozildi} sozlama)");
    yonaltir(SAYT_URL . '/admin/sozlamalar.php');
}

$barcha = [];
foreach (db_barcha('SELECT * FROM sozlamalar ORDER BY kalit') as $s) {
    $barcha[$s['kalit']] = $s;
}

$guruhlar = [
    'Sayt' => [
        'sayt_nomi'     => ['text', 'Sayt nomi'],
        'sayt_shior'    => ['textarea', 'Sayt shiori'],
        'aloqa_telefon' => ['tel', 'Aloqa telefoni'],
        'aloqa_email'   => ['email', 'Aloqa email'],
        'telegram_kanal'=> ['url', 'Telegram kanal URL'],
    ],
    'Telegram bot' => [
        'telegram_bot_token'      => ['password', 'Bot tokeni'],
        'telegram_bot_username'   => ['text',     'Bot username'],
        'telegram_admin_id'       => ['text',     'Admin Telegram ID'],
        'telegram_webhook_secret' => ['password', 'Webhook secret'],
    ],
    "To'lov tizimlari" => [
        'click_merchant_id' => ['text', 'Click Merchant ID'],
        'click_service_id'  => ['text', 'Click Service ID'],
        'click_secret'      => ['password', 'Click Secret'],
        'payme_merchant_id' => ['text', 'Payme Merchant ID'],
        'payme_key'         => ['password', 'Payme key'],
    ],
    'Test' => [
        'test_vaqti_minut' => ['number', 'Test vaqti (daqiqa)'],
        'savol_soni_test'  => ['number', 'Savollar soni'],
    ],
    'Boshqa' => [
        'referal_bonus' => ['number', 'Referal bonus (so\'m)'],
        'cron_kalit'    => ['password', 'Cron kalit'],
    ],
];
$maxfiy = ['token','secret','key','parol','password','kalit'];

$admin_sahifa    = 'sozlamalar';
$sahifa_sarlavha = 'Sozlamalar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST">
    <?= csrf_input() ?>

    <?php foreach ($guruhlar as $guruh => $kalitlar): ?>
    <div class="b-card" style="padding:1.25rem; margin-bottom:1rem;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1rem;
                   text-transform:uppercase; letter-spacing:.05em;
                   padding-bottom:.65rem; border-bottom:1px solid #000;
                   margin-bottom:1.25rem;">
            <?= e($guruh) ?>
        </h2>

        <div style="display:grid; grid-template-columns:1fr; gap:.75rem;"
             class="md:grid-cols-2">
            <?php foreach ($kalitlar as $k => [$tip, $tavsif]):
                $qiymat = $barcha[$k]['qiymat'] ?? '';
                $is_secret = array_reduce($maxfiy, fn($c, $w) => $c || str_contains($k, $w), false);
                $input_tip = $is_secret && $tip === 'text' ? 'password' : $tip;
                $col = $tip === 'textarea' ? 'md:col-span-2' : '';
            ?>
            <div class="<?= $col ?>">
                <label class="field-label">
                    <?= e($tavsif) ?>
                    <?php if ($is_secret): ?>
                    <span style="color:#666; font-weight:normal;">(maxfiy)</span>
                    <?php endif; ?>
                </label>
                <?php if ($tip === 'textarea'): ?>
                <textarea name="<?= e($k) ?>" rows="2" class="field"><?= e($qiymat) ?></textarea>
                <?php else: ?>
                <input name="<?= e($k) ?>" type="<?= e($input_tip) ?>"
                       value="<?= e($qiymat) ?>" class="field"
                       autocomplete="<?= $is_secret ? 'new-password' : 'off' ?>"
                       <?= $tip === 'number' ? 'min="0" step="1"' : '' ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Webhook URL'lar -->
    <div class="b-card" style="padding:1.25rem; margin-bottom:1rem;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1rem;
                   text-transform:uppercase; letter-spacing:.05em;
                   padding-bottom:.65rem; border-bottom:1px solid #000;
                   margin-bottom:1rem;">
            Webhook URL'lar
        </h2>
        <p style="font-size:.825rem; color:#666; margin-bottom:1rem;">
            To'lov tizimlari merchant kabinetiga kiriting.
        </p>
        <?php
        $urls = [
            ['Click webhook', SAYT_URL . '/api/click_webhook.php'],
            ['Payme webhook', SAYT_URL . '/api/payme_webhook.php'],
            ['Telegram bot',  SAYT_URL . '/bot.php'],
        ];
        foreach ($urls as [$nom, $url]):
        ?>
        <div style="display:flex; gap:.75rem; align-items:center;
                    padding:.6rem 0; border-bottom:1px solid #E5E5E5;
                    font-size:.82rem;"
             x-data="{ copied: false }">
            <span style="width:130px; color:#666; flex-shrink:0;"><?= e($nom) ?></span>
            <code style="flex:1; font-family:monospace; word-break:break-all;
                         color:#000;"><?= e($url) ?></code>
            <button type="button"
                    @click="navigator.clipboard.writeText('<?= e($url) ?>'); copied=true; setTimeout(()=>copied=false, 2000)"
                    class="btn btn-xs"
                    :class="copied ? 'btn-primary' : ''"
                    style="flex-shrink:0;">
                <span x-text="copied ? '✓' : 'Nusxa'">Nusxa</span>
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Saqlash</button>
    <?php $cron_k = sozlama('cron_kalit', ''); if ($cron_k): ?>
    <a href="<?= e(SAYT_URL) ?>/cron/sozlash.php?kalit=<?= urlencode($cron_k) ?>&harakat=webhook_set"
       target="_blank" class="btn btn-sm">Telegram webhook o'rnatish</a>
    <?php endif; ?>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
