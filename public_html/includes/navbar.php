<?php
/**
 * VatanParvar Yaypan — Navigatsiya paneli (light/dark qo'llab-quvvatlash)
 */
$f      = $f ?? joriy_foydalanuvchi();
$curUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$curUrl = SAYT_URL . $curUri;

function nav_active(string $href): bool {
    global $curUrl;
    return str_starts_with($curUrl, $href);
}
?>

<nav x-data="{ open: false, scrolled: false }"
     x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)"
     class="sticky top-0 z-50 transition-all duration-300"
     :class="scrolled || open ? 'shadow-lg' : ''"
     style="backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            background: var(--nav-bg); border-bottom: 1px solid var(--border);">

    <div style="max-width:1280px; margin:0 auto; padding:0 1rem;
                height:64px; display:flex; align-items:center; gap:1rem;">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>"
           style="display:flex;align-items:center;gap:.65rem;text-decoration:none;flex-shrink:0;"
           class="group">
            <span style="width:36px;height:36px;border-radius:10px;
                         background:linear-gradient(135deg,#2563EB,#7C3AED);
                         display:flex;align-items:center;justify-content:center;
                         font-weight:900;color:#fff;font-size:.95rem;
                         box-shadow:0 3px 10px rgba(37,99,235,0.35);
                         transition:transform .2s;"
                  class="group-hover:scale-105">V</span>
            <span style="font-weight:800;font-size:1.1rem;color:var(--text);display:none;"
                  class="sm:block"><?= e(SAYT_NOMI) ?></span>
        </a>

        <!-- Desktop menyu -->
        <div style="display:none;flex:1;gap:.25rem;" class="md:flex items-center">
            <?php if ($f): ?>
                <?php
                $items = [
                    [SAYT_URL.'/dashboard', '📊', t('boshqaruv_paneli')],
                    [SAYT_URL.'/test',      '📝', t('testlar')],
                    [SAYT_URL.'/tolov',     '💎', t('tariflar')],
                    [SAYT_URL.'/referal',   '🎁', t('referal')],
                ];
                foreach ($items as [$href, $ico, $nom]):
                    $active = nav_active($href);
                ?>
                <a href="<?= e($href) ?>"
                   style="display:flex;align-items:center;gap:.4rem;padding:.5rem .85rem;
                          border-radius:9px;text-decoration:none;font-size:.875rem;
                          font-weight:<?= $active ? '600' : '500' ?>;
                          transition:all .15s;
                          color: <?= $active ? 'var(--blue)' : 'var(--muted)' ?>;
                          background: <?= $active ? 'rgba(37,99,235,0.1)' : 'transparent' ?>;"
                   onmouseover="this.style.background='<?= $active ? 'rgba(37,99,235,0.12)' : 'var(--surface)' ?>';this.style.color='<?= $active ? 'var(--blue)' : 'var(--text)' ?>'"
                   onmouseout="this.style.background='<?= $active ? 'rgba(37,99,235,0.1)' : 'transparent' ?>';this.style.color='<?= $active ? 'var(--blue)' : 'var(--muted)' ?>'">
                    <span><?= $ico ?></span>
                    <?= e($nom) ?>
                </a>
                <?php endforeach; ?>

                <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/"
                   style="display:flex;align-items:center;gap:.4rem;padding:.5rem .85rem;
                          border-radius:9px;text-decoration:none;font-size:.875rem;font-weight:600;
                          background:rgba(124,58,237,0.1);color:var(--violet);transition:all .15s;"
                   onmouseover="this.style.background='rgba(124,58,237,0.18)'"
                   onmouseout="this.style.background='rgba(124,58,237,0.1)'">
                    ⚙️ <?= e(t('admin_panel')) ?>
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
                   style="padding:.5rem .85rem;border-radius:9px;text-decoration:none;
                          font-size:.875rem;font-weight:500;color:var(--muted);transition:all .15s;"
                   onmouseover="this.style.background='var(--surface)';this.style.color='var(--text)'"
                   onmouseout="this.style.background='transparent';this.style.color='var(--muted)'">
                    <?= e($nom) ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Bo'shliq -->
        <div style="flex:1;" class="md:hidden"></div>

        <!-- Desktop: o'ng tomon -->
        <div style="display:none;align-items:center;gap:.65rem;flex-shrink:0;" class="md:flex">

            <!-- Tema tugmasi -->
            <button onclick="toggleTheme()"
                    class="theme-btn" title="Tema almashtirish">
                <span id="theme-icon-nav">🌙</span>
            </button>

            <?php if ($f): ?>
            <div x-data="{ menu: false }" style="position:relative;">
                <button @click="menu = !menu" @keydown.escape.window="menu = false"
                        style="display:flex;align-items:center;gap:.55rem;padding:.4rem .65rem .4rem .4rem;
                               border-radius:10px;border:none;cursor:pointer;background:transparent;
                               color:var(--text);transition:background .15s;"
                        onmouseover="this.style.background='var(--surface)'"
                        onmouseout="this.style.background='transparent'">
                    <!-- Avatar -->
                    <div style="width:32px;height:32px;border-radius:50%;
                                background:linear-gradient(135deg,#2563EB,#7C3AED);
                                display:flex;align-items:center;justify-content:center;
                                font-weight:700;color:#fff;font-size:.78rem;
                                flex-shrink:0;overflow:hidden;">
                        <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                            <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;" alt="">
                        <?php else: ?>
                            <?= e(bosh_harflar($f)) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:.875rem;font-weight:600;max-width:110px;
                                 overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($f['ism']) ?>
                    </span>
                    <svg :class="menu ? 'rotate-180' : ''"
                         style="width:14px;height:14px;color:var(--muted);transition:transform .2s;"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div x-show="menu" @click.outside="menu = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="glass-card"
                     style="position:absolute;right:0;top:calc(100% + .5rem);
                            width:220px;padding:.5rem;
                            box-shadow:0 12px 32px -8px var(--shadow);"
                     x-cloak>
                    <div style="padding:.75rem .875rem;border-bottom:1px solid var(--border);margin-bottom:.35rem;">
                        <p style="font-weight:600;font-size:.875rem;color:var(--text);
                                  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= e($f['ism'].' '.($f['familiya']??'')) ?>
                        </p>
                        <p style="font-size:.75rem;color:var(--muted);margin-top:.15rem;">
                            <?= e($f['telefon']) ?>
                        </p>
                        <?php if ((float)($f['bonus_balans']??0) > 0): ?>
                        <p style="font-size:.72rem;color:#10B981;margin-top:.25rem;font-weight:600;">
                            💰 <?= e(pul($f['bonus_balans'])) ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <?php
                    $dd_items = [
                        [SAYT_URL.'/profil',  '👤', t('profil')],
                        [SAYT_URL.'/referal', '🎁', t('referal')],
                    ];
                    foreach ($dd_items as [$href,$ico,$nom]):
                    ?>
                    <a href="<?= e($href) ?>"
                       style="display:flex;align-items:center;gap:.65rem;padding:.55rem .875rem;
                              border-radius:8px;text-decoration:none;font-size:.875rem;
                              color:var(--text2);transition:background .15s;"
                       onmouseover="this.style.background='var(--surface)'"
                       onmouseout="this.style.background='transparent'">
                        <span><?= $ico ?></span><?= e($nom) ?>
                    </a>
                    <?php endforeach; ?>

                    <div style="border-top:1px solid var(--border);margin-top:.35rem;padding-top:.35rem;">
                        <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
                           style="display:flex;align-items:center;gap:.65rem;padding:.55rem .875rem;
                                  border-radius:8px;text-decoration:none;font-size:.875rem;
                                  color:#FCA5A5;transition:background .15s;"
                           onmouseover="this.style.background='rgba(239,68,68,0.08)'"
                           onmouseout="this.style.background='transparent'">
                            🚪 <?= e(t('chiqish')) ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/login"   class="btn btn-ghost btn-sm"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-sm"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobil: hamburger -->
        <button @click="open = !open"
                style="display:flex;padding:.5rem;border-radius:9px;border:none;cursor:pointer;
                       background:transparent;color:var(--text);transition:background .15s;flex-shrink:0;"
                onmouseover="this.style.background='var(--surface)'"
                onmouseout="this.style.background='transparent'"
                class="md:hidden">
            <svg x-show="!open" style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="open" style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobil menyu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="border-top:1px solid var(--border);padding:.75rem 1rem 1rem;
                background:var(--nav-bg);"
         class="md:hidden" x-cloak>

        <?php if ($f): ?>
        <!-- Foydalanuvchi info -->
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem;
                    border-radius:10px;background:var(--surface);margin-bottom:.5rem;">
            <div style="width:40px;height:40px;border-radius:50%;flex-shrink:0;
                        background:linear-gradient(135deg,#2563EB,#7C3AED);
                        display:flex;align-items:center;justify-content:center;
                        font-weight:700;color:#fff;overflow:hidden;">
                <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </div>
            <div style="min-width:0;">
                <p style="font-weight:600;font-size:.9rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= e($f['ism'].' '.($f['familiya']??'')) ?>
                </p>
                <p style="font-size:.75rem;color:var(--muted);"><?= e($f['telefon']) ?></p>
            </div>
        </div>

        <?php
        $mob_items = [
            [SAYT_URL.'/dashboard', '📊', t('boshqaruv_paneli')],
            [SAYT_URL.'/test',      '📝', t('testlar')],
            [SAYT_URL.'/tolov',     '💎', t('tariflar')],
            [SAYT_URL.'/referal',   '🎁', t('referal')],
            [SAYT_URL.'/profil',    '👤', t('profil')],
        ];
        foreach ($mob_items as [$href,$ico,$nom]):
            $active = nav_active($href);
        ?>
        <a href="<?= e($href) ?>"
           style="display:flex;align-items:center;gap:.75rem;padding:.65rem .875rem;
                  border-radius:9px;text-decoration:none;font-size:.9rem;
                  margin-bottom:.2rem;transition:background .15s;
                  color: <?= $active ? 'var(--blue)' : 'var(--text2)' ?>;
                  background: <?= $active ? 'rgba(37,99,235,0.1)' : 'transparent' ?>;"
           onmouseover="this.style.background='var(--surface)'"
           onmouseout="this.style.background='<?= $active ? 'rgba(37,99,235,0.1)' : 'transparent' ?>'">
            <span style="width:24px;text-align:center;"><?= $ico ?></span>
            <?= e($nom) ?>
        </a>
        <?php endforeach; ?>

        <?php if (in_array($f['rol'], ['admin','developer'], true)): ?>
        <a href="<?= e(SAYT_URL) ?>/admin/"
           style="display:flex;align-items:center;gap:.75rem;padding:.65rem .875rem;
                  border-radius:9px;text-decoration:none;font-size:.9rem;
                  color:var(--violet);background:rgba(124,58,237,0.08);margin-bottom:.2rem;">
            <span style="width:24px;text-align:center;">⚙️</span>
            <?= e(t('admin_panel')) ?>
        </a>
        <?php endif; ?>

        <div style="border-top:1px solid var(--border);margin-top:.5rem;padding-top:.5rem;
                    display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php"
               style="display:flex;align-items:center;gap:.65rem;padding:.65rem .875rem;
                      border-radius:9px;text-decoration:none;font-size:.9rem;color:#FCA5A5;"
               onmouseover="this.style.background='rgba(239,68,68,0.08)'"
               onmouseout="this.style.background='transparent'">
                🚪 <?= e(t('chiqish')) ?>
            </a>
            <button onclick="toggleTheme()" class="theme-btn">
                <span id="theme-icon-mob">🌙</span>
            </button>
        </div>

        <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem;">
            <a href="<?= e(SAYT_URL) ?>/login"    class="btn btn-ghost" style="text-align:center;"><?= e(t('kirish')) ?></a>
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary" style="text-align:center;"><?= e(t('royxatdan_otish')) ?></a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<script>
// Navbar'dagi tema ikonlarini yangilash
(function() {
    function updateNavIcons() {
        var theme = document.documentElement.getAttribute('data-theme') || 'dark';
        var icon  = theme === 'dark' ? '🌙' : '☀️';
        ['theme-icon-nav','theme-icon-mob','theme-icon'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = icon;
        });
    }
    document.addEventListener('DOMContentLoaded', updateNavIcons);
    // toggleTheme chaqirilganda ham yangilash
    var origToggle = window.toggleTheme;
    window.toggleTheme = function() {
        if (origToggle) origToggle();
        updateNavIcons();
    };
})();
</script>
