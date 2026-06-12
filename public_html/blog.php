<?php
/**
 * VatanParvar Yaypan — Blog ro'yxat sahifasi
 *
 * Barcha chop etilgan blog postlari + qidiruv + kategoriya filtri.
 */
require_once __DIR__ . '/config/auth.php';

if ((int) sozlama('blog_aktiv', 1) === 0) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Filterlar
$qidiruv = trim(olish('q'));
$kategoriya = trim(olish('kat'));
$sahifa_no = max(1, (int) olish('p'));
$limit = 9;
$offset = ($sahifa_no - 1) * $limit;

$shartlar = ['holat = "chop"'];
$params = [];
if ($qidiruv) {
    $shartlar[] = '(sarlavha LIKE ? OR qisqa LIKE ?)';
    $params[] = '%' . $qidiruv . '%';
    $params[] = '%' . $qidiruv . '%';
}
if ($kategoriya) {
    $shartlar[] = 'kategoriya = ?';
    $params[] = $kategoriya;
}
$where = ' WHERE ' . implode(' AND ', $shartlar);

$jami = (int) db_qiymat("SELECT COUNT(*) FROM bloglar $where", $params);
$jami_sahifa = (int) ceil($jami / $limit);

$bloglar = db_barcha(
    "SELECT * FROM bloglar $where ORDER BY yaratilgan DESC LIMIT $limit OFFSET $offset",
    $params
);

// Kategoriyalar ro'yxati
$kategoriyalar = db_barcha(
    'SELECT kategoriya, COUNT(*) AS son FROM bloglar
     WHERE holat = "chop"
     GROUP BY kategoriya ORDER BY son DESC LIMIT 10'
);

// Mashhur (eng ko'p ko'rilgan) postlar
$mashhur = db_barcha('SELECT * FROM bloglar WHERE holat = "chop" ORDER BY koruv DESC LIMIT 5');

