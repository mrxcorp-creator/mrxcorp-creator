<?php
/**
 * VatanParvar Yaypan — Parolni unutdim sahifasi
 *
 * Parolni qayta tiklash Telegram orqali boriladi:
 * Foydalanuvchi telefonini kiritadi -> agar uning Telegram akkaunti bog'langan bo'lsa,
 * yangi vaqtinchalik parol botga yuboriladi.
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
                // Vaqtinchalik parol generatsiya qilish
                $yangi_parol = bin2hex(random_bytes(4)); // 8 ta belgi
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

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-xl shadow-xl shadow-blue-500/30">V</span>
                <span class="font-display font-bold text-white text-2xl">VatanParvar</span>
            </a>
        </div>

        <div class="glass-card p-8">
            <h1 class="text-2xl mb-2 text-white"><?= e(t('parolni_unutdim')) ?></h1>
            <p class="text-brand-muted mb-6">
                Telefon raqamingizni kiriting. Yangi parol Telegram orqali yuboriladi.
            </p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm animate-shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
                <div class="mb-5 p-3 rounded-lg bg-green-500/10 border border-green-500/30 text-green-300 text-sm">
                    <?= e($muvaffaqiyat) ?>
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

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                </button>
            </form>
            <?php endif; ?>

            <p class="text-center text-brand-muted mt-6 text-sm">
                <a href="<?= e(SAYT_URL) ?>/login" class="text-blue-400 hover:text-blue-300 font-medium">← <?= e(t('kirish')) ?></a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
