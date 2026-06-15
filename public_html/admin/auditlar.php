<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$qidiruv = olish('q');
$harakat_filt = olish('harakat');

$shartlar = [];
$params = [];

if ($qidiruv) {
    $shartlar[] = '(a.harakat LIKE ? OR a.ip LIKE ? OR fo.ism LIKE ? OR fo.telefon LIKE ?)';
    $params = ["%$qidiruv%", "%$qidiruv%", "%$qidiruv%", "%$qidiruv%"];
}
if ($harakat_filt) {
    $shartlar[] = 'a.harakat = ?';
    $params[] = $harakat_filt;
}
$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

try {
    $jami = (int) db_qiymat("SELECT COUNT(*) FROM auditlar a LEFT JOIN foydalanuvchilar fo ON a.foydalanuvchi_id = fo.id $where", $params);
} catch (Throwable $e) {
    $jami = 0;
}
$sahifa = max(1, (int) olish('p'));
$limit = 50;
$offset = ($sahifa - 1) * $limit;

try {
    $royxat = db_barcha(
        "SELECT a.*, fo.ism, fo.familiya, fo.telefon, fo.rol
         FROM auditlar a
         LEFT JOIN foydalanuvchilar fo ON a.foydalanuvchi_id = fo.id
         $where
         ORDER BY a.id DESC LIMIT $limit OFFSET $offset",
        $params
    );
} catch (Throwable $e) {
    $royxat = [];
}

try {
    $harakatlar = db_barcha('SELECT DISTINCT harakat FROM auditlar ORDER BY harakat');
} catch (Throwable $e) {
    $harakatlar = [];
}

$admin_sahifa = 'auditlar';
$sahifa_sarlavha = 'Audit log';
require_once __DIR__ . '/_layout.php';
?>

<form method="GET" class="grid sm:grid-cols-3 gap-3 mb-4">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Harakat, IP, foydalanuvchi..." class="field sm:col-span-2">
    <select name="harakat" onchange="this.form.submit()" class="field">
        <option value="">Barcha harakatlar</option>
        <?php foreach ($harakatlar as $h): ?>
            <option value="<?= e($h['harakat']) ?>" <?= $harakat_filt === $h['harakat'] ? 'selected' : '' ?>>
                <?= e($h['harakat']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="glass p-5 fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-bold text-lg">Jami: <span class="grad-text"><?= $jami ?></span></h2>
        <span class="text-xs text-muted">Eski yozuvlar 90 kundan keyin avtomatik o'chiriladi</span>
    </div>

    <?php if (empty($royxat)): ?>
        <p class="text-center py-8 text-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
                        <th class="py-2 pr-3">#</th>
                        <th class="py-2 pr-3">Foydalanuvchi</th>
                        <th class="py-2 pr-3">Harakat</th>
                        <th class="py-2 pr-3">Obyekt</th>
                        <th class="py-2 pr-3">IP</th>
                        <th class="py-2 pr-3">Tafsilot</th>
                        <th class="py-2">Vaqt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($royxat as $r):
                        $tafsilot = $r['tafsilot'] ? json_decode($r['tafsilot'], true) : null;
                    ?>
                        <tr class="hover:bg-white/3 align-top">
                            <td class="py-2.5 pr-3 font-mono text-xs text-muted"><?= (int)$r['id'] ?></td>
                            <td class="py-2.5 pr-3">
                                <?php if ($r['ism']): ?>
                                    <div class="font-medium"><?= e($r['ism']) ?> <?= e($r['familiya'] ?? '') ?></div>
                                    <div class="text-xs text-muted"><?= e($r['telefon']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted text-xs">Noma'lum</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2 chip-grad"><?= e($r['harakat']) ?></span>
                            </td>
                            <td class="py-2.5 pr-3 text-xs">
                                <?php if ($r['obyekt_turi']): ?>
                                    <span class="text-muted"><?= e($r['obyekt_turi']) ?>:</span>
                                    <strong>#<?= (int)$r['obyekt_id'] ?></strong>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-3 font-mono text-xs"><?= e($r['ip']) ?></td>
                            <td class="py-2.5 pr-3 text-xs max-w-xs">
                                <?php if ($tafsilot): ?>
                                    <details>
                                        <summary class="cursor-pointer text-muted hover:text-text">Ko'rish</summary>
                                        <pre class="text-xs mt-2 p-2 bg-black/30 rounded overflow-auto max-w-xs"><?= e(json_encode($tafsilot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                    </details>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 text-muted text-xs whitespace-nowrap"><?= e(vaqt_oldin($r['yaratilgan'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php $jami_sahifa = (int) ceil($jami / $limit); if ($jami_sahifa > 1): ?>
            <div class="flex justify-center gap-1 mt-4 flex-wrap">
                <?php for ($i = 1; $i <= min($jami_sahifa, 10); $i++): ?>
                    <a href="?p=<?= $i ?>&q=<?= e($qidiruv) ?>&harakat=<?= e($harakat_filt) ?>"
                       class="px-3 py-1.5 rounded-lg text-sm <?= $i === $sahifa ? 'grad-bg text-white font-bold' : 'bg-white/5 hover:bg-white/10' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <?php if ($jami_sahifa > 10): ?>
                    <span class="px-3 py-1.5 text-muted">...</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
