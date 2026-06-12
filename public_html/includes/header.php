<?php
/**
 * VatanParvar Yaypan — Sahifa boshi (header)
 * ------------------------------------------------------------
 * Har bir sahifa boshida `require_once 'includes/header.php'` qilinadi.
 *
 * Sahifaga maxsus o'zgaruvchilarni quyidagicha berish mumkin:
 *   $sahifa_sarlavha = 'Tariflar';
 *   $sahifa_tavsif   = 'Bizning tariflar...';
 *   $body_class      = 'auth-page';
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();
?>
<!DOCTYPE html>
<html lang="<?= str_starts_with($_SESSION['til'] ?? 'uz', 'ru') ? 'ru' : 'uz' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0A0F1E">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e($sahifa_tavsif) ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($sahifa_sarlavha) ?>">
    <meta property="og:description" content="<?= e($sahifa_tavsif) ?>">
    <meta property="og:url" content="<?= e(SAYT_URL . $_SERVER['REQUEST_URI']) ?>">

    <!-- CSRF (AJAX uchun) -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-bg':      '#0A0F1E',
                        'brand-surface': 'rgba(255,255,255,0.05)',
                        'brand-border':  'rgba(255,255,255,0.10)',
                        'brand-primary': '#3B82F6',
                        'brand-success': '#22C55E',
                        'brand-error':   '#EF4444',
                        'brand-text':    '#F4F4FF',
                        'brand-muted':   '#8A99B8'
                    },
                    fontFamily: {
                        'display': ['Manrope', 'system-ui', 'sans-serif'],
                        'sans':    ['Inter', 'system-ui', 'sans-serif']
                    },
                    animation: {
                        'fade-up':  'fadeUp 0.5s ease both',
                        'fade-in':  'fadeIn 0.4s ease both',
                        'shake':    'shake 0.4s ease-in-out',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: 0, transform: 'translateY(20px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                        fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                        shake:  { '0%,100%': { transform:'translateX(0)' }, '25%': { transform:'translateX(-6px)' }, '75%': { transform:'translateX(6px)' } }
                    }
                }
            }
        };
    </script>

    <!-- Alpine.js (CDN) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Manrope va Inter shriftlari -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%233B82F6'/><text x='50%' y='54%' text-anchor='middle' fill='white' font-family='Arial' font-size='34' font-weight='800'>V</text></svg>">

    <style>
        /* ----- DIZAYN TIZIMI ----- */
        :root {
            --clr-bg-dark:    #0A0F1E;
            --clr-surface:    rgba(255,255,255,0.05);
            --clr-border:     rgba(255,255,255,0.10);
            --clr-primary:    #3B82F6;
            --clr-success:    #22C55E;
            --clr-error:      #EF4444;
            --clr-text-main:  #F4F4FF;
            --clr-text-muted: #8A99B8;
        }

        html, body {
            background: var(--clr-bg-dark);
            color: var(--clr-text-main);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.02em;
            font-weight: 700;
        }

        /* ----- Glassmorphism karta ----- */
        .glass-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1rem;
            transition: transform .25s ease, border-color .25s ease;
        }
        .glass-card:hover { border-color: rgba(255,255,255,0.18); }
        .glass-card-hover:hover { transform: translateY(-4px); }

        /* ----- Aurora orqa fon ----- */
        .aurora-bg {
            position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;
        }
        .aurora-bg::before, .aurora-bg::after {
            content: ''; position: absolute; width: 50vw; height: 50vw;
            border-radius: 50%; filter: blur(120px); opacity: 0.35;
        }
        .aurora-bg::before { background: #3B82F6; top: -10vw; left: -10vw; }
        .aurora-bg::after  { background: #6366F1; bottom: -10vw; right: -10vw; }

        /* ----- Tugmalar ----- */
        .btn-primary {
            background: #2563EB;
            color: white; font-weight: 600; padding: .75rem 1.5rem;
            border-radius: .75rem; transition: all .2s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-primary:hover { background: #3B82F6; transform: translateY(-1px); box-shadow: 0 10px 30px -10px rgba(59,130,246,.6); }
        .btn-primary:active { transform: scale(.97); }

        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: white; font-weight: 600; padding: .75rem 1.5rem;
            border-radius: .75rem; border: 1px solid rgba(255,255,255,0.12);
            transition: all .2s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.12); }
        .btn-ghost:active { transform: scale(.97); }

        .btn-danger {
            background: rgba(239,68,68,.15);
            color: #FCA5A5; font-weight: 600; padding: .65rem 1.25rem;
            border-radius: .75rem; border: 1px solid rgba(239,68,68,.30);
            transition: all .2s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-danger:hover { background: rgba(239,68,68,.30); }
        .btn-danger:active { transform: scale(.97); }

        /* ----- Form maydonlari ----- */
        .field {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: .75rem;
            padding: .8rem 1rem;
            color: white;
            width: 100%;
            transition: all .2s ease;
        }
        .field:focus {
            outline: none;
            border-color: #3B82F6;
            background: rgba(59,130,246,0.05);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.20);
        }
        .field::placeholder { color: rgba(255,255,255,0.40); }
        .field-label { display: block; font-size: .85rem; color: var(--clr-text-muted); margin-bottom: .35rem; font-weight: 500; }

        /* ----- Animatsiya yordamchilari ----- */
        .fade-up { animation: fadeUp 0.4s ease both; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

        /* ----- Skroll panel ----- */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.10); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.20); }

        /* ----- Tanlovni rang berish ----- */
        ::selection { background: #3B82F6; color: white; }

        /* ----- Anti-copy himoya (faqat foydalanuvchilarga) ----- */
        .no-select { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen overflow-x-hidden">

<!-- Orqa aurora effekti -->
<div class="aurora-bg" aria-hidden="true"></div>

<?php if ($flash): ?>
    <!-- Flash xabar -->
    <div x-data="{show:true}" x-show="show" x-transition.duration.300ms
         class="fixed top-5 right-5 z-50 max-w-sm">
        <div class="glass-card p-4 flex items-start gap-3 shadow-2xl
                    <?= $flash['tur'] === 'muvaffaqiyat' ? 'border-green-500/40' :
                       ($flash['tur'] === 'xato' ? 'border-red-500/40' : 'border-blue-500/40') ?>">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($flash['tur'] === 'muvaffaqiyat'): ?>
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                <?php elseif ($flash['tur'] === 'xato'): ?>
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                <?php else: ?>
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-1 4a1 1 0 0 1 1-1 1 1 0 0 1 1 1v5a1 1 0 1 1-2 0V9z"/></svg>
                <?php endif; ?>
            </div>
            <p class="text-sm text-white"><?= e($flash['matn']) ?></p>
            <button @click="show=false" class="text-white/40 hover:text-white">×</button>
        </div>
    </div>
<?php endif; ?>
