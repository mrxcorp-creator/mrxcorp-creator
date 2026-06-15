<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/fikrlar.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');

    if ($harakat === 'tasdiq') {
        db_bajar('UPDATE fikrlar SET tasdiq = 1 WHERE id = ?', [$id]);
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kfayl) @unlink($kfayl);
    }
    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM fikrlar WHERE id = ?', [$id]);
        foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kfayl) @unlink($kfayl);
    }
    if ($harakat === 'qoshish') {
        $ism = post('ism');
        $matn = post('matn');
        $baho = max(1, min(5, (int) post('baho')));
        if ($ism && $matn) {
            db_bajar('INSERT INTO fikrlar (ism, matn, baho, tasdiq) VALUES (?, ?, ?, 1)',
                     [$ism, $matn, $baho]);
            foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $kfayl) @unlink($kfayl);
        }
    }
    flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    yonaltir(SAYT_URL . '/admin/fikrlar.php?holat=' . urlencode(olish('holat')));
}

$holat = olish('holat');
$shart = '';
if ($holat === 'kutilmoqda') {
    $shart = ' WHERE tasdiq = 0';
} elseif ($holat === 'tasdiqlangan') {
    $shart = ' WHERE tasdiq = 1';
}

$royxat = db_barcha("SELECT * FROM fikrlar $shart ORDER BY yaratilgan DESC LIMIT 200");

$admin_sahifa = 'fikrlar';
$sahifa_sarlavha = t('fikrlar');
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="glass p-5 mb-6 fade-up" x-data="{open: false}">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="qoshish">

    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h2 class="font-display font-bold text-lg">➕ Qo'lda fikr qo'shish</h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="grid sm:grid-cols-3 gap-3 mt-4">
        <input name="ism" required placeholder="Ism" class="field">
        <select name="baho" class="field">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-primary"><?= e(t('qoshish')) ?></button>
        <textarea name="matn" required placeholder="Fikr matni..." rows="2" class="field sm:col-span-3"></textarea>
    </div>
</form>

<div class="flex gap-2 mb-4">
    <a href="?" class="px-3 py-1.5 rounded-lg text-sm <?= !$holat ? 'grad-bg text-white font-semibold' : 'bg-white/5' ?>">Barcha</a>
    <a href="?holat=kutilmoqda" class="px-3 py-1.5 rounded-lg text-sm <?= $holat === 'kutilmoqda' ? 'grad-bg text-white font-semibold' : 'bg-white/5' ?>"><?= e(t('kutilmoqda')) ?></a>
    <a href="?holat=tasdiqlangan" class="px-3 py-1.5 rounded-lg text-sm <?= $holat === 'tasdiqlangan' ? 'grad-bg text-white font-semibold' : 'bg-white/5' ?>"><?= e(t('tasdiqlangan')) ?></a>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <?php foreach ($royxat as $r): ?>
        <div class="glass p-5 fade-up <?= !$r['tasdiq'] ? '!border-amber/40' : '' ?>">
            <div class="flex items-start justify-between mb-2">
                <strong><?= e($r['ism']) ?></strong>
                <span class="text-amber tracking-wider"><?= str_repeat('★', (int)$r['baho']) ?></span>
            </div>
            <p class="text-sm text-muted leading-relaxed"><?= e($r['matn']) ?></p>
            <div class="flex items-center justify-between mt-4 pt-3 border-t border-white/5">
                <span class="text-xs text-muted"><?= e(vaqt_oldin($r['yaratilgan'])) ?></span>
                <div class="flex gap-3">
                    <?php if (!$r['tasdiq']): ?>
                        <form method="POST" class="inline">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="tasdiq">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="text-success text-xs hover:underline">✓ Tasdiq</button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs text-success">✓ <?= e(t('tasdiqlangan')) ?></span>
                    <?php endif; ?>
                    <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="harakat" value="ochirish">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="text-danger text-xs hover:underline"><?= e(t('ochirish')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($royxat)): ?>
    <div class="glass p-12 text-center text-muted text-sm fade-up">
        <p><?= e(t('malumot_yoq')) ?></p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
