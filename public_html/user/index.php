<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi boshqaruv paneli (dashboard)
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
    'SELECT o.*, t.nomi AS tarif_nomi, t.nomi_cyrl AS tarif_nomi_cyrl
     FROM obunalar o JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

// ----- Davom etayotgan test -----
$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi, b.nomi_cyrl
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

// ----- Oxirgi natijalar (5 ta) -----
$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi, b.nomi_cyrl
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

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8">
    <!-- Salomlashish -->
    <div class="mb-8 fade-up">
        <h1 class="text-3xl mb-1"><?= e(t('salom')) ?>, <span class="text-blue-400"><?= e(fu_ism($f) ?: $f['ism']) ?></span> 👋</h1>
        <p class="text-brand-muted">Bugun nimani o'rganamiz?</p>
    </div>

    <!-- Statistika kartalar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-card glass-card-hover p-5 fade-up" style="animation-delay:.05s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 011-1h0a1 1 0 011 1v2a1 1 0 11-2 0V2zM4.22 5.64a1 1 0 011.41 0l1.42 1.42a1 1 0 01-1.41 1.41L4.22 7.05a1 1 0 010-1.41zM2 10a1 1 0 011-1h2a1 1 0 110 2H3a1 1 0 01-1-1zM10 18a1 1 0 011 1v0a1 1 0 11-2 0 1 1 0 011-1zm5.78-12.36a1 1 0 011.42 0 1 1 0 010 1.41l-1.42 1.42a1 1 0 11-1.41-1.41l1.41-1.42zM15 9a1 1 0 011 1 1 1 0 11-2 0 1 1 0 011-1zM10 6a4 4 0 100 8 4 4 0 000-8z"/></svg>
                </div>
                <span class="text-xs text-brand-muted uppercase tracking-wider"><?= e(t('umumiy_test')) ?></span>
            </div>
            <div class="text-3xl font-display font-bold"><?= $jami_test ?></div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up" style="animation-delay:.1s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                </div>
                <span class="text-xs text-brand-muted uppercase tracking-wider"><?= e(t('togri_javoblar')) ?></span>
            </div>
            <div class="text-3xl font-display font-bold"><?= $togri_javoblar ?></div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up" style="animation-delay:.15s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                </div>
                <span class="text-xs text-brand-muted uppercase tracking-wider"><?= e(t('oz_natija')) ?></span>
            </div>
            <div class="text-3xl font-display font-bold"><?= $oz_natija ?>%</div>
        </div>

        <div class="glass-card glass-card-hover p-5 fade-up" style="animation-delay:.2s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl <?= $obuna ? 'bg-yellow-500/20 text-yellow-400' : 'bg-white/10 text-white/40' ?> flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L3 6v6c0 4 3 7 7 8 4-1 7-4 7-8V6l-7-4z"/></svg>
                </div>
                <span class="text-xs text-brand-muted uppercase tracking-wider"><?= e(t('obuna_holati')) ?></span>
            </div>
            <?php if ($obuna): ?>
                <div class="text-lg font-display font-bold text-yellow-400"><?= e(tk(['nomi' => $obuna['tarif_nomi'], 'nomi_cyrl' => $obuna['tarif_nomi_cyrl']], 'nomi')) ?></div>
                <div class="text-xs text-brand-muted mt-1"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'], 'd.m.Y')) ?></div>
            <?php else: ?>
                <div class="text-lg font-display font-bold text-brand-muted"><?= e(t('obuna_yoq')) ?></div>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="text-xs text-blue-400 hover:underline mt-1 inline-block"><?= e(t('tarif_olish')) ?> →</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Davom etayotgan test -->
    <?php if ($davom): ?>
        <div class="glass-card p-6 mb-8 border-yellow-500/30 fade-up bg-yellow-500/5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="text-yellow-400 text-sm font-medium mb-1">⏳ Davom etayotgan test</div>
                    <h3 class="text-xl font-display"><?= e(tk($davom, 'nomi')) ?></h3>
                    <p class="text-sm text-brand-muted mt-1">Boshlangan: <?= e(vaqt_oldin($davom['boshlangan'])) ?></p>
                </div>
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>" class="btn-primary">
                    <?= e(t('davom_etish')) ?> →
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Chap: oxirgi natijalar -->
        <div class="lg:col-span-2 glass-card p-6 fade-up">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-display"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary text-sm py-2 px-4">
                    <?= e(t('yangi_test')) ?>
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
                <div class="py-12 text-center text-brand-muted">
                    <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p><?= e(t('natijalar_yoq')) ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($oxirgi as $r):
                        $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                        $rang = $foiz >= 90 ? 'green' : ($foiz >= 70 ? 'blue' : ($foiz >= 50 ? 'yellow' : 'red'));
                    ?>
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-white/5 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-lg bg-<?= $rang ?>-500/20 text-<?= $rang ?>-400 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    <?= $foiz ?>%
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium truncate">№<?= (int)$r['raqam'] ?> — <?= e(tk($r, 'nomi')) ?></div>
                                    <div class="text-xs text-brand-muted">
                                        <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> · <?= e(vaqt_oldin($r['tugagan'])) ?>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                               class="text-brand-muted hover:text-white text-sm">→</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- O'ng: 7 kunlik faollik -->
        <div class="glass-card p-6 fade-up">
            <h2 class="text-xl font-display mb-5">7 kunlik faollik</h2>
            <?php if (empty($grafik)): ?>
                <div class="py-12 text-center text-brand-muted text-sm">
                    Grafik uchun ma'lumot kam
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php
                    $maks = max(array_column($grafik, 'son')) ?: 1;
                    foreach ($grafik as $g):
                        $w = round($g['son'] / $maks * 100);
                    ?>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-16 text-brand-muted text-xs"><?= e(date('d.m', strtotime($g['sana']))) ?></span>
                            <div class="flex-1 h-6 bg-white/5 rounded-md overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-md flex items-center justify-end pr-2 text-xs font-medium" style="width:<?= $w ?>%">
                                    <?= (int)$g['son'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-white/10">
                <h3 class="font-display mb-3 text-sm">Tezkor harakatlar</h3>
                <div class="space-y-2">
                    <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span><?= e(t('profil')) ?></span>
                        <span class="text-brand-muted">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span><?= e(t('referal')) ?></span>
                        <span class="text-brand-muted">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span><?= e(t('tariflar')) ?></span>
                        <span class="text-brand-muted">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
