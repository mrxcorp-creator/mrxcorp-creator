<?php
/**
 * VatanParvar Yaypan — Parolni unutdim sahifasi
 */
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
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
                'SELECT * FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if (!$f) {
                $xato = t('kirish_xato');
            } elseif (!$f['telegram_id']) {
                $xato = "Telegram bog'lanmagan. Iltimos, admin bilan bog'laning: " . sozlama('aloqa_telefon');
            } else {
                $yangi_parol = bin2hex(random_bytes(4));
                $hash = password_hash($yangi_parol, PASSWORD_BCRYPT);
                db_bajar('UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?', [$hash, $f['id']]);

                $xabar = "🔐 <b>Vaqtinchalik parolingiz:</b>\n\n<code>{$yangi_parol}</code>\n\nKirgandan so'ng profilingizdan parolni o'zgartiring.";
                telegram_yubor($f['telegram_id'], $xabar);
                kirish_qayd($telefon, true);

                $muvaffaqiyat = "Yangi parol Telegram orqali yuborildi. Botingizni tekshiring.";
            }
        }
    }
}

$sahifa_sarlavha = t('parolni_unutdim');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10 relative">

    <div class="absolute top-32 left-1/4 w-32 h-32 rounded-full bg-gradient-to-br from-amber-200 to-sky-200 opacity-40 blur-2xl floating" aria-hidden="true"></div>
    <div class="absolute bottom-32 right-1/4 w-36 h-36 rounded-full bg-gradient-to-br from-sky-200 to-emerald-200 opacity-40 blur-2xl floating" style="animation-delay:1.2s" aria-hidden="true"></div>

    <div class="w-full max-w-md scale-in relative">
        <div class="text-center mb-8 fade-up">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5 group">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white text-2xl shadow-glow group-hover:scale-110 transition-all duration-300">V</span>
                <span class="font-display font-bold text-brand-text text-2xl">VatanParvar</span>
            </a>
        </div>

        <div class="glass-card p-8 fade-up stagger-1 shadow-medium">
            <div class="mb-6 text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-brand-text mb-2"><?= e(t('parolni_unutdim')) ?></h1>
                <p class="text-brand-muted text-sm">
                    Telefon raqamingizni kiriting.<br>Yangi parol Telegram orqali yuboriladi.
                </p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm animate-shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2.5">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    <span><?= e($muvaffaqiyat) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$muvaffaqiyat): ?>
            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <input id="telefon" name="telefon" type="tel" required
                               placeholder="+998 90 123 45 67"
                               class="field pl-11" autocomplete="tel">
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-3.5" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                </button>
            </form>
            <?php endif; ?>

            <p class="text-center text-brand-muted mt-6 text-sm">
                <a href="<?= e(SAYT_URL) ?>/login" class="text-sky-600 hover:text-sky-700 font-semibold transition inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <?= e(t('kirish')) ?>
                </a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
