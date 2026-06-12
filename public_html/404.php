<?php
/**
 * 404 — Sahifa topilmadi
 */
require_once __DIR__ . '/config/auth.php';

http_response_code(404);
$sahifa_sarlavha = '404 — Sahifa topilmadi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 text-center relative">
    <div class="absolute top-20 left-1/4 w-32 h-32 rounded-full bg-gradient-to-br from-sky-200 to-blue-200 opacity-40 blur-2xl floating"></div>
    <div class="absolute bottom-20 right-1/4 w-40 h-40 rounded-full bg-gradient-to-br from-violet-200 to-sky-200 opacity-40 blur-2xl floating" style="animation-delay:1s"></div>

    <div class="scale-in relative">
        <div class="text-[10rem] sm:text-[14rem] font-display font-bold leading-none mb-4 select-none">
            <span class="text-gradient">404</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-display font-bold text-brand-text mb-3">Sahifa topilmadi</h1>
        <p class="text-brand-muted mb-8 max-w-md mx-auto">
            Siz qidirayotgan sahifa mavjud emas yoki ko'chirilgan. Bosh sahifaga qayting.
        </p>
        <a href="<?= e(SAYT_URL) ?>" class="btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <?= e(t('bosh_sahifa')) ?>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
