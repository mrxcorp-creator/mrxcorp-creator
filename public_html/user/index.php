<?php
/**
 * AvtoTest Pro — Foydalanuvchi dashboard
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

// ── Statistika ──────────────────────────────────────────────
$stat = db_qator(
    'SELECT COUNT(*) AS jami,
            COALESCE(SUM(togri_son),0)   AS togri,
            COALESCE(SUM(xato_son),0)    AS xato,
            COALESCE(SUM(umumiy_son),0)  AS umumiy,
            COALESCE(MAX(ROUND(togri_son/NULLIF(umumiy_son,0)*100)),0) AS eng_yaxshi
     FROM natijalar WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
    [$f['id']]
);
$jami_test   = (int)  ($stat['jami']       ?? 0);
$togri       = (int)  ($stat['togri']      ?? 0);
$umumiy      = max(1, (int)($stat['umumiy'] ?? 1));
$oz_natija   = round($togri / $umumiy * 100);
$eng_yaxshi  = (int)  ($stat['eng_yaxshi'] ?? 0);

// ── Faol obuna ──────────────────────────────────────────────
$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

// ── Davom etayotgan test ─────────────────────────────────────
$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

// ── Oxirgi 5 natija ──────────────────────────────────────────
$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

// ── 7 kunlik grafik ──────────────────────────────────────────
$grafik_raw = db_barcha(
    'SELECT DATE(tugagan) AS sana,
            COUNT(*) AS son,
            ROUND(AVG(togri_son/NULLIF(umumiy_son,0)*100)) AS oz
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"
       AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(tugagan)',
    [$f['id']]
);
$grafik_map  = array_column($grafik_raw, null, 'sana');
$grafik_maks = max(array_map(fn($g) => (int)$g['son'], $grafik_raw ?: [['son'=>0]]) ?: [1]);

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8 pb-16">

    <!-- ── Salomlashish ────────────────────────────────────── -->
    <div class="mb-8 fade-up">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-display font-black mb-1">
                    <?= e(t('salom')) ?>,
                    <span class="grad-text-blue"><?= e($f['ism']) ?></span> 👋
                </h1>
                <p class="text-white/45">Bugun ham bir narsalarni o'rganamiz!</p>
            </div>
            <a href="<?= e(SAYT_URL) ?>/test"
               class="btn btn-primary flex-shrink-0">
                📝 <?= e(t('yangi_test')) ?>
            </a>
        </div>
    </div>

    <!-- ── Statistika kartalari ────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php
        $cards = [
            ['📝', t('umumiy_test'),   $jami_test,          'blue',   ''],
            ['✅', t('togri_javoblar'), $togri,              'emerald',''],
            ['📊', t('oz_natija'),      $oz_natija . '%',    'violet', ''],
            ['🏆', 'Eng yaxshi',        $eng_yaxshi . '%',   'amber',  ''],
        ];
        foreach ($cards as $i => [$ico, $nom, $val, $rang, $sub]):
            $colors = [
                'blue'   => ['bg-blue-500/10',   'text-blue-400',   'border-blue-500/15'],
                'emerald'=> ['bg-emerald-500/10', 'text-emerald-400','border-emerald-500/15'],
                'violet' => ['bg-violet-500/10',  'text-violet-400', 'border-violet-500/15'],
                'amber'  => ['bg-amber-500/10',   'text-amber-400',  'border-amber-500/15'],
            ][$rang];
        ?>
        <div class="glass-card glass-card-hover p-5 border <?= $colors[2] ?> fade-up"
             style="animation-delay:<?= 0.06*$i ?>s">
            <div class="w-11 h-11 rounded-xl <?= $colors[0] ?> <?= $colors[1] ?>
                        flex items-center justify-center text-2xl mb-4">
                <?= $ico ?>
            </div>
            <div class="text-2xl font-display font-black tabnum"><?= e($val) ?></div>
            <div class="text-xs text-white/45 uppercase tracking-wide mt-1"><?= e($nom) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Obuna + davom etayotgan test ───────────────────── -->
    <div class="grid md:grid-cols-2 gap-4 mb-6">

        <!-- Obuna kartasi -->
        <?php if ($obuna):
            $kun_qoldi = max(0, (int)((strtotime($obuna['tugash']) - time()) / 86400));
            $foiz_q = min(100, round($kun_qoldi / 30 * 100));
            $rang = $kun_qoldi <= 3 ? 'red' : ($kun_qoldi <= 7 ? 'amber' : 'emerald');
        ?>
        <div class="glass-card p-5 border border-amber-500/20 bg-amber-500/[0.03] fade-up">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center text-2xl flex-shrink-0">⭐</div>
                    <div>
                        <p class="text-xs text-white/45 uppercase tracking-wide mb-0.5"><?= e(t('obuna_holati')) ?></p>
                        <p class="font-display font-bold text-amber-400"><?= e($obuna['tarif_nomi']) ?></p>
                    </div>
                </div>
                <a href="<?= e(SAYT_URL) ?>/tolov" class="btn btn-ghost btn-sm flex-shrink-0">Yangilash</a>
            </div>
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-white/50"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'],'d.m.Y')) ?></span>
                <span class="font-semibold text-<?= $rang ?>-400"><?= $kun_qoldi ?> kun</span>
            </div>
            <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                <div class="h-full bg-<?= $rang ?>-400 rounded-full transition-all duration-700"
                     style="width:<?= $foiz_q ?>%"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="glass-card p-5 border border-white/[0.08] fade-up">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-xl bg-white/[0.05] text-white/25 flex items-center justify-center text-2xl flex-shrink-0">🔒</div>
                <div>
                    <p class="text-xs text-white/45 uppercase tracking-wide mb-0.5"><?= e(t('obuna_holati')) ?></p>
                    <p class="font-semibold text-white/60"><?= e(t('obuna_yoq')) ?></p>
                </div>
            </div>
            <p class="text-sm text-white/40 mb-4">Pullik biletlarga kirish uchun tarif oling.</p>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="btn btn-primary btn-sm">
                💎 <?= e(t('tarif_olish')) ?> →
            </a>
        </div>
        <?php endif; ?>

        <!-- Davom etayotgan test YOKI yangi test -->
        <?php if ($davom): ?>
        <div class="glass-card p-5 border border-blue-500/25 bg-blue-500/[0.03] fade-up" style="animation-delay:.07s">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center text-2xl flex-shrink-0 animate-pulse">⏳</div>
                    <div>
                        <p class="text-xs text-white/45 uppercase tracking-wide mb-0.5">Davom etayotgan</p>
                        <p class="font-bold text-white/90">№<?= (int)$davom['raqam'] ?> — <?= e(mb_substr($davom['nomi'],0,20)) ?></p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-white/40 mb-4">Boshlangan: <?= e(vaqt_oldin($davom['boshlangan'])) ?></p>
            <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>"
               class="btn btn-primary btn-sm w-full">
                ▶ <?= e(t('davom_etish')) ?>
            </a>
        </div>
        <?php else: ?>
        <div class="glass-card p-5 border border-white/[0.08] fade-up" style="animation-delay:.07s">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl flex-shrink-0">🚀</div>
                <div>
                    <p class="text-xs text-white/45 uppercase tracking-wide mb-0.5">Tayyor</p>
                    <p class="font-semibold text-white/80">Yangi testni boshlang</p>
                </div>
            </div>
            <p class="text-sm text-white/40 mb-4">Barcha biletlar va haqiqiy savollar sizni kutmoqda.</p>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-success btn-sm w-full">
                📝 <?= e(t('biletlar_royxati')) ?> →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Asosiy kontent: Natijalar + Grafik ─────────────── -->
    <div class="grid lg:grid-cols-3 gap-5">

        <!-- Oxirgi natijalar -->
        <div class="lg:col-span-2 glass-card p-6 fade-up">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-display font-bold text-lg"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost btn-sm">
                    Barchasi →
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-white/[0.04] flex items-center justify-center text-4xl mb-4">📋</div>
                <p class="font-semibold text-white/60 mb-1">Hali natijalar yo'q</p>
                <p class="text-sm text-white/35 mb-5"><?= e(t('natijalar_yoq')) ?></p>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary btn-sm">Birinchi testni boshlash →</a>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($oxirgi as $r):
                    $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                    $rang = natija_rang($foiz);
                    $bar_colors = ['green'=>'bg-emerald-500','blue'=>'bg-blue-500','yellow'=>'bg-amber-500','red'=>'bg-red-500'][$rang];
                ?>
                <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                   class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-white/[0.04] transition-all group">

                    <!-- Foiz doirasi (oddiy) -->
                    <div class="w-12 h-12 rounded-xl bg-<?= $rang ?>-500/12 text-<?= $rang ?>-400
                                flex items-center justify-center font-display font-black text-sm tabnum
                                flex-shrink-0 group-hover:scale-105 transition-transform">
                        <?= $foiz ?>%
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-white/90 truncate">
                            №<?= (int)$r['raqam'] ?> — <?= e($r['nomi']) ?>
                        </p>
                        <div class="flex items-center gap-3 mt-1.5">
                            <!-- Progress bar -->
                            <div class="flex-1 h-1.5 bg-white/[0.07] rounded-full overflow-hidden">
                                <div class="h-full <?= $bar_colors ?> rounded-full" style="width:<?= $foiz ?>%"></div>
                            </div>
                            <span class="text-xs text-white/35 tabnum flex-shrink-0">
                                <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?>
                            </span>
                        </div>
                        <p class="text-xs text-white/30 mt-1"><?= e(vaqt_oldin($r['tugagan'])) ?></p>
                    </div>

                    <svg class="w-4 h-4 text-white/20 group-hover:text-white/50 flex-shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- O'ng ustun -->
        <div class="space-y-4">

            <!-- 7 kunlik grafik -->
            <div class="glass-card p-5 fade-up" style="animation-delay:.1s">
                <h2 class="font-display font-bold text-sm uppercase tracking-wide text-white/50 mb-4">📅 7 kunlik faollik</h2>
                <div class="space-y-2">
                    <?php for ($i = 6; $i >= 0; $i--):
                        $sana = date('Y-m-d', strtotime("-{$i} days"));
                        $g    = $grafik_map[$sana] ?? ['son' => 0, 'oz' => 0];
                        $son  = (int)$g['son'];
                        $w    = $son > 0 ? max(10, round($son / $grafik_maks * 100)) : 0;
                    ?>
                    <div class="flex items-center gap-2.5">
                        <span class="w-10 text-xs text-white/35 tabnum flex-shrink-0">
                            <?= date('d.m', strtotime($sana)) ?>
                        </span>
                        <div class="flex-1 h-6 bg-white/[0.04] rounded-lg overflow-hidden relative">
                            <?php if ($son > 0): ?>
                            <div class="h-full bg-gradient-to-r from-blue-600 to-violet-600 rounded-lg
                                        flex items-center justify-end px-2 transition-all duration-700"
                                 style="width:<?= $w ?>%">
                                <span class="text-[10px] font-bold text-white"><?= $son ?></span>
                            </div>
                            <?php else: ?>
                            <div class="h-full flex items-center pl-2.5">
                                <span class="text-xs text-white/20">—</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Tezkor harakatlar -->
            <div class="glass-card p-5 fade-up" style="animation-delay:.15s">
                <h2 class="font-display font-bold text-sm uppercase tracking-wide text-white/50 mb-3">⚡ Tezkor</h2>
                <div class="space-y-1">
                    <?php
                    $shortcuts = [
                        [SAYT_URL.'/test',    '📝', t('biletlar_royxati')],
                        [SAYT_URL.'/tolov',   '💎', t('tariflar')],
                        [SAYT_URL.'/referal', '🎁', t('referal')],
                        [SAYT_URL.'/profil',  '👤', t('profil')],
                    ];
                    foreach ($shortcuts as [$href, $ico, $nom]):
                    ?>
                    <a href="<?= e($href) ?>"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                              text-sm text-white/60 hover:text-white hover:bg-white/[0.05]
                              transition-all group">
                        <span class="text-base w-5 text-center flex-shrink-0"><?= $ico ?></span>
                        <span class="flex-1"><?= e($nom) ?></span>
                        <svg class="w-3.5 h-3.5 text-white/20 group-hover:text-white/40 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
