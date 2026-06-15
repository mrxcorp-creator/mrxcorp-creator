<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();
$til             = $_SESSION['til'] ?? 'uz_latn';

$css_versiya = is_file(__DIR__ . '/../assets/css/style.css')
    ? filemtime(__DIR__ . '/../assets/css/style.css') : time();
$js_versiya = is_file(__DIR__ . '/../assets/js/app.js')
    ? filemtime(__DIR__ . '/../assets/js/app.js') : time();

$csp_nonce = bin2hex(random_bytes(8));
$csp = "default-src 'self'; "
     . "script-src 'self' 'nonce-{$csp_nonce}'; "
     . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
     . "font-src 'self' https://fonts.gstatic.com data:; "
     . "img-src 'self' data: https:; "
     . "connect-src 'self'; "
     . "frame-ancestors 'self'; "
     . "base-uri 'self'; "
     . "form-action 'self'";
header("Content-Security-Policy: {$csp}");
?>
<!DOCTYPE html>
<html lang="uz" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0B1024" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#F8FAFC" media="(prefers-color-scheme: light)">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e($sahifa_tavsif) ?>">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($sahifa_sarlavha) ?>">
    <meta property="og:description" content="<?= e($sahifa_tavsif) ?>">
    <meta property="og:url" content="<?= e(SAYT_URL . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:image" content="<?= e(SAYT_URL) ?>/assets/img/og-cover.svg">

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg">
    <link rel="apple-touch-icon" href="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg">
    <link rel="manifest" href="<?= e(SAYT_URL) ?>/manifest.webmanifest">
    <link rel="canonical" href="<?= e(SAYT_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(SAYT_URL) ?>/assets/css/style.css?v=<?= $css_versiya ?>">

    <script nonce="<?= $csp_nonce ?>" src="<?= e(SAYT_URL) ?>/assets/js/app.js?v=<?= $js_versiya ?>"></script>
    <script defer nonce="<?= $csp_nonce ?>" src="<?= e(SAYT_URL) ?>/assets/js/alpine.min.js?v=1"></script>
</head>
<body class="<?= e($body_class) ?> min-h-screen overflow-x-hidden bg-bg text-text">

<div class="aurora" aria-hidden="true"><div class="blob"></div></div>

<?php if ($flash): ?>
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false, 5000)"
         x-transition.duration.300ms
         class="fixed top-5 right-5 z-50 max-w-sm">
        <div class="glass-strong p-4 flex items-start gap-3 shadow-2xl
                    <?= $flash['tur'] === 'muvaffaqiyat' ? '!border-success/40' :
                       ($flash['tur'] === 'xato' ? '!border-danger/40' : '!border-violet/40') ?>">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($flash['tur'] === 'muvaffaqiyat'): ?>
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                <?php elseif ($flash['tur'] === 'xato'): ?>
                    <svg class="w-5 h-5 text-danger" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                <?php else: ?>
                    <svg class="w-5 h-5 text-violet" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-1 4a1 1 0 0 1 1-1 1 1 0 0 1 1 1v5a1 1 0 1 1-2 0V9z"/></svg>
                <?php endif; ?>
            </div>
            <p class="text-sm flex-1"><?= e($flash['matn']) ?></p>
            <button @click="show=false" class="text-muted hover:text-text text-lg leading-none">×</button>
        </div>
    </div>
<?php endif; ?>
