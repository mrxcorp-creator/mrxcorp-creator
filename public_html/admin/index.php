<?php
/**
 * VatanParvar Yaypan — Admin dashboard
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

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

$daromad_raw = db_barcha(
    'SELECT DATE(yaratilgan) AS sana, COALESCE(SUM(summa),0) AS summa
     FROM tolovlar WHERE holat = "muvaffaqiyatli"
       AND yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(yaratilgan)'
);
$daromad_map  = array_column($daromad_raw, 'summa', 'sana');
$daromad_maks = max(array_values($daromad_map) ?: [1]) ?: 1;
?>

<!-- Asosiy statistika -->
<div style="display:grid; grid-template-columns: 1fr; gap:0;
            border:1px solid #000; margin-bottom: 1rem;"
     class="grid-cols-2 lg:grid-cols-4">
    <?php
    $cards = [
        ['Foydalanuvchilar', $stat['foydalanuvchi'], '+'.$stat['foydalanuvchi_24h'].' bugun'],
        ['Daromad',          pul($stat['tolov_jami']), '+'.pul($stat['tolov_24h']).' bugun'],
        ['Faol obunalar',    $stat['obuna_faol'],     'hozir aktiv'],
        ['Testlar',          $stat['test_jami'],      '+'.$stat['test_24h'].' bugun'],
    ];
    foreach ($cards as $i => [$nom, $val, $sub]):
        $border = '';
        if ($i % 2 !== 0) $border .= 'border-left: 1px solid #E5E5E5;';
        if ($i >= 2) $border .= 'border-top: 1px solid #E5E5E5;';
    ?>
    <div style="padding:1.25rem; <?= $border ?>"
         class="lg:border-l lg:border-l-[#E5E5E5] lg:[&:first-child]:border-l-0 lg:border-t-0">
        <div style="font-size:.72rem; color:#666; text-transform:uppercase;
                    letter-spacing:.08em; margin-bottom:.5rem;">
            <?= e($nom) ?>
        </div>
        <div style="font-family:Georgia,serif; font-weight:700;
                    font-size: 1.6rem; line-height:1; margin-bottom:.4rem;" class="tabnum">
            <?= e($val) ?>
        </div>
        <div style="font-size:.72rem; color:#000;"><?= e($sub) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- 2-qator -->
<div style="display:grid; grid-template-columns: 1fr; gap:0;
            border:1px solid #000; margin-bottom: 1.5rem;"
     class="grid-cols-2 sm:grid-cols-4">
    <?php
    $cards2 = [
        ['Bu oy',      pul($stat['tolov_oy'])],
        ['Biletlar',   $stat['bilet_soni']],
        ['Savollar',   $stat['savol_soni']],
        ['Kutilmoqda', $kutmoqda_tolov],
    ];
    foreach ($cards2 as $i => [$nom, $val]):
        $border = $i > 0 ? 'border-left: 1px solid #E5E5E5;' : '';
    ?>
    <div style="padding:1rem; <?= $border ?>">
        <div style="font-size:.7rem; color:#666; text-transform:uppercase;
                    letter-spacing:.08em; margin-bottom:.4rem;">
            <?= e($nom) ?>
        </div>
        <div style="font-family:Georgia,serif; font-weight:700;
                    font-size: 1.25rem;" class="tabnum">
            <?= e($val) ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Grafik + Oxirgi to'lovlar -->
<div style="display:grid; grid-template-columns: 1fr; gap: 1rem;"
     class="lg:grid-cols-3">

    <!-- Grafik -->
    <div class="b-card" style="padding: 1.25rem;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:.9rem;
                   margin-bottom: 1rem; padding-bottom: .65rem;
                   border-bottom: 1px solid #000;
                   text-transform:uppercase; letter-spacing:.05em;">
            7 kunlik daromad
        </h2>
        <?php for ($i = 6; $i >= 0; $i--):
            $sana = date('Y-m-d', strtotime("-{$i} days"));
            $sum  = (float)($daromad_map[$sana] ?? 0);
            $w    = $sum > 0 ? max(8, round($sum / $daromad_maks * 100)) : 0;
        ?>
        <div style="display:flex; align-items:center; gap:.5rem;
                    margin-bottom:.4rem; font-size:.78rem;">
            <span style="width:42px; color:#666;" class="tabnum">
                <?= date('d.m', strtotime($sana)) ?>
            </span>
            <div style="flex:1; height:18px; border:1px solid #E5E5E5; background:#FAFAFA;">
                <?php if ($w > 0): ?>
                <div style="height:100%; background:#000; width:<?= $w ?>%;
                            display:flex; align-items:center; justify-content:flex-end;
                            padding-right:.4rem; color:#fff; font-size:.7rem;
                            font-family:Georgia,serif; font-weight:700;">
                    <?= number_format($sum/1000, 0) ?>K
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Oxirgi to'lovlar -->
    <div class="b-card" style="padding: 1.25rem;" class="lg:col-span-2">
        <div style="display:flex; align-items:center; justify-content:space-between;
                    padding-bottom:.65rem; border-bottom: 1px solid #000;
                    margin-bottom: 1rem;">
            <h2 style="font-family:Georgia,serif; font-weight:700; font-size:.9rem;
                       text-transform:uppercase; letter-spacing:.05em;">
                Oxirgi to'lovlar
            </h2>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php"
               style="font-size:.78rem; color:#000;
                      text-decoration:underline; text-underline-offset:3px;">
                Barchasi →
            </a>
        </div>

        <?php if (empty($oxirgi_tolov)): ?>
        <p style="text-align:center; padding: 2rem 0; color:#666; font-size:.85rem;">
            <?= e(t('malumot_yoq')) ?>
        </p>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="b-table" style="font-size:.82rem;">
                <thead>
                    <tr>
                        <th>Foydalanuvchi</th>
                        <th>Tarif</th>
                        <th>Summa</th>
                        <th>Holat</th>
                        <th>Vaqt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($oxirgi_tolov as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['ism']) ?> <?= e($row['familiya'] ?? '') ?></strong>
                            <div style="font-size:.7rem; color:#666; font-family:monospace;">
                                <?= e($row['telefon']) ?>
                            </div>
                        </td>
                        <td><?= e($row['tarif_nomi']) ?></td>
                        <td style="font-weight:600;" class="tabnum"><?= e(pul($row['summa'])) ?></td>
                        <td>
                            <span class="badge <?= $row['holat'] === 'muvaffaqiyatli' ? 'badge-filled' : 'badge-light' ?>">
                                <?= e($row['holat']) ?>
                            </span>
                        </td>
                        <td style="color:#666;"><?= e(vaqt_oldin($row['yaratilgan'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
