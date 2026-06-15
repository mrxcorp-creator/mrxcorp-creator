<?php
/**
 * AvtoTest Pro — Admin panel sidebar + header layout
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$admin_sahifa  = $admin_sahifa  ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

// Flash olish
$flash = flash_ol();

// Tez statistika (sidebar uchun)
$kutmoqda_tolov = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');
$kutmoqda_fikr  = (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0');
?>
<!DOCTYPE html>
<html lang="<?= str_starts_with($_SESSION['til'] ?? 'uz', 'ru') ? 'ru' : 'uz' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($sahifa_sarlavha) ?> — AvtoTest Pro Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-bg':     '#070C18',
                    'brand-surface':'rgba(255,255,255,0.04)',
                    'brand-border': 'rgba(255,255,255,0.08)',
                    'brand-primary':'#3B82F6',
                    'brand-muted':  '#7E8FB0',
                    'brand-text':   '#F0F4FF',
                },
                fontFamily: {
                    'display': ['Manrope','system-ui','sans-serif'],
                    'sans':    ['Inter','system-ui','sans-serif'],
                }
            }
        }
    };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='%232563EB'/><text x='50%' y='56%' text-anchor='middle' dominant-baseline='middle' fill='white' font-family='Arial Black' font-size='30' font-weight='900'>A</text></svg>">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            background: #070C18; color: #F0F4FF;
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            height: 100%;
        }
        h1,h2,h3,h4 { font-family: 'Manrope', system-ui, sans-serif; font-weight: 700; letter-spacing: -.02em; }
        .glass-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1rem;
            transition: border-color .2s;
        }
        .glass-card:hover { border-color: rgba(255,255,255,0.13); }
        .btn-primary {
            background: linear-gradient(135deg,#2563EB,#3B82F6); color:#fff;
            font-weight:600; padding:.65rem 1.25rem; border-radius:.75rem;
            display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            transition:all .2s; cursor:pointer; border:none;
            box-shadow: 0 4px 12px -3px rgba(59,130,246,.35);
        }
        .btn-primary:hover  { background:linear-gradient(135deg,#1D4ED8,#2563EB); transform:translateY(-1px); }
        .btn-primary:active { transform:scale(.97); }
        .btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }
        .btn-ghost {
            background:rgba(255,255,255,.05); color:#fff; font-weight:600;
            padding:.65rem 1.25rem; border-radius:.75rem;
            border:1px solid rgba(255,255,255,.10);
            display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            transition:all .2s; cursor:pointer;
        }
        .btn-ghost:hover { background:rgba(255,255,255,.10); }
        .btn-danger {
            background:rgba(239,68,68,.12); color:#FCA5A5; font-weight:600;
            padding:.55rem 1rem; border-radius:.75rem;
            border:1px solid rgba(239,68,68,.25);
            display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            transition:all .2s; cursor:pointer;
        }
        .btn-danger:hover { background:rgba(239,68,68,.25); }
        .btn-success {
            background:rgba(34,197,94,.15); color:#86EFAC; font-weight:600;
            padding:.55rem 1rem; border-radius:.75rem;
            border:1px solid rgba(34,197,94,.3);
            display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            transition:all .2s; cursor:pointer;
        }
        .btn-success:hover { background:rgba(34,197,94,.25); }
        .field {
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.09);
            border-radius:.75rem; padding:.75rem 1rem; color:#F0F4FF; width:100%;
            transition:all .2s; font-size:.9375rem;
        }
        .field:focus { outline:none; border-color:#3B82F6; background:rgba(59,130,246,.06); box-shadow:0 0 0 3px rgba(59,130,246,.18); }
        .field::placeholder { color:rgba(255,255,255,.3); }
        .field option { background:#1E293B; }
        .field-label { display:block; font-size:.82rem; color:#7E8FB0; margin-bottom:.3rem; font-weight:500; }
        .badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600; }
        .badge-blue   { background:rgba(59,130,246,.18);  color:#93C5FD; }
        .badge-green  { background:rgba(34,197,94,.18);   color:#86EFAC; }
        .badge-yellow { background:rgba(245,158,11,.18);  color:#FCD34D; }
        .badge-red    { background:rgba(239,68,68,.18);   color:#FCA5A5; }
        .badge-purple { background:rgba(168,85,247,.18);  color:#D8B4FE; }
        .badge-gray   { background:rgba(255,255,255,.08); color:#94A3B8; }
        .fade-up { animation: fadeUp .4s ease both; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
        ::-webkit-scrollbar { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:3px; }
        [x-cloak] { display:none!important; }
        .tabnum { font-variant-numeric:tabular-nums; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden">

<!-- Flash xabar -->
<?php if ($flash): ?>
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed top-5 right-5 z-[200] max-w-sm w-full" x-cloak>
    <div class="glass-card px-4 py-3 flex items-start gap-3 shadow-2xl
        <?= $flash['tur'] === 'muvaffaqiyat' ? 'border-green-500/40' : 'border-red-500/40' ?>">
        <span class="text-lg flex-shrink-0"><?= $flash['tur'] === 'muvaffaqiyat' ? '✅' : '❌' ?></span>
        <p class="text-sm text-white flex-1"><?= e($flash['matn']) ?></p>
        <button @click="show=false" class="text-white/40 hover:text-white text-lg">×</button>
    </div>
</div>
<?php endif; ?>

<div x-data="{ sidebar: window.innerWidth >= 1024 }" class="flex min-h-screen">

    <!-- ===== SIDEBAR ===== -->
    <aside x-show="sidebar"
           x-transition:enter="transition-transform duration-300"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen flex flex-col bg-brand-bg/98 backdrop-blur-xl border-r border-white/[0.07] overflow-y-auto"
           x-cloak>

        <!-- Logo -->
        <div class="p-4 border-b border-white/[0.07] flex items-center justify-between">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2 flex-1 min-w-0">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-display font-black text-white text-base flex-shrink-0">A</span>
                <div class="min-w-0">
                    <span class="font-display font-bold text-white text-sm block truncate">AvtoTest Pro</span>
                    <span class="text-xs text-brand-muted">Admin Panel</span>
                </div>
            </a>
            <button @click="sidebar=false" class="lg:hidden p-1.5 rounded-lg hover:bg-white/[0.06]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Navigatsiya -->
        <nav class="flex-1 p-3 space-y-0.5">
            <?php
            $menyu = [
                'index'            => ['🏠', t('boshqaruv_paneli'), 'index.php',          ''],
                'biletlar'         => ['🎫', 'Biletlar',            'biletlar.php',        ''],
                'savollar'         => ['❓', 'Savollar',             'savollar.php',        ''],
                'foydalanuvchilar' => ['👥', 'Foydalanuvchilar',     'foydalanuvchilar.php',''],
                'tariflar'         => ['💎', t('tariflar'),          'tariflar.php',        ''],
                'promokodlar'      => ['🎟️','Promo kodlar',          'promokodlar.php',     ''],
                'tolovlar'         => ['💳', "To'lovlar",            'tolovlar.php',        $kutmoqda_tolov ? "($kutmoqda_tolov)" : ''],
                'fikrlar'          => ['💬', t('fikrlar'),           'fikrlar.php',         $kutmoqda_fikr  ? "($kutmoqda_fikr)"  : ''],
                'sozlamalar'       => ['⚙️', 'Sozlamalar',           'sozlamalar.php',      ''],
            ];
            foreach ($menyu as $key => [$emoji, $nomi, $url, $badge]):
                $faol = $admin_sahifa === $key;
            ?>
                <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all group
                       <?= $faol ? 'bg-blue-500/15 text-blue-400 font-semibold' : 'text-white/70 hover:text-white hover:bg-white/[0.05]' ?>">
                    <span class="text-base w-6 text-center flex-shrink-0"><?= $emoji ?></span>
                    <span class="flex-1 truncate"><?= e($nomi) ?></span>
                    <?php if ($badge): ?>
                        <span class="badge badge-yellow text-xs flex-shrink-0"><?= $badge ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Alt qism -->
        <div class="p-3 border-t border-white/[0.07] space-y-0.5">
            <div class="px-3 py-2 mb-2">
                <p class="text-xs font-semibold truncate"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></p>
                <span class="badge <?= $f['rol'] === 'developer' ? 'badge-purple' : 'badge-blue' ?> text-xs">
                    <?= strtoupper(e($f['rol'])) ?>
                </span>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-white/70 hover:text-white hover:bg-white/[0.05] transition">
                👤 Foydalanuvchi paneli
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-red-400 hover:bg-red-500/10 transition">
                🚪 <?= e(t('chiqish')) ?>
            </a>
        </div>
    </aside>

    <!-- Mobil overlay -->
    <div x-show="sidebar && window.innerWidth < 1024"
         @click="sidebar = false"
         class="fixed inset-0 z-30 bg-black/60 lg:hidden"
         x-cloak></div>

    <!-- ===== ASOSIY KONTENT ===== -->
    <div class="flex-1 min-w-0 flex flex-col">
        <!-- Yuqori header -->
        <header class="sticky top-0 z-20 bg-brand-bg/90 backdrop-blur-xl border-b border-white/[0.07] h-14 flex items-center px-4 gap-3">
            <button @click="sidebar = !sidebar" class="p-2 rounded-xl hover:bg-white/[0.06] transition" aria-label="Menyu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="font-display font-semibold text-lg flex-1 truncate"><?= e($sahifa_sarlavha) ?></h1>
            <!-- Tez harakatlar -->
            <?php if ($kutmoqda_tolov || $kutmoqda_fikr): ?>
            <div class="flex items-center gap-2">
                <?php if ($kutmoqda_tolov): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
                       class="badge badge-yellow">⏳ <?= $kutmoqda_tolov ?></a>
                <?php endif; ?>
                <?php if ($kutmoqda_fikr): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
                       class="badge badge-blue">💬 <?= $kutmoqda_fikr ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <span class="hidden sm:flex items-center gap-2 text-sm text-brand-muted flex-shrink-0">
                <span class="badge <?= $f['rol'] === 'developer' ? 'badge-purple' : 'badge-blue' ?>"><?= strtoupper(e($f['rol'])) ?></span>
                <?= e($f['ism']) ?>
            </span>
        </header>

        <!-- Kontent -->
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
