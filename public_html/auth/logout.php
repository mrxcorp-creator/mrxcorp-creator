<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_tekshir(post('csrf_token'))) {
    tizimdan_chiqish();
    yonaltir(SAYT_URL . '/');
}

$f = joriy_foydalanuvchi();
if (!$f) {
    yonaltir(SAYT_URL . '/');
}

$sahifa_sarlavha = t('chiqish');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="ring-grad max-w-sm w-full fade-up">
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-danger/15 text-danger flex items-center justify-center mb-4 text-3xl">🚪</div>
            <h1 class="text-2xl font-display font-bold mb-2"><?= e(t('chiqish')) ?></h1>
            <p class="text-muted mb-6 text-sm"><?= e(t('tasdiqlaysizmi')) ?></p>

            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <button type="submit" class="btn btn-danger w-full"><?= e(t('chiqish')) ?></button>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="btn btn-ghost w-full"><?= e(t('bekor_qilish')) ?></a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
