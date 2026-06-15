<?php
/**
 * VatanParvar Yaypan — Biletlar CRUD
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL . '/admin/biletlar.php'); }

    $harakat = post('harakat');
    $id      = (int) post('id');

    if (in_array($harakat, ['yaratish','tahrirlash'], true)) {
        $raqam  = (int) post('raqam');
        $nomi   = trim(post('nomi'));
        $tavsif = trim(post('tavsif'));
        $tur    = post('tur') === 'bepul' ? 'bepul' : 'pullik';
        $holat  = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$raqam || !$nomi) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar('UPDATE biletlar SET raqam=?,nomi=?,tavsif=?,tur=?,holat=? WHERE id=?',
                     [$raqam,$nomi,$tavsif,$tur,$holat,$id]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            db_bajar('INSERT INTO biletlar (raqam,nomi,tavsif,tur,holat) VALUES (?,?,?,?,?)',
                     [$raqam,$nomi,$tavsif,$tur,$holat]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }

    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM biletlar WHERE id=?', [$id]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }
}

$tahrir  = olish('tahrir') ? db_qator('SELECT * FROM biletlar WHERE id=?', [(int) olish('tahrir')]) : null;
$qidiruv = olish('q');
$params  = [];
$where   = '';
if ($qidiruv) {
    $where   = ' WHERE b.nomi LIKE ? OR b.raqam = ?';
    $params  = ['%'.$qidiruv.'%', is_numeric($qidiruv) ? (int)$qidiruv : 0];
}

$biletlar = db_barcha(
    "SELECT b.*, (SELECT COUNT(*) FROM savollar WHERE bilet_id=b.id) AS savol_son
     FROM biletlar b {$where} ORDER BY b.raqam",
    $params
);

$admin_sahifa    = 'biletlar';
$sahifa_sarlavha = 'Biletlar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Forma -->
<form method="POST" class="glass-card p-5 mb-6 fade-up" x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer select-none" @click="open = !open">
        <h2 class="font-display text-base">
            <?= $tahrir ? '✏️ Bilet tahrirlash' : '➕ Yangi bilet qo\'shish' ?>
        </h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="mt-5 grid sm:grid-cols-2 gap-4">
        <div>
            <label class="field-label">Raqam *</label>
            <input type="number" name="raqam" required min="1" value="<?= e($tahrir['raqam'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Nomi *</label>
            <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Turi</label>
            <select name="tur" class="field">
                <option value="pullik" <?= ($tahrir['tur'] ?? 'pullik') === 'pullik' ? 'selected' : '' ?>>Pullik (PRO)</option>
                <option value="bepul"  <?= ($tahrir['tur'] ?? '') === 'bepul'        ? 'selected' : '' ?>>Bepul (Demo)</option>
            </select>
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol"   <?= ($tahrir['holat'] ?? 'faol') === 'faol'   ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol'     ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Tavsif</label>
            <textarea name="tavsif" rows="2" class="field"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/biletlar.php" class="btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Qidiruv -->
<form method="GET" class="mb-4 max-w-xs">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="<?= e(t('qidirish')) ?>..." class="field">
</form>

<!-- Biletlar jadvali -->
<div class="glass-card p-5 fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display text-base">Jami: <?= count($biletlar) ?></h2>
    </div>
    <?php if (empty($biletlar)): ?>
        <p class="text-center py-10 text-brand-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase tracking-wide">
                        <th class="py-2.5 pr-3">№</th>
                        <th class="py-2.5 pr-3">Nomi</th>
                        <th class="py-2.5 pr-3">Tur</th>
                        <th class="py-2.5 pr-3">Savollar</th>
                        <th class="py-2.5 pr-3">Holat</th>
                        <th class="py-2.5 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05]">
                    <?php foreach ($biletlar as $b): ?>
                        <tr class="hover:bg-white/[0.03] transition">
                            <td class="py-3 pr-3 font-mono font-bold tabnum"><?= (int)$b['raqam'] ?></td>
                            <td class="py-3 pr-3 font-medium"><?= e($b['nomi']) ?></td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= $b['tur'] === 'bepul' ? 'badge-green' : 'badge-yellow' ?>">
                                    <?= e($b['tur']) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-3 font-bold tabnum"><?= (int)$b['savol_son'] ?></td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= $b['holat'] === 'faol' ? 'badge-green' : 'badge-red' ?>">
                                    <?= e($b['holat']) ?>
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="<?= e(SAYT_URL) ?>/admin/savollar.php?bilet=<?= (int)$b['id'] ?>"
                                       class="badge badge-blue cursor-pointer hover:opacity-80">Savollar</a>
                                    <a href="?tahrir=<?= (int)$b['id'] ?>"
                                       class="badge badge-yellow cursor-pointer hover:opacity-80"><?= e(t('tahrirlash')) ?></a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Bilet va barcha savollar o\'chirilsinmi?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="ochirish">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="badge badge-red cursor-pointer hover:opacity-80"><?= e(t('ochirish')) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
