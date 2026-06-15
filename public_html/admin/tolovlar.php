<?php
/**
 * AvtoTest Pro — To'lovlarni boshqarish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/tolovlar.php');
    }

    $harakat = post('harakat');
    $id      = (int) post('id');
    $tolov   = $id ? db_qator('SELECT * FROM tolovlar WHERE id = ?', [$id]) : null;

    if ($tolov && $harakat === 'tasdiq' && $tolov['holat'] !== 'muvaffaqiyatli') {
        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
        if ($tarif) {
            $kun = match ($tarif['tur']) {
                'kun'   => (int) $tarif['qiymat'],
                'oy'    => (int) $tarif['qiymat'] * 30,
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
                $fo = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$tolov['foydalanuvchi_id']]);
                if ($fo && $fo['referal_orqali']) {
                    $bonus = (float) sozlama('referal_bonus', 5000);
                    db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans + ? WHERE id = ?',
                             [$bonus, (int)$fo['referal_orqali']]);
                    db_bajar('UPDATE referallar SET holat = "tasdiq", bonus_summa = ? WHERE referal_id = ?',
                             [$bonus, $fo['id']]);
                }

                if ($fo && $fo['telegram_id']) {
                    telegram_yubor((int)$fo['telegram_id'],
                        "✅ <b>To'lovingiz tasdiqlandi!</b>\nTarif: <b>{$tarif['nomi']}</b>");
                }

                db()->commit();
                flash_qoy('muvaffaqiyat', 'To\'lov tasdiqlandi, obuna ochildi');
            } catch (Throwable $e) {
                db()->rollBack();
                flash_qoy('xato', 'Xato: ' . $e->getMessage());
            }
        }
    }

    if ($tolov && $harakat === 'bekor') {
        db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', 'Bekor qilindi');
    }

    yonaltir(SAYT_URL . '/admin/tolovlar.php?holat=' . urlencode(olish('holat')) . '&q=' . urlencode(olish('q')));
}

$holat   = olish('holat');
$qidiruv = olish('q');
$shartlar = [];
$params   = [];

if ($holat && in_array($holat, ['kutilmoqda','muvaffaqiyatli','bekor','xato'], true)) {
    $shartlar[] = 't.holat = ?';
    $params[]   = $holat;
}
if ($qidiruv) {
    $shartlar[] = '(fo.telefon LIKE ? OR fo.ism LIKE ? OR fo.familiya LIKE ?)';
    array_push($params, "%{$qidiruv}%", "%{$qidiruv}%", "%{$qidiruv}%");
}

$where  = $shartlar ? 'WHERE ' . implode(' AND ', $shartlar) : '';
$jami   = (int) db_qiymat("SELECT COUNT(*) FROM tolovlar t JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id JOIN tariflar ta ON t.tarif_id = ta.id {$where}", $params);
$royxat = db_barcha(
    "SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     {$where}
     ORDER BY t.yaratilgan DESC LIMIT 100",
    $params
);

$admin_sahifa    = 'tolovlar';
$sahifa_sarlavha = "To'lovlar";
require_once __DIR__ . '/_layout.php';
?>

<!-- Filtr -->
<form method="GET" class="glass-card p-4 mb-5 grid sm:grid-cols-3 gap-3 fade-up">
    <input name="q" value="<?= e($qidiruv) ?>" placeholder="Telefon yoki ism..." class="field sm:col-span-2">
    <select name="holat" onchange="this.form.submit()" class="field">
        <option value="">Barcha holatlar (<?= $jami ?>)</option>
        <?php foreach (['kutilmoqda' => 'Kutilmoqda', 'muvaffaqiyatli' => 'Muvaffaqiyatli', 'bekor' => 'Bekor', 'xato' => 'Xato'] as $v => $n): ?>
            <option value="<?= $v ?>" <?= $holat === $v ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="glass-card p-5 fade-up">
    <?php if (empty($royxat)): ?>
        <p class="text-center py-12 text-brand-muted"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-brand-muted text-xs uppercase tracking-wide">
                        <th class="py-2.5 pr-3">#</th>
                        <th class="py-2.5 pr-3">Foydalanuvchi</th>
                        <th class="py-2.5 pr-3">Tarif</th>
                        <th class="py-2.5 pr-3">Summa</th>
                        <th class="py-2.5 pr-3">Usul</th>
                        <th class="py-2.5 pr-3">Holat</th>
                        <th class="py-2.5 pr-3">Vaqt</th>
                        <th class="py-2.5 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05]">
                    <?php foreach ($royxat as $t): ?>
                        <tr class="hover:bg-white/[0.03] transition">
                            <td class="py-3 pr-3 font-mono text-xs text-brand-muted tabnum"><?= (int)$t['id'] ?></td>
                            <td class="py-3 pr-3">
                                <p class="font-medium"><?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?></p>
                                <p class="text-xs text-brand-muted font-mono"><?= e($t['telefon']) ?></p>
                            </td>
                            <td class="py-3 pr-3"><?= e($t['tarif_nomi']) ?></td>
                            <td class="py-3 pr-3 font-bold tabnum"><?= e(pul($t['summa'])) ?></td>
                            <td class="py-3 pr-3">
                                <span class="badge badge-gray uppercase"><?= e($t['tolov_turi']) ?></span>
                            </td>
                            <td class="py-3 pr-3">
                                <span class="badge <?= match($t['holat']) {
                                    'muvaffaqiyatli' => 'badge-green',
                                    'kutilmoqda'     => 'badge-yellow',
                                    default          => 'badge-red'
                                } ?>"><?= e($t['holat']) ?></span>
                            </td>
                            <td class="py-3 pr-3 text-xs text-brand-muted tabnum"><?= e(sana($t['yaratilgan'])) ?></td>
                            <td class="py-3 text-right">
                                <?php if ($t['holat'] === 'kutilmoqda'): ?>
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" class="inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="tasdiq">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <button class="btn-success text-xs py-1.5 px-3">✓ Tasdiq</button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Bekor qilinsinmi?')">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="harakat" value="bekor">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <button class="btn-danger text-xs py-1.5 px-3">✗</button>
                                        </form>
                                    </div>
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
