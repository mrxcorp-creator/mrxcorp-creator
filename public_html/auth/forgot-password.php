<?php
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
$muvaffaqiyat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!honeypot_tekshir()) {
        $xato = t('csrf_xato');
        sleep(1);
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
            kirish_qayd($telefon, false);

            if ($f && $f['telegram_id']) {
                $yangi_parol = bin2hex(random_bytes(5));
                $hash = password_hash($yangi_parol, PASSWORD_BCRYPT);
                db_bajar('UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?', [$hash, $f['id']]);

                $xabar = "🔐 <b>Vaqtinchalik parolingiz:</b>\n\n<code>{$yangi_parol}</code>\n\n"
                       . "Kirgandan so'ng profilingizdan parolni o'zgartiring.";
                telegram_yubor($f['telegram_id'], $xabar);
                audit_yoz('parol_tiklandi', 'foydalanuvchi', (int) $f['id']);
            }

            $muvaffaqiyat = "Agar telefon raqami ro'yxatda bo'lsa va Telegram bog'langan bo'lsa, yangi parol yuboriladi.";
        }
    }
}

$logo_yoli = sozlama('logo_yoli', '');
$logo_url = $logo_yoli && is_file(UPLOAD_PATH . '/' . $logo_yoli)
    ? SAYT_URL . '/uploads/' . $logo_yoli
    : SAYT_URL . '/assets/img/logo-mark.svg';
$chap_sarlavha = sozlama('login_chap_sarlavha', t('sayt_nomi'));

$sahifa_sarlavha = t('parolni_unutdim');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen grid lg:grid-cols-2">

    <div class="hidden lg:flex relative overflow-hidden">
        <div class="absolute inset-0 grad-bg opacity-95"></div>
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.3) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.3) 0%, transparent 50%);"></div>

        <div class="relative z-10 flex flex-col justify-center p-10 xl:p-16 w-full text-white">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-3 group w-fit mb-10">
                <img src="<?= e($logo_url) ?>" alt="" class="w-12 h-12 rounded-2xl bg-white/10 p-1 backdrop-blur">
                <span class="font-display font-extrabold text-xl"><?= e($chap_sarlavha) ?></span>
            </a>

            <div class="text-7xl mb-6">🔐</div>
            <h2 class="text-4xl xl:text-5xl font-display font-extrabold mb-4 leading-tight max-w-lg">
                Parolni unutdingizmi?
            </h2>
            <p class="text-lg opacity-90 max-w-md leading-relaxed">
                Hech qanday muammo yo'q. Telefon raqamingizni kiriting va Telegram bot orqali yangi parol oling.
            </p>
        </div>
    </div>

    <div class="flex items-center justify-center px-4 py-10 sm:px-8 relative">
        <a href="<?= e(SAYT_URL) ?>" class="absolute top-6 left-6 lg:hidden flex items-center gap-2 text-muted hover:text-text">
            <img src="<?= e($logo_url) ?>" alt="" class="w-9 h-9 rounded-xl">
            <span class="font-display font-extrabold">VatanParvar</span>
        </a>

        <button onclick="window.vpTheme.toggle()" aria-label="Mavzu o'zgartirish"
                class="absolute top-6 right-6 w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/5 transition text-muted hover:text-text">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
        </button>

        <div class="w-full max-w-md fade-up mt-10 lg:mt-0">
            <div class="mb-6">
                <h1 class="text-3xl font-display font-extrabold mb-2"><?= e(t('parolni_unutdim')) ?></h1>
                <p class="text-muted text-sm">Telegram bog'langan akkauntlar uchun</p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
                <div class="mb-5 p-4 rounded-xl bg-success/10 border border-success/30 text-success text-sm">
                    ✓ <?= e($muvaffaqiyat) ?>
                </div>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-primary w-full">← <?= e(t('kirish')) ?></a>
            <?php else: ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>
                <?= honeypot_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <button type="submit" class="btn btn-primary w-full text-base" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/10">
                <a href="<?= e(SAYT_URL) ?>/login" class="grad-text font-semibold text-sm">← <?= e(t('kirish')) ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
