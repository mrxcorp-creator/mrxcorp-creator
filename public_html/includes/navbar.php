<?php
/**
 * VatanParvar Yaypan — Brutalizm navbar
 * Faqat tipografika va qora ramkalar
 */
$f      = $f ?? joriy_foydalanuvchi();
$curUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

function nav_active(string $href): bool {
    global $curUri;
    $path = parse_url($href, PHP_URL_PATH) ?: '/';
    return $curUri === $path
        || ($path !== '/' && str_starts_with($curUri, $path));
}
?>

<nav x-data="{ open: false }"
     class="border-b border-black bg-white sticky top-0 z-40">

    <div style="max-width:1200px; margin:0 auto;
                padding: 0 1.25rem; height: 64px;
                display:flex; align-items:center; gap: 1.5rem;">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>"
           style="display:flex; align-items:center; gap:.65rem;
                  text-decoration:none; flex-shrink:0;">
            <span style="display:inline-flex; align-items:center; justify-content:center;
                         width:38px; height:38px;
                         border:1px solid #000; background:#fff;
                         font-family:Georgia,serif; font-weight:700;
                         font-size:1.05rem; color:#000;">V</span>
            <span style="font-family:Georgia,serif; font-weight:700;
                         font-size:1.05rem; letter-spacing:-.01em;
                         color:#000; display:none;"
                  class="sm:inline-block">
                <?= e(SAYT_NOMI) ?>
            </span>
        </a>

        <!-- Desktop menyu -->
        <div style="display:none; flex:1; gap:.25rem;"
             class="md:flex md:items-center">
            <?php if ($f): ?>
                <?php
                $items = [
                    [SAYT_URL.'/dashboard', t('boshqaruv_paneli')],
                    [SAYT_URL.'/test',      t('testlar')],
                    [SAYT_URL.'/tolov',     t('tariflar')],
                    [SAYT_URL.'/referal',   t('referal')],
                ];
                foreach ($items as [$href, $nom]):
                    $active = nav_active($href);
                ?>
                <a href="<?= e($href) ?>"
                   style="padding:.5rem .9rem;
                          font-size:.9rem; font-weight:500;
                          color:#000; text-decoration: <?= $active ? 'underline' : 'none' ?>;
                          text-underline-offset: 5px;
                          transition: background-color .15s;
                          background: <?= $active ? '#F5F5F5' : 'transparent' ?>;"
                   onmouseover="this.style.background='#F5F5F5'"
                   onmouseout="this.style.background='<?= $active ? '#F5F5F5' : 'transparent' ?>'">
                    <?= e($nom) ?>
                </a>
                <?php endforeach; ?>

                <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/"
                   style="padding:.5rem .9rem; font-size:.9rem; font-weight:500;
                          color:#000; text-decoration:none;
                          background:#000; color:#fff;
                          margin-left:.5rem; transition:opacity .15s;"
                   onmouseover="this.style.opacity='.85'"
                   onmouseout="this.style.opacity='1'">
                    <?= e(t('admin_panel')) ?>
                </a>
                <?php endif; ?>
            <?php else: ?>
                <?php
                $pub = [
                    [SAYT_URL.'/#tariflar', t('tariflar')],
                    [SAYT_URL.'/#fikrlar',  t('fikrlar')],
                    [SAYT_URL.'/#aloqa',    t('aloqa')],
                ];
                foreach ($pub as [$href, $nom]):
                ?>
                <a href="<?= e($href) ?>"
                   style="padding:.5rem .9rem; font-size:.9rem; font-weight:500;
                          color:#000; text-decoration:none;"
                   onmouseover="this.style.background='#F5F5F5'"
                   onmouseout="this.style.background='transparent'">
                    <?= e($nom) ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Bo'sh joy mobile uchun -->
        <div style="flex:1;" class="md:hidden"></div>

        <!-- Desktop o'ng tomon -->
        <div style="display:none; align-items:center; gap:.5rem;"
             class="md:flex">

            <?php if ($f): ?>
            <!-- Profil dropdown -->
            <div x-data="{ menu: false }" style="position:relative;">
                <button @click="menu = !menu"
                        style="display:flex; align-items:center; gap:.5rem;
                               padding:.4rem .75rem; border:1px solid #000;
                               background:#fff; cursor:pointer;
                               font-family:inherit; transition:background-color .15s;"
                        onmouseover="this.style.background='#F5F5F5'"
                        onmouseout="this.style.background='#fff'">
                    <span style="display:flex; align-items:center; justify-content:center;
                                 width:24px; height:24px; border:1px solid #000;
                                 font-family:Georgia,serif; font-weight:700;
                                 font-size:.7rem; flex-shrink:0;
                                 background:#fff; color:#000;">
                        <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                            <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>"
                                 style="width:100%; height:100%; object-fit:cover;" alt="">
                        <?php else: ?>
                            <?= e(bosh_harflar($f)) ?>
                        <?php endif; ?>
                    </span>
                    <span style="font-size:.875rem; font-weight:500; max-width:100px;
                                 overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= e($f['ism']) ?>
                    </span>
                </button>

                <div x-show="menu" @click.outside="menu = false"
                     x-transition
                     style="position:absolute; right:0; top:calc(100% + 4px);
                            min-width:200px; border:1px solid #000;
                            background:#fff;"
                     x-cloak>
                    <div style="padding:.75rem .9rem; border-bottom:1px solid #E5E5E5;">
                        <div style="font-weight:600; font-size:.875rem;
                                    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= e($f['ism'].' '.($f['familiya'] ?? '')) ?>
                        </div>
                        <div style="font-size:.75rem; color:#666; margin-top:.15rem;">
                            <?= e($f['telefon']) ?>
                        </div>
                        <?php if ((float)($f['bonus_balans'] ?? 0) > 0): ?>
                        <div style="font-size:.75rem; margin-top:.25rem; font-weight:600;">
                            <?= e(pul($f['bonus_balans'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= e(SAYT_URL) ?>/profil"
                       style="display:block; padding:.6rem .9rem; font-size:.875rem;
                              text-decoration:none; color:#000;"
                       onmouseover="this.style.background='#F5F5F5'"
                       onmouseout="this.style.background='transparent'">
                        <?= e(t('profil')) ?>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/referal"
                       style="display:block; padding:.6rem .9rem; font-size:.875rem;
                              text-decoration:none; color:#000;"
                       onmouseover="this.style.background='#F5F5F5'"
                       onmouseout="this.style.background='transparent'">
                        <?= e(t('referal')) ?>
                    </a>
                    <div style="border-top:1px solid #E5E5E5;">
                        <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                           style="display:block; padding:.6rem .9rem; font-size:.875rem;
                                  text-decoration:none; color:#000;"
                           onmouseover="this.style.background='#000';this.style.color='#fff'"
                           onmouseout="this.style.background='transparent';this.style.color='#000'">
                            <?= e(t('chiqish')) ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/login"    class="btn btn-ghost btn-sm"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-sm"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Hamburger mobil -->
        <button @click="open = !open"
                style="display:flex; align-items:center; justify-content:center;
                       width:38px; height:38px; border:1px solid #000;
                       background:#fff; cursor:pointer; flex-shrink:0;"
                class="md:hidden"
                onmouseover="this.style.background='#F5F5F5'"
                onmouseout="this.style.background='#fff'">
            <span x-show="!open" style="font-size:1.2rem; line-height:1;">≡</span>
            <span x-show="open" style="font-size:1.2rem; line-height:1;" x-cloak>×</span>
        </button>
    </div>

    <!-- Mobil menyu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="border-top:1px solid #000; background:#fff;"
         class="md:hidden"
         x-cloak>

        <?php if ($f): ?>
        <!-- User info -->
        <div style="padding:1rem 1.25rem; border-bottom:1px solid #E5E5E5;
                    display:flex; align-items:center; gap:.75rem;">
            <span style="display:flex; align-items:center; justify-content:center;
                         width:40px; height:40px; border:1px solid #000;
                         font-family:Georgia,serif; font-weight:700; flex-shrink:0;">
                <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </span>
            <div style="min-width:0;">
                <div style="font-weight:600; font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= e($f['ism'].' '.($f['familiya'] ?? '')) ?>
                </div>
                <div style="font-size:.75rem; color:#666;"><?= e($f['telefon']) ?></div>
            </div>
        </div>

        <?php
        $mob_items = [
            [SAYT_URL.'/dashboard', t('boshqaruv_paneli')],
            [SAYT_URL.'/test',      t('testlar')],
            [SAYT_URL.'/tolov',     t('tariflar')],
            [SAYT_URL.'/referal',   t('referal')],
            [SAYT_URL.'/profil',    t('profil')],
        ];
        foreach ($mob_items as [$href, $nom]):
            $active = nav_active($href);
        ?>
        <a href="<?= e($href) ?>"
           style="display:block; padding:.85rem 1.25rem; font-size:.95rem;
                  font-weight:<?= $active ? '600' : '500' ?>;
                  color:#000; text-decoration:none;
                  border-bottom:1px solid #E5E5E5;
                  background: <?= $active ? '#F5F5F5' : 'transparent' ?>;">
            <?= e($nom) ?>
        </a>
        <?php endforeach; ?>

        <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
        <a href="<?= e(SAYT_URL) ?>/admin/"
           style="display:block; padding:.85rem 1.25rem; font-size:.95rem;
                  font-weight:600; color:#fff; background:#000;
                  text-decoration:none; border-bottom:1px solid #E5E5E5;">
            <?= e(t('admin_panel')) ?>
        </a>
        <?php endif; ?>

        <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
           style="display:block; padding:.85rem 1.25rem; font-size:.95rem;
                  color:#000; text-decoration:none;">
            <?= e(t('chiqish')) ?>
        </a>

        <?php else: ?>
        <a href="<?= e(SAYT_URL) ?>/#tariflar" style="display:block; padding:.85rem 1.25rem; font-size:.95rem; color:#000; text-decoration:none; border-bottom:1px solid #E5E5E5;"><?= e(t('tariflar')) ?></a>
        <a href="<?= e(SAYT_URL) ?>/#fikrlar"  style="display:block; padding:.85rem 1.25rem; font-size:.95rem; color:#000; text-decoration:none; border-bottom:1px solid #E5E5E5;"><?= e(t('fikrlar')) ?></a>
        <a href="<?= e(SAYT_URL) ?>/#aloqa"    style="display:block; padding:.85rem 1.25rem; font-size:.95rem; color:#000; text-decoration:none; border-bottom:1px solid #E5E5E5;"><?= e(t('aloqa')) ?></a>

        <div style="padding:1rem 1.25rem; display:grid; grid-template-columns:1fr 1fr; gap:.5rem;">
            <a href="<?= e(SAYT_URL) ?>/login"    class="btn btn-ghost"   style="text-align:center;"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary" style="text-align:center;"><?= e(t('royxatdan_otish')) ?></a>
        </div>
        <?php endif; ?>
    </div>
</nav>
