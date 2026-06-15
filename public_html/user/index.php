<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

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

$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi
     FROM obunalar o JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

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
    <div class="mb-8 fade-up">
        <h1 class="text-3xl md:text-4xl mb-1 font-display font-extrabold">
            <?= e(t('salom')) ?>, <span class="grad-text"><?= e($f['ism']) ?></span> 👋
        </h1>
        <p class="text-muted">Bugun nimani o'rganamiz?</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass glass-hover p-5 fade-up" style="animation-delay:.05s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-cyan/15 text-cyan flex items-center justify-center text-lg">📝</div>
                <span class="text-xs text-muted uppercase tracking-wider"><?= e(t('umumiy_test')) ?></span>
            </div>
            <div class="text-3xl font-display font-extrabold"><?= $jami_test ?></div>
        </div>

        <div class="glass glass-hover p-5 fade-up" style="animation-delay:.1s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-success/15 text-success flex items-center justify-center text-lg">✓</div>
                <span class="text-xs text-muted uppercase tracking-wider"><?= e(t('togri_javoblar')) ?></span>
            </div>
            <div class="text-3xl font-display font-extrabold"><?= $togri_javoblar ?></div>
        </div>

        <div class="glass glass-hover p-5 fade-up" style="animation-delay:.15s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-violet/15 text-violet flex items-center justify-center text-lg">📊</div>
                <span class="text-xs text-muted uppercase tracking-wider"><?= e(t('oz_natija')) ?></span>
            </div>
            <div class="text-3xl font-display font-extrabold grad-text"><?= $oz_natija ?>%</div>
        </div>

        <div class="glass glass-hover p-5 fade-up" style="animation-delay:.2s">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl <?= $obuna ? 'bg-amber/15 text-amber' : 'bg-white/5 text-white/40' ?> flex items-center justify-center text-lg">⚡</div>
                <span class="text-xs text-muted uppercase tracking-wider"><?= e(t('obuna_holati')) ?></span>
            </div>
            <?php if ($obuna): ?>
                <div class="text-lg font-display font-bold text-amber"><?= e($obuna['tarif_nomi']) ?></div>
                <div class="text-xs text-muted mt-1"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'], 'd.m.Y')) ?></div>
            <?php else: ?>
                <div class="text-lg font-display font-bold text-muted"><?= e(t('obuna_yoq')) ?></div>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="text-xs text-violet hover:text-pink mt-1 inline-block transition"><?= e(t('tarif_olish')) ?> →</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($davom): ?>
        <div class="ring-grad mb-8 fade-up">
            <div class="p-6 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber/20 text-amber flex items-center justify-center text-2xl flex-shrink-0">⏳</div>
                    <div>
                        <div class="text-amber text-sm font-medium mb-1"><?= e(t('davom_etayotgan')) ?></div>
                        <h3 class="text-xl font-display font-bold">№<?= (int)$davom['raqam'] ?> — <?= e($davom['nomi']) ?></h3>
                        <p class="text-sm text-muted mt-1"><?= e(t('boshlangan')) ?>: <?= e(vaqt_oldin($davom['boshlangan'])) ?></p>
                    </div>
                </div>
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>" class="btn btn-primary">
                    <?= e(t('davom_etish')) ?> →
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass p-6 fade-up">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-display font-bold"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary text-sm py-2 px-4">
                    + <?= e(t('yangi_test')) ?>
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
                <div class="py-12 text-center text-muted">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl grad-bg-soft flex items-center justify-center text-4xl opacity-60">📋</div>
                    <p><?= e(t('natijalar_yoq')) ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($oxirgi as $r):
                        $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                        $rang = $foiz >= 90 ? 'success' : ($foiz >= 70 ? 'cyan' : ($foiz >= 50 ? 'amber' : 'danger'));
                    ?>
                        <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-<?= $rang ?>/15 text-<?= $rang ?> flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    <?= $foiz ?>%
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium truncate">№<?= (int)$r['raqam'] ?> — <?= e($r['nomi']) ?></div>
                                    <div class="text-xs text-muted">
                                        <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> · <?= e(vaqt_oldin($r['tugagan'])) ?>
                                    </div>
                                </div>
                            </div>
                            <span class="text-muted group-hover:text-white text-sm transition">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass p-6 fade-up">
            <h2 class="text-xl font-display font-bold mb-5"><?= e(t('kunlik_faollik')) ?></h2>
            <?php if (empty($grafik)): ?>
                <div class="py-10 text-center text-muted text-sm">
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
                            <span class="w-16 text-muted text-xs"><?= e(date('d.m', strtotime($g['sana']))) ?></span>
                            <div class="flex-1 h-7 bg-white/5 rounded-lg overflow-hidden">
                                <div class="h-full grad-bg rounded-lg flex items-center justify-end pr-2 text-xs font-bold text-white" style="width:<?= $w ?>%">
                                    <?= (int)$g['son'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-white/10">
                <h3 class="font-display font-bold mb-3 text-sm"><?= e(t('tezkor_harakatlar')) ?></h3>
                <div class="space-y-1.5">
                    <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span class="flex items-center gap-2">👤 <?= e(t('profil')) ?></span>
                        <span class="text-muted">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span class="flex items-center gap-2">🎁 <?= e(t('referal')) ?></span>
                        <span class="text-muted">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-white/5 text-sm transition">
                        <span class="flex items-center gap-2">💎 <?= e(t('tariflar')) ?></span>
                        <span class="text-muted">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
