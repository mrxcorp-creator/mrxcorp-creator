<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';
http_response_code(404);
$sahifa_sarlavha = t('404_sarlavha');
require_once __DIR__ . '/includes/header.php';
?>
<div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1.25rem;">
    <div style="text-align:center; max-width: 32rem;">
        <div style="font-family:Georgia,serif; font-weight:700;
                    font-size: clamp(6rem, 18vw, 11rem); line-height: 1;
                    color: #000; margin-bottom: 1rem;">
            404
        </div>
        <div style="border-top: 1px solid #000; padding-top: 1.5rem;">
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.75rem; margin-bottom: 1rem;">
                <?= e(t('404_sarlavha')) ?>
            </h1>
            <p style="font-size: 1rem; color: #555; line-height: 1.6;
                      margin-bottom: 2rem;">
                <?= e(t('404_tavsif')) ?>
            </p>
            <div style="display:flex; gap:.75rem; justify-content:center; flex-wrap:wrap;">
                <a href="<?= e(SAYT_URL) ?>/" class="btn btn-primary">
                    <?= e(t('uyga_qaytish')) ?>
                </a>
                <a href="javascript:history.back()" class="btn btn-ghost">
                    ← <?= e(t('orqaga')) ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
