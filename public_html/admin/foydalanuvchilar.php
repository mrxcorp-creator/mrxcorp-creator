<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');
    $maqsad = $id ? db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$id]) : null;

    if ($maqsad) {
        if ($harakat === 'block') {
            $yangi = $maqsad['holat'] === 'faol' ? 'bloklangan' : 'faol';
            db_bajar('UPDATE foydalanuvchilar SET holat = ? WHERE id = ?', [$yangi, $id]);
            audit_yoz('foydalanuvchi_' . $yangi, 'foydalanuvchi', $id);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        if ($harakat === 'rol' && $f['rol'] === 'developer' && $maqsad['id'] !== $f['id']) {
            $rol = post('rol');
            if (in_array($rol, ['user', 'admin', 'developer'], true)) {
                db_bajar('UPDATE foydalanuvchilar SET rol = ? WHERE id = ?', [$rol, $id]);
                audit_yoz('rol_ozgartirildi', 'foydalanuvchi', $id, [
                    'eski' => $maqsad['rol'], 'yangi' => $rol,
                ]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            }
        }
        if ($harakat === 'bonus') {
            $bonus = (float) post('bonus');
            if ($bonus != 0) {
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?', [$bonus, $id]);
                bonus_yoz($id, $bonus, 'admin', 'Admin: ' . ($f['ism'] ?? 'admin'));
                audit_yoz('bonus_qoshildi', 'foydalanuvchi', $id, ['summa' => $bonus]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            }
        }
        if ($harakat === 'obuna') {
            $tarif_id = (int) post('tarif_id');
            $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tarif_id]);
            if ($tarif) {
                $kun = match ($tarif['tur']) {
                    'kun' => $tarif['qiymat'],
                    'oy'  => $tarif['qiymat'] * 30,
                    default => 365,
                };
                db()->beginTransaction();
                try {
                    $tolov_id = db_bajar(
                        'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                         VALUES (?, ?, ?, "manual", "muvaffaqiyatli", ?)',
                        [$id, $tarif_id, $tarif['narx'], "Admin: " . $f['ism']]
                    );
                    db_bajar(
                        'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, tolov_id, boshlanish, tugash, holat)
                         VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                        [$id, $tarif_id, $tolov_id, $kun]
                    );
                    audit_yoz('obuna_qolda_berildi', 'foydalanuvchi', (int) $id, [
                        'tarif' => $tarif['nomi'],
                        'kun' => $kun,
                    ]);
                    db()->commit();
                    if ($maqsad['telegram_id']) {
                        telegram_yubor($maqsad['telegram_id'],
                            "🎉 <b>Sizga obuna sovg'a qilindi!</b>\nTarif: <b>{$tarif['nomi']}</b>");
                    }
                    flash_qoy('muvaffaqiyat', 'Obuna qo\'shildi');
                } catch (Exception $exc) {
                    db()->rollBack();
                    flash_qoy('xato', 'Xato: ' . $exc->getMessage());
                }
            }
        }
    }
    yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php');
}

$qidiruv = olish('q');
$rol_filt = olish('rol');
$shartlar = [];
$params = [];
if ($qidiruv) {
    $shartlar[] = '(ism LIKE ? OR familiya LIKE ? OR telefon LIKE ?)';
    $params = ["%$qidiruv%", "%$qidiruv%", "%$qidiruv%"];
}
if ($rol_filt && in_array($rol_filt, ['user', 'admin', 'developer'], true)) {
    $shartlar[] = 'rol = ?';
    $params[] = $rol_filt;
}
$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

$jami = (int) db_qiymat("SELECT COUNT(*) FROM foydalanuvchilar $where", $params);
$sahifa = max(1, (int) olish('p'));
$limit = 20;
$offset = ($sahifa - 1) * $limit;

$royxat = db_barcha(
    "SELECT * FROM foydalanuvchilar $where ORDER BY id DESC LIMIT $limit OFFSET $offset",
    $params
);

$tariflar = db_barcha('SELECT id, nomi, narx FROM tariflar WHERE holat = "faol"');

$admin_sahifa = 'foydalanuvchilar';
$sahifa_sarlavha = 'Foydalanuvchilar';
require_once __DIR__ . '/_layout.php';
?>

<form method="GET" class="grid sm:grid-cols-3 gap-3 mb-4">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Ism, familiya yoki telefon..." class="field sm:col-span-2">
    <select name="rol" onchange="this.form.submit()" class="field">
        <option value="">Barcha rollar</option>
        <option value="user" <?= $rol_filt === 'user' ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= $rol_filt === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="developer" <?= $rol_filt === 'developer' ? 'selected' : '' ?>>Developer</option>
    </select>
</form>

