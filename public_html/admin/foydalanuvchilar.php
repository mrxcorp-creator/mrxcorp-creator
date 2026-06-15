<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php');
    }
    $harakat = post('harakat');
    $id      = (int) post('id');
    $maqsad  = $id ? db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$id]) : null;

    if ($maqsad) {
        if ($harakat === 'block') {
            $yangi = $maqsad['holat'] === 'faol' ? 'bloklangan' : 'faol';
            db_bajar('UPDATE foydalanuvchilar SET holat = ? WHERE id = ?', [$yangi, $id]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        if ($harakat === 'rol' && $f['rol'] === 'developer') {
            $rol = post('rol');
            if (in_array($rol, ['user','admin','developer'], true) && $id !== $f['id']) {
                db_bajar('UPDATE foydalanuvchilar SET rol = ? WHERE id = ?', [$rol, $id]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            }
        }
        if ($harakat === 'bonus') {
            $bonus = (float) post('bonus');
            if ($bonus > 0) {
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?', [$bonus, $id]);
                flash_qoy('muvaffaqiyat', 'Bonus qo\'shildi: ' . pul($bonus));
            }
        }
        if ($harakat === 'obuna') {
            $tarif_id = (int) post('tarif_id');
            $tarif    = $tarif_id ? db_qator('SELECT * FROM tariflar WHERE id = ?', [$tarif_id]) : null;
            if ($tarif) {
                $kun = match ($tarif['tur']) {
                    'kun' => (int)$tarif['qiymat'],
                    'oy'  => (int)$tarif['qiymat'] * 30,
                    default => 365,
                };
                db_bajar(
                    'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
                     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                    [$id, $tarif_id, $kun]
                );
                db_bajar(
                    'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, "manual", "muvaffaqiyatli", ?)',
                    [$id, $tarif_id, $tarif['narx'], 'Admin: ' . $f['ism']]
                );
                if ($maqsad['telegram_id']) {
                    telegram_yubor((int)$maqsad['telegram_id'],
                        "✓ Sizga obuna sovg'a qilindi!\nTarif: <b>{$tarif['nomi']}</b>");
                }
                flash_qoy('muvaffaqiyat', 'Obuna qo\'shildi');
            }
        }
    }
    yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php?' . http_build_query([
        'q' => olish('q'), 'rol' => olish('rol'), 'p' => olish('p'),
    ]));
}

$qidiruv  = olish('q');
$rol_filt = olish('rol');
$shartlar = []; $params = [];
if ($qidiruv) {
    $shartlar[] = '(fo.ism LIKE ? OR fo.familiya LIKE ? OR fo.telefon LIKE ?)';
    array_push($params, "%{$qidiruv}%", "%{$qidiruv}%", "%{$qidiruv}%");
}
if ($rol_filt && in_array($rol_filt, ['user','admin','developer'], true)) {
    $shartlar[] = 'fo.rol = ?'; $params[] = $rol_filt;
}
$where = $shartlar ? 'WHERE ' . implode(' AND ', $shartlar) : '';
$jami   = (int) db_qiymat("SELECT COUNT(*) FROM foydalanuvchilar fo {$where}", $params);
$sahifa = max(1, (int) olish('p'));
$limit  = 25;
$offset = ($sahifa - 1) * $limit;

$royxat = db_barcha(
    "SELECT fo.*,
        (SELECT COUNT(*) FROM obunalar
         WHERE foydalanuvchi_id = fo.id AND holat = 'faol' AND tugash > NOW()) AS obuna_faol,
        (SELECT COUNT(*) FROM natijalar
         WHERE foydalanuvchi_id = fo.id AND holat = 'tugagan') AS test_son
     FROM foydalanuvchilar fo {$where}
     ORDER BY fo.id DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);

$tariflar = db_barcha('SELECT id, nomi, narx FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');

