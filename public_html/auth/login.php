<?php
/**
 * VatanParvar Yaypan — Kirish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato              = '';
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
                flash_qoy('muvaffaqiyat', t('salom') . ', ' . $u['ism']);
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

<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding: 2rem 1.25rem;">
    <div style="width:100%; max-width: 28rem;">

        <!-- Logo -->
        <div style="text-align:center; margin-bottom: 2rem;">
            <a href="<?= e(SAYT_URL) ?>"
               style="display:inline-flex; flex-direction:column; align-items:center; gap:.5rem;
                      text-decoration:none;">
                <span style="display:flex; align-items:center; justify-content:center;
                             width:56px; height:56px; border:1px solid #000;
                             font-family:Georgia,serif; font-weight:700; font-size:1.5rem;">V</span>
                <span style="font-family:Georgia,serif; font-weight:700;
                             font-size:1.25rem; color:#000;">
                    <?= e(SAYT_NOMI) ?>
                </span>
            </a>
        </div>

        <div class="b-card" style="padding: 2rem;">
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.75rem; margin-bottom: .35rem;">
                <?= e(t('kirish')) ?>
            </h1>
            <p style="font-size:.9rem; color:#666; margin-bottom: 1.5rem;">
                Akkauntingizga xush kelibsiz
            </p>

            <?php if ($xato): ?>
            <div style="padding: .75rem 1rem; border: 1px solid #000;
                        background: #000; color: #fff; font-size:.85rem;
                        margin-bottom: 1.25rem;">
                <?= e($xato) ?>
            </div>
            <?php endif; ?>

            <form method="POST" x-data="{ loading: false, showPass: false }" @submit="loading = true">
                <?= csrf_input() ?>

                <div style="margin-bottom: 1rem;">
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($telefon_kiritildi) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between;
                                margin-bottom: .4rem;">
                        <label class="field-label" for="parol" style="margin-bottom:0;">
                            <?= e(t('parol')) ?>
                        </label>
                        <a href="<?= e(SAYT_URL) ?>/forgot-password"
                           style="font-size:.78rem; color:#000;
                                  text-decoration:underline; text-underline-offset:3px;">
                            <?= e(t('parolni_unutdim')) ?>
                        </a>
                    </div>
                    <div style="position:relative;">
                        <input id="parol" name="parol"
                               :type="showPass ? 'text' : 'password'"
                               required placeholder="••••••••"
                               class="field" style="padding-right: 3rem;"
                               autocomplete="current-password">
                        <button type="button"
                                @click="showPass = !showPass"
                                style="position:absolute; right:.5rem; top:50%;
                                       transform:translateY(-50%);
                                       background:none; border:none; padding:.4rem;
                                       cursor:pointer; font-size:.78rem;
                                       color:#666; text-decoration:underline;
                                       text-underline-offset:3px;">
                            <span x-text="showPass ? 'Yashirish' : 'Ko\'rsatish'">Ko'rsatish</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"
                        style="width:100%;"
                        :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?> →'">
                        <?= e(t('kirish')) ?> →
                    </span>
                </button>
            </form>

            <div style="border-top: 1px solid #E5E5E5; margin: 1.5rem 0; padding-top: 1rem;
                        text-align: center;">
                <p style="font-size:.875rem; color:#666;">
                    Akkauntingiz yo'qmi?
                    <a href="<?= e(SAYT_URL) ?>/register"
                       style="color:#000; font-weight:500; text-decoration:underline;
                              text-underline-offset:3px; margin-left:.25rem;">
                        <?= e(t('royxatdan_otish')) ?>
                    </a>
                </p>
            </div>
        </div>

        <p style="text-align:center; margin-top: 1rem;">
            <a href="<?= e(SAYT_URL) ?>"
               style="font-size:.78rem; color:#666; text-decoration:none;">
                ← <?= e(t('bosh_sahifa')) ?>
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
