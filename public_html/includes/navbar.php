<?php
/**
 * VatanParvar Yaypan — Navigatsiya paneli
 * ------------------------------------------------------------
 * Yorqin (oq + havorang) tema bilan zamonaviy navbar.
 */
$f = $f ?? joriy_foydalanuvchi();
$logo_url = sozlama('logo_url');
$sayt_nomi = sozlama('sayt_nomi', 'VatanParvar');
?>

<nav x-data="{open:false, scrolled:false}"
     @scroll.window="scrolled = window.scrollY > 10"
     :class="scrolled ? 'shadow-soft border-brand-border' : 'border-transparent'"
     class="sticky top-0 z-40 backdrop-blur-xl bg-white/80 border-b transition-all duration-300">

    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

        <!-- Logo -->
        <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 group">
            <?php if ($logo_url && is_file(UPLOAD_PATH . '/' . $logo_url)): ?>
                <span class="relative w-10 h-10 rounded-xl overflow-hidden shadow-soft group-hover:shadow-glow transition-all duration-300 group-hover:scale-110 bg-white">
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($logo_url) ?>" alt="<?= e($sayt_nomi) ?>" class="w-full h-full object-contain">
                </span>
            <?php else: ?>
                <span class="relative w-10 h-10 rounded-xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white shadow-soft group-hover:shadow-glow transition-all duration-300 group-hover:scale-110">
                    <?= e(mb_substr($sayt_nomi, 0, 1)) ?>
                    <span class="absolute inset-0 rounded-xl bg-gradient-to-br from-sky-300 to-blue-500 opacity-0 group-hover:opacity-100 blur-md -z-10 transition-opacity duration-300"></span>
                </span>
            <?php endif; ?>
            <span class="font-display font-bold text-brand-text text-lg hidden sm:inline"><?= e($sayt_nomi) ?></span>
        </a>

        <!-- Markaziy menyu (desktop) -->
        <div class="hidden md:flex items-center gap-1">
            <?php if ($f): ?>
                <a href="<?= e(SAYT_URL) ?>/dashboard" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('boshqaruv_paneli')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('testlar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/imtihon" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm">🎓 Imtihon</a>
                <a href="<?= e(SAYT_URL) ?>/reyting" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm">🏆 Reyting</a>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('tariflar')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/referal" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('referal')) ?></a>
                <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                    <a href="<?= e(SAYT_URL) ?>/admin/" class="px-4 py-2 rounded-lg bg-gradient-to-r from-sky-50 to-blue-50 text-sky-700 hover:from-sky-100 hover:to-blue-100 transition-all duration-200 font-medium text-sm border border-sky-100">
                        <?= e(t('admin_panel')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm">Bosh</a>
                <a href="<?= e(SAYT_URL) ?>/tariflar" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('tariflar')) ?></a>
                <?php if ((int) sozlama('blog_aktiv', 1)): ?>
                    <a href="<?= e(SAYT_URL) ?>/blog" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm">Blog</a>
                <?php endif; ?>
                <a href="<?= e(SAYT_URL) ?>/aloqa" class="px-4 py-2 rounded-lg text-brand-body hover:text-sky-600 hover:bg-sky-50 transition-all duration-200 font-medium text-sm"><?= e(t('aloqa')) ?></a>
            <?php endif; ?>
        </div>

        <!-- O'ng tomon -->
        <div class="hidden md:flex items-center gap-2">
            <!-- Dark mode toggle (har doim ko'rinadi) -->
            <button @click="
                        const dark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('tema', dark ? 'dark' : 'light');
                    "
                    class="p-2.5 rounded-xl hover:bg-sky-50 transition group" aria-label="Tema almashtirish" title="Tema almashtirish">
                <svg class="w-5 h-5 text-brand-body group-hover:text-sky-600 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="w-5 h-5 text-amber-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            <?php if ($f): ?>
                <!-- Chat tugmasi -->
                <a href="<?= e(SAYT_URL) ?>/chat"
                   x-data="{son: 0, init() { this.yangila(); setInterval(() => this.yangila(), 20000); },
                            async yangila() { try { const r = await fetch('<?= e(SAYT_URL) ?>/api/bildirishnoma.php?action=ruyhat', {credentials:'same-origin'}).then(r=>r.json()); this.son = r.chat_oqilmagan || 0; } catch(e) {} } }"
                   class="relative p-2.5 rounded-xl hover:bg-sky-50 transition group" aria-label="Chat">
                    <svg class="w-5 h-5 text-brand-body group-hover:text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span x-show="son > 0" x-cloak
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center animate-pulse-soft"
                          x-text="son > 9 ? '9+' : son"></span>
                </a>

                <!-- Bildirishnoma qo'ng'irog'i -->
                <div x-data="bildirishnomaPanel()" class="relative">
                    <button @click="menu=!menu; if (menu) yangila()"
                            class="relative p-2.5 rounded-xl hover:bg-sky-50 transition group" aria-label="Bildirishnomalar">
                        <svg class="w-5 h-5 text-brand-body group-hover:text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span x-show="son > 0" x-cloak
                              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-sky-500 text-white text-[10px] font-bold flex items-center justify-center animate-pulse-soft"
                              x-text="son > 9 ? '9+' : son"></span>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="menu" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         @click.outside="menu=false"
                         class="absolute right-0 top-full mt-2 w-80 max-h-[80vh] glass-card shadow-medium origin-top-right overflow-hidden flex flex-col">
                        <div class="p-3 border-b border-brand-border flex items-center justify-between flex-shrink-0">
                            <h3 class="font-display font-bold text-brand-text">Bildirishnomalar</h3>
                            <button @click="hammasiOqildi()" x-show="son > 0" x-cloak
                                    class="text-xs text-sky-600 hover:text-sky-700 font-semibold">
                                Hammasini o'qildi
                            </button>
                        </div>
                        <div class="overflow-y-auto flex-1">
                            <template x-if="loading">
                                <div class="p-8 text-center">
                                    <svg class="w-6 h-6 animate-spin mx-auto text-sky-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                    </svg>
                                </div>
                            </template>
                            <template x-if="!loading && ruyhat.length === 0">
                                <div class="p-8 text-center text-brand-muted text-sm">
                                    <div class="text-4xl mb-2">🔕</div>
                                    Bildirishnomalar yo'q
                                </div>
                            </template>
                            <template x-for="b in ruyhat" :key="b.id">
                                <a :href="b.link || '#'"
                                   @click="b.link ? null : $event.preventDefault(); oqildi(b.id)"
                                   class="block p-3 hover:bg-sky-50/50 border-b border-brand-border/50 transition"
                                   :class="!b.oqilgan ? 'bg-sky-50/30' : ''">
                                    <div class="flex gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg flex-shrink-0"
                                             :class="{
                                                 'bg-sky-100': b.tur === 'info',
                                                 'bg-emerald-100': b.tur === 'muvaffaqiyat',
                                                 'bg-amber-100': b.tur === 'ogohlantirish',
                                                 'bg-rose-100': b.tur === 'xato'
                                             }"
                                             x-text="b.ikon"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-sm text-brand-text" x-text="b.sarlavha"></div>
                                            <div class="text-xs text-brand-muted line-clamp-2 mt-0.5" x-text="b.matn"></div>
                                            <div class="text-[10px] text-brand-light mt-1" x-text="b.vaqt"></div>
                                        </div>
                                        <span x-show="!b.oqilgan" class="w-2 h-2 rounded-full bg-sky-500 mt-2 flex-shrink-0"></span>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-data="{menu:false}" class="relative">
                    <button @click="menu=!menu"
                            class="flex items-center gap-2.5 hover:bg-sky-50 rounded-xl pl-1.5 pr-3 py-1.5 transition-all duration-200 group">
                        <span class="w-9 h-9 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-sm font-bold text-white shadow-soft ring-2 ring-white">
                            <?= e(bosh_harflar($f)) ?>
                        </span>
                        <span class="text-sm font-medium text-brand-text"><?= e($f['ism']) ?></span>
                        <svg class="w-4 h-4 text-brand-muted transition-transform duration-200" :class="menu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="menu"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                         @click.outside="menu=false"
                         class="absolute right-0 top-full mt-2 w-60 glass-card p-2 shadow-medium origin-top-right"
                         x-cloak>
                        <div class="px-3 py-2 mb-1 border-b border-brand-border">
                            <div class="text-sm font-semibold text-brand-text"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></div>
                            <div class="text-xs text-brand-muted"><?= e($f['telefon']) ?></div>
                        </div>
                        <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 hover:text-sky-700 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?= e(t('profil')) ?>
                        </a>
                        <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 hover:text-sky-700 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <?= e(t('referal')) ?>
                        </a>
                        <hr class="my-1 border-brand-border">
                        <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-rose-50 text-rose-600 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <?= e(t('chiqish')) ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SAYT_URL) ?>/login" class="text-sm font-semibold text-brand-body hover:text-sky-600 px-4 py-2 transition"><?= e(t('kirish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-sm py-2.5 px-5"><?= e(t('royxatdan_otish')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobil menyu tugmasi -->
        <button @click="open=!open"
                class="md:hidden p-2 rounded-lg hover:bg-sky-50 transition"
                aria-label="Menyu">
            <svg x-show="!open" class="w-6 h-6 text-brand-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="open" class="w-6 h-6 text-brand-text" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobil ochiluvchi menyu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-brand-border bg-white/95 backdrop-blur-xl px-4 py-3 space-y-1"
         x-cloak>
        <?php if ($f): ?>
            <div class="flex items-center gap-3 px-3 py-3 rounded-xl bg-gradient-to-r from-sky-50 to-blue-50 mb-3 border border-sky-100">
                <span class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white shadow-soft text-lg">
                    <?= e(bosh_harflar($f)) ?>
                </span>
                <div>
                    <div class="font-semibold text-brand-text"><?= e($f['ism']) ?></div>
                    <div class="text-xs text-brand-muted"><?= e($f['telefon']) ?></div>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">📊</span>
                <?= e(t('boshqaruv_paneli')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">📝</span>
                <?= e(t('testlar')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/imtihon" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-base">🎓</span>
                Imtihon
            </a>
            <a href="<?= e(SAYT_URL) ?>/reyting" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-base">🏆</span>
                Reyting
            </a>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">💎</span>
                <?= e(t('tariflar')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">🎁</span>
                <?= e(t('referal')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">👤</span>
                <?= e(t('profil')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/chat" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">💬</span>
                Yordam (chat)
            </a>
            <a href="<?= e(SAYT_URL) ?>/bildirishnomalar" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-sky-50 transition text-sm font-medium">
                <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">🔔</span>
                Bildirishnomalar
            </a>
            <?php if (in_array($f['rol'], ['admin', 'developer'], true)): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gradient-to-r from-sky-50 to-blue-50 text-sky-700 transition text-sm font-medium border border-sky-100 mt-2">
                    <span class="w-8 h-8 rounded-lg bg-white text-sky-600 flex items-center justify-center text-base">⚙️</span>
                    <?= e(t('admin_panel')) ?>
                </a>
            <?php endif; ?>
            <a href="<?= e(SAYT_URL) ?>/auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-rose-50 text-rose-600 transition text-sm font-medium mt-2">
                <span class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-base">🚪</span>
                <?= e(t('chiqish')) ?>
            </a>
        <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/" class="block px-4 py-3 rounded-lg hover:bg-sky-50 text-brand-text transition font-medium">
                Bosh sahifa
            </a>
            <a href="<?= e(SAYT_URL) ?>/tariflar" class="block px-4 py-3 rounded-lg hover:bg-sky-50 text-brand-text transition font-medium">
                <?= e(t('tariflar')) ?>
            </a>
            <?php if ((int) sozlama('blog_aktiv', 1)): ?>
                <a href="<?= e(SAYT_URL) ?>/blog" class="block px-4 py-3 rounded-lg hover:bg-sky-50 text-brand-text transition font-medium">
                    Blog
                </a>
            <?php endif; ?>
            <a href="<?= e(SAYT_URL) ?>/aloqa" class="block px-4 py-3 rounded-lg hover:bg-sky-50 text-brand-text transition font-medium">
                <?= e(t('aloqa')) ?>
            </a>
            <hr class="my-2 border-brand-border">
            <a href="<?= e(SAYT_URL) ?>/login" class="block px-4 py-3 rounded-lg hover:bg-sky-50 text-brand-text transition font-medium text-center">
                <?= e(t('kirish')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary w-full block text-center">
                <?= e(t('royxatdan_otish')) ?>
            </a>
        <?php endif; ?>
    </div>
</nav>
