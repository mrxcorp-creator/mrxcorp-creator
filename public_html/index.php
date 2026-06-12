<?php
/**
 * VatanParvar Yaypan — Bosh sahifa
 *
 * Yorqin (oq + havorang) tema, zamonaviy animatsiyalar bilan.
 * 1 soatlik kesh bilan ishlaydi.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

// ============================================================
// KESH
// ============================================================
$kesh_fayli = CACHE_PATH . '/indeks_' . ($_SESSION['til'] ?? 'uz_latn') . '.html';
$kesh_muddat = 3600;

if (!joriy_foydalanuvchi() && file_exists($kesh_fayli) && (time() - filemtime($kesh_fayli)) < $kesh_muddat) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}

ob_start();

// ============================================================
// MA'LUMOTLARNI OLISH
// ============================================================
$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar  = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 9');
$bilet_son = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

$sahifa_sarlavha = t('sayt_nomi') . ' — ' . t('hero_sarlavha');
$sahifa_tavsif = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================================
     HERO SECTION — Yorqin va zamonaviy
     ============================================================ -->
<section class="relative max-w-7xl mx-auto px-4 pt-12 pb-20 lg:pt-20 lg:pb-28">

    <!-- Dekorativ floating shakllar -->
    <div class="absolute top-10 right-10 w-72 h-72 rounded-full bg-gradient-to-br from-sky-200 to-blue-300 opacity-30 blur-3xl floating" aria-hidden="true"></div>
    <div class="absolute top-32 -left-20 w-96 h-96 rounded-full bg-gradient-to-br from-violet-200 to-sky-200 opacity-25 blur-3xl floating" style="animation-delay:2s" aria-hidden="true"></div>

    <div class="relative text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-sky-200 shadow-soft mb-8 fade-up">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-500"></span>
            </span>
            <span class="text-sm font-semibold text-brand-text">🚗 #1 Avto maktab nazariyasi platformasi</span>
        </div>

        <!-- Asosiy sarlavha -->
        <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-display font-bold tracking-tight mb-6 leading-[1.1] fade-up stagger-1">
            <span class="text-brand-text"><?= e(t('hero_sarlavha')) ?></span>
            <br>
            <span class="text-gradient">birinchi urinishdan!</span>
        </h1>

        <p class="text-lg md:text-xl text-brand-muted max-w-2xl mx-auto mb-10 leading-relaxed fade-up stagger-2">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <!-- CTA tugmalar -->
        <div class="flex flex-wrap gap-3 justify-center fade-up stagger-3">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-4 px-8 group">
                <span>🚀 <?= e(t('hero_tugma_boshla')) ?></span>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost text-base py-4 px-8">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= e(t('hero_tugma_demo')) ?>
            </a>
        </div>

        <!-- Statistika -->
        <div class="grid grid-cols-3 max-w-2xl mx-auto mt-16 gap-3 fade-up stagger-4">
            <div class="glass-card p-5 hover:-translate-y-1 transition-transform duration-300">
                <div class="text-3xl md:text-4xl font-display font-bold text-gradient"><?= $bilet_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">Bilet</div>
            </div>
            <div class="glass-card p-5 hover:-translate-y-1 transition-transform duration-300">
                <div class="text-3xl md:text-4xl font-display font-bold text-gradient"><?= $savol_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">Real savol</div>
            </div>
            <div class="glass-card p-5 hover:-translate-y-1 transition-transform duration-300">
                <div class="text-3xl md:text-4xl font-display font-bold text-gradient"><?= $foydalanuvchi_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">Foydalanuvchi</div>
            </div>
        </div>

        <!-- Trust indicators -->
        <div class="flex items-center justify-center gap-6 mt-10 text-brand-muted text-sm fade-up stagger-5">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                <span>Bepul boshlash</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                <span>Karta kerak emas</span>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     XUSUSIYATLAR
     ============================================================ -->
<section id="xususiyatlar" class="max-w-7xl mx-auto px-4 py-20 relative">

    <div class="text-center mb-14" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Xususiyatlar</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">
            Nima uchun bizni <span class="text-gradient">tanlashadi?</span>
        </h2>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">Eng yaxshi tajriba va eng yangilangan ma'lumotlar bilan ta'minlaymiz</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php
        $xususiyatlar = [
            ['📚', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif'), 'from-sky-100 to-blue-100', 'text-sky-600'],
            ['💾', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif'), 'from-emerald-100 to-teal-100', 'text-emerald-600'],
            ['🤖', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif'), 'from-violet-100 to-purple-100', 'text-violet-600'],
            ['📊', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif'), 'from-amber-100 to-orange-100', 'text-amber-600'],
        ];
        foreach ($xususiyatlar as $i => $x):
        ?>
            <div class="glass-card glass-card-hover p-7 group cursor-default" data-animate style="animation-delay: <?= 0.1 * $i ?>s">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br <?= $x[3] ?> flex items-center justify-center text-3xl mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                    <?= $x[0] ?>
                </div>
                <h3 class="font-display text-lg font-bold text-brand-text mb-2"><?= e($x[1]) ?></h3>
                <p class="text-sm text-brand-muted leading-relaxed"><?= e($x[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     TARIFLAR
     ============================================================ -->
<section id="tariflar" class="max-w-7xl mx-auto px-4 py-20 relative">

    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-gradient-to-br from-sky-100 to-blue-100 opacity-40 blur-3xl -z-10" aria-hidden="true"></div>

    <div class="text-center mb-14" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Narxlar</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-brand-muted text-lg"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-12 text-center text-brand-muted">
            <p><?= e(t('tariflar_yoq')) ?></p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($tariflar as $i => $tar): ?>
                <div class="glass-card glass-card-hover p-7 relative <?= $tar['mashhur'] ? 'ring-2 ring-sky-400 ring-offset-4 ring-offset-brand-bg shadow-glow' : '' ?>"
                     data-animate style="animation-delay: <?= 0.1 * $i ?>s">

                    <?php if ($tar['mashhur']): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-sky-500 to-blue-600 text-white whitespace-nowrap shadow-soft">
                            ⭐ <?= e(t('mashhur')) ?>
                        </span>
                    <?php endif; ?>

                    <h3 class="font-display text-xl font-bold text-brand-text mb-3"><?= e($tar['nomi']) ?></h3>

                    <div class="flex items-baseline gap-2 mb-3">
                        <span class="text-4xl font-display font-bold text-gradient"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                        <span class="text-brand-muted text-sm font-medium">so'm</span>
                    </div>

                    <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="line-through text-brand-light text-sm"><?= e(pul($tar['eski_narx'])) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">
                                -<?= round((1 - $tar['narx'] / $tar['eski_narx']) * 100) ?>%
                            </span>
                        </div>
                    <?php endif; ?>

                    <p class="text-sm text-brand-muted mb-6 min-h-[3rem] leading-relaxed"><?= e($tar['tavsif']) ?></p>

                    <a href="<?= e(SAYT_URL) ?>/register"
                       class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full text-sm">
                        <?= e(t('tarif_olish')) ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============================================================
     FOYDALANUVCHILAR FIKRI
     ============================================================ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-20">

    <div class="text-center mb-14" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Fikrlar</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-brand-muted text-lg">Ular haqimizda nima deydi</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($fikrlar as $i => $fikr):
            $ranglar = ['from-sky-400 to-blue-500', 'from-emerald-400 to-teal-500', 'from-violet-400 to-purple-500', 'from-amber-400 to-orange-500', 'from-rose-400 to-pink-500'];
            $rang = $ranglar[$i % count($ranglar)];
        ?>
            <div class="glass-card glass-card-hover p-6 group" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br <?= $rang ?> flex items-center justify-center font-bold text-white text-lg shadow-soft">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-brand-text"><?= e($fikr['ism']) ?></div>
                        <div class="text-amber-400 text-sm tracking-wider"><?= str_repeat('★', (int)$fikr['baho']) ?><span class="text-brand-light"><?= str_repeat('★', 5 - (int)$fikr['baho']) ?></span></div>
                    </div>
                    <svg class="w-8 h-8 text-sky-100 group-hover:text-sky-200 transition" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                </div>
                <p class="text-brand-body text-sm leading-relaxed">
                    "<?= e($fikr['matn']) ?>"
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CTA Banner
     ============================================================ -->
<section class="max-w-6xl mx-auto px-4 py-20">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 via-sky-600 to-blue-700 p-10 sm:p-16 text-center shadow-glow-lg" data-animate>

        <!-- Dekorativ orqa fon -->
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 rounded-full bg-blue-400/20 blur-3xl"></div>
        </div>

        <!-- SVG patterns -->
        <svg class="absolute top-5 right-5 w-32 h-32 text-white/10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L13.09 8.26L19.5 9L14 13.74L15.18 19.92L12 17.27L8.82 19.92L10 13.74L4.5 9L10.91 8.26L12 2Z"/></svg>

        <div class="relative">
            <h2 class="text-3xl md:text-5xl font-display font-bold text-white mb-4">
                Bugun boshlang!
            </h2>
            <p class="text-sky-100 text-lg mb-8 max-w-xl mx-auto">
                Demo testni bepul yeching va platformaning qulayligini his qiling.
            </p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="<?= e(SAYT_URL) ?>/register" class="bg-white text-sky-700 hover:bg-sky-50 font-bold py-4 px-8 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl inline-flex items-center gap-2">
                    <?= e(t('royxatdan_otish')) ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="<?= e(SAYT_URL) ?>/login" class="bg-white/10 text-white border-2 border-white/30 hover:bg-white/20 font-bold py-4 px-8 rounded-2xl transition backdrop-blur-sm">
                    <?= e(t('kirish')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ALOQA
     ============================================================ -->
<section id="aloqa" class="max-w-5xl mx-auto px-4 py-20">

    <div class="text-center mb-12" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Aloqa</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3"><?= e(t('aloqa')) ?></h2>
        <p class="text-brand-muted text-lg">Savol bormi? Bog'lanishingiz mumkin</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="glass-card glass-card-hover p-7 text-center group" data-animate>
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Telefon</div>
            <div class="font-display font-bold text-brand-text"><?= e(sozlama('aloqa_telefon')) ?></div>
        </a>

        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="glass-card glass-card-hover p-7 text-center group" data-animate style="animation-delay:.05s">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-violet-100 to-purple-100 text-violet-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Email</div>
            <div class="font-display font-bold text-brand-text"><?= e(sozlama('aloqa_email')) ?></div>
        </a>

        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="glass-card glass-card-hover p-7 text-center group" data-animate style="animation-delay:.1s">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-sky-100 to-blue-100 text-sky-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Telegram</div>
            <div class="font-display font-bold text-brand-text">Kanalga obuna</div>
        </a>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

// ============================================================
// KESHGA YOZISH (faqat mehmon foydalanuvchilar uchun)
// ============================================================
if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) {
        mkdir(CACHE_PATH, 0755, true);
    }
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
