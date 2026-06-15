<?php
/**
 * VatanParvar Yaypan — HTML Head + Dizayn tizimi
 * Light / Dark mode qo'llab-quvvatlash
 * Google Fonts olib tashlandi (tezlik uchun — system fonts ishlatiladi)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();

$html_lang = match($_SESSION['til'] ?? TIL_DEFAULT) {
    'uz_cyrl' => 'uz-Cyrl',
    default   => 'uz-Latn',
};
?>
<!DOCTYPE html>
<html lang="<?= $html_lang ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0A0F1E" id="meta-theme">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e(mb_substr($sahifa_tavsif, 0, 155)) ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?= e(SAYT_NOMI) ?>">
    <meta property="og:title"       content="<?= e($sahifa_sarlavha) ?>">
    <meta property="og:description" content="<?= e($sahifa_tavsif) ?>">
    <meta property="og:url"         content="<?= e(SAYT_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%232563EB'/%3E%3Ctext x='32' y='46' text-anchor='middle' fill='white' font-family='Arial Black' font-size='34' font-weight='900'%3EV%3C/text%3E%3C/svg%3E">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: ['selector','[data-theme="dark"]'],
        theme: {
            extend: {
                fontFamily: {
                    'sans':    ['-apple-system','BlinkMacSystemFont','Segoe UI','Roboto','sans-serif'],
                    'display': ['Segoe UI','SF Pro Display','-apple-system','BlinkMacSystemFont','sans-serif'],
                }
            }
        }
    };
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════
           TEMA O'ZGARUVCHILARI — dark va light
        ═══════════════════════════════════════════════════ */
        :root, [data-theme="dark"] {
            --bg:          #0A0F1E;
            --bg2:         #111827;
            --surface:     rgba(255,255,255,0.05);
            --surface2:    rgba(255,255,255,0.08);
            --border:      rgba(255,255,255,0.09);
            --border2:     rgba(255,255,255,0.16);
            --text:        #F1F5FF;
            --text2:       #CBD5E1;
            --muted:       #7E8FB0;
            --blue:        #3B82F6;
            --blue-dark:   #2563EB;
            --blue-glow:   rgba(59,130,246,0.3);
            --green:       #10B981;
            --amber:       #F59E0B;
            --red:         #EF4444;
            --violet:      #7C3AED;
            --card-bg:     rgba(255,255,255,0.04);
            --nav-bg:      rgba(10,15,30,0.85);
            --shadow:      rgba(0,0,0,0.4);
            --meta-theme:  #0A0F1E;
        }

        [data-theme="light"] {
            --bg:          #F0F4FF;
            --bg2:         #FFFFFF;
            --surface:     rgba(255,255,255,0.85);
            --surface2:    rgba(255,255,255,0.95);
            --border:      rgba(0,0,0,0.09);
            --border2:     rgba(0,0,0,0.18);
            --text:        #1E2A4A;
            --text2:       #374151;
            --muted:       #64748B;
            --blue:        #2563EB;
            --blue-dark:   #1D4ED8;
            --blue-glow:   rgba(37,99,235,0.2);
            --green:       #059669;
            --amber:       #D97706;
            --red:         #DC2626;
            --violet:      #7C3AED;
            --card-bg:     rgba(255,255,255,0.9);
            --nav-bg:      rgba(240,244,255,0.9);
            --shadow:      rgba(0,0,0,0.12);
            --meta-theme:  #F0F4FF;
        }

        /* ═══════════════════════════════════════════════════
           BAZAVIY USLUBLAR
        ═══════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            scroll-behavior: smooth;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                         Oxygen, Ubuntu, Cantarell, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.3s ease;
        }

        h1, h2, h3, h4, h5, h6 {
            letter-spacing: -0.02em;
            line-height: 1.2;
            font-weight: 700;
            color: var(--text);
        }

        /* ═══════════════════════════════════════════════════
           ORQA FON EFFEKTLARI
        ═══════════════════════════════════════════════════ */
        .aurora {
            position: fixed; inset: 0; z-index: -1;
            pointer-events: none; overflow: hidden;
        }
        [data-theme="dark"] .aurora::before {
            content: '';
            position: absolute;
            width: 65vw; height: 60vw;
            top: -18vw; left: -12vw;
            background: radial-gradient(ellipse, rgba(59,130,246,0.15) 0%, transparent 65%);
            border-radius: 50%;
        }
        [data-theme="dark"] .aurora::after {
            content: '';
            position: absolute;
            width: 50vw; height: 45vw;
            bottom: -14vw; right: -8vw;
            background: radial-gradient(ellipse, rgba(124,58,237,0.12) 0%, transparent 65%);
            border-radius: 50%;
        }
        [data-theme="light"] .aurora::before,
        [data-theme="light"] .aurora::after { display: none; }

        /* ═══════════════════════════════════════════════════
           GLASS KARTALAR
        ═══════════════════════════════════════════════════ */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .glass-card:hover { border-color: var(--border2); }

        .glass-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -12px var(--shadow);
        }

        /* ═══════════════════════════════════════════════════
           TUGMALAR
        ═══════════════════════════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            padding: 0.75rem 1.5rem;
            font-size: 0.9375rem;
            line-height: 1;
        }
        .btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue-dark), var(--blue));
            color: #ffffff;
            box-shadow: 0 4px 14px -3px var(--blue-glow);
        }
        .btn-primary:hover:not(:disabled) {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -4px var(--blue-glow);
        }
        .btn-primary:active:not(:disabled) { transform: scale(0.97); }

        .btn-ghost {
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover:not(:disabled) {
            background: var(--surface);
            border-color: var(--border2);
        }
        .btn-ghost:active:not(:disabled) { transform: scale(0.97); }

        .btn-success {
            background: linear-gradient(135deg, #059669, #10B981);
            color: #ffffff;
            box-shadow: 0 4px 12px -3px rgba(16,185,129,0.4);
        }
        .btn-success:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }

        .btn-danger {
            background: rgba(239,68,68,0.12);
            color: #FCA5A5;
            border: 1px solid rgba(239,68,68,0.25);
        }
        .btn-danger:hover:not(:disabled) { background: rgba(239,68,68,0.22); }

        .btn-sm  { padding: 0.5rem 1rem; font-size: 0.84rem; border-radius: 8px; }
        .btn-xs  { padding: 0.35rem 0.75rem; font-size: 0.78rem; border-radius: 7px; }
        .btn-lg  { padding: 0.9rem 2rem; font-size: 1.05rem; }
        .btn-xl  { padding: 1.05rem 2.4rem; font-size: 1.1rem; border-radius: 13px; }

        /* ═══════════════════════════════════════════════════
           FORMA MAYDONLARI
        ═══════════════════════════════════════════════════ */
        .field {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 0.8rem 1rem;
            color: var(--text);
            width: 100%;
            font-size: 0.9375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .field:focus {
            border-color: var(--blue);
            background: rgba(37,99,235,0.06);
            box-shadow: 0 0 0 3px var(--blue-glow);
        }
        .field:hover:not(:focus) { border-color: var(--border2); }
        .field::placeholder { color: var(--muted); opacity: 0.7; }
        .field:disabled { opacity: 0.5; cursor: not-allowed; }
        .field option { background: var(--bg2); color: var(--text); }
        textarea.field { resize: vertical; min-height: 80px; }
        select.field { cursor: pointer; }

        .field-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 0.35rem;
        }

        /* File input */
        input[type="file"].field::file-selector-button {
            background: rgba(37,99,235,0.12);
            color: #60A5FA;
            border: none;
            border-radius: 6px;
            padding: 0.3rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            margin-right: 0.75rem;
            transition: background 0.2s;
        }
        input[type="file"].field::file-selector-button:hover {
            background: rgba(37,99,235,0.22);
        }

        /* Number input */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
        input[type="number"] { -moz-appearance: textfield; }

        /* ═══════════════════════════════════════════════════
           BADGE'LAR
        ═══════════════════════════════════════════════════ */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            line-height: 1.4;
        }
        .badge-blue   { background: rgba(59,130,246,0.15);  color: #93C5FD; border: 1px solid rgba(59,130,246,0.2); }
        .badge-green  { background: rgba(16,185,129,0.15);  color: #6EE7B7; border: 1px solid rgba(16,185,129,0.2); }
        .badge-yellow { background: rgba(245,158,11,0.15);  color: #FCD34D; border: 1px solid rgba(245,158,11,0.2); }
        .badge-red    { background: rgba(239,68,68,0.15);   color: #FCA5A5; border: 1px solid rgba(239,68,68,0.2); }
        .badge-purple { background: rgba(124,58,237,0.15);  color: #C4B5FD; border: 1px solid rgba(124,58,237,0.2); }
        .badge-gray   { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
        .badge-cyan   { background: rgba(6,182,212,0.15);   color: #67E8F9; border: 1px solid rgba(6,182,212,0.2); }

        /* Light mode'da badge'lar */
        [data-theme="light"] .badge-blue   { background: rgba(37,99,235,0.1);  color: #1D4ED8; }
        [data-theme="light"] .badge-green  { background: rgba(5,150,105,0.1);  color: #065F46; }
        [data-theme="light"] .badge-yellow { background: rgba(217,119,6,0.1);  color: #92400E; }
        [data-theme="light"] .badge-red    { background: rgba(220,38,38,0.1);  color: #991B1B; }
        [data-theme="light"] .badge-purple { background: rgba(124,58,237,0.1); color: #5B21B6; }
        [data-theme="light"] .badge-gray   { background: rgba(0,0,0,0.06);     color: #4B5563; }

        /* ═══════════════════════════════════════════════════
           ANIMATSIYALAR
        ═══════════════════════════════════════════════════ */
        .fade-up  { animation: fadeUp  0.45s ease both; }
        .fade-in  { animation: fadeIn  0.3s  ease both; }
        .scale-in { animation: scaleIn 0.3s  ease both; }
        .animate-shake { animation: shake 0.4s ease-in-out; }

        @keyframes fadeUp  { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn  { from { opacity:0; } to { opacity:1; } }
        @keyframes scaleIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
        @keyframes shake   { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }
        @keyframes float   { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes shimmer { from{background-position:-200% 0} to{background-position:200% 0} }

        /* ═══════════════════════════════════════════════════
           GRADIENT MATN
        ═══════════════════════════════════════════════════ */
        .grad-text {
            background: linear-gradient(135deg, #60A5FA 0%, #A78BFA 50%, #34D399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        [data-theme="light"] .grad-text {
            background: linear-gradient(135deg, #2563EB 0%, #7C3AED 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ═══════════════════════════════════════════════════
           SCROLLBAR
        ═══════════════════════════════════════════════════ */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        /* ═══════════════════════════════════════════════════
           TEMA TUGMASI
        ═══════════════════════════════════════════════════ */
        .theme-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
            color: var(--text);
        }
        .theme-btn:hover {
            background: var(--surface);
            border-color: var(--border2);
            transform: scale(1.05);
        }

        /* ═══════════════════════════════════════════════════
           BOSHQA YORDAMCHILAR
        ═══════════════════════════════════════════════════ */
        [x-cloak] { display: none !important; }
        .no-select { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
        .tabnum    { font-variant-numeric: tabular-nums; }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        ::selection { background: var(--blue-glow); color: #fff; }

        /* Light mode karta ustida hover */
        [data-theme="light"] .glass-card {
            box-shadow: 0 2px 12px -4px var(--shadow);
        }
        [data-theme="light"] .glass-card:hover {
            box-shadow: 0 6px 20px -6px var(--shadow);
        }

        /* ═══════════════════════════════════════════════════
           LIGHT MODE — TAILWIND OVERRIDES
           Tailwind CDN "text-white" klasslari light modeda
           ko'rinmas bo'lib qolmasligi uchun
        ═══════════════════════════════════════════════════ */
        [data-theme="light"] body    { color: var(--text); }
        [data-theme="light"] h1,[data-theme="light"] h2,
        [data-theme="light"] h3,[data-theme="light"] h4 { color: var(--text); }
        [data-theme="light"] .glass-card { color: var(--text); }

        /* text-white/XX — har xil shaffoflik darajalar */
        [data-theme="light"] .text-white    { color: var(--text)  !important; }
        [data-theme="light"] .text-white\/90{ color: var(--text)  !important; }
        [data-theme="light"] .text-white\/85{ color: var(--text)  !important; }
        [data-theme="light"] .text-white\/80{ color: var(--text2) !important; }
        [data-theme="light"] .text-white\/70{ color: var(--text2) !important; }
        [data-theme="light"] .text-white\/60{ color: var(--muted) !important; }
        [data-theme="light"] .text-white\/55{ color: var(--muted) !important; }
        [data-theme="light"] .text-white\/50{ color: var(--muted) !important; }
        [data-theme="light"] .text-white\/45{ color: var(--muted) !important; }
        [data-theme="light"] .text-white\/40{ color: var(--muted) !important; opacity:.85; }
        [data-theme="light"] .text-white\/35{ color: var(--muted) !important; opacity:.75; }
        [data-theme="light"] .text-white\/30{ color: var(--muted) !important; opacity:.65; }
        [data-theme="light"] .text-white\/25{ color: var(--muted) !important; opacity:.55; }
        [data-theme="light"] .text-white\/20{ color: var(--muted) !important; opacity:.45; }

        /* Tugmalar ichidagi matn oq qolsin */
        [data-theme="light"] .btn-primary,
        [data-theme="light"] .btn-primary .text-white,
        [data-theme="light"] .btn-primary span,
        [data-theme="light"] .btn-success,
        [data-theme="light"] .btn-success span { color: #ffffff !important; }

        /* Fon ranglari */
        [data-theme="light"] .bg-white\/5   { background: var(--surface) !important; }
        [data-theme="light"] .bg-white\/8   { background: var(--surface) !important; }
        [data-theme="light"] .bg-white\/10  { background: var(--surface2) !important; }
        [data-theme="light"] .bg-white\/\[0\.04\] { background: var(--surface) !important; }
        [data-theme="light"] .bg-white\/\[0\.05\] { background: var(--surface) !important; }
        [data-theme="light"] .bg-white\/\[0\.06\] { background: var(--surface2) !important; }
        [data-theme="light"] .bg-white\/\[0\.07\] { background: var(--surface2) !important; }
        [data-theme="light"] .bg-white\/\[0\.08\] { background: var(--surface2) !important; }
        [data-theme="light"] .bg-white\/\[0\.03\] { background: rgba(0,0,0,.03) !important; }
        [data-theme="light"] .bg-white\/\[0\.02\] { background: rgba(0,0,0,.02) !important; }

        /* Chegara ranglari */
        [data-theme="light"] .border-white\/\[0\.06\],
        [data-theme="light"] .border-white\/\[0\.07\],
        [data-theme="light"] .border-white\/\[0\.08\] { border-color: var(--border) !important; }
        [data-theme="light"] .border-white\/10 { border-color: var(--border) !important; }
        [data-theme="light"] .border-white\/5  { border-color: var(--border) !important; }

        /* Jadval qatorlari */
        [data-theme="light"] .divide-white\/5  > * { border-color: var(--border) !important; }
        [data-theme="light"] .divide-white\/\[0\.04\] > * { border-color: var(--border) !important; }

        /* hover:bg */
        [data-theme="light"] .hover\:bg-white\/5:hover  { background: var(--surface) !important; }
        [data-theme="light"] .hover\:bg-white\/10:hover { background: var(--surface2) !important; }
        [data-theme="light"] .hover\:bg-white\/\[0\.05\]:hover { background: var(--surface) !important; }
        [data-theme="light"] .hover\:bg-white\/\[0\.06\]:hover { background: var(--surface) !important; }

        /* Rang sinflar light mode'da to'g'ri ko'rinsin */
        [data-theme="light"] .text-brand-muted { color: var(--muted) !important; }

        /* Gradient matn */
        [data-theme="light"] .grad-text,
        [data-theme="light"] .bg-clip-text,
        [data-theme="light"] .bg-gradient-to-r.bg-clip-text { color: transparent; }

        /* Input placeholder */
        [data-theme="light"] input::placeholder,
        [data-theme="light"] textarea::placeholder { color: var(--muted); opacity: .7; }

        /* Scrollbar */
        [data-theme="light"] ::-webkit-scrollbar-thumb { background: var(--border2); }
    </style>

    <!-- TEMA: saqlangan afzallikni oldindan qo'llash (flicker oldini olish) -->
    <script>
    (function() {
        var saved = localStorage.getItem('vpy_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
    })();
    </script>
</head>
<body class="<?= e($body_class) ?> min-h-screen">

<!-- Aurora orqa fon (faqat dark mode'da ko'rinadi) -->
<div class="aurora" aria-hidden="true"></div>

<!-- Flash xabar -->
<?php if ($flash): ?>
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
    <div class="glass-card px-4 py-3.5 flex items-start gap-3 pointer-events-auto"
         style="box-shadow: 0 8px 32px -8px var(--shadow);
                <?= $flash['tur'] === 'muvaffaqiyat'
                    ? 'border-color: rgba(16,185,129,0.4); background: rgba(16,185,129,0.06);'
                    : ($flash['tur'] === 'xato'
                        ? 'border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.06);'
                        : 'border-color: rgba(59,130,246,0.4); background: rgba(59,130,246,0.06);') ?>">
        <span class="text-xl flex-shrink-0 mt-0.5">
            <?= match($flash['tur'] ?? '') { 'muvaffaqiyat' => '✅', 'xato' => '❌', default => 'ℹ️' } ?>
        </span>
        <p class="flex-1 text-sm" style="color: var(--text)"><?= e($flash['matn']) ?></p>
        <button @click="show = false"
                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-sm
                       transition"
                style="background: var(--surface); color: var(--muted)">×</button>
    </div>
</div>
<?php endif; ?>
