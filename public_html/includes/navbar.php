<?php $f = $f ?? joriy_foydalanuvchi(); ?>

<nav x-data="{open:false}" class="sticky top-0 z-40 backdrop-blur-2xl bg-bg/70 border-b border-white/5">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-3">

        <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 group">
            <img src="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg" alt="" class="w-9 h-9 rounded-xl group-hover:scale-110 transition">
            <span class="font-display font-extrabold text-white text-lg hidden sm:inline">
                VatanParvar <span class="grad-text">Yaypan</span>
            </span>
        </a>

        <div class="hidden md:flex items-center gap-1">
            <?php if ($f): ?>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('boshqaruv_paneli')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('testlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/referal" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('referal')) ?></a>
                <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/" class="px-3 py-2 rounded-lg grad-bg-soft text-violet hover:opacity-90 transition text-sm font-semibold border border-violet/30">
                        ⚡ <?= e(t('admin_panel')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/#xususiyatlar" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('xususiyatlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#tariflar" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#fikrlar" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('fikrlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/#aloqa" class="px-3 py-2 rounded-lg hover:bg-white/5 transition text-sm"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <div class="hidden md:flex items-center gap-2">
            <?php if ($f): ?>
                <div x-data="{menu:false}" class="relative">
                    <button @click="menu=!menu" class="flex items-center gap-2 hover:bg-white/5 rounded-lg pl-1.5 pr-3 py-1.5 transition">
                        <span class="w-8 h-8 rounded-full grad-bg flex items-center justify-center text-sm font-bold text-white overflow-hidden">
                            <?php if ($f['avatar'] && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= e(bosh_harflar($f)) ?>
                            <?php endif; ?>
                        </span>
                        <span class="text-sm"><?= e($f['ism']) ?></span>
                        <svg class="w-3 h-3 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5 5 5-5z"/></svg>
                    </button>
                    <div x-show="menu" x-transition @click.outside="menu=false" x-cloak
                         class="absolute right-0 top-full mt-2 w-60 glass-strong p-2">
                        <div class="px-3 py-2 mb-1 border-b border-white/10">
                            <div class="text-xs text-muted">A'zo</div>
                            <div class="text-sm truncate"><?= e($f['telefon']) ?></div>
                        </div>
                        <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/5 text-sm">👤 <?= e(t('profil')) ?></a>
                        <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/5 text-sm">🎁 <?= e(t('referal')) ?></a>
                        <hr class="my-1 border-white/10">
                        <form method="POST" action="<?= e(SAYT_URL) ?>/auth/logout.php" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-danger/10 text-danger text-sm">🚪 <?= e(t('chiqish')) ?></button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-ghost text-sm py-2 px-4"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary text-sm py-2 px-4"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <button @click="open=!open" class="md:hidden p-2 rounded-lg hover:bg-white/5">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="open" x-transition class="md:hidden border-t border-white/10 bg-bg/95 backdrop-blur-xl px-4 py-3 space-y-1" x-cloak>
        <?php if ($f): ?>
            <div class="flex items-center gap-3 px-3 py-3 rounded-xl glass mb-3">
                <span class="w-11 h-11 rounded-full grad-bg flex items-center justify-center font-bold text-white overflow-hidden">
                    <?php if ($f['avatar'] && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(bosh_harflar($f)) ?>
                    <?php endif; ?>
                </span>
                <div class="min-w-0">
                    <div class="font-medium truncate"><?= e($f['ism']) ?></div>
                    <div class="text-xs text-muted truncate"><?= e($f['telefon']) ?></div>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('boshqaruv_paneli')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/test" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('testlar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('tariflar')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/referal" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('referal')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/profil" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('profil')) ?></a>
            <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/" class="block px-4 py-2.5 rounded-lg grad-bg-soft text-violet font-semibold border border-violet/30">⚡ <?= e(t('admin_panel')) ?></a>
            <?php endif; ?>
            <form method="POST" action="<?= e(SAYT_URL) ?>/auth/logout.php" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="w-full text-left block px-4 py-2.5 rounded-lg text-danger hover:bg-danger/10"><?= e(t('chiqish')) ?></button>
            </form>
        <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/login" class="block px-4 py-2.5 rounded-lg hover:bg-white/5"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="block px-4 py-3 rounded-lg grad-bg text-center font-semibold text-white"><?= e(t('royxatdan_otish')) ?></a>
        <?php endif; ?>
    </div>
</nav>
