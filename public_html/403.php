<?php
/**
 * VatanParvar Yaypan — 403 Kirish taqiqlangan
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';
http_response_code(403);
$sahifa_sarlavha = t('403_sarlavha');
$body_class      = 'error-page';
require_once __DIR__ . '/includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center fade-up max-w-lg">
        <div class="text-[8rem] leading-none font-display font-black text-white/10 select-none mb-4">403</div>
        <div class="w-20 h-20 mx-auto rounded-2xl bg-red-500/15 flex items-center justify-center text-5xl mb-6">🚫</div>
        <h1 class="text-3xl font-display font-bold mb-3"><?= e(t('403_sarlavha')) ?></h1>
        <p class="text-brand-muted mb-8 leading-relaxed"><?= e(t('403_tavsif')) ?></p>
        <div class="flex flex-wrap gap-3 justify-center">
            <?php if (joriy_foydalanuvchi()): ?>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="btn-primary">Dashboard →</a>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn-primary"><?= e(t('kirish')) ?></a>
            <?php endif; ?>
            <a href="<?= e(SAYT_URL) ?>/" class="btn-ghost"><?= e(t('uyga_qaytish')) ?></a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
