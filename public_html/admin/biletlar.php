<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

$harakat = post('harakat') ?: olish('harakat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }

    if (in_array($harakat, ['yaratish', 'tahrirlash'], true)) {
        $id = (int) post('id');
        $raqam = (int) post('raqam');
        $nomi = post('nomi');
        $tavsif = post('tavsif');
        $tur = post('tur') === 'bepul' ? 'bepul' : 'pullik';
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$raqam || !$nomi) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar(
                'UPDATE biletlar SET raqam = ?, nomi = ?, tavsif = ?, tur = ?, holat = ? WHERE id = ?',
                [$raqam, $nomi, $tavsif, $tur, $holat, $id]
            );
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            db_bajar(
                'INSERT INTO biletlar (raqam, nomi, tavsif, tur, holat) VALUES (?, ?, ?, ?, ?)',
                [$raqam, $nomi, $tavsif, $tur, $holat]
            );
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }

    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM biletlar WHERE id = ?', [(int) post('id')]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }
}

$tahrir = null;
if (olish('tahrir')) {
    $tahrir = db_qator('SELECT * FROM biletlar WHERE id = ?', [(int) olish('tahrir')]);
}

$qidiruv = olish('q');
$shart = '';
$params = [];
if ($qidiruv) {
    $shart = ' WHERE nomi LIKE ? OR raqam = ?';
    $params = ['%' . $qidiruv . '%', is_numeric($qidiruv) ? (int) $qidiruv : 0];
}
$biletlar = db_barcha(
    "SELECT b.*, (SELECT COUNT(*) FROM savollar WHERE bilet_id = b.id) AS savol_son
     FROM biletlar b $shart ORDER BY b.raqam",
    $params
);

$admin_sahifa = 'biletlar';
$sahifa_sarlavha = 'Biletlar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="glass p-5 mb-6 fade-up" x-data="{open: <?= $tahrir ? 'true' : 'false' ?>}">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int) ($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h2 class="font-display font-bold text-lg">
            <?= $tahrir ? '✏️ Bilet tahrirlash' : '➕ Yangi bilet qo\'shish' ?>
        </h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="mt-4 grid sm:grid-cols-2 gap-4">
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
                <option value="bepul" <?= ($tahrir['tur'] ?? '') === 'bepul' ? 'selected' : '' ?>>Bepul</option>
                <option value="pullik" <?= ($tahrir['tur'] ?? 'pullik') === 'pullik' ? 'selected' : '' ?>>Pullik</option>
            </select>
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol" <?= ($tahrir['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Tavsif</label>
            <textarea name="tavsif" rows="2" class="field"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="<?= e(SAYT_URL) ?>/admin/biletlar.php" class="btn btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<form method="GET" class="mb-4 max-w-md">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="<?= e(t('qidirish')) ?>..." class="field">
</form>

<div class="glass p-5 fade-up">
    <?php if (empty($biletlar)): ?>
        <p class="text-center py-8 text-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
                        <th class="py-2 pr-3">№</th>
                        <th class="py-2 pr-3">Nomi</th>
                        <th class="py-2 pr-3">Tur</th>
                        <th class="py-2 pr-3">Savollar</th>
                        <th class="py-2 pr-3">Holat</th>
                        <th class="py-2 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($biletlar as $b): ?>
                        <tr class="hover:bg-white/3">
                            <td class="py-2.5 pr-3 font-mono font-bold grad-text"><?= (int)$b['raqam'] ?></td>
                            <td class="py-2.5 pr-3"><?= e($b['nomi']) ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2 <?= $b['tur'] === 'bepul' ? 'bg-success/15 text-success border-success/30' : 'bg-amber/15 text-amber border-amber/30' ?>">
                                    <?= e($b['tur']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 font-bold"><?= (int)$b['savol_son'] ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2 <?= $b['holat'] === 'faol' ? 'bg-success/15 text-success border-success/30' : 'bg-danger/15 text-danger border-danger/30' ?>">
                                    <?= e($b['holat']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-right whitespace-nowrap">
                                <a href="<?= e(SAYT_URL) ?>/admin/savollar.php?bilet=<?= (int)$b['id'] ?>" class="text-cyan hover:underline mr-2 text-xs">Savollar</a>
                                <a href="?tahrir=<?= (int)$b['id'] ?>" class="text-amber hover:underline mr-2 text-xs"><?= e(t('tahrirlash')) ?></a>
                                <form method="POST" class="inline" onsubmit="return confirm('Rostdan ham o\'chirilsinmi? Barcha savollar ham o\'chiriladi!')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochirish">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="text-danger hover:underline text-xs"><?= e(t('ochirish')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
