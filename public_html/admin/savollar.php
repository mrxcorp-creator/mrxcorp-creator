<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$bilet_id = (int)(olish('bilet') ?: post('bilet_id'));
$harakat  = post('harakat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/savollar.php?bilet='.$bilet_id); }

    if (in_array($harakat, ['yaratish','tahrirlash'], true)) {
        $id     = (int) post('id');
        $matn   = trim(post('matn'));
        $a = trim(post('variant_a')); $b = trim(post('variant_b'));
        $c = trim(post('variant_c')); $d = trim(post('variant_d'));
        $togri  = post('togri_javob');
        $izoh   = trim(post('izoh'));
        if (!$bilet_id || !$matn || !$a || !$b || !in_array($togri,['a','b','c','d'],true)) {
            flash_qoy('xato', t('kerakli_maydon'));
        } else {
            $rasm = $id ? db_qiymat('SELECT rasm FROM savollar WHERE id=?', [$id]) : null;
            if (!empty($_FILES['rasm']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['rasm'], 'savollar', 800);
                if ($yangi) { if ($rasm) @unlink(UPLOAD_PATH.'/'.$rasm); $rasm = $yangi; }
            }
            if (post('rasm_ochir') === '1' && $rasm) { @unlink(UPLOAD_PATH.'/'.$rasm); $rasm = null; }

            if ($id) {
                db_bajar('UPDATE savollar SET matn=?,rasm=?,variant_a=?,variant_b=?,variant_c=?,variant_d=?,togri_javob=?,izoh=? WHERE id=?',
                    [$matn,$rasm,$a,$b,$c?:null,$d?:null,$togri,$izoh?:null,$id]);
            } else {
                db_bajar('INSERT INTO savollar (bilet_id,matn,rasm,variant_a,variant_b,variant_c,variant_d,togri_javob,izoh) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$bilet_id,$matn,$rasm,$a,$b,$c?:null,$d?:null,$togri,$izoh?:null]);
            }
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/savollar.php?bilet='.$bilet_id);
    }

    if ($harakat === 'ochirish') {
        $s = db_qator('SELECT rasm FROM savollar WHERE id=?', [(int)post('id')]);
        if ($s && $s['rasm']) @unlink(UPLOAD_PATH.'/'.$s['rasm']);
        db_bajar('DELETE FROM savollar WHERE id=?', [(int)post('id')]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/savollar.php?bilet='.$bilet_id);
    }
}

$biletlar = db_barcha('SELECT id, raqam, nomi FROM biletlar ORDER BY raqam');
$bilet    = $bilet_id ? db_qator('SELECT * FROM biletlar WHERE id=?', [$bilet_id]) : null;
$tahrir   = olish('tahrir') ? db_qator('SELECT * FROM savollar WHERE id=?', [(int)olish('tahrir')]) : null;
$savollar = $bilet_id ? db_barcha('SELECT * FROM savollar WHERE bilet_id=? ORDER BY tartib, id', [$bilet_id]) : [];

$admin_sahifa    = 'savollar';
$sahifa_sarlavha = 'Savollar';
require_once __DIR__ . '/_layout.php';
?>

<form method="GET" class="b-card"
      style="padding:1rem; margin-bottom:1.25rem;
             display:flex; align-items:flex-end; gap:.75rem; flex-wrap:wrap;">
    <div style="flex:1; min-width:250px;">
        <label class="field-label">Bilet tanlang</label>
        <select name="bilet" onchange="this.form.submit()" class="field">
            <option value="">— bilet —</option>
            <?php foreach ($biletlar as $bl): ?>
            <option value="<?= (int)$bl['id'] ?>" <?= $bilet_id === (int)$bl['id'] ? 'selected' : '' ?>>
                №<?= (int)$bl['raqam'] ?> — <?= e($bl['nomi']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($bilet): ?>
    <div style="font-size:.875rem; color:#666; padding-bottom:.7rem;">
        <?= count($savollar) ?> ta savol
    </div>
    <?php endif; ?>
</form>

<?php if ($bilet): ?>

<form method="POST" enctype="multipart/form-data" class="b-card"
      style="padding:1.25rem; margin-bottom:1.25rem;"
      x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">
    <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">

    <div @click="open = !open" style="display:flex; justify-content:space-between; cursor:pointer;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1.05rem;">
            <?= $tahrir ? 'Savol tahrirlash' : '+ Yangi savol' ?>
            — №<?= (int)$bilet['raqam'] ?>
        </h2>
        <span x-text="open ? '−' : '+'" style="font-size:1.25rem;"></span>
    </div>

    <div x-show="open" x-transition style="margin-top:1.25rem; display:flex; flex-direction:column; gap:.85rem;">
        <div>
            <label class="field-label">Savol matni *</label>
            <textarea name="matn" required rows="3" class="field"><?= e($tahrir['matn'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="field-label">Rasm (ixtiyoriy)</label>
            <input type="file" name="rasm" accept="image/*" class="field">
            <?php if (!empty($tahrir['rasm'])): ?>
            <div style="margin-top:.5rem; display:flex; align-items:center; gap:.85rem;">
                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($tahrir['rasm']) ?>"
                     style="height:60px; border:1px solid #000;">
                <label style="display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:#000;">
                    <input type="checkbox" name="rasm_ochir" value="1">
                    Rasmni o'chirish
                </label>
            </div>
            <?php endif; ?>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
            <?php foreach (['a','b','c','d'] as $v):
                $req = in_array($v, ['a','b'], true);
            ?>
            <div>
                <label class="field-label" style="text-transform:uppercase;">
                    <?= $v ?>) Variant <?= $req ? '*' : '(ixtiyoriy)' ?>
                </label>
                <textarea name="variant_<?= $v ?>" rows="2" class="field"
                          <?= $req ? 'required' : '' ?>><?= e($tahrir['variant_'.$v] ?? '') ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
            <div>
                <label class="field-label">To'g'ri javob *</label>
                <select name="togri_javob" required class="field">
                    <option value="">—</option>
                    <?php foreach (['a','b','c','d'] as $v): ?>
                    <option value="<?= $v ?>" <?= ($tahrir['togri_javob']??'')===$v?'selected':'' ?>>
                        <?= strtoupper($v) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label">Tartib raqami</label>
                <input type="number" name="tartib" value="<?= (int)($tahrir['tartib'] ?? 0) ?>" min="0" class="field">
            </div>
        </div>

        <div>
            <label class="field-label">Izoh (test tugagandan keyin ko'rinadi)</label>
            <textarea name="izoh" rows="2" class="field"><?= e($tahrir['izoh'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; gap:.5rem;">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
            <a href="?bilet=<?= (int)$bilet_id ?>" class="btn"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Savollar ro'yxati -->
<div class="b-card">
    <div style="padding:.85rem 1.25rem; border-bottom:1px solid #000;">
        <strong>Savollar (<?= count($savollar) ?>)</strong>
    </div>
    <?php if (empty($savollar)): ?>
    <div style="padding:3rem; text-align:center; color:#666;">Hali savol qo'shilmagan</div>
    <?php else: ?>
    <?php foreach ($savollar as $i => $s): ?>
    <details style="border-bottom:1px solid #E5E5E5;">
        <summary style="display:flex; align-items:center; gap:.85rem;
                        padding:.85rem 1.25rem; cursor:pointer; user-select:none;
                        transition: background-color .15s;"
                 onmouseover="this.style.background='#F5F5F5'"
                 onmouseout="this.style.background='transparent'">
            <span style="display:inline-flex; align-items:center; justify-content:center;
                         width:28px; height:28px; border:1px solid #000;
                         font-family:Georgia,serif; font-weight:700;
                         font-size:.8rem; flex-shrink:0;" class="tabnum">
                <?= $i + 1 ?>
            </span>
            <span style="flex:1; font-size:.875rem; overflow:hidden;
                         text-overflow:ellipsis; white-space:nowrap;">
                <?= e(mb_substr($s['matn'], 0, 100)) ?>
            </span>
            <span class="badge badge-filled" style="text-transform:uppercase;">
                <?= e($s['togri_javob']) ?>
            </span>
        </summary>
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #E5E5E5; background: #FAFAFA;">
            <p style="margin-bottom:.85rem; font-size:.9rem; line-height:1.55;">
                <?= e($s['matn']) ?>
            </p>
            <?php if ($s['rasm']): ?>
            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>"
                 style="max-width:18rem; border:1px solid #000; margin-bottom:.85rem;" loading="lazy">
            <?php endif; ?>
            <div style="display:grid; grid-template-columns:1fr; gap:.4rem; font-size:.85rem;"
                 class="sm:grid-cols-2">
                <?php foreach (['a','b','c','d'] as $v):
                    if (empty($s['variant_'.$v])) continue;
                    $togri = $s['togri_javob'] === $v;
                ?>
                <div style="padding:.5rem .75rem; border:1px solid #000;
                            <?= $togri ? 'background:#000; color:#fff;' : '' ?>">
                    <strong style="text-transform:uppercase;"><?= $v ?>)</strong>
                    <?= e($s['variant_'.$v]) ?>
                    <?php if ($togri): ?> ✓<?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($s['izoh'])): ?>
            <div style="margin-top:.85rem; padding:.65rem .85rem; background:#fff;
                        border:1px solid #000; font-size:.82rem;">
                💡 <?= e($s['izoh']) ?>
            </div>
            <?php endif; ?>
            <div style="display:flex; gap:.5rem; margin-top: 1rem; padding-top:.75rem;
                        border-top:1px solid #E5E5E5;">
                <a href="?bilet=<?= (int)$bilet_id ?>&tahrir=<?= (int)$s['id'] ?>" class="btn btn-xs">
                    Tahrirlash
                </a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('O\'chirilsinmi?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="ochirish">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">
                    <button class="btn btn-xs btn-danger">O'chirish</button>
                </form>
            </div>
        </div>
    </details>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="b-card" style="padding:3rem; text-align:center; color:#666;">
    Avval yuqoridan biletni tanlang.
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
