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

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">

        <!-- Yuqorigi panelni yashirish/ko'rsatish (mini) -->
        <div class="flex justify-end gap-2 mb-6">
            <button type="button" onclick="vpToggleTheme()"
                    class="w-10 h-10 rounded-xl border border-app bg-glass hover:bg-surface transition flex items-center justify-center text-app"
                    aria-label="Tema o'zgartirish">
                <svg class="w-4 h-4 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="w-4 h-4 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>
            <?php $joriy_til = $_SESSION['til'] ?? 'uz_latn'; ?>
            <div class="flex items-center text-xs border border-app bg-glass rounded-xl p-1">
                <a href="?til=uz_latn" class="px-2.5 py-1.5 rounded-lg <?= $joriy_til === 'uz_latn' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_latn' ? 'style="background: var(--gradient-primary);"' : '' ?>>Lat</a>
                <a href="?til=uz_cyrl" class="px-2.5 py-1.5 rounded-lg <?= $joriy_til === 'uz_cyrl' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_cyrl' ? 'style="background: var(--gradient-primary);"' : '' ?>>Кир</a>
            </div>
        </div>

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5">
                <span class="w-14 h-14 rounded-2xl flex items-center justify-center font-display font-extrabold text-white text-2xl shadow-lg"
                      style="background: var(--gradient-primary); box-shadow: 0 12px 32px var(--accent-glow);">V</span>
                <span class="font-display font-extrabold text-app text-2xl">VatanParvar</span>
            </a>
        </div>

        <!-- Karta -->
        <div class="glass-card-premium p-8">
            <h1 class="text-2xl mb-2 text-app"><?= e(t('kirish')) ?></h1>
            <p class="text-app-2 mb-6"><?= e(t('avval_kiring')) ?></p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl border text-sm animate-shake flex items-start gap-2"
                     style="background: var(--error-soft); border-color: color-mix(in srgb, var(--error) 30%, transparent); color: var(--error);">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                    <span><?= e($xato) ?></span>
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
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-app-2 hover:text-app">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.244 7.244L19.5 19.5m-3.378-3.378-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <a href="<?= e(SAYT_URL) ?>/forgot-password"
                       class="text-sm text-accent hover:opacity-80 transition">
                        <?= e(t('parolni_unutdim')) ?>
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full text-base py-3.5" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?>'"><?= e(t('kirish')) ?></span>
                </button>
            </form>

            <p class="text-center text-app-2 mt-6 text-sm">
                <?= e(t('royxatdan_otish')) ?>?
                <a href="<?= e(SAYT_URL) ?>/register" class="text-accent hover:opacity-80 font-medium ml-1"><?= e(t('royxatdan_otish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-app-2">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-app transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
