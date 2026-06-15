<?php
/**
 * AvtoTest Pro — HTML Head + Design System
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
<html lang="<?= $html_lang ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#070C1A">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e(mb_substr($sahifa_tavsif, 0, 155)) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e(SAYT_URL . strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?= e(SAYT_NOMI) ?>">
    <meta property="og:title"       content="<?= e($sahifa_sarlavha) ?>">
    <meta property="og:description" content="<?= e($sahifa_tavsif) ?>">
    <meta property="og:url"         content="<?= e(SAYT_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <!-- Favicon — SVG inline -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%233B82F6'/%3E%3Cstop offset='100%25' stop-color='%237C3AED'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='16' fill='url(%23g)'/%3E%3Ctext x='32' y='44' text-anchor='middle' fill='white' font-family='Arial Black' font-size='34' font-weight='900'%3EA%3C/text%3E%3C/svg%3E">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand':       '#3B82F6',
                    'brand-dark':  '#1D4ED8',
                    'brand-bg':    '#070C1A',
                    'brand-card':  'rgba(255,255,255,0.04)',
                    'brand-text':  '#F1F5FF',
                    'brand-muted': '#6B7CA8',
                },
                fontFamily: {
                    'display': ['Manrope', 'system-ui', 'sans-serif'],
                    'sans':    ['Inter',   'system-ui', 'sans-serif'],
                },
                backdropBlur: { 'xl': '24px', '2xl': '40px' },
                keyframes: {
                    fadeUp:    { from: { opacity: '0', transform: 'translateY(20px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                    fadeIn:    { from: { opacity: '0' },                               to: { opacity: '1' } },
                    scaleIn:   { from: { opacity: '0', transform: 'scale(0.95)' },     to: { opacity: '1', transform: 'scale(1)' } },
                    shake:     { '0%,100%': { transform: 'translateX(0)' }, '20%,60%': { transform: 'translateX(-5px)' }, '40%,80%': { transform: 'translateX(5px)' } },
                    shimmer:   { from: { backgroundPosition: '-200% 0' }, to: { backgroundPosition: '200% 0' } },
                    float:     { '0%,100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-8px)' } },
                    gradMove:  { '0%': { backgroundPosition: '0% 50%' }, '50%': { backgroundPosition: '100% 50%' }, '100%': { backgroundPosition: '0% 50%' } },
                },
                animation: {
                    'fade-up':    'fadeUp 0.5s ease both',
                    'fade-in':    'fadeIn 0.35s ease both',
                    'scale-in':   'scaleIn 0.3s ease both',
                    'shake':      'shake 0.45s ease-in-out',
                    'float':      'float 4s ease-in-out infinite',
                    'grad-move':  'gradMove 6s ease infinite',
                    'shimmer':    'shimmer 2s linear infinite',
                },
            }
        }
    };
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- ═══════════════════════════════════════════
         DIZAYN TIZIMI — CSS
    ═══════════════════════════════════════════ -->
    <style>
        /* ── Reset & Base ───────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:         #070C1A;
            --bg2:        #0D1426;
            --surface:    rgba(255,255,255,0.04);
            --surface2:   rgba(255,255,255,0.07);
            --border:     rgba(255,255,255,0.08);
            --border2:    rgba(255,255,255,0.14);
            --blue:       #3B82F6;
            --blue-dark:  #1D4ED8;
            --blue-glow:  rgba(59,130,246,0.35);
            --violet:     #7C3AED;
            --green:      #10B981;
            --amber:      #F59E0B;
            --red:        #EF4444;
            --text:       #F1F5FF;
            --muted:      #6B7CA8;
            --radius:     14px;
            --radius-sm:  10px;
            --radius-xs:  8px;
        }

        html { background: var(--bg); color: var(--text); scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.025em;
            line-height: 1.2;
            font-weight: 700;
        }

        img { max-width: 100%; }

        /* ── Aurora Background ──────────────────── */
        .aurora {
            position: fixed; inset: 0; z-index: -1;
            pointer-events: none; overflow: hidden;
        }
        .aurora::before {
            content: '';
            position: absolute;
            width: 70vw; height: 60vw;
            top: -20vw; left: -15vw;
            background: radial-gradient(ellipse at center, rgba(59,130,246,0.18) 0%, transparent 65%);
            border-radius: 50%;
        }
        .aurora::after {
            content: '';
            position: absolute;
            width: 55vw; height: 50vw;
            bottom: -15vw; right: -10vw;
            background: radial-gradient(ellipse at center, rgba(124,58,237,0.15) 0%, transparent 65%);
            border-radius: 50%;
        }
        .aurora-mid {
            position: absolute;
            width: 40vw; height: 35vw;
            top: 45%; left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(ellipse at center, rgba(16,185,129,0.06) 0%, transparent 65%);
            border-radius: 50%;
        }

        /* ── Glass Card ─────────────────────────── */
        .glass {
            background: var(--surface);
            backdrop-filter: blur(24px) saturate(150%);
            -webkit-backdrop-filter: blur(24px) saturate(150%);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .glass:hover { border-color: var(--border2); }
        .glass-card {
            background: var(--surface);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: border-color .25s ease, transform .25s ease, box-shadow .25s ease;
        }
        .glass-card:hover { border-color: var(--border2); }
        .glass-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 48px -12px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.08);
        }

        /* ── Gradient Text ──────────────────────── */
        .grad-text {
            background: linear-gradient(135deg, #60A5FA 0%, #A78BFA 50%, #34D399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .grad-text-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #7C3AED 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Buttons ────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: .45rem; font-weight: 600; border-radius: var(--radius-sm);
            border: none; cursor: pointer; transition: all .2s ease;
            white-space: nowrap; line-height: 1;
            padding: .75rem 1.5rem;
            font-family: 'Inter', sans-serif;
            font-size: .9375rem;
        }
        .btn:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }

        .btn-primary {
            background: linear-gradient(135deg, #2563EB 0%, #4F46E5 100%);
            color: #fff;
            box-shadow: 0 4px 16px -4px rgba(59,130,246,.5), inset 0 1px 0 rgba(255,255,255,.15);
        }
        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #1D4ED8 0%, #4338CA 100%);
            box-shadow: 0 8px 24px -4px rgba(59,130,246,.6), inset 0 1px 0 rgba(255,255,255,.15);
            transform: translateY(-1px);
        }
        .btn-primary:active:not(:disabled) { transform: scale(.97); }

        .btn-ghost {
            background: rgba(255,255,255,.06);
            color: #fff;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
        }
        .btn-ghost:hover:not(:disabled) {
            background: rgba(255,255,255,.10);
            border-color: rgba(255,255,255,.20);
        }
        .btn-ghost:active:not(:disabled) { transform: scale(.97); }

        .btn-success {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            color: #fff;
            box-shadow: 0 4px 14px -4px rgba(16,185,129,.45);
        }
        .btn-success:hover:not(:disabled) {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: rgba(239,68,68,.12);
            color: #FCA5A5;
            border: 1px solid rgba(239,68,68,.25);
        }
        .btn-danger:hover:not(:disabled) {
            background: rgba(239,68,68,.22);
            border-color: rgba(239,68,68,.4);
        }

        .btn-sm { padding: .5rem 1rem; font-size: .85rem; border-radius: var(--radius-xs); }
        .btn-xs { padding: .35rem .75rem; font-size: .78rem; border-radius: 6px; }
        .btn-lg { padding: .95rem 2rem; font-size: 1.05rem; }
        .btn-xl { padding: 1.1rem 2.5rem; font-size: 1.1rem; border-radius: 14px; }

        /* ── Form Fields ────────────────────────── */
        .field {
            background: rgba(255,255,255,.04);
            border: 1.5px solid rgba(255,255,255,.09);
            border-radius: var(--radius-sm);
            padding: .8rem 1rem;
            color: var(--text);
            width: 100%;
            font-size: .9375rem;
            font-family: 'Inter', sans-serif;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
            outline: none;
        }
        .field:focus {
            border-color: var(--blue);
            background: rgba(59,130,246,.06);
            box-shadow: 0 0 0 3.5px rgba(59,130,246,.16);
        }
        .field:hover:not(:focus) { border-color: rgba(255,255,255,.15); }
        .field::placeholder { color: rgba(255,255,255,.3); }
        .field:disabled { opacity: .5; cursor: not-allowed; }
        .field option { background: #111827; color: #F1F5FF; }
        textarea.field { resize: vertical; min-height: 80px; }
        select.field { cursor: pointer; }

        .field-label {
            display: block;
            font-size: .8125rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: .35rem;
            letter-spacing: .01em;
        }

        .field-error {
            font-size: .8rem;
            color: #F87171;
            margin-top: .3rem;
            display: flex; align-items: center; gap: .3rem;
        }

        /* ── Badges ─────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: .25rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .02em;
            line-height: 1.4;
        }
        .badge-blue   { background: rgba(59,130,246,.16);  color: #93C5FD; border: 1px solid rgba(59,130,246,.2); }
        .badge-green  { background: rgba(16,185,129,.16);  color: #6EE7B7; border: 1px solid rgba(16,185,129,.2); }
        .badge-yellow { background: rgba(245,158,11,.16);  color: #FCD34D; border: 1px solid rgba(245,158,11,.2); }
        .badge-red    { background: rgba(239,68,68,.16);   color: #FCA5A5; border: 1px solid rgba(239,68,68,.2); }
        .badge-purple { background: rgba(124,58,237,.16);  color: #C4B5FD; border: 1px solid rgba(124,58,237,.2); }
        .badge-cyan   { background: rgba(6,182,212,.16);   color: #67E8F9; border: 1px solid rgba(6,182,212,.2); }
        .badge-gray   { background: rgba(255,255,255,.08); color: #94A3B8; border: 1px solid rgba(255,255,255,.1); }

        /* ── Animations ─────────────────────────── */
        .fade-up  { animation: fadeUp  .5s ease both; }
        .fade-in  { animation: fadeIn  .35s ease both; }
        .scale-in { animation: scaleIn .3s ease both; }
        .animate-shake { animation: shake .45s ease-in-out; }

        @keyframes fadeUp  { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
        @keyframes scaleIn { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
        @keyframes shake   { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }
        @keyframes float   { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes shimmer { from{background-position:-200% 0} to{background-position:200% 0} }

        /* ── Scrollbar ──────────────────────────── */
        ::-webkit-scrollbar       { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.18); }

        /* ── Text Selection ─────────────────────── */
        ::selection { background: rgba(59,130,246,.35); color: #fff; }

        /* ── Alpine cloak ───────────────────────── */
        [x-cloak] { display: none !important; }

        /* ── Helpers ────────────────────────────── */
        .no-select { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
        .tabnum    { font-variant-numeric: tabular-nums; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-4 { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }

        /* ── Gradient borders ───────────────────── */
        .grad-border {
            background: linear-gradient(var(--bg2),var(--bg2)) padding-box,
                        linear-gradient(135deg,rgba(59,130,246,.5),rgba(124,58,237,.5)) border-box;
            border: 1.5px solid transparent;
        }

        /* ── Skeleton loader ────────────────────── */
        .skeleton {
            background: linear-gradient(90deg,rgba(255,255,255,.04) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--radius-xs);
        }

        /* ── Input file styling ─────────────────── */
        input[type="file"].field {
            padding: .6rem 1rem;
        }
        input[type="file"].field::file-selector-button {
            background: rgba(59,130,246,.15);
            color: #93C5FD;
            border: none;
            border-radius: 6px;
            padding: .3rem .75rem;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            margin-right: .75rem;
            transition: background .2s;
        }
        input[type="file"].field::file-selector-button:hover {
            background: rgba(59,130,246,.25);
        }

        /* ── Number input hide arrows ───────────── */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
        input[type="number"] { -moz-appearance: textfield; }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen">

<!-- Aurora background -->
<div class="aurora" aria-hidden="true">
    <div class="aurora-mid"></div>
</div>

<?php if ($flash): ?>
<!-- Flash xabar toast -->
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-5 right-5 z-[999] w-full max-w-sm pointer-events-none"
     x-cloak>
    <div class="glass-card px-4 py-3.5 flex items-start gap-3 pointer-events-auto shadow-2xl
        <?= match($flash['tur'] ?? '') {
            'muvaffaqiyat' => 'border-emerald-500/40 bg-emerald-500/[0.05]',
            'xato'         => 'border-red-500/40 bg-red-500/[0.05]',
            default        => 'border-blue-500/40 bg-blue-500/[0.05]'
        } ?>">
        <div class="text-xl flex-shrink-0 mt-0.5">
            <?= match($flash['tur'] ?? '') {
                'muvaffaqiyat' => '✅', 'xato' => '❌', default => 'ℹ️'
            } ?>
        </div>
        <p class="flex-1 text-sm text-white leading-relaxed"><?= e($flash['matn']) ?></p>
        <button @click="show = false"
                class="flex-shrink-0 w-6 h-6 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/60 hover:text-white transition text-sm">
            ×
        </button>
    </div>
</div>
<?php endif; ?>
