<?php
/**
 * VatanParvar Yaypan — Dashboard
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$stat = db_qator(
    'SELECT COUNT(*) AS jami,
            COALESCE(SUM(togri_son),0) AS togri,
            COALESCE(SUM(umumiy_son),0) AS umumiy,
            COALESCE(MAX(ROUND(togri_son/NULLIF(umumiy_son,0)*100)),0) AS eng_yaxshi
     FROM natijalar WHERE foydalanuvchi_id = ? AND holat = "tugagan"',
    [$f['id']]
);
$jami_test  = (int) ($stat['jami'] ?? 0);
$togri      = (int) ($stat['togri'] ?? 0);
$umumiy     = max(1, (int) ($stat['umumiy'] ?? 1));
$oz_natija  = round($togri / $umumiy * 100);
$eng_yaxshi = (int) ($stat['eng_yaxshi'] ?? 0);

$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$davom = db_qator(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "davom"
     ORDER BY n.boshlangan DESC LIMIT 1',
    [$f['id']]
);

$oxirgi = db_barcha(
    'SELECT n.*, b.raqam, b.nomi FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
     ORDER BY n.tugagan DESC LIMIT 5',
    [$f['id']]
);

$grafik_raw = db_barcha(
    'SELECT DATE(tugagan) AS sana, COUNT(*) AS son
     FROM natijalar
     WHERE foydalanuvchi_id = ? AND holat = "tugagan"
       AND tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(tugagan)',
    [$f['id']]
);
$grafik_map  = array_column($grafik_raw, null, 'sana');
$grafik_maks = max(array_column($grafik_raw, 'son') ?: [1]);

$sahifa_sarlavha = t('boshqaruv_paneli');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main style="max-width:1200px; margin:0 auto; padding: 2.5rem 1.25rem 4rem;">

    <!-- Sarlavha -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start;
                flex-wrap:wrap; gap:1rem; margin-bottom: 2.5rem;">
        <div>
            <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                      letter-spacing:.08em; margin-bottom:.5rem;">
                <?= e(t('boshqaruv_paneli')) ?>
            </p>
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.75rem, 4vw, 2.5rem);">
                <?= e(t('salom')) ?>, <?= e($f['ism']) ?>.
            </h1>
        </div>
        <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary">
            <?= e(t('yangi_test')) ?> →
        </a>
    </div>

    <!-- Statistika kartalari -->
    <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                border: 1px solid #000; margin-bottom: 1rem;"
         class="grid-cols-2 lg:grid-cols-4">
        <?php
        $cards = [
            [t('umumiy_test'),    $jami_test],
            [t('togri_javoblar'), $togri],
            [t('oz_natija'),      $oz_natija . '%'],
            ['Eng yaxshi',        $eng_yaxshi . '%'],
        ];
        foreach ($cards as $i => [$nom, $val]):
            $border = '';
            if ($i % 2 !== 0) $border .= 'border-left: 1px solid #E5E5E5;';
            if ($i >= 2) $border .= 'border-top: 1px solid #E5E5E5;';
        ?>
        <div style="padding: 1.5rem; <?= $border ?>"
             class="lg:border-l lg:border-l-[#E5E5E5] lg:[&:first-child]:border-l-0 lg:border-t-0">
            <div style="font-size:.75rem; color:#666; text-transform:uppercase;
                        letter-spacing:.08em; margin-bottom:.65rem;">
                <?= e($nom) ?>
            </div>
            <div style="font-family:Georgia,serif; font-weight:700;
                        font-size: 2rem; line-height: 1;" class="tabnum">
                <?= e($val) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Obuna + Davom etayotgan test -->
    <div style="display:grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 2rem;"
         class="md:grid-cols-2">

        <!-- Obuna -->
        <?php if ($obuna):
            $kun_qoldi = max(0, (int) ((strtotime($obuna['tugash']) - time()) / 86400));
        ?>
        <div class="b-card" style="padding: 1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;
                        gap:1rem; margin-bottom: 1rem;">
                <div style="font-size:.78rem; color:#666; text-transform:uppercase;
                            letter-spacing:.08em;">
                    <?= e(t('obuna_holati')) ?>
                </div>
                <span class="badge badge-filled">Faol</span>
            </div>
            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.5rem; margin-bottom: .5rem;">
                <?= e($obuna['tarif_nomi']) ?>
            </h3>
            <p style="font-size:.875rem; color:#555;">
                <?= e(t('tugaydigan_sana')) ?>:
                <strong><?= e(sana($obuna['tugash'], 'd.m.Y')) ?></strong>
                <span style="color:#000;">— <?= $kun_qoldi ?> kun qoldi</span>
            </p>
        </div>
        <?php else: ?>
        <div class="b-card" style="padding: 1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;
                        gap:1rem; margin-bottom: 1rem;">
                <div style="font-size:.78rem; color:#666; text-transform:uppercase;
                            letter-spacing:.08em;">
                    <?= e(t('obuna_holati')) ?>
                </div>
                <span class="badge badge-light">Yo'q</span>
            </div>
            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.25rem; margin-bottom: .75rem;">
                <?= e(t('obuna_yoq')) ?>
            </h3>
            <p style="font-size:.875rem; color:#555; margin-bottom: 1rem;">
                Pullik biletlarga kirish uchun tarif oling.
            </p>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="btn btn-primary btn-sm">
                <?= e(t('tarif_olish')) ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Davom etayotgan test -->
        <?php if ($davom): ?>
        <div class="b-card" style="padding: 1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;
                        gap:1rem; margin-bottom: 1rem;">
                <div style="font-size:.78rem; color:#666; text-transform:uppercase;
                            letter-spacing:.08em;">
                    Davom etayotgan
                </div>
                <span class="badge">Tugatish kerak</span>
            </div>
            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.25rem; margin-bottom: .5rem;">
                №<?= (int)$davom['raqam'] ?> — <?= e($davom['nomi']) ?>
            </h3>
            <p style="font-size:.85rem; color:#555; margin-bottom: 1rem;">
                Boshlangan: <?= e(vaqt_oldin($davom['boshlangan'])) ?>
            </p>
            <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$davom['bilet_id'] ?>"
               class="btn btn-primary btn-sm">
                <?= e(t('davom_etish')) ?> →
            </a>
        </div>
        <?php else: ?>
        <div class="b-card" style="padding: 1.5rem;">
            <div style="font-size:.78rem; color:#666; text-transform:uppercase;
                        letter-spacing:.08em; margin-bottom: 1rem;">
                Tezkor harakat
            </div>
            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.25rem; margin-bottom: .5rem;">
                Yangi testni boshlash
            </h3>
            <p style="font-size:.85rem; color:#555; margin-bottom: 1rem;">
                Barcha biletlar va savollar tayyor.
            </p>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary btn-sm">
                <?= e(t('biletlar_royxati')) ?> →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Asosiy kontent -->
    <div style="display:grid; grid-template-columns: 1fr; gap: 1rem;"
         class="lg:grid-cols-3">

        <!-- Oxirgi natijalar -->
        <div class="b-card" style="padding: 1.5rem; grid-column: 1 / span 1;"
             class="lg:col-span-2">
            <div style="display:flex; justify-content:space-between; align-items:center;
                        margin-bottom: 1.25rem; padding-bottom: .75rem;
                        border-bottom: 1px solid #000;">
                <h2 style="font-family:Georgia,serif; font-weight:700; font-size: 1.15rem;">
                    <?= e(t('oxirgi_natijalar')) ?>
                </h2>
                <a href="<?= e(SAYT_URL) ?>/test"
                   style="font-size:.82rem; color:#000;
                          text-decoration:underline; text-underline-offset:3px;">
                    Barchasi →
                </a>
            </div>

            <?php if (empty($oxirgi)): ?>
            <div style="text-align:center; padding: 2.5rem 0;">
                <p style="font-size:.95rem; color:#666; margin-bottom: 1rem;">
                    <?= e(t('natijalar_yoq')) ?>
                </p>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary btn-sm">
                    Birinchi testni boshlash →
                </a>
            </div>
            <?php else: ?>
            <div>
                <?php foreach ($oxirgi as $i => $r):
                    $foiz = $r['umumiy_son'] > 0
                        ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
                ?>
                <a href="<?= e(SAYT_URL) ?>/test?natija=<?= (int)$r['id'] ?>"
                   style="display:flex; align-items:center; gap:1rem;
                          padding: .85rem 0; text-decoration:none;
                          <?= $i < count($oxirgi) - 1 ? 'border-bottom: 1px solid #E5E5E5;' : '' ?>"
                   onmouseover="this.style.background='#F5F5F5'"
                   onmouseout="this.style.background='transparent'">

                    <!-- Foiz -->
                    <div style="width:54px; height:54px; flex-shrink:0;
                                border:1px solid #000;
                                display:flex; align-items:center; justify-content:center;
                                font-family:Georgia,serif; font-weight:700;
                                font-size: .95rem;" class="tabnum">
                        <?= $foiz ?>%
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:.9rem; color:#000;
                                    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            №<?= (int)$r['raqam'] ?> — <?= e($r['nomi']) ?>
                        </div>
                        <div style="font-size:.78rem; color:#666; margin-top:.2rem;">
                            <?= (int)$r['togri_son'] ?>/<?= (int)$r['umumiy_son'] ?> ·
                            <?= e(vaqt_oldin($r['tugagan'])) ?>
                        </div>
                    </div>

                    <span style="font-size:1rem; color:#000;">→</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right column -->
        <div style="display:flex; flex-direction:column; gap: 1rem;">

            <!-- 7 kunlik faollik -->
            <div class="b-card" style="padding: 1.5rem;">
                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: .9rem; margin-bottom: 1rem;
                           padding-bottom: .65rem; border-bottom: 1px solid #000;
                           text-transform:uppercase; letter-spacing: .05em;">
                    7 kunlik faollik
                </h3>
                <?php for ($i = 6; $i >= 0; $i--):
                    $sana_k = date('Y-m-d', strtotime("-{$i} days"));
                    $g = $grafik_map[$sana_k] ?? ['son' => 0];
                    $son = (int)$g['son'];
                    $w = $son > 0 ? max(8, round($son / $grafik_maks * 100)) : 0;
                ?>
                <div style="display:flex; align-items:center; gap:.65rem;
                            margin-bottom: .5rem; font-size:.8rem;">
                    <span style="width:42px; color:#666;" class="tabnum">
                        <?= date('d.m', strtotime($sana_k)) ?>
                    </span>
                    <div style="flex:1; height: 16px; border:1px solid #E5E5E5;
                                background:#FAFAFA; position:relative;">
                        <?php if ($w > 0): ?>
                        <div style="height: 100%; background: #000; width: <?= $w ?>%;"></div>
                        <?php endif; ?>
                    </div>
                    <span style="width:24px; text-align:right; font-weight:600;" class="tabnum">
                        <?= $son ?: '·' ?>
                    </span>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Tezkor havolalar -->
            <div class="b-card" style="padding: 1.5rem;">
                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: .9rem; margin-bottom: 1rem;
                           padding-bottom: .65rem; border-bottom: 1px solid #000;
                           text-transform:uppercase; letter-spacing: .05em;">
                    Tezkor havolalar
                </h3>
                <?php
                $shortcuts = [
                    [SAYT_URL.'/test',    t('biletlar_royxati')],
                    [SAYT_URL.'/tolov',   t('tariflar')],
                    [SAYT_URL.'/referal', t('referal')],
                    [SAYT_URL.'/profil',  t('profil')],
                ];
                foreach ($shortcuts as $i => [$href, $nom]):
                ?>
                <a href="<?= e($href) ?>"
                   style="display:flex; justify-content:space-between; align-items:center;
                          padding: .65rem 0; text-decoration:none; color:#000;
                          font-size:.875rem;
                          <?= $i < count($shortcuts) - 1 ? 'border-bottom: 1px solid #E5E5E5;' : '' ?>"
                   onmouseover="this.style.background='#F5F5F5'"
                   onmouseout="this.style.background='transparent'">
                    <?= e($nom) ?>
                    <span>→</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
