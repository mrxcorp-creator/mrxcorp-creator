<?php
/**
 * VatanParvar Yaypan — Bosh sahifa (premium dizayn)
 *
 * 1 soatlik kesh bilan ishlaydi.
 * Kesh fayli til bo'yicha bo'linadi: /kesh/indeks_uz_latn.html | indeks_uz_cyrl.html
 * Tema (dark/light) klient tomonida localStorage orqali ishlaydi —
 * shu sababli kesh kalitida tema yo'q.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

// ============================================================
// KESH
// ============================================================
$joriy_til    = $_SESSION['til'] ?? 'uz_latn';
$kesh_fayli   = CACHE_PATH . '/indeks_' . $joriy_til . '.html';
$kesh_muddat  = 3600; // 1 soat

// Mehmon foydalanuvchi va kesh yangi bo'lsa — to'g'ridan-to'g'ri xizmat ko'rsatamiz
if (empty($_SESSION['foydalanuvchi_id'])
    && file_exists($kesh_fayli)
    && (time() - filemtime($kesh_fayli)) < $kesh_muddat) {
    header('X-Cache: HIT');
    header('Content-Type: text/html; charset=utf-8');
    readfile($kesh_fayli);
    exit;
}

ob_start();

// ============================================================
// MA'LUMOTLAR
// ============================================================
$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar  = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 9');
$bilet_son         = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son         = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
$banner            = banner_olish();

$sahifa_sarlavha = t('sayt_nomi') . ' — ' . t('hero_sarlavha');
$sahifa_tavsif   = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================================
     ADMIN BANNER (agar yuklangan va faol bo'lsa)
     ============================================================ -->
<?php if ($banner): ?>
    <section class="max-w-7xl mx-auto px-4 pt-6">
        <?php if ($banner['havola']): ?>
            <a href="<?= e($banner['havola']) ?>" target="_blank" rel="noopener" class="block group">
        <?php else: ?>
            <div class="block">
        <?php endif; ?>
            <img src="<?= e($banner['rasm']) ?>" alt="Banner"
                 class="w-full h-auto rounded-3xl shadow-2xl transition group-hover:scale-[1.01] fade-up">
        <?php if ($banner['havola']): ?>
            </a>
        <?php else: ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="relative max-w-7xl mx-auto px-4 pt-20 pb-16 text-center">
    <div class="fade-up">
        <span class="badge badge-accent mb-6 text-sm">
            <span class="relative flex w-2 h-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background: var(--accent);"></span>
                <span class="relative inline-flex rounded-full h-2 w-2" style="background: var(--accent);"></span>
            </span>
            #1 Avto maktab nazariyasi platformasi
        </span>

        <h1 class="text-5xl sm:text-6xl md:text-7xl font-display font-extrabold tracking-tight mb-6 leading-[1.05]">
            <?= e(t('hero_sarlavha')) ?>
            <br><span class="gradient-text">birinchi urinishdan!</span>
        </h1>

        <p class="text-lg md:text-xl text-app-2 max-w-2xl mx-auto mb-10 leading-relaxed">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-3.5 px-7">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>
                <?= e(t('hero_tugma_boshla')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost text-base py-3.5 px-7">
                <?= e(t('hero_tugma_demo')) ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

        <!-- Statistika -->
        <div class="grid grid-cols-3 max-w-3xl mx-auto mt-20 gap-3 sm:gap-4">
            <?php
            $stats = [
                ['son' => $bilet_son,         'label' => 'Bilet'],
                ['son' => $savol_son,         'label' => 'Real savol'],
                ['son' => $foydalanuvchi_son, 'label' => 'Foydalanuvchi'],
            ];
            foreach ($stats as $i => $s):
            ?>
                <div class="glass-card p-5 fade-up text-center" style="animation-delay:<?= 0.1 * ($i + 1) ?>s">
                    <div class="text-3xl md:text-4xl font-display font-extrabold gradient-text"><?= (int)$s['son'] ?>+</div>
                    <div class="text-xs sm:text-sm text-muted-app mt-1.5"><?= e($s['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     REKLAMA — bosh sahifa yuqori
     ============================================================ -->
<?php $rek_yuqori = reklama_chiqar('bosh_yuqori'); if ($rek_yuqori): ?>
    <section class="max-w-7xl mx-auto px-4">
        <?= $rek_yuqori ?>
    </section>
<?php endif; ?>

<!-- ============================================================
     XUSUSIYATLAR (premium SVG ikonalar)
     ============================================================ -->
<section class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <h2 class="text-3xl md:text-4xl mb-4">Nima uchun bizni tanlashadi?</h2>
        <p class="text-app-2 text-lg max-w-2xl mx-auto">Eng yaxshi tajriba va eng yangilangan ma'lumotlar</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php
        $xususiyatlar = [
            [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>',
                'title' => t('xususiyat_1_sarlavha'),
                'desc'  => t('xususiyat_1_tavsif'),
                'grad'  => 'linear-gradient(135deg, #3B82F6, #2563EB)',
            ],
            [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>',
                'title' => t('xususiyat_2_sarlavha'),
                'desc'  => t('xususiyat_2_tavsif'),
                'grad'  => 'linear-gradient(135deg, #10B981, #16A34A)',
            ],
            [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>',
                'title' => t('xususiyat_3_sarlavha'),
                'desc'  => t('xususiyat_3_tavsif'),
                'grad'  => 'linear-gradient(135deg, #8B5CF6, #6366F1)',
            ],
            [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>',
                'title' => t('xususiyat_4_sarlavha'),
                'desc'  => t('xususiyat_4_tavsif'),
                'grad'  => 'linear-gradient(135deg, #F59E0B, #EF4444)',
            ],
        ];
        foreach ($xususiyatlar as $i => $x):
        ?>
            <div class="glass-card glass-card-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white mb-5 shadow-md"
                     style="background: <?= $x['grad'] ?>">
                    <?= $x['icon'] ?>
                </div>
                <h3 class="font-display text-lg mb-2 text-app"><?= e($x['title']) ?></h3>
                <p class="text-sm text-app-2 leading-relaxed"><?= e($x['desc']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     TARIFLAR (premium kartalar)
     ============================================================ -->
<section id="tariflar" class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <span class="badge badge-accent mb-3"><?= e(t('tariflar')) ?></span>
        <h2 class="text-3xl md:text-4xl mb-4"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-app-2 text-lg max-w-2xl mx-auto"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-12 text-center text-app-2">
            <p><?= e(t('tariflar_yoq')) ?></p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($tariflar as $i => $tar): ?>
                <div class="<?= $tar['mashhur'] ? 'gradient-border' : 'glass-card glass-card-hover' ?> p-7 fade-up relative"
                     style="animation-delay:<?= 0.06 * ($i + 1) ?>s; <?= $tar['mashhur'] ? 'box-shadow: var(--shadow-glow);' : '' ?>">
                    <?php if ($tar['mashhur']): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold text-white whitespace-nowrap shadow-md"
                              style="background: var(--gradient-primary);">
                            ⭐ <?= e(t('mashhur')) ?>
                        </span>
                    <?php endif; ?>

                    <h3 class="font-display text-xl mb-3 text-app"><?= e(tk($tar, 'nomi')) ?></h3>

                    <div class="flex items-baseline gap-1.5 mb-2">
                        <span class="text-4xl font-display font-extrabold gradient-text"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                        <span class="text-app-2 text-sm">so'm</span>
                    </div>

                    <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                        <div class="line-through text-app-2 text-sm mb-3">
                            <?= e(pul($tar['eski_narx'])) ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-sm text-app-2 mb-6 min-h-[3rem] leading-relaxed"><?= e(tk($tar, 'tavsif')) ?></p>

                    <a href="<?= e(SAYT_URL) ?>/register" class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full">
                        <?= e(t('tarif_olish')) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============================================================
     FIKRLAR
     ============================================================ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <span class="badge badge-success mb-3">★ <?= count($fikrlar) ?> sharh</span>
        <h2 class="text-3xl md:text-4xl mb-4"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-app-2 text-lg">Ular haqimizda nima deydi</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($fikrlar as $i => $fikr): ?>
            <div class="glass-card glass-card-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <!-- Tirnoq dekoratsiyasi -->
                <svg class="w-8 h-8 text-accent opacity-30 mb-3" fill="currentColor" viewBox="0 0 32 32"><path d="M9.352 4C4.456 7.456 1 13.12 1 19.36 1 24.832 4.32 28 8.784 28c4.224 0 7.36-3.36 7.36-7.328 0-3.968-2.592-6.88-5.872-6.88-.704 0-1.632.176-1.808.24.464-3.08 3.376-6.752 6.272-8.56L9.352 4zm16 0c-4.832 3.456-8.288 9.12-8.288 15.36 0 5.472 3.32 8.64 7.784 8.64 4.16 0 7.36-3.36 7.36-7.328 0-3.968-2.592-6.88-5.872-6.88-.704 0-1.632.176-1.808.24.464-3.08 3.376-6.752 6.272-8.56L25.352 4z"/></svg>

                <p class="text-app-2 text-sm leading-relaxed mb-5">
                    <?= e(tk($fikr, 'matn')) ?>
                </p>

                <div class="flex items-center gap-3 pt-4 border-t border-app">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-white shadow-md"
                         style="background: var(--gradient-primary);">
                        <?= e(mb_strtoupper(mb_substr(tk($fikr, 'ism'), 0, 1))) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-app truncate"><?= e(tk($fikr, 'ism')) ?></div>
                        <div class="text-yellow-400 text-xs"><?= str_repeat('★', (int)$fikr['baho']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     REKLAMA — bosh sahifa pastki (CTA dan oldin)
     ============================================================ -->
<?php $rek_pastki = reklama_chiqar('bosh_pastki'); if ($rek_pastki): ?>
    <section class="max-w-7xl mx-auto px-4">
        <?= $rek_pastki ?>
    </section>
<?php endif; ?>

<!-- ============================================================
     CTA (premium gradient banner)
     ============================================================ -->
<section class="max-w-5xl mx-auto px-4 py-16">
    <div class="glass-card-premium p-10 sm:p-16 text-center relative overflow-hidden fade-up">
        <!-- Dekorativ gradient -->
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full opacity-30 blur-3xl"
             style="background: var(--gradient-primary);"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full opacity-20 blur-3xl"
             style="background: var(--gradient-warm);"></div>

        <div class="relative">
            <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-5 leading-tight">
                Bugun <span class="gradient-text">boshlang!</span>
            </h2>
            <p class="text-app-2 text-lg mb-8 max-w-xl mx-auto">
                Demo testni bepul yeching va platformaning qulayligini his qiling.
            </p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-3.5 px-8">
                    <?= e(t('royxatdan_otish')) ?>
                </a>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn-ghost text-base py-3.5 px-8">
                    <?= e(t('kirish')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ALOQA
     ============================================================ -->
<section id="aloqa" class="max-w-5xl mx-auto px-4 py-20">
    <div class="text-center mb-12 fade-up">
        <h2 class="text-3xl md:text-4xl mb-4"><?= e(t('aloqa')) ?></h2>
        <p class="text-app-2 text-lg">Savol bormi? Bog'lanishingiz mumkin</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="glass-card glass-card-hover p-7 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4 text-white shadow-md"
                 style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z"/></svg>
            </div>
            <div class="text-sm text-app-2 mb-1">Telefon</div>
            <div class="font-display font-semibold text-app"><?= e(sozlama('aloqa_telefon')) ?></div>
        </a>
        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="glass-card glass-card-hover p-7 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4 text-white shadow-md"
                 style="background: linear-gradient(135deg, #10B981, #059669);">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            </div>
            <div class="text-sm text-app-2 mb-1">Email</div>
            <div class="font-display font-semibold text-app text-sm"><?= e(sozlama('aloqa_email')) ?></div>
        </a>
        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="glass-card glass-card-hover p-7 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4 text-white shadow-md"
                 style="background: linear-gradient(135deg, #06B6D4, #0891B2);">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </div>
            <div class="text-sm text-app-2 mb-1">Telegram</div>
            <div class="font-display font-semibold text-app">Kanalga obuna</div>
        </a>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

// ============================================================
// KESHGA YOZISH (faqat mehmon foydalanuvchilar uchun)
// ============================================================
if (empty($_SESSION['foydalanuvchi_id'])) {
    if (!is_dir(CACHE_PATH)) {
        @mkdir(CACHE_PATH, 0755, true);
    }
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
