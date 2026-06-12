<?php
/**
 * VatanParvar Yaypan — Navigatsiya paneli
 * ------------------------------------------------------------
 * Foydalanuvchi va admin uchun universal yuqori panel.
 */
$f = $f ?? joriy_foydalanuvchi();
?>

<nav x-data="{open:false}" class="sticky top-0 z-40 backdrop-blur-xl bg-brand-bg/70 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white shadow-lg shadow-blue-500/30">V</span>
            <span class="font-display font-bold text-white text-lg hidden sm:inline">VatanParvar</span>
        </a>

        <!-- Markaziy menyu (desktop) -->
        <div class="hidden md:flex items-center gap-2">
            <?php if ($f): ?>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('boshqaruv_paneli')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('testlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/referal" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('referal')) ?></a>
                <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/" class="px-4 py-2 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition">
                        <?= e(t('admin_panel')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa" class="px-4 py-2 rounded-lg hover:bg-white/5 transition"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <!-- O'ng tomon -->
        <div class="hidden md:flex items-center gap-3">
            <!-- Til almashtirgich -->
            <?php $joriy_til = $_SESSION['til'] ?? 'uz_latn'; ?>
            <div class="flex items-center gap-1 text-xs bg-white/5 rounded-lg p-1">
                <a href="?til=uz_latn" class="px-2 py-1 rounded <?= $joriy_til === 'uz_latn' ? 'bg-blue-500/30 text-white font-semibold' : 'text-brand-muted hover:text-white' ?>">Lat</a>
                <a href="?til=uz_cyrl" class="px-2 py-1 rounded <?= $joriy_til === 'uz_cyrl' ? 'bg-blue-500/30 text-white font-semibold' : 'text-brand-muted hover:text-white' ?>">Кир</a>
            </div>

            <?php if ($f): ?>
                <div x-data="{menu:false}" class="relative">
                    <button @click="menu=!menu" class="flex items-center gap-2 hover:bg-white/5 rounded-lg pl-2 pr-3 py-1.5 transition">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white">
                            <?= e(bosh_harflar($f)) ?>
                        </span>
                        <span class="text-sm"><?= e($f['ism']) ?></span>
                    </button>
                    <div x-show="menu" x-transition @click.outside="menu=false"
                         class="absolute right-0 top-full mt-2 w-56 glass-card p-2"
                         x-cloak>
                        <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('profil')) ?></a>
                        <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('referal')) ?></a>
                        <hr class="my-1 border-white/10">
                        <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-red-500/10 text-red-400"><?= e(t('chiqish')) ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn-ghost"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobil menyu tugmasi -->
        <button @click="open=!open" class="md:hidden p-2 rounded-lg hover:bg-white/5">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Mobil ochiluvchi -->
    <div x-show="open" x-transition class="md:hidden border-t border-white/10 bg-brand-bg/95 backdrop-blur-xl px-4 py-3 space-y-1" x-cloak>
        <!-- Til almashtirgich (mobil) -->
        <?php $joriy_til = $_SESSION['til'] ?? 'uz_latn'; ?>
        <div class="flex items-center gap-2 px-3 py-2 mb-2">
            <span class="text-xs text-brand-muted">Til:</span>
            <a href="?til=uz_latn" class="px-3 py-1 rounded-lg text-xs <?= $joriy_til === 'uz_latn' ? 'bg-blue-500/30 text-white font-semibold' : 'bg-white/5 text-brand-muted' ?>">Lotin</a>
            <a href="?til=uz_cyrl" class="px-3 py-1 rounded-lg text-xs <?= $joriy_til === 'uz_cyrl' ? 'bg-blue-500/30 text-white font-semibold' : 'bg-white/5 text-brand-muted' ?>">Кирилл</a>
        </div>

        <?php if ($f): ?>
            <div class="flex items-center gap-3 px-3 py-3 rounded-lg bg-white/5 mb-2">
                <span class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white"><?= e(bosh_harflar($f)) ?></span>
                <div>
                    <div class="font-medium"><?= e($f['ism']) ?></div>
                    <div class="text-xs text-brand-muted"><?= e($f['telefon']) ?></div>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('boshqaruv_paneli')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/test" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('testlar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('tariflar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/referal" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('referal')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/profil" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('profil')) ?></a>
            <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/" class="block px-3 py-2 rounded-lg bg-blue-500/10 text-blue-400"><?= e(t('admin_panel')) ?></a>
            <?php endif; ?>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="block px-3 py-2 rounded-lg text-red-400 hover:bg-red-500/10"><?= e(t('chiqish')) ?></a>
        <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/login" class="block px-3 py-2 rounded-lg hover:bg-white/5"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="block px-3 py-2 rounded-lg bg-blue-600 text-center font-semibold"><?= e(t('royxatdan_otish')) ?></a>
        <?php endif; ?>
    </div>
</nav>

<style>[x-cloak]{display:none!important;}</style>
