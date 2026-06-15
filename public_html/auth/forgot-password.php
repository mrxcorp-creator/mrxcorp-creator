<?php
/**
 * VatanParvar Yaypan — Parolni tiklash
 *
 * BUG FIX: funksiyalar.php AVVAL yuklanadi
 * (telegram_yubor() header.php'dan oldin kerak)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php'; // ← CRITICAL

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato         = '';
$muvaffaqiyat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
        $telefon = telefon_tozala(post('telefon'));

        if (!$telefon) {
            $xato = t('telefon_format');
        } else {
            $u = db_qator(
                'SELECT id, ism, telegram_id, holat
                 FROM foydalanuvchilar
                 WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );

            if (!$u) {
                // Xavfsizlik: aniq xabar bermaylik
                $muvaffaqiyat = 'Agar bu raqam ro\'yxatdan o\'tgan bo\'lsa, parol yuborildi.';
            } elseif (!$u['telegram_id']) {
                $aloqa = sozlama('aloqa_telefon', '');
                $xato  = 'Bu akkaunt Telegram\'ga bog\'lanmagan.'
                       . ($aloqa ? " Admin: $aloqa" : '');
            } else {
                $yangi_parol = parol_generat(10);
                $hash        = password_hash($yangi_parol, PASSWORD_BCRYPT, ['cost' => 12]);

                db_bajar(
                    'UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?',
                    [$hash, (int)$u['id']]
                );

                $xabar = "🔐 <b>Yangi vaqtinchalik parolingiz:</b>\n\n"
                       . "<code>{$yangi_parol}</code>\n\n"
                       . "Kirganingizdan so'ng <b>Profil → Parolni o'zgartirish</b> "
                       . "bo'limidan yangilang.";

                $yuborildi = telegram_yubor((int)$u['telegram_id'], $xabar);

                $muvaffaqiyat = $yuborildi
                    ? 'Yangi parol Telegram botingizga yuborildi. Iltimos, tekshiring.'
                    : 'Telegram xabar yuborishda muammo. Keyinroq urinib ko\'ring.';
            }
        }
    }
}

$sahifa_sarlavha = t('parolni_unutdim');
$body_class      = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md fade-up">

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600
                             flex items-center justify-center font-display font-black text-white text-2xl
                             shadow-xl shadow-blue-500/30">A</span>
                <span class="font-display font-bold text-white text-2xl">
                    VatanParvar <span class="text-blue-400">Yaypan</span>
                </span>
            </a>
        </div>

        <div class="glass-card p-8">

            <!-- Sarlavha -->
            <div class="flex items-start gap-3 mb-6">
                <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-400
                            flex items-center justify-center text-2xl flex-shrink-0">
                    🔑
                </div>
                <div>
                    <h1 class="font-display font-black text-xl">
                        <?= e(t('parolni_unutdim')) ?>
                    </h1>
                    <p class="text-white/40 text-sm mt-0.5">
                        Yangi parol Telegram orqali yuboriladi
                    </p>
                </div>
            </div>

            <!-- Xato -->
            <?php if ($xato): ?>
            <div class="flex items-start gap-2.5 p-3.5 rounded-xl mb-5
                        bg-red-500/10 border border-red-500/25 text-red-300 text-sm animate-shake">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <?= e($xato) ?>
            </div>
            <?php endif; ?>

            <!-- Muvaffaqiyat -->
            <?php if ($muvaffaqiyat): ?>
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25
                        text-emerald-300 text-sm mb-5 flex items-start gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= e($muvaffaqiyat) ?>
            </div>
            <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-primary w-full">
                <?= e(t('kirish')) ?> →
            </a>

            <?php else: ?>

            <p class="text-sm text-white/50 mb-5 leading-relaxed">
                Ro'yxatdan o'tishda ishlatgan telefon raqamingizni kiriting.
                Akkauntingizga Telegram bog'langan bo'lsa, yangi parol botga yuboriladi.
            </p>

            <form method="POST" class="space-y-4"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <button type="submit" class="btn btn-primary w-full btn-lg" :disabled="loading">
                    <svg x-show="loading" class="w-4 h-4 animate-spin"
                         fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'">
                        <?= e(t('tasdiqlash')) ?>
                    </span>
                </button>
            </form>

            <!-- Telegram bot ulash eslatmasi -->
            <div class="mt-5 p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.07]">
                <p class="text-xs text-white/35 leading-relaxed">
                    💡 <strong class="text-white/50">Telegram yo'q?</strong>
                    Avval tizimga kirib, profilingizdan Telegram'ni ulang.
                    Shundan so'ng parolni tiklash ishlaydi.
                </p>
            </div>

            <?php endif; ?>

            <p class="text-center text-sm text-white/35 mt-5">
                <a href="<?= e(SAYT_URL) ?>/login"
                   class="text-blue-400 hover:text-blue-300 transition font-medium">
                    ← <?= e(t('kirish')) ?>
                </a>
            </p>
        </div>

        <p class="text-center mt-4 text-xs text-white/25">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-white/50 transition">
                ← <?= e(t('bosh_sahifa')) ?>
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
