<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/promokodlar.php'); }
    $harakat = post('harakat');
    $id      = (int) post('id');

    if (in_array($harakat, ['yaratish','tahrirlash'], true)) {
        $kod    = strtoupper(preg_replace('/[^A-Z0-9]/i', '', post('kod')));
        $cf     = max(0, min(100, (int) post('chegirma_foiz')));
        $cs     = max(0, (float) post('chegirma_summa'));
        $maks   = max(1, (int) post('maks_ishlatish') ?: 100);
        $tugash = post('tugash_sanasi') ?: null;
        $holat  = post('holat') === 'nofaol' ? 'nofaol' : 'faol';
        if (!$kod || mb_strlen($kod) < 3) {
            flash_qoy('xato', "Kod kamida 3 belgi bo'lishi kerak");
        } elseif ($cf === 0 && $cs === 0.0) {
            flash_qoy('xato', 'Chegirma kiritilmagan');
        } else {
            if ($id) {
                db_bajar('UPDATE promo_kodlar SET kod=?,chegirma_foiz=?,chegirma_summa=?,maks_ishlatish=?,tugash_sanasi=?,holat=? WHERE id=?',
                         [$kod,$cf,$cs,$maks,$tugash,$holat,$id]);
            } else {
                if (db_qiymat('SELECT 1 FROM promo_kodlar WHERE kod=?', [$kod])) {
                    flash_qoy('xato', "Kod allaqachon mavjud"); yonaltir(SAYT_URL.'/admin/promokodlar.php');
                }
                db_bajar('INSERT INTO promo_kodlar (kod,chegirma_foiz,chegirma_summa,maks_ishlatish,tugash_sanasi,holat) VALUES (?,?,?,?,?,?)',
                         [$kod,$cf,$cs,$maks,$tugash,$holat]);
            }
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        yonaltir(SAYT_URL . '/admin/promokodlar.php');
    }
    if ($harakat === 'toggle_holat') {
        $p = db_qator('SELECT * FROM promo_kodlar WHERE id=?', [$id]);
        if ($p) {
            $yangi = $p['holat'] === 'faol' ? 'nofaol' : 'faol';
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

$tahrir = olish('tahrir') ? db_qator('SELECT * FROM promo_kodlar WHERE id=?', [(int)olish('tahrir')]) : null;
$kodlar = db_barcha('SELECT * FROM promo_kodlar ORDER BY yaratilgan DESC');

$admin_sahifa    = 'promokodlar';
$sahifa_sarlavha = 'Promo kodlar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="b-card"
      style="padding:1.25rem; margin-bottom:1.25rem;"
      x-data="{ open: <?= $tahrir ? 'true' : 'false' ?> }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="<?= $tahrir ? 'tahrirlash' : 'yaratish' ?>">
    <input type="hidden" name="id" value="<?= (int)($tahrir['id'] ?? 0) ?>">

    <div @click="open = !open" style="display:flex; justify-content:space-between; cursor:pointer;">
        <h2 style="font-family:Georgia,serif; font-weight:700; font-size:1.05rem;">
            <?= $tahrir ? 'Promo tahrirlash' : '+ Yangi promo' ?>
        </h2>
        <span x-text="open ? '−' : '+'" style="font-size:1.25rem;"></span>
    </div>

    <div x-show="open" x-transition style="margin-top:1.25rem;
            display:grid; grid-template-columns:1fr; gap:.75rem;" class="md:grid-cols-3">
        <div>
            <label class="field-label">Kod *</label>
            <input name="kod" required value="<?= e($tahrir['kod'] ?? '') ?>"
                   class="field" style="text-transform:uppercase; letter-spacing:.1em; font-family:monospace;"
                   maxlength="30" placeholder="YAZGI2025">
        </div>
        <div>
            <label class="field-label">Chegirma % (yoki summa)</label>
            <input type="number" name="chegirma_foiz" min="0" max="100"
                   value="<?= e($tahrir['chegirma_foiz'] ?? 0) ?>" class="field" placeholder="20">
        </div>
        <div>
            <label class="field-label">Chegirma summa (so'm)</label>
            <input type="number" name="chegirma_summa" min="0" step="100"
                   value="<?= e($tahrir['chegirma_summa'] ?? 0) ?>" class="field" placeholder="5000">
        </div>
        <div>
            <label class="field-label">Maks. ishlatish</label>
            <input type="number" name="maks_ishlatish" min="1"
                   value="<?= e($tahrir['maks_ishlatish'] ?? 100) ?>" class="field">
        </div>
        <div>
            <label class="field-label">Tugash sanasi</label>
            <input type="datetime-local" name="tugash_sanasi"
                   value="<?= $tahrir['tugash_sanasi'] ? date('Y-m-d\TH:i', strtotime($tahrir['tugash_sanasi'])) : '' ?>"
                   class="field">
        </div>
        <div>
            <label class="field-label">Holat</label>
            <select name="holat" class="field">
                <option value="faol"   <?= ($tahrir['holat']??'faol')==='faol' ? 'selected' : '' ?>>Faol</option>
                <option value="nofaol" <?= ($tahrir['holat']??'')==='nofaol' ? 'selected' : '' ?>>Nofaol</option>
            </select>
        </div>
        <div class="md:col-span-3" style="display:flex; gap:.5rem;">
            <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            <?php if ($tahrir): ?><a href="?" class="btn"><?= e(t('bekor_qilish')) ?></a><?php endif; ?>
        </div>
    </div>
</form>

<div class="b-card">
    <?php if (empty($kodlar)): ?>
    <div style="padding:3rem; text-align:center; color:#666;">Hali promo kod yo'q</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="b-table">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Chegirma</th>
                    <th>Foydalanish</th>
                    <th>Tugash</th>
                    <th>Holat</th>
                    <th style="text-align:right;">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kodlar as $k):
                    $tugadi = $k['tugash_sanasi'] && strtotime($k['tugash_sanasi']) < time();
                ?>
                <tr style="<?= $tugadi ? 'opacity:.5;' : '' ?>">
                    <td style="font-family:monospace; font-weight:700; letter-spacing:.05em;">
                        <?= e($k['kod']) ?>
                    </td>
                    <td>
                        <?php if ((float)$k['chegirma_foiz'] > 0): ?>
                        <span class="badge badge-filled"><?= (int)$k['chegirma_foiz'] ?>%</span>
                        <?php endif; ?>
                        <?php if ((float)$k['chegirma_summa'] > 0): ?>
                        <span class="badge"><?= e(pul($k['chegirma_summa'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="tabnum">
                        <strong><?= (int)$k['ishlatilgan'] ?></strong>
                        <span style="color:#666;">/ <?= (int)$k['maks_ishlatish'] ?></span>
                    </td>
                    <td style="font-size:.78rem; color:#666;">
                        <?= $k['tugash_sanasi'] ? e(sana($k['tugash_sanasi'], 'd.m.Y')) : '—' ?>
                    </td>
                    <td>
                        <span class="badge <?= $k['holat']==='faol' ? '' : 'badge-filled' ?>">
                            <?= e($k['holat']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <a href="?tahrir=<?= (int)$k['id'] ?>" class="btn btn-xs">Tahrir</a>
                        <form method="POST" style="display:inline;">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="toggle_holat">
                            <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                            <button class="btn btn-xs">
                                <?= $k['holat']==='faol' ? 'Off' : 'On' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('O\'chirilsinmi?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="ochirish">
                            <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                            <button class="btn btn-xs btn-danger">×</button>
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
