<?php
/**
 * 404 — Sahifa topilmadi
 */
require_once __DIR__ . '/config/auth.php';

http_response_code(404);
$sahifa_sarlavha = '404 — Sahifa topilmadi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 text-center">
    <div class="fade-up">
        <div class="text-9xl font-display font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent mb-4">
            404
        </div>
        <h1 class="text-3xl mb-3">Sahifa topilmadi</h1>
        <p class="text-brand-muted mb-8 max-w-md mx-auto">
            Siz qidirayotgan sahifa mavjud emas yoki ko'chirilgan. Bosh sahifaga qayting.
        </p>
        <a href="<?= e(SAYT_URL) ?>" class="btn-primary"><?= e(t('bosh_sahifa')) ?></a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
