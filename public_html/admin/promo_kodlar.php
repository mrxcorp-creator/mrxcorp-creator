<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/promo_kodlar.php');
    }
    $harakat = post('harakat');

    if (in_array($harakat, ['yarat', 'yangila'], true)) {
        $id = (int) post('id');
        $kod = strtoupper(preg_replace('/[^A-Z0-9]/i', '', post('kod')));
        $chegirma_foiz = max(0, min(100, (int) post('chegirma_foiz')));
        $chegirma_summa = max(0, (int) post('chegirma_summa'));
        $maks_ishlatish = max(1, (int) post('maks_ishlatish'));
        $tugash = post('tugash_sanasi') ?: null;
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$kod || mb_strlen($kod) < 3) {
            flash_qoy('xato', 'Promo kod kamida 3 belgi bo\'lishi kerak');
        } elseif ($id) {
            try {
                db_bajar(
                    'UPDATE promo_kodlar SET kod=?, chegirma_foiz=?, chegirma_summa=?, maks_ishlatish=?, tugash_sanasi=?, holat=? WHERE id=?',
                    [$kod, $chegirma_foiz, $chegirma_summa, $maks_ishlatish, $tugash, $holat, $id]
                );
                audit_yoz('promo_yangilandi', 'promo', $id, ['kod' => $kod]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            } catch (Throwable $e) {
                flash_qoy('xato', $e->getMessage());
            }
        } else {
            try {
                $yangi_id = db_bajar(
                    'INSERT INTO promo_kodlar (kod, chegirma_foiz, chegirma_summa, maks_ishlatish, tugash_sanasi, holat)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$kod, $chegirma_foiz, $chegirma_summa, $maks_ishlatish, $tugash, $holat]
                );
                audit_yoz('promo_yaratildi', 'promo', $yangi_id, ['kod' => $kod]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            } catch (Throwable $e) {
                flash_qoy('xato', 'Bunday kod allaqachon mavjud yoki xato: ' . $e->getMessage());
            }
        }
    }

    if ($harakat === 'ochir') {
        $id = (int) post('id');
        db_bajar('DELETE FROM promo_kodlar WHERE id = ?', [$id]);
        audit_yoz('promo_ochirildi', 'promo', $id);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    yonaltir(SAYT_URL . '/admin/promo_kodlar.php');
}

$tahrir = olish('tahrir') ? db_qator('SELECT * FROM promo_kodlar WHERE id = ?', [(int) olish('tahrir')]) : null;
$royxat = db_barcha('SELECT * FROM promo_kodlar ORDER BY id DESC');

$admin_sahifa = 'promo_kodlar';
$sahifa_sarlavha = 'Promo kodlar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="glass p-5 mb-6 fade-up" x-data="{open: <?= $tahrir ? 'true' : 'false' ?>}">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'yangila' : 'yarat' ?>">
    <input type="hidden" name="id" value="<?= (int) ($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
        <h2 class="font-display font-bold text-lg">
            <?= $tahrir ? '✏️ Promo kod tahrirlash' : '➕ Yangi promo kod' ?>
        </h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="grid sm:grid-cols-2 gap-4 mt-4">
        <div>
            <label class="field-label">Kod * <span class="text-xs text-muted">(faqat ASCII)</span></label>
            <input name="kod" required pattern="[A-Z0-9]{3,30}" class="field uppercase" maxlength="30"
                   value="<?= e($tahrir['kod'] ?? '') ?>" placeholder="YANGIYIL2026">
        </div>
        <div>
            <label class="field-label">Maks ishlatish</label>
            <input name="maks_ishlatish" type="number" min="1" class="field"
                   value="<?= (int) ($tahrir['maks_ishlatish'] ?? 100) ?>">
        </div>
        <div>
            <label class="field-label">Chegirma foiz (%) <span class="text-xs text-muted">(0–100)</span></label>
            <input name="chegirma_foiz" type="number" min="0" max="100" class="field"
                   value="<?= (int) ($tahrir['chegirma_foiz'] ?? 0) ?>">
        </div>
        <div>
            <label class="field-label">Yoki summa (so'm) <span class="text-xs text-muted">(qat'iy)</span></label>
            <input name="chegirma_summa" type="number" min="0" step="100" class="field"
                   value="<?= (int) ($tahrir['chegirma_summa'] ?? 0) ?>">
        </div>
        <div>
            <label class="field-label">Tugash sanasi <span class="text-xs text-muted">(ixtiyoriy)</span></label>
            <input name="tugash_sanasi" type="datetime-local" class="field"
                   value="<?= $tahrir && $tahrir['tugash_sanasi'] ? e(date('Y-m-d\TH:i', strtotime($tahrir['tugash_sanasi']))) : '' ?>">
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol" <?= ($tahrir['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="?" class="btn btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="glass p-5 fade-up">
    <h2 class="font-display font-bold text-lg mb-4">Mavjud kodlar (<span class="grad-text"><?= count($royxat) ?></span>)</h2>

    <?php if (empty($royxat)): ?>
        <p class="text-center py-8 text-muted text-sm">Hali promo kod yo'q.</p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
                        <th class="py-2 pr-3">Kod</th>
                        <th class="py-2 pr-3">Chegirma</th>
                        <th class="py-2 pr-3">Ishlatilgan</th>
                        <th class="py-2 pr-3">Tugash</th>
                        <th class="py-2 pr-3">Holat</th>
                        <th class="py-2 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($royxat as $p):
                        $tugagan = $p['tugash_sanasi'] && strtotime($p['tugash_sanasi']) < time();
                        $tugagan_son = $p['ishlatilgan'] >= $p['maks_ishlatish'];
                        $haqiqiy_holat = $tugagan ? 'tugagan' : ($tugagan_son ? 'tugagan' : $p['holat']);
                    ?>
                        <tr class="hover:bg-white/3">
                            <td class="py-2.5 pr-3 font-mono font-bold grad-text"><?= e($p['kod']) ?></td>
                            <td class="py-2.5 pr-3">
                                <?php if ($p['chegirma_foiz'] > 0): ?>
                                    <span class="text-success"><?= (int)$p['chegirma_foiz'] ?>%</span>
                                <?php else: ?>
                                    <?= e(pul($p['chegirma_summa'])) ?>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="font-bold"><?= (int)$p['ishlatilgan'] ?></span>
                                <span class="text-muted">/ <?= (int)$p['maks_ishlatish'] ?></span>
                            </td>
                            <td class="py-2.5 pr-3 text-xs">
                                <?= $p['tugash_sanasi'] ? e(sana($p['tugash_sanasi'], 'd.m.Y H:i')) : '<span class="text-muted">cheksiz</span>' ?>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2
                                    <?= $haqiqiy_holat === 'faol' ? 'bg-success/15 text-success border-success/30' :
                                       ($haqiqiy_holat === 'tugagan' ? 'bg-amber/15 text-amber border-amber/30' : 'bg-danger/15 text-danger border-danger/30') ?>">
                                    <?= e($haqiqiy_holat) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-right whitespace-nowrap">
                                <a href="?tahrir=<?= (int)$p['id'] ?>" class="text-amber hover:underline text-xs mr-2"><?= e(t('tahrirlash')) ?></a>
                                <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochir">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
