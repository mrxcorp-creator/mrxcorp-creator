<?php
/**
 * AvtoTest Pro — Admin boshqaruv paneli
 * BUG FIX: auth.php va funksiyalar.php AVVAL yuklanadi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$admin_sahifa    = 'index';
$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/_layout.php';

// ── Statistika ──────────────────────────────────────────────
$stat = [
    'foydalanuvchi'     => (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar'),
    'foydalanuvchi_24h' => (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'foydalanuvchi_7d'  => (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 7 DAY)'),
    'tolov_jami'        => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"'),
    'tolov_24h'         => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'tolov_oy'          => (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND MONTH(yaratilgan) = MONTH(NOW()) AND YEAR(yaratilgan) = YEAR(NOW())'),
    'obuna_faol'        => (int)   db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()'),
    'test_jami'         => (int)   db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"'),
    'test_24h'          => (int)   db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan" AND tugagan > DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
    'savol_soni'        => (int)   db_qiymat('SELECT COUNT(*) FROM savollar'),
    'bilet_soni'        => (int)   db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"'),
    'kutilmoqda_t'      => (int)   db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"'),
    'kutilmoqda_f'      => (int)   db_qiymat('SELECT COUNT(*) FROM fikrlar WHERE tasdiq = 0'),
];

$oxirgi_tolov = db_barcha(
    'SELECT t.*, fo.ism, fo.familiya, fo.telefon, ta.nomi AS tarif_nomi
     FROM tolovlar t
     JOIN foydalanuvchilar fo ON t.foydalanuvchi_id = fo.id
     JOIN tariflar ta ON t.tarif_id = ta.id
     ORDER BY t.yaratilgan DESC LIMIT 8'
);

// 7 kunlik daromad (to'liq massiv)
$daromad_raw = db_barcha(
    'SELECT DATE(yaratilgan) AS sana, COALESCE(SUM(summa),0) AS summa
     FROM tolovlar WHERE holat = "muvaffaqiyatli"
       AND yaratilgan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(yaratilgan)'
);
$daromad_map  = array_column($daromad_raw, 'summa', 'sana');
$daromad_maks = max(array_values($daromad_map) ?: [1]) ?: 1;
?>

<!-- ── Statistika kartalari ──────────────────────────────── -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
<?php
$cards = [
    ['icon'=>'👥','nom'=>'Foydalanuvchilar','qiymat'=>$stat['foydalanuvchi'],
     'sub'=>'+'.($stat['foydalanuvchi_24h']).' bugun','rang'=>'blue'],
    ['icon'=>'💰','nom'=>'Jami daromad','qiymat'=>pul($stat['tolov_jami']),
     'sub'=>'+'.pul($stat['tolov_24h']).' bugun','rang'=>'emerald'],
    ['icon'=>'⭐','nom'=>'Faol obunalar','qiymat'=>$stat['obuna_faol'],
     'sub'=>'hozir aktiv','rang'=>'amber'],
    ['icon'=>'📝','nom'=>'Testlar','qiymat'=>$stat['test_jami'],
     'sub'=>'+'.$stat['test_24h'].' bugun','rang'=>'violet'],
];
foreach ($cards as $i => $c):
    $rects = [
        'blue'    => ['bg-blue-500/10','text-blue-400','border-blue-500/20'],
        'emerald' => ['bg-emerald-500/10','text-emerald-400','border-emerald-500/20'],
        'amber'   => ['bg-amber-500/10','text-amber-400','border-amber-500/20'],
        'violet'  => ['bg-violet-500/10','text-violet-400','border-violet-500/20'],
    ][$c['rang']];
?>
<div class="glass-card p-5 fade-up border <?= $rects[2] ?>" style="animation-delay:<?= $i * .07 ?>s">
    <div class="flex items-start justify-between mb-4">
        <div class="w-10 h-10 rounded-xl <?= $rects[0] ?> <?= $rects[1] ?> flex items-center justify-center text-xl">
            <?= $c['icon'] ?>
        </div>
        <span class="text-xs <?= $rects[1] ?> font-medium opacity-70 mt-1">PRO</span>
    </div>
    <div class="text-2xl font-display font-bold tabnum"><?= $c['qiymat'] ?></div>
    <div class="text-xs text-brand-muted uppercase tracking-wide mt-1"><?= e($c['nom']) ?></div>
    <div class="text-xs <?= $rects[1] ?> mt-2 font-medium"><?= $c['sub'] ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- ── 2-qator: kichik kartalar ──────────────────────────── -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
<?php
$cards2 = [
    ['📅','Bu oy',pul($stat['tolov_oy']),'text-blue-400'],
    ['🎫','Biletlar',$stat['bilet_soni'],'text-purple-400'],
    ['❓','Savollar',$stat['savol_soni'],'text-cyan-400'],
    ['⏳','Kutilmoqda',$stat['kutilmoqda_t'],'text-amber-400'],
];
foreach ($cards2 as [$ico, $nom, $val, $clr]):
?>
<div class="glass-card p-4 fade-up text-center">
    <div class="text-xl mb-1"><?= $ico ?></div>
    <div class="text-xl font-display font-bold <?= $clr ?> tabnum"><?= $val ?></div>
    <div class="text-xs text-brand-muted mt-0.5"><?= e($nom) ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- ── Ogohlantirishlar ───────────────────────────────────── -->
<?php if ($stat['kutilmoqda_t'] || $stat['kutilmoqda_f']): ?>
<div class="grid sm:grid-cols-2 gap-3 mb-6">
    <?php if ($stat['kutilmoqda_t']): ?>
    <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php?holat=kutilmoqda"
       class="glass-card p-4 border-amber-500/30 bg-amber-500/[0.04] hover:border-amber-500/50 transition flex items-center gap-3 fade-up">
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl flex-shrink-0">⏳</div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">Kutilayotgan to'lovlar</p>
            <p class="text-xs text-brand-muted">Tasdiqlash kerak: <strong class="text-amber-400"><?= $stat['kutilmoqda_t'] ?></strong> ta</p>
        </div>
        <svg class="w-4 h-4 text-brand-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($stat['kutilmoqda_f']): ?>
    <a href="<?= e(SAYT_URL) ?>/admin/fikrlar.php?holat=kutilmoqda"
       class="glass-card p-4 border-blue-500/30 bg-blue-500/[0.04] hover:border-blue-500/50 transition flex items-center gap-3 fade-up">
        <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl flex-shrink-0">💬</div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">Yangi fikrlar</p>
            <p class="text-xs text-brand-muted">Moderatsiya kerak: <strong class="text-blue-400"><?= $stat['kutilmoqda_f'] ?></strong> ta</p>
        </div>
        <svg class="w-4 h-4 text-brand-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Grafik + oxirgi to'lovlar ─────────────────────────── -->
<div class="grid lg:grid-cols-3 gap-5 mb-6">
    <!-- Daromad grafigi -->
    <div class="glass-card p-5 fade-up">
        <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-brand-muted mb-4">📈 7 kunlik daromad</h2>
        <div class="space-y-2">
            <?php for ($i = 6; $i >= 0; $i--):
                $sana = date('Y-m-d', strtotime("-{$i} days"));
                $sum  = (float)($daromad_map[$sana] ?? 0);
                $w    = $sum > 0 ? max(8, round($sum / $daromad_maks * 100)) : 0;
            ?>
            <div class="flex items-center gap-2">
                <span class="w-11 text-xs text-brand-muted tabnum shrink-0"><?= date('d.m', strtotime($sana)) ?></span>
                <div class="flex-1 h-7 bg-white/[0.04] rounded-lg overflow-hidden relative">
                    <?php if ($w > 0): ?>
                    <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-500 rounded-lg flex items-center justify-end pr-2 min-w-[2rem] transition-all duration-700"
                         style="width:<?= $w ?>%">
                        <span class="text-[10px] font-bold tabnum text-white"><?= number_format($sum/1000,0) ?>K</span>
                    </div>
                    <?php else: ?>
                    <div class="h-full flex items-center pl-3"><span class="text-xs text-white/20">—</span></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <div class="mt-4 pt-4 border-t border-white/[0.06] flex justify-between text-xs text-brand-muted">
            <span>Bu oy: <strong class="text-white"><?= e(pul($stat['tolov_oy'])) ?></strong></span>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php" class="text-blue-400 hover:underline">Barchasi →</a>
        </div>
    </div>

    <!-- Oxirgi to'lovlar -->
    <div class="lg:col-span-2 glass-card p-5 fade-up">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-sm font-semibold uppercase tracking-wide text-brand-muted">💳 Oxirgi to'lovlar</h2>
            <a href="<?= e(SAYT_URL) ?>/admin/tolovlar.php" class="text-xs text-blue-400 hover:underline">Barchasi →</a>
        </div>
        <?php if (empty($oxirgi_tolov)): ?>
            <p class="text-center text-brand-muted py-10 text-sm"><?= e(t('malumot_yoq')) ?></p>
        <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-brand-muted uppercase tracking-wide border-b border-white/[0.06]">
                        <th class="pb-2 pr-3">Foydalanuvchi</th>
                        <th class="pb-2 pr-3">Tarif</th>
                        <th class="pb-2 pr-3">Summa</th>
                        <th class="pb-2 pr-3">Holat</th>
                        <th class="pb-2">Vaqt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($oxirgi_tolov as $row): ?>
                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.02] transition">
                        <td class="py-2.5 pr-3">
                            <p class="font-medium text-white"><?= e($row['ism']) ?> <?= e($row['familiya'] ?? '') ?></p>
                            <p class="text-brand-muted font-mono mt-0.5"><?= e($row['telefon']) ?></p>
                        </td>
                        <td class="py-2.5 pr-3 text-white/80"><?= e($row['tarif_nomi']) ?></td>
                        <td class="py-2.5 pr-3 font-bold tabnum text-white"><?= e(pul($row['summa'])) ?></td>
                        <td class="py-2.5 pr-3">
                            <span class="badge <?= match($row['holat']) {
                                'muvaffaqiyatli' => 'badge-green',
                                'kutilmoqda'     => 'badge-yellow',
                                default          => 'badge-red'
                            } ?>"><?= e($row['holat']) ?></span>
                        </td>
                        <td class="py-2.5 text-brand-muted"><?= e(vaqt_oldin($row['yaratilgan'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Tezkor navigatsiya ─────────────────────────────────── -->
<div class="grid sm:grid-cols-3 gap-4">
<?php
$shortcuts = [
    [SAYT_URL.'/admin/foydalanuvchilar.php','👥','Foydalanuvchilar','Ro\'yxatni boshqarish','blue'],
    [SAYT_URL.'/admin/biletlar.php','🎫','Biletlar va savollar','CRUD boshqaruv','purple'],
    [SAYT_URL.'/admin/promokodlar.php','🎟️','Promo kodlar','Chegirma kodlari','emerald'],
];
foreach ($shortcuts as [$href,$ico,$nom,$sub,$rang]):
    $tc = ['blue'=>'text-blue-400','purple'=>'text-purple-400','emerald'=>'text-emerald-400'][$rang];
    $bg = ['blue'=>'bg-blue-500/10','purple'=>'bg-purple-500/10','emerald'=>'bg-emerald-500/10'][$rang];
?>
<a href="<?= e($href) ?>" class="glass-card p-4 fade-up hover:border-white/20 transition flex items-center gap-3 group">
    <div class="w-10 h-10 rounded-xl <?= $bg ?> <?= $tc ?> flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-110 transition-transform">
        <?= $ico ?>
    </div>
    <div class="min-w-0">
        <p class="font-semibold text-sm text-white truncate"><?= e($nom) ?></p>
        <p class="text-xs text-brand-muted"><?= e($sub) ?></p>
    </div>
    <svg class="w-4 h-4 text-brand-muted ml-auto flex-shrink-0 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
