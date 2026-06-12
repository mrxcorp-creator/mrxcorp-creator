<?php
/**
 * Admin — boshqaruv paneli (statistika)
 */
$admin_sahifa = 'index';
$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/_layout.php';

$stat = [
    'foydalanuvchi'  => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar'),
    'foydalanuvchi_24h' => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'tolov_jami'     => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"'),
    'tolov_24h'      => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'obuna_faol'     => (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()'),
    'test_jami'      => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"'),
    'test_24h'       => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan" AND tugagan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'kutayotgan_fikr'=> (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0'),
    'kutayotgan_tolov'=> (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"'),
];

$oxirgi_tolov = db_barcha(
    'SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     ORDER BY t.yaratilgan DESC LIMIT 10'
);
?>

<!-- Statistika kartalari -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-1">Foydalanuvchilar</div>
        <div class="text-3xl font-display font-bold"><?= $stat['foydalanuvchi'] ?></div>
        <div class="text-xs text-green-400 mt-1">+<?= $stat['foydalanuvchi_24h'] ?> bugun</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-1">Daromad</div>
        <div class="text-2xl font-display font-bold"><?= e(pul($stat['tolov_jami'])) ?></div>
        <div class="text-xs text-green-400 mt-1">+<?= e(pul($stat['tolov_24h'])) ?> bugun</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-1">Faol obunalar</div>
        <div class="text-3xl font-display font-bold text-yellow-400"><?= $stat['obuna_faol'] ?></div>
        <div class="text-xs text-brand-muted mt-1">hozir faol</div>
    </div>
    <div class="glass-card p-5 fade-up">
        <div class="text-xs text-brand-muted uppercase mb-1">Yechilgan testlar</div>
        <div class="text-3xl font-display font-bold"><?= $stat['test_jami'] ?></div>
        <div class="text-xs text-green-400 mt-1">+<?= $stat['test_24h'] ?> bugun</div>
    </div>
</div>

<!-- Ogohlantirishlar -->
<?php if ($stat['kutayotgan_tolov'] || $stat['kutayotgan_fikr']): ?>
    <div class="grid sm:grid-cols-2 gap-3 mb-6">
        <?php if ($stat['kutayotgan_tolov']): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
               class="glass-card p-4 border-yellow-500/30 hover:border-yellow-500/60 fade-up flex items-center gap-3">
                <span class="text-2xl">⏳</span>
                <div class="flex-1">
                    <div class="font-medium">Kutilayotgan to'lovlar</div>
                    <div class="text-xs text-brand-muted">Tasdiqlash kerak: <strong class="text-yellow-400"><?= $stat['kutayotgan_tolov'] ?></strong></div>
                </div>
                <span>→</span>
            </a>
        <?php endif; ?>
        <?php if ($stat['kutayotgan_fikr']): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
               class="glass-card p-4 border-blue-500/30 hover:border-blue-500/60 fade-up flex items-center gap-3">
                <span class="text-2xl">💬</span>
                <div class="flex-1">
                    <div class="font-medium">Yangi fikrlar</div>
                    <div class="text-xs text-brand-muted">Moderatsiya kerak: <strong class="text-blue-400"><?= $stat['kutayotgan_fikr'] ?></strong></div>
                </div>
                <span>→</span>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Oxirgi to'lovlar -->
<div class="glass-card p-5 fade-up">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-display text-lg">Oxirgi to'lovlar</h2>
        <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php" class="text-sm text-blue-400 hover:underline">Barchasi →</a>
    </div>

    <?php if (empty($oxirgi_tolov)): ?>
        <p class="text-center text-brand-muted py-8 text-sm"><?= e(t('malumot_yoq')) ?></p>
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
                        <th class="py-2">Sana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($oxirgi_tolov as $t): ?>
                        <tr class="hover:bg-white/3">
                            <td class="py-2.5 pr-3 font-mono text-xs"><?= (int)$t['id'] ?></td>
                            <td class="py-2.5 pr-3">
                                <?= e($t['ism']) ?> <?= e($t['familiya'] ?? '') ?>
                                <div class="text-xs text-brand-muted"><?= e($t['telefon']) ?></div>
                            </td>
                            <td class="py-2.5 pr-3"><?= e($t['tarif_nomi']) ?></td>
                            <td class="py-2.5 pr-3 font-bold"><?= e(pul($t['summa'])) ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="text-xs px-2 py-0.5 rounded bg-white/10"><?= e(strtoupper($t['tolov_turi'])) ?></span>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    <?= $t['holat'] === 'muvaffaqiyatli' ? 'bg-green-500/20 text-green-400' :
                                       ($t['holat'] === 'kutilmoqda' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') ?>">
                                    <?= e($t['holat']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-brand-muted text-xs"><?= e(vaqt_oldin($t['yaratilgan'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
