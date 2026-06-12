<?php
/**
 * Admin — Analitika va hisobotlar
 *
 * Asosiy ko'rsatkichlar:
 *  - Foydalanuvchilar (jami, yangi, faol)
 *  - Daromad (jami, oylik, kunlik trend)
 *  - Konversiya: ro'yxat -> birinchi to'lov
 *  - Eng ko'p sotilgan tariflar
 *  - 7 va 30 kunlik grafiklar
 *  - Saytning aktiv vaqtlari (soat bo'yicha)
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

// ====== ASOSIY KO'RSATKICHLAR ======
$foyd_jami = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
$foyd_30 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 30 DAY)');
$foyd_7 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 7 DAY)');
$foyd_24 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');

$foyd_aktiv_30 = (int) db_qiymat('SELECT COUNT(DISTINCT foydalanuvchi_id) FROM natijalar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 30 DAY)');

$tolov_jami = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
$tolov_30 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 30 DAY)');
$tolov_7 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 7 DAY)');
$tolov_24 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');

$obuna_faol = (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()');

// Konversiya
$tolov_qilganlar = (int) db_qiymat('SELECT COUNT(DISTINCT foydalanuvchi_id) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
$konversiya = $foyd_jami > 0 ? round($tolov_qilganlar / $foyd_jami * 100, 1) : 0;

// O'rtacha to'lov
$ortacha_tolov = $tolov_qilganlar > 0 ? $tolov_jami / $tolov_qilganlar : 0;

// ====== 30 KUNLIK GRAFIKLAR ======
$reg_grafik = db_barcha(
    'SELECT DATE(yaratilgan) AS sana, COUNT(*) AS son
     FROM foydalanuvchilar
     WHERE yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(yaratilgan) ORDER BY sana'
);

$tolov_grafik = db_barcha(
    'SELECT DATE(yaratilgan) AS sana, SUM(summa) AS summa
     FROM tolovlar
     WHERE holat = "muvaffaqiyatli" AND yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(yaratilgan) ORDER BY sana'
);

$test_grafik = db_barcha(
    'SELECT DATE(tugagan) AS sana, COUNT(*) AS son
     FROM natijalar
     WHERE holat = "tugagan" AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(tugagan) ORDER BY sana'
);

// ====== EN KO'P SOTILGAN TARIFLAR ======
$top_tariflar = db_barcha(
    'SELECT t.nomi, t.narx, COUNT(*) AS sotilgan, SUM(tl.summa) AS jami_summa
     FROM tolovlar tl
     JOIN tariflar t ON tl.tarif_id = t.id
     WHERE tl.holat = "muvaffaqiyatli"
     GROUP BY t.id ORDER BY sotilgan DESC LIMIT 10'
);

// ====== AKTIV SOATLAR (oxirgi 30 kun) ======
$soatlar = db_barcha(
    'SELECT HOUR(yaratilgan) AS soat, COUNT(*) AS son
     FROM natijalar
     WHERE yaratilgan >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY HOUR(yaratilgan) ORDER BY soat'
);
$soat_map = array_column($soatlar, 'son', 'soat');

// ====== TO'LOV USULLARI ======
$tolov_usuli = db_barcha(
    'SELECT tolov_turi, COUNT(*) AS son, SUM(summa) AS summa
     FROM tolovlar WHERE holat = "muvaffaqiyatli"
     GROUP BY tolov_turi ORDER BY summa DESC'
);

// ====== TOP FOYDALANUVCHILAR (eng ko'p test ishlagan) ======
$top_foyd = db_barcha(
    'SELECT fo.id, fo.ism, fo.familiya, fo.telefon, COUNT(n.id) AS test_son, SUM(n.togri_son) AS togri
     FROM natijalar n JOIN foydalanuvchilar fo ON n.foydalanuvchi_id = fo.id
     WHERE n.holat = "tugagan"
     GROUP BY fo.id ORDER BY test_son DESC LIMIT 10'
);

// Tendentsiya % (haftalik)
function tend_foiz(int $bu_hafta, int $oldingi_hafta): array {
    if ($oldingi_hafta === 0) return ['son' => $bu_hafta, 'foiz' => $bu_hafta > 0 ? 100 : 0];
    $foiz = round(($bu_hafta - $oldingi_hafta) / $oldingi_hafta * 100, 1);
    return ['son' => $bu_hafta, 'foiz' => $foiz];
}
$reg_oldingi_7 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)');
$reg_tend = tend_foiz($foyd_7, $reg_oldingi_7);

$tolov_oldingi_7 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)');
$tolov_tend_foiz = $tolov_oldingi_7 > 0 ? round(($tolov_7 - $tolov_oldingi_7) / $tolov_oldingi_7 * 100, 1) : 100;

$admin_sahifa = 'analitika';
$sahifa_sarlavha = 'Analitika';
require_once __DIR__ . '/_layout.php';
?>

<!-- Asosiy KPI kartalar -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-2">Foydalanuvchilar</div>
        <div class="text-3xl font-display font-bold text-brand-text"><?= $foyd_jami ?></div>
        <div class="text-xs mt-1 <?= $reg_tend['foiz'] >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= $reg_tend['foiz'] >= 0 ? '↑' : '↓' ?> <?= abs($reg_tend['foiz']) ?>% (vs o'tgan hafta)
        </div>
    </div>

    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-2">Faol obunalar</div>
        <div class="text-3xl font-display font-bold text-amber-600"><?= $obuna_faol ?></div>
        <div class="text-xs text-brand-muted mt-1">hozir aktiv</div>
    </div>

    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-2">Daromad (jami)</div>
        <div class="text-2xl font-display font-bold text-emerald-700"><?= e(pul($tolov_jami)) ?></div>
        <div class="text-xs mt-1 <?= $tolov_tend_foiz >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= $tolov_tend_foiz >= 0 ? '↑' : '↓' ?> <?= abs($tolov_tend_foiz) ?>% hafta
        </div>
    </div>

    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-2">Konversiya</div>
        <div class="text-3xl font-display font-bold text-violet-600"><?= $konversiya ?>%</div>
        <div class="text-xs text-brand-muted mt-1"><?= $tolov_qilganlar ?> / <?= $foyd_jami ?> to'lov qildi</div>
    </div>
</div>

<!-- Davr ko'rsatkichlari -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="glass-card p-4">
        <div class="text-xs text-brand-muted uppercase">Bugungi reg</div>
        <div class="text-xl font-display font-bold text-brand-text"><?= $foyd_24 ?></div>
    </div>
    <div class="glass-card p-4">
        <div class="text-xs text-brand-muted uppercase">Haftalik reg</div>
        <div class="text-xl font-display font-bold text-brand-text"><?= $foyd_7 ?></div>
    </div>
    <div class="glass-card p-4">
        <div class="text-xs text-brand-muted uppercase">Bugungi tushum</div>
        <div class="text-xl font-display font-bold text-emerald-700"><?= e(pul($tolov_24)) ?></div>
    </div>
    <div class="glass-card p-4">
        <div class="text-xs text-brand-muted uppercase">O'rtacha to'lov</div>
        <div class="text-xl font-display font-bold text-brand-text"><?= e(pul($ortacha_tolov)) ?></div>
    </div>
</div>

<!-- 30-kunlik grafiklar -->
<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">📈 Yangi foydalanuvchilar (30 kun)</h2>
        <?php $maks = max(array_column($reg_grafik, 'son')) ?: 1; ?>
        <div class="flex items-end gap-1 h-32">
            <?php foreach ($reg_grafik as $g):
                $h = round($g['son'] / $maks * 100);
            ?>
                <div class="flex-1 flex flex-col items-center group" title="<?= e(date('d.m', strtotime($g['sana']))) ?>: <?= (int)$g['son'] ?>">
                    <div class="w-full bg-gradient-to-t from-sky-500 to-sky-300 rounded-t transition-all duration-300 group-hover:from-sky-600 group-hover:to-sky-400" style="height: <?= max(2, $h) ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-brand-muted text-center mt-2">30 kun ichida: <strong class="text-brand-text"><?= $foyd_30 ?></strong> ta yangi foydalanuvchi</p>
    </div>

    <div class="glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">💰 Daromad (30 kun)</h2>
        <?php $maks_t = max(array_column($tolov_grafik, 'summa')) ?: 1; ?>
        <div class="flex items-end gap-1 h-32">
            <?php foreach ($tolov_grafik as $g):
                $h = round((float)$g['summa'] / $maks_t * 100);
            ?>
                <div class="flex-1 flex flex-col items-center group" title="<?= e(date('d.m', strtotime($g['sana']))) ?>: <?= e(pul($g['summa'])) ?>">
                    <div class="w-full bg-gradient-to-t from-emerald-500 to-emerald-300 rounded-t transition-all duration-300 group-hover:from-emerald-600 group-hover:to-emerald-400" style="height: <?= max(2, $h) ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-brand-muted text-center mt-2">30 kun ichida: <strong class="text-brand-text"><?= e(pul($tolov_30)) ?></strong></p>
    </div>
</div>

<!-- Top tariflar va to'lov usullari -->
<div class="grid lg:grid-cols-2 gap-5 mb-6">
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">🏆 Eng ko'p sotilgan tariflar</h2>
        <?php if (empty($top_tariflar)): ?>
            <p class="text-brand-muted text-sm text-center py-6">Hali sotuv yo'q</p>
        <?php else:
            $maks_s = max(array_column($top_tariflar, 'sotilgan')) ?: 1;
        ?>
            <div class="space-y-2.5">
                <?php foreach ($top_tariflar as $tt):
                    $w = round($tt['sotilgan'] / $maks_s * 100);
                ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-brand-text"><?= e($tt['nomi']) ?></span>
                            <span class="font-bold text-emerald-700"><?= (int)$tt['sotilgan'] ?> ta · <?= e(pul($tt['jami_summa'])) ?></span>
                        </div>
                        <div class="h-2 bg-sky-50 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-400 to-blue-500 rounded-full" style="width: <?= $w ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">💳 To'lov usullari</h2>
        <?php if (empty($tolov_usuli)): ?>
            <p class="text-brand-muted text-sm text-center py-6">Hali to'lov yo'q</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php
                $jami_son = array_sum(array_column($tolov_usuli, 'son'));
                foreach ($tolov_usuli as $tu):
                    $foiz = round($tu['son'] / max(1, $jami_son) * 100);
                    $rang = match ($tu['tolov_turi']) {
                        'click'   => 'from-sky-400 to-blue-500',
                        'payme'   => 'from-cyan-400 to-teal-500',
                        'manual'  => 'from-amber-400 to-orange-500',
                        'bonus'   => 'from-emerald-400 to-green-500',
                        default   => 'from-violet-400 to-purple-500',
                    };
                ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-brand-text uppercase"><?= e($tu['tolov_turi']) ?></span>
                            <span class="text-xs text-brand-muted"><?= (int)$tu['son'] ?> ta · <?= e(pul($tu['summa'])) ?></span>
                        </div>
                        <div class="h-2 bg-sky-50 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r <?= $rang ?> rounded-full transition-all duration-700" style="width: <?= $foiz ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Aktiv soatlar + Top foydalanuvchilar -->
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">⏰ Aktiv soatlar (30 kun)</h2>
        <?php $soat_maks = $soat_map ? max($soat_map) : 1; ?>
        <div class="flex items-end gap-0.5 h-24">
            <?php for ($s = 0; $s < 24; $s++):
                $son = $soat_map[$s] ?? 0;
                $h = round($son / max(1, $soat_maks) * 100);
            ?>
                <div class="flex-1 flex flex-col items-center justify-end group" title="<?= $s ?>:00 — <?= $son ?> ta">
                    <div class="w-full bg-gradient-to-t from-violet-500 to-violet-300 rounded-t transition-all group-hover:from-violet-600" style="height: <?= max(2, $h) ?>%"></div>
                </div>
            <?php endfor; ?>
        </div>
        <div class="flex justify-between text-[10px] text-brand-muted mt-1.5 px-1">
            <span>00</span><span>06</span><span>12</span><span>18</span><span>23</span>
        </div>
    </div>

    <div class="glass-card p-5 fade-up">
        <h2 class="font-display font-bold text-brand-text mb-4">🥇 Eng faol foydalanuvchilar</h2>
        <div class="space-y-2">
            <?php foreach ($top_foyd as $i => $tf): ?>
                <div class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-sky-50/50">
                    <span class="w-6 text-center text-xs font-bold <?= $i < 3 ? 'text-amber-500' : 'text-brand-light' ?>">#<?= $i + 1 ?></span>
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                        <?= e(mb_strtoupper(mb_substr($tf['ism'], 0, 1))) ?>
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-brand-text truncate"><?= e($tf['ism']) ?></div>
                        <div class="text-[10px] text-brand-muted"><?= (int)$tf['test_son'] ?> test · <?= (int)$tf['togri'] ?> ✓</div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($top_foyd)): ?>
                <p class="text-brand-muted text-sm text-center py-4">Hali ma'lumot yo'q</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
