<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi boshqaruv paneli (dashboard)
 * Yorqin tema bilan zamonaviy dizayn.
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

// ----- Statistika -----
$stat = db_qator(
    'SELECT
        COUNT(*) AS jami,
        COALESCE(SUM(togri_son), 0) AS togri,
        COALESCE(SUM(xato_son), 0)  AS xato,
        COALESCE(SUM(umumiy_son), 0) AS umumiy
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
    [$f['id']]
);
$jami_test = (int) ($stat['jami'] ?? 0);
$togri_javoblar = (int) ($stat['togri'] ?? 0);
$umumiy = max(1, (int) ($stat['umumiy'] ?? 1));
$oz_natija = round(($togri_javoblar / $umumiy) * 100);

// ----- Faol obuna -----
$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi
     FROM obunalar o JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

// ----- Davom etayotgan test -----
$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

// ----- Oxirgi natijalar (5 ta) -----
$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

// ----- 7 kunlik grafik -----
$grafik = db_barcha(
    'SELECT DATE(tugagan) AS sana, COUNT(*) AS son, AVG(togri_son/umumiy_son*100) AS oz
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(tugagan)
     ORDER BY sana',
    [$f['id']]
);

// ----- So'nggi bildirishnomalar (3 ta) -----
$bildirishnomalar = db_barcha(
    'SELECT * FROM bildirishnomalar
     WHERE foydalanuvchi_id = ?
     ORDER BY yaratilgan DESC LIMIT 3',
    [$f['id']]
);

