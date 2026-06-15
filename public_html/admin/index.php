<?php
$admin_sahifa = 'index';
$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/_layout.php';

$stat = [
    'foydalanuvchi'    => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar'),
    'foydalanuvchi_24h'=> (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'tolov_jami'       => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"'),
    'tolov_24h'        => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'obuna_faol'       => (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()'),
    'test_jami'        => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"'),
    'test_24h'         => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan" AND tugagan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'kutayotgan_fikr'  => (int) db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0'),
    'kutayotgan_tolov' => (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"'),
    'onlayn'           => (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE oxirgi_kirish > DATE_SUB(NOW(), INTERVAL 5 MINUTE)'),
    'davom_etayotgan'  => (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "davom"'),
];

$oxirgi_tolov = db_barcha(
    'SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     ORDER BY t.yaratilgan DESC LIMIT 10'
);
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="ring-grad fade-up">
        <div class="p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-cyan/15 text-cyan flex items-center justify-center text-lg">👥</div>
                <span class="text-xs text-muted uppercase tracking-wider">Foydalanuvchilar</span>
            </div>
            <div class="text-3xl font-display font-extrabold"><?= $stat['foydalanuvchi'] ?></div>
            <div class="flex items-center gap-3 mt-1 text-xs">
                <span class="text-success">+<?= $stat['foydalanuvchi_24h'] ?> bugun</span>
                <span class="text-muted">·</span>
                <span class="text-success flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                    <?= $stat['onlayn'] ?> onlayn
                </span>
            </div>
        </div>
    </div>
    <div class="glass p-5 fade-up">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-success/15 text-success flex items-center justify-center text-lg">💰</div>
            <span class="text-xs text-muted uppercase tracking-wider">Daromad</span>
        </div>
        <div class="text-2xl font-display font-extrabold grad-text"><?= e(pul($stat['tolov_jami'])) ?></div>
        <div class="text-xs text-success mt-1">+<?= e(pul($stat['tolov_24h'])) ?> bugun</div>
    </div>
    <div class="glass p-5 fade-up">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-amber/15 text-amber flex items-center justify-center text-lg">⚡</div>
            <span class="text-xs text-muted uppercase tracking-wider">Faol obunalar</span>
        </div>
        <div class="text-3xl font-display font-extrabold text-amber"><?= $stat['obuna_faol'] ?></div>
        <div class="text-xs text-muted mt-1">hozir faol</div>
    </div>
    <div class="glass p-5 fade-up">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-violet/15 text-violet flex items-center justify-center text-lg">📝</div>
            <span class="text-xs text-muted uppercase tracking-wider">Yechilgan testlar</span>
        </div>
        <div class="text-3xl font-display font-extrabold"><?= $stat['test_jami'] ?></div>
        <div class="flex items-center gap-3 mt-1 text-xs">
            <span class="text-success">+<?= $stat['test_24h'] ?> bugun</span>
            <?php if ($stat['davom_etayotgan'] > 0): ?>
                <span class="text-muted">·</span>
                <span class="text-amber animate-pulse"><?= $stat['davom_etayotgan'] ?> jarayonda</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($stat['kutayotgan_tolov'] || $stat['kutayotgan_fikr']): ?>
    <div class="grid sm:grid-cols-2 gap-3 mb-6">
        <?php if ($stat['kutayotgan_tolov']): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
               class="glass glass-hover p-4 !border-amber/40 fade-up flex items-center gap-3">
                <span class="text-3xl">⏳</span>
                <div class="flex-1">
                    <div class="font-medium">Kutilayotgan to'lovlar</div>
                    <div class="text-xs text-muted">Tasdiqlash kerak: <strong class="text-amber"><?= $stat['kutayotgan_tolov'] ?></strong></div>
                </div>
                <span class="text-muted">→</span>
            </a>
        <?php endif; ?>
        <?php if ($stat['kutayotgan_fikr']): ?>
            <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
               class="glass glass-hover p-4 !border-violet/40 fade-up flex items-center gap-3">
                <span class="text-3xl">💬</span>
                <div class="flex-1">
                    <div class="font-medium">Yangi fikrlar</div>
                    <div class="text-xs text-muted">Moderatsiya kerak: <strong class="text-violet"><?= $stat['kutayotgan_fikr'] ?></strong></div>
                </div>
                <span class="text-muted">→</span>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="glass p-5 fade-up">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-display font-bold text-lg">Oxirgi to'lovlar</h2>
        <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php" class="text-sm grad-text font-semibold">Barchasi →</a>
    </div>

    <?php if (empty($oxirgi_tolov)): ?>
        <p class="text-center text-muted py-8 text-sm"><?= e(t('malumot_yoq')) ?></p>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
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
                                <div class="text-xs text-muted"><?= e($t['telefon']) ?></div>
                            </td>
                            <td class="py-2.5 pr-3"><?= e($t['tarif_nomi']) ?></td>
                            <td class="py-2.5 pr-3 font-bold"><?= e(pul($t['summa'])) ?></td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2"><?= e(strtoupper($t['tolov_turi'])) ?></span>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="chip text-xs !py-0.5 !px-2
                                    <?= $t['holat'] === 'muvaffaqiyatli' ? 'bg-success/15 text-success border-success/30' :
                                       ($t['holat'] === 'kutilmoqda' ? 'bg-amber/15 text-amber border-amber/30' : 'bg-danger/15 text-danger border-danger/30') ?>">
                                    <?= e($t['holat']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-muted text-xs"><?= e(vaqt_oldin($t['yaratilgan'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