$admin_sahifa    = 'foydalanuvchilar';
$sahifa_sarlavha = 'Foydalanuvchilar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Filtr -->
<form method="GET" class="b-card"
      style="padding:1rem; margin-bottom:1.25rem;
             display:grid; grid-template-columns:1fr; gap:.75rem;"
      class="md:grid-cols-12">
    <div class="md:col-span-7">
        <input name="q" value="<?= e($qidiruv) ?>"
               placeholder="Ism, familiya yoki telefon..." class="field">
    </div>
    <div class="md:col-span-3">
        <select name="rol" onchange="this.form.submit()" class="field">
            <option value="">Barcha rollar</option>
            <?php foreach (['user'=>'User','admin'=>'Admin','developer'=>'Developer'] as $v=>$n): ?>
            <option value="<?= $v ?>" <?= $rol_filt === $v ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="md:col-span-2">
        <button type="submit" class="btn btn-primary" style="width:100%;">Qidirish</button>
    </div>
</form>

<!-- Jadval -->
<div class="b-card">
    <div style="padding:.85rem 1.25rem; border-bottom:1px solid #000;
                display:flex; justify-content:space-between; align-items:center;
                flex-wrap:wrap; gap:.5rem;">
        <div style="font-size:.875rem;">
            Jami: <strong class="tabnum"><?= $jami ?></strong>
            <?php if ($qidiruv || $rol_filt): ?>
            <span style="color:#666;">— filtr</span>
            <?php endif; ?>
        </div>
        <?php if ($qidiruv || $rol_filt): ?>
        <a href="<?= e(SAYT_URL) ?>/admin/foydalanuvchilar.php"
           class="btn btn-xs">✕ Tozalash</a>
        <?php endif; ?>
    </div>

    <?php if (empty($royxat)): ?>
    <div style="padding: 3rem; text-align:center; color:#666;">
        <?= e(t('malumot_yoq')) ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="b-table" style="min-width:800px;">
            <thead>
                <tr>
                    <th>Foydalanuvchi</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Obuna</th>
                    <th>Testlar</th>
                    <th>Bonus</th>
                    <th>Holat</th>
                    <th style="text-align:right;">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($royxat as $u): ?>
                <tr x-data="{ menu: false }">
                    <td>
                        <strong><?= e($u['ism']) ?> <?= e($u['familiya'] ?? '') ?></strong>
                        <div style="font-size:.7rem; color:#666; margin-top:.15rem;">
                            ID: <?= (int)$u['id'] ?>
                        </div>
                    </td>
                    <td style="font-family:monospace; font-size:.78rem;">
                        <?= e($u['telefon']) ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['rol']==='user' ? '' : 'badge-filled' ?>">
                            <?= e($u['rol']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ((int)$u['obuna_faol'] > 0): ?>
                        <span class="badge badge-filled">Faol</span>
                        <?php else: ?>
                        <span style="color:#999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="tabnum"><?= (int)$u['test_son'] ?></td>
                    <td class="tabnum">
                        <?php if ((float)$u['bonus_balans'] > 0): ?>
                        <strong><?= e(pul($u['bonus_balans'])) ?></strong>
                        <?php else: ?>
                        <span style="color:#999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['holat']==='faol' ? '' : 'badge-filled' ?>">
                            <?= e($u['holat']) ?>
                        </span>
                    </td>
                    <td style="text-align:right; position:relative;">
                        <button @click="menu = !menu" class="btn btn-xs">⋯</button>
                        <div x-show="menu" @click.outside="menu = false"
                             style="position:absolute; right:1rem; top:100%; z-index:30;
                                    width:240px; background:#fff; border:1px solid #000;
                                    text-align:left;"
                             x-cloak>
                            <details>
                                <summary style="padding:.6rem .9rem; cursor:pointer; font-size:.85rem;
                                                border-bottom:1px solid #E5E5E5; user-select:none;">
                                    Obuna berish
                                </summary>
                                <form method="POST" style="padding:.65rem; border-bottom:1px solid #E5E5E5;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="obuna">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <select name="tarif_id" required class="field"
                                            style="font-size:.78rem; padding:.4rem; margin-bottom:.4rem;">
                                        <option value="">— tarif —</option>
                                        <?php foreach ($tariflar as $tar): ?>
                                        <option value="<?= (int)$tar['id'] ?>">
                                            <?= e($tar['nomi']) ?> (<?= e(pul($tar['narx'])) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary btn-xs" style="width:100%;">Berish</button>
                                </form>
                            </details>

                            <details>
                                <summary style="padding:.6rem .9rem; cursor:pointer; font-size:.85rem;
                                                border-bottom:1px solid #E5E5E5; user-select:none;">
                                    Bonus qo'shish
                                </summary>
                                <form method="POST" style="padding:.65rem; border-bottom:1px solid #E5E5E5;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="bonus">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="number" name="bonus" required min="0" step="1000"
                                           placeholder="5000" class="field"
                                           style="font-size:.78rem; padding:.4rem; margin-bottom:.4rem;">
                                    <button class="btn btn-primary btn-xs" style="width:100%;">Qo'shish</button>
                                </form>
                            </details>

                            <form method="POST" style="border-bottom:1px solid #E5E5E5;">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="block">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button style="display:block; width:100%; text-align:left;
                                               padding:.6rem .9rem; background:none; border:none;
                                               cursor:pointer; font-family:inherit; font-size:.85rem;"
                                        onmouseover="this.style.background='#F5F5F5'"
                                        onmouseout="this.style.background='transparent'">
                                    <?= $u['holat']==='faol' ? '⊘ Bloklash' : '✓ Faollashtirish' ?>
                                </button>
                            </form>

                            <?php if ($f['rol'] === 'developer' && $u['id'] !== $f['id']): ?>
                            <details>
                                <summary style="padding:.6rem .9rem; cursor:pointer; font-size:.85rem;
                                                user-select:none;">
                                    Rol o'zgartirish
                                </summary>
                                <form method="POST" style="padding:.65rem;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="rol">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <select name="rol" class="field"
                                            style="font-size:.78rem; padding:.4rem; margin-bottom:.4rem;">
                                        <?php foreach (['user','admin','developer'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['rol']===$r?'selected':'' ?>>
                                            <?= ucfirst($r) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary btn-xs" style="width:100%;">Saqlash</button>
                                </form>
                            </details>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sahifalash -->
    <?php $jami_sah = (int) ceil($jami / $limit); if ($jami_sah > 1): ?>
    <div style="padding:.85rem 1.25rem; border-top:1px solid #000;
                display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:.5rem; font-size:.78rem;">
        <span style="color:#666;">
            <?= ($offset+1) ?>–<?= min($offset+$limit, $jami) ?> / <?= $jami ?>
        </span>
        <div style="display:flex; gap:.25rem; flex-wrap:wrap;">
            <?php
            $show = [];
            for ($i = 1; $i <= $jami_sah; $i++) {
                if ($i === 1 || $i === $jami_sah || abs($i - $sahifa) <= 2) $show[] = $i;
            }
            $prev = 0;
            foreach ($show as $i):
                if ($prev && $i - $prev > 1): ?>
                <span style="padding:.3rem .55rem; color:#999;">…</span>
                <?php endif; ?>
                <a href="?p=<?= $i ?>&q=<?= urlencode($qidiruv) ?>&rol=<?= urlencode($rol_filt) ?>"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          min-width:30px; padding:.3rem .55rem; border:1px solid #000;
                          text-decoration:none;
                          background: <?= $i === $sahifa ? '#000' : '#fff' ?>;
                          color: <?= $i === $sahifa ? '#fff' : '#000' ?>;"
                   class="tabnum">
                    <?= $i ?>
                </a>
            <?php $prev = $i; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
