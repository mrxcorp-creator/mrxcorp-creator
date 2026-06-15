<?php
/**
 * AvtoTest Pro — Sahifa boshi (HTML head + aurora bg + flash)
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();

$html_lang = match($_SESSION['til'] ?? TIL_DEFAULT) {
    'ru'      => 'ru',
    'uz_cyrl' => 'uz-Cyrl',
    default   => 'uz-Latn',
};
?>
<!DOCTYPE html>
<html lang="<?= $html_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#080D1A">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e(mb_substr($sahifa_tavsif, 0, 160)) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e(SAYT_URL . strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?= e(SAYT_NOMI) ?>">
    <meta property="og:title"       content="<?= e($sahifa_sarlavha) ?>">
    <meta property="og:description" content="<?= e($sahifa_tavsif) ?>">
    <meta property="og:url"         content="<?= e(SAYT_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <!-- CSRF (AJAX uchun) -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-bg':      '#080D1A',
                    'brand-card':    'rgba(255,255,255,0.04)',
                    'brand-border':  'rgba(255,255,255,0.09)',
                    'brand-primary': '#3B82F6',
                    'brand-success': '#22C55E',
                    'brand-warning': '#F59E0B',
                    'brand-error':   '#EF4444',
                    'brand-text':    '#F0F4FF',
                    'brand-muted':   '#7E8FB0',
                },
                fontFamily: {
                    'display': ['Manrope', 'system-ui', 'sans-serif'],
                    'sans':    ['Inter', 'system-ui', 'sans-serif'],
                },
                animation: {
                    'fade-up':  'fadeUp 0.45s ease both',
                    'fade-in':  'fadeIn 0.3s ease both',
                    'shake':    'shake 0.4s ease-in-out',
                    'pulse-slow': 'pulse 3s cubic-bezier(0.4,0,0.6,1) infinite',
                },
                keyframes: {
                    fadeUp:  { '0%': {opacity:'0', transform:'translateY(18px)'}, '100%': {opacity:'1', transform:'translateY(0)'} },
                    fadeIn:  { '0%': {opacity:'0'}, '100%': {opacity:'1'} },
                    shake:   { '0%,100%': {transform:'translateX(0)'}, '20%,60%': {transform:'translateX(-6px)'}, '40%,80%': {transform:'translateX(6px)'} },
                },
                boxShadow: {
                    'glow-blue': '0 0 40px -10px rgba(59,130,246,0.5)',
                    'glow-sm':   '0 0 20px -8px rgba(59,130,246,0.4)',
                },
            }
        }
    };
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='%232563EB'/><text x='50%' y='56%' text-anchor='middle' dominant-baseline='middle' fill='white' font-family='Arial Black,sans-serif' font-size='30' font-weight='900'>A</text></svg>">

    <style>
        /* ===== DIZAYN TIZIMI ===== */
        :root {
            --bg:        #080D1A;
            --surface:   rgba(255,255,255,0.04);
            --border:    rgba(255,255,255,0.09);
            --primary:   #3B82F6;
            --primary-h: #2563EB;
            --success:   #22C55E;
            --error:     #EF4444;
            --warning:   #F59E0B;
            --text:      #F0F4FF;
            --muted:     #7E8FB0;
            --radius:    1rem;
            --radius-sm: .75rem;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        h1,h2,h3,h4,h5,h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.025em;
            font-weight: 700;
        }

        /* ===== Glassmorphism karta ===== */
        .glass-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: var(--radius);
            transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
        }
        .glass-card:hover { border-color: rgba(255,255,255,0.15); }
        .glass-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.4);
        }

        /* ===== Aurora orqa fon ===== */
        .aurora-bg {
            position: fixed; inset: 0; z-index: -1;
            overflow: hidden; pointer-events: none;
        }
        .aurora-bg span {
            position: absolute; border-radius: 50%;
            filter: blur(100px); opacity: 0.25;
        }
        .aurora-bg span:nth-child(1) {
            width: 55vw; height: 55vw;
            background: radial-gradient(circle, #2563EB, transparent 70%);
            top: -15vw; left: -10vw;
        }
        .aurora-bg span:nth-child(2) {
            width: 45vw; height: 45vw;
            background: radial-gradient(circle, #6366F1, transparent 70%);
            bottom: -12vw; right: -8vw;
        }
        .aurora-bg span:nth-child(3) {
            width: 30vw; height: 30vw;
            background: radial-gradient(circle, #0EA5E9, transparent 70%);
            top: 40%; left: 50%; transform: translate(-50%,-50%);
            opacity: 0.12;
        }

        /* ===== Tugmalar ===== */
        .btn-primary {
            background: linear-gradient(135deg, #2563EB, #3B82F6);
            color: #fff; font-weight: 600;
            padding: .75rem 1.5rem; border-radius: var(--radius-sm);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            transition: all .2s ease; border: none; cursor: pointer;
            box-shadow: 0 4px 15px -3px rgba(59,130,246,0.4);
        }
        .btn-primary:hover  { background: linear-gradient(135deg,#1D4ED8,#2563EB); transform: translateY(-1px); box-shadow: 0 8px 25px -5px rgba(59,130,246,0.5); }
        .btn-primary:active { transform: scale(.97); }
        .btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }

        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: #fff; font-weight: 600;
            padding: .75rem 1.5rem; border-radius: var(--radius-sm);
            border: 1px solid rgba(255,255,255,0.11);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            transition: all .2s ease; cursor: pointer;
        }
        .btn-ghost:hover  { background: rgba(255,255,255,0.11); border-color: rgba(255,255,255,0.2); }
        .btn-ghost:active { transform: scale(.97); }
        .btn-ghost:disabled { opacity:.4; cursor:not-allowed; }

        .btn-success {
            background: linear-gradient(135deg, #16A34A, #22C55E);
            color: #fff; font-weight: 600;
            padding: .65rem 1.25rem; border-radius: var(--radius-sm);
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            transition: all .2s ease; cursor: pointer;
            box-shadow: 0 4px 12px -3px rgba(34,197,94,0.35);
        }
        .btn-success:hover { background: linear-gradient(135deg,#15803D,#16A34A); transform: translateY(-1px); }

        .btn-danger {
            background: rgba(239,68,68,.12);
            color: #FCA5A5; font-weight: 600;
            padding: .65rem 1.25rem; border-radius: var(--radius-sm);
            border: 1px solid rgba(239,68,68,.25);
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            transition: all .2s ease; cursor: pointer;
        }
        .btn-danger:hover { background: rgba(239,68,68,.25); border-color: rgba(239,68,68,.4); }

        /* ===== Form maydonlari ===== */
        .field {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: var(--radius-sm);
            padding: .8rem 1rem;
            color: var(--text);
            width: 100%;
            transition: all .2s ease;
            font-size: .9375rem;
        }
        .field:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(59,130,246,0.06);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.18);
        }
        .field::placeholder { color: rgba(255,255,255,0.35); }
        .field:disabled { opacity: .5; cursor: not-allowed; }
        .field option { background: #1E293B; color: #fff; }

        .field-label {
            display: block;
            font-size: .83rem;
            color: var(--muted);
            margin-bottom: .35rem;
            font-weight: 500;
            letter-spacing: .01em;
        }

        /* ===== Animatsiya yordamchilari ===== */
        .fade-up { animation: fadeUp 0.45s ease both; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }

        /* ===== Badge ===== */
        .badge {
            display: inline-flex; align-items: center;
            padding: .2rem .65rem; border-radius: 999px;
            font-size: .75rem; font-weight: 600; line-height: 1.4;
        }
        .badge-blue   { background: rgba(59,130,246,.18);  color: #93C5FD; }
        .badge-green  { background: rgba(34,197,94,.18);   color: #86EFAC; }
        .badge-yellow { background: rgba(245,158,11,.18);  color: #FCD34D; }
        .badge-red    { background: rgba(239,68,68,.18);   color: #FCA5A5; }
        .badge-purple { background: rgba(168,85,247,.18);  color: #D8B4FE; }
        .badge-gray   { background: rgba(255,255,255,.08); color: #94A3B8; }

        /* ===== Scrollbar ===== */
        ::-webkit-scrollbar       { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.18); }

        /* ===== Tanlov rangi ===== */
        ::selection { background: rgba(59,130,246,.4); color: #fff; }

        /* ===== Alpine x-cloak ===== */
        [x-cloak] { display: none !important; }

        /* ===== No-select (test sahifasi) ===== */
        .no-select { -webkit-user-select:none; -moz-user-select:none; user-select:none; }

        /* ===== Jadvalli raqam ===== */
        .tabnum { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen">

<!-- Aurora orqa fon -->
<div class="aurora-bg" aria-hidden="true">
    <span></span><span></span><span></span>
</div>

<?php if ($flash): ?>
<!-- Flash xabar -->
<div x-data="{show:true}"
     x-show="show"
     x-init="setTimeout(()=>show=false, 5000)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-5 right-5 z-[200] w-full max-w-sm pointer-events-none"
     x-cloak>
    <div class="glass-card px-4 py-3 flex items-start gap-3 shadow-2xl pointer-events-auto
        <?= match($flash['tur'] ?? '') {
            'muvaffaqiyat' => 'border-green-500/40 bg-green-500/5',
            'xato'         => 'border-red-500/40 bg-red-500/5',
            default        => 'border-blue-500/40 bg-blue-500/5'
        } ?>">
        <span class="text-xl flex-shrink-0 mt-0.5">
            <?= match($flash['tur'] ?? '') { 'muvaffaqiyat' => '✅', 'xato' => '❌', default => 'ℹ️' } ?>
        </span>
        <p class="text-sm text-white flex-1 leading-relaxed"><?= e($flash['matn']) ?></p>
        <button @click="show=false" class="text-white/40 hover:text-white text-lg leading-none flex-shrink-0">×</button>
    </div>
</div>
<?php endif; ?>
