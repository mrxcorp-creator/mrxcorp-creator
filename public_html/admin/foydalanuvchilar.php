<?php
/**
 * AvtoTest Pro — Foydalanuvchilar boshqaruvi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php'); }

    $harakat = post('harakat');
    $id      = (int) post('id');
    $maqsad  = $id ? db_qator('SELECT * FROM foydalanuvchilar WHERE id=?', [$id]) : null;

    if ($maqsad) {
        if ($harakat === 'block') {
            $yangi = $maqsad['holat'] === 'faol' ? 'bloklangan' : 'faol';
            db_bajar('UPDATE foydalanuvchilar SET holat=? WHERE id=?', [$yangi, $id]);
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        }
        if ($harakat === 'rol' && $f['rol'] === 'developer') {
            $rol = post('rol');
            if (in_array($rol, ['user','admin','developer'], true)) {
                db_bajar('UPDATE foydalanuvchilar SET rol=? WHERE id=?', [$rol, $id]);
                flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
            }
        }
        if ($harakat === 'bonus') {
            $bonus = (float) post('bonus');
            if ($bonus > 0) {
                db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id=?', [$bonus, $id]);
                flash_qoy('muvaffaqiyat', "Bonus qo'shildi: " . pul($bonus));
            }
        }
        if ($harakat === 'obuna') {
            $tarif_id = (int) post('tarif_id');
            $tarif    = $tarif_id ? db_qator('SELECT * FROM tariflar WHERE id=?', [$tarif_id]) : null;
            if ($tarif) {
                $kun = match($tarif['tur']) { 'kun' => (int)$tarif['qiymat'], 'oy' => (int)$tarif['qiymat'] * 30, default => 365 };
                db_bajar('INSERT INTO obunalar (foydalanuvchi_id,tarif_id,boshlanish,tugash,holat) VALUES (?,?,NOW(),DATE_ADD(NOW(),INTERVAL ? DAY),"faol")',
                         [$id, $tarif_id, $kun]);
                db_bajar('INSERT INTO tolovlar (foydalanuvchi_id,tarif_id,summa,tolov_turi,holat,izoh) VALUES (?,?,?,"manual","muvaffaqiyatli",?)',
                         [$id, $tarif_id, $tarif['narx'], "Admin: " . $f['ism']]);
                if ($maqsad['telegram_id']) {
                    telegram_yubor((int)$maqsad['telegram_id'],
                        "🎉 <b>Sizga obuna sovg'a qilindi!</b>\nTarif: <b>{$tarif['nomi']}</b>");
                }
                flash_qoy('muvaffaqiyat', "Obuna qo'shildi");
            }
        }
    }
    yonaltir(SAYT_URL . '/admin/foydalanuvchilar.php?' . http_build_query(['q' => olish('q'), 'rol' => olish('rol'), 'p' => olish('p')]));
}

$qidiruv   = olish('q');
$rol_filt  = olish('rol');
$shartlar  = [];
$params    = [];
if ($qidiruv) {
    $shartlar[] = '(ism LIKE ? OR familiya LIKE ? OR telefon LIKE ?)';
    array_push($params, "%{$qidiruv}%", "%{$qidiruv}%", "%{$qidiruv}%");
}
if ($rol_filt && in_array($rol_filt, ['user','admin','developer'], true)) {
    $shartlar[] = 'rol = ?'; $params[] = $rol_filt;
}
$where = $shartlar ? 'WHERE ' . implode(' AND ', $shartlar) : '';
$jami    = (int) db_qiymat("SELECT COUNT(*) FROM foydalanuvchilar {$where}", $params);
$sahifa  = max(1, (int) olish('p'));
$limit   = 25;
$offset  = ($sahifa - 1) * $limit;
$royxat  = db_barcha("SELECT * FROM foydalanuvchilar {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}", $params);
$tariflar = db_barcha('SELECT id, nomi, narx FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');

$admin_sahifa    = 'foydalanuvchilar';
$sahifa_sarlavha = 'Foydalanuvchilar';
require_once __DIR__ . '/_layout.php';
?>

<!-- Filtr -->
<form method="GET" class="glass-card p-4 mb-5 grid sm:grid-cols-3 gap-3 fade-up">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Ism, familiya yoki telefon..." class="field sm:col-span-2">
    <select name="rol" onchange="this.form.submit()" class="field">
        <option value="">Barcha rollar (<?= $jami ?>)</option>
        <?php foreach (['user'=>'User','admin'=>'Admin','developer'=>'Developer'] as $v=>$n): ?>
            <option value="<?= $v ?>" <?= $rol_filt === $v ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="glass-card p-5 fade-up">
    <?php if (empty($royxat)): ?>
        <p class="text-center py-10 text-brand-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase tracking-wide">
                        <th class="py-2.5 pr-3">ID</th>
                        <th class="py-2.5 pr-3">Ism</th>
                        <th class="py-2.5 pr-3">Telefon</th>
                        <th class="py-2.5 pr-3">Rol</th>
                        <th class="py-2.5 pr-3">Bonus</th>
                        <th class="py-2.5 pr-3">Holat</th>
                        <th class="py-2.5 pr-3">Sana</th>
                        <th class="py-2.5 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05]">
                    <?php foreach ($royxat as $u):
                        $obuna_faol = (bool) db_qiymat('SELECT 1 FROM obunalar WHERE foydalanuvchi_id=? AND holat="faol" AND tugash>NOW()', [$u['id']]);
                    ?>
                        <tr class="hover:bg-white/[0.03] transition" x-data="{ menu: false }">
                            <td class="py-3 pr-3 font-mono text-xs text-brand-muted tabnum"><?= (int)$u['id'] ?></td>
                            <td class="py-3 pr-3">
                                <div class="font-medium"><?= e($u['ism']) ?> <?= e($u['familiya'] ?? '') ?></div>
                                <?php if ($obuna_faol): ?><span class="badge badge-yellow text-xs">⭐ Faol</span><?php endif; ?>
                            </td>
                            <td class="py-3 pr-3 font-mono text-xs"><?= e($u['telefon']) ?></td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= match($u['rol']) { 'developer'=>'badge-purple','admin'=>'badge-blue',default=>'badge-gray' } ?>">
                                    <?= e($u['rol']) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-3 tabnum"><?= e(pul($u['bonus_balans'])) ?></td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= $u['holat'] === 'faol' ? 'badge-green' : 'badge-red' ?>"><?= e($u['holat']) ?></span>
                            </td>
                            <td class="py-3 pr-3 text-xs text-brand-muted tabnum"><?= e(sana($u['yaratilgan'], 'd.m.Y')) ?></td>
                            <td class="py-3 text-right relative">
                                <button @click="menu = !menu; $nextTick(() => $el.parentElement.parentElement.scrollIntoView({block:'nearest'}))"
                                        class="px-3 py-1.5 rounded-lg hover:bg-white/[0.08] text-brand-muted hover:text-white transition text-lg leading-none">⋯</button>

                                <div x-show="menu" @click.outside="menu=false"
                                     class="absolute right-0 top-full mt-1 z-30 w-60 glass-card p-2 text-left shadow-2xl"
                                     x-cloak>

                                    <!-- Obuna berish -->
                                    <details class="text-sm">
                                        <summary class="px-3 py-2 rounded-lg hover:bg-white/[0.05] cursor-pointer select-none">
                                            🎁 Obuna berish
                                        </summary>
                                        <form method="POST" class="p-2 space-y-2">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="obuna">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <select name="tarif_id" required class="field text-xs">
                                                <option value="">— tarif tanlang —</option>
                                                <?php foreach ($tariflar as $tar): ?>
                                                    <option value="<?= (int)$tar['id'] ?>"><?= e($tar['nomi']) ?> (<?= e(pul($tar['narx'])) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn-primary w-full text-xs py-1.5">Berish</button>
                                        </form>
                                    </details>

                                    <!-- Bonus -->
                                    <details class="text-sm">
                                        <summary class="px-3 py-2 rounded-lg hover:bg-white/[0.05] cursor-pointer select-none">
                                            💰 Bonus qo'shish
                                        </summary>
                                        <form method="POST" class="p-2 space-y-2">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="bonus">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="number" name="bonus" required step="100" min="100" placeholder="5000" class="field text-xs">
                                            <button class="btn-primary w-full text-xs py-1.5">Qo'shish</button>
                                        </form>
                                    </details>

                                    <div class="border-t border-white/[0.07] my-1"></div>

                                    <!-- Block/unblock -->
                                    <form method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="block">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-white/[0.05] text-sm text-<?= $u['holat'] === 'faol' ? 'red' : 'green' ?>-400 transition">
                                            <?= $u['holat'] === 'faol' ? '🚫 Bloklash' : '✓ Faollashtirish' ?>
                                        </button>
                                    </form>

                                    <!-- Rol o'zgartirish (faqat developer) -->
                                    <?php if ($f['rol'] === 'developer' && $u['id'] !== $f['id']): ?>
                                        <details class="text-sm">
                                            <summary class="px-3 py-2 rounded-lg hover:bg-white/[0.05] cursor-pointer select-none">
                                                🔧 Rol o'zgartirish
                                            </summary>
                                            <form method="POST" class="p-2 space-y-2">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="harakat" value="rol">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <select name="rol" class="field text-xs">
                                                    <?php foreach (['user','admin','developer'] as $r): ?>
                                                        <option value="<?= $r ?>" <?= $u['rol'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn-primary w-full text-xs py-1.5">Saqlash</button>
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
            <div class="flex justify-center flex-wrap gap-1 mt-5">
                <?php for ($i = 1; $i <= min($jami_sah, 20); $i++): ?>
                    <a href="?p=<?= $i ?>&q=<?= urlencode($qidiruv) ?>&rol=<?= urlencode($rol_filt) ?>"
                       class="w-9 h-9 rounded-lg text-sm flex items-center justify-center tabnum transition
                           <?= $i === $sahifa ? 'bg-blue-500 text-white' : 'bg-white/[0.05] hover:bg-white/10' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
