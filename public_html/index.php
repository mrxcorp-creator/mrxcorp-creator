<?php
/**
 * VatanParvar Yaypan — Bosh sahifa (Hero, qisqa preview)
 *
 * Saytning landing sahifasi. Boshqa bo'limlar alohida sahifalarda:
 *   /tariflar, /blog, /aloqa
 *
 * 1 soatlik kesh bilan ishlaydi.
 */
require_once __DIR__ . '/config/auth.php';

// ============================================================
// KESH (faqat mehmonlar uchun)
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
$bilet_son = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

// Mashhur tariflar (faqat mashhur belgilanganlari)
$mashhur_tarif = db_qator('SELECT * FROM tariflar WHERE holat = "faol" AND mashhur = 1 ORDER BY tartib LIMIT 1');

// So'nggi blog postlari (3 ta)
$bloglar = (int) sozlama('blog_aktiv', 1)
    ? db_barcha('SELECT * FROM bloglar WHERE holat = "chop" ORDER BY yaratilgan DESC LIMIT 3')
    : [];

// Tasdiqlangan fikrlar (3 ta)
$fikrlar = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 3');

$banner_url = sozlama('banner_url');

$sahifa_sarlavha = t('sayt_nomi') . ' — ' . t('hero_sarlavha');
$sahifa_tavsif = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="relative max-w-7xl mx-auto px-4 pt-12 pb-16 lg:pt-20 lg:pb-24">

    <!-- Dekorativ floating shakllar -->
    <div class="absolute top-10 right-10 w-72 h-72 rounded-full bg-gradient-to-br from-sky-200 to-blue-300 opacity-30 blur-3xl floating" aria-hidden="true"></div>
    <div class="absolute top-32 -left-20 w-96 h-96 rounded-full bg-gradient-to-br from-violet-200 to-sky-200 opacity-25 blur-3xl floating" style="animation-delay:2s" aria-hidden="true"></div>

    <div class="relative grid lg:grid-cols-2 gap-12 items-center">

        <!-- Chap: matn -->
        <div class="text-center lg:text-left">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-sky-200 shadow-soft mb-6 fade-up">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-500"></span>
                </span>
                <span class="text-sm font-semibold text-brand-text">🚗 #1 Avto maktab platformasi</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-display font-bold tracking-tight mb-6 leading-[1.1] fade-up stagger-1">
                <span class="text-brand-text">Imtihonni</span>
                <br>
                <span class="text-gradient">birinchi urinishdan</span>
                <br>
                <span class="text-brand-text">topshiring!</span>
            </h1>

            <p class="text-lg text-brand-muted max-w-xl mx-auto lg:mx-0 mb-8 leading-relaxed fade-up stagger-2">
                <?= e(sozlama('about_matn', t('hero_tavsif'))) ?>
            </p>

            <!-- CTA tugmalar -->
            <div class="flex flex-wrap gap-3 justify-center lg:justify-start fade-up stagger-3">
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-4 px-8 group">
                    <span>🚀 Bepul boshlash</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="<?= e(SAYT_URL) ?>/tariflar" class="btn-ghost text-base py-4 px-8">
                    Tariflarni ko'rish
                </a>
            </div>

            <!-- Trust indicators -->
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 mt-8 text-brand-muted text-sm fade-up stagger-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    <span>Bepul demo</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    <span>Karta talab qilinmaydi</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    <span>24/7 qo'llab-quvvatlash</span>
                </div>
            </div>
        </div>

        <!-- O'ng: Banner rasm yoki dekorativ illustratsiya -->
        <div class="relative fade-up stagger-3">
            <?php if ($banner_url && is_file(UPLOAD_PATH . '/' . $banner_url)): ?>
                <div class="relative rounded-3xl overflow-hidden shadow-glow-lg">
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($banner_url) ?>"
                         alt="VatanParvar Yaypan banner"
                         class="w-full h-auto rounded-3xl">
                </div>
            <?php else: ?>
                <!-- Dekorativ illustratsiya (banner yo'q bo'lsa) -->
                <div class="relative">
                    <!-- Asosiy karta -->
                    <div class="relative rounded-3xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 p-8 shadow-glow-lg overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-white/20 blur-3xl"></div>
                        <div class="relative">
                            <div class="text-7xl mb-4">🚗</div>
                            <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-5 mb-4 border border-white/20">
                                <div class="text-white/80 text-xs uppercase tracking-wider mb-1">Demo savol</div>
                                <p class="text-white text-base font-medium">Yo'l harakati qoidalariga binoan, qaysi belgi xavf belgilarini bildiradi?</p>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-emerald-400/30 backdrop-blur-sm rounded-xl px-4 py-3 border border-emerald-300/50 flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-md bg-emerald-400 text-white flex items-center justify-center text-xs font-bold">A</span>
                                    <span class="text-white text-sm flex-1">Uchburchak shaklli qizil hoshiyali belgilar</span>
                                    <span class="text-white">✓</span>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/20 flex items-center gap-3 opacity-70">
                                    <span class="w-7 h-7 rounded-md bg-white/20 text-white flex items-center justify-center text-xs font-bold">B</span>
                                    <span class="text-white text-sm flex-1">Doira shaklidagi ko'k belgilar</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating mini kartalar -->
                    <div class="absolute -top-4 -right-4 glass-card px-4 py-3 floating shadow-medium">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">📊</div>
                            <div>
                                <div class="text-xs text-brand-muted">Natija</div>
                                <div class="text-sm font-bold text-brand-text">95%</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-6 -left-6 glass-card px-4 py-3 floating shadow-medium" style="animation-delay:1.5s">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">⏱️</div>
                            <div>
                                <div class="text-xs text-brand-muted">Vaqt</div>
                                <div class="text-sm font-bold text-brand-text">25 daq</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     STATISTIKA
     ============================================================ -->
