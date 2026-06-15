<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$stat = db_qator(
    "SELECT
        COUNT(*) AS jami,
        COALESCE(SUM(togri_son), 0) AS togri,
        COALESCE(SUM(xato_son), 0)  AS xato,
        COALESCE(SUM(umumiy_son), 0) AS umumiy,
        COALESCE(MAX(togri_son/umumiy_son*100), 0) AS eng_yaxshi_foiz,
        COALESCE(AVG(togri_son/umumiy_son*100), 0) AS ortacha_foiz,
        COALESCE(MIN(togri_son/umumiy_son*100), 0) AS eng_yomon_foiz
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = 'tugagan' AND umumiy_son > 0",
    [$f['id']]
);
$jami = (int) ($stat['jami'] ?? 0);

$kunlik = db_barcha(
    "SELECT DATE(tugagan) AS sana,
            COUNT(*) AS son,
            AVG(togri_son/umumiy_son*100) AS ortacha,
            SUM(togri_son) AS togri,
            SUM(xato_son) AS xato
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = 'tugagan' AND umumiy_son > 0
       AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(tugagan)
     ORDER BY sana",
    [$f['id']]
);

$bilet_stat = db_barcha(
    "SELECT b.raqam, b.nomi,
            COUNT(*) AS son,
            AVG(n.togri_son/n.umumiy_son*100) AS ortacha,
            MAX(n.togri_son/n.umumiy_son*100) AS eng_yaxshi
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = 'tugagan' AND n.umumiy_son > 0
     GROUP BY b.id ORDER BY ortacha DESC LIMIT 20",
    [$f['id']]
);

$soatlik = db_barcha(
    "SELECT HOUR(tugagan) AS soat, COUNT(*) AS son, AVG(togri_son/umumiy_son*100) AS ortacha
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = 'tugagan' AND umumiy_son > 0
     GROUP BY HOUR(tugagan)",
    [$f['id']]
);

$soatlar_xarita = [];
foreach ($soatlik as $s) $soatlar_xarita[(int) $s['soat']] = $s;

$haftalik = db_barcha(
    "SELECT WEEKDAY(tugagan) AS kun, COUNT(*) AS son, AVG(togri_son/umumiy_son*100) AS ortacha
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = 'tugagan' AND umumiy_son > 0
       AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
     GROUP BY WEEKDAY(tugagan)",
    [$f['id']]
);

$kunlar_nomlari = ['Du', 'Se', 'Cho', 'Pa', 'Ju', 'Sha', 'Ya'];
$haftalik_xarita = [];
foreach ($haftalik as $h) $haftalik_xarita[(int) $h['kun']] = $h;

$ortacha_foiz = round($stat['ortacha_foiz']);
$eng_yaxshi = round($stat['eng_yaxshi_foiz']);

$daraja = $ortacha_foiz >= 90 ? ['Mukammal', 'success', '🏆'] :
          ($ortacha_foiz >= 75 ? ['Yaxshi', 'cyan', '⭐'] :
          ($ortacha_foiz >= 60 ? ['O\'rta', 'amber', '📈'] :
                                  ['Mashq kerak', 'danger', '🔄']));

