<?php
/**
 * VatanParvar Yaypan — Tizimga kirish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
$telefon_kiritildi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
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

<div class="min-h-screen flex items-center justify-center px-4 py-10 relative">

    <!-- Floating dekorativ shakllar -->
    <div class="absolute top-20 left-10 w-32 h-32 rounded-full bg-gradient-to-br from-sky-200 to-blue-200 opacity-40 blur-2xl floating" aria-hidden="true"></div>
    <div class="absolute bottom-20 right-10 w-40 h-40 rounded-full bg-gradient-to-br from-violet-200 to-sky-200 opacity-40 blur-2xl floating" style="animation-delay:1s" aria-hidden="true"></div>

    <div class="w-full max-w-md scale-in relative">
        <!-- Logo -->
        <div class="text-center mb-8 fade-up">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5 group">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white text-2xl shadow-glow group-hover:scale-110 transition-all duration-300">V</span>
                <span class="font-display font-bold text-brand-text text-2xl">VatanParvar</span>
            </a>
        </div>

        <!-- Karta -->
        <div class="glass-card p-8 fade-up stagger-1 shadow-medium">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-brand-text mb-2"><?= e(t('kirish')) ?> 👋</h1>
                <p class="text-brand-muted">Akkauntingizga kiring va davom eting</p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-start gap-2 animate-shake">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                    <span><?= e($xato) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <input id="telefon" name="telefon" type="tel" required
                               value="<?= e($telefon_kiritildi) ?>"
                               placeholder="+998 90 123 45 67"
                               class="field pl-11" autocomplete="tel">
                    </div>
                </div>

                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?></label>
                    <div x-data="{show:false}" class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input id="parol" name="parol" required
                               :type="show ? 'text' : 'password'"
                               placeholder="••••••••"
                               class="field pl-11 pr-12" autocomplete="current-password">
                        <button type="button" @click="show=!show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-light hover:text-sky-600 transition p-1">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 102.8 2.8M16.7 16.7A9.7 9.7 0 0112 18c-6 0-9.5-6-9.5-6a17 17 0 014.3-4.7M9.4 5.2A10 10 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-2.5 3.4"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <a href="<?= e(SAYT_URL) ?>/forgot-password"
                       class="text-sm text-sky-600 hover:text-sky-700 font-medium transition link-anim">
                        <?= e(t('parolni_unutdim')) ?>
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full py-3.5 mt-2" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?>'"><?= e(t('kirish')) ?></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-brand-border"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-white px-3 text-brand-muted">yoki</span>
                </div>
            </div>

            <p class="text-center text-brand-muted text-sm">
                Akkauntingiz yo'qmi?
                <a href="<?= e(SAYT_URL) ?>/register" class="text-sky-600 hover:text-sky-700 font-semibold ml-1 transition link-anim"><?= e(t('royxatdan_otish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-brand-muted fade-up stagger-3">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-sky-600 transition inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <?= e(t('bosh_sahifa')) ?>
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