<section class="max-w-6xl mx-auto px-4 -mt-4 mb-12">
    <div class="glass-card p-2 shadow-medium">
        <div class="grid grid-cols-3 gap-2">
            <div class="text-center p-4 rounded-xl hover:bg-sky-50 transition" data-animate>
                <div class="text-2xl md:text-4xl font-display font-bold text-gradient"><?= $bilet_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">Bilet</div>
            </div>
            <div class="text-center p-4 rounded-xl hover:bg-sky-50 transition border-x border-brand-border" data-animate style="animation-delay:.1s">
                <div class="text-2xl md:text-4xl font-display font-bold text-gradient"><?= $savol_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">Real savol</div>
            </div>
            <div class="text-center p-4 rounded-xl hover:bg-sky-50 transition" data-animate style="animation-delay:.2s">
                <div class="text-2xl md:text-4xl font-display font-bold text-gradient"><?= $foydalanuvchi_son ?>+</div>
                <div class="text-xs sm:text-sm text-brand-muted mt-1 font-medium">O'quvchi</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     XUSUSIYATLAR
     ============================================================ -->
<section class="max-w-7xl mx-auto px-4 py-16">

    <div class="text-center mb-12" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Xususiyatlar</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">
            Nima uchun <span class="text-gradient">VatanParvar?</span>
        </h2>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">Eng yaxshi tajriba va eng yangi ma'lumotlar</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php
        $xususiyatlar = [
            ['📚', '500+ real savol', "YHXBB imtihonidan eng so'nggi yangilangan baza", 'from-sky-100 to-blue-100', 'text-sky-600'],
            ['💾', 'Auto-save', "Internet uzilsa ham javoblar yo'qolmaydi", 'from-emerald-100 to-teal-100', 'text-emerald-600'],
            ['🤖', 'Telegram bot', "Bildirishnomalar va to'lovlar real vaqtda", 'from-violet-100 to-purple-100', 'text-violet-600'],
            ['📊', 'Statistika', 'Kuchli va zaif tomonlaringizni grafiklarda', 'from-amber-100 to-orange-100', 'text-amber-600'],
        ];
        foreach ($xususiyatlar as $i => $x):
        ?>
            <div class="glass-card glass-card-hover p-7 group" data-animate style="animation-delay: <?= 0.08 * $i ?>s">
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
     QANDAY ISHLAYDI (3 qadam)
     ============================================================ -->
