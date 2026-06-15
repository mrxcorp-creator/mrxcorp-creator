<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/fikrlar.php'); }
    $harakat = post('harakat');
    $id      = (int) post('id');

    if ($harakat === 'tasdiq')   { db_bajar('UPDATE fikrlar SET tasdiq = 1 WHERE id = ?', [$id]); kesh_tozala(); flash_qoy('muvaffaqiyat','Tasdiqlandi'); }
    if ($harakat === 'bekor')    { db_bajar('UPDATE fikrlar SET tasdiq = 0 WHERE id = ?', [$id]); kesh_tozala(); flash_qoy('muvaffaqiyat',t('malumot_saqlandi')); }
    if ($harakat === 'ochirish') { db_bajar('DELETE FROM fikrlar WHERE id = ?', [$id]); kesh_tozala(); flash_qoy('muvaffaqiyat',t('malumot_saqlandi')); }
    if ($harakat === 'qoshish') {
        $ism  = trim(post('ism'));
        $matn = trim(post('matn'));
        $baho = max(1, min(5, (int) post('baho') ?: 5));
        if ($ism && mb_strlen($matn) >= 5) {
            db_bajar('INSERT INTO fikrlar (ism,matn,baho,tasdiq) VALUES (?,?,?,1)', [$ism,$matn,$baho]);
            kesh_tozala();
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
    }
    yonaltir(SAYT_URL . '/admin/fikrlar.php?holat=' . urlencode(olish('holat')));
}

$holat = olish('holat');
$shart = match($holat) {
    'kutilmoqda'   => ' WHERE tasdiq = 0',
    'tasdiqlangan' => ' WHERE tasdiq = 1',
    default        => ''
};
$royxat = db_barcha("SELECT * FROM fikrlar {$shart} ORDER BY yaratilgan DESC LIMIT 200");

$admin_sahifa    = 'fikrlar';
$sahifa_sarlavha = 'Fikrlar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Qo'shish formasi -->
<form method="POST" class="b-card"
      style="padding:1.25rem; margin-bottom:1.25rem;"
      x-data="{ open: false }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="qoshish">

    <div @click="open = !open" style="display:flex; justify-content:space-between; cursor:pointer;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1rem;">
            + Qo'lda fikr qo'shish
        </h2>
        <span x-text="open ? '−' : '+'" style="font-size:1.25rem;"></span>
    </div>
    <div x-show="open" x-transition style="margin-top:1.25rem; display:grid;
            grid-template-columns:1fr; gap:.75rem;" class="md:grid-cols-3">
        <input name="ism" required placeholder="Ism" class="field">
        <select name="baho" class="field">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5-$i) ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-primary">Qo'shish</button>
        <textarea name="matn" required placeholder="Fikr matni..." rows="3"
                  class="field" class="md:col-span-3"></textarea>
    </div>
</form>

<!-- Filtr -->
<div style="display:flex; gap:.5rem; margin-bottom: 1.25rem; flex-wrap:wrap;">
    <?php foreach (['' => 'Barcha', 'kutilmoqda' => 'Kutilmoqda', 'tasdiqlangan' => 'Tasdiqlangan'] as $v => $n): ?>
    <a href="?holat=<?= $v ?>"
       style="padding:.5rem 1rem; border:1px solid #000; text-decoration:none;
              font-size:.85rem;
              <?= $holat === $v ? 'background:#000; color:#fff;' : 'background:#fff; color:#000;' ?>"
       onmouseover="this.style.background='<?= $holat===$v?'#333':'#F5F5F5' ?>'"
       onmouseout="this.style.background='<?= $holat===$v?'#000':'#fff' ?>'">
        <?= $n ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Fikrlar -->
<?php if (empty($royxat)): ?>
<div class="b-card" style="padding:3rem; text-align:center; color:#666;">
    <?= e(t('malumot_yoq')) ?>
</div>
<?php else: ?>
<div style="display:grid; grid-template-columns:1fr; gap:1rem;" class="md:grid-cols-2">
    <?php foreach ($royxat as $r): ?>
    <div class="b-card" style="padding:1.25rem;
        <?= !$r['tasdiq'] ? 'border-color:#000; background:#FAFAFA;' : '' ?>">

        <div style="display:flex; justify-content:space-between; align-items:flex-start;
                    margin-bottom:.75rem;">
            <div>
                <strong style="font-family:Georgia,serif; font-size:1rem;">
                    <?= e($r['ism']) ?>
                </strong>
                <div style="font-size:.85rem; margin-top:.2rem; letter-spacing:.1em;">
                    <?= str_repeat('★', (int)$r['baho']) . str_repeat('☆', 5-(int)$r['baho']) ?>
                </div>
            </div>
            <span class="badge <?= $r['tasdiq'] ? 'badge-filled' : '' ?>">
                <?= $r['tasdiq'] ? 'Tasdiqlangan' : 'Kutilmoqda' ?>
            </span>
        </div>

        <p style="font-size:.875rem; color:#000; line-height:1.6; margin-bottom:1rem;">
            <?= e($r['matn']) ?>
        </p>

        <div style="display:flex; justify-content:space-between; align-items:center;
                    padding-top:.85rem; border-top:1px solid #E5E5E5;">
            <span style="font-size:.78rem; color:#666;">
                <?= e(vaqt_oldin($r['yaratilgan'])) ?>
            </span>
            <div style="display:flex; gap:.4rem;">
                <?php if (!$r['tasdiq']): ?>
                <form method="POST" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="tasdiq">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-xs btn-primary">✓ Tasdiq</button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="bekor">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-xs">Bekor</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('O\'chirilsinmi?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="ochirish">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-xs btn-danger">O'chirish</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
