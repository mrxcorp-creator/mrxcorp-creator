<?php
/**
 * VatanParvar Yaypan — Admin layout (brutalizm)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$admin_sahifa    = $admin_sahifa    ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

$kutmoqda_tolov = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');
$kutmoqda_fikr  = (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0');
$flash          = flash_ol();
?>
<!DOCTYPE html>
<html lang="<?= ($_SESSION['til'] ?? 'uz') === 'uz_cyrl' ? 'uz-Cyrl' : 'uz-Latn' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($sahifa_sarlavha) ?> — Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='black' stroke='black' stroke-width='4'/%3E%3Ctext x='32' y='44' text-anchor='middle' fill='white' font-family='Georgia,serif' font-size='34' font-weight='700'%3EV%3C/text%3E%3C/svg%3E">

    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --bg:       #FFFFFF;
            --hover:    #F5F5F5;
            --text:     #000000;
            --muted:    #666666;
            --border:   #000000;
            --border-l: #E5E5E5;
        }
        html, body {
            background: var(--bg); color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100%;
            line-height: 1.5;
        }
        h1, h2, h3, h4 {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700; letter-spacing:-.02em; color: var(--text);
        }

        /* Karta */
        .b-card {
            background: var(--bg);
            border: 1px solid var(--border);
            transition: background-color .15s;
        }
        .b-card-light { background: var(--bg); border: 1px solid var(--border-l); }

        /* Tugmalar */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: .4rem; padding: .65rem 1.25rem;
            font-family: inherit; font-size: .875rem; font-weight: 500;
            border: 1px solid var(--border); background: var(--bg); color: var(--text);
            cursor: pointer; text-decoration: none;
            transition: background-color .15s, color .15s;
            white-space: nowrap;
        }
        .btn:hover    { background: var(--hover); }
        .btn-primary  { background: var(--text); color: var(--bg); border-color: var(--text); }
        .btn-primary:hover { background: var(--bg); color: var(--text); }
        .btn-danger:hover  { background: var(--text); color: var(--bg); }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .btn-sm { padding:.45rem .85rem; font-size:.8rem; }
        .btn-xs { padding:.3rem .65rem; font-size:.72rem; }

        /* Form */
        .field {
            display: block; width: 100%;
            padding: .65rem .9rem;
            background: var(--bg); border: 1px solid var(--border);
            color: var(--text); font-family: inherit; font-size: .9rem;
            outline: none;
            transition: background-color .15s;
        }
        .field:hover { background: var(--hover); }
        .field:focus { outline: 2px solid var(--text); outline-offset: -2px; }
        .field::placeholder { color: var(--muted); opacity: .7; }
        .field option { background: var(--bg); }
        textarea.field { resize: vertical; min-height: 80px; }

        .field-label { display: block; font-size: .8rem; font-weight: 500; color: var(--text); margin-bottom: .35rem; }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
        input[type="number"] { -moz-appearance: textfield; }

        /* Badge */
        .badge {
            display: inline-flex; align-items: center;
            padding: .15rem .55rem; font-size: .72rem; font-weight: 500;
            border: 1px solid var(--border); background: var(--bg); color: var(--text);
        }
        .badge-filled { background: var(--text); color: var(--bg); }
        .badge-light  { background: #F5F5F5; border-color: var(--border-l); color: var(--text); }

        /* Sidebar nav */
        .nav-link {
            display: flex; align-items: center; gap: .65rem;
            padding: .55rem .85rem; font-size: .875rem; color: var(--text);
            text-decoration: none; transition: background-color .15s;
            border-bottom: 1px solid var(--border-l);
        }
        .nav-link:hover  { background: var(--hover); }
        .nav-link.active { background: var(--text); color: var(--bg); font-weight: 600; }

        /* Jadval */
        .b-table { width: 100%; border-collapse: collapse; }
        .b-table th, .b-table td {
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--border-l);
            text-align: left;
        }
        .b-table thead th {
            font-size: .75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .06em;
            border-bottom: 1px solid var(--border);
        }
        .b-table tbody tr:hover { background: var(--hover); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: var(--text); border: 2px solid var(--bg); }

        ::selection { background: var(--text); color: var(--bg); }

        [x-cloak] { display: none !important; }
        .tabnum { font-variant-numeric: tabular-nums; }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>