<section class="max-w-6xl mx-auto px-4 py-16">
    <div class="text-center mb-14" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Jarayon</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">Qanday ishlaydi?</h2>
        <p class="text-brand-muted text-lg">Faqat 3 ta sodda qadam</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 relative">
        <!-- Qadam ulagichi (faqat desktop) -->
        <div class="hidden md:block absolute top-12 left-[15%] right-[15%] h-0.5 bg-gradient-to-r from-sky-200 via-sky-400 to-sky-200" aria-hidden="true"></div>

        <?php
        $qadamlar = [
            ['1', "Ro'yxatdan o'ting", "Telefon raqamingiz orqali bepul akkaunt yarating"],
            ['2', "Tarif tanlang", "O'zingizga mos tarifni tanlab, to'lov qiling"],
            ['3', "Mashq qiling", "Test ishlang, statistikani ko'ring va tayyorlaning"],
        ];
        foreach ($qadamlar as $i => $q):
        ?>
            <div class="text-center relative" data-animate style="animation-delay: <?= 0.1 * $i ?>s">
                <div class="relative inline-block mb-5">
                    <div class="w-24 h-24 rounded-full bg-white border-4 border-sky-100 flex items-center justify-center text-4xl font-display font-bold text-gradient shadow-soft hover:shadow-glow transition-all duration-300 hover:scale-110">
                        <?= $q[0] ?>
                    </div>
                </div>
                <h3 class="font-display text-xl font-bold text-brand-text mb-2"><?= e($q[1]) ?></h3>
                <p class="text-brand-muted text-sm max-w-xs mx-auto"><?= e($q[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     TARIFLAR PREVIEW
     ============================================================ -->
<?php if ($mashhur_tarif): ?>
<section class="max-w-6xl mx-auto px-4 py-16">
    <div class="glass-card p-8 md:p-12 shadow-medium relative overflow-hidden" data-animate>
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-gradient-to-br from-sky-200 to-blue-300 opacity-30 blur-3xl"></div>

        <div class="relative grid md:grid-cols-2 gap-8 items-center">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase tracking-wider mb-3">⭐ Mashhur tarif</span>
                <h2 class="text-3xl md:text-4xl font-display font-bold text-brand-text mb-3"><?= e($mashhur_tarif['nomi']) ?></h2>
                <p class="text-brand-muted text-lg mb-6"><?= e($mashhur_tarif['tavsif']) ?></p>

                <div class="flex items-baseline gap-3 mb-6">
                    <span class="text-5xl font-display font-bold text-gradient"><?= e(number_format($mashhur_tarif['narx'], 0, '.', ' ')) ?></span>
                    <span class="text-brand-muted">so'm</span>
                    <?php if ($mashhur_tarif['eski_narx'] && $mashhur_tarif['eski_narx'] > $mashhur_tarif['narx']): ?>
                        <span class="line-through text-brand-light text-lg"><?= e(pul($mashhur_tarif['eski_narx'])) ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">
                            -<?= round((1 - $mashhur_tarif['narx'] / $mashhur_tarif['eski_narx']) * 100) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary">
                        Hoziroq boshlash
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/tariflar" class="btn-ghost">Barcha tariflar</a>
                </div>
            </div>

            <div class="space-y-3">
                <?php
                $afzalliklar = ['Barcha biletlarga kirish', '500+ real savol', 'Telegram bildirishnomalar', "Statistika va tahlil", "Auto-save tizimi"];
                foreach ($afzalliklar as $i => $a):
                ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/60 border border-brand-border" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                        </div>
                        <span class="text-brand-text font-medium"><?= e($a) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     BLOG PREVIEW (so'nggi 3 post)
     ============================================================ -->
<?php if (!empty($bloglar)): ?>
<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="flex items-end justify-between mb-10 flex-wrap gap-3" data-animate>
        <div>
            <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Maqolalar</span>
            <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text">Blog va maslahatlar</h2>
        </div>
        <a href="<?= e(SAYT_URL) ?>/blog" class="text-sky-600 hover:text-sky-700 font-semibold text-sm flex items-center gap-1 link-anim">
            Barchasi
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
        <?php foreach ($bloglar as $i => $b): ?>
            <a href="<?= e(SAYT_URL) ?>/blog/<?= e($b['slug']) ?>" class="glass-card glass-card-hover overflow-hidden group" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                <?php if ($b['rasm'] && is_file(UPLOAD_PATH . '/' . $b['rasm'])): ?>
                    <div class="aspect-video overflow-hidden bg-sky-50">
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($b['rasm']) ?>" alt="<?= e($b['sarlavha']) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                <?php else: ?>
                    <div class="aspect-video bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-6xl text-white/80">
                        📰
                    </div>
                <?php endif; ?>
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-2 text-xs">
                        <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 font-semibold"><?= e($b['kategoriya']) ?></span>
                        <span class="text-brand-light">·</span>
                        <span class="text-brand-muted"><?= e(vaqt_oldin($b['yaratilgan'])) ?></span>
                    </div>
                    <h3 class="font-display font-bold text-lg text-brand-text mb-2 line-clamp-2 group-hover:text-sky-600 transition"><?= e($b['sarlavha']) ?></h3>
                    <p class="text-sm text-brand-muted line-clamp-2"><?= e($b['qisqa']) ?></p>
                    <div class="mt-4 inline-flex items-center gap-1 text-sky-600 text-sm font-semibold">
                        O'qish
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     FOYDALANUVCHILAR FIKRI (qisqa)
     ============================================================ -->
<?php if (!empty($fikrlar)): ?>
<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Fikrlar</span>
        <h2 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">O'quvchilarimiz aytgan</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
        <?php foreach ($fikrlar as $i => $fikr):
            $ranglar = ['from-sky-400 to-blue-500', 'from-emerald-400 to-teal-500', 'from-violet-400 to-purple-500'];
            $rang = $ranglar[$i % count($ranglar)];
        ?>
            <div class="glass-card glass-card-hover p-6 group" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                <svg class="w-8 h-8 text-sky-200 mb-3" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                <p class="text-brand-body text-sm leading-relaxed mb-4 line-clamp-4">
                    <?= e($fikr['matn']) ?>
                </p>
                <div class="flex items-center gap-3 pt-4 border-t border-brand-border">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $rang ?> flex items-center justify-center font-bold text-white shadow-soft">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-brand-text text-sm"><?= e($fikr['ism']) ?></div>
                        <div class="text-amber-400 text-xs"><?= str_repeat('★', (int)$fikr['baho']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CTA Banner
     ============================================================ -->
<section class="max-w-6xl mx-auto px-4 py-16">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 via-sky-600 to-blue-700 p-10 sm:p-16 text-center shadow-glow-lg" data-animate>
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 rounded-full bg-blue-400/20 blur-3xl"></div>
        </div>

        <div class="relative">
            <h2 class="text-3xl md:text-5xl font-display font-bold text-white mb-4">
                Bugun boshlang!
            </h2>
            <p class="text-sky-100 text-lg mb-8 max-w-xl mx-auto">
                Demo testni bepul yeching va platformaning qulayligini his qiling.
            </p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="<?= e(SAYT_URL) ?>/register" class="bg-white text-sky-700 hover:bg-sky-50 font-bold py-4 px-8 rounded-2xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl inline-flex items-center gap-2">
                    Bepul ro'yxatdan o'tish
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="<?= e(SAYT_URL) ?>/aloqa" class="bg-white/10 text-white border-2 border-white/30 hover:bg-white/20 font-bold py-4 px-8 rounded-2xl transition backdrop-blur-sm">
                    Bog'lanish
                </a>
            </div>
        </div>
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
