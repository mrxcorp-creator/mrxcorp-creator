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

$sahifa_sarlavha = t('parolni_unutdim');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-3">
                <img src="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg" alt="" class="w-12 h-12 rounded-2xl">
                <span class="font-display font-extrabold text-2xl">VatanParvar <span class="grad-text">Yaypan</span></span>
            </a>
        </div>

        <div class="ring-grad">
            <div class="p-8">
                <h1 class="text-2xl font-display font-bold mb-2"><?= e(t('parolni_unutdim')) ?></h1>
                <p class="text-muted mb-6 text-sm">
                    Telefon raqamingizni kiriting. Yangi parol Telegram orqali yuboriladi.
                </p>

                <?php if ($xato): ?>
                    <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm shake">
                        <?= e($xato) ?>
                    </div>
                <?php endif; ?>

                <?php if ($muvaffaqiyat): ?>
                    <div class="mb-5 p-3 rounded-xl bg-success/10 border border-success/30 text-success text-sm">
                        <?= e($muvaffaqiyat) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$muvaffaqiyat): ?>
                <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                    <?= csrf_input() ?>
                    <?= honeypot_input() ?>
                        <input id="telefon" name="telefon" type="tel" required
                               placeholder="+998 90 123 45 67"
                               class="field" autocomplete="tel">
                    </div>

                    <button type="submit" class="btn btn-primary w-full text-base" :disabled="loading">
                        <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                    </button>
                </form>
                <?php endif; ?>

                <p class="text-center text-muted mt-6 text-sm">
                    <a href="<?= e(SAYT_URL) ?>/login" class="grad-text font-semibold">← <?= e(t('kirish')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
