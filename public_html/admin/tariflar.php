<?php
/**
 * Admin — Tariflar CRUD
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/tariflar.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');

    if (in_array($harakat, ['yaratish', 'tahrirlash'], true)) {
        $nomi = post('nomi');
        $tavsif = post('tavsif');
        $tur = in_array(post('tur'), ['kun','oy','bilet'], true) ? post('tur') : 'oy';
        $qiymat = max(1, (int) post('qiymat'));
        $narx = (float) post('narx');
        $eski = (float) post('eski_narx') ?: null;
        $mashhur = post('mashhur') === '1' ? 1 : 0;
        $tartib = (int) post('tartib');
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        // Avto kirill versiyalari
        $nomi_cyrl   = trim((string) post('nomi_cyrl'))   ?: ($nomi   ? lotin_dan_kirill($nomi)   : '');
        $tavsif_cyrl = trim((string) post('tavsif_cyrl')) ?: ($tavsif ? lotin_dan_kirill($tavsif) : '');

        if (!$nomi || $narx <= 0) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar(
                'UPDATE tariflar SET nomi=?, nomi_cyrl=?, tavsif=?, tavsif_cyrl=?, tur=?, qiymat=?, narx=?, eski_narx=?, mashhur=?, tartib=?, holat=? WHERE id=?',
                [$nomi, $nomi_cyrl, $tavsif, $tavsif_cyrl, $tur, $qiymat, $narx, $eski, $mashhur, $tartib, $holat, $id]
            );
        } else {
            db_bajar(
                'INSERT INTO tariflar (nomi, nomi_cyrl, tavsif, tavsif_cyrl, tur, qiymat, narx, eski_narx, mashhur, tartib, holat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$nomi, $nomi_cyrl, $tavsif, $tavsif_cyrl, $tur, $qiymat, $narx, $eski, $mashhur, $tartib, $holat]
            );
        }
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/tariflar.php');
    }
    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM tariflar WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/tariflar.php');
    }
}

$tahrir = olish('tahrir') ? db_qator('SELECT * FROM tariflar WHERE id = ?', [(int) olish('tahrir')]) : null;
$tariflar = db_barcha('SELECT * FROM tariflar ORDER BY tartib, narx');

$admin_sahifa = 'tariflar';
$sahifa_sarlavha = t('tariflar');
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="glass-card p-5 mb-6 fade-up" x-data="{open: <?= $tahrir ? 'true' : 'false' ?>}">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int) ($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h2 class="font-display text-lg">
            <?= $tahrir ? '✏️ Tarif tahrirlash' : '➕ Yangi tarif' ?>
        </h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="grid sm:grid-cols-2 gap-4 mt-4">
        <div>
            <label class="field-label">Nomi (lotin) *</label>
            <input name="nomi" required value="<?= e($tahrir['nomi'] ?? '') ?>" class="field" data-translit="nomi_cyrl">
        </div>
        <div>
            <label class="field-label">Nomi (kirill) <span class="text-xs text-brand-muted">— avto</span></label>
            <input name="nomi_cyrl" value="<?= e($tahrir['nomi_cyrl'] ?? '') ?>" class="field" placeholder="Avto">
        </div>
        <div>
            <label class="field-label">Narxi (so'm) *</label>
            <input name="narx" type="number" required min="0" step="100" value="<?= e($tahrir['narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tur</label>
            <select name="tur" class="field">
                <?php foreach (['kun' => 'Kun', 'oy' => 'Oy', 'bilet' => 'Bilet'] as $v => $n): ?>
                    <option value="<?= $v ?>" <?= ($tahrir['tur'] ?? 'oy') === $v ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Qiymat (necha)</label>
            <input name="qiymat" type="number" min="1" value="<?= e($tahrir['qiymat'] ?? 1) ?>" class="field">
        </div>
        <div>
            <label class="field-label">Eski narx (chizilgan)</label>
            <input name="eski_narx" type="number" min="0" step="100" value="<?= e($tahrir['eski_narx'] ?? '') ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tartib</label>
            <input name="tartib" type="number" value="<?= e($tahrir['tartib'] ?? 0) ?>" class="field">
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Tavsif (lotin)</label>
            <textarea name="tavsif" rows="2" class="field" data-translit="tavsif_cyrl"><?= e($tahrir['tavsif'] ?? '') ?></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="field-label">Tavsif (kirill) <span class="text-xs text-brand-muted">— avto</span></label>
            <textarea name="tavsif_cyrl" rows="2" class="field" placeholder="Avto"><?= e($tahrir['tavsif_cyrl'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-4 sm:col-span-2">
            <label class="flex items-center gap-2"><input type="checkbox" name="mashhur" value="1" <?= !empty($tahrir['mashhur']) ? 'checked' : '' ?>> Eng mashhur</label>
            <label class="flex items-center gap-2">
                <select name="holat" class="field text-sm">
                    <option value="faol" <?= ($tahrir['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                    <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
                </select>
            </label>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="?" class="btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($tariflar as $t): ?>
        <div class="glass-card p-5 fade-up <?= $t['holat'] === 'nofaol' ? 'opacity-50' : '' ?>">
            <div class="flex items-start justify-between">
                <h3 class="font-display"><?= e(tk($t, 'nomi')) ?></h3>
                <?php if ($t['mashhur']): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400">★</span>
                <?php endif; ?>
            </div>
            <div class="text-2xl font-display font-bold text-blue-400 mt-2"><?= e(pul($t['narx'])) ?></div>
            <?php if ($t['eski_narx']): ?>
                <div class="line-through text-brand-muted text-xs"><?= e(pul($t['eski_narx'])) ?></div>
            <?php endif; ?>
            <p class="text-xs text-brand-muted mt-2 line-clamp-2"><?= e(tk($t, 'tavsif')) ?></p>
            <div class="flex gap-2 mt-4">
                <a href="?tahrir=<?= (int)$t['id'] ?>" class="text-yellow-400 text-xs hover:underline"><?= e(t('tahrirlash')) ?></a>
                <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="harakat" value="ochirish">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="text-red-400 text-xs hover:underline"><?= e(t('ochirish')) ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
