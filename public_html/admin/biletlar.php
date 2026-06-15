<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/biletlar.php');
    }
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

$tahrir   = olish('tahrir') ? db_qator('SELECT * FROM biletlar WHERE id=?', [(int)olish('tahrir')]) : null;
$qidiruv  = olish('q');
$where    = ''; $params = [];
if ($qidiruv) {
    $where  = ' WHERE b.nomi LIKE ? OR b.raqam = ?';
    $params = ["%{$qidiruv}%", is_numeric($qidiruv) ? (int)$qidiruv : 0];
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
<form method="POST" class="b-card"
      style="padding:1.25rem; margin-bottom:1.25rem;"
      x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div @click="open = !open"
         style="display:flex; justify-content:space-between; align-items:center;
                cursor:pointer; user-select:none;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1.05rem;">
            <?= $tahrir ? '✏️ Bilet tahrirlash' : '+ Yangi bilet' ?>
        </h2>
        <span x-text="open ? '−' : '+'" style="font-size:1.25rem;"></span>
    </div>

    <div x-show="open" x-transition style="margin-top:1.25rem;
            display:grid; grid-template-columns: 1fr; gap:.75rem;"
         class="md:grid-cols-2">
        <div>
            <label class="field-label">Raqam *</label>
            <input type="number" name="raqam" required min="1"
                   value="<?= e($tahrir['raqam'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Nomi *</label>
            <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Turi</label>
            <select name="tur" class="field">
                <option value="pullik" <?= ($tahrir['tur']??'pullik')==='pullik' ? 'selected' : '' ?>>Pullik (PRO)</option>
                <option value="bepul"  <?= ($tahrir['tur']??'')==='bepul' ? 'selected' : '' ?>>Bepul</option>
            </select>
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol"   <?= ($tahrir['holat']??'faol')==='faol' ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat']??'')==='nofaol' ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="field-label">Tavsif</label>
            <textarea name="tavsif" rows="2" class="field"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="md:col-span-2" style="display:flex; gap:.5rem;">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/biletlar.php" class="btn"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Qidiruv -->
<form method="GET" style="max-width: 24rem; margin-bottom:1rem;">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Qidirish..." class="field">
</form>

<!-- Jadval -->
<div class="b-card">
    <div style="padding:.85rem 1.25rem; border-bottom:1px solid #000;">
        Jami: <strong class="tabnum"><?= count($biletlar) ?></strong>
    </div>

    <?php if (empty($biletlar)): ?>
    <div style="padding:3rem; text-align:center; color:#666;"><?= e(t('malumot_yoq')) ?></div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="b-table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Nomi</th>
                    <th>Tur</th>
                    <th>Savollar</th>
                    <th>Holat</th>
                    <th style="text-align:right;">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($biletlar as $b): ?>
                <tr>
                    <td style="font-family:Georgia,serif; font-weight:700;" class="tabnum">
                        <?= (int)$b['raqam'] ?>
                    </td>
                    <td><strong><?= e($b['nomi']) ?></strong></td>
                    <td>
                        <span class="badge <?= $b['tur']==='bepul' ? '' : 'badge-filled' ?>">
                            <?= e($b['tur']) ?>
                        </span>
                    </td>
                    <td class="tabnum"><strong><?= (int)$b['savol_son'] ?></strong></td>
                    <td>
                        <span class="badge <?= $b['holat']==='faol' ? '' : 'badge-filled' ?>">
                            <?= e($b['holat']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <a href="<?= e(SAYT_URL) ?>/admin/savollar.php?bilet=<?= (int)$b['id'] ?>"
                           class="btn btn-xs">Savollar</a>
                        <a href="?tahrir=<?= (int)$b['id'] ?>" class="btn btn-xs">Tahrir</a>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Bilet va savollar o\'chirilsinmi?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="ochirish">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button class="btn btn-xs btn-danger">O'chirish</button>
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
