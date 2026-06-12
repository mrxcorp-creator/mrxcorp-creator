<?php
/**
 * 403 — Ruxsat yo'q
 */
require_once __DIR__ . '/config/auth.php';

http_response_code(403);
$sahifa_sarlavha = '403 — Ruxsat yo\'q';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 text-center">
    <div class="fade-up">
        <div class="text-9xl font-display font-bold bg-gradient-to-r from-red-400 to-orange-400 bg-clip-text text-transparent mb-4">
            403
        </div>
        <h1 class="text-3xl mb-3">Ruxsat yo'q</h1>
        <p class="text-brand-muted mb-8 max-w-md mx-auto">
            <?= e(t('ruxsat_yoq')) ?>
        </p>
        <a href="<?= e(SAYT_URL) ?>" class="btn-primary"><?= e(t('bosh_sahifa')) ?></a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
