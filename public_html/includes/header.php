<?php
/**
 * VatanParvar Yaypan — Sahifa boshi (header)
 * ------------------------------------------------------------
 * Yorqin (oq + havorang) zamonaviy tema.
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

// Foydalanuvchi kirill alifbosini tanlagan bo'lsa — sahifaning butun chiqishini transliteratsiya qilamiz
if (($_SESSION['til'] ?? 'uz_latn') === 'uz_cyrl') {
    ob_start('transliteratsiya_filtri');
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0EA5E9">

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
                        'brand-bg':       '#F8FAFC',  /* yorqin oq fon */
                        'brand-bg-soft':  '#F0F9FF',  /* yumshoq havorang fon */
                        'brand-surface':  '#FFFFFF',
                        'brand-border':   '#E2E8F0',  /* slate-200 */
                        'brand-primary':  '#0EA5E9',  /* sky-500 — havorang */
                        'brand-primary2': '#38BDF8',  /* sky-400 */
                        'brand-primary3': '#0284C7',  /* sky-600 */
                        'brand-success':  '#10B981',
                        'brand-error':    '#EF4444',
                        'brand-warning':  '#F59E0B',
                        'brand-text':     '#0F172A',  /* slate-900 */
                        'brand-body':     '#334155',  /* slate-700 */
                        'brand-muted':    '#64748B',  /* slate-500 */
                        'brand-light':    '#94A3B8'   /* slate-400 */
                    },
                    fontFamily: {
                        'display': ['Manrope', 'system-ui', 'sans-serif'],
                        'sans':    ['Inter', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        'soft':    '0 2px 8px -2px rgba(14,165,233,0.08), 0 4px 16px -4px rgba(15,23,42,0.06)',
                        'medium':  '0 4px 16px -4px rgba(14,165,233,0.12), 0 8px 32px -8px rgba(15,23,42,0.08)',
                        'glow':    '0 0 0 4px rgba(14,165,233,0.15), 0 8px 24px -8px rgba(14,165,233,0.5)',
                        'glow-lg': '0 0 0 6px rgba(14,165,233,0.10), 0 20px 40px -12px rgba(14,165,233,0.4)'
                    },
                    animation: {
                        'fade-up':  'fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                        'fade-in':  'fadeIn 0.5s ease both',
                        'fade-down':'fadeDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                        'scale-in': 'scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both',
                        'slide-right':'slideRight 0.5s cubic-bezier(0.16, 1, 0.3, 1) both',
                        'shake':    'shake 0.4s ease-in-out',
                        'float':    'float 3s ease-in-out infinite',
                        'pulse-soft':'pulseSoft 2s ease-in-out infinite',
                        'shimmer':  'shimmer 2s linear infinite',
                        'gradient': 'gradientShift 8s ease infinite'
                    },
                    keyframes: {
                        fadeUp:    { '0%': { opacity: 0, transform: 'translateY(24px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                        fadeDown:  { '0%': { opacity: 0, transform: 'translateY(-16px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                        fadeIn:    { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                        scaleIn:   { '0%': { opacity: 0, transform: 'scale(0.92)' }, '100%': { opacity: 1, transform: 'scale(1)' } },
                        slideRight:{ '0%': { opacity: 0, transform: 'translateX(-24px)' }, '100%': { opacity: 1, transform: 'translateX(0)' } },
                        shake:     { '0%,100%': { transform:'translateX(0)' }, '25%': { transform:'translateX(-6px)' }, '75%': { transform:'translateX(6px)' } },
                        float:     { '0%,100%': { transform:'translateY(0)' }, '50%': { transform:'translateY(-8px)' } },
                        pulseSoft: { '0%,100%': { opacity: 1, transform: 'scale(1)' }, '50%': { opacity: 0.85, transform: 'scale(1.03)' } },
                        shimmer:   { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                        gradientShift: { '0%,100%': { backgroundPosition: '0% 50%' }, '50%': { backgroundPosition: '100% 50%' } }
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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2338BDF8'/><stop offset='1' stop-color='%230284C7'/></linearGradient></defs><rect width='64' height='64' rx='14' fill='url(%23g)'/><text x='50%25' y='54%25' text-anchor='middle' fill='white' font-family='Arial' font-size='34' font-weight='800'>V</text></svg>">

    <!-- PWA -->
    <link rel="manifest" href="<?= e(SAYT_URL) ?>/manifest.json">
    <link rel="apple-touch-icon" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2338BDF8'/><stop offset='1' stop-color='%230284C7'/></linearGradient></defs><rect width='192' height='192' rx='42' fill='url(%23g)'/><text x='50%25' y='54%25' text-anchor='middle' fill='white' font-family='Arial' font-size='102' font-weight='800'>V</text></svg>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">

    <script>
        // Dark mode boshlang'ich (cookie/localStorage'dan)
        (function () {
            try {
                const saved = localStorage.getItem('tema') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                if (saved === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>

    <style>
        /* ============================================================
           DIZAYN TIZIMI — Yorqin (oq + havorang) tema
           ============================================================ */
        :root {
            --clr-bg:         #F8FAFC;
            --clr-bg-soft:    #F0F9FF;
            --clr-surface:    #FFFFFF;
            --clr-border:     #E2E8F0;
            --clr-border-hov: #CBD5E1;
            --clr-primary:    #0EA5E9;
            --clr-primary-2:  #38BDF8;
            --clr-primary-3:  #0284C7;
            --clr-success:    #10B981;
            --clr-error:      #EF4444;
            --clr-text:       #0F172A;
            --clr-body:       #334155;
            --clr-muted:      #64748B;
            --clr-light:      #94A3B8;
        }

        /* ============================================================
           DARK MODE
           ============================================================ */
        html.dark {
            --clr-bg:         #0F172A;
            --clr-bg-soft:    #1E293B;
            --clr-surface:    #1E293B;
            --clr-border:     #334155;
            --clr-border-hov: #475569;
            --clr-text:       #F8FAFC;
            --clr-body:       #CBD5E1;
            --clr-muted:      #94A3B8;
            --clr-light:      #64748B;
        }
        html.dark body { background: var(--clr-bg); color: var(--clr-body); }
        html.dark h1, html.dark h2, html.dark h3, html.dark h4, html.dark h5, html.dark h6 { color: var(--clr-text); }
        html.dark .glass-card {
            background: rgba(30, 41, 59, 0.85);
            border-color: rgba(71, 85, 105, 0.5);
        }
        html.dark .glass-card:hover { border-color: rgba(100, 116, 139, 0.6); }
        html.dark .field {
            background: #1E293B;
            border-color: #334155;
            color: #F8FAFC;
        }
        html.dark .field::placeholder { color: #64748B; }
        html.dark .btn-ghost {
            background: rgba(30, 41, 59, 0.8);
            border-color: #334155;
            color: #F8FAFC;
        }
        html.dark .btn-ghost:hover { background: #1E293B; border-color: var(--clr-primary); }
        html.dark .aurora-bg {
            background:
                radial-gradient(ellipse 800px 600px at 10% -10%, rgba(56,189,248,0.15), transparent 50%),
                radial-gradient(ellipse 700px 500px at 90% 10%, rgba(99,102,241,0.12), transparent 50%),
                radial-gradient(ellipse 600px 400px at 50% 100%, rgba(139,92,246,0.10), transparent 50%),
                linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
        }
        html.dark nav.sticky { background: rgba(30, 41, 59, 0.85) !important; }
        html.dark nav.sticky a { color: var(--clr-body); }
        html.dark .text-brand-text { color: var(--clr-text); }
        html.dark .text-brand-body { color: var(--clr-body); }
        html.dark .text-brand-muted { color: var(--clr-muted); }
        html.dark .border-brand-border { border-color: var(--clr-border); }
        html.dark .bg-white { background: var(--clr-surface); }
        html.dark .bg-sky-50 { background: rgba(56, 189, 248, 0.08); }
        html.dark .bg-sky-50\/30 { background: rgba(56, 189, 248, 0.05); }
        html.dark .bg-sky-50\/50 { background: rgba(56, 189, 248, 0.07); }
        html.dark .hover\:bg-sky-50:hover { background: rgba(56, 189, 248, 0.12); }
        html.dark .hover\:bg-sky-50\/50:hover { background: rgba(56, 189, 248, 0.08); }
        html.dark .bg-emerald-50 { background: rgba(16, 185, 129, 0.10); }
        html.dark .bg-rose-50 { background: rgba(239, 68, 68, 0.10); }
        html.dark .bg-amber-50 { background: rgba(251, 146, 60, 0.10); }
        html.dark .bg-violet-50 { background: rgba(139, 92, 246, 0.10); }
        html.dark .bg-brand-bg-soft { background: rgba(30, 41, 59, 0.5); }

        :root {
            --shadow-sm:  0 1px 2px rgba(15,23,42,.04);
            --shadow:     0 2px 8px -2px rgba(14,165,233,.08), 0 4px 16px -4px rgba(15,23,42,.06);
            --shadow-md:  0 4px 16px -4px rgba(14,165,233,.12), 0 8px 32px -8px rgba(15,23,42,.08);
            --shadow-lg:  0 12px 32px -8px rgba(14,165,233,.18), 0 20px 48px -12px rgba(15,23,42,.10);
            --shadow-glow:0 0 0 4px rgba(14,165,233,.15), 0 8px 24px -8px rgba(14,165,233,.5);
        }

        * { -webkit-tap-highlight-color: transparent; }

        html, body {
            background: var(--clr-bg);
            color: var(--clr-body);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.02em;
            font-weight: 700;
            color: var(--clr-text);
        }

        a { color: inherit; text-decoration: none; }

        /* ============================================================
           ORQA FON — Animatsiyali yumshoq mesh gradient
           ============================================================ */
        .aurora-bg {
            position: fixed; inset: 0; z-index: -10; overflow: hidden; pointer-events: none;
            background:
                radial-gradient(ellipse 800px 600px at 10% -10%, rgba(56,189,248,0.18), transparent 50%),
                radial-gradient(ellipse 700px 500px at 90% 10%, rgba(14,165,233,0.14), transparent 50%),
                radial-gradient(ellipse 600px 400px at 50% 100%, rgba(99,102,241,0.10), transparent 50%),
                linear-gradient(180deg, #F0F9FF 0%, #F8FAFC 100%);
        }
        .aurora-bg::before {
            content: ''; position: absolute;
            top: 20%; right: -10%;
            width: 50vw; height: 50vw;
            background: radial-gradient(circle, rgba(14,165,233,0.20), transparent 70%);
            border-radius: 50%; filter: blur(60px);
            animation: float 12s ease-in-out infinite;
        }
        .aurora-bg::after {
            content: ''; position: absolute;
            bottom: 10%; left: -10%;
            width: 45vw; height: 45vw;
            background: radial-gradient(circle, rgba(165,180,252,0.18), transparent 70%);
            border-radius: 50%; filter: blur(80px);
            animation: float 14s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%,100% { transform: translateY(0) translateX(0); }
            50%     { transform: translateY(-30px) translateX(20px); }
        }

        /* ============================================================
           KARTALAR — Glassmorphism + soft shadow
           ============================================================ */
        .glass-card {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--clr-border);
            border-radius: 1.25rem;
            box-shadow: var(--shadow);
            transition: transform .35s cubic-bezier(.16,1,.3,1),
                        box-shadow .35s cubic-bezier(.16,1,.3,1),
                        border-color .25s ease;
        }
        .glass-card:hover {
            border-color: var(--clr-border-hov);
        }
        .glass-card-hover:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(14,165,233,0.30);
        }

        /* ============================================================
           TUGMALAR — Zamonaviy va silliq
           ============================================================ */
        .btn-primary {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #38BDF8 0%, #0EA5E9 50%, #0284C7 100%);
            background-size: 200% 200%;
            color: white; font-weight: 600;
            padding: .85rem 1.65rem;
            border-radius: .85rem;
            display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
            box-shadow: 0 4px 14px -2px rgba(14,165,233,0.45), 0 0 0 1px rgba(14,165,233,0.15) inset;
            transition: all .3s cubic-bezier(.16,1,.3,1);
            cursor: pointer; border: 0;
        }
        .btn-primary:hover {
            background-position: 100% 50%;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -4px rgba(14,165,233,0.55), 0 0 0 1px rgba(14,165,233,0.25) inset;
        }
        .btn-primary:active {
            transform: translateY(0) scale(.98);
            box-shadow: 0 2px 8px -2px rgba(14,165,233,0.5);
        }
        .btn-primary::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.35) 50%, transparent 70%);
            transform: translateX(-150%);
            transition: transform .8s ease;
        }
        .btn-primary:hover::after { transform: translateX(150%); }

        .btn-ghost {
            background: rgba(255,255,255,0.7);
            color: var(--clr-text); font-weight: 600;
            padding: .8rem 1.5rem;
            border-radius: .85rem;
            border: 1px solid var(--clr-border);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            transition: all .25s cubic-bezier(.16,1,.3,1);
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .btn-ghost:hover {
            background: white;
            border-color: var(--clr-primary-2);
            color: var(--clr-primary-3);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        .btn-ghost:active { transform: scale(.97); }

        .btn-danger {
            background: rgba(239,68,68,.08);
            color: var(--clr-error); font-weight: 600;
            padding: .7rem 1.3rem;
            border-radius: .85rem;
            border: 1px solid rgba(239,68,68,.25);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            transition: all .25s ease;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: rgba(239,68,68,.15);
            border-color: rgba(239,68,68,.45);
            transform: translateY(-1px);
        }
        .btn-danger:active { transform: scale(.97); }

        /* ============================================================
           FORMA MAYDONLARI
           ============================================================ */
        .field {
            background: white;
            border: 1.5px solid var(--clr-border);
            border-radius: .85rem;
            padding: .85rem 1.05rem;
            color: var(--clr-text);
            width: 100%;
            font-size: 0.95rem;
            transition: all .25s ease;
            box-shadow: var(--shadow-sm);
        }
        .field:hover {
            border-color: var(--clr-border-hov);
        }
        .field:focus {
            outline: none;
            border-color: var(--clr-primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(14,165,233,0.12), var(--shadow-sm);
        }
        .field::placeholder { color: var(--clr-light); }
        .field-label {
            display: block;
            font-size: .85rem;
            color: var(--clr-body);
            margin-bottom: .4rem;
            font-weight: 600;
        }

        /* ============================================================
           ANIMATSIYALAR — Zamonaviy va silliq
           ============================================================ */
        .fade-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        @keyframes fadeUp {
            0%   { opacity: 0; transform: translateY(24px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .scale-in { animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        @keyframes scaleIn {
            0%   { opacity: 0; transform: scale(0.92); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Stagger delays */
        .stagger-1 { animation-delay: .05s; }
        .stagger-2 { animation-delay: .10s; }
        .stagger-3 { animation-delay: .15s; }
        .stagger-4 { animation-delay: .20s; }
        .stagger-5 { animation-delay: .25s; }
        .stagger-6 { animation-delay: .30s; }

        /* Gradient text */
        .text-gradient {
            background: linear-gradient(135deg, #0EA5E9 0%, #6366F1 50%, #8B5CF6 100%);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            animation: gradientShift 6s ease infinite;
        }
        @keyframes gradientShift {
            0%,100% { background-position: 0% 50%; }
            50%     { background-position: 100% 50%; }
        }

        /* Underline link animatsiyasi */
        .link-anim { position: relative; }
        .link-anim::after {
            content: '';
            position: absolute; bottom: -2px; left: 0;
            width: 0; height: 2px;
            background: var(--clr-primary);
            transition: width .3s cubic-bezier(.16,1,.3,1);
        }
        .link-anim:hover::after { width: 100%; }

        /* Shimmer skeleton */
        .shimmer {
            background: linear-gradient(90deg, #F1F5F9 0%, #E2E8F0 50%, #F1F5F9 100%);
            background-size: 200% 100%;
            animation: shimmer 1.8s linear infinite;
        }
        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Float */
        .floating { animation: floatY 3.5s ease-in-out infinite; }
        @keyframes floatY {
            0%,100% { transform: translateY(0); }
            50%     { transform: translateY(-10px); }
        }

        /* ============================================================
           SCROLLBAR
           ============================================================ */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #CBD5E1, #94A3B8);
            border-radius: 5px;
            border: 2px solid #F8FAFC;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #38BDF8, #0EA5E9);
        }

        /* Tanlovni rang berish */
        ::selection { background: rgba(14,165,233,0.25); color: var(--clr-text); }

        /* No-select utility */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        /* x-cloak Alpine */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen overflow-x-hidden text-brand-body">

<!-- Yumshoq aurora orqa fon -->
<div class="aurora-bg" aria-hidden="true"></div>

<?php if ($flash): ?>
    <!-- Flash xabar — toast tipida o'ngda yuqorida -->
    <div x-data="{show:true}"
         x-init="setTimeout(() => show = false, 5000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-400"
         x-transition:enter-start="opacity-0 translate-x-12"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-12"
         class="fixed top-5 right-5 z-50 max-w-sm">
        <div class="glass-card p-4 flex items-start gap-3 shadow-lg
                    <?= $flash['tur'] === 'muvaffaqiyat' ? 'border-l-4 border-l-emerald-500' :
                       ($flash['tur'] === 'xato' ? 'border-l-4 border-l-rose-500' : 'border-l-4 border-l-sky-500') ?>">
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($flash['tur'] === 'muvaffaqiyat'): ?>
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                    </div>
                <?php elseif ($flash['tur'] === 'xato'): ?>
                    <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                    </div>
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-sky-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-1 4a1 1 0 0 1 1-1 1 1 0 0 1 1 1v5a1 1 0 1 1-2 0V9z"/></svg>
                    </div>
                <?php endif; ?>
            </div>
            <p class="text-sm text-brand-text flex-1 pt-1.5"><?= e($flash['matn']) ?></p>
            <button @click="show=false" class="text-brand-light hover:text-brand-text transition flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
<?php endif; ?>
