<?php
/**
 * VatanParvar Yaypan — Admin layout (sidebar + header)
 * Yorqin tema bilan zamonaviy admin paneli.
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$admin_sahifa = $admin_sahifa ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="{sidebar: window.innerWidth > 1024}" class="min-h-screen flex">

    <!-- SIDEBAR -->
    <aside x-show="sidebar"
           x-transition:enter="transition-transform duration-300 ease-out"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-200 ease-in"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen overflow-y-auto bg-white border-r border-brand-border flex flex-col shadow-soft" x-cloak>

        <div class="p-4 border-b border-brand-border flex items-center gap-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2 flex-1 group">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white shadow-soft group-hover:shadow-glow transition-all">V</span>
                <div>
                    <div class="font-display font-bold text-brand-text text-sm">Admin Panel</div>
                    <div class="text-[10px] text-brand-muted uppercase tracking-wider">VatanParvar</div>
                </div>
            </a>
            <button @click="sidebar=false" class="lg:hidden p-1.5 rounded-lg hover:bg-sky-50 text-brand-muted">
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
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?= $aktiv ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white font-semibold shadow-soft' : 'hover:bg-sky-50 text-brand-body hover:text-sky-700' ?>">
                    <span class="text-lg w-7 h-7 flex items-center justify-center <?= $aktiv ? '' : 'bg-sky-50 rounded-lg' ?>"><?= $emoji ?></span>
                    <span><?= e($nomi) ?></span>
                    <?php if ($aktiv): ?>
                        <span class="ml-auto text-white/80">→</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-t border-brand-border space-y-1">
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-sky-50 text-sm text-brand-body">
                <span class="text-base">👤</span>
                <span>Foydalanuvchi panel</span>
            </a>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-rose-50 text-rose-600 text-sm">
                <span class="text-base">🚪</span>
                <span><?= e(t('chiqish')) ?></span>
            </a>
        </div>
    </aside>

    <!-- ASOSIY -->
    <div class="flex-1 min-w-0 bg-gradient-to-br from-sky-50/30 to-white">
        <!-- Yuqori paneli -->
        <header class="sticky top-0 z-30 backdrop-blur-xl bg-white/80 border-b border-brand-border h-14 flex items-center px-4 gap-3 shadow-soft">
            <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-sky-50 text-brand-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-display font-bold text-base sm:text-lg flex-1 truncate text-brand-text"><?= e($sahifa_sarlavha) ?></h1>
            <span class="hidden sm:flex items-center gap-2 text-sm">
                <span class="text-brand-muted"><?= e($f['ism']) ?></span>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold <?= $f['rol'] === 'developer' ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700' ?>">
                    <?= e(strtoupper($f['rol'])) ?>
                </span>
            </span>
        </header>

        <!-- Sahifa kontenti -->
        <main class="p-4 sm:p-6">
