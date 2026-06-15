<?php
/**
 * VatanParvar Yaypan — Parolni tiklash
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

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
                'SELECT id, ism, telegram_id FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if (!$u) {
                $muvaffaqiyat = "Agar bu raqam ro'yxatdan o'tgan bo'lsa, parol yuborildi.";
            } elseif (!$u['telegram_id']) {
                $aloqa = sozlama('aloqa_telefon', '');
                $xato  = "Bu akkaunt Telegram'ga bog'lanmagan."
                       . ($aloqa ? " Admin: $aloqa" : '');
            } else {
                $yangi_parol = parol_generat(10);
                $hash        = password_hash($yangi_parol, PASSWORD_BCRYPT, ['cost' => 12]);
                db_bajar(
                    'UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?',
                    [$hash, (int)$u['id']]
                );
                $xabar = "🔐 <b>Yangi vaqtinchalik parol:</b>\n\n"
                       . "<code>{$yangi_parol}</code>\n\n"
                       . "Kirganingizdan so'ng profildan o'zgartiring.";
                $yub = telegram_yubor((int)$u['telegram_id'], $xabar);
                $muvaffaqiyat = $yub
                    ? 'Yangi parol Telegram botingizga yuborildi.'
                    : 'Telegram xabar yuborishda muammo yuzaga keldi.';
            }
        }
    }
}

$sahifa_sarlavha = t('parolni_unutdim');
$body_class      = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:2rem 1.25rem;">
    <div style="width:100%; max-width: 28rem;">

        <div style="text-align:center; margin-bottom: 2rem;">
            <a href="<?= e(SAYT_URL) ?>"
               style="display:inline-flex; flex-direction:column; align-items:center; gap:.5rem;
                      text-decoration:none;">
                <span style="display:flex; align-items:center; justify-content:center;
                             width:56px; height:56px; border:1px solid #000;
                             font-family:Georgia,serif; font-weight:700; font-size:1.5rem;">V</span>
                <span style="font-family:Georgia,serif; font-weight:700;
                             font-size:1.25rem;"><?= e(SAYT_NOMI) ?></span>
            </a>
        </div>

        <div class="b-card" style="padding: 2rem;">
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.5rem; margin-bottom: .35rem;">
                <?= e(t('parolni_unutdim')) ?>
            </h1>
            <p style="font-size:.875rem; color:#666; margin-bottom: 1.5rem;">
                Yangi parol Telegram orqali yuboriladi
            </p>

            <?php if ($xato): ?>
            <div style="padding: .75rem 1rem; border: 1px solid #000;
                        background: #000; color: #fff; font-size:.85rem;
                        margin-bottom: 1.25rem;">
                <?= e($xato) ?>
            </div>
            <?php endif; ?>

            <?php if ($muvaffaqiyat): ?>
            <div style="padding: 1rem; border: 1px solid #000;
                        background: #F5F5F5; font-size:.875rem;
                        margin-bottom: 1.25rem;">
                <?= e($muvaffaqiyat) ?>
            </div>
            <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-primary"
               style="width:100%;">
                <?= e(t('kirish')) ?> →
            </a>

            <?php else: ?>

            <p style="font-size:.875rem; color:#444; margin-bottom: 1.25rem; line-height:1.6;">
                Akkauntingizga Telegram bog'langan bo'lishi kerak.
                Telefon raqamingizni kiriting.
            </p>

            <form method="POST"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                <?= csrf_input() ?>

                <div style="margin-bottom: 1.25rem;">
                    <label class="field-label"><?= e(t('telefon')) ?></label>
                    <input name="telefon" type="tel" required
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <button type="submit" class="btn btn-primary btn-lg"
                        style="width:100%;" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'">
                        <?= e(t('tasdiqlash')) ?>
                    </span>
                </button>
            </form>

            <?php endif; ?>

            <p style="text-align:center; margin-top: 1.5rem; font-size:.875rem;">
                <a href="<?= e(SAYT_URL) ?>/login"
                   style="color:#000; text-decoration:underline; text-underline-offset:3px;">
                    ← <?= e(t('kirish')) ?>
                </a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
