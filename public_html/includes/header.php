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

$sahifa_sarlavha ??= t('sayt_nomi');
$sahifa_tavsif   ??= t('sayt_shior');
$body_class      ??= '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();
$joriy_til       = $_SESSION['til'] ?? 'uz_latn';
$html_lang       = $joriy_til === 'uz_cyrl' ? 'uz-Cyrl' : 'uz-Latn';
?>
<!DOCTYPE html>
<html lang="<?= e($html_lang) ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
    <meta name="theme-color" content="#0A0F1E" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#F8FAFC" media="(prefers-color-scheme: light)">

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

    <!-- Tema (FOUC oldini olish — body renderdan oldin) -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('vp_theme');
                if (!t) {
                    t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
                }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) { /* noop */ }
        })();
    </script>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['selector', '[data-theme="dark"]'],
            theme: {
                extend: {
                    fontFamily: {
                        'display': ['Manrope', 'system-ui', 'sans-serif'],
                        'sans':    ['Inter', 'system-ui', 'sans-serif']
                    },
                    animation: {
                        'fade-up':  'fadeUp 0.5s ease both',
                        'fade-in':  'fadeIn 0.4s ease both',
                        'shake':    'shake 0.4s ease-in-out',
                        'shimmer':  'shimmer 2.5s linear infinite',
                        'float':    'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeUp:  { '0%': { opacity: 0, transform: 'translateY(20px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                        fadeIn:  { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                        shake:   { '0%,100%': { transform:'translateX(0)' }, '25%': { transform:'translateX(-6px)' }, '75%': { transform:'translateX(6px)' } },
                        shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                        float:   { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-12px)' } },
                    }
                }
            }
        };
    </script>

    <!-- Alpine.js (CDN) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Shriftlar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <?php
    $favicon_fayl = sozlama('sayt_favicon', '');
    if ($favicon_fayl && is_file(UPLOAD_PATH . '/dizayn/' . $favicon_fayl)):
    ?>
        <link rel="icon" href="<?= e(SAYT_URL) ?>/uploads/dizayn/<?= e($favicon_fayl) ?>">
    <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%233B82F6'/><stop offset='1' stop-color='%237C3AED'/></linearGradient></defs><rect width='64' height='64' rx='14' fill='url(%23g)'/><text x='50%25' y='54%25' text-anchor='middle' fill='white' font-family='Arial' font-size='34' font-weight='800'>V</text></svg>">
    <?php endif; ?>

    <style>
        /* ============================================================
           DIZAYN TIZIMI — DARK + LIGHT TEMA
           CSS o'zgaruvchilar `data-theme` attribute-ga qarab almashadi.
           ============================================================ */

        :root,
        [data-theme="dark"] {
            /* Fon */
            --bg-primary:        #0A0F1E;
            --bg-secondary:      #0F1629;
            --bg-elevated:       rgba(255,255,255,0.04);
            --bg-glass:          rgba(255,255,255,0.05);
            --bg-glass-strong:   rgba(255,255,255,0.08);

            /* Chegaralar */
            --border:            rgba(255,255,255,0.10);
            --border-strong:     rgba(255,255,255,0.18);

            /* Matn */
            --text-primary:      #F4F4FF;
            --text-secondary:    #BBC3D6;
            --text-muted:        #8A99B8;
            --text-on-accent:    #FFFFFF;

            /* Aksent (asosiy brand rang) */
            --accent:            #3B82F6;
            --accent-hover:      #2563EB;
            --accent-soft:       rgba(59,130,246,0.12);
            --accent-glow:       rgba(59,130,246,0.40);

            /* Status */
            --success:           #22C55E;
            --success-soft:      rgba(34,197,94,0.12);
            --warning:           #F59E0B;
            --warning-soft:      rgba(245,158,11,0.12);
            --error:             #EF4444;
            --error-soft:        rgba(239,68,68,0.12);

            /* Gradient palette (premium feel) */
            --gradient-primary:  linear-gradient(135deg, #3B82F6 0%, #6366F1 50%, #8B5CF6 100%);
            --gradient-accent:   linear-gradient(135deg, #60A5FA 0%, #818CF8 100%);
            --gradient-warm:     linear-gradient(135deg, #F59E0B 0%, #EF4444 100%);
            --gradient-success:  linear-gradient(135deg, #10B981 0%, #22C55E 100%);
            --gradient-mesh:     radial-gradient(at 20% 30%, rgba(59,130,246,0.15) 0%, transparent 50%),
                                 radial-gradient(at 80% 20%, rgba(124,58,237,0.12) 0%, transparent 50%),
                                 radial-gradient(at 50% 80%, rgba(34,211,238,0.10) 0%, transparent 50%);

            /* Soyalar */
            --shadow-sm:         0 2px 8px rgba(0,0,0,0.20);
            --shadow-md:         0 8px 24px rgba(0,0,0,0.30);
            --shadow-lg:         0 20px 60px rgba(0,0,0,0.40);
            --shadow-glow:       0 10px 40px var(--accent-glow);

            color-scheme: dark;
        }

        [data-theme="light"] {
            --bg-primary:        #F8FAFC;
            --bg-secondary:      #FFFFFF;
            --bg-elevated:       rgba(255,255,255,0.80);
            --bg-glass:          rgba(255,255,255,0.70);
            --bg-glass-strong:   rgba(255,255,255,0.85);

            --border:            rgba(15,23,42,0.08);
            --border-strong:     rgba(15,23,42,0.16);

            --text-primary:      #0F172A;
            --text-secondary:    #334155;
            --text-muted:        #64748B;
            --text-on-accent:    #FFFFFF;

            --accent:            #2563EB;
            --accent-hover:      #1D4ED8;
            --accent-soft:       rgba(37,99,235,0.08);
            --accent-glow:       rgba(37,99,235,0.25);

            --success:           #16A34A;
            --success-soft:      rgba(22,163,74,0.10);
            --warning:           #D97706;
            --warning-soft:      rgba(217,119,6,0.10);
            --error:             #DC2626;
            --error-soft:        rgba(220,38,38,0.10);

            --gradient-primary:  linear-gradient(135deg, #2563EB 0%, #4F46E5 50%, #7C3AED 100%);
            --gradient-accent:   linear-gradient(135deg, #3B82F6 0%, #6366F1 100%);
            --gradient-warm:     linear-gradient(135deg, #D97706 0%, #DC2626 100%);
            --gradient-success:  linear-gradient(135deg, #059669 0%, #16A34A 100%);
            --gradient-mesh:     radial-gradient(at 20% 30%, rgba(59,130,246,0.10) 0%, transparent 50%),
                                 radial-gradient(at 80% 20%, rgba(124,58,237,0.08) 0%, transparent 50%),
                                 radial-gradient(at 50% 80%, rgba(34,211,238,0.06) 0%, transparent 50%);

            --shadow-sm:         0 2px 8px rgba(15,23,42,0.06);
            --shadow-md:         0 8px 24px rgba(15,23,42,0.08);
            --shadow-lg:         0 20px 60px rgba(15,23,42,0.10);
            --shadow-glow:       0 10px 40px var(--accent-glow);

            color-scheme: light;
        }

        /* ============================================================
           ASOSIY ELEMENTLAR
           ============================================================ */
        * { -webkit-tap-highlight-color: transparent; }

        html, body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            scroll-behavior: smooth;
            transition: background-color .3s ease, color .3s ease;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', system-ui, sans-serif;
            letter-spacing: -0.02em;
            font-weight: 700;
            color: var(--text-primary);
        }

        a { color: inherit; text-decoration: none; }

        /* ============================================================
           AURORA / MESH GRADIENT FON
           ============================================================ */
        .aurora-bg {
            position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;
            background: var(--gradient-mesh);
        }
        .aurora-bg::before, .aurora-bg::after {
            content: ''; position: absolute; width: 50vw; height: 50vw; max-width: 800px; max-height: 800px;
            border-radius: 50%; filter: blur(120px); opacity: 0.30;
            animation: float 12s ease-in-out infinite;
        }
        .aurora-bg::before { background: var(--accent); top: -10vw; left: -10vw; }
        .aurora-bg::after  { background: #8B5CF6; bottom: -10vw; right: -10vw; animation-delay: -6s; }
        [data-theme="light"] .aurora-bg::before,
        [data-theme="light"] .aurora-bg::after { opacity: 0.18; }

        /* ============================================================
           GLASS KARTA (premium glassmorphism)
           ============================================================ */
        .glass-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px) saturate(140%);
            -webkit-backdrop-filter: blur(20px) saturate(140%);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            transition: transform .3s cubic-bezier(.4,0,.2,1), border-color .3s ease, box-shadow .3s ease;
            box-shadow: var(--shadow-sm);
        }
        .glass-card:hover { border-color: var(--border-strong); }
        .glass-card-hover:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

        .glass-card-premium {
            background: var(--bg-glass-strong);
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .glass-card-premium::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: linear-gradient(135deg, rgba(255,255,255,0.06) 0%, transparent 50%);
            border-radius: inherit;
        }
        [data-theme="light"] .glass-card-premium::before {
            background: linear-gradient(135deg, rgba(255,255,255,0.6) 0%, transparent 50%);
        }

        /* ============================================================
           TUGMALAR — premium hashamatli
           ============================================================ */
        .btn-primary {
            background: var(--gradient-primary);
            color: var(--text-on-accent);
            font-weight: 600;
            padding: .75rem 1.5rem;
            border-radius: .85rem;
            transition: all .25s cubic-bezier(.4,0,.2,1);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            position: relative; overflow: hidden;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px var(--accent-glow);
            background-size: 200% 100%;
            background-position: 0% 0;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px var(--accent-glow), var(--shadow-md);
            background-position: 100% 0;
        }
        .btn-primary:active { transform: translateY(0) scale(.97); }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .btn-ghost {
            background: var(--bg-glass);
            backdrop-filter: blur(10px);
            color: var(--text-primary);
            font-weight: 600;
            padding: .75rem 1.5rem;
            border-radius: .85rem;
            border: 1px solid var(--border);
            transition: all .25s cubic-bezier(.4,0,.2,1);
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            cursor: pointer;
        }
        .btn-ghost:hover { background: var(--bg-glass-strong); border-color: var(--border-strong); transform: translateY(-1px); }
        .btn-ghost:active { transform: scale(.97); }

        .btn-danger {
            background: var(--error-soft);
            color: var(--error);
            font-weight: 600;
            padding: .65rem 1.25rem;
            border-radius: .85rem;
            border: 1px solid color-mix(in srgb, var(--error) 30%, transparent);
            transition: all .25s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            cursor: pointer;
        }
        .btn-danger:hover { background: color-mix(in srgb, var(--error) 25%, transparent); }

        .btn-success {
            background: var(--gradient-success);
            color: white;
            font-weight: 600;
            padding: .75rem 1.5rem;
            border-radius: .85rem;
            transition: all .25s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            cursor: pointer;
            border: none;
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(34,197,94,.30); }

        /* ============================================================
           FORM MAYDONLARI
           ============================================================ */
        .field {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: .85rem;
            padding: .85rem 1rem;
            color: var(--text-primary);
            width: 100%;
            transition: all .2s ease;
            font: inherit;
        }
        .field:focus {
            outline: none;
            border-color: var(--accent);
            background: var(--accent-soft);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
        }
        .field::placeholder { color: var(--text-muted); opacity: .6; }
        .field-label {
            display: block;
            font-size: .85rem;
            color: var(--text-secondary);
            margin-bottom: .4rem;
            font-weight: 500;
        }

        /* Select */
        select.field {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14' fill='none'%3E%3Cpath d='M3.5 5.25L7 8.75L10.5 5.25' stroke='%238A99B8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        /* ============================================================
           GRADIENT MATN (premium hero)
           ============================================================ */
        .gradient-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .gradient-border {
            position: relative;
            background: var(--bg-glass);
            border-radius: 1.25rem;
        }
        .gradient-border::before {
            content: ''; position: absolute; inset: -1px;
            border-radius: inherit; padding: 1px;
            background: var(--gradient-primary);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            pointer-events: none;
        }

        /* ============================================================
           ANIMATSIYA YORDAMCHILARI
           ============================================================ */
        .fade-up { animation: fadeUp 0.5s cubic-bezier(.4,0,.2,1) both; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(24px); } 100% { opacity: 1; transform: translateY(0); } }

        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes float   { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }

        /* Skroll panel */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-strong); }

        /* Tanlovni rang berish */
        ::selection { background: var(--accent); color: white; }

        /* ============================================================
           UTILITY KLASSLAR — Tailwind o'rniga (yoki bilan birga)
           Ko'p ishlatilanlarni `var(--*)` orqali aliasing.
           ============================================================ */
        .bg-app          { background: var(--bg-primary); }
        .bg-surface      { background: var(--bg-elevated); }
        .bg-glass        { background: var(--bg-glass); }
        .border-app      { border-color: var(--border); }
        .text-app        { color: var(--text-primary); }
        .text-app-2      { color: var(--text-secondary); }
        .text-muted-app  { color: var(--text-muted); }
        .text-accent     { color: var(--accent); }
        .text-success    { color: var(--success); }
        .text-warning    { color: var(--warning); }
        .text-error      { color: var(--error); }

        /* Tema toggle ikonkalari */
        [data-theme="dark"] .theme-icon-light { display: inline-flex; }
        [data-theme="dark"] .theme-icon-dark  { display: none; }
        [data-theme="light"] .theme-icon-light { display: none; }
        [data-theme="light"] .theme-icon-dark  { display: inline-flex; }

        /* No-select (test sahifasi) */
        .no-select { -webkit-user-select: none; -moz-user-select: none; user-select: none; }

        /* Accessibility — fokus halqasi */
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: .5rem; }
        button:focus, a:focus, input:focus, textarea:focus, select:focus { outline: none; }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ============================================================
           BADGE va STATUSLAR
           ============================================================ */
        .badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            border: 1px solid transparent;
        }
        .badge-accent  { background: var(--accent-soft); color: var(--accent); border-color: color-mix(in srgb, var(--accent) 20%, transparent); }
        .badge-success { background: var(--success-soft); color: var(--success); border-color: color-mix(in srgb, var(--success) 20%, transparent); }
        .badge-warning { background: var(--warning-soft); color: var(--warning); border-color: color-mix(in srgb, var(--warning) 20%, transparent); }
        .badge-error   { background: var(--error-soft); color: var(--error); border-color: color-mix(in srgb, var(--error) 20%, transparent); }

        /* Divider */
        .divider { height: 1px; background: var(--border); border: 0; }

        /* Skeleton (yuklanmoqda) */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-elevated) 25%, var(--bg-glass-strong) 50%, var(--bg-elevated) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s linear infinite;
            border-radius: .75rem;
        }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen overflow-x-hidden bg-app text-app">

<!-- Orqa aurora/mesh effekti -->
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
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                <?php elseif ($flash['tur'] === 'xato'): ?>
                    <svg class="w-5 h-5 text-error" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                <?php else: ?>
                    <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-1 4a1 1 0 0 1 1-1 1 1 0 0 1 1 1v5a1 1 0 1 1-2 0V9z"/></svg>
                <?php endif; ?>
            </div>
            <p class="text-sm text-app flex-1"><?= e($flash['matn']) ?></p>
            <button @click="show=false" class="text-muted-app hover:text-app text-lg leading-none">×</button>
        </div>
    </div>
<?php endif; ?>
