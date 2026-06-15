<?php
/**
 * AvtoTest Pro — Navigatsiya paneli
 * Active route aniq aniqlanadi, hover effektlari polished
 */
$f      = $f ?? joriy_foydalanuvchi();
$curUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$curUrl = SAYT_URL . $curUri;

/** Hozirgi sahifa ekanligini tekshirish */
function nav_faol(string $href): string {
    global $curUrl;
    return str_starts_with($curUrl, $href)
        ? 'text-white bg-white/[0.07] font-semibold'
        : 'text-white/60 hover:text-white hover:bg-white/[0.05]';
}
?>

<nav x-data="{ open: false, scrolled: false }"
     x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 24)"
     :class="scrolled || open ? 'shadow-lg shadow-black/20 bg-[#070C1A]/90' : 'bg-transparent'"
     class="sticky top-0 z-50 backdrop-blur-2xl border-b border-white/[0.06] transition-all duration-300">

    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">

        <!-- ── Logo ─────────────────────────────────────────── -->
        <a href="<?= e(SAYT_URL) ?>"
           class="flex items-center gap-2.5 flex-shrink-0 group mr-2">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600
                         flex items-center justify-center font-display font-black text-white text-base
                         shadow-lg shadow-blue-500/25
                         group-hover:scale-105 group-hover:shadow-blue-500/40 transition-all duration-200">A</span>
            <span class="font-display font-bold text-white hidden sm:block leading-none">
                AvtoTest <span class="text-blue-400">Pro</span>
            </span>
        </a>

        <!-- ── Desktop Menyu ────────────────────────────────── -->
        <div class="hidden md:flex items-center gap-1 flex-1">
            <?php if ($f): ?>
                <?php
                $items = [
                    [SAYT_URL.'/dashboard', '📊', t('boshqaruv_paneli')],
                    [SAYT_URL.'/test',      '📝', t('testlar')],
                    [SAYT_URL.'/tolov',     '💎', t('tariflar')],
                    [SAYT_URL.'/referal',   '🎁', t('referal')],
                ];
                foreach ($items as [$href, $ico, $nom]):
                ?>
                <a href="<?= e($href) ?>"
                   class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm transition-all duration-150 <?= nav_faol($href) ?>">
                    <span class="text-sm leading-none"><?= $ico ?></span>
                    <?= e($nom) ?>
                </a>
                <?php endforeach; ?>

                <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/"
                   class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm transition-all duration-150
                       <?= nav_faol(SAYT_URL.'/admin/') ?>
                       bg-violet-500/10 !text-violet-300 hover:bg-violet-500/20">
                    <span class="text-sm leading-none">⚙️</span>
                    <?= e(t('admin_panel')) ?>
                </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="px-3.5 py-2 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition-all"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar"  class="px-3.5 py-2 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition-all"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa"    class="px-3.5 py-2 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition-all"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Spacer -->
        <div class="flex-1 md:hidden"></div>

        <!-- ── Desktop: O'ng tomon ──────────────────────────── -->
        <div class="hidden md:flex items-center gap-2.5 flex-shrink-0">
            <?php if ($f): ?>

                <!-- Foydalanuvchi dropdown -->
                <div x-data="{ menu: false }" class="relative">
                    <button @click="menu = !menu"
                            @keydown.escape.window="menu = false"
                            class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl
                                   hover:bg-white/[0.06] transition-all duration-150 group">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                                    flex items-center justify-center font-bold text-white text-sm
                                    flex-shrink-0 overflow-hidden ring-2 ring-white/10">
                            <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                                <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>" class="w-full h-full object-cover" alt="">
                            <?php else: ?>
                                <?= e(bosh_harflar($f)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm font-medium text-white/90 max-w-[110px] truncate"><?= e($f['ism']) ?></span>
                        <svg class="w-3.5 h-3.5 text-white/40 transition-transform duration-200"
                             :class="menu ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown panel -->
                    <div x-show="menu"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.outside="menu = false"
                         class="absolute right-0 top-full mt-2.5 w-60 glass-card shadow-2xl shadow-black/40 p-1.5"
                         x-cloak>

                        <!-- User info -->
                        <div class="px-3 py-2.5 mb-1 border-b border-white/[0.06]">
                            <p class="font-semibold text-sm text-white truncate"><?= e($f['ism'].' '.($f['familiya']??'')) ?></p>
                            <p class="text-xs text-white/40 mt-0.5"><?= e($f['telefon']) ?></p>
                            <?php if ((float)($f['bonus_balans']??0) > 0): ?>
                            <p class="text-xs text-emerald-400 mt-1 font-medium">💰 Bonus: <?= e(pul($f['bonus_balans'])) ?></p>
                            <?php endif; ?>
                        </div>

                        <a href="<?= e(SAYT_URL) ?>/profil"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-white/70 hover:text-white hover:bg-white/[0.06] transition-all">
                            <span class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-base">👤</span>
                            <?= e(t('profil')) ?>
                        </a>
                        <a href="<?= e(SAYT_URL) ?>/referal"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-white/70 hover:text-white hover:bg-white/[0.06] transition-all">
                            <span class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-base">🎁</span>
                            <?= e(t('referal')) ?>
                        </a>

                        <div class="border-t border-white/[0.06] mt-1 pt-1">
                            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-red-400/80 hover:text-red-400 hover:bg-red-500/[0.08] transition-all">
                                <span class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center text-base">🚪</span>
                                <?= e(t('chiqish')) ?>
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login"
                   class="btn btn-ghost btn-sm"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register"
                   class="btn btn-primary btn-sm"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- ── Mobile: Hamburger ─────────────────────────────── -->
        <button @click="open = !open"
                class="md:hidden p-2 rounded-lg hover:bg-white/[0.06] transition text-white/70 hover:text-white"
                :aria-label="open ? 'Yopish' : 'Menyu'">
            <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- ── Mobil menyu ───────────────────────────────────────── -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="md:hidden border-t border-white/[0.06] bg-[#070C1A]/98 backdrop-blur-2xl px-4 pb-4"
         x-cloak>

        <?php if ($f): ?>
            <!-- User card -->
            <div class="flex items-center gap-3 py-3 mb-2 border-b border-white/[0.06]">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                            flex items-center justify-center font-bold text-white flex-shrink-0 overflow-hidden">
                    <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                        <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>" class="w-full h-full object-cover" alt="">
                    <?php else: ?>
                        <?= e(bosh_harflar($f)) ?>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold truncate"><?= e($f['ism']) ?> <?= e($f['familiya']??'') ?></p>
                    <p class="text-xs text-white/40"><?= e($f['telefon']) ?></p>
                </div>
            </div>

            <?php
            $mob = [
                [SAYT_URL.'/dashboard', '📊', t('boshqaruv_paneli')],
                [SAYT_URL.'/test',      '📝', t('testlar')],
                [SAYT_URL.'/tolov',     '💎', t('tariflar')],
                [SAYT_URL.'/referal',   '🎁', t('referal')],
                [SAYT_URL.'/profil',    '👤', t('profil')],
            ];
            foreach ($mob as [$href,$ico,$nom]):
            ?>
            <a href="<?= e($href) ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all duration-150
                      <?= nav_faol($href) ?>">
                <span class="text-lg leading-none w-6 text-center"><?= $ico ?></span>
                <?= e($nom) ?>
            </a>
            <?php endforeach; ?>

            <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm bg-violet-500/10 text-violet-300 hover:bg-violet-500/20 transition mt-1">
                <span class="text-lg leading-none w-6 text-center">⚙️</span>
                <?= e(t('admin_panel')) ?>
            </a>
            <?php endif; ?>

            <div class="border-t border-white/[0.06] mt-2 pt-2">
                <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-500/[0.08] transition">
                    <span class="text-lg leading-none w-6 text-center">🚪</span>
                    <?= e(t('chiqish')) ?>
                </a>
            </div>

        <?php else: ?>
            <div class="py-3 space-y-1">
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="block px-3 py-2.5 rounded-xl text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar"  class="block px-3 py-2.5 rounded-xl text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa"    class="block px-3 py-2.5 rounded-xl text-sm text-white/60 hover:text-white hover:bg-white/[0.05] transition"><?= e(t('aloqa')) ?></a>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-white/[0.06]">
                <a href="<?= e(SAYT_URL) ?>/login"    class="btn btn-ghost text-center text-sm py-3"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary text-center text-sm py-3"><?= e(t('royxatdan_otish')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</nav>
