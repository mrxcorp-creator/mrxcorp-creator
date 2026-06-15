<?php
/**
 * VatanParvar Yaypan — Promo kodlar boshqaruvi (NEW)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL . '/admin/promokodlar.php'); }

    $harakat = post('harakat');
    $id      = (int) post('id');

    if (in_array($harakat, ['yaratish','tahrirlash'], true)) {
        $kod           = strtoupper(preg_replace('/[^A-Z0-9]/i', '', post('kod')));
        $chegirma_foiz = max(0, min(100, (int) post('chegirma_foiz')));
        $chegirma_summa= max(0, (float) post('chegirma_summa'));
        $maks          = max(1, (int) post('maks_ishlatish') ?: 100);
        $tugash        = post('tugash_sanasi') ?: null;
        $holat         = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$kod || mb_strlen($kod) < 3) {
            flash_qoy('xato', "Kod kamida 3 belgidan iborat bo'lishi kerak");
        } elseif ($chegirma_foiz === 0 && $chegirma_summa === 0.0) {
            flash_qoy('xato', 'Chegirma foizi yoki summasi kiritilishi kerak');
        } else {
            if ($id) {
                db_bajar(
                    'UPDATE promo_kodlar SET kod=?,chegirma_foiz=?,chegirma_summa=?,maks_ishlatish=?,tugash_sanasi=?,holat=? WHERE id=?',
                    [$kod,$chegirma_foiz,$chegirma_summa,$maks,$tugash,$holat,$id]
                );
            } else {
                // Mavjudligini tekshirish
                if (db_qiymat('SELECT 1 FROM promo_kodlar WHERE kod=?', [$kod])) {
                    flash_qoy('xato', "Ushbu kod avval qo'shilgan");
                    yonaltir(SAYT_URL . '/admin/promokodlar.php');
                }
                db_bajar(
                    'INSERT INTO promo_kodlar (kod,chegirma_foiz,chegirma_summa,maks_ishlatish,tugash_sanasi,holat) VALUES (?,?,?,?,?,?)',
                    [$kod,$chegirma_foiz,$chegirma_summa,$maks,$tugash,$holat]
                );
            }
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/promokodlar.php');
    }

    if ($harakat === 'toggle_holat') {
        $promo = db_qator('SELECT * FROM promo_kodlar WHERE id=?', [$id]);
        if ($promo) {
            $yangi = $promo['holat'] === 'faol' ? 'nofaol' : 'faol';
            db_bajar('UPDATE promo_kodlar SET holat=? WHERE id=?', [$yangi, $id]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/promokodlar.php');
    }

    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM promo_kodlar WHERE id=?', [$id]);
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        yonaltir(SAYT_URL . '/admin/promokodlar.php');
    }
}

$tahrir   = olish('tahrir') ? db_qator('SELECT * FROM promo_kodlar WHERE id=?', [(int)olish('tahrir')]) : null;
$kodlar   = db_barcha('SELECT * FROM promo_kodlar ORDER BY yaratilgan DESC');
$jami_foy = (int) db_qiymat('SELECT COALESCE(SUM(ishlatilgan),0) FROM promo_kodlar');

$admin_sahifa    = 'promokodlar';
$sahifa_sarlavha = 'Promo kodlar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Statistika -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="glass-card p-4 fade-up text-center">
        <p class="text-2xl font-display font-bold"><?= count($kodlar) ?></p>
        <p class="text-xs text-brand-muted mt-1">Jami kodlar</p>
    </div>
    <div class="glass-card p-4 fade-up text-center">
        <p class="text-2xl font-display font-bold text-green-400"><?= count(array_filter($kodlar, fn($k)=>$k['holat']==='faol')) ?></p>
        <p class="text-xs text-brand-muted mt-1">Faol</p>
    </div>
    <div class="glass-card p-4 fade-up text-center">
        <p class="text-2xl font-display font-bold text-blue-400 tabnum"><?= $jami_foy ?></p>
        <p class="text-xs text-brand-muted mt-1">Jami foydalanish</p>
    </div>
    <div class="glass-card p-4 fade-up text-center">
        <p class="text-2xl font-display font-bold text-yellow-400"><?= count(array_filter($kodlar, fn($k)=>$k['tugash_sanasi'] && strtotime($k['tugash_sanasi']) < time())) ?></p>
        <p class="text-xs text-brand-muted mt-1">Muddati o'tgan</p>
    </div>
</div>

<!-- Forma -->
<form method="POST" class="glass-card p-5 mb-6 fade-up" x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div class="flex items-center justify-between cursor-pointer select-none" @click="open = !open">
        <h2 class="font-display text-base"><?= $tahrir ? '✏️ Promo kod tahrirlash' : '➕ Yangi promo kod' ?></h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>

    <div x-show="open" x-transition class="mt-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="field-label">Kod *</label>
            <input name="kod" required value="<?= e($tahrir['kod'] ?? '') ?>"
                   class="field uppercase tracking-widest font-mono" maxlength="30"
                   placeholder="YAZGI2025">
        </div>
        <div>
            <label class="field-label">Chegirma foizi (%)</label>
            <input type="number" name="chegirma_foiz" min="0" max="100"
                   value="<?= e($tahrir['chegirma_foiz'] ?? 0) ?>" class="field"
                   placeholder="20">
        </div>
        <div>
            <label class="field-label">Chegirma summasi (so'm)</label>
            <input type="number" name="chegirma_summa" min="0" step="100"
                   value="<?= e($tahrir['chegirma_summa'] ?? 0) ?>" class="field"
                   placeholder="5000">
        </div>
        <div>
            <label class="field-label">Maks. foydalanish soni</label>
            <input type="number" name="maks_ishlatish" min="1"
                   value="<?= e($tahrir['maks_ishlatish'] ?? 100) ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tugash sanasi (ixtiyoriy)</label>
            <input type="datetime-local" name="tugash_sanasi"
                   value="<?= e($tahrir['tugash_sanasi'] ? date('Y-m-d\TH:i', strtotime($tahrir['tugash_sanasi'])) : '') ?>"
                   class="field">
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol"   <?= ($tahrir['holat'] ?? 'faol') === 'faol'   ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat'] ?? '') === 'nofaol'     ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-3 flex gap-3">
            <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?>
                <a href="?" class="btn-ghost"><?= e(t('bekor_qilish')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Promo kodlar jadvali -->
<div class="glass-card p-5 fade-up">
    <?php if (empty($kodlar)): ?>
        <div class="py-12 text-center text-brand-muted">
            <div class="text-4xl mb-3">🎟️</div>
            <p>Hali promo kodlar yo'q</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase tracking-wide">
                        <th class="py-2.5 pr-3">Kod</th>
                        <th class="py-2.5 pr-3">Chegirma</th>
                        <th class="py-2.5 pr-3">Foydalanish</th>
                        <th class="py-2.5 pr-3">Tugash</th>
                        <th class="py-2.5 pr-3">Holat</th>
                        <th class="py-2.5 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05]">
                    <?php foreach ($kodlar as $k):
                        $tugadi = $k['tugash_sanasi'] && strtotime($k['tugash_sanasi']) < time();
                        $toldi  = (int)$k['ishlatilgan'] >= (int)$k['maks_ishlatish'];
                    ?>
                        <tr class="hover:bg-white/[0.03] transition <?= ($tugadi || $toldi) ? 'opacity-60' : '' ?>">
                            <td class="py-3 pr-3 font-mono font-bold tracking-widest text-blue-300">
                                <?= e($k['kod']) ?>
                            </td>
                            <td class="py-3 pr-3">
                                <?php if ((float)$k['chegirma_foiz'] > 0): ?>
                                    <span class="badge badge-green"><?= (int)$k['chegirma_foiz'] ?>%</span>
                                <?php endif; ?>
                                <?php if ((float)$k['chegirma_summa'] > 0): ?>
                                    <span class="badge badge-blue"><?= e(pul($k['chegirma_summa'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 pr-3 tabnum">
                                <span class="font-semibold"><?= (int)$k['ishlatilgan'] ?></span>
                                <span class="text-brand-muted"> / <?= (int)$k['maks_ishlatish'] ?></span>
                                <!-- Progress -->
                                <div class="w-16 h-1 bg-white/10 rounded-full mt-1 overflow-hidden">
                                    <?php $foiz_k = min(100, (int)$k['maks_ishlatish'] > 0 ? round((int)$k['ishlatilgan']/(int)$k['maks_ishlatish']*100) : 0); ?>
                                    <div class="h-full <?= $foiz_k >= 100 ? 'bg-red-400' : 'bg-blue-400' ?> rounded-full" style="width:<?= $foiz_k ?>%"></div>
                                </div>
                            </td>
                            <td class="py-3 pr-3 text-xs <?= $tugadi ? 'text-red-400' : 'text-brand-muted' ?>">
                                <?= $k['tugash_sanasi'] ? e(sana($k['tugash_sanasi'], 'd.m.Y')) : '—' ?>
                            </td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= $k['holat'] === 'faol' ? 'badge-green' : 'badge-red' ?>">
                                    <?= e($k['holat']) ?>
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?tahrir=<?= (int)$k['id'] ?>" class="badge badge-yellow cursor-pointer">✏️</a>
                                    <form method="POST" class="inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="toggle_holat">
                                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                        <button class="badge <?= $k['holat'] === 'faol' ? 'badge-gray' : 'badge-green' ?> cursor-pointer">
                                            <?= $k['holat'] === 'faol' ? 'Off' : 'On' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="ochirish">
                                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                        <button class="badge badge-red cursor-pointer">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
