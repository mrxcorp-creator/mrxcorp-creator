<?php
/**
 * Admin — Savollar CRUD
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$harakat = post('harakat');
$bilet_id = (int) (olish('bilet') ?: post('bilet_id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/savollar.php');
    }

    if (in_array($harakat, ['yaratish', 'tahrirlash'], true)) {
        $id = (int) post('id');
        $matn = post('matn');
        $a = post('variant_a'); $b = post('variant_b');
        $c = post('variant_c'); $d = post('variant_d');
        $togri = post('togri_javob');
        $izoh = post('izoh');

        if (!$bilet_id || !$matn || !$a || !$b || !in_array($togri, ['a','b','c','d'], true)) {
            flash_qoy('xato', t('kerakli_maydon'));
        } else {
            $rasm = $id ? db_qiymat('SELECT rasm FROM savollar WHERE id = ?', [$id]) : null;
            if (!empty($_FILES['rasm']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['rasm'], 'savollar', 800);
                if ($yangi) $rasm = $yangi;
            }
            if (post('rasm_ochir') === '1') $rasm = null;

            if ($id) {
                db_bajar(
                    'UPDATE savollar SET matn=?, rasm=?, variant_a=?, variant_b=?, variant_c=?, variant_d=?, togri_javob=?, izoh=? WHERE id=?',
                    [$matn, $rasm, $a, $b, $c, $d, $togri, $izoh, $id]
                );
            } else {
                db_bajar(
                    'INSERT INTO savollar (bilet_id, matn, rasm, variant_a, variant_b, variant_c, variant_d, togri_javob, izoh)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$bilet_id, $matn, $rasm, $a, $b, $c, $d, $togri, $izoh]
                );
            }
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/savollar.php?bilet=' . $bilet_id);
    }

    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM savollar WHERE id = ?', [(int) post('id')]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/savollar.php?bilet=' . $bilet_id);
    }
}

$biletlar = db_barcha('SELECT id, raqam, nomi FROM biletlar ORDER BY raqam');
$bilet = $bilet_id ? db_qator('SELECT * FROM biletlar WHERE id = ?', [$bilet_id]) : null;
$tahrir = olish('tahrir') ? db_qator('SELECT * FROM savollar WHERE id = ?', [(int) olish('tahrir')]) : null;
$savollar = $bilet_id
    ? db_barcha('SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id', [$bilet_id])
    : [];

$admin_sahifa = 'savollar';
$sahifa_sarlavha = 'Savollar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Bilet tanlash -->
<form method="GET" class="glass-card p-4 mb-4 flex gap-3 items-end fade-up">
    <div class="flex-1">
        <label class="field-label">Bilet</label>
        <select name="bilet" onchange="this.form.submit()" class="field">
            <option value="">— tanlang —</option>
            <?php foreach ($biletlar as $b): ?>
                <option value="<?= (int)$b['id'] ?>" <?= $bilet_id === (int)$b['id'] ? 'selected' : '' ?>>
                    №<?= (int)$b['raqam'] ?> — <?= e($b['nomi']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($bilet): ?>
    <!-- Forma -->
    <form method="POST" enctype="multipart/form-data" class="glass-card p-5 mb-6 fade-up" x-data="{open: <?= $tahrir ? 'true' : 'false' ?>}">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
        <input type="hidden" name="id" value="<?= (int) ($tahrir['id'] ?? 0) ?>">
        <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">

        <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
            <h2 class="font-display text-lg">
                <?= $tahrir ? '✏️ Savol tahrirlash' : '➕ Yangi savol' ?> — №<?= (int)$bilet['raqam'] ?>
            </h2>
            <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>

        <div x-show="open" x-transition class="mt-4 space-y-4">
            <div>
                <label class="field-label">Savol matni *</label>
                <textarea name="matn" required rows="2" class="field"><?= e($tahrir['matn'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="field-label">Rasm (ixtiyoriy)</label>
                <input type="file" name="rasm" accept="image/*" class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-blue-500/20 file:text-sky-600">
                <?php if (!empty($tahrir['rasm'])): ?>
                    <div class="mt-2 flex items-center gap-3">
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($tahrir['rasm']) ?>" class="h-16 rounded-lg">
                        <label class="text-xs text-rose-600"><input type="checkbox" name="rasm_ochir" value="1"> Rasmni o'chirish</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach (['a','b','c','d'] as $v):
                    $req = in_array($v, ['a','b'], true);
                ?>
                    <div>
                        <label class="field-label uppercase"><?= $v ?>) Variant <?= $req ? '*' : '' ?></label>
                        <textarea name="variant_<?= $v ?>" rows="2" class="field" <?= $req ? 'required' : '' ?>><?= e($tahrir['variant_' . $v] ?? '') ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">To'g'ri javob *</label>
                    <select name="togri_javob" required class="field uppercase">
                        <option value="">— tanlang —</option>
                        <?php foreach (['a','b','c','d'] as $v): ?>
                            <option value="<?= $v ?>" <?= ($tahrir['togri_javob'] ?? '') === $v ? 'selected' : '' ?>><?= strtoupper($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="field-label">Izoh (test tugagandan keyin ko'rsatiladi)</label>
                <textarea name="izoh" rows="2" class="field"><?= e($tahrir['izoh'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
                <?php if ($tahrir): ?>
                    <a href="?bilet=<?= (int)$bilet_id ?>" class="btn-ghost"><?= e(t('bekor_qilish')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Savollar ro'yxati -->
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display text-lg mb-4">Savollar (<?= count($savollar) ?>)</h2>
        <?php if (empty($savollar)): ?>
            <p class="text-center py-8 text-brand-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($savollar as $i => $s): ?>
                    <details class="border border-brand-border rounded-lg group">
                        <summary class="px-4 py-3 cursor-pointer flex items-center gap-3 hover:bg-sky-50">
                            <span class="w-7 h-7 rounded-md bg-blue-500/20 flex items-center justify-center text-xs font-bold flex-shrink-0"><?= $i + 1 ?></span>
                            <span class="flex-1 truncate text-sm"><?= e(mb_substr($s['matn'], 0, 100)) ?></span>
                            <span class="text-xs text-emerald-600 uppercase"><?= e($s['togri_javob']) ?></span>
                        </summary>
                        <div class="p-4 border-t border-brand-border text-sm space-y-2">
                            <p class="text-white"><?= e($s['matn']) ?></p>
                            <?php if ($s['rasm']): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" class="max-w-md rounded-lg">
                            <?php endif; ?>
                            <?php foreach (['a','b','c','d'] as $v):
                                if (empty($s['variant_' . $v])) continue;
                                $togri = $s['togri_javob'] === $v;
                            ?>
                                <div class="<?= $togri ? 'text-emerald-600' : 'text-brand-muted' ?>">
                                    <strong class="uppercase mr-2"><?= $v ?>)</strong><?= e($s['variant_' . $v]) ?>
                                    <?php if ($togri): ?> ✓<?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!empty($s['izoh'])): ?>
                                <p class="p-2 rounded bg-sky-100 text-sky-700 text-xs">💡 <?= e($s['izoh']) ?></p>
                            <?php endif; ?>
                            <div class="flex gap-2 pt-2">
                                <a href="?bilet=<?= (int)$bilet_id ?>&tahrir=<?= (int)$s['id'] ?>" class="text-amber-600 text-xs hover:underline"><?= e(t('tahrirlash')) ?></a>
                                <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochirish">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">
                                    <button class="text-rose-600 text-xs hover:underline"><?= e(t('ochirish')) ?></button>
                                </form>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="glass-card p-12 text-center text-brand-muted fade-up">
        <p>Avval yuqoridan biletni tanlang.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
