<?php
/**
 * AvtoTest Pro — Foydalanuvchi boshqaruv paneli (Dashboard)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

// Umumiy statistika
$stat = db_qator(
    'SELECT
        COUNT(*)                                          AS jami,
        COALESCE(SUM(togri_son), 0)                      AS togri,
        COALESCE(SUM(xato_son), 0)                       AS xato,
        COALESCE(SUM(umumiy_son), 0)                     AS umumiy,
        COALESCE(MAX(ROUND(togri_son/umumiy_son*100)),0)  AS eng_yaxshi
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
    [$f['id']]
);
$jami_test      = (int)  ($stat['jami']      ?? 0);
$togri_javoblar = (int)  ($stat['togri']     ?? 0);
$umumiy         = max(1, (int) ($stat['umumiy'] ?? 1));
$oz_natija      = round($togri_javoblar / $umumiy * 100);
$eng_yaxshi     = (int)  ($stat['eng_yaxshi'] ?? 0);

// Faol obuna
$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

// Davom etayotgan test
$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

// Oxirgi 5 natija
$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

// 7 kunlik faollik (grafik uchun)
$grafik_raw = db_barcha(
    'SELECT DATE(tugagan) AS sana, COUNT(*) AS son,
            ROUND(AVG(togri_son/umumiy_son*100)) AS oz
     FROM natijalar
     WHERE foydalanuvchi_id = ?
       AND holat = "tugagan"
       AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(tugagan)
     ORDER BY sana',
    [$f['id']]
);

// Oxirgi 7 kun uchun to'liq ro'yxat (bo'sh kunlar ham)
$grafik = [];
for ($i = 6; $i >= 0; $i--) {
    $sana = date('Y-m-d', strtotime("-{$i} days"));
    $grafik[$sana] = ['sana' => $sana, 'son' => 0, 'oz' => 0];
}
foreach ($grafik_raw as $g) {
    if (isset($grafik[$g['sana']])) {
        $grafik[$g['sana']] = $g;
    }
}
$grafik      = array_values($grafik);
$grafik_maks = max(array_column($grafik, 'son')) ?: 1;

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8">

    <!-- Salomlashish -->
    <div class="mb-8 fade-up">
        <h1 class="text-3xl font-display mb-1">
            <?= e(t('salom')) ?>, <span class="text-blue-400"><?= e($f['ism']) ?></span> 👋
        </h1>
        <p class="text-brand-muted">Bugun ham mukammallikka bir qadam yaqinlashing!</p>
    </div>

    <!-- Statistika kartalari -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $cards = [
            ['icon'=>'📝', 'qiymat'=>$jami_test,      'nom'=>t('umumiy_test'),   'rang'=>'blue',   'delay'=>'.05s'],
            ['icon'=>'✅', 'qiymat'=>$togri_javoblar,  'nom'=>t('togri_javoblar'),'rang'=>'green',  'delay'=>'.10s'],
            ['icon'=>'📊', 'qiymat'=>$oz_natija.'%',   'nom'=>t('oz_natija'),     'rang'=>'indigo', 'delay'=>'.15s'],
            ['icon'=>'🏆', 'qiymat'=>$eng_yaxshi.'%',  'nom'=>'Eng yaxshi',       'rang'=>'yellow', 'delay'=>'.20s'],
        ];
        foreach ($cards as $c):
        ?>
        <div class="glass-card glass-card-hover p-5 fade-up" style="animation-delay:<?= $c['delay'] ?>">
            <div class="w-10 h-10 rounded-xl bg-<?= $c['rang'] ?>-500/15 text-<?= $c['rang'] ?>-400 flex items-center justify-center text-lg mb-3">
                <?= $c['icon'] ?>
            </div>
            <div class="text-2xl font-display font-bold tabnum"><?= $c['qiymat'] ?></div>
            <div class="text-xs text-brand-muted mt-1 uppercase tracking-wide"><?= e($c['nom']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Obuna + davom etayotgan test -->
    <div class="grid md:grid-cols-2 gap-4 mb-8">
        <!-- Obuna holati -->
        <div class="glass-card p-5 fade-up <?= $obuna ? 'border-yellow-500/30 bg-yellow-500/[0.03]' : '' ?>">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl <?= $obuna ? 'bg-yellow-500/20 text-yellow-400' : 'bg-white/8 text-white/30' ?> flex items-center justify-center text-2xl flex-shrink-0">
                        <?= $obuna ? '⭐' : '🔒' ?>
                    </span>
                    <div>
                        <p class="text-xs text-brand-muted uppercase tracking-wide mb-0.5"><?= e(t('obuna_holati')) ?></p>
                        <?php if ($obuna): ?>
                            <p class="font-display font-semibold text-yellow-400"><?= e($obuna['tarif_nomi']) ?></p>
                            <p class="text-xs text-brand-muted">
                                <?= e(t('tugaydigan_sana')) ?>:
                                <?= e(sana($obuna['tugash'], 'd.m.Y')) ?>
                                <?php
                                $kun_qoldi = (int) ((strtotime($obuna['tugash']) - time()) / 86400);
                                $rang = $kun_qoldi <= 3 ? 'text-red-400' : ($kun_qoldi <= 7 ? 'text-yellow-400' : 'text-green-400');
                                ?>
                                <span class="<?= $rang ?> font-medium">(<?= $kun_qoldi ?> kun)</span>
                            </p>
                        <?php else: ?>
                            <p class="font-medium text-brand-muted"><?= e(t('obuna_yoq')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!$obuna): ?>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="btn-primary text-sm py-2 px-4 flex-shrink-0">
                        <?= e(t('tarif_olish')) ?>
                    </a>
                <?php else: ?>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="btn-ghost text-xs py-1.5 px-3 flex-shrink-0">Yangilash</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Davom etayotgan test -->
        <?php if ($davom): ?>
        <div class="glass-card p-5 fade-up border-blue-500/30 bg-blue-500/[0.03]">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-2xl flex-shrink-0 animate-pulse-slow">⏳</span>
                    <div>
                        <p class="text-xs text-brand-muted uppercase tracking-wide mb-0.5">Davom etayotgan test</p>
                        <p class="font-semibold">№<?= (int)$davom['raqam'] ?> — <?= e(mb_substr($davom['nomi'], 0, 25)) ?></p>
                        <p class="text-xs text-brand-muted"><?= e(vaqt_oldin($davom['boshlangan'])) ?> boshlangan</p>
                    </div>
                </div>
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>" class="btn-primary text-sm py-2 px-4 flex-shrink-0">
                    <?= e(t('davom_etish')) ?> →
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="glass-card p-5 fade-up flex items-center gap-4">
            <span class="w-11 h-11 rounded-xl bg-green-500/15 text-green-400 flex items-center justify-center text-2xl flex-shrink-0">🚀</span>
            <div class="flex-1 min-w-0">
                <p class="font-semibold mb-0.5">Yangi test boshlash</p>
                <p class="text-xs text-brand-muted">Barcha biletlar va savollar sizni kutmoqda</p>
            </div>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary text-sm py-2 px-4 flex-shrink-0"><?= e(t('yangi_test')) ?></a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Asosiy kontent -->
    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Oxirgi natijalar -->
        <div class="lg:col-span-2 glass-card p-6 fade-up">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-display"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary text-sm py-2 px-4">📝 <?= e(t('yangi_test')) ?></a>
            </div>

            <?php if (empty($oxirgi)): ?>
                <div class="py-16 text-center text-brand-muted">
                    <div class="text-5xl mb-4">📋</div>
                    <p class="font-medium mb-1">Hali natijalar yo'q</p>
                    <p class="text-sm"><?= e(t('natijalar_yoq')) ?></p>
                    <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary mt-4 text-sm">Birinchi testni boshlash →</a>
                </div>
            <?php else: ?>
                <div class="space-y-1">
                    <?php foreach ($oxirgi as $r):
                        $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                        $rang = natija_rang($foiz);
                    ?>
                        <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/[0.05] transition group">
                            <div class="w-12 h-12 rounded-xl bg-<?= $rang ?>-500/15 text-<?= $rang ?>-400 flex items-center justify-center font-display font-bold text-sm flex-shrink-0 tabnum">
                                <?= $foiz ?>%
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">№<?= (int)$r['raqam'] ?> — <?= e($r['nomi']) ?></p>
                                <p class="text-xs text-brand-muted">
                                    <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> to'g'ri · <?= e(vaqt_oldin($r['tugagan'])) ?>
                                </p>
                            </div>
                            <!-- Progress bar -->
                            <div class="w-16 hidden sm:block">
                                <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-<?= $rang ?>-400 rounded-full" style="width:<?= $foiz ?>%"></div>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-brand-muted group-hover:text-white transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 pt-4 border-t border-white/[0.07]">
                    <a href="<?= e(SAYT_URL) ?>/test" class="text-sm text-blue-400 hover:underline">Barcha biletlarni ko'rish →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 7 kunlik faollik + tezkor harakatlar -->
        <div class="space-y-4">
            <!-- Faollik grafigi -->
            <div class="glass-card p-5 fade-up">
                <h2 class="text-lg font-display mb-4">📅 7 kunlik faollik</h2>
                <div class="space-y-2">
                    <?php foreach ($grafik as $g): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-14 text-xs text-brand-muted tabnum"><?= date('d.m', strtotime($g['sana'])) ?></span>
                            <div class="flex-1 h-6 bg-white/[0.04] rounded-lg overflow-hidden relative">
                                <?php if ($g['son'] > 0):
                                    $w = round($g['son'] / $grafik_maks * 100);
                                ?>
                                    <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-500 rounded-lg flex items-center justify-end px-2"
                                         style="width:<?= $w ?>%">
                                        <span class="text-xs font-semibold tabnum"><?= $g['son'] ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="h-full flex items-center px-2">
                                        <span class="text-xs text-white/20">—</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tezkor harakatlar -->
            <div class="glass-card p-5 fade-up">
                <h2 class="text-lg font-display mb-3">⚡ Tezkor harakatlar</h2>
                <div class="space-y-1">
                    <?php
                    $shortcuts = [
                        [SAYT_URL.'/test',    '📝', t('biletlar_royxati'),     'Testni boshlang'],
                        [SAYT_URL.'/tolov',   '💎', t('tariflar'),             'Obuna yangilash'],
                        [SAYT_URL.'/referal', '🎁', t('referal'),              'Do\'st taklif qiling'],
                        [SAYT_URL.'/profil',  '👤', t('profil'),               'Sozlamalar'],
                    ];
                    foreach ($shortcuts as [$href, $icon, $nom, $tavsif]):
                    ?>
                    <a href="<?= e($href) ?>"
                       class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white/[0.05] transition group">
                        <span class="text-lg"><?= $icon ?></span>
                        <div class="flex-1">
                            <p class="text-sm font-medium"><?= e($nom) ?></p>
                            <p class="text-xs text-brand-muted"><?= e($tavsif) ?></p>
                        </div>
                        <svg class="w-4 h-4 text-white/20 group-hover:text-white/60 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
