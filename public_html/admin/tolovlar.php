<?php
/**
 * Admin — To'lovlarni boshqarish
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/tolovlar.php');
    }
    $harakat = post('harakat');
    $id = (int) post('id');
    $tolov = db_qator('SELECT * FROM tolovlar WHERE id = ?', [$id]);

    if ($tolov && $harakat === 'tasdiq' && $tolov['holat'] !== 'muvaffaqiyatli') {
        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
        if ($tarif) {
            $kun = match ($tarif['tur']) {
                'kun' => $tarif['qiymat'],
                'oy'  => $tarif['qiymat'] * 30,
                default => 365,
            };
            db()->beginTransaction();
            try {
                db_bajar('UPDATE tolovlar SET holat = "muvaffaqiyatli" WHERE id = ?', [$id]);
                db_bajar(
                    'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
                     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                    [$tolov['foydalanuvchi_id'], $tarif['id'], $kun]
                );

                // Referal bonus
                $foydalanuvchi = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
                if ($foydalanuvchi['referal_orqali']) {
                    $bonus = (float) sozlama('referal_bonus', 5000);
                    db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                             [$bonus, $foydalanuvchi['referal_orqali']]);
                    db_bajar('UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                             [$bonus, $foydalanuvchi['id']]);
                }

                if ($foydalanuvchi['telegram_id']) {
                    telegram_yubor($foydalanuvchi['telegram_id'],
                        "✅ <b>To'lovingiz tasdiqlandi!</b>\nTarif: <b>{$tarif['nomi']}</b>");
                }
                bildirishnoma_yarat(
                    $foydalanuvchi['id'],
                    "To'lovingiz admin tomonidan tasdiqlandi ✅",
                    "Tarif: {$tarif['nomi']}\nEndi siz barcha imkoniyatlardan foydalanishingiz mumkin.",
                    '/dashboard',
                    'muvaffaqiyat',
                    '✅'
                );
                db()->commit();
                flash_qoy('muvaffaqiyat', 'To\'lov tasdiqlandi va obuna ochildi');
            } catch (Exception $exc) {
                db()->rollBack();
                flash_qoy('xato', 'Xato: ' . $exc->getMessage());
            }
        }
    }

    if ($tolov && $harakat === 'bekor') {
        db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', 'Bekor qilindi');
    }

    yonaltir(SAYT_URL . '/admin/tolovlar.php?holat=' . urlencode(olish('holat')));
}

$holat = olish('holat');
$qidiruv = olish('q');
$shartlar = [];
$params = [];
if ($holat && in_array($holat, ['kutilmoqda','muvaffaqiyatli','bekor','xato'], true)) {
    $shartlar[] = 't.holat = ?';
    $params[] = $holat;
}
if ($qidiruv) {
    $shartlar[] = '(fo.telefon LIKE ? OR fo.ism LIKE ?)';
    $params = array_merge($params, ["%$qidiruv%", "%$qidiruv%"]);
}
$where = $shartlar ? ' WHERE ' . implode(' AND ', $shartlar) : '';

$royxat = db_barcha(
    "SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     $where
     ORDER BY t.yaratilgan DESC LIMIT 100",
    $params
);

$admin_sahifa = 'tolovlar';
$sahifa_sarlavha = "To'lovlar";
require_once __DIR__ . '/_layout.php';
?>

<form method="GET" class="grid sm:grid-cols-3 gap-3 mb-4">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Telefon yoki ism..." class="field sm:col-span-2">
    <select name="holat" onchange="this.form.submit()" class="field">
        <option value="">Barcha holatlar</option>
        <option value="kutilmoqda" <?= $holat === 'kutilmoqda' ? 'selected' : '' ?>>Kutilmoqda</option>
        <option value="muvaffaqiyatli" <?= $holat === 'muvaffaqiyatli' ? 'selected' : '' ?>>Muvaffaqiyatli</option>
        <option value="bekor" <?= $holat === 'bekor' ? 'selected' : '' ?>>Bekor</option>
        <option value="xato" <?= $holat === 'xato' ? 'selected' : '' ?>>Xato</option>
    </select>
</form>

<div class="glass-card p-5 fade-up">
    <?php if (empty($royxat)): ?>
        <p class="text-center py-8 text-brand-muted text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase">
                        <th class="py-2 pr-3">#</th>
                        <th class="py-2 pr-3">Foydalanuvchi</th>
                        <th class="py-2 pr-3">Tarif</th>
                        <th class="py-2 pr-3">Summa</th>
                        <th class="py-2 pr-3">Usul</th>
                        <th class="py-2 pr-3">Holat</th>
                        <th class="py-2 pr-3">Sana</th>
                        <th class="py-2 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border">
                    <?php foreach ($royxat as $t): ?>
                        <tr class="hover:bg-sky-50">
                            <td class="py-2.5 pr-3 font-mono text-xs"><?= (int)$t['id'] ?></td>
                            <td class="py-2.5 pr-3">
                                <?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?>
                                <div class="text-xs text-brand-muted"><?= e($t['telefon']) ?></div>
                            </td>
                            <td class="py-2.5 pr-3"><?= e($t['tarif_nomi']) ?></td>
                            <td class="py-2.5 pr-3 font-bold"><?= e(pul($t['summa'])) ?></td>
                            <td class="py-2.5 pr-3"><span class="text-xs px-2 py-0.5 rounded bg-sky-100 uppercase"><?= e($t['tolov_turi']) ?></span></td>
                            <td class="py-2.5 pr-3">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    <?= $t['holat'] === 'muvaffaqiyatli' ? 'bg-emerald-100 text-emerald-700' :
                                       ($t['holat'] === 'kutilmoqda' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') ?>">
                                    <?= e($t['holat']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 text-xs text-brand-muted"><?= e(sana($t['yaratilgan'])) ?></td>
                            <td class="py-2.5 text-right">
                                <?php if ($t['holat'] === 'kutilmoqda'): ?>
                                    <form method="POST" class="inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="tasdiq">
                                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                        <button class="text-emerald-600 text-xs hover:underline mr-2">✓ Tasdiq</button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Bekor qilinsinmi?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="harakat" value="bekor">
                                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                        <button class="text-rose-600 text-xs hover:underline">✗ Bekor</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
