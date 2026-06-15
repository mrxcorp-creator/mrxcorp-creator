<?php
/**
 * AvtoTest Pro — Tariflar CRUD
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL . '/admin/tariflar.php'); }
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
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            db_bajar('INSERT INTO tariflar (nomi,tavsif,tur,qiymat,narx,eski_narx,mashhur,tartib,holat) VALUES (?,?,?,?,?,?,?,?,?)',
                     [$nomi,$tavsif,$tur,$qiymat,$narx,$eski,$mashhur,$tartib,$holat]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
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

$tahrir  = olish('tahrir') ? db_qator('SELECT * FROM tariflar WHERE id=?', [(int)olish('tahrir')]) : null;
$tariflar = db_barcha('SELECT * FROM tariflar ORDER BY tartib, narx');

$admin_sahifa    = 'tariflar';
$sahifa_sarlavha = t('tariflar');
require_once __DIR__ . '/_layout.php';
?>

<!-- Forma -->
<form method="POST" class="glass-card p-5 mb-6 fade-up" x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer select-none" @click="open = !open">
        <h2 class="font-display text-base"><?= $tahrir ? '✏️ Tarif tahrirlash' : '➕ Yangi tarif' ?></h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="mt-5 grid sm:grid-cols-2 gap-4">
        <div>
            <label class="field-label">Nomi *</label>
            <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field" placeholder="1 oylik">
        </div>
        <div>
            <label class="field-label">Narxi (so'm) *</label>
            <input name="narx" type="number" required min="0" step="100" value="<?= e($tahrir['narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Eski narx (chizilgan)</label>
            <input name="eski_narx" type="number" min="0" step="100" value="<?= e($tahrir['eski_narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tur</label>
            <select name="tur" class="field">
                <?php foreach (['kun'=>'Kunlik','oy'=>'Oylik','bilet'=>'Bilet'] as $v=>$n): ?>
                    <option value="<?= $v ?>" <?= ($tahrir['tur'] ?? 'oy') === $v ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Qiymat (necha kun/oy)</label>
            <input name="qiymat" type="number" min="1" value="<?= e($tahrir['qiymat'] ?? 1) ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tartib raqami</label>
            <input name="tartib" type="number" value="<?= e($tahrir['tartib'] ?? 0) ?>" class="field">
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Tavsif</label>
            <textarea name="tavsif" rows="2" class="field"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-5">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="mashhur" value="1" <?= !empty($tahrir['mashhur']) ? 'checked' : '' ?> class="w-4 h-4 rounded">
                <span class="text-sm">⭐ Eng mashhur</span>
            </label>
            <select name="holat" class="field text-sm max-w-[130px]">
                <option value="faol"   <?= ($tahrir['holat'] ?? 'faol') === 'faol'   ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol'     ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="?" class="btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Tariflar grid -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($tariflar as $t): ?>
        <div class="glass-card p-5 fade-up <?= $t['holat'] === 'nofaol' ? 'opacity-50' : '' ?>">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-display"><?= e($t['nomi']) ?></h3>
                <?php if ($t['mashhur']): ?>
                    <span class="badge badge-blue">⭐</span>
                <?php endif; ?>
            </div>
            <?php if ($t['eski_narx'] && (float)$t['eski_narx'] > (float)$t['narx']): ?>
                <div class="line-through text-xs text-brand-muted"><?= e(pul($t['eski_narx'])) ?></div>
            <?php endif; ?>
            <div class="text-2xl font-display font-bold text-blue-400 mb-1 tabnum"><?= e(pul($t['narx'])) ?></div>
            <p class="text-xs text-brand-muted line-clamp-2 mb-3"><?= e($t['tavsif']) ?></p>
            <div class="flex items-center justify-between">
                <span class="badge <?= $t['holat'] === 'faol' ? 'badge-green' : 'badge-red' ?>"><?= e($t['holat']) ?></span>
                <div class="flex gap-2">
                    <a href="?tahrir=<?= (int)$t['id'] ?>" class="badge badge-yellow cursor-pointer"><?= e(t('tahrirlash')) ?></a>
                    <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="harakat" value="ochirish">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button class="badge badge-red cursor-pointer"><?= e(t('ochirish')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
