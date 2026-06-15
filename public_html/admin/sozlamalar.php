<?php
/**
 * AvtoTest Pro — Sozlamalar sahifasi
 * BUG FIX: kesh_tozala() — barcha til keshlari tozalanadi
 * YANGI: telegram_webhook_secret + click_service_id sozlamalari
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
        if ($kalit === 'csrf_token' || !is_string($qiymat)) continue;
        if (!preg_match('/^[a-z0-9_]{2,100}$/', $kalit))      continue;
        sozlama_saqla($kalit, trim($qiymat));
        $yozildi++;
    }

    kesh_tozala(); // barcha til keshlari tozalanadi
    flash_qoy('muvaffaqiyat', t('malumot_saqlandi') . " ({$yozildi} sozlama)");
    yonaltir(SAYT_URL . '/admin/sozlamalar.php');
}

// Barcha sozlamalarni olib, kalit bo'yicha indekslash
$barcha = [];
foreach (db_barcha('SELECT * FROM sozlamalar ORDER BY kalit') as $s) {
    $barcha[$s['kalit']] = $s;
}

// Guruhlangan sozlamalar (kalit → [tip, tavsif])
$guruhlar = [
    'Sayt ma\'lumotlari' => [
        'sayt_nomi'     => ['text',     'Sayt nomi'],
        'sayt_shior'    => ['textarea', 'Bosh sahifa shiori'],
        'aloqa_telefon' => ['tel',      'Aloqa telefoni'],
        'aloqa_email'   => ['email',    'Aloqa email'],
        'telegram_kanal'=> ['url',      'Telegram kanal URL'],
    ],
    'Telegram Bot' => [
        'telegram_bot_token'      => ['password', 'Bot tokeni (BotFather dan oling)'],
        'telegram_bot_username'   => ['text',     'Bot username (@ belgisisiz)'],
        'telegram_admin_id'       => ['text',     'Admin Telegram ID (raqam)'],
        'telegram_webhook_secret' => ['password', 'Webhook himoya kodi (ixtiyoriy, xavfsizlik uchun)'],
    ],
    'To\'lov tizimlari' => [
        'click_merchant_id' => ['text',     'Click Merchant ID'],
        'click_service_id'  => ['text',     'Click Service ID'],
        'click_secret'      => ['password', 'Click Secret kaliti'],
        'payme_merchant_id' => ['text',     'Payme Merchant ID'],
        'payme_key'         => ['password', 'Payme kaliti (test yoki prod)'],
    ],
    'Test sozlamalari' => [
        'test_vaqti_minut' => ['number', 'Test vaqti (daqiqada, standart: 25)'],
        'savol_soni_test'  => ['number', 'Bir testdagi savollar soni (standart: 20)'],
    ],
    'Referal & Xizmat' => [
        'referal_bonus' => ['number',   'Referal bonus summasi (so\'m)'],
        'cron_kalit'    => ['password', 'Cron/webhook himoya kaliti (32+ belgili tasodifiy string)'],
    ],
];

$maxfiy_soʼzlar = ['token','secret','key','parol','password','kalit'];

$admin_sahifa    = 'sozlamalar';
$sahifa_sarlavha = 'Sozlamalar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="space-y-5">
    <?= csrf_input() ?>

    <?php foreach ($guruhlar as $guruh_nomi => $kalitlar): ?>
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-semibold text-sm uppercase tracking-wide
                   text-white/50 mb-5"><?= e($guruh_nomi) ?></h2>

        <div class="grid sm:grid-cols-2 gap-4">
            <?php foreach ($kalitlar as $k => [$tip, $tavsif]):
                $qiymat  = $barcha[$k]['qiymat'] ?? '';
                $maxfiy  = array_reduce(
                    $maxfiy_soʼzlar,
                    fn($c, $w) => $c || str_contains($k, $w),
                    false
                );
                $input_tip = ($maxfiy && $tip === 'text') ? 'password' : $tip;
                $col_span  = $tip === 'textarea' ? 'sm:col-span-2' : '';
            ?>
            <div class="<?= $col_span ?>">
                <label class="field-label" for="sozlama_<?= e($k) ?>">
                    <?= e($tavsif) ?>
                    <?php if (str_contains($k, 'secret') || str_contains($k, 'token')
                            || str_contains($k, 'kalit')): ?>
                    <span class="text-amber-400/60 text-[10px] ml-1">🔒</span>
                    <?php endif; ?>
                </label>

                <?php if ($tip === 'textarea'): ?>
                    <textarea name="<?= e($k) ?>"
                              id="sozlama_<?= e($k) ?>"
                              rows="2" class="field"><?= e($qiymat) ?></textarea>
                <?php else: ?>
                    <input name="<?= e($k) ?>"
                           id="sozlama_<?= e($k) ?>"
                           type="<?= e($input_tip) ?>"
                           value="<?= e($qiymat) ?>"
                           class="field"
                           autocomplete="<?= $maxfiy ? 'new-password' : 'off' ?>"
                           <?= $tip === 'number' ? 'min="0" step="1"' : '' ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Webhook URL'lar (read-only) -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display font-semibold text-sm uppercase tracking-wide
                   text-white/50 mb-4">🔗 Webhook URL'lar</h2>
        <p class="text-sm text-white/40 mb-4">
            To'lov tizimlari merchant kabinetiga kiriting.
        </p>

        <?php
        $urls = [
            ['Click webhook',  SAYT_URL . '/api/click_webhook.php'],
            ['Payme webhook',  SAYT_URL . '/api/payme_webhook.php'],
            ['Telegram bot',   SAYT_URL . '/bot.php'],
            ['Cron backup',    SAYT_URL . '/cron/backup.php?kalit=KALIT'],
            ['Cron eslatma',   SAYT_URL . '/cron/obuna_eslatma.php?kalit=KALIT'],
        ];
        foreach ($urls as [$nom, $url]):
        ?>
        <div class="flex items-center gap-3 py-2.5 border-b border-white/[0.05] last:border-0"
             x-data="{ nusxa: false }">
            <span class="text-xs text-white/35 w-32 flex-shrink-0"><?= e($nom) ?></span>
            <code class="text-xs font-mono text-blue-300/80 flex-1 truncate">
                <?= e($url) ?>
            </code>
            <button type="button"
                    @click="navigator.clipboard.writeText('<?= e($url) ?>');
                            nusxa=true; setTimeout(()=>nusxa=false, 2000)"
                    class="badge cursor-pointer transition flex-shrink-0"
                    :class="nusxa ? 'badge-green' : 'badge-blue'">
                <span x-text="nusxa ? '✓' : 'Nusxa'">Nusxa</span>
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Submit + Telegram webhook -->
    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="btn btn-primary">
            💾 <?= e(t('saqlash')) ?>
        </button>
        <?php $cron_k = sozlama('cron_kalit', ''); if ($cron_k): ?>
        <a href="<?= e(SAYT_URL) ?>/cron/sozlash.php?kalit=<?= urlencode($cron_k) ?>&harakat=webhook_set"
           target="_blank"
           class="btn btn-ghost btn-sm">
            🤖 Telegram webhookni o'rnatish
        </a>
        <?php endif; ?>
        <span class="text-xs text-white/30">
            * Saqlashdan so'ng kesh avtomatik tozalanadi
        </span>
    </div>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
