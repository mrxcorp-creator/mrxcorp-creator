<?php
/**
 * AvtoTest Pro — Admin boshqaruv paneli
 */
$admin_sahifa    = 'index';
$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/_layout.php';

$stat = [
    'foydalanuvchi'    => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar'),
    'foydalanuvchi_24h'=> (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'tolov_jami'       => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"'),
    'tolov_24h'        => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'tolov_oy'         => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND MONTH(yaratilgan) = MONTH(NOW()) AND YEAR(yaratilgan) = YEAR(NOW())'),
    'obuna_faol'       => (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()'),
    'test_jami'        => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"'),
    'test_24h'         => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan" AND tugagan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'savol_soni'       => (int) db_qiymat('SELECT COUNT(*) FROM savollar'),
    'bilet_soni'       => (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"'),
];

$oxirgi_tolov = db_barcha(
    'SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     ORDER BY t.yaratilgan DESC LIMIT 8'
);

// Oxirgi 7 kunlik daromad grafigi
$daromad_grafik = db_barcha(
    'SELECT DATE(yaratilgan) AS sana, COALESCE(SUM(summa),0) AS summa, COUNT(*) AS son
     FROM tolovlar
     WHERE holat = "muvaffaqiyatli"
       AND yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(yaratilgan)
     ORDER BY sana'
);
$grafik_maks = max(array_column($daromad_grafik, 'summa') ?: [1]) ?: 1;
?>

<!-- Asosiy statistika kartalari -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['👥', 'Foydalanuvchilar', $stat['foydalanuvchi'],  '+' . $stat['foydalanuvchi_24h']    . ' bugun', 'blue'],
        ['💰', 'Jami daromad',     pul($stat['tolov_jami']),'+' . pul($stat['tolov_24h'])        . ' bugun', 'green'],
        ['⭐', 'Faol obunalar',    $stat['obuna_faol'],     'hozir faol',                         'yellow'],
        ['📝', 'Testlar',          $stat['test_jami'],      '+' . $stat['test_24h']              . ' bugun', 'indigo'],
    ];
    foreach ($cards as $i => [$icon, $nom, $qiymat, $qoshimcha, $rang]):
    ?>
    <div class="glass-card p-5 fade-up" style="animation-delay:<?= $i * .05 ?>s">
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl"><?= $icon ?></span>
            <span class="badge badge-<?= $rang ?> text-xs">Pro</span>
        </div>
        <div class="text-2xl font-display font-bold tabnum mb-1"><?= $qiymat ?></div>
        <div class="text-xs text-brand-muted uppercase tracking-wide"><?= e($nom) ?></div>
        <div class="text-xs text-green-400 mt-1"><?= $qoshimcha ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Ikkinchi qator -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="glass-card p-5 fade-up">
        <div class="text-2xl mb-2">📅</div>
        <div class="text-xl font-display font-bold tabnum"><?= pul($stat['tolov_oy']) ?></div>
        <div class="text-xs text-brand-muted uppercase tracking-wide mt-1">Bu oy daromad</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-2xl mb-2">🎫</div>
        <div class="text-xl font-display font-bold tabnum"><?= $stat['bilet_soni'] ?></div>
        <div class="text-xs text-brand-muted uppercase tracking-wide mt-1">Faol biletlar</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-2xl mb-2">❓</div>
        <div class="text-xl font-display font-bold tabnum"><?= $stat['savol_soni'] ?></div>
        <div class="text-xs text-brand-muted uppercase tracking-wide mt-1">Jami savollar</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-2xl mb-2">⏳</div>
        <div class="text-xl font-display font-bold text-yellow-400 tabnum"><?= $kutmoqda_tolov ?></div>
        <div class="text-xs text-brand-muted uppercase tracking-wide mt-1">Kutilmoqda</div>
        <?php if ($kutmoqda_tolov): ?>
        <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
           class="text-xs text-blue-400 hover:underline mt-1 inline-block">Ko'rish →</a>
        <?php endif; ?>
    </div>
</div>

<!-- Grafik + oxirgi to'lovlar -->
<div class="grid lg:grid-cols-3 gap-5 mb-6">
    <!-- Daromad grafigi -->
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display text-base mb-4">📈 7 kunlik daromad</h2>
        <div class="space-y-2">
            <?php
            // To'liq 7 kun
            $grafik_map = array_column($daromad_grafik, null, 'sana');
            for ($i = 6; $i >= 0; $i--):
                $sana = date('Y-m-d', strtotime("-{$i} days"));
                $g    = $grafik_map[$sana] ?? ['summa' => 0, 'son' => 0];
                $w    = $g['summa'] > 0 ? round((float)$g['summa'] / $grafik_maks * 100) : 0;
            ?>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-12 text-brand-muted tabnum"><?= date('d.m', strtotime($sana)) ?></span>
                    <div class="flex-1 h-6 bg-white/[0.04] rounded-lg overflow-hidden">
                        <?php if ($w > 0): ?>
                            <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-500 rounded-lg flex items-center justify-end px-2 transition-all duration-500"
                                 style="width:<?= $w ?>%">
                                <span class="text-xs font-semibold tabnum"><?= number_format((float)$g['summa'] / 1000, 0) ?>K</span>
                            </div>
                        <?php else: ?>
                            <div class="h-full flex items-center px-2">
                                <span class="text-white/20">—</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Oxirgi to'lovlar -->
    <div class="lg:col-span-2 glass-card p-5 fade-up">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-base">💳 Oxirgi to'lovlar</h2>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php" class="text-xs text-blue-400 hover:underline">Barchasi →</a>
        </div>
        <?php if (empty($oxirgi_tolov)): ?>
            <p class="text-center text-brand-muted py-8 text-sm"><?= e(t('malumot_yoq')) ?></p>
        <?php else: ?>
            <div class="overflow-x-auto -mx-5 px-5">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-brand-muted uppercase tracking-wide">
                            <th class="py-2 pr-3">Foydalanuvchi</th>
                            <th class="py-2 pr-3">Tarif</th>
                            <th class="py-2 pr-3">Summa</th>
                            <th class="py-2 pr-3">Holat</th>
                            <th class="py-2">Vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <?php foreach ($oxirgi_tolov as $t): ?>
                            <tr class="hover:bg-white/[0.03] transition">
                                <td class="py-2.5 pr-3">
                                    <p class="font-medium"><?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?></p>
                                    <p class="text-brand-muted font-mono"><?= e($t['telefon']) ?></p>
                                </td>
                                <td class="py-2.5 pr-3"><?= e($t['tarif_nomi']) ?></td>
                                <td class="py-2.5 pr-3 font-bold tabnum"><?= e(pul($t['summa'])) ?></td>
                                <td class="py-2.5 pr-3">
                                    <span class="badge <?= match($t['holat']) {
                                        'muvaffaqiyatli' => 'badge-green',
                                        'kutilmoqda'     => 'badge-yellow',
                                        default          => 'badge-red'
                                    } ?>"><?= e($t['holat']) ?></span>
                                </td>
                                <td class="py-2.5 text-brand-muted"><?= e(vaqt_oldin($t['yaratilgan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
