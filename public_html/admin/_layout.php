<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$admin_sahifa = $admin_sahifa ?? 'index';
$sahifa_sarlavha = $sahifa_sarlavha ?? t('admin_panel');

require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="{sidebar: window.innerWidth > 1024}" class="min-h-screen flex">

    <aside x-show="sidebar"
           x-transition:enter="transition-transform duration-300"
           x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-300"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen overflow-y-auto bg-bg/95 backdrop-blur-2xl border-r border-white/10 flex flex-col" x-cloak>

        <div class="p-4 border-b border-white/10 flex items-center gap-2.5">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 flex-1">
                <img src="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg" alt="" class="w-9 h-9 rounded-xl">
                <div class="min-w-0">
                    <div class="font-display font-extrabold text-sm leading-tight">VatanParvar</div>
                    <div class="text-xs grad-text font-bold leading-tight">Admin Panel</div>
                </div>
            </a>
            <button @click="sidebar=false" class="lg:hidden p-1.5 rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="p-3 flex-1 space-y-1 text-sm">
            <?php
            $kutilgan_tolov = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');
            $kutilgan_fikr  = (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0');

            $menyu = [
                'index'           => ['🏠', t('boshqaruv_paneli'), 'index.php', 0],
                'biletlar'        => ['🎫', t('admin_biletlar'),    'biletlar.php', 0],
                'savollar'        => ['❓', t('admin_savollar'),    'savollar.php', 0],
                'savollar_import' => ['📥', 'Savol import',          'savollar_import.php', 0],
                'foydalanuvchilar'=> ['👥', t('admin_foydalanuvchilar'), 'foydalanuvchilar.php', 0],
                'tariflar'        => ['💎', t('tariflar'),          'tariflar.php', 0],
                'tolovlar'        => ['💳', t('admin_tolovlar'),    'tolovlar.php', $kutilgan_tolov],
                'fikrlar'         => ['💬', t('fikrlar'),           'fikrlar.php', $kutilgan_fikr],
                'sozlamalar'      => ['⚙️', t('admin_sozlamalar'),  'sozlamalar.php', 0],
                'dizayn'          => ['🎨', t('admin_dizayn'),      'dizayn.php', 0],
                'promo_kodlar'    => ['🎟️', t('admin_promo'),       'promo_kodlar.php', 0],
                'telegram'        => ['📱', 'Telegram',              'telegram.php', 0],
                'bloklangan_iplar'=> ['🚫', t('admin_bloklangan'),  'bloklangan_iplar.php', 0],
                'tools'           => ['🔧', t('admin_tools'),       'tools.php', 0],
                'auditlar'        => ['📜', t('admin_audit'),       'auditlar.php', 0],
            ];
            if ($f['rol'] === 'developer') {
                $menyu['check']   = ['🔍', t('admin_diagnostika'), '../check.php', 0];
                $menyu['migrate'] = ['🗄️', 'Migrations', '../migrate.php', 0];
                $menyu['xatolar'] = ['🐛', t('admin_xatolar'), '../xatolarni_kor.php', 0];
            }
            foreach ($menyu as $key => [$emoji, $nomi, $url, $badge]):
                $aktiv = $admin_sahifa === $key;
            ?>
                <a href="<?= e(SAYT_URL) ?>/admin/<?= $url ?>"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $aktiv ? 'grad-bg-soft text-white font-semibold border border-violet/30' : 'hover:bg-white/5 text-white/80' ?>">
                    <span class="text-lg w-5 text-center"><?= $emoji ?></span>
                    <span class="flex-1"><?= e($nomi) ?></span>
                    <?php if ($badge > 0): ?>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-pink/20 text-pink font-bold"><?= $badge ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-t border-white/10 space-y-1">
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/5 text-sm">
                <span class="w-5 text-center">👤</span> <?= e(t('boshqaruv_paneli')) ?>
            </a>
            <form method="POST" action="<?= e(SAYT_URL) ?>/auth/logout.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-danger/10 text-danger text-sm">
                    <span class="w-5 text-center">🚪</span> <?= e(t('chiqish')) ?>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-30 backdrop-blur-2xl bg-bg/70 border-b border-white/10 h-14 flex items-center px-4 gap-3">
            <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-display font-bold text-lg flex-1 truncate"><?= e($sahifa_sarlavha) ?></h1>
            <span class="hidden sm:flex items-center gap-2 text-sm text-muted">
                <?= e($f['ism']) ?>
                <span class="chip text-xs !py-0.5 !px-2 <?= $f['rol'] === 'developer' ? 'bg-violet/15 text-violet border-violet/30' : 'bg-cyan/15 text-cyan border-cyan/30' ?>">
                    <?= e(strtoupper($f['rol'])) ?>
                </span>
            </span>
        </header>

        <main class="p-4 sm:p-6">
