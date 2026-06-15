<?php
/**
 * AvtoTest Pro — Universal navigatsiya paneli
 */
$f   = $f ?? joriy_foydalanuvchi();
$til = $_SESSION['til'] ?? TIL_DEFAULT;
?>

<nav x-data="{ open: false, scroll: false }"
     x-init="scroll = window.scrollY > 20; window.addEventListener('scroll', () => scroll = window.scrollY > 20)"
     :class="scroll ? 'bg-brand-bg/90 shadow-lg shadow-black/20' : 'bg-transparent'"
     class="sticky top-0 z-50 backdrop-blur-xl border-b border-white/[0.07] transition-all duration-300">

    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 flex-shrink-0">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-display font-black text-white text-base shadow-glow-sm">A</span>
            <span class="font-display font-bold text-white hidden sm:inline">
                AvtoTest <span class="text-blue-400">Pro</span>
            </span>
        </a>

        <!-- Desktop: Markaziy menyu -->
        <div class="hidden md:flex items-center gap-1 flex-1 justify-center">
            <?php if ($f): ?>
                <?php
                $nav_items = [
                    [SAYT_URL . '/dashboard', t('boshqaruv_paneli'), ''],
                    [SAYT_URL . '/test',      t('testlar'),          ''],
                    [SAYT_URL . '/tolov',     t('tariflar'),         ''],
                    [SAYT_URL . '/referal',   t('referal'),          ''],
                ];
                $cur = SAYT_URL . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
                foreach ($nav_items as [$href, $nom, $icon]):
                    $faol = str_starts_with($cur, $href);
                ?>
                    <a href="<?= e($href) ?>"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $faol
                           ? 'bg-blue-500/15 text-blue-400'
                           : 'text-white/70 hover:text-white hover:bg-white/[0.06]' ?>">
                        <?= e($nom) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium bg-indigo-500/15 text-indigo-400 hover:bg-indigo-500/25 transition">
                        ⚙️ <?= e(t('admin_panel')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="px-3.5 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/[0.06] transition"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar"  class="px-3.5 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/[0.06] transition"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa"    class="px-3.5 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/[0.06] transition"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Desktop: O'ng tomon -->
        <div class="hidden md:flex items-center gap-3 flex-shrink-0">
            <?php if ($f): ?>
                <div x-data="{ menu: false }" class="relative">
                    <button @click="menu = !menu" @keydown.escape.window="menu = false"
                            class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-white/[0.06] transition">
                        <!-- Avatar -->
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white flex-shrink-0 overflow-hidden">
                            <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover" alt="">
                            <?php else: ?>
                                <?= e(bosh_harflar($f)) ?>
                            <?php endif; ?>
                        </span>
                        <span class="text-sm font-medium max-w-[120px] truncate"><?= e($f['ism']) ?></span>
                        <svg class="w-4 h-4 text-brand-muted transition-transform" :class="menu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="menu" x-transition @click.outside="menu = false"
                         class="absolute right-0 top-full mt-2 w-56 glass-card p-2 shadow-2xl" x-cloak>
                        <div class="px-3 py-2 border-b border-white/[0.07] mb-2">
                            <p class="text-sm font-semibold truncate"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></p>
                            <p class="text-xs text-brand-muted"><?= e($f['telefon']) ?></p>
                        </div>
                        <a href="<?= e(SAYT_URL) ?>/profil"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[0.06] text-sm transition">👤 <?= e(t('profil')) ?></a>
                        <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[0.06] text-sm transition">🎁 <?= e(t('referal')) ?></a>
                        <?php if ((float)($f['bonus_balans'] ?? 0) > 0): ?>
                        <div class="px-3 py-2 text-xs text-brand-muted">
                            💰 Bonus: <span class="text-green-400 font-semibold"><?= e(pul($f['bonus_balans'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="border-t border-white/[0.07] mt-2 pt-2">
                            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-red-500/10 text-red-400 text-sm transition">
                               🚪 <?= e(t('chiqish')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login"    class="btn-ghost text-sm py-2 px-4"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-sm py-2 px-4"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobil: Hamburger tugmasi -->
        <button @click="open = !open" class="md:hidden p-2 rounded-lg hover:bg-white/[0.06] transition" aria-label="Menyu">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobil ochiluvchi menyu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="md:hidden border-t border-white/[0.07] bg-brand-bg/98 backdrop-blur-xl px-4 py-3 space-y-1"
         x-cloak>
        <?php if ($f): ?>
            <!-- Foydalanuvchi info -->
            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.04] mb-3">
                <span class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white flex-shrink-0 overflow-hidden">
                    <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover" alt="">
                    <?php else: ?>
                        <?= e(bosh_harflar($f)) ?>
                    <?php endif; ?>
                </span>
                <div class="min-w-0">
                    <p class="font-semibold truncate"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></p>
                    <p class="text-xs text-brand-muted"><?= e($f['telefon']) ?></p>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition">📊 <?= e(t('boshqaruv_paneli')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/test"      class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition">📝 <?= e(t('testlar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/tolov"     class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition">💎 <?= e(t('tariflar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/referal"   class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition">🎁 <?= e(t('referal')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/profil"    class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition">👤 <?= e(t('profil')) ?></a>
            <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-indigo-500/15 text-indigo-400">⚙️ <?= e(t('admin_panel')) ?></a>
            <?php endif; ?>
            <div class="pt-2 border-t border-white/[0.07]">
                <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-500/10 transition">🚪 <?= e(t('chiqish')) ?></a>
            </div>
        <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/#tariflar" class="block px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition"><?= e(t('tariflar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/#fikrlar"  class="block px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition"><?= e(t('fikrlar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/#aloqa"    class="block px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition"><?= e(t('aloqa')) ?></a>
            <div class="grid grid-cols-2 gap-2 pt-2">
                <a href="<?= e(SAYT_URL) ?>/login"    class="btn-ghost text-center text-sm py-2.5"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-center text-sm py-2.5"><?= e(t('royxatdan_otish')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</nav>
