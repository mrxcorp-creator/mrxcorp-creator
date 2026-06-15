<?php
/**
 * AvtoTest Pro — Parolni tiklash sahifasi
 *
 * BUG FIX: funksiyalar.php endi AVVAL yuklanadi, chunki telegram_yubor()
 * header.php yuklagunga qadar ishlatiladi.
 *
 * Mexanizm:
 *  1. Foydalanuvchi telefonini kiritadi.
 *  2. Telegram bog'langan bo'lsa — yangi vaqtinchalik parol botga yuboriladi.
 *  3. Telegram bog'lanmagan bo'lsa — admin bilan bog'lanish tavsiyasi.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php'; // ← CRITICAL: telegram_yubor() uchun

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
            $f = db_qator(
                'SELECT id, ism, telegram_id, parol_hash, holat
                 FROM foydalanuvchilar
                 WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );

            if (!$f) {
                // Xavfsizlik uchun aniq xabar bermaymiz
                $muvaffaqiyat = "Agar bu raqam ro'yxatdan o'tgan bo'lsa, parol yuborildi.";
            } elseif (!$f['telegram_id']) {
                $aloqa_tel = sozlama('aloqa_telefon', '');
                $xato = "Bu akkaunt Telegram'ga bog'lanmagan. "
                      . ($aloqa_tel ? "Admin bilan bog'laning: {$aloqa_tel}" : "Iltimos, admin bilan bog'laning.");
            } else {
                // Vaqtinchalik parol generatsiya
                $yangi_parol = parol_generat(10);
                $hash        = password_hash($yangi_parol, PASSWORD_BCRYPT, ['cost' => 12]);

                db_bajar(
                    'UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?',
                    [$hash, (int) $f['id']]
                );

                $xabar = "🔐 <b>Vaqtinchalik parolingiz:</b>\n\n"
                       . "<code>{$yangi_parol}</code>\n\n"
                       . "Ushbu parol bilan kirganingizdan so'ng uni <b>Profil → Parolni o'zgartirish</b> bo'limidan yangilang.";

                $yuborildi = telegram_yubor((int) $f['telegram_id'], $xabar);
                kirish_qayd($telefon, true);

                $muvaffaqiyat = $yuborildi
                    ? "Yangi parol Telegram botingizga yuborildi. Iltimos, tekshiring."
                    : "Telegram xabar yuborishda xato. Iltimos, keyinroq urinib ko'ring.";
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
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-display font-black text-white text-2xl shadow-glow-blue">A</span>
                <span class="font-display font-bold text-white text-2xl">AvtoTest <span class="text-blue-400">Pro</span></span>
            </a>
        </div>

        <div class="glass-card p-8">
            <div class="flex items-center gap-3 mb-6">
                <span class="w-11 h-11 rounded-xl bg-yellow-500/15 text-yellow-400 flex items-center justify-center text-xl flex-shrink-0">🔑</span>
                <div>
                    <h1 class="text-xl font-display"><?= e(t('parolni_unutdim')) ?></h1>
                    <p class="text-xs text-brand-muted">Yangi parol Telegram orqali yuboriladi</p>
                </div>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2 animate-shake">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
                <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-green-300 text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span><?= e($muvaffaqiyat) ?></span>
                </div>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn-primary w-full"><?= e(t('kirish')) ?></a>
            <?php else: ?>
                <p class="text-brand-muted text-sm mb-5">
                    Ro'yxatdan o'tishda ishlatgan telefon raqamingizni kiriting.
                    Akkauntingizga Telegram bog'langan bo'lsa, yangi parol botga yuboriladi.
                </p>

                <form method="POST" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    <?= csrf_input() ?>

                    <div>
                        <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                        <input id="telefon" name="telefon" type="tel" required
                               placeholder="+998 90 123 45 67"
                               class="field" autocomplete="tel">
                    </div>

                    <button type="submit" class="btn-primary w-full" :disabled="loading">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                    </button>
                </form>
            <?php endif; ?>

            <p class="text-center text-brand-muted text-sm mt-6">
                <a href="<?= e(SAYT_URL) ?>/login" class="text-blue-400 hover:text-blue-300 transition">← <?= e(t('kirish')) ?></a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