<div class="glass p-5 fade-up">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-display font-bold text-lg">Jami: <span class="grad-text"><?= $jami ?></span></h2>
    </div>

    <?php if (empty($royxat)): ?>
        <p class="text-center py-8 text-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Ism</th>
                        <th class="py-2 pr-3">Telefon</th>
                        <th class="py-2 pr-3">Rol</th>
                        <th class="py-2 pr-3">Bonus</th>
                        <th class="py-2 pr-3">Holat</th>
                        <th class="py-2 pr-3">Sana</th>
                        <th class="py-2 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($royxat as $u):
                        $obuna_son = (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE foydalanuvchi_id = ? AND holat = "faol" AND tugash > NOW()', [$u['id']]);
                    ?>
                        <tr class="hover:bg-white/3" x-data="{menu: false}">
                            <td class="py-2.5 pr-3 font-mono text-xs"><?= (int)$u['id'] ?></td>
                            <td class="py-2.5 pr-3">
                                <div class="font-medium"><?= e($u['ism']) ?> <?= e($u['familiya'] ?? '') ?></div>
                                <?php if ($obuna_son): ?>
                                    <span class="text-xs text-amber">⚡ Faol obuna</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 pr-3 font-mono text-xs"><?= e($u['telefon']) ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2
                                    <?= $u['rol'] === 'developer' ? 'bg-violet/15 text-violet border-violet/30' :
                                       ($u['rol'] === 'admin' ? 'bg-cyan/15 text-cyan border-cyan/30' : '') ?>">
                                    <?= e($u['rol']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 pr-3"><?= e(pul($u['bonus_balans'])) ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2 <?= $u['holat'] === 'faol' ? 'bg-success/15 text-success border-success/30' : 'bg-danger/15 text-danger border-danger/30' ?>">
                                    <?= e($u['holat']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 text-xs text-muted"><?= e(sana($u['yaratilgan'], 'd.m.Y')) ?></td>
                            <td class="py-2.5 text-right relative">
                                <button @click="menu = !menu" class="px-2 py-1 rounded hover:bg-white/10">⋯</button>
                                <div x-show="menu" @click.outside="menu=false" x-cloak
                                     class="absolute right-0 top-full mt-1 z-20 w-60 glass-strong p-2 text-left">
                                    <details class="text-sm">
                                        <summary class="px-3 py-2 rounded hover:bg-white/5 cursor-pointer">🎁 Obuna berish</summary>
                                        <form method="POST" class="p-2 space-y-2">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="obuna">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <select name="tarif_id" required class="field text-xs">
                                                <option value="">— tarif —</option>
                                                <?php foreach ($tariflar as $tar): ?>
                                                    <option value="<?= (int)$tar['id'] ?>"><?= e($tar['nomi']) ?> (<?= e(pul($tar['narx'])) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-primary w-full text-xs py-1.5">Berish</button>
                                        </form>
                                    </details>

                                    <details class="text-sm">
                                        <summary class="px-3 py-2 rounded hover:bg-white/5 cursor-pointer">💰 Bonus qo'shish</summary>
                                        <form method="POST" class="p-2 space-y-2">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="bonus">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="number" name="bonus" required step="100" placeholder="5000" class="field text-xs">
                                            <button class="btn btn-primary w-full text-xs py-1.5">Qo'shish</button>
                                        </form>
                                    </details>

                                    <form method="POST" class="px-3 py-2 hover:bg-white/5">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="block">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="text-left w-full text-<?= $u['holat'] === 'faol' ? 'danger' : 'success' ?> text-sm">
                                            <?= $u['holat'] === 'faol' ? '🚫 Bloklash' : '✓ Faollashtirish' ?>
                                        </button>
                                    </form>

                                    <?php if ($f['rol'] === 'developer' && $u['id'] !== $f['id']): ?>
                                        <details class="text-sm">
                                            <summary class="px-3 py-2 rounded hover:bg-white/5 cursor-pointer">🔧 Rol</summary>
                                            <form method="POST" class="p-2 space-y-2">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="harakat" value="rol">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <select name="rol" class="field text-xs">
                                                    <option value="user" <?= $u['rol'] === 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="developer" <?= $u['rol'] === 'developer' ? 'selected' : '' ?>>Developer</option>
                                                </select>
                                                <button class="btn btn-primary w-full text-xs py-1.5">Saqlash</button>
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

        <?php $jami_sahifa = (int) ceil($jami / $limit); if ($jami_sahifa > 1): ?>
            <div class="flex justify-center gap-1 mt-4 flex-wrap">
                <?php for ($i = 1; $i <= $jami_sahifa; $i++):
                    if ($i > 4 && $i < $jami_sahifa - 2 && abs($i - $sahifa) > 1) continue;
                ?>
                    <a href="?p=<?= $i ?>&q=<?= e($qidiruv) ?>&rol=<?= e($rol_filt) ?>"
                       class="px-3 py-1.5 rounded-lg text-sm <?= $i === $sahifa ? 'grad-bg text-white font-bold' : 'bg-white/5 hover:bg-white/10' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
