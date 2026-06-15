<?php
require_once __DIR__ . '/config/auth.php';

http_response_code(403);
$sahifa_sarlavha = '403 — ' . t('ruxsat_yoq');
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 text-center">
    <div class="fade-up max-w-md">
        <div class="text-[10rem] sm:text-[14rem] font-display font-extrabold leading-none mb-4"
             style="background: linear-gradient(135deg, #EF4444, #F59E0B); -webkit-background-clip: text; background-clip: text; color: transparent;">
            403
        </div>
        <h1 class="text-3xl mb-3"><?= e(t('ruxsat_yoq')) ?></h1>
        <p class="text-muted mb-8"><?= e(t('ruxsat_yoq')) ?></p>
        <a href="<?= e(SAYT_URL) ?>" class="btn btn-primary"><?= e(t('bosh_sahifa')) ?></a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
