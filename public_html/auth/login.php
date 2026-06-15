<?php
/**
 * AvtoTest Pro — Kirish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

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
            $u = db_qator(
                'SELECT * FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if ($u && password_verify($parol, $u['parol_hash'])) {
                kirish_qayd($telefon, true);
                tizimga_kirgan((int) $u['id']);
                $manzil = in_array($u['rol'], ['admin','developer'], true)
                    ? SAYT_URL . '/admin/'
                    : SAYT_URL . '/dashboard';
                flash_qoy('muvaffaqiyat', t('salom') . ', ' . $u['ism'] . '! 👋');
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

<div class="min-h-screen flex items-stretch">

    <!-- ── Chap: Branding paneli ─────────────────────────── -->
    <div class="hidden lg:flex flex-col justify-between w-[44%] xl:w-[42%] p-12 relative overflow-hidden
                bg-gradient-to-br from-blue-600/20 via-[#070C1A] to-violet-700/20
                border-r border-white/[0.06]">

        <!-- Decorative orb -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2
                    w-[28rem] h-[28rem] rounded-full
                    bg-gradient-to-br from-blue-600/25 to-violet-600/20
                    blur-[80px] pointer-events-none"></div>

        <a href="<?= e(SAYT_URL) ?>" class="relative flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600
                         flex items-center justify-center font-display font-black text-white text-xl
                         shadow-lg shadow-blue-500/30">A</span>
            <span class="font-display font-bold text-white text-xl">AvtoTest <span class="text-blue-400">Pro</span></span>
        </a>

        <div class="relative">
            <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-4">Platforma ustunliklari</p>
            <div class="space-y-4">
                <?php
                $afzalliklar = [
                    ['📚', '500+ haqiqiy savol', 'YHXBB imtihonidan eng yangi bazani ishla'],
                    ['⏱️', 'Vaqt nazorati',       'Real imtihon sharoitida mashq qiling'],
                    ['📊', 'Batafsil statistika', 'Zaif tomonlarni aniq aniqlang'],
                    ['🤖', 'Telegram bot',        'Bildirishnomalar va natijalar bot orqali'],
                ];
                foreach ($afzalliklar as [$ico, $nom, $tavsif]):
                ?>
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/[0.06] flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                        <?= $ico ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-white"><?= $nom ?></p>
                        <p class="text-xs text-white/45 mt-0.5"><?= $tavsif ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="relative text-xs text-white/30">
            © <?= date('Y') ?> AvtoTest Pro. Barcha huquqlar himoyalangan.
        </p>
    </div>

    <!-- ── O'ng: Login forma ─────────────────────────────── -->
    <div class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">

            <!-- Mobile logo -->
            <div class="lg:hidden text-center mb-8">
                <a href="<?= e(SAYT_URL) ?>" class="inline-flex flex-col items-center gap-2">
                    <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600
                                 flex items-center justify-center font-display font-black text-white text-2xl
                                 shadow-xl shadow-blue-500/30">A</span>
                    <span class="font-display font-bold text-white text-2xl">AvtoTest <span class="text-blue-400">Pro</span></span>
                </a>
            </div>

            <div class="fade-up">
                <h1 class="text-2xl font-display font-black mb-1"><?= e(t('kirish')) ?></h1>
                <p class="text-white/45 text-sm mb-7">Akkauntingizga xush kelibsiz</p>

                <!-- Xato -->
                <?php if ($xato): ?>
                <div class="flex items-center gap-2.5 p-3.5 rounded-xl
                            bg-red-500/10 border border-red-500/25 text-red-300 text-sm
                            mb-6 animate-shake">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <?= e($xato) ?>
                </div>
                <?php endif; ?>

                <!-- Forma -->
                <form method="POST" class="space-y-4"
                      x-data="{ loading: false, showPass: false }"
                      @submit="loading = true">
                    <?= csrf_input() ?>

                    <div>
                        <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                        <input id="telefon" name="telefon" type="tel" required
                               value="<?= e($telefon_kiritildi) ?>"
                               placeholder="+998 90 123 45 67"
                               class="field" autocomplete="tel">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="field-label mb-0" for="parol"><?= e(t('parol')) ?></label>
                            <a href="<?= e(SAYT_URL) ?>/forgot-password"
                               class="text-xs text-blue-400 hover:text-blue-300 transition">
                                <?= e(t('parolni_unutdim')) ?> →
                            </a>
                        </div>
                        <div class="relative" x-data="">
                            <input id="parol" name="parol"
                                   :type="showPass ? 'text' : 'password'"
                                   required placeholder="••••••••"
                                   class="field pr-11" autocomplete="current-password">
                            <button type="button"
                                    @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-white/35 hover:text-white/70 transition p-1">
                                <svg x-show="!showPass" class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPass" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg mt-2" :disabled="loading">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?> →'"><?= e(t('kirish')) ?> →</span>
                    </button>
                </form>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/[0.08]"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-4 bg-[#070C1A] text-xs text-white/35">yoki</span>
                    </div>
                </div>

                <a href="<?= e(SAYT_URL) ?>/register"
                   class="btn btn-ghost w-full btn-lg">
                    ✨ <?= e(t('royxatdan_otish')) ?>
                </a>

                <p class="text-center text-xs text-white/30 mt-5">
                    <a href="<?= e(SAYT_URL) ?>" class="hover:text-white/60 transition">← <?= e(t('bosh_sahifa')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
