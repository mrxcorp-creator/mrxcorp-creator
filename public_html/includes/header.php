<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();
$til             = $_SESSION['til'] ?? 'uz_latn';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0B1024">

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

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg':         '#070B14',
                        'bg-2':       '#0F1626',
                        'surface':    'rgba(255,255,255,0.04)',
                        'border':     'rgba(255,255,255,0.10)',
                        'cyan':       '#06B6D4',
                        'violet':     '#8B5CF6',
                        'pink':       '#EC4899',
                        'amber':      '#F59E0B',
                        'success':    '#10B981',
                        'danger':     '#EF4444',
                        'text':       '#F1F5F9',
                        'muted':      '#94A3B8'
                    },
                    fontFamily: {
                        'display': ['Manrope', 'system-ui', 'sans-serif'],
                        'sans':    ['Inter', 'system-ui', 'sans-serif']
                    },
                    backgroundImage: {
                        'brand-gradient': 'linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%)',
                        'brand-gradient-soft': 'linear-gradient(135deg, rgba(6,182,212,.18), rgba(139,92,246,.18), rgba(236,72,153,.18))'
                    }
                }
            }
        };
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --c-bg: #070B14;
            --c-bg-2: #0F1626;
            --c-surface: rgba(255,255,255,0.04);
            --c-surface-h: rgba(255,255,255,0.07);
            --c-border: rgba(255,255,255,0.10);
            --c-border-h: rgba(255,255,255,0.20);
            --c-cyan: #06B6D4;
            --c-violet: #8B5CF6;
            --c-pink: #EC4899;
            --c-amber: #F59E0B;
            --c-success: #10B981;
            --c-danger: #EF4444;
            --c-text: #F1F5F9;
            --c-muted: #94A3B8;
            --grad: linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%);
            --grad-soft: linear-gradient(135deg, rgba(6,182,212,.15), rgba(139,92,246,.15), rgba(236,72,153,.15));
        }

        html, body {
            background: var(--c-bg);
            color: var(--c-text);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.02em;
            font-weight: 700;
        }

        .grad-text {
            background: var(--grad);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .grad-bg { background: var(--grad); }
        .grad-bg-soft { background: var(--grad-soft); }

        .glass {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1.25rem;
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }
        .glass:hover { border-color: rgba(255,255,255,0.18); }
        .glass-hover:hover { transform: translateY(-4px); background: rgba(255,255,255,0.06); }

        .glass-strong {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 1.25rem;
        }

        .aurora { position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none; }
        .aurora::before, .aurora::after, .aurora .blob {
            content: ''; position: absolute; width: 50vw; height: 50vw;
            border-radius: 50%; filter: blur(140px); opacity: .35;
            animation: aurora-float 18s ease-in-out infinite;
        }
        .aurora::before { background: #06B6D4; top: -12vw; left: -10vw; }
        .aurora::after  { background: #EC4899; bottom: -12vw; right: -10vw; animation-delay: -6s; }
        .aurora .blob   { background: #8B5CF6; top: 30%; left: 35%; width: 35vw; height: 35vw; animation-delay: -12s; opacity: .25; }
        @keyframes aurora-float {
            0%, 100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(40px, -30px) scale(1.1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .aurora::before, .aurora::after, .aurora .blob { animation: none; }
        }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            font-weight: 600; padding: .8rem 1.5rem; border-radius: .9rem;
            transition: transform .15s ease, box-shadow .25s ease, background .25s ease, opacity .15s ease;
            cursor: pointer; line-height: 1.2;
        }
        .btn:active { transform: scale(.96); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

        .btn-primary {
            background: var(--grad); color: white;
            box-shadow: 0 6px 20px -8px rgba(139,92,246,.6);
        }
        .btn-primary:hover { box-shadow: 0 10px 32px -8px rgba(236,72,153,.7); transform: translateY(-1px); }

        .btn-ghost {
            background: rgba(255,255,255,0.05); color: white;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.10); border-color: rgba(255,255,255,0.25); }

        .btn-danger {
            background: rgba(239,68,68,0.12); color: #FCA5A5;
            border: 1px solid rgba(239,68,68,0.30);
        }
        .btn-danger:hover { background: rgba(239,68,68,0.22); }

        .field {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: .85rem;
            padding: .85rem 1rem;
            color: white; width: 100%;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .field:focus {
            outline: none;
            border-color: var(--c-violet);
            background: rgba(139,92,246,0.06);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.20);
        }
        .field::placeholder { color: rgba(255,255,255,0.35); }
        .field-label {
            display: block; font-size: .85rem; color: var(--c-muted);
            margin-bottom: .4rem; font-weight: 500;
        }

        .chip {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .35rem .9rem; border-radius: 999px;
            font-size: .8rem; font-weight: 500;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
        }
        .chip-grad {
            background: var(--grad-soft);
            border-color: rgba(139,92,246,0.30);
            color: #C4B5FD;
        }

        .fade-up { animation: fadeUp .5s cubic-bezier(.2,.8,.2,1) both; }
        .fade-in { animation: fadeIn .4s ease both; }
        .shake   { animation: shake .4s ease-in-out; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px);} to {opacity:1; transform:translateY(0);} }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes shake  { 0%,100%{transform:translateX(0);} 25%{transform:translateX(-6px);} 75%{transform:translateX(6px);} }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.30); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(139,92,246,0.50); }

        ::selection { background: rgba(139,92,246,0.50); color: white; }

        [x-cloak] { display: none !important; }

        .no-select { -webkit-user-select: none; -moz-user-select: none; user-select: none; }

        .ring-grad {
            background: var(--grad);
            padding: 1px;
            border-radius: 1.25rem;
        }
        .ring-grad > * { border-radius: calc(1.25rem - 1px); background: var(--c-bg-2); }

        .glow-cyan   { box-shadow: 0 0 24px -4px rgba(6,182,212,.55); }
        .glow-violet { box-shadow: 0 0 24px -4px rgba(139,92,246,.55); }
        .glow-pink   { box-shadow: 0 0 24px -4px rgba(236,72,153,.55); }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen overflow-x-hidden">

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
            <p class="text-sm text-white flex-1"><?= e($flash['matn']) ?></p>
            <button @click="show=false" class="text-white/40 hover:text-white text-lg leading-none">×</button>
        </div>
    </div>
<?php endif; ?>
