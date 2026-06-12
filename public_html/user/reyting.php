<?php
/**
 * VatanParvar Yaypan — Reyting (Leaderboard)
 *
 * Filterlar: kunlik, haftalik, oylik, jami
 * Mezon: imtihondan o'tgan + to'g'ri javoblar yig'indisi
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$davr = olish('davr', 'haftalik');

$shartlar = ['n.holat = "tugagan"'];
if ($davr === 'kunlik') {
    $shartlar[] = 'DATE(n.tugagan) = CURDATE()';
} elseif ($davr === 'haftalik') {
    $shartlar[] = 'n.tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($davr === 'oylik') {
    $shartlar[] = 'n.tugagan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
}
$where = ' WHERE ' . implode(' AND ', $shartlar);

// Top 50
$top = db_barcha(
    "SELECT
        fo.id, fo.ism, fo.familiya, fo.avatar,
        SUM(n.togri_son) AS togri,
        COUNT(*) AS test_son,
        SUM(CASE WHEN n.tur = 'imtihon' AND n.otdimi = 1 THEN 1 ELSE 0 END) AS imtihon_pass,
        ROUND(AVG(n.togri_son / NULLIF(n.umumiy_son, 0) * 100), 1) AS oz_foiz
     FROM natijalar n
     JOIN foydalanuvchilar fo ON n.foydalanuvchi_id = fo.id
     $where
     GROUP BY fo.id
     ORDER BY togri DESC, imtihon_pass DESC, oz_foiz DESC
     LIMIT 50"
);

// Mening o'rnim
$mening_orin = null;
foreach ($top as $i => $t) {
    if ((int)$t['id'] === (int)$f['id']) {
        $mening_orin = $i + 1;
        break;
    }
}
if ($mening_orin === null) {
    // Top 50 dan tashqarida bo'lsa, taxminiy o'rin
    $umumiy = (int) db_qiymat(
        "SELECT COUNT(DISTINCT n.foydalanuvchi_id) FROM natijalar n $where"
    );
    if ($umumiy > 50) $mening_orin = "50+";
}

$sahifa_sarlavha = "Reyting jadvali";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 py-8">

    <!-- Hero -->
    <div class="text-center mb-8 fade-up">
        <div class="text-5xl mb-3 floating">🏆</div>
        <h1 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">
            Reyting jadvali
        </h1>
        <p class="text-brand-muted text-lg max-w-xl mx-auto">
            Eng aktiv va eng yaxshi natija egalari
        </p>
    </div>

    <!-- Davr filterlari -->
    <div class="flex justify-center gap-1 mb-6 fade-up">
        <div class="inline-flex p-1 bg-white rounded-2xl border border-brand-border shadow-soft">
            <?php foreach (['kunlik' => 'Bugun', 'haftalik' => 'Hafta', 'oylik' => 'Oy', 'jami' => 'Jami'] as $k => $n): ?>
                <a href="?davr=<?= $k ?>"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition <?= $davr === $k ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50' ?>">
                    <?= $n ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mening o'rnim -->
    <?php if ($mening_orin): ?>
        <div class="glass-card p-4 mb-6 fade-up bg-gradient-to-r from-sky-50 to-blue-50 border-sky-200">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-display font-bold text-white text-lg">
                    <?= is_numeric($mening_orin) ? '#' . $mening_orin : $mening_orin ?>
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-brand-text">Sizning o'rningiz</div>
                    <div class="text-xs text-brand-muted">Yuqoriga ko'tarilish uchun ko'proq mashq qiling</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Top 3 — taxt -->
    <?php if (count($top) >= 3): ?>
        <div class="grid grid-cols-3 gap-3 mb-8 fade-up">
            <!-- 2-o'rin -->
            <div class="text-center">
                <div class="relative inline-block mb-2 pt-6">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-slate-300 to-slate-400 flex items-center justify-center font-display font-bold text-white text-xl shadow-medium">
                        <?= e(mb_strtoupper(mb_substr($top[1]['ism'], 0, 1))) ?>
                    </div>
                    <div class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-slate-400 text-white flex items-center justify-center font-bold text-sm shadow-soft">2</div>
                </div>
                <div class="font-semibold text-brand-text text-sm truncate"><?= e($top[1]['ism']) ?></div>
                <div class="text-xs text-brand-muted"><?= (int)$top[1]['togri'] ?> ✓</div>
                <div class="h-16 mt-2 bg-gradient-to-t from-slate-300 to-slate-200 rounded-t-xl"></div>
            </div>
            <!-- 1-o'rin (taxt) -->
            <div class="text-center">
                <div class="relative inline-block mb-2">
                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 text-3xl">👑</span>
                    <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center font-display font-bold text-white text-2xl shadow-glow ring-4 ring-amber-200">
                        <?= e(mb_strtoupper(mb_substr($top[0]['ism'], 0, 1))) ?>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold shadow-soft">1</div>
                </div>
                <div class="font-display font-bold text-brand-text truncate"><?= e($top[0]['ism']) ?></div>
                <div class="text-sm text-amber-600 font-semibold"><?= (int)$top[0]['togri'] ?> ✓</div>
                <div class="h-24 mt-2 bg-gradient-to-t from-amber-400 to-amber-300 rounded-t-xl"></div>
            </div>
            <!-- 3-o'rin -->
            <div class="text-center">
                <div class="relative inline-block mb-2 pt-8">
                    <div class="w-14 h-14 mx-auto rounded-full bg-gradient-to-br from-orange-400 to-amber-600 flex items-center justify-center font-display font-bold text-white shadow-medium">
                        <?= e(mb_strtoupper(mb_substr($top[2]['ism'], 0, 1))) ?>
                    </div>
                    <div class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-sm shadow-soft">3</div>
                </div>
                <div class="font-semibold text-brand-text text-sm truncate"><?= e($top[2]['ism']) ?></div>
                <div class="text-xs text-brand-muted"><?= (int)$top[2]['togri'] ?> ✓</div>
                <div class="h-12 mt-2 bg-gradient-to-t from-orange-400 to-orange-300 rounded-t-xl"></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Ro'yxat -->
    <div class="glass-card p-3 fade-up">
        <?php if (empty($top)): ?>
            <div class="py-12 text-center text-brand-muted">
                <div class="text-5xl mb-3">🏁</div>
                Bu davrda hech kim test ishlamadi. Birinchi bo'ling!
            </div>
        <?php else: ?>
            <div class="divide-y divide-brand-border">
                <?php foreach ($top as $i => $t):
                    $orin = $i + 1;
                    $is_me = (int)$t['id'] === (int)$f['id'];
                ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl <?= $is_me ? 'bg-gradient-to-r from-sky-50 to-blue-50 ring-1 ring-sky-200' : '' ?>">
                        <div class="w-10 flex-shrink-0 flex items-center justify-center font-display font-bold
                            <?= $orin === 1 ? 'text-amber-500 text-xl' :
                               ($orin === 2 ? 'text-slate-400 text-lg' :
                               ($orin === 3 ? 'text-orange-500 text-lg' : 'text-brand-muted')) ?>">
                            <?php if ($orin <= 3): ?>
                                <?= ['🥇','🥈','🥉'][$orin - 1] ?>
                            <?php else: ?>
                                #<?= $orin ?>
                            <?php endif; ?>
                        </div>

                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white shadow-soft flex-shrink-0">
                            <?php if ($t['avatar'] && is_file(UPLOAD_PATH . '/' . $t['avatar'])): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($t['avatar']) ?>" class="w-full h-full rounded-full object-cover">
                            <?php else: ?>
                                <?= e(mb_strtoupper(mb_substr($t['ism'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-brand-text truncate">
                                <?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?>
                                <?php if ($is_me): ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-sky-500 text-white ml-1">Siz</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-brand-muted flex items-center gap-2">
                                <span><?= (int)$t['test_son'] ?> test</span>
                                <?php if ((int)$t['imtihon_pass'] > 0): ?>
                                    <span class="text-emerald-600">· 🎓 <?= (int)$t['imtihon_pass'] ?></span>
                                <?php endif; ?>
                                <?php if ($t['oz_foiz']): ?>
                                    <span>· <?= round($t['oz_foiz']) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <div class="font-display font-bold text-lg <?= $orin <= 3 ? 'text-gradient' : 'text-brand-text' ?>"><?= (int)$t['togri'] ?></div>
                            <div class="text-[10px] text-brand-muted uppercase">to'g'ri</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-center text-xs text-brand-muted mt-6">
        Reyting har 5 daqiqada yangilanadi. To'g'ri javoblar yig'indisi bo'yicha tartiblanadi.
    </p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
