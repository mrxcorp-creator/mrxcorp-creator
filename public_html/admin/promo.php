<?php
/**
 * Admin — Promo kodlar va kampaniyalar boshqaruvi
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/promo.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');

    // ----- PROMO KODLAR -----
    if (in_array($harakat, ['kod_yaratish', 'kod_tahrirlash'], true)) {
        $kod = strtoupper(preg_replace('/[^A-Z0-9]/i', '', post('kod')));
        $foiz = max(0, min(100, (int) post('chegirma_foiz')));
        $summa = (float) post('chegirma_summa');
        $maks = max(1, (int) post('maks_ishlatish'));
        $tugash = post('tugash_sanasi');
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$kod || mb_strlen($kod) < 3) {
            flash_qoy('xato', "Kod kamida 3 belgi");
        } elseif ($id) {
            db_bajar(
                'UPDATE promo_kodlar SET kod=?, chegirma_foiz=?, chegirma_summa=?, maks_ishlatish=?, tugash_sanasi=?, holat=? WHERE id=?',
                [$kod, $foiz, $summa, $maks, $tugash ?: null, $holat, $id]
            );
            audit_yoz($f['id'], 'promo_kod_tahrirlash', 'promo_kodlar', $id, "Kod: $kod");
            flash_qoy('muvaffaqiyat', "Kod yangilandi");
        } else {
            $bor = db_qiymat('SELECT id FROM promo_kodlar WHERE kod = ?', [$kod]);
            if ($bor) flash_qoy('xato', "Bunday kod allaqachon bor");
            else {
                $yangi = db_bajar(
                    'INSERT INTO promo_kodlar (kod, chegirma_foiz, chegirma_summa, maks_ishlatish, tugash_sanasi, holat) VALUES (?, ?, ?, ?, ?, ?)',
                    [$kod, $foiz, $summa, $maks, $tugash ?: null, $holat]
                );
                audit_yoz($f['id'], 'promo_kod_yaratish', 'promo_kodlar', $yangi, "Kod: $kod");
                flash_qoy('muvaffaqiyat', "Kod yaratildi: $kod");
            }
        }
        yonaltir(SAYT_URL . '/admin/promo.php');
    }

    if ($harakat === 'kod_ochirish') {
        db_bajar('DELETE FROM promo_kodlar WHERE id = ?', [$id]);
        audit_yoz($f['id'], 'promo_kod_ochirish', 'promo_kodlar', $id);
        flash_qoy('muvaffaqiyat', 'O\'chirildi');
        yonaltir(SAYT_URL . '/admin/promo.php');
    }

    // ----- KAMPANIYALAR -----
    if (in_array($harakat, ['kamp_yaratish', 'kamp_tahrirlash'], true)) {
        $nomi = post('nomi');
        $tavsif = post('tavsif');
        $foiz = max(0, min(100, (int) post('chegirma_foiz')));
        $summa = (float) post('chegirma_summa');
        $tarif_id = (int) post('tarif_id') ?: null;
        $boshlanish = post('boshlanish') ?: date('Y-m-d H:i:s');
        $tugash = post('tugash');
        $holat = post('holat') === 'nofaol' ? 'nofaol' : 'faol';

        if (!$nomi || !$tugash) {
            flash_qoy('xato', t('kerakli_maydon'));
        } elseif ($id) {
            db_bajar(
                'UPDATE promo_kampaniyalar SET nomi=?, tavsif=?, chegirma_foiz=?, chegirma_summa=?, tarif_id=?, boshlanish=?, tugash=?, holat=? WHERE id=?',
                [$nomi, $tavsif, $foiz, $summa, $tarif_id, $boshlanish, $tugash, $holat, $id]
            );
            flash_qoy('muvaffaqiyat', "Yangilandi");
        } else {
            $yangi = db_bajar(
                'INSERT INTO promo_kampaniyalar (nomi, tavsif, chegirma_foiz, chegirma_summa, tarif_id, boshlanish, tugash, holat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$nomi, $tavsif, $foiz, $summa, $tarif_id, $boshlanish, $tugash, $holat]
            );
            audit_yoz($f['id'], 'kampaniya_yaratish', 'promo_kampaniyalar', $yangi, "Nomi: $nomi");
            flash_qoy('muvaffaqiyat', "Kampaniya yaratildi");
        }
        yonaltir(SAYT_URL . '/admin/promo.php');
    }

    if ($harakat === 'kamp_ochirish') {
        db_bajar('DELETE FROM promo_kampaniyalar WHERE id = ?', [$id]);
        audit_yoz($f['id'], 'kampaniya_ochirish', 'promo_kampaniyalar', $id);
        flash_qoy('muvaffaqiyat', 'O\'chirildi');
        yonaltir(SAYT_URL . '/admin/promo.php');
    }
}

$kodlar = db_barcha('SELECT * FROM promo_kodlar ORDER BY yaratilgan DESC');
$kampaniyalar = db_barcha('SELECT k.*, t.nomi AS tarif_nomi FROM promo_kampaniyalar k LEFT JOIN tariflar t ON k.tarif_id = t.id ORDER BY k.yaratilgan DESC');
$tariflar = db_barcha('SELECT id, nomi FROM tariflar WHERE holat = "faol"');

$tahrir_kod = olish('kod_tahrir') ? db_qator('SELECT * FROM promo_kodlar WHERE id = ?', [(int) olish('kod_tahrir')]) : null;
$tahrir_kamp = olish('kamp_tahrir') ? db_qator('SELECT * FROM promo_kampaniyalar WHERE id = ?', [(int) olish('kamp_tahrir')]) : null;

$admin_sahifa = 'promo';
$sahifa_sarlavha = 'Promo va kampaniyalar';
require_once __DIR__ . '/_layout.php';
?>

<div x-data="{tab: '<?= $tahrir_kamp ? 'kamp' : 'kod' ?>'}">
    <!-- Tablar -->
    <div class="inline-flex p-1 bg-white rounded-2xl border border-brand-border shadow-soft mb-6">
        <button @click="tab = 'kod'" :class="tab === 'kod' ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition">🎟 Promo kodlar (<?= count($kodlar) ?>)</button>
        <button @click="tab = 'kamp'" :class="tab === 'kamp' ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition">🎯 Kampaniyalar (<?= count($kampaniyalar) ?>)</button>
    </div>

    <!-- ============ PROMO KODLAR ============ -->
    <div x-show="tab === 'kod'">
        <form method="POST" class="glass-card p-5 mb-5 fade-up" x-data="{open: <?= $tahrir_kod ? 'true' : 'false' ?>}">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="<?= $tahrir_kod ? 'kod_tahrirlash' : 'kod_yaratish' ?>">
            <input type="hidden" name="id" value="<?= (int) ($tahrir_kod['id'] ?? 0) ?>">

            <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                <h2 class="font-display font-bold text-lg text-brand-text">
                    <?= $tahrir_kod ? '✏️ Kodni tahrirlash' : '➕ Yangi promo kod' ?>
                </h2>
                <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>

            <div x-show="open" x-transition class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Kod (A-Z, 0-9) *</label>
                    <input name="kod" required pattern="[A-Za-z0-9]+" minlength="3" maxlength="20" value="<?= e($tahrir_kod['kod'] ?? '') ?>" placeholder="YANGI2026" class="field uppercase font-mono">
                </div>
                <div>
                    <label class="field-label">Chegirma %</label>
                    <input type="number" name="chegirma_foiz" min="0" max="100" value="<?= e($tahrir_kod['chegirma_foiz'] ?? 0) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Yoki summa (so'm)</label>
                    <input type="number" name="chegirma_summa" min="0" step="500" value="<?= e($tahrir_kod['chegirma_summa'] ?? 0) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Maks ishlatish</label>
                    <input type="number" name="maks_ishlatish" min="1" value="<?= e($tahrir_kod['maks_ishlatish'] ?? 1) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Tugash sanasi</label>
                    <input type="datetime-local" name="tugash_sanasi" value="<?= e($tahrir_kod['tugash_sanasi'] ? date('Y-m-d\TH:i', strtotime($tahrir_kod['tugash_sanasi'])) : '') ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Holat</label>
                    <select name="holat" class="field">
                        <option value="faol" <?= ($tahrir_kod['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                        <option value="nofaol" <?= ($tahrir_kod['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3 flex gap-3">
                    <button class="btn-primary"><?= e(t('saqlash')) ?></button>
                    <?php if ($tahrir_kod): ?><a href="?" class="btn-ghost">Bekor qilish</a><?php endif; ?>
                </div>
            </div>
        </form>

        <div class="glass-card p-4 fade-up overflow-x-auto">
            <?php if (empty($kodlar)): ?>
                <p class="text-center text-brand-muted py-8">Hali promo kodlar yo'q</p>
            <?php else: ?>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-brand-muted text-xs uppercase">
                            <th class="py-2 pr-3">Kod</th>
                            <th class="py-2 pr-3">Chegirma</th>
                            <th class="py-2 pr-3">Ishlatildi</th>
                            <th class="py-2 pr-3">Tugash</th>
                            <th class="py-2 pr-3">Holat</th>
                            <th class="py-2 text-right">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        <?php foreach ($kodlar as $k):
                            $tugagan = $k['tugash_sanasi'] && strtotime($k['tugash_sanasi']) < time();
                            $tugagan_ishlatish = (int)$k['ishlatilgan'] >= (int)$k['maks_ishlatish'];
                        ?>
                            <tr class="hover:bg-sky-50/30 <?= ($tugagan || $tugagan_ishlatish) ? 'opacity-60' : '' ?>">
                                <td class="py-2.5 pr-3 font-mono font-bold"><?= e($k['kod']) ?></td>
                                <td class="py-2.5 pr-3">
                                    <?php if ($k['chegirma_foiz'] > 0): ?>
                                        <strong class="text-emerald-700"><?= (int)$k['chegirma_foiz'] ?>%</strong>
                                    <?php else: ?>
                                        <strong class="text-emerald-700"><?= e(pul($k['chegirma_summa'])) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 pr-3 text-xs"><?= (int)$k['ishlatilgan'] ?> / <?= (int)$k['maks_ishlatish'] ?></td>
                                <td class="py-2.5 pr-3 text-xs text-brand-muted"><?= $k['tugash_sanasi'] ? e(sana($k['tugash_sanasi'], 'd.m.Y H:i')) : 'Cheksiz' ?></td>
                                <td class="py-2.5 pr-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $k['holat'] === 'faol' && !$tugagan && !$tugagan_ishlatish ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                        <?= $tugagan ? 'tugagan' : ($tugagan_ishlatish ? 'limit' : $k['holat']) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <a href="?kod_tahrir=<?= (int)$k['id'] ?>" class="text-amber-600 text-xs hover:underline mr-2">Tahrirlash</a>
                                    <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="kod_ochirish">
                                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                        <button class="text-rose-600 text-xs hover:underline">O'chirish</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ KAMPANIYALAR ============ -->
    <div x-show="tab === 'kamp'" x-cloak>
        <form method="POST" class="glass-card p-5 mb-5 fade-up" x-data="{open: <?= $tahrir_kamp ? 'true' : 'false' ?>}">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="<?= $tahrir_kamp ? 'kamp_tahrirlash' : 'kamp_yaratish' ?>">
            <input type="hidden" name="id" value="<?= (int) ($tahrir_kamp['id'] ?? 0) ?>">

            <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                <h2 class="font-display font-bold text-lg text-brand-text">
                    <?= $tahrir_kamp ? '✏️ Kampaniyani tahrirlash' : '➕ Yangi kampaniya' ?>
                </h2>
                <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>

            <div x-show="open" x-transition class="mt-4 grid sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="field-label">Nomi *</label>
                    <input name="nomi" required value="<?= e($tahrir_kamp['nomi'] ?? '') ?>" placeholder="Bayram chegirmasi" class="field">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Tavsif</label>
                    <textarea name="tavsif" rows="2" class="field"><?= e($tahrir_kamp['tavsif'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="field-label">Tarif (ixtiyoriy — bo'sh bo'lsa hammasiga)</label>
                    <select name="tarif_id" class="field">
                        <option value="0">— Hammasiga —</option>
                        <?php foreach ($tariflar as $tar): ?>
                            <option value="<?= (int)$tar['id'] ?>" <?= ($tahrir_kamp['tarif_id'] ?? 0) === $tar['id'] ? 'selected' : '' ?>><?= e($tar['nomi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label">Holat</label>
                    <select name="holat" class="field">
                        <option value="faol" <?= ($tahrir_kamp['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                        <option value="nofaol" <?= ($tahrir_kamp['holat'] ?? '') === 'nofaol' ? 'selected' : '' ?>>Nofaol</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Chegirma %</label>
                    <input type="number" name="chegirma_foiz" min="0" max="100" value="<?= e($tahrir_kamp['chegirma_foiz'] ?? 0) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Yoki summa (so'm)</label>
                    <input type="number" name="chegirma_summa" min="0" step="500" value="<?= e($tahrir_kamp['chegirma_summa'] ?? 0) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Boshlanish</label>
                    <input type="datetime-local" name="boshlanish" value="<?= e($tahrir_kamp['boshlanish'] ? date('Y-m-d\TH:i', strtotime($tahrir_kamp['boshlanish'])) : date('Y-m-d\TH:i')) ?>" class="field">
                </div>
                <div>
                    <label class="field-label">Tugash *</label>
                    <input type="datetime-local" name="tugash" required value="<?= e($tahrir_kamp['tugash'] ? date('Y-m-d\TH:i', strtotime($tahrir_kamp['tugash'])) : '') ?>" class="field">
                </div>
                <div class="sm:col-span-2 flex gap-3">
                    <button class="btn-primary"><?= e(t('saqlash')) ?></button>
                    <?php if ($tahrir_kamp): ?><a href="?" class="btn-ghost">Bekor qilish</a><?php endif; ?>
                </div>
            </div>
        </form>

        <div class="grid sm:grid-cols-2 gap-4">
            <?php foreach ($kampaniyalar as $kk):
                $tugagan = strtotime($kk['tugash']) < time();
            ?>
                <div class="glass-card p-5 <?= $tugagan ? 'opacity-60' : '' ?>">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-display font-bold text-brand-text"><?= e($kk['nomi']) ?></h3>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $kk['holat'] === 'faol' && !$tugagan ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                            <?= $tugagan ? 'tugagan' : $kk['holat'] ?>
                        </span>
                    </div>
                    <p class="text-sm text-brand-muted mb-3"><?= e($kk['tavsif']) ?></p>
                    <div class="text-2xl font-display font-bold text-emerald-600 mb-2">
                        <?= $kk['chegirma_foiz'] > 0 ? '-' . (int)$kk['chegirma_foiz'] . '%' : '-' . pul($kk['chegirma_summa']) ?>
                    </div>
                    <div class="text-xs text-brand-muted mb-3">
                        <?= e($kk['tarif_nomi'] ?: 'Barcha tariflarga') ?> · <?= e(sana($kk['boshlanish'], 'd.m')) ?> – <?= e(sana($kk['tugash'], 'd.m.Y')) ?>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <a href="?kamp_tahrir=<?= (int)$kk['id'] ?>" class="text-amber-600 hover:underline">Tahrirlash</a>
                        <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="kamp_ochirish">
                            <input type="hidden" name="id" value="<?= (int)$kk['id'] ?>">
                            <button class="text-rose-600 hover:underline">O'chirish</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($kampaniyalar)): ?>
                <p class="sm:col-span-2 text-center text-brand-muted py-8 glass-card">Hali kampaniyalar yo'q</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
