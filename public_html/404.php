<?php
/**
 * AvtoTest Pro — 404 Sahifa topilmadi
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';
http_response_code(404);
$sahifa_sarlavha = t('404_sarlavha');
$body_class      = 'error-page';
require_once __DIR__ . '/includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center fade-up max-w-lg">
        <div class="text-[8rem] leading-none font-display font-black text-white/10 select-none mb-4">404</div>
        <div class="w-20 h-20 mx-auto rounded-2xl bg-blue-500/15 flex items-center justify-center text-5xl mb-6">🔍</div>
        <h1 class="text-3xl font-display font-bold mb-3"><?= e(t('404_sarlavha')) ?></h1>
        <p class="text-brand-muted mb-8 leading-relaxed"><?= e(t('404_tavsif')) ?></p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/" class="btn-primary"><?= e(t('uyga_qaytish')) ?></a>
            <a href="javascript:history.back()" class="btn-ghost"><?= e(t('orqaga')) ?></a>
        </div>
        <p class="text-xs text-brand-muted mt-8">
            Muammo davom etsa, <a href="<?= e(SAYT_URL) ?>/#aloqa" class="text-blue-400 hover:underline">biz bilan bog'laning</a>.
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