<body class="min-h-screen">

<?php if ($flash): ?>
<div x-data="{v:true}" x-show="v" x-init="setTimeout(()=>v=false,4000)"
     style="position:fixed; top:1rem; right:1rem; z-index:99; max-width:24rem;"
     x-cloak>
    <div class="b-card" style="padding:.75rem 1rem; display:flex; align-items:center; gap:.75rem;
        <?= ($flash['tur'] ?? '') === 'xato' ? 'background:#000; color:#fff;' : '' ?>">
        <span style="flex:1; font-size:.875rem;"><?= e($flash['matn']) ?></span>
        <button @click="v=false" style="background:none; border:none; color:inherit;
                cursor:pointer; font-size:1.1rem; line-height:1; padding:0 .25rem;">×</button>
    </div>
</div>
<?php endif; ?>

<div x-data="{ sb: window.innerWidth >= 1024 }" style="display:flex; min-height:100vh;">

    <!-- Sidebar -->
    <aside x-show="sb"
           x-transition:enter="transition-transform duration-200"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           class="bg-white border-r border-black"
           style="position:fixed; top:0; left:0; bottom:0;
                  width: 240px; z-index:40;
                  display:flex; flex-direction:column;
                  overflow-y:auto;"
           :class="{ 'lg:sticky': true }"
           x-cloak>

        <!-- Logo -->
        <div style="display:flex; align-items:center; justify-content:space-between;
                    padding: 1rem 1.25rem; border-bottom: 1px solid #000;">
            <a href="<?= e(SAYT_URL) ?>"
               style="display:flex; align-items:center; gap:.55rem; text-decoration:none;">
                <span style="display:flex; align-items:center; justify-content:center;
                             width: 32px; height: 32px; border:1px solid #000;
                             font-family: Georgia, serif; font-weight:700; font-size:.9rem;">V</span>
                <div>
                    <div style="font-family:Georgia,serif; font-weight:700; font-size:.9rem; line-height:1;">
                        <?= e(SAYT_NOMI) ?>
                    </div>
                    <div style="font-size:.7rem; color:#666; margin-top:.15rem;">Admin Panel</div>
                </div>
            </a>
            <button @click="sb = false" class="lg:hidden"
                    style="background:none; border:none; cursor:pointer;
                           font-size:1.1rem; line-height:1; padding:.25rem;">×</button>
        </div>

        <!-- Navigatsiya -->
        <nav style="flex:1; padding:.5rem 0; overflow-y:auto;">

            <div style="padding:.6rem 1.25rem; font-size:.7rem; font-weight:600;
                        text-transform:uppercase; letter-spacing:.08em; color:#666;
                        border-bottom: 1px solid #E5E5E5;">
                Asosiy
            </div>
            <?php
            $menyu_asosiy = [
                'index'            => ['Boshqaruv',         'index.php',          ''],
                'foydalanuvchilar' => ['Foydalanuvchilar',  'foydalanuvchilar.php', ''],
                'tolovlar'         => ['To\'lovlar',         'tolovlar.php',         $kutmoqda_tolov ?: ''],
                'fikrlar'          => ['Fikrlar',           'fikrlar.php',          $kutmoqda_fikr  ?: ''],
            ];
            foreach ($menyu_asosiy as $key => [$nom, $url, $badge]):
                $faol = $admin_sahifa === $key;
            ?>
            <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
               class="nav-link <?= $faol ? 'active' : '' ?>"
               style="<?= $faol ? '' : 'border-bottom-color: #E5E5E5;' ?>">
                <span style="flex:1;"><?= e($nom) ?></span>
                <?php if ($badge): ?>
                <span class="badge <?= $faol ? '' : 'badge-filled' ?>"
                      style="<?= $faol ? 'border-color:#fff; color:#fff;' : '' ?>">
                    <?= $badge ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>

            <div style="padding:.6rem 1.25rem; font-size:.7rem; font-weight:600;
                        text-transform:uppercase; letter-spacing:.08em; color:#666;
                        border-bottom: 1px solid #E5E5E5; margin-top:.5rem;">
                Kontent
            </div>
            <?php
            $menyu_kontent = [
                'biletlar'    => ['Biletlar',     'biletlar.php'],
                'savollar'    => ['Savollar',     'savollar.php'],
                'tariflar'    => ['Tariflar',     'tariflar.php'],
                'promokodlar' => ['Promo kodlar', 'promokodlar.php'],
            ];
            foreach ($menyu_kontent as $key => [$nom, $url]):
                $faol = $admin_sahifa === $key;
            ?>
            <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
               class="nav-link <?= $faol ? 'active' : '' ?>">
                <?= e($nom) ?>
            </a>
            <?php endforeach; ?>

            <div style="padding:.6rem 1.25rem; font-size:.7rem; font-weight:600;
                        text-transform:uppercase; letter-spacing:.08em; color:#666;
                        border-bottom: 1px solid #E5E5E5; margin-top:.5rem;">
                Tizim
            </div>
            <a href="<?= e(SAYT_URL) ?>/admin/sozlamalar.php"
               class="nav-link <?= $admin_sahifa === 'sozlamalar' ? 'active' : '' ?>">
                Sozlamalar
            </a>
        </nav>

        <!-- Foydalanuvchi (alt) -->
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #000;">
            <div style="display:flex; align-items:center; gap:.65rem; margin-bottom:.75rem;">
                <span style="display:flex; align-items:center; justify-content:center;
                             width:32px; height:32px; border:1px solid #000;
                             font-family:Georgia,serif; font-weight:700;
                             font-size:.78rem; flex-shrink:0;">
                    <?= e(bosh_harflar($f)) ?>
                </span>
                <div style="min-width:0;">
                    <div style="font-weight:600; font-size:.825rem;
                                overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= e($f['ism']) ?>
                    </div>
                    <span class="badge badge-filled" style="font-size:.65rem;">
                        <?= strtoupper(e($f['rol'])) ?>
                    </span>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard"
               style="display:block; padding:.5rem .65rem; font-size:.78rem;
                      color:#000; text-decoration:none; border:1px solid #E5E5E5;
                      margin-bottom: .35rem; transition: background-color .15s;"
               onmouseover="this.style.background='#F5F5F5'"
               onmouseout="this.style.background='transparent'">
                ← Foydalanuvchi panel
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
               style="display:block; padding:.5rem .65rem; font-size:.78rem;
                      color:#fff; background:#000; text-decoration:none;
                      text-align:center;">
                Chiqish
            </a>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div x-show="sb && window.innerWidth < 1024"
         @click="sb = false"
         style="position:fixed; inset:0; z-index:30;
                background:rgba(0,0,0,.5);"
         class="lg:hidden" x-cloak></div>

    <!-- Asosiy kontent -->
    <div style="flex:1; min-width:0; display:flex; flex-direction:column;"
         class="lg:ml-[240px]">

        <!-- Topbar -->
        <header style="position:sticky; top:0; z-index:20;
                       background:#fff; border-bottom:1px solid #000;
                       padding: 0 1.25rem; height:60px;
                       display:flex; align-items:center; gap:1rem;">
            <button @click="sb = !sb"
                    style="display:flex; align-items:center; justify-content:center;
                           width:36px; height:36px; border:1px solid #000;
                           background:#fff; cursor:pointer;"
                    onmouseover="this.style.background='#F5F5F5'"
                    onmouseout="this.style.background='#fff'">
                ≡
            </button>
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.1rem; flex:1; min-width:0;
                       overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= e($sahifa_sarlavha) ?>
            </h1>

            <?php if ($kutmoqda_tolov): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
               class="badge badge-filled" style="text-decoration:none;">
                ⏳ <?= $kutmoqda_tolov ?>
            </a>
            <?php endif; ?>
            <?php if ($kutmoqda_fikr): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
               class="badge" style="text-decoration:none;">
                💬 <?= $kutmoqda_fikr ?>
            </a>
            <?php endif; ?>
        </header>

        <!-- Kontent -->
        <main style="flex:1; padding: 1.5rem 1.25rem 3rem;">
