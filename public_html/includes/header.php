<?php
/**
 * VatanParvar Yaypan — Brutalizm dizayn tizimi
 *
 * Minimalist brutalizm:
 *   - Faqat oq fon (#FFFFFF)
 *   - Yupqa qora ramkalar (1px solid #000)
 *   - Och kulrang hover (#F5F5F5)
 *   - Mukammal tipografika
 *   - Hech qanday gradient, blur yoki shadow
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/funksiyalar.php';

$sahifa_sarlavha = $sahifa_sarlavha ?? t('sayt_nomi');
$sahifa_tavsif   = $sahifa_tavsif   ?? t('sayt_shior');
$body_class      = $body_class      ?? '';
$f               = joriy_foydalanuvchi();
$flash           = flash_ol();

$html_lang = ($_SESSION['til'] ?? TIL_DEFAULT) === 'uz_cyrl' ? 'uz-Cyrl' : 'uz-Latn';
?>
<!DOCTYPE html>
<html lang="<?= $html_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($sahifa_sarlavha) ?> — <?= e(SAYT_NOMI) ?></title>
    <meta name="description" content="<?= e(mb_substr($sahifa_tavsif, 0, 155)) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='white' stroke='black' stroke-width='4'/%3E%3Ctext x='32' y='44' text-anchor='middle' fill='black' font-family='Georgia,serif' font-size='34' font-weight='700'%3EV%3C/text%3E%3C/svg%3E">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════
           BRUTALIZM DIZAYN TIZIMI
           ═══════════════════════════════════════════════════ */
        :root {
            --bg:        #FFFFFF;
            --bg-alt:    #FAFAFA;
            --hover:     #F5F5F5;
            --text:      #000000;
            --muted:     #666666;
            --border:    #000000;
            --border-l:  #E5E5E5;
            --accent:    #000000;
            --error:     #000000;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Georgia', 'Times New Roman', Times, serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            line-height: 1.5;
        }

        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        /* TIPOGRAFIKA — Mukammal */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.2;
            color: var(--text);
        }
        h1 { font-size: clamp(2.25rem, 5vw, 4rem); }
        h2 { font-size: clamp(1.75rem, 3.5vw, 2.5rem); }
        h3 { font-size: 1.5rem; }
        h4 { font-size: 1.25rem; }

        p { color: var(--text); }

        a { color: var(--text); text-decoration: underline; text-underline-offset: 3px; }
        a:hover { color: var(--muted); }

        /* ═══════════════════════════════════════════════════
           KARTALAR — qora ramkali
           ═══════════════════════════════════════════════════ */
        .b-card {
            background: var(--bg);
            border: 1px solid var(--border);
            transition: background-color 0.15s ease;
        }
        .b-card-hover:hover { background: var(--hover); }

        .b-card-light {
            background: var(--bg);
            border: 1px solid var(--border-l);
        }

        /* ═══════════════════════════════════════════════════
           TUGMALAR — oddiy, qora ramkali
           ═══════════════════════════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-family: inherit;
            font-size: 0.9375rem;
            font-weight: 500;
            line-height: 1;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn:hover { background: var(--hover); }
        .btn:active { background: var(--text); color: var(--bg); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-primary {
            background: var(--text);
            color: var(--bg);
            border-color: var(--text);
        }
        .btn-primary:hover { background: var(--bg); color: var(--text); }
        .btn-primary:active { background: #333; color: var(--bg); }

        .btn-ghost {
            background: var(--bg);
            color: var(--text);
            border-color: var(--border);
        }
        .btn-ghost:hover { background: var(--hover); }

        .btn-danger {
            background: var(--bg);
            color: var(--text);
            border-color: var(--border);
        }
        .btn-danger:hover { background: var(--text); color: var(--bg); }

        .btn-sm  { padding: 0.5rem 1rem; font-size: 0.825rem; }
        .btn-xs  { padding: 0.35rem 0.7rem; font-size: 0.75rem; }
        .btn-lg  { padding: 0.95rem 2rem; font-size: 1rem; }
        .btn-xl  { padding: 1.1rem 2.5rem; font-size: 1.0625rem; }

        /* ═══════════════════════════════════════════════════
           FORMA MAYDONLARI
           ═══════════════════════════════════════════════════ */
        .field {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            font-family: inherit;
            font-size: 0.9375rem;
            line-height: 1.4;
            outline: none;
            transition: background-color 0.15s ease;
        }
        .field:hover { background: var(--hover); }
        .field:focus { background: var(--bg); border-color: var(--text); outline: 2px solid var(--text); outline-offset: -2px; }
        .field::placeholder { color: var(--muted); opacity: 0.7; }
        .field:disabled { opacity: 0.5; cursor: not-allowed; }
        .field option { background: var(--bg); color: var(--text); }
        textarea.field { resize: vertical; min-height: 90px; line-height: 1.5; }
        select.field { cursor: pointer; }

        .field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }

        /* File input */
        input[type="file"].field { padding: 0.6rem 1rem; }
        input[type="file"].field::file-selector-button {
            background: var(--text); color: var(--bg);
            border: none; padding: 0.4rem 0.9rem;
            font-family: inherit; font-size: 0.82rem;
            cursor: pointer; margin-right: 0.75rem;
        }
        input[type="file"].field::file-selector-button:hover { background: #333; }

        /* Number arrows hide */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
        input[type="number"] { -moz-appearance: textfield; }

        /* ═══════════════════════════════════════════════════
           BADGE'LAR — minimal
           ═══════════════════════════════════════════════════ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.6rem;
            font-size: 0.72rem;
            font-weight: 500;
            line-height: 1.4;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
        }
        .badge-filled { background: var(--text); color: var(--bg); border-color: var(--text); }
        .badge-light  { background: var(--bg-alt); color: var(--text); border-color: var(--border-l); }

        /* ═══════════════════════════════════════════════════
           SCROLLBAR
           ═══════════════════════════════════════════════════ */
        ::-webkit-scrollbar       { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--text); border: 2px solid var(--bg); }

        /* TANLOV */
        ::selection { background: var(--text); color: var(--bg); }

        /* ANIMATSIYA */
        .fade-in { animation: fadeIn 0.4s ease both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ALPINE CLOAK */
        [x-cloak] { display: none !important; }

        /* TABULAR NUMBERS */
        .tabnum { font-variant-numeric: tabular-nums; }

        /* CHEGARALAR */
        .b-divider { border-top: 1px solid var(--border); }
        .b-divider-l { border-top: 1px solid var(--border-l); }

        /* JADVAL */
        table.b-table { width: 100%; border-collapse: collapse; }
        table.b-table th, table.b-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-l);
            text-align: left;
        }
        table.b-table thead th {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        table.b-table tbody tr:hover { background: var(--hover); }

        /* OQ FON / OYAT */
        body { background: var(--bg); }

        /* ANTI-COPY */
        .no-select { -webkit-user-select: none; user-select: none; }

        /* LINE CLAMP */
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-4 { display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>
<body class="<?= e($body_class) ?> min-h-screen">

<?php if ($flash): ?>
<!-- Flash xabar — qora ramka -->
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 4000)"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-4 right-4 z-50 max-w-sm"
     x-cloak>
    <div class="b-card flex items-start gap-3 p-3 pr-2"
         style="<?= ($flash['tur'] ?? '') === 'xato' ? 'background:#000;color:#fff;' : '' ?>">
        <div class="text-sm flex-1 leading-relaxed"><?= e($flash['matn']) ?></div>
        <button @click="show = false"
                class="px-2 py-0.5 text-base leading-none hover:opacity-60">×</button>
    </div>
</div>
<?php endif; ?>
