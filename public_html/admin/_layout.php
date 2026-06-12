<?php
/**
 * VatanParvar Yaypan — Admin layout (sidebar + header)
 * Har bir admin sahifasi:
 *   $admin_sahifa = 'biletlar';   // sidebar uchun aktiv
 *   require_once '_layout.php';
 *   ... HTML ...
 *   require_once '_layout_end.php';
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$admin_sahifa = $admin_sahifa ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="{sidebar: window.innerWidth > 1024}" class="min-h-screen flex">

    <!-- SIDEBAR -->
    <aside x-show="sidebar" x-transition:enter="transition-transform duration-300"
           x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen overflow-y-auto bg-brand-bg/95 backdrop-blur-xl border-r border-white/10 flex flex-col" x-cloak>

        <div class="p-4 border-b border-white/10 flex items-center gap-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2 flex-1">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white">V</span>
                <span class="font-display font-bold text-white">Admin Panel</span>
            </a>
            <button @click="sidebar=false" class="lg:hidden p-1.5 rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="p-3 flex-1 space-y-1 text-sm">
            <?php
            $menyu = [
                'index'           => ['🏠', t('boshqaruv_paneli'), 'index.php'],
                'biletlar'        => ['🎫', 'Biletlar',            'biletlar.php'],
                'savollar'        => ['❓', 'Savollar',             'savollar.php'],
                'foydalanuvchilar'=> ['👥', 'Foydalanuvchilar',     'foydalanuvchilar.php'],
                'tariflar'        => ['💎', t('tariflar'),          'tariflar.php'],
                'tolovlar'        => ['💳', "To'lovlar",            'tolovlar.php'],
                'fikrlar'         => ['💬', t('fikrlar'),           'fikrlar.php'],
                'sozlamalar'      => ['⚙️', 'Sozlamalar',           'sozlamalar.php'],
            ];
            foreach ($menyu as $key => [$emoji, $nomi, $url]):
                $aktiv = $admin_sahifa === $key;
            ?>
                <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= $aktiv ? 'bg-blue-500/15 text-blue-400 font-medium' : 'hover:bg-white/5 text-white/80' ?>">
                    <span class="text-lg"><?= $emoji ?></span>
                    <span><?= e($nomi) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-t border-white/10">
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/5 text-sm">
                <span>👤</span> <?= e(t('boshqaruv_paneli')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-red-500/10 text-red-400 text-sm">
                <span>🚪</span> <?= e(t('chiqish')) ?>
            </a>
        </div>
    </aside>

    <!-- ASOSIY -->
    <div class="flex-1 min-w-0">
        <!-- Yuqori paneli -->
        <header class="sticky top-0 z-30 backdrop-blur-xl bg-brand-bg/70 border-b border-white/10 h-14 flex items-center px-4 gap-3">
            <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-display font-semibold text-lg flex-1 truncate"><?= e($sahifa_sarlavha) ?></h1>

            <!-- Til almashtirgich -->
            <?php
            $joriy_til = $_SESSION['til'] ?? 'uz_latn';
            $url_til = function (string $til) {
                $param = http_build_query(array_merge($_GET, ['til' => $til]));
                return '?' . $param;
            };
            ?>
            <div class="hidden sm:flex items-center gap-1 text-xs bg-white/5 rounded-lg p-1">
                <a href="<?= e($url_til('uz_latn')) ?>" class="px-2 py-1 rounded <?= $joriy_til === 'uz_latn' ? 'bg-blue-500/30 text-white font-semibold' : 'text-brand-muted hover:text-white' ?>">Lat</a>
                <a href="<?= e($url_til('uz_cyrl')) ?>" class="px-2 py-1 rounded <?= $joriy_til === 'uz_cyrl' ? 'bg-blue-500/30 text-white font-semibold' : 'text-brand-muted hover:text-white' ?>">Кир</a>
            </div>

            <span class="hidden sm:inline text-sm text-brand-muted">
                <?= e($f['ism']) ?>
                <span class="text-xs px-2 py-0.5 ml-1 rounded-full <?= $f['rol'] === 'developer' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400' ?>">
                    <?= e(strtoupper($f['rol'])) ?>
                </span>
            </span>
        </header>

        <!-- Sahifa kontenti -->
        <main class="p-4 sm:p-6">
