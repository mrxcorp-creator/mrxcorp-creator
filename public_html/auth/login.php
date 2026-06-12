<?php
/**
 * VatanParvar Yaypan — Tizimga kirish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';

// Agar avval kirgan bo'lsa — dashboard'ga
if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
$telefon_kiritildi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ----- CSRF -----
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    }
    // ----- Rate limit -----
    elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    }
    else {
        $telefon = telefon_tozala(post('telefon'));
        $parol   = post('parol');
        $telefon_kiritildi = post('telefon');

        if (!$telefon) {
            $xato = t('telefon_format');
        } elseif (!$parol) {
            $xato = t('kerakli_maydon');
        } else {
            $f = db_qator(
                'SELECT * FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if ($f && password_verify($parol, $f['parol_hash'])) {
                kirish_qayd($telefon, true);
                tizimga_kirgan($f['id']);

                // Adminlarni admin panelga, oddiy foydalanuvchilarni dashboardga
                $manzil = in_array($f['rol'], ['admin', 'developer'], true)
                          ? '/admin/' : '/dashboard';
                flash_qoy('muvaffaqiyat', t('salom') . ', ' . $f['ism'] . '!');
                yonaltir(SAYT_URL . $manzil);
            } else {
                kirish_qayd($telefon, false);
                $xato = t('kirish_xato');
            }
        }
    }
}

$sahifa_sarlavha = t('kirish');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-xl shadow-xl shadow-blue-500/30">V</span>
                <span class="font-display font-bold text-white text-2xl">VatanParvar</span>
            </a>
        </div>

        <!-- Karta -->
        <div class="glass-card p-8">
            <h1 class="text-2xl mb-2 text-white"><?= e(t('kirish')) ?></h1>
            <p class="text-brand-muted mb-6"><?= e(t('avval_kiring')) ?></p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm animate-shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($telefon_kiritildi) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?></label>
                    <div x-data="{show:false}" class="relative">
                        <input id="parol" name="parol" required
                               :type="show ? 'text' : 'password'"
                               placeholder="••••••••"
                               class="field pr-12" autocomplete="current-password">
                        <button type="button" @click="show=!show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-muted hover:text-white">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 102.8 2.8M16.7 16.7A9.7 9.7 0 0112 18c-6 0-9.5-6-9.5-6a17 17 0 014.3-4.7M9.4 5.2A10 10 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-2.5 3.4"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <a href="<?= e(SAYT_URL) ?>/forgot-password"
                       class="text-sm text-blue-400 hover:text-blue-300 transition">
                        <?= e(t('parolni_unutdim')) ?>
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?>'"><?= e(t('kirish')) ?></span>
                </button>
            </form>

            <p class="text-center text-brand-muted mt-6 text-sm">
                <?= e(t('royxatdan_otish')) ?>?
                <a href="<?= e(SAYT_URL) ?>/register" class="text-blue-400 hover:text-blue-300 font-medium ml-1"><?= e(t('royxatdan_otish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-brand-muted">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-white transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
