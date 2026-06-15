<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) { flash_qoy('xato', t('csrf_xato')); yonaltir(SAYT_URL.'/admin/tolovlar.php'); }

    $harakat = post('harakat');
    $id      = (int) post('id');
    $tolov   = $id ? db_qator('SELECT * FROM tolovlar WHERE id = ?', [$id]) : null;

    if ($tolov && $harakat === 'tasdiq' && $tolov['holat'] !== 'muvaffaqiyatli') {
        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ?', [$tolov['tarif_id']]);
        if ($tarif) {
            $kun = match ($tarif['tur']) {
                'kun' => (int)$tarif['qiymat'],
                'oy'  => (int)$tarif['qiymat'] * 30,
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
                        "✓ <b>To'lovingiz tasdiqlandi!</b>\nTarif: {$tarif['nomi']}");
                }
                db()->commit();
                flash_qoy('muvaffaqiyat', 'Tasdiqlandi');
            } catch (Throwable $e) {
                db()->rollBack();
                flash_qoy('xato', $e->getMessage());
            }
        }
    }
    if ($tolov && $harakat === 'bekor') {
        db_bajar('UPDATE tolovlar SET holat = "bekor" WHERE id = ?', [$id]);
        flash_qoy('muvaffaqiyat', 'Bekor qilindi');
    }
    yonaltir(SAYT_URL . '/admin/tolovlar.php?holat=' . urlencode(olish('holat')));
}

$holat   = olish('holat');
$qidiruv = olish('q');
$shartlar = []; $params = [];
if ($holat && in_array($holat, ['kutilmoqda','muvaffaqiyatli','bekor','xato'], true)) {
    $shartlar[] = 't.holat = ?'; $params[] = $holat;
}
if ($qidiruv) {
    $shartlar[] = '(fo.telefon LIKE ? OR fo.ism LIKE ?)';
    array_push($params, "%{$qidiruv}%", "%{$qidiruv}%");
}
$where  = $shartlar ? 'WHERE ' . implode(' AND ', $shartlar) : '';
$royxat = db_barcha(
    "SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     {$where} ORDER BY t.yaratilgan DESC LIMIT 100",
    $params
);

$admin_sahifa    = 'tolovlar';
$sahifa_sarlavha = "To'lovlar";
require_once __DIR__ . '/_layout.php';
?>

<form method="GET" class="b-card"
      style="padding:1rem; margin-bottom:1.25rem;
             display:grid; grid-template-columns:1fr; gap:.75rem;"
      class="md:grid-cols-3">
    <div style="grid-column: span 2;">
        <input name="q" value="<?= e($qidiruv) ?>"
               placeholder="Telefon yoki ism..." class="field">
    </div>
    <div>
        <select name="holat" onchange="this.form.submit()" class="field">
            <option value="">Barcha holatlar</option>
            <?php foreach (['kutilmoqda'=>'Kutilmoqda','muvaffaqiyatli'=>'Muvaffaqiyatli','bekor'=>'Bekor','xato'=>'Xato'] as $v=>$n): ?>
            <option value="<?= $v ?>" <?= $holat===$v?'selected':'' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="b-card">
    <?php if (empty($royxat)): ?>
    <div style="padding:3rem; text-align:center; color:#666;"><?= e(t('malumot_yoq')) ?></div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="b-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Foydalanuvchi</th>
                    <th>Tarif</th>
                    <th>Summa</th>
                    <th>Usul</th>
                    <th>Holat</th>
                    <th>Vaqt</th>
                    <th style="text-align:right;">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($royxat as $t): ?>
                <tr>
                    <td style="font-family:monospace; color:#666;" class="tabnum">
                        <?= (int)$t['id'] ?>
                    </td>
                    <td>
                        <strong><?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?></strong>
                        <div style="font-size:.7rem; color:#666; font-family:monospace;">
                            <?= e($t['telefon']) ?>
                        </div>
                    </td>
                    <td><?= e($t['tarif_nomi']) ?></td>
                    <td style="font-weight:600;" class="tabnum"><?= e(pul($t['summa'])) ?></td>
                    <td>
                        <span class="badge"><?= strtoupper(e($t['tolov_turi'])) ?></span>
                    </td>
                    <td>
                        <span class="badge <?= $t['holat']==='muvaffaqiyatli' ? 'badge-filled' : '' ?>">
                            <?= e($t['holat']) ?>
                        </span>
                    </td>
                    <td style="font-size:.78rem; color:#666;" class="tabnum">
                        <?= e(sana($t['yaratilgan'])) ?>
                    </td>
                    <td style="text-align:right;">
                        <?php if ($t['holat'] === 'kutilmoqda'): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="tasdiq">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <button class="btn btn-xs btn-primary">Tasdiq</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bekor?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="bekor">
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <button class="btn btn-xs btn-danger">Bekor</button>
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