$sahifa_sarlavha = "Blog — " . t('sayt_nomi');
$sahifa_tavsif = "Avto maktab nazariyasi va imtihon haqida foydali maqolalar.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="relative max-w-7xl mx-auto px-4 pt-12 pb-8">
    <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-gradient-to-br from-violet-200 to-sky-200 opacity-30 blur-3xl -z-10" aria-hidden="true"></div>

    <div class="text-center fade-up">
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Blog</span>
        <h1 class="text-4xl md:text-6xl font-display font-bold text-brand-text mb-4">
            Maslahatlar va <span class="text-gradient">maqolalar</span>
        </h1>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">
            Avto maktab nazariyasi, imtihonga tayyorgarlik va yo'l qoidalari haqida
        </p>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-4 gap-6">

        <!-- Asosiy: Bloglar -->
        <div class="lg:col-span-3">

            <!-- Qidiruv + filtrlar -->
            <form method="GET" class="glass-card p-4 mb-6 flex flex-wrap gap-3 items-center fade-up">
                <div class="flex-1 min-w-[200px] relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Qidirish..." class="field pl-11">
                </div>
                <?php if ($kategoriya || $qidiruv): ?>
                    <a href="<?= e(SAYT_URL) ?>/blog" class="btn-ghost text-sm">Tozalash</a>
                <?php endif; ?>
                <button class="btn-primary text-sm">Qidirish</button>
            </form>

            <?php if ($qidiruv || $kategoriya): ?>
                <p class="text-brand-muted text-sm mb-4">
                    Topildi: <strong class="text-brand-text"><?= $jami ?></strong> ta natija
                    <?php if ($kategoriya): ?>"<strong><?= e($kategoriya) ?></strong>" kategoriyasida<?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if (empty($bloglar)): ?>
                <div class="glass-card p-16 text-center text-brand-muted">
                    <div class="text-5xl mb-4">📝</div>
                    <p class="text-lg">Hozircha postlar yo'q</p>
                    <p class="text-sm mt-2">Tez orada qiziqarli maqolalar paydo bo'ladi!</p>
                </div>
            <?php else: ?>
                <div class="grid sm:grid-cols-2 gap-5">
                    <?php foreach ($bloglar as $i => $b): ?>
                        <article class="glass-card glass-card-hover overflow-hidden group" data-animate style="animation-delay: <?= 0.05 * $i ?>s">
                            <a href="<?= e(SAYT_URL) ?>/blog/<?= e($b['slug']) ?>" class="block">
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
                                    <div class="flex items-center gap-2 mb-3 text-xs">
                                        <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 font-semibold"><?= e($b['kategoriya']) ?></span>
                                        <span class="text-brand-light">·</span>
                                        <span class="text-brand-muted"><?= e(vaqt_oldin($b['yaratilgan'])) ?></span>
                                    </div>
                                    <h3 class="font-display font-bold text-lg text-brand-text mb-2 line-clamp-2 group-hover:text-sky-600 transition"><?= e($b['sarlavha']) ?></h3>
                                    <p class="text-sm text-brand-muted line-clamp-3 mb-3"><?= e($b['qisqa']) ?></p>
                                    <div class="flex items-center justify-between text-xs text-brand-muted pt-3 border-t border-brand-border">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                                            <?= (int)$b['koruv'] ?>
                                        </span>
                                        <span class="text-sky-600 font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                                            O'qish <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Sahifalash -->
                <?php if ($jami_sahifa > 1): ?>
                    <div class="flex justify-center gap-1.5 mt-10" data-animate>
                        <?php
                        $url_params = [];
                        if ($qidiruv) $url_params['q'] = $qidiruv;
                        if ($kategoriya) $url_params['kat'] = $kategoriya;

                        $build_url = function ($p) use ($url_params) {
                            $url_params['p'] = $p;
                            return '?' . http_build_query($url_params);
                        };

                        if ($sahifa_no > 1):
                        ?>
                            <a href="<?= e($build_url($sahifa_no - 1)) ?>" class="px-3 py-2 rounded-lg bg-white border border-brand-border hover:bg-sky-50 hover:border-sky-300 text-sm transition">←</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $jami_sahifa; $i++):
                            if ($jami_sahifa > 7 && abs($i - $sahifa_no) > 2 && $i !== 1 && $i !== $jami_sahifa) {
                                if ($i === 2 || $i === $jami_sahifa - 1) echo '<span class="px-2 py-2 text-brand-light">…</span>';
                                continue;
                            }
                        ?>
                            <a href="<?= e($build_url($i)) ?>"
                               class="px-3.5 py-2 rounded-lg text-sm font-semibold transition <?= $i === $sahifa_no ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-soft' : 'bg-white border border-brand-border hover:bg-sky-50 hover:border-sky-300' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($sahifa_no < $jami_sahifa): ?>
                            <a href="<?= e($build_url($sahifa_no + 1)) ?>" class="px-3 py-2 rounded-lg bg-white border border-brand-border hover:bg-sky-50 hover:border-sky-300 text-sm transition">→</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-5">
            <!-- Kategoriyalar -->
            <?php if (!empty($kategoriyalar)): ?>
                <div class="glass-card p-5 fade-up">
                    <h3 class="font-display font-bold text-brand-text mb-3 text-sm uppercase tracking-wider">Kategoriyalar</h3>
                    <div class="space-y-1">
                        <a href="<?= e(SAYT_URL) ?>/blog"
                           class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-sky-50 text-sm transition <?= !$kategoriya ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-brand-body' ?>">
                            <span>Barchasi</span>
                            <span class="text-xs text-brand-muted"><?= $jami ?></span>
                        </a>
                        <?php foreach ($kategoriyalar as $k): ?>
                            <a href="?kat=<?= urlencode($k['kategoriya']) ?>"
                               class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-sky-50 text-sm transition <?= $kategoriya === $k['kategoriya'] ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-brand-body' ?>">
                                <span><?= e($k['kategoriya']) ?></span>
                                <span class="text-xs text-brand-muted"><?= (int)$k['son'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mashhur postlar -->
            <?php if (!empty($mashhur)): ?>
                <div class="glass-card p-5 fade-up">
                    <h3 class="font-display font-bold text-brand-text mb-3 text-sm uppercase tracking-wider">Eng ko'p o'qilgan</h3>
                    <div class="space-y-3">
                        <?php foreach ($mashhur as $i => $m): ?>
                            <a href="<?= e(SAYT_URL) ?>/blog/<?= e($m['slug']) ?>"
                               class="flex items-start gap-3 group">
                                <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-sky-100 to-blue-100 text-sky-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <?= $i + 1 ?>
                                </span>
                                <div class="flex-1">
                                    <div class="text-sm font-semibold text-brand-text line-clamp-2 group-hover:text-sky-600 transition"><?= e($m['sarlavha']) ?></div>
                                    <div class="text-xs text-brand-muted mt-1"><?= (int)$m['koruv'] ?> ko'rilgan</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CTA -->
            <div class="glass-card p-5 bg-gradient-to-br from-sky-50 to-blue-50 fade-up">
                <div class="text-2xl mb-2">🚀</div>
                <h3 class="font-display font-bold text-brand-text mb-1">Bepul boshlang!</h3>
                <p class="text-sm text-brand-muted mb-3">Demo testlarni hoziroq sinab ko'ring</p>
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary w-full text-sm">
                    Ro'yxatdan o'tish
                </a>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
