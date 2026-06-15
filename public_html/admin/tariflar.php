<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/tariflar.php'); }
    $harakat = post('harakat');
    $id      = (int) post('id');

    if (in_array($harakat, ['yaratish','tahrirlash'], true)) {
        $nomi    = trim(post('nomi'));
        $tavsif  = trim(post('tavsif'));
        $tur     = in_array(post('tur'),['kun','oy','bilet'],true) ? post('tur') : 'oy';
        $qiymat  = max(1, (int) post('qiymat'));
        $narx    = (float) post('narx');
        $eski    = (float) post('eski_narx') ?: null;
        $mashhur = post('mashhur') === '1' ? 1 : 0;
        $tartib  = (int) post('tartib');
        $holat   = post('holat') === 'nofaol' ? 'nofaol' : 'faol';
        if (!$nomi || $narx <= 0) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar('UPDATE tariflar SET nomi=?,tavsif=?,tur=?,qiymat=?,narx=?,eski_narx=?,mashhur=?,tartib=?,holat=? WHERE id=?',
                     [$nomi,$tavsif,$tur,$qiymat,$narx,$eski,$mashhur,$tartib,$holat,$id]);
        } else {
            db_bajar('INSERT INTO tariflar (nomi,tavsif,tur,qiymat,narx,eski_narx,mashhur,tartib,holat) VALUES (?,?,?,?,?,?,?,?,?)',
                     [$nomi,$tavsif,$tur,$qiymat,$narx,$eski,$mashhur,$tartib,$holat]);
        }
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        kesh_tozala();
        yonaltir(SAYT_URL . '/admin/tariflar.php');
    }
    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM tariflar WHERE id=?', [$id]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        kesh_tozala();
        yonaltir(SAYT_URL . '/admin/tariflar.php');
    }
}

$tahrir   = olish('tahrir') ? db_qator('SELECT * FROM tariflar WHERE id=?', [(int)olish('tahrir')]) : null;
$tariflar = db_barcha('SELECT * FROM tariflar ORDER BY tartib, narx');

$admin_sahifa    = 'tariflar';
$sahifa_sarlavha = 'Tariflar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="b-card"
      style="padding:1.25rem; margin-bottom:1.25rem;"
      x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div @click="open = !open" style="display:flex; justify-content:space-between; cursor:pointer;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1.05rem;">
            <?= $tahrir ? 'Tarif tahrirlash' : '+ Yangi tarif' ?>
        </h2>
        <span x-text="open ? '−' : '+'" style="font-size:1.25rem;"></span>
    </div>

    <div x-show="open" x-transition style="margin-top:1.25rem;
            display:grid; grid-template-columns:1fr; gap:.75rem;"
         class="md:grid-cols-2">
        <div>
            <label class="field-label">Nomi *</label>
            <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Narxi (so'm) *</label>
            <input type="number" name="narx" required min="0" step="100"
                   value="<?= e($tahrir['narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Eski narx (chizilgan)</label>
            <input type="number" name="eski_narx" min="0" step="100"
                   value="<?= e($tahrir['eski_narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tur</label>
            <select name="tur" class="field">
                <?php foreach (['kun'=>'Kunlik','oy'=>'Oylik','bilet'=>'Bilet'] as $v=>$n): ?>
                <option value="<?= $v ?>" <?= ($tahrir['tur']??'oy')===$v?'selected':'' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Qiymat (necha)</label>
            <input type="number" name="qiymat" min="1" value="<?= e($tahrir['qiymat'] ?? 1) ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tartib</label>
            <input type="number" name="tartib" value="<?= e($tahrir['tartib'] ?? 0) ?>" class="field">
        </div>
        <div class="md:col-span-2">
            <label class="field-label">Tavsif</label>
            <textarea name="tavsif" rows="2" class="field"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="md:col-span-2" style="display:flex; align-items:center; gap:1rem;">
            <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                <input type="checkbox" name="mashhur" value="1" <?= !empty($tahrir['mashhur']) ? 'checked' : '' ?>>
                <span style="font-size:.875rem;">Tavsiya etiladi</span>
            </label>
            <select name="holat" class="field" style="max-width:140px;">
                <option value="faol"   <?= ($tahrir['holat']??'faol')==='faol' ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat']??'')==='nofaol' ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="md:col-span-2" style="display:flex; gap:.5rem;">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?><a href="?" class="btn"><?= e(t('bekor_qilish')) ?></a><?php endif; ?>
        </div>
    </div>
</form>

<!-- Tariflar grid -->
<div style="display:grid; grid-template-columns: 1fr; gap:1rem;"
     class="md:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($tariflar as $t): ?>
    <div class="b-card" style="padding:1.25rem; <?= $t['holat']==='nofaol' ? 'opacity:.6;' : '' ?>">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;
                    margin-bottom:.75rem;">
            <h3 style="font-family:Georgia,serif; font-weight:700;"><?= e($t['nomi']) ?></h3>
            <?php if ($t['mashhur']): ?>
            <span class="badge badge-filled">⭐</span>
            <?php endif; ?>
        </div>
        <?php if ($t['eski_narx'] && (float)$t['eski_narx'] > (float)$t['narx']): ?>
        <div style="font-size:.78rem; text-decoration:line-through; color:#999;">
            <?= e(pul($t['eski_narx'])) ?>
        </div>
        <?php endif; ?>
        <div style="font-family:Georgia,serif; font-weight:700; font-size: 1.85rem;
                    line-height:1; margin-bottom:.5rem;" class="tabnum">
            <?= number_format((float)$t['narx'], 0, '.', ' ') ?>
        </div>
        <div style="font-size:.78rem; color:#666; margin-bottom:1rem;">so'm</div>
        <p style="font-size:.78rem; color:#555; min-height:2.5rem;" class="line-clamp-2">
            <?= e($t['tavsif']) ?>
        </p>
        <div style="display:flex; justify-content:space-between; align-items:center;
                    margin-top:1rem; padding-top:.75rem; border-top:1px solid #E5E5E5;">
            <span class="badge <?= $t['holat']==='faol' ? '' : 'badge-filled' ?>">
                <?= e($t['holat']) ?>
            </span>
            <div style="display:flex; gap:.25rem;">
                <a href="?tahrir=<?= (int)$t['id'] ?>" class="btn btn-xs">Tahrir</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('O\'chirilsinmi?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="ochirish">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="btn btn-xs btn-danger">O'chirish</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
