<?php
/**
 * AvtoTest Pro — Sayt sozlamalari
 * BUG FIX: kesh_tozala() bilan barcha til keshlari tozalanadi
 * NEW: click_service_id sozlamasi qo'shildi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/sozlamalar.php');
    }
    $yozildi = 0;
    foreach ($_POST as $kalit => $qiymat) {
        if ($kalit === 'csrf_token' || !is_string($qiymat)) {
            continue;
        }
        if (!preg_match('/^[a-z0-9_]+$/', $kalit)) {
            continue; // Faqat xavfsiz kalitlarni saqlash
        }
        sozlama_saqla($kalit, trim($qiymat));
        $yozildi++;
    }
    kesh_tozala(); // BUG FIX: barcha til keshlari tozalanadi
    flash_qoy('muvaffaqiyat', t('malumot_saqlandi') . " ({$yozildi} sozlama)");
    yonaltir(SAYT_URL . '/admin/sozlamalar.php');
}

$barcha = [];
foreach (db_barcha('SELECT * FROM sozlamalar ORDER BY kalit') as $s) {
    $barcha[$s['kalit']] = $s;
}

// Guruhlangan sozlamalar
$guruhlar = [
    'Sayt ma\'lumotlari' => [
        'sayt_nomi'     => ['text',     'Sayt nomi'],
        'sayt_shior'    => ['textarea', 'Sayt shiori (bosh sahifa)'],
        'aloqa_telefon' => ['text',     'Aloqa telefoni'],
        'aloqa_email'   => ['email',    'Aloqa email'],
        'telegram_kanal'=> ['url',      'Telegram kanal URL'],
    ],
    'Telegram Bot' => [
        'telegram_bot_token'   => ['password', 'Bot tokeni (BotFather dan)'],
        'telegram_bot_username'=> ['text',     'Bot username (@boshsiz)'],
        'telegram_admin_id'    => ['text',     'Admin Telegram ID'],
    ],
    'To\'lov tizimlari' => [
        'click_merchant_id' => ['text',     'Click Merchant ID'],
        'click_service_id'  => ['text',     'Click Service ID'],  // NEW
        'click_secret'      => ['password', 'Click Secret key'],
        'payme_merchant_id' => ['text',     'Payme Merchant ID'],
        'payme_key'         => ['password', 'Payme test/prod kaliti'],
    ],
    'Test sozlamalari' => [
        'test_vaqti_minut'  => ['number', 'Test vaqti (daqiqada)'],
        'savol_soni_test'   => ['number', 'Bir testdagi savollar soni'],
    ],
    'Referal va Cron' => [
        'referal_bonus' => ['number', 'Referal bonus summasi (so\'m)'],
        'cron_kalit'    => ['password', 'Cron muhofaza kaliti (tasodifiy string)'],
    ],
];
$maxfiy_kalitlar = ['token', 'secret', 'key', 'parol', 'kalit', 'password'];

$admin_sahifa    = 'sozlamalar';
$sahifa_sarlavha = 'Sozlamalar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="space-y-5">
    <?= csrf_input() ?>

    <?php foreach ($guruhlar as $guruh_nomi => $kalitlar): ?>
        <div class="glass-card p-5 fade-up">
            <h2 class="font-display text-base mb-4"><?= e($guruh_nomi) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($kalitlar as $k => [$tip, $tavsif]):
                    $qiymat = $barcha[$k]['qiymat'] ?? '';
                    $maxfiy = array_reduce($maxfiy_kalitlar, fn($carry, $w) => $carry || str_contains($k, $w), false);
                    $input_tip = $maxfiy && $tip === 'text' ? 'password' : $tip;
                ?>
                    <div <?= $tip === 'textarea' ? 'class="sm:col-span-2"' : '' ?>>
                        <label class="field-label" for="sozlama_<?= e($k) ?>"><?= e($tavsif) ?></label>
                        <?php if ($tip === 'textarea'): ?>
                            <textarea name="<?= e($k) ?>" id="sozlama_<?= e($k) ?>"
                                      rows="2" class="field"><?= e($qiymat) ?></textarea>
                        <?php else: ?>
                            <input name="<?= e($k) ?>" id="sozlama_<?= e($k) ?>"
                                   type="<?= e($input_tip) ?>"
                                   value="<?= e($qiymat) ?>"
                                   class="field"
                                   autocomplete="<?= $maxfiy ? 'off' : 'on' ?>"
                                   <?= $tip === 'number' ? 'step="1" min="0"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Webhook URL'lar (o'qish uchun) -->
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display text-base mb-3">🔗 Webhook URL'lar</h2>
        <p class="text-sm text-brand-muted mb-4">
            Ushbu URL'larni to'lov tizimlari merchant kabinetiga kiriting.
        </p>
        <?php
        $urls = [
            ['Click vebhuk',    SAYT_URL . '/api/click_webhook.php'],
            ['Payme vebhuk',    SAYT_URL . '/api/payme_webhook.php'],
            ['Telegram bot',    SAYT_URL . '/bot.php'],
            ['Cron: backup',    SAYT_URL . '/cron/backup.php?kalit=KALIT'],
            ['Cron: eslatma',   SAYT_URL . '/cron/obuna_eslatma.php?kalit=KALIT'],
        ];
        foreach ($urls as [$nom, $url]):
        ?>
            <div class="flex items-center gap-3 py-2 border-b border-white/[0.05] last:border-0"
                 x-data="{ nusxa: false }">
                <span class="text-xs text-brand-muted w-28 flex-shrink-0"><?= e($nom) ?></span>
                <code class="text-xs font-mono flex-1 text-blue-300 truncate"><?= e($url) ?></code>
                <button type="button"
                        @click="navigator.clipboard.writeText('<?= e($url) ?>');nusxa=true;setTimeout(()=>nusxa=false,2000)"
                        class="badge badge-blue hover:badge-green cursor-pointer flex-shrink-0 transition-colors">
                    <span x-show="!nusxa">Nusxa</span>
                    <span x-show="nusxa" x-cloak>✓</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
        <a href="<?= e(SAYT_URL) ?>/cron/sozlash.php?kalit=<?= e(sozlama('cron_kalit','')) ?>&harakat=webhook_set"
           target="_blank"
           class="btn-ghost text-sm">🤖 Webhook o'rnatish</a>
    </div>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
