<?php
/**
 * AvtoTest Pro — Tizimga kirish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

// Avval kirgan bo'lsa → dashboard
if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato             = '';
$telefon_kiritildi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
        $telefon          = telefon_tozala(post('telefon'));
        $parol            = post('parol');
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
                tizimga_kirgan((int) $f['id']);

                $manzil = in_array($f['rol'], ['admin', 'developer'], true)
                    ? SAYT_URL . '/admin/'
                    : SAYT_URL . '/dashboard';

                flash_qoy('muvaffaqiyat', t('salom') . ', ' . $f['ism'] . '! 👋');
                yonaltir($manzil);
            } else {
                kirish_qayd($telefon, false);
                $xato = t('kirish_xato');
            }
        }
    }
}

$sahifa_sarlavha = t('kirish');
$body_class      = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md fade-up">

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-display font-black text-white text-2xl shadow-glow-blue">A</span>
                <span class="font-display font-bold text-white text-2xl">AvtoTest <span class="text-blue-400">Pro</span></span>
            </a>
            <p class="text-brand-muted text-sm mt-2"><?= e(t('sayt_shior')) ?></p>
        </div>

        <div class="glass-card p-8">
            <h1 class="text-2xl font-display mb-1"><?= e(t('kirish')) ?></h1>
            <p class="text-brand-muted text-sm mb-6">Akkauntingizga kiring</p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2 animate-shake">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{ loading: false, showPass: false }" @submit="loading = true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($telefon_kiritildi) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="field-label mb-0" for="parol"><?= e(t('parol')) ?></label>
                        <a href="<?= e(SAYT_URL) ?>/forgot-password"
                           class="text-xs text-blue-400 hover:text-blue-300 transition">
                            <?= e(t('parolni_unutdim')) ?>
                        </a>
                    </div>
                    <div class="relative">
                        <input id="parol" name="parol" :type="showPass ? 'text' : 'password'"
                               required placeholder="••••••••"
                               class="field pr-11" autocomplete="current-password">
                        <button type="button" @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-muted hover:text-white transition"
                                :aria-label="showPass ? 'Yashirish' : 'Ko\'rsatish'">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?>'"><?= e(t('kirish')) ?></span>
                </button>
            </form>

            <p class="text-center text-brand-muted text-sm mt-6">
                Akkauntingiz yo'qmi?
                <a href="<?= e(SAYT_URL) ?>/register" class="text-blue-400 hover:text-blue-300 font-medium ml-1"><?= e(t('royxatdan_otish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-5 text-xs text-brand-muted">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-white transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