$sahifa_sarlavha = 'Mening statistikam';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 py-8">

    <div class="mb-8 fade-up">
        <span class="chip chip-grad mb-3">📊 Statistika</span>
        <h1 class="text-3xl md:text-4xl mb-2 font-display font-extrabold">
            Mening <span class="grad-text">o'sishim</span>
        </h1>
        <p class="text-muted">Sizning testlardagi natijalaringiz, vaqt bo'yicha o'sish</p>
    </div>

    <?php if ($jami === 0): ?>
        <div class="ring-grad fade-up">
            <div class="p-12 text-center">
                <div class="w-24 h-24 mx-auto rounded-3xl grad-bg-soft flex items-center justify-center text-5xl mb-4">📊</div>
                <p class="text-muted mb-6">Statistika ko'rsatish uchun avval test ishlash kerak.</p>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary">🚗 Birinchi testni boshlash</a>
            </div>
        </div>
    <?php else: ?>

        <div class="ring-grad mb-6 fade-up">
            <div class="p-6 grid sm:grid-cols-2 items-center gap-6">
                <div>
                    <div class="text-sm uppercase tracking-widest text-muted mb-2">Sizning darajangiz</div>
                    <div class="flex items-center gap-3">
                        <span class="text-5xl"><?= $daraja[2] ?></span>
                        <div>
                            <div class="text-3xl font-display font-extrabold text-<?= $daraja[1] ?>"><?= e($daraja[0]) ?></div>
                            <div class="text-sm text-muted">O'rtacha <?= $ortacha_foiz ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-muted mb-1">Eng yaxshi natija</div>
                    <div class="text-5xl font-display font-extrabold grad-text"><?= $eng_yaxshi ?>%</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="glass p-4 fade-up">
                <div class="text-xs text-muted uppercase mb-1">Jami testlar</div>
                <div class="text-3xl font-display font-extrabold"><?= $jami ?></div>
            </div>
            <div class="glass p-4 fade-up">
                <div class="text-xs text-muted uppercase mb-1">To'g'ri javoblar</div>
                <div class="text-3xl font-display font-extrabold text-success"><?= (int) $stat['togri'] ?></div>
            </div>
            <div class="glass p-4 fade-up">
                <div class="text-xs text-muted uppercase mb-1">Xato javoblar</div>
                <div class="text-3xl font-display font-extrabold text-danger"><?= (int) $stat['xato'] ?></div>
            </div>
            <div class="glass p-4 fade-up">
                <div class="text-xs text-muted uppercase mb-1">Aniqlik</div>
                <?php $aniqlik = $stat['umumiy'] > 0 ? round($stat['togri'] / $stat['umumiy'] * 100) : 0; ?>
                <div class="text-3xl font-display font-extrabold grad-text"><?= $aniqlik ?>%</div>
            </div>
        </div>

        <?php if (!empty($kunlik)): ?>
            <div class="glass p-6 mb-6 fade-up">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-display font-bold text-lg">📈 30 kunlik dinamika</h2>
                    <span class="text-xs text-muted"><?= count($kunlik) ?> ta faol kun</span>
                </div>

                <?php
                $maks_son = max(1, max(array_column($kunlik, 'son')));
                ?>
                <div class="overflow-x-auto">
                    <div class="flex items-end gap-1 min-w-max h-48 pb-2">
                        <?php foreach ($kunlik as $k):
                            $bal = round($k['son'] / $maks_son * 100);
                            $foiz = round($k['ortacha']);
                            $rang = $foiz >= 90 ? 'success' : ($foiz >= 70 ? 'cyan' : ($foiz >= 50 ? 'amber' : 'danger'));
                        ?>
                            <div class="flex flex-col items-center group" style="width: 32px;">
                                <div class="text-xs font-bold mb-1 opacity-0 group-hover:opacity-100 transition text-<?= $rang ?>">
                                    <?= $foiz ?>%
                                </div>
                                <div class="w-6 grad-bg rounded-t" style="height: <?= $bal ?>%;
                                            background: <?= $rang === 'success' ? '#10B981' : ($rang === 'cyan' ? '#06B6D4' : ($rang === 'amber' ? '#F59E0B' : '#EF4444')) ?>;"
                                     title="<?= $k['son'] ?> ta test, <?= $foiz ?>%"></div>
                                <div class="text-xs text-muted mt-1 -rotate-45 origin-top-left whitespace-nowrap" style="margin-left: -10px;">
                                    <?= date('d.m', strtotime($k['sana'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($haftalik_xarita) && count($haftalik_xarita) > 0): ?>
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div class="glass p-5 fade-up">
                    <h3 class="font-display font-bold mb-4">🗓️ Hafta kunlari bo'yicha</h3>
                    <div class="space-y-2">
                        <?php
                        $maks_kun = 1;
                        foreach ($haftalik_xarita as $h) $maks_kun = max($maks_kun, $h['son']);
                        for ($i = 0; $i < 7; $i++):
                            $h = $haftalik_xarita[$i] ?? null;
                            $w = $h ? round($h['son'] / $maks_kun * 100) : 0;
                        ?>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-12 text-muted"><?= $kunlar_nomlari[$i] ?></span>
                                <div class="flex-1 h-7 bg-white/5 rounded-lg overflow-hidden">
                                    <?php if ($h): ?>
                                        <div class="h-full grad-bg rounded-lg flex items-center justify-end pr-2 text-xs font-bold text-white"
                                             style="width: <?= max(8, $w) ?>%">
                                            <?= (int) $h['son'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="w-10 text-right text-xs text-muted">
                                    <?= $h ? round($h['ortacha']) . '%' : '—' ?>
                                </span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <?php if (!empty($soatlar_xarita)): ?>
                    <div class="glass p-5 fade-up">
                        <h3 class="font-display font-bold mb-4">⏰ Faollik vaqtlari</h3>
                        <?php
                        $maks_soat = 1;
                        foreach ($soatlar_xarita as $s) $maks_soat = max($maks_soat, $s['son']);
                        ?>
                        <div class="grid grid-cols-12 gap-1 mb-2">
                            <?php for ($i = 0; $i < 24; $i++):
                                $s = $soatlar_xarita[$i] ?? null;
                                $intensity = $s ? round($s['son'] / $maks_soat * 100) : 0;
                            ?>
                                <div class="aspect-square rounded text-[10px] flex items-center justify-center font-mono"
                                     style="background: rgba(139, 92, 246, <?= $intensity / 100 * 0.6 ?>);
                                            color: <?= $intensity > 30 ? 'white' : '#94A3B8' ?>"
                                     title="<?= $i ?>:00 — <?= $s ? $s['son'] . ' test' : '0' ?>">
                                    <?= $i ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted">
                            <span>00:00</span>
                            <span>23:00</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($bilet_stat)): ?>
            <div class="glass p-6 fade-up">
                <h2 class="font-display font-bold text-lg mb-5">🎫 Biletlar bo'yicha natijalar</h2>

                <div class="space-y-2">
                    <?php foreach ($bilet_stat as $b):
                        $foiz = round($b['ortacha']);
                        $eng = round($b['eng_yaxshi']);
                        $rang = $foiz >= 90 ? 'success' : ($foiz >= 70 ? 'cyan' : ($foiz >= 50 ? 'amber' : 'danger'));
                    ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/3 transition group">
                            <div class="w-12 h-12 rounded-xl bg-<?= $rang ?>/15 text-<?= $rang ?> flex items-center justify-center font-display font-bold flex-shrink-0">
                                <?= (int) $b['raqam'] ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate"><?= e($b['nomi']) ?></div>
                                <div class="flex items-center gap-3 mt-1 text-xs text-muted">
                                    <span><?= (int) $b['son'] ?> marta</span>
                                    <span>O'rtacha: <span class="font-bold text-<?= $rang ?>"><?= $foiz ?>%</span></span>
                                    <span>Eng yaxshi: <span class="font-bold text-success"><?= $eng ?>%</span></span>
                                </div>
                            </div>
                            <div class="hidden sm:block w-32 h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-<?= $rang ?> rounded-full transition-all duration-500"
                                     style="width: <?= $foiz ?>%; background: <?= $rang === 'success' ? '#10B981' : ($rang === 'cyan' ? '#06B6D4' : ($rang === 'amber' ? '#F59E0B' : '#EF4444')) ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
