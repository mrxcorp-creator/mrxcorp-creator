<?php
/**
 * VatanParvar Yaypan — Parolni unutdim sahifasi
 *
 * Parolni qayta tiklash Telegram orqali boriladi.
 * Xavfsizlik uchun foydalanuvchi mavjudmi yoki yo'qligi haqida ma'lumot
 * ochib berilmaydi (har holda neytral xabar).
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
            // Foydalanuvchi mavjud bo'lsa va Telegram ulangan bo'lsa — yuboramiz.
            // Aks holda ham bir xil neytral xabar (info leak'ni oldini olamiz).
            $f = db_qator(
                'SELECT * FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if ($f && $f['telegram_id']) {
                $yangi_parol = bin2hex(random_bytes(4));
                $hash = password_hash($yangi_parol, PASSWORD_BCRYPT);
                db_bajar('UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?', [$hash, $f['id']]);

                $xabar = "🔐 <b>Vaqtinchalik parolingiz:</b>\n\n<code>{$yangi_parol}</code>\n\nKirgandan so'ng profilingizdan parolni o'zgartiring.";
                telegram_yubor($f['telegram_id'], $xabar);
            }
            // Har qanday holatda ham bir xil javob:
            $muvaffaqiyat = "Agar telefon raqamingiz tizimda mavjud va Telegram bog'langan bo'lsa, yangi parol Telegram orqali yuborildi.";
        }
    }
}

$sahifa_sarlavha = t('parolni_unutdim');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">

        <div class="flex justify-end gap-2 mb-6">
            <button type="button" onclick="vpToggleTheme()"
                    class="w-10 h-10 rounded-xl border border-app bg-glass hover:bg-surface transition flex items-center justify-center text-app">
                <svg class="w-4 h-4 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="w-4 h-4 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>
        </div>

        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-block">
                <?= logo_html('lg', false) ?>
            </a>
        </div>

        <div class="glass-card-premium p-8">
            <h1 class="text-2xl mb-2 text-app"><?= e(t('parolni_unutdim')) ?></h1>
            <p class="text-app-2 mb-6">
                Telefon raqamingizni kiriting. Yangi parol Telegram orqali yuboriladi.
            </p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl border text-sm animate-shake"
                     style="background: var(--error-soft); border-color: color-mix(in srgb, var(--error) 30%, transparent); color: var(--error);">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
                <div class="mb-5 p-3.5 rounded-xl border text-sm flex items-start gap-2"
                     style="background: var(--success-soft); border-color: color-mix(in srgb, var(--success) 30%, transparent); color: var(--success);">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                    <span><?= e($muvaffaqiyat) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$muvaffaqiyat): ?>
            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <button type="submit" class="btn-primary w-full text-base py-3.5" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                </button>
            </form>
            <?php endif; ?>

            <p class="text-center text-app-2 mt-6 text-sm">
                <a href="<?= e(SAYT_URL) ?>/login" class="text-accent hover:opacity-80 font-medium">← <?= e(t('kirish')) ?></a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
