<?php
/**
 * VatanParvar Yaypan — Admin layout (premium sidebar + header)
 * Har bir admin sahifasi:
 *   $admin_sahifa = 'biletlar';
 *   require_once '_layout.php';
 *   ... HTML ...
 *   require_once '_layout_end.php';
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$admin_sahifa    ??= 'index';
$sahifa_sarlavha ??= t('admin_panel');
$joriy_til       = $_SESSION['til'] ?? 'uz_latn';

$bilan_til = function (string $til): string {
    $q = array_merge($_GET, ['til' => $til]);
    return '?' . http_build_query($q);
};

require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="{sidebar: window.innerWidth > 1024}" class="min-h-screen flex">

    <!-- ============================================================
         SIDEBAR
         ============================================================ -->
    <aside x-show="sidebar"
           x-transition:enter="transition-transform duration-300"
           x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen overflow-y-auto border-r border-app flex flex-col"
           style="background: color-mix(in srgb, var(--bg-primary) 95%, transparent); backdrop-filter: blur(24px);"
           x-cloak>

        <!-- Logo -->
        <div class="p-4 border-b border-app flex items-center gap-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 flex-1 group">
                <?= logo_html('md', true) ?>
                <div class="min-w-0">
                    <div class="font-display font-bold text-app truncate">Admin Panel</div>
                    <div class="text-xs text-muted-app">VatanParvar</div>
                </div>
            </a>
            <button @click="sidebar=false" class="lg:hidden p-1.5 rounded-lg hover:bg-glass text-app">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Menyu -->
        <nav class="p-3 flex-1 space-y-0.5 text-sm">
            <?php
            // [kalit, label, url, SVG path data]
            $menyu = [
                ['index',            t('boshqaruv_paneli'), 'index.php',
                 'M2.25 12 11.204 3.045a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                ['biletlar',         'Biletlar',            'biletlar.php',
                 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-1.5h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z'],
                ['savollar',         'Savollar',            'savollar.php',
                 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z'],
                ['foydalanuvchilar', 'Foydalanuvchilar',    'foydalanuvchilar.php',
                 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                ['tariflar',         t('tariflar'),         'tariflar.php',
                 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5z'],
                ['tolovlar',         "To'lovlar",            'tolovlar.php',
                 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                ['fikrlar',          t('fikrlar'),          'fikrlar.php',
                 'M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z'],
                ['dizayn',           'Dizayn',              'dizayn.php',
                 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z'],
                ['reklamalar',       'Reklamalar',          'reklamalar.php',
                 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46'],
                ['sozlamalar',       'Sozlamalar',          'sozlamalar.php',
                 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 0 1 0 1.255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-1.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z'],
            ];
            foreach ($menyu as [$key, $label, $url, $svg]):
                $aktiv = $admin_sahifa === $key;
            ?>
                <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition relative <?= $aktiv ? 'text-white font-semibold' : 'text-app-2 hover:text-app hover:bg-glass' ?>"
                   <?= $aktiv ? 'style="background: var(--gradient-primary); box-shadow: 0 8px 24px var(--accent-glow);"' : '' ?>>
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="<?= $aktiv ? '2' : '1.7' ?>"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $svg ?>"/></svg>
                    <span class="truncate"><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Pastki menyu -->
        <div class="p-3 border-t border-app space-y-0.5">
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-glass text-app-2 hover:text-app text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                <?= e(t('boshqaruv_paneli')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-red-500/10 text-error text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                <?= e(t('chiqish')) ?>
            </a>
        </div>
    </aside>

    <!-- Mobil overlay -->
    <div x-show="sidebar && window.innerWidth < 1024" @click="sidebar=false"
         class="fixed inset-0 z-30 bg-black/50 lg:hidden" x-cloak></div>

    <!-- ============================================================
         ASOSIY KONTENT
         ============================================================ -->
    <div class="flex-1 min-w-0 flex flex-col">

        <!-- Yuqori panel -->
        <header class="sticky top-0 z-20 border-b border-app h-16 flex items-center px-4 sm:px-6 gap-3"
                style="background: color-mix(in srgb, var(--bg-primary) 75%, transparent); backdrop-filter: blur(20px);">
            <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-glass text-app">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <h1 class="font-display font-bold text-lg flex-1 truncate text-app"><?= e($sahifa_sarlavha) ?></h1>

            <!-- Tema o'tkazgich -->
            <button type="button" onclick="vpToggleTheme()"
                    class="w-9 h-9 rounded-xl border border-app bg-glass hover:bg-surface transition flex items-center justify-center text-app"
                    aria-label="Tema o'zgartirish" title="Tema">
                <svg class="w-4 h-4 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="w-4 h-4 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>

            <!-- Til o'tkazgich -->
            <div class="hidden sm:flex items-center text-xs border border-app bg-glass rounded-xl p-1">
                <a href="<?= e($bilan_til('uz_latn')) ?>"
                   class="px-2.5 py-1.5 rounded-lg transition <?= $joriy_til === 'uz_latn' ? 'text-white font-semibold' : 'text-app-2 hover:text-app' ?>"
                   <?= $joriy_til === 'uz_latn' ? 'style="background: var(--gradient-primary);"' : '' ?>>Lat</a>
                <a href="<?= e($bilan_til('uz_cyrl')) ?>"
                   class="px-2.5 py-1.5 rounded-lg transition <?= $joriy_til === 'uz_cyrl' ? 'text-white font-semibold' : 'text-app-2 hover:text-app' ?>"
                   <?= $joriy_til === 'uz_cyrl' ? 'style="background: var(--gradient-primary);"' : '' ?>>Кир</a>
            </div>

            <!-- Foydalanuvchi badge -->
            <div class="hidden sm:flex items-center gap-2 pl-2 ml-1 border-l border-app">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold text-white shadow-sm"
                      style="background: var(--gradient-primary);">
                    <?= e(bosh_harflar($f)) ?>
                </span>
                <div class="text-sm leading-tight">
                    <div class="text-app font-medium"><?= e(fu_ism($f) ?: $f['ism']) ?></div>
                    <div class="text-xs">
                        <span class="badge <?= $f['rol'] === 'developer' ? 'badge-warning' : 'badge-accent' ?>" style="padding: 1px 6px; font-size: 10px;">
                            <?= e(mb_strtoupper($f['rol'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sahifa kontenti -->
        <main class="p-4 sm:p-6 lg:p-8 flex-1">
