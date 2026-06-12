<?php
/**
 * VatanParvar Yaypan — Navigatsiya paneli (premium)
 * ------------------------------------------------------------
 * Foydalanuvchi va admin uchun universal yuqori panel.
 * Tila va tema o'tkazgich tugmalari joriy URL'ning boshqa parametrlarini
 * saqlaydi (har gal tugma bosilganda filtrlar va tab holati buzilmaydi).
 */
$f ??= joriy_foydalanuvchi();
$joriy_til = $_SESSION['til'] ?? 'uz_latn';

/** Joriy URL ga GET parametrlarni qo'shish (mavjudlarini saqlab) */
$bilan_til = function (string $til): string {
    $q = array_merge($_GET, ['til' => $til]);
    return '?' . http_build_query($q);
};
?>

<nav x-data="{open:false}" class="sticky top-0 z-40 backdrop-blur-xl border-b border-app"
     style="background: color-mix(in srgb, var(--bg-primary) 70%, transparent);">

    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-3">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 group">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-bold text-white text-lg shadow-md transition-transform group-hover:scale-105"
                  style="background: var(--gradient-primary); box-shadow: 0 8px 24px var(--accent-glow);">V</span>
            <span class="font-display font-bold text-app text-lg hidden sm:inline">VatanParvar</span>
        </a>

        <!-- Markaziy menyu (desktop) -->
        <div class="hidden md:flex items-center gap-1">
            <?php if ($f): ?>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('boshqaruv_paneli')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('testlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/referal" class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('referal')) ?></a>
                <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/" class="px-3.5 py-2 rounded-lg badge-accent text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 1.255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-1.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?= e(t('admin_panel')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar"  class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa"    class="px-3.5 py-2 rounded-lg hover:bg-glass transition text-app-2 hover:text-app"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <!-- O'ng tomon: tema, til, foydalanuvchi -->
        <div class="hidden md:flex items-center gap-2">

            <!-- Tema o'tkazgich -->
            <button type="button" onclick="vpToggleTheme()"
                    class="w-10 h-10 rounded-xl border border-app bg-glass hover:bg-surface transition flex items-center justify-center text-app"
                    aria-label="Tema o'zgartirish" title="Tema">
                <!-- Quyosh (ko'rinadi DARK rejimda — light'ga o'tish ma'nosida) -->
                <svg class="w-5 h-5 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <!-- Oy (ko'rinadi LIGHT rejimda — dark'ga o'tish ma'nosida) -->
                <svg class="w-5 h-5 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>

            <!-- Til o'tkazgich -->
            <div class="flex items-center text-xs border border-app bg-glass rounded-xl p-1">
                <a href="<?= e($bilan_til('uz_latn')) ?>"
                   class="px-2.5 py-1.5 rounded-lg transition <?= $joriy_til === 'uz_latn' ? 'text-white shadow-sm font-semibold' : 'text-app-2 hover:text-app' ?>"
                   <?= $joriy_til === 'uz_latn' ? 'style="background: var(--gradient-primary);"' : '' ?>>Lat</a>
                <a href="<?= e($bilan_til('uz_cyrl')) ?>"
                   class="px-2.5 py-1.5 rounded-lg transition <?= $joriy_til === 'uz_cyrl' ? 'text-white shadow-sm font-semibold' : 'text-app-2 hover:text-app' ?>"
                   <?= $joriy_til === 'uz_cyrl' ? 'style="background: var(--gradient-primary);"' : '' ?>>Кир</a>
            </div>

            <?php if ($f): ?>
                <!-- Foydalanuvchi menyusi -->
                <div x-data="{menu:false}" class="relative">
                    <button @click="menu=!menu"
                            class="flex items-center gap-2 hover:bg-glass rounded-xl pl-1.5 pr-3 py-1.5 transition border border-transparent hover:border-app">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold text-white shadow-sm"
                              style="background: var(--gradient-primary);">
                            <?= e(bosh_harflar($f)) ?>
                        </span>
                        <span class="text-sm text-app"><?= e(fu_ism($f) ?: $f['ism']) ?></span>
                        <svg class="w-4 h-4 text-app-2 transition-transform" :class="menu && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="menu" x-transition @click.outside="menu=false"
                         class="absolute right-0 top-full mt-2 w-60 glass-card-premium p-2 shadow-lg" x-cloak>
                        <div class="px-3 py-2 mb-1">
                            <div class="font-medium text-app text-sm"><?= e(fu_ism($f)) ?></div>
                            <div class="text-xs text-muted-app"><?= e($f['telefon']) ?></div>
                        </div>
                        <hr class="divider mb-1">
                        <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-glass text-app text-sm">
                            <svg class="w-4 h-4 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <?= e(t('profil')) ?>
                        </a>
                        <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-glass text-app text-sm">
                            <svg class="w-4 h-4 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0z"/></svg>
                            <?= e(t('referal')) ?>
                        </a>
                        <hr class="divider my-1">
                        <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-red-500/10 text-error text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                            <?= e(t('chiqish')) ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn-ghost text-sm py-2 px-4"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-sm py-2 px-4"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobil menyu tugmasi -->
        <button @click="open=!open" class="md:hidden p-2 rounded-lg hover:bg-glass border border-app text-app">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open"  class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Mobil ochiluvchi menyu -->
    <div x-show="open" x-transition class="md:hidden border-t border-app px-4 py-4 space-y-1.5"
         style="background: color-mix(in srgb, var(--bg-primary) 95%, transparent); backdrop-filter: blur(20px);" x-cloak>

        <!-- Tema + Til o'tkazgich (mobil) -->
        <div class="flex items-center gap-2 mb-3">
            <button type="button" onclick="vpToggleTheme()"
                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border border-app bg-glass text-app text-sm">
                <svg class="w-4 h-4 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="w-4 h-4 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                <span class="theme-icon-light">Yorug'</span>
                <span class="theme-icon-dark">Qorong'i</span>
            </button>
            <div class="flex items-center gap-1 border border-app bg-glass rounded-xl p-1">
                <a href="<?= e($bilan_til('uz_latn')) ?>" class="px-3 py-1.5 rounded-lg text-xs transition <?= $joriy_til === 'uz_latn' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_latn' ? 'style="background: var(--gradient-primary);"' : '' ?>>Lotin</a>
                <a href="<?= e($bilan_til('uz_cyrl')) ?>" class="px-3 py-1.5 rounded-lg text-xs transition <?= $joriy_til === 'uz_cyrl' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_cyrl' ? 'style="background: var(--gradient-primary);"' : '' ?>>Кирилл</a>
            </div>
        </div>

        <?php if ($f): ?>
            <div class="flex items-center gap-3 px-3 py-3 rounded-xl bg-glass mb-3 border border-app">
                <span class="w-11 h-11 rounded-xl flex items-center justify-center font-bold text-white shadow-md"
                      style="background: var(--gradient-primary);"><?= e(bosh_harflar($f)) ?></span>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-app truncate"><?= e(fu_ism($f) ?: $f['ism']) ?></div>
                    <div class="text-xs text-muted-app truncate"><?= e($f['telefon']) ?></div>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="block px-3 py-2.5 rounded-xl hover:bg-glass text-app"><?= e(t('boshqaruv_paneli')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/test"      class="block px-3 py-2.5 rounded-xl hover:bg-glass text-app"><?= e(t('testlar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/tolov"     class="block px-3 py-2.5 rounded-xl hover:bg-glass text-app"><?= e(t('tariflar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/referal"   class="block px-3 py-2.5 rounded-xl hover:bg-glass text-app"><?= e(t('referal')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/profil"    class="block px-3 py-2.5 rounded-xl hover:bg-glass text-app"><?= e(t('profil')) ?></a>
            <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/" class="block px-3 py-2.5 rounded-xl badge-accent text-center font-semibold"><?= e(t('admin_panel')) ?></a>
            <?php endif; ?>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="block px-3 py-2.5 rounded-xl text-error hover:bg-red-500/10"><?= e(t('chiqish')) ?></a>
        <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/login" class="block px-3 py-2.5 rounded-xl border border-app text-center text-app font-semibold"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="block px-3 py-2.5 rounded-xl text-white text-center font-semibold mt-2"
               style="background: var(--gradient-primary);"><?= e(t('royxatdan_otish')) ?></a>
        <?php endif; ?>
    </div>
</nav>

<style>[x-cloak]{display:none!important;}</style>

<!-- Tema o'tkazgich JS (global) -->
<script>
    window.vpToggleTheme = function () {
        var html = document.documentElement;
        var joriy = html.getAttribute('data-theme') || 'dark';
        var yangi = joriy === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', yangi);
        try { localStorage.setItem('vp_theme', yangi); } catch (e) {}

        // Theme-color meta-ni ham yangilab qo'yamiz (mobile browser bar)
        var meta = document.querySelector('meta[name="theme-color"]:not([media])');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'theme-color');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', yangi === 'dark' ? '#0A0F1E' : '#F8FAFC');
    };
</script>
