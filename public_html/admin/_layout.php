<?php
/**
 * VatanParvar Yaypan — Admin panel layout (sidebar + topbar)
 * Har bir admin sahifasi oldin o'z require_once'larini bajaradi,
 * so'ng $admin_sahifa + $sahifa_sarlavha ni o'rnatib _layout.php ni chaqiradi.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$admin_sahifa    = $admin_sahifa    ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

// Tez statistika (badge lar uchun)
$kutmoqda_tolov = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');
$kutmoqda_fikr  = (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0');
$flash          = flash_ol();
?>
<!DOCTYPE html>
<html lang="<?= str_starts_with($_SESSION['til'] ?? 'uz', 'ru') ? 'ru' : 'uz' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($sahifa_sarlavha) ?> — VatanParvar Yaypan Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: {
            colors: {
                'brand-bg':   '#070C1A',
                'brand-muted':'#6B7CA8',
                'brand-text': '#F1F5FF',
            },
            fontFamily: {
                'display': ['Manrope','system-ui','sans-serif'],
                'sans':    ['Inter','system-ui','sans-serif'],
            }
        }}
    };
    </script>
    <!-- Google Fonts olib tashlandi — system fonts ishlatiladi (tezroq) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%233B82F6'/%3E%3Cstop offset='100%25' stop-color='%237C3AED'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='16' fill='url(%23g)'/%3E%3Ctext x='32' y='44' text-anchor='middle' fill='white' font-family='Arial Black' font-size='34' font-weight='900'%3EA%3C/text%3E%3C/svg%3E">

    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        /* Admin panel — har doim dark tema */
        :root {
            --bg:       #070C1A;
            --bg2:      #0C1220;
            --surface:  rgba(255,255,255,0.04);
            --surface2: rgba(255,255,255,0.07);
            --border:   rgba(255,255,255,0.08);
            --border2:  rgba(255,255,255,0.14);
            --blue:     #3B82F6;
            --muted:    #6B7CA8;
            --text:     #F1F5FF;
            --text2:    #CBD5E1;
            --muted:    #6B7CA8;
            --shadow:   rgba(0,0,0,0.4);
        }
        html, body { background:var(--bg); color:var(--text);
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
            -webkit-font-smoothing:antialiased; height:100%; }
        h1,h2,h3,h4 { letter-spacing:-.02em; font-weight:700; color:var(--text); }

        /* Glass card */
        .glass-card {
            background:var(--surface);
            backdrop-filter:blur(20px);
            border:1px solid var(--border);
            border-radius:var(--radius);
            transition:border-color .2s;
        }
        .glass-card:hover { border-color:var(--border2); }

        /* Buttons */
        .btn { display:inline-flex;align-items:center;justify-content:center;gap:.4rem;font-weight:600;border-radius:9px;border:none;cursor:pointer;transition:all .18s;padding:.6rem 1.15rem;font-family:'Inter',sans-serif;font-size:.875rem; }
        .btn:disabled { opacity:.4;cursor:not-allowed; }
        .btn-primary { background:linear-gradient(135deg,#2563EB,#4F46E5);color:#fff;box-shadow:0 4px 14px -4px rgba(59,130,246,.4); }
        .btn-primary:hover:not(:disabled) { background:linear-gradient(135deg,#1D4ED8,#4338CA);transform:translateY(-1px); }
        .btn-ghost { background:rgba(255,255,255,.06);color:#fff;border:1px solid rgba(255,255,255,.12); }
        .btn-ghost:hover:not(:disabled) { background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.2); }
        .btn-success { background:linear-gradient(135deg,#059669,#10B981);color:#fff;box-shadow:0 4px 12px -4px rgba(16,185,129,.4); }
        .btn-success:hover:not(:disabled) { background:linear-gradient(135deg,#047857,#059669); }
        .btn-danger { background:rgba(239,68,68,.12);color:#FCA5A5;border:1px solid rgba(239,68,68,.25); }
        .btn-danger:hover:not(:disabled) { background:rgba(239,68,68,.22); }
        .btn-sm { padding:.45rem .9rem;font-size:.8125rem;border-radius:8px; }
        .btn-xs { padding:.3rem .7rem;font-size:.75rem;border-radius:6px; }

        /* Form fields */
        .field { background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.09);border-radius:9px;padding:.7rem .95rem;color:var(--text);width:100%;font-size:.9rem;transition:border-color .2s,box-shadow .2s,background .2s;outline:none;font-family:'Inter',sans-serif; }
        .field:focus { border-color:var(--blue);background:rgba(59,130,246,.06);box-shadow:0 0 0 3px rgba(59,130,246,.16); }
        .field:hover:not(:focus) { border-color:rgba(255,255,255,.15); }
        .field::placeholder { color:rgba(255,255,255,.28); }
        .field option { background:#111827; }
        .field-label { display:block;font-size:.8rem;color:var(--muted);margin-bottom:.3rem;font-weight:500; }
        textarea.field { resize:vertical; }

        /* Badges */
        .badge { display:inline-flex;align-items:center;padding:.18rem .55rem;border-radius:999px;font-size:.7rem;font-weight:600;line-height:1.4; }
        .badge-blue   { background:rgba(59,130,246,.16);color:#93C5FD;border:1px solid rgba(59,130,246,.2); }
        .badge-green  { background:rgba(16,185,129,.16);color:#6EE7B7;border:1px solid rgba(16,185,129,.2); }
        .badge-yellow { background:rgba(245,158,11,.16);color:#FCD34D;border:1px solid rgba(245,158,11,.2); }
        .badge-red    { background:rgba(239,68,68,.16);color:#FCA5A5;border:1px solid rgba(239,68,68,.2); }
        .badge-purple { background:rgba(124,58,237,.16);color:#C4B5FD;border:1px solid rgba(124,58,237,.2); }
        .badge-gray   { background:rgba(255,255,255,.08);color:#94A3B8;border:1px solid rgba(255,255,255,.1); }

        /* Sidebar nav item */
        .nav-item { display:flex;align-items:center;gap:.7rem;padding:.65rem .85rem;border-radius:9px;font-size:.875rem;transition:all .15s;color:rgba(255,255,255,.55);text-decoration:none; }
        .nav-item:hover { background:rgba(255,255,255,.05);color:rgba(255,255,255,.9); }
        .nav-item.active { background:rgba(59,130,246,.14);color:#93C5FD;font-weight:600; }
        .nav-item .icon { width:1.25rem;text-align:center;flex-shrink:0;font-size:1.1rem;line-height:1; }

        /* Fade up */
        .fade-up { animation:fadeUp .45s ease both; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

        /* Scrollbar */
        ::-webkit-scrollbar { width:5px;height:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1);border-radius:3px; }
        ::-webkit-scrollbar-thumb:hover { background:rgba(255,255,255,.18); }

        /* Alpine cloak */
        [x-cloak] { display:none!important; }
        .tabnum { font-variant-numeric:tabular-nums; }
        ::selection { background:rgba(59,130,246,.3);color:#fff; }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden">

<!-- ── Flash toast ──────────────────────────────────────── -->
<?php if ($flash): ?>
<div x-data="{v:true}" x-show="v" x-init="setTimeout(()=>v=false,5000)"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed top-5 right-5 z-[999] max-w-sm w-full pointer-events-none" x-cloak>
    <div class="glass-card px-4 py-3 flex items-start gap-3 shadow-2xl pointer-events-auto
        <?= $flash['tur']==='muvaffaqiyat' ? 'border-emerald-500/40 bg-emerald-500/[0.05]' : 'border-red-500/40 bg-red-500/[0.05]' ?>">
        <span class="text-xl"><?= $flash['tur']==='muvaffaqiyat' ? '✅' : '❌' ?></span>
        <p class="flex-1 text-sm text-white"><?= e($flash['matn']) ?></p>
        <button @click="v=false" class="text-white/40 hover:text-white text-lg ml-1">×</button>
    </div>
</div>
<?php endif; ?>

<div x-data="{ sb: window.innerWidth >= 1024 }" class="flex h-screen overflow-hidden">

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside x-show="sb"
           x-transition:enter="transition-transform duration-250"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 flex flex-col
                  w-60 h-screen shrink-0 overflow-y-auto
                  bg-[#070C1A] border-r border-white/[0.07]"
           x-cloak>

        <!-- Logo -->
        <div class="flex items-center justify-between px-4 h-14 border-b border-white/[0.06] flex-shrink-0">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600
                             flex items-center justify-center font-display font-black text-white text-sm flex-shrink-0
                             shadow-md shadow-blue-500/25">A</span>
                <div class="min-w-0">
                    <p class="font-display font-bold text-white text-sm leading-none">VatanParvar Yaypan</p>
                    <p class="text-[10px] text-white/35 mt-0.5">Admin Panel</p>
                </div>
            </a>
            <button @click="sb=false" class="lg:hidden p-1.5 rounded-lg hover:bg-white/[0.06] text-white/40 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">
            <!-- Bosh -->
            <p class="text-[10px] font-semibold text-white/25 uppercase tracking-widest px-2 mb-2 mt-1">Asosiy</p>
            <?php
            $menyu_asosiy = [
                'index'            => ['🏠', 'Boshqaruv paneli', 'index.php'],
                'foydalanuvchilar' => ['👥', 'Foydalanuvchilar',  'foydalanuvchilar.php'],
                'tolovlar'         => ['💳', "To'lovlar",         'tolovlar.php',  $kutmoqda_tolov],
                'fikrlar'          => ['💬', 'Fikrlar',           'fikrlar.php',   $kutmoqda_fikr],
            ];
            foreach ($menyu_asosiy as $key => [$ico, $nom, $url, $badge_val]):
                $faol = $admin_sahifa === $key;
                $badge_val = $badge_val ?? 0;
            ?>
            <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
               class="nav-item <?= $faol ? 'active' : '' ?>">
                <span class="icon"><?= $ico ?></span>
                <span class="flex-1"><?= e($nom) ?></span>
                <?php if ($badge_val > 0): ?>
                <span class="badge badge-yellow text-[10px]"><?= $badge_val ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>

            <!-- Kontent -->
            <p class="text-[10px] font-semibold text-white/25 uppercase tracking-widest px-2 mb-2 mt-4">Kontent</p>
            <?php
            $menyu_kontent = [
                'biletlar'    => ['🎫', 'Biletlar',     'biletlar.php'],
                'savollar'    => ['❓', 'Savollar',      'savollar.php'],
                'tariflar'    => ['💎', 'Tariflar',      'tariflar.php'],
                'promokodlar' => ['🎟️','Promo kodlar',  'promokodlar.php'],
            ];
            foreach ($menyu_kontent as $key => [$ico, $nom, $url]):
                $faol = $admin_sahifa === $key;
            ?>
            <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
               class="nav-item <?= $faol ? 'active' : '' ?>">
                <span class="icon"><?= $ico ?></span>
                <span><?= e($nom) ?></span>
            </a>
            <?php endforeach; ?>

            <!-- Tizim -->
            <p class="text-[10px] font-semibold text-white/25 uppercase tracking-widest px-2 mb-2 mt-4">Tizim</p>
            <a href="<?= e(SAYT_URL) ?>/admin/sozlamalar.php"
               class="nav-item <?= $admin_sahifa === 'sozlamalar' ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span>Sozlamalar</span>
            </a>
        </nav>

        <!-- Alt qism: Foydalanuvchi -->
        <div class="px-3 py-3 border-t border-white/[0.06] flex-shrink-0">
            <div class="px-2 py-2 mb-1">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                                flex items-center justify-center font-bold text-white text-xs flex-shrink-0">
                        <?= e(bosh_harflar($f)) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-sm text-white truncate"><?= e($f['ism']) ?></p>
                        <span class="badge <?= $f['rol']==='developer' ? 'badge-purple' : 'badge-blue' ?>" style="font-size:.62rem;padding:.12rem .45rem">
                            <?= strtoupper(e($f['rol'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard"
               class="nav-item text-xs">
                <span class="icon">👤</span>
                <span>Foydalanuvchi panel</span>
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
               class="nav-item text-xs text-red-400/70 hover:text-red-400 hover:bg-red-500/[0.07]">
                <span class="icon">🚪</span>
                <span><?= e(t('chiqish')) ?></span>
            </a>
        </div>
    </aside>

    <!-- Mobil overlay -->
    <div x-show="sb && window.innerWidth < 1024"
         @click="sb = false"
         class="fixed inset-0 z-30 bg-black/60 lg:hidden" x-cloak></div>

    <!-- ══════════ ASOSIY KONTENT ══════════ -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- Topbar -->
        <header class="sticky top-0 z-20 flex items-center gap-3 h-14 px-4
                       bg-[#070C1A]/90 backdrop-blur-2xl border-b border-white/[0.06]
                       flex-shrink-0">

            <!-- Sidebar toggle -->
            <button @click="sb = !sb"
                    class="p-2 rounded-xl hover:bg-white/[0.06] text-white/40 hover:text-white transition flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Sarlavha -->
            <h1 class="font-display font-bold text-base flex-1 truncate"><?= e($sahifa_sarlavha) ?></h1>

            <!-- Badge lar -->
            <div class="flex items-center gap-2">
                <?php if ($kutmoqda_tolov): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
                   class="badge badge-yellow hover:opacity-80 transition cursor-pointer text-xs">
                    ⏳ <?= $kutmoqda_tolov ?>
                </a>
                <?php endif; ?>
                <?php if ($kutmoqda_fikr): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
                   class="badge badge-blue hover:opacity-80 transition cursor-pointer text-xs">
                    💬 <?= $kutmoqda_fikr ?>
                </a>
                <?php endif; ?>

                <!-- Rol badge -->
                <span class="hidden sm:inline badge <?= $f['rol']==='developer' ? 'badge-purple' : 'badge-blue' ?> ml-1">
                    <?= strtoupper(e($f['rol'])) ?>
                </span>
                <span class="hidden sm:inline text-sm text-white/55"><?= e($f['ism']) ?></span>
            </div>
        </header>

        <!-- Kontent area -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-4 sm:p-6">
