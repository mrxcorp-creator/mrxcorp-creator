<?php
require_once __DIR__ . '/config/auth.php';

http_response_code(404);
$sahifa_sarlavha = '404 — ' . t('sahifa_topilmadi');
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 text-center">
    <div class="fade-up max-w-md">
        <div class="text-[10rem] sm:text-[14rem] font-display font-extrabold grad-text leading-none mb-4">
            404
        </div>
        <h1 class="text-3xl mb-3"><?= e(t('sahifa_topilmadi')) ?></h1>
        <p class="text-muted mb-8"><?= e(t('sahifa_topilmadi_t')) ?></p>
        <a href="<?= e(SAYT_URL) ?>" class="btn btn-primary"><?= e(t('bosh_sahifa')) ?></a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
