<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi boshqaruv paneli (dashboard)
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

// ----- Statistika -----
$stat = db_qator(
    'SELECT
        COUNT(*) AS jami,
        COALESCE(SUM(togri_son), 0) AS togri,
        COALESCE(SUM(xato_son), 0)  AS xato,
        COALESCE(SUM(umumiy_son), 0) AS umumiy
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
    [$f['id']]
);
$jami_test = (int) ($stat['jami'] ?? 0);
$togri_javoblar = (int) ($stat['togri'] ?? 0);
$umumiy = max(1, (int) ($stat['umumiy'] ?? 1));
$oz_natija = round(($togri_javoblar / $umumiy) * 100);

// ----- Faol obuna -----
$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi, t.nomi_cyrl AS tarif_nomi_cyrl
     FROM obunalar o JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

// ----- Davom etayotgan test -----
$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi, b.nomi_cyrl
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

// ----- Oxirgi natijalar (5 ta) -----
$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi, b.nomi_cyrl
     FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

// ----- 7 kunlik grafik -----
$grafik = db_barcha(
    'SELECT DATE(tugagan) AS sana, COUNT(*) AS son, AVG(togri_son/umumiy_son*100) AS oz
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(tugagan)
     ORDER BY sana',
    [$f['id']]
);

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <!-- Salomlashish -->
    <div class="mb-8 fade-up">
        <h1 class="text-3xl md:text-4xl mb-2">
            <?= e(t('salom')) ?>, <span class="gradient-text"><?= e(fu_ism($f) ?: $f['ism']) ?></span> 👋
        </h1>
        <p class="text-app-2 text-base">Bugun nimani o'rganamiz?</p>
    </div>

    <!-- Statistika kartalar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <!-- Jami testlar -->
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden" style="animation-delay:.05s">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20 blur-2xl"
                 style="background: linear-gradient(135deg, #3B82F6, #2563EB);"></div>
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-md"
                         style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    </div>
                    <span class="text-xs text-muted-app uppercase tracking-wider font-medium"><?= e(t('umumiy_test')) ?></span>
                </div>
                <div class="text-3xl font-display font-extrabold text-app"><?= $jami_test ?></div>
            </div>
        </div>

        <!-- To'g'ri javoblar -->
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden" style="animation-delay:.1s">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20 blur-2xl"
                 style="background: linear-gradient(135deg, #10B981, #16A34A);"></div>
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-md"
                         style="background: linear-gradient(135deg, #10B981, #16A34A);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </div>
                    <span class="text-xs text-muted-app uppercase tracking-wider font-medium"><?= e(t('togri_javoblar')) ?></span>
                </div>
                <div class="text-3xl font-display font-extrabold text-app"><?= $togri_javoblar ?></div>
            </div>
        </div>

        <!-- O'rtacha ball -->
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden" style="animation-delay:.15s">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20 blur-2xl"
                 style="background: linear-gradient(135deg, #6366F1, #8B5CF6);"></div>
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-md"
                         style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    </div>
                    <span class="text-xs text-muted-app uppercase tracking-wider font-medium"><?= e(t('oz_natija')) ?></span>
                </div>
                <div class="text-3xl font-display font-extrabold text-app"><?= $oz_natija ?>%</div>
            </div>
        </div>

        <!-- Obuna -->
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden" style="animation-delay:.2s">
            <?php if ($obuna): ?>
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-25 blur-2xl"
                     style="background: linear-gradient(135deg, #F59E0B, #EF4444);"></div>
            <?php endif; ?>
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md text-white"
                         style="background: <?= $obuna ? 'linear-gradient(135deg, #F59E0B, #EF4444)' : 'var(--bg-elevated); color: var(--text-muted)' ?>;">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4 6v6c0 4.5 3 8.5 8 9.5 5-1 8-5 8-9.5V6l-8-4z"/></svg>
                    </div>
                    <span class="text-xs text-muted-app uppercase tracking-wider font-medium"><?= e(t('obuna_holati')) ?></span>
                </div>
                <?php if ($obuna): ?>
                    <div class="text-base font-display font-bold text-app truncate"><?= e(tk(['nomi' => $obuna['tarif_nomi'], 'nomi_cyrl' => $obuna['tarif_nomi_cyrl']], 'nomi')) ?></div>
                    <div class="text-xs text-app-2 mt-1"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'], 'd.m.Y')) ?></div>
                <?php else: ?>
                    <div class="text-base font-display font-bold text-app-2"><?= e(t('obuna_yoq')) ?></div>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="text-xs text-accent hover:underline mt-1 inline-block"><?= e(t('tarif_olish')) ?> →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Davom etayotgan test -->
    <?php if ($davom): ?>
        <div class="glass-card-premium p-6 mb-8 fade-up relative overflow-hidden"
             style="border-color: color-mix(in srgb, var(--warning) 40%, transparent);">
            <div class="absolute -top-20 -right-20 w-60 h-60 rounded-full opacity-20 blur-3xl"
                 style="background: var(--gradient-warm);"></div>
            <div class="relative flex items-center justify-between flex-wrap gap-4">
                <div class="min-w-0">
                    <div class="badge badge-warning mb-2">
                        <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                        Davom etayotgan test
                    </div>
                    <h3 class="text-xl font-display text-app truncate">№<?= (int)$davom['raqam'] ?> — <?= e(tk($davom, 'nomi')) ?></h3>
                    <p class="text-sm text-app-2 mt-1">Boshlangan: <?= e(vaqt_oldin($davom['boshlangan'])) ?></p>
                </div>
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>" class="btn-primary flex-shrink-0">
                    <?= e(t('davom_etish')) ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Chap: oxirgi natijalar -->
        <div class="lg:col-span-2 glass-card p-6 fade-up">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display text-app"><?= e(t('oxirgi_natijalar')) ?></h2>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-primary text-sm py-2 px-4">
                    <?= e(t('yangi_test')) ?>
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
                <div class="py-16 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4 opacity-50"
                         style="background: var(--bg-elevated);">
                        <svg class="w-8 h-8 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <p class="text-app-2"><?= e(t('natijalar_yoq')) ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($oxirgi as $r):
                        $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                        $rang_grad = $foiz >= 90 ? 'linear-gradient(135deg, #10B981, #16A34A)' :
                                    ($foiz >= 70 ? 'linear-gradient(135deg, #3B82F6, #2563EB)' :
                                    ($foiz >= 50 ? 'linear-gradient(135deg, #F59E0B, #D97706)' :
                                                   'linear-gradient(135deg, #EF4444, #DC2626)'));
                    ?>
                        <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-glass transition group">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0 shadow-md"
                                     style="background: <?= $rang_grad ?>;">
                                    <?= $foiz ?>%
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-app truncate">№<?= (int)$r['raqam'] ?> — <?= e(tk($r, 'nomi')) ?></div>
                                    <div class="text-xs text-app-2 mt-0.5">
                                        <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> · <?= e(vaqt_oldin($r['tugagan'])) ?>
                                    </div>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-app-2 group-hover:text-app group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- O'ng: 7 kunlik faollik -->
        <div class="glass-card p-6 fade-up">
            <h2 class="text-xl font-display mb-5 text-app">7 kunlik faollik</h2>
            <?php if (empty($grafik)): ?>
                <div class="py-12 text-center text-app-2 text-sm">
                    Grafik uchun ma'lumot kam
                </div>
            <?php else: ?>
                <div class="space-y-2.5">
                    <?php
                    $maks = max(array_column($grafik, 'son')) ?: 1;
                    foreach ($grafik as $g):
                        $w = round($g['son'] / $maks * 100);
                    ?>
                        <div class="flex items-center gap-2.5 text-sm">
                            <span class="w-14 text-app-2 text-xs"><?= e(date('d.m', strtotime($g['sana']))) ?></span>
                            <div class="flex-1 h-7 rounded-lg overflow-hidden" style="background: var(--bg-elevated);">
                                <div class="h-full rounded-lg flex items-center justify-end pr-2 text-xs font-semibold text-white shadow-sm"
                                     style="width:<?= $w ?>%; background: var(--gradient-primary);">
                                    <?= (int)$g['son'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-app">
                <h3 class="font-display mb-3 text-sm text-app">Tezkor harakatlar</h3>
                <div class="space-y-1.5">
                    <a href="<?= e(SAYT_URL) ?>/profil" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-glass text-sm transition group">
                        <span class="flex items-center gap-2.5 text-app">
                            <svg class="w-4 h-4 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <?= e(t('profil')) ?>
                        </span>
                        <span class="text-app-2 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/referal" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-glass text-sm transition group">
                        <span class="flex items-center gap-2.5 text-app">
                            <svg class="w-4 h-4 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0z"/></svg>
                            <?= e(t('referal')) ?>
                        </span>
                        <span class="text-app-2 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-glass text-sm transition group">
                        <span class="flex items-center gap-2.5 text-app">
                            <svg class="w-4 h-4 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5z"/></svg>
                            <?= e(t('tariflar')) ?>
                        </span>
                        <span class="text-app-2 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
