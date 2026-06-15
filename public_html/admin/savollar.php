<?php
/**
 * AvtoTest Pro — Savollar CRUD
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

$bilet_id = (int)(olish('bilet') ?: post('bilet_id'));
$harakat  = post('harakat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL . '/admin/savollar.php?bilet='.$bilet_id); }

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

<!-- Bilet tanlash -->
<form method="GET" class="glass-card p-4 mb-5 flex gap-3 items-end fade-up">
    <div class="flex-1">
        <label class="field-label">Bilet tanlang</label>
        <select name="bilet" onchange="this.form.submit()" class="field">
            <option value="">— bilet tanlang —</option>
            <?php foreach ($biletlar as $bl): ?>
                <option value="<?= (int)$bl['id'] ?>" <?= $bilet_id === (int)$bl['id'] ? 'selected' : '' ?>>
                    №<?= (int)$bl['raqam'] ?> — <?= e($bl['nomi']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($bilet): ?>
        <div class="text-sm text-brand-muted pb-2">
            <?= count($savollar) ?> ta savol
        </div>
    <?php endif; ?>
</form>

<?php if ($bilet): ?>
    <!-- Savol forma -->
    <form method="POST" enctype="multipart/form-data" class="glass-card p-5 mb-6 fade-up"
          x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
        <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">
        <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">

        <div class="flex items-center justify-between cursor-pointer select-none" @click="open = !open">
            <h2 class="font-display text-base">
                <?= $tahrir ? '✏️ Savol tahrirlash' : '➕ Yangi savol' ?> — №<?= (int)$bilet['raqam'] ?>
            </h2>
            <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>

        <div x-show="open" x-transition class="mt-5 space-y-4">
            <div>
                <label class="field-label">Savol matni *</label>
                <textarea name="matn" required rows="3" class="field"><?= e($tahrir['matn'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="field-label">Rasm (ixtiyoriy, JPEG/PNG/WebP maks 5 MB)</label>
                <input type="file" name="rasm" accept="image/*" class="field file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-blue-500/20 file:text-blue-400 file:cursor-pointer">
                <?php if (!empty($tahrir['rasm'])): ?>
                    <div class="mt-2 flex items-center gap-3">
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($tahrir['rasm']) ?>" class="h-16 rounded-xl">
                        <label class="text-xs text-red-400 flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="rasm_ochir" value="1"> Rasmni o'chirish
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach (['a','b','c','d'] as $v):
                    $req = in_array($v, ['a','b'], true);
                ?>
                    <div>
                        <label class="field-label uppercase font-bold text-<?= $tahrir['togri_javob'] ?? '' === $v ? 'green-400' : 'brand-muted' ?>">
                            <?= $v ?>) Variant <?= $req ? '*' : '(ixtiyoriy)' ?>
                        </label>
                        <textarea name="variant_<?= $v ?>" rows="2" class="field" <?= $req ? 'required' : '' ?>><?= e($tahrir['variant_'.$v] ?? '') ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">To'g'ri javob *</label>
                    <select name="togri_javob" required class="field uppercase">
                        <option value="">— tanlang —</option>
                        <?php foreach (['a','b','c','d'] as $v): ?>
                            <option value="<?= $v ?>" <?= ($tahrir['togri_javob'] ?? '') === $v ? 'selected' : '' ?>>
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
                <label class="field-label">Izoh (test tugagandan so'ng ko'rsatiladi)</label>
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
        <h2 class="font-display text-base mb-4">Savollar (<?= count($savollar) ?>)</h2>
        <?php if (empty($savollar)): ?>
            <p class="text-center py-8 text-brand-muted text-sm">Hali savollar qo'shilmagan</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($savollar as $i => $s): ?>
                    <details class="border border-white/[0.08] rounded-xl group">
                        <summary class="px-4 py-3 cursor-pointer flex items-center gap-3 hover:bg-white/[0.04] rounded-xl transition">
                            <span class="w-7 h-7 rounded-lg bg-blue-500/20 flex items-center justify-center text-xs font-bold flex-shrink-0 tabnum"><?= $i + 1 ?></span>
                            <span class="flex-1 truncate text-sm"><?= e(mb_substr($s['matn'], 0, 120)) ?></span>
                            <span class="badge badge-green uppercase flex-shrink-0"><?= e($s['togri_javob']) ?></span>
                        </summary>
                        <div class="px-4 pb-4 border-t border-white/[0.07] pt-3 text-sm space-y-3">
                            <p><?= e($s['matn']) ?></p>
                            <?php if ($s['rasm']): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" class="max-w-sm rounded-xl" loading="lazy">
                            <?php endif; ?>
                            <div class="grid sm:grid-cols-2 gap-2">
                                <?php foreach (['a','b','c','d'] as $v):
                                    if (empty($s['variant_'.$v])) continue;
                                    $t_ = $s['togri_javob'] === $v;
                                ?>
                                    <div class="p-2 rounded-lg border <?= $t_ ? 'border-green-500/30 bg-green-500/[0.06] text-green-300' : 'border-white/[0.07] text-brand-muted' ?>">
                                        <span class="font-bold uppercase mr-1"><?= $v ?>)</span><?= e($s['variant_'.$v]) ?>
                                        <?php if ($t_): ?> ✓<?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($s['izoh'])): ?>
                                <p class="p-2.5 rounded-xl bg-blue-500/10 border border-blue-500/25 text-blue-300 text-xs">💡 <?= e($s['izoh']) ?></p>
                            <?php endif; ?>
                            <div class="flex gap-3 pt-1">
                                <a href="?bilet=<?= (int)$bilet_id ?>&tahrir=<?= (int)$s['id'] ?>"
                                   class="badge badge-yellow cursor-pointer"><?= e(t('tahrirlash')) ?></a>
                                <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochirish">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">
                                    <button class="badge badge-red cursor-pointer"><?= e(t('ochirish')) ?></button>
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
        <div class="text-4xl mb-3">👆</div>
        <p>Avval yuqoridan biletni tanlang</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
