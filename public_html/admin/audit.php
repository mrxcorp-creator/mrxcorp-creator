<?php
/**
 * Admin — Audit log (kim, qachon, nima qildi)
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

// Filter parametrlari
$qidiruv = trim(olish('q'));
$user_id_filt = (int) olish('user');
$amal_filt = trim(olish('amal'));
$kun = trim(olish('kun')); // bugun, hafta, oy

$shartlar = [];
$params = [];
if ($qidiruv) {
    $shartlar[] = '(al.tafsilot LIKE ? OR fo.ism LIKE ?)';
    $params[] = '%' . $qidiruv . '%';
    $params[] = '%' . $qidiruv . '%';
}
if ($user_id_filt) {
    $shartlar[] = 'al.foydalanuvchi_id = ?';
    $params[] = $user_id_filt;
}
if ($amal_filt) {
    $shartlar[] = 'al.amal = ?';
    $params[] = $amal_filt;
}
if ($kun === 'bugun') $shartlar[] = 'DATE(al.yaratilgan) = CURDATE()';
elseif ($kun === 'hafta') $shartlar[] = 'al.yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
elseif ($kun === 'oy') $shartlar[] = 'al.yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';

$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

$jami = (int) db_qiymat("SELECT COUNT(*) FROM audit_log al LEFT JOIN foydalanuvchilar fo ON al.foydalanuvchi_id = fo.id $where", $params);

$sahifa_no = max(1, (int) olish('p'));
$limit = 50;
$offset = ($sahifa_no - 1) * $limit;

$ruyhat = db_barcha(
    "SELECT al.*, fo.ism, fo.familiya, fo.rol
     FROM audit_log al
     LEFT JOIN foydalanuvchilar fo ON al.foydalanuvchi_id = fo.id
     $where
     ORDER BY al.yaratilgan DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// Statistika
$stat_amallar = db_barcha(
    'SELECT amal, COUNT(*) AS son FROM audit_log
     WHERE yaratilgan >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY amal ORDER BY son DESC LIMIT 10'
);

$admin_sahifa = 'audit';
$sahifa_sarlavha = 'Audit Log';
require_once __DIR__ . '/_layout.php';
?>

<div class="grid lg:grid-cols-4 gap-5 mb-6">
    <div class="glass-card p-4 lg:col-span-3">
        <h2 class="font-display font-bold text-brand-text mb-3">Faollik (oxirgi 7 kun)</h2>
        <?php if (empty($stat_amallar)): ?>
            <p class="text-brand-muted text-sm">Hech narsa yozilmagan</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php
                $maks = max(array_column($stat_amallar, 'son')) ?: 1;
                foreach ($stat_amallar as $s):
                    $w = round($s['son'] / $maks * 100);
                ?>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-32 text-brand-muted text-xs font-mono truncate"><?= e($s['amal']) ?></span>
                        <div class="flex-1 h-6 bg-sky-50 rounded-md overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-400 to-blue-500 rounded-md flex items-center justify-end pr-2 text-xs font-bold text-white" style="width:<?= $w ?>%">
                                <?= (int)$s['son'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card p-4 text-center">
        <div class="text-xs text-brand-muted uppercase mb-2">Jami yozuvlar</div>
        <div class="text-4xl font-display font-bold text-gradient"><?= $jami ?></div>
    </div>
</div>

<form method="GET" class="grid sm:grid-cols-4 gap-3 mb-4">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Qidiruv..." class="field text-sm sm:col-span-2">
    <select name="amal" onchange="this.form.submit()" class="field text-sm">
        <option value="">Barcha amallar</option>
        <?php foreach ($stat_amallar as $s): ?>
            <option value="<?= e($s['amal']) ?>" <?= $amal_filt === $s['amal'] ? 'selected' : '' ?>><?= e($s['amal']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="kun" onchange="this.form.submit()" class="field text-sm">
        <option value="">Barcha vaqt</option>
        <option value="bugun" <?= $kun === 'bugun' ? 'selected' : '' ?>>Bugun</option>
        <option value="hafta" <?= $kun === 'hafta' ? 'selected' : '' ?>>Hafta</option>
        <option value="oy" <?= $kun === 'oy' ? 'selected' : '' ?>>Oy</option>
    </select>
</form>

<div class="glass-card p-4 fade-up">
    <?php if (empty($ruyhat)): ?>
        <div class="py-12 text-center text-brand-muted">
            <div class="text-5xl mb-3">📜</div>
            Hozircha audit yozuvlari yo'q
        </div>
    <?php else: ?>
        <div class="overflow-x-auto -mx-4 px-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase">
                        <th class="py-2 pr-3">Vaqt</th>
                        <th class="py-2 pr-3">Foydalanuvchi</th>
                        <th class="py-2 pr-3">Amal</th>
                        <th class="py-2 pr-3">Obyekt</th>
                        <th class="py-2 pr-3">Tafsilot</th>
                        <th class="py-2">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border">
                    <?php foreach ($ruyhat as $r): ?>
                        <tr class="hover:bg-sky-50/30">
                            <td class="py-2 pr-3 text-xs text-brand-muted whitespace-nowrap"><?= e(date('d.m H:i', strtotime($r['yaratilgan']))) ?></td>
                            <td class="py-2 pr-3">
                                <?php if ($r['ism']): ?>
                                    <div class="font-semibold text-brand-text"><?= e($r['ism']) ?> <?= e($r['familiya'] ?? '') ?></div>
                                    <span class="text-[10px] text-brand-muted uppercase"><?= e($r['rol']) ?></span>
                                <?php else: ?>
                                    <span class="text-brand-muted text-xs">— Anonim —</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 pr-3"><span class="text-xs px-2 py-0.5 rounded bg-sky-100 text-sky-700 font-mono"><?= e($r['amal']) ?></span></td>
                            <td class="py-2 pr-3 text-xs">
                                <?php if ($r['obyekt']): ?>
                                    <span class="text-brand-muted"><?= e($r['obyekt']) ?></span>
                                    <?php if ($r['obyekt_id']): ?>
                                        <span class="text-brand-light">#<?= (int)$r['obyekt_id'] ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 pr-3 text-xs text-brand-body max-w-xs truncate" title="<?= e($r['tafsilot']) ?>"><?= e($r['tafsilot']) ?></td>
                            <td class="py-2 text-xs font-mono text-brand-muted"><?= e($r['ip']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php $jami_sahifa = (int) ceil($jami / $limit); if ($jami_sahifa > 1): ?>
            <div class="flex justify-center gap-1 mt-4">
                <?php for ($i = 1; $i <= $jami_sahifa; $i++):
                    if ($jami_sahifa > 7 && abs($i - $sahifa_no) > 2 && $i !== 1 && $i !== $jami_sahifa) {
                        if ($i === 2 || $i === $jami_sahifa - 1) echo '<span class="px-2 py-1.5 text-brand-light text-xs">…</span>';
                        continue;
                    }
                    $url_p = '?p=' . $i . ($qidiruv ? '&q=' . urlencode($qidiruv) : '') . ($amal_filt ? '&amal=' . urlencode($amal_filt) : '') . ($kun ? '&kun=' . urlencode($kun) : '');
                ?>
                    <a href="<?= $url_p ?>"
                       class="px-3 py-1.5 rounded text-xs font-semibold <?= $i === $sahifa_no ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white' : 'bg-white border border-brand-border hover:bg-sky-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