// ----- Streak (ketma-ket kunlar) — gamification -->
$kunlar = db_barcha(
    'SELECT DISTINCT DATE(tugagan) AS sana FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"
     ORDER BY sana DESC LIMIT 30',
    [$f['id']]
);
$streak = 0;
$bugun = strtotime('today');
foreach ($kunlar as $k) {
    $kun_ts = strtotime($k['sana']);
    $farq = (int) (($bugun - $kun_ts) / 86400);
    if ($farq === $streak) {
        $streak++;
    } else {
        break;
    }
}

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8">
    <!-- Salomlashish + Streak -->
    <div class="mb-8 fade-up flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-display font-bold text-brand-text mb-1">
                <?= e(t('salom')) ?>, <span class="text-gradient"><?= e($f['ism']) ?></span> 👋
            </h1>
            <p class="text-brand-muted">Bugun nimani o'rganamiz?</p>
        </div>

        <?php if ($streak > 0): ?>
            <div class="flex items-center gap-3 bg-gradient-to-r from-amber-100 to-orange-100 rounded-2xl px-4 py-2.5 border border-amber-200 shadow-soft">
                <span class="text-3xl">🔥</span>
                <div>
                    <div class="text-2xl font-display font-bold text-amber-700"><?= $streak ?> kun</div>
                    <div class="text-[11px] text-amber-600 font-semibold uppercase tracking-wider">Ketma-ket</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Statistika kartalar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-card glass-card-hover p-5 fade-up stagger-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-sky-100 to-blue-100 text-sky-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <div class="text-3xl font-display font-bold text-brand-text"><?= $jami_test ?></div>
            <div class="text-xs text-brand-muted mt-1 font-medium uppercase tracking-wider"><?= e(t('umumiy_test')) ?></div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up stagger-2">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-display font-bold text-brand-text"><?= $togri_javoblar ?></div>
            <div class="text-xs text-brand-muted mt-1 font-medium uppercase tracking-wider"><?= e(t('togri_javoblar')) ?></div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up stagger-3">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 text-violet-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-display font-bold text-brand-text"><?= $oz_natija ?>%</div>
            <div class="text-xs text-brand-muted mt-1 font-medium uppercase tracking-wider"><?= e(t('oz_natija')) ?></div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up stagger-4 <?= $obuna ? 'ring-2 ring-amber-200' : '' ?>">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl <?= $obuna ? 'bg-gradient-to-br from-amber-100 to-orange-100 text-amber-600' : 'bg-brand-bg text-brand-light' ?> flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L3 6v6c0 4 3 7 7 8 4-1 7-4 7-8V6l-7-4z"/></svg>
                </div>
            </div>
            <?php if ($obuna): ?>
                <div class="text-base font-display font-bold text-brand-text truncate"><?= e($obuna['tarif_nomi']) ?></div>
                <div class="text-xs text-brand-muted mt-1 font-medium">
                    <?= e(sana($obuna['tugash'], 'd.m.Y')) ?> gacha
                </div>
            <?php else: ?>
                <div class="text-base font-display font-bold text-brand-light"><?= e(t('obuna_yoq')) ?></div>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="text-xs text-sky-600 hover:underline font-medium mt-1 inline-block"><?= e(t('tarif_olish')) ?> →</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Davom etayotgan test -->
    <?php if ($davom): ?>
        <div class="mb-8 fade-up">
            <div class="rounded-3xl bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400 p-1 shadow-soft">
                <div class="rounded-[22px] bg-white p-6 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center text-3xl flex-shrink-0">
                            ⏳
                        </div>
                        <div>
                            <div class="text-amber-600 text-sm font-bold mb-1 uppercase tracking-wider">Davom etayotgan test</div>
                            <h3 class="text-xl font-display font-bold text-brand-text"><?= e($davom['nomi']) ?></h3>
                            <p class="text-sm text-brand-muted mt-1">Boshlangan: <?= e(vaqt_oldin($davom['boshlangan'])) ?></p>
                        </div>
                    </div>
                    <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>" class="btn-primary">
                        <?= e(t('davom_etish')) ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Chap: oxirgi natijalar -->
        <div class="lg:col-span-2 glass-card p-6 fade-up">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-brand-text"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary text-sm py-2 px-4">
                    <?= e(t('yangi_test')) ?>
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
                <div class="py-16 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-sky-100 to-blue-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-brand-muted mb-4"><?= e(t('natijalar_yoq')) ?></p>
                    <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary inline-flex">
                        Birinchi testni boshlash
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($oxirgi as $i => $r):
                        $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                        $rang_class = $foiz >= 90 ? 'from-emerald-100 to-teal-100 text-emerald-700' :
                                     ($foiz >= 70 ? 'from-sky-100 to-blue-100 text-sky-700' :
                                     ($foiz >= 50 ? 'from-amber-100 to-orange-100 text-amber-700' : 'from-rose-100 to-pink-100 text-rose-700'));
                    ?>
                        <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                           class="flex items-center justify-between p-4 rounded-xl hover:bg-sky-50 transition-all duration-200 hover:translate-x-1 group fade-up" style="animation-delay: <?= 0.05 * $i ?>s">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $rang_class ?> flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    <?= $foiz ?>%
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-brand-text truncate">№<?= (int)$r['raqam'] ?> — <?= e($r['nomi']) ?></div>
                                    <div class="text-xs text-brand-muted">
                                        <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> · <?= e(vaqt_oldin($r['tugagan'])) ?>
                                    </div>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-brand-light group-hover:text-sky-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- O'ng: 7 kunlik faollik + tezkor harakatlar -->
        <div class="space-y-6">
            <div class="glass-card p-6 fade-up">
                <h2 class="text-xl font-display font-bold text-brand-text mb-5">7 kunlik faollik</h2>
                <?php if (empty($grafik)): ?>
                    <div class="py-12 text-center text-brand-muted text-sm">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-sky-50 flex items-center justify-center">📈</div>
                        Grafik uchun ma'lumot kam
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php
                        $maks = max(array_column($grafik, 'son')) ?: 1;
                        foreach ($grafik as $g):
                            $w = round($g['son'] / $maks * 100);
                        ?>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-14 text-brand-muted text-xs font-medium"><?= e(date('d.m', strtotime($g['sana']))) ?></span>
                                <div class="flex-1 h-7 bg-sky-50 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-sky-400 to-blue-500 rounded-lg flex items-center justify-end pr-2 text-xs font-bold text-white shadow-soft transition-all duration-700" style="width:<?= $w ?>%">
                                        <?= (int)$g['son'] ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="glass-card p-6 fade-up">
                <h3 class="font-display font-bold text-brand-text mb-4 text-sm uppercase tracking-wider">Tezkor harakatlar</h3>
                <div class="space-y-2">
                    <a href="<?= e(SAYT_URL) ?>/test" class="flex items-center justify-between p-3 rounded-xl hover:bg-sky-50 transition group">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center text-base">📝</span>
                            <span class="font-medium text-brand-text"><?= e(t('biletlar_royxati')) ?></span>
                        </div>
                        <svg class="w-4 h-4 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/chat" class="flex items-center justify-between p-3 rounded-xl hover:bg-sky-50 transition group">
                        <div class="flex items-center gap-3 relative">
                            <span class="w-9 h-9 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center text-base">💬</span>
                            <span class="font-medium text-brand-text">Yordam (AI/Admin)</span>
                            <?php $chat_oqilmagan = chat_oqilmagan_son($f['id']); if ($chat_oqilmagan > 0): ?>
                                <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-bold animate-pulse-soft"><?= $chat_oqilmagan ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="w-4 h-4 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center justify-between p-3 rounded-xl hover:bg-sky-50 transition group">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-base">👤</span>
                            <span class="font-medium text-brand-text"><?= e(t('profil')) ?></span>
                        </div>
                        <svg class="w-4 h-4 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center justify-between p-3 rounded-xl hover:bg-sky-50 transition group">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-base">🎁</span>
                            <span class="font-medium text-brand-text"><?= e(t('referal')) ?></span>
                        </div>
                        <svg class="w-4 h-4 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="flex items-center justify-between p-3 rounded-xl hover:bg-sky-50 transition group">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-base">💎</span>
                            <span class="font-medium text-brand-text"><?= e(t('tariflar')) ?></span>
                        </div>
                        <svg class="w-4 h-4 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <!-- So'nggi bildirishnomalar -->
            <?php if (!empty($bildirishnomalar)): ?>
            <div class="glass-card p-6 fade-up">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display font-bold text-brand-text text-sm uppercase tracking-wider flex items-center gap-2">
                        🔔 So'nggi bildirishnomalar
                    </h3>
                    <a href="<?= e(SAYT_URL) ?>/bildirishnomalar" class="text-xs text-sky-600 hover:underline font-semibold">Barchasi</a>
                </div>
                <div class="space-y-2">
                    <?php foreach ($bildirishnomalar as $b):
                        $rang = match ($b['tur']) {
                            'muvaffaqiyat'  => 'bg-emerald-50 border-emerald-200',
                            'ogohlantirish' => 'bg-amber-50 border-amber-200',
                            'xato'          => 'bg-rose-50 border-rose-200',
                            default         => 'bg-sky-50 border-sky-200',
                        };
                    ?>
                        <a href="<?= e($b['link'] ?: '#') ?>"
                           class="block p-3 rounded-xl border <?= $rang ?> hover:shadow-soft transition <?= !$b['oqilgan'] ? 'ring-1 ring-sky-300' : '' ?>">
                            <div class="flex items-start gap-2">
                                <span class="text-xl flex-shrink-0"><?= e($b['ikon'] ?: '🔔') ?></span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-brand-text line-clamp-1"><?= e($b['sarlavha']) ?></div>
                                    <?php if ($b['matn']): ?>
                                        <div class="text-xs text-brand-muted line-clamp-2 mt-0.5"><?= e($b['matn']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-[10px] text-brand-light mt-1"><?= e(vaqt_oldin($b['yaratilgan'])) ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
