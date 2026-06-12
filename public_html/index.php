<?php
/**
 * VatanParvar Yaypan — Bosh sahifa
 *
 * Bu sahifa 1 soatlik kesh bilan ishlaydi.
 * Kesh fayli: /kesh/indeks_keshi.html
 * Admin biror narsani o'zgartirsa (tarif, fikr) — kesh tozalanadi.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

// ============================================================
// KESH
// ============================================================
$kesh_fayli = CACHE_PATH . '/indeks_' . ($_SESSION['til'] ?? 'uz_latn') . '.html';
$kesh_muddat = 3600; // 1 soat

// Foydalanuvchi kirgan bo'lsa kesh ishlatmaymiz
if (!joriy_foydalanuvchi() && file_exists($kesh_fayli) && (time() - filemtime($kesh_fayli)) < $kesh_muddat) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}

ob_start();

// ============================================================
// MA'LUMOTLARNI OLISH
// ============================================================
$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar  = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 9');
$bilet_son = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

$sahifa_sarlavha = t('sayt_nomi') . ' — ' . t('hero_sarlavha');
$sahifa_tavsif = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="relative max-w-7xl mx-auto px-4 pt-16 pb-24 text-center">
    <div class="fade-up">
        <span class="inline-block px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-sm mb-6">
            🚗 #1 Avto maktab nazariyasi platformasi
        </span>

        <h1 class="text-4xl sm:text-6xl md:text-7xl font-display font-bold tracking-tight mb-6 leading-tight">
            <?= e(t('hero_sarlavha')) ?>
            <br><span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">birinchi urinishdan!</span>
        </h1>

        <p class="text-lg md:text-xl text-brand-muted max-w-2xl mx-auto mb-8 leading-relaxed">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-lg py-4 px-8">
                🚀 <?= e(t('hero_tugma_boshla')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost text-lg py-4 px-8">
                <?= e(t('hero_tugma_demo')) ?> →
            </a>
        </div>

        <!-- Statistika -->
        <div class="grid grid-cols-3 max-w-2xl mx-auto mt-16 gap-4">
            <div class="glass-card p-4 fade-up" style="animation-delay:.1s">
                <div class="text-2xl md:text-3xl font-display font-bold text-blue-400"><?= $bilet_son ?>+</div>
                <div class="text-xs text-brand-muted mt-1">Bilet</div>
            </div>
            <div class="glass-card p-4 fade-up" style="animation-delay:.2s">
                <div class="text-2xl md:text-3xl font-display font-bold text-blue-400"><?= $savol_son ?>+</div>
                <div class="text-xs text-brand-muted mt-1">Real savol</div>
            </div>
            <div class="glass-card p-4 fade-up" style="animation-delay:.3s">
                <div class="text-2xl md:text-3xl font-display font-bold text-blue-400"><?= $foydalanuvchi_son ?>+</div>
                <div class="text-xs text-brand-muted mt-1">Foydalanuvchi</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     XUSUSIYATLAR
     ============================================================ -->
<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <h2 class="text-3xl md:text-4xl mb-3">Nima uchun bizni tanlashadi?</h2>
        <p class="text-brand-muted">Eng yaxshi tajriba va eng yangilangan ma'lumotlar</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $xususiyatlar = [
            ['📚', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif'), 'blue'],
            ['💾', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif'), 'green'],
            ['🤖', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif'), 'purple'],
            ['📊', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif'), 'yellow'],
        ];
        foreach ($xususiyatlar as $i => $x):
        ?>
            <div class="glass-card glass-card-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <div class="w-14 h-14 rounded-xl bg-<?= $x[3] ?>-500/20 text-<?= $x[3] ?>-400 flex items-center justify-center text-3xl mb-4">
                    <?= $x[0] ?>
                </div>
                <h3 class="font-display text-lg mb-2"><?= e($x[1]) ?></h3>
                <p class="text-sm text-brand-muted leading-relaxed"><?= e($x[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     TARIFLAR
     ============================================================ -->
<section id="tariflar" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <h2 class="text-3xl md:text-4xl mb-3"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-brand-muted"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-12 text-center text-brand-muted">
            <p><?= e(t('tariflar_yoq')) ?></p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($tariflar as $i => $tar): ?>
                <div class="glass-card glass-card-hover p-6 fade-up relative
                    <?= $tar['mashhur'] ? 'border-blue-500/50 bg-blue-500/5' : '' ?>"
                     style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                    <?php if ($tar['mashhur']): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-500 to-indigo-500 text-white whitespace-nowrap">
                            ⭐ <?= e(t('mashhur')) ?>
                        </span>
                    <?php endif; ?>

                    <h3 class="font-display text-xl mb-2"><?= e($tar['nomi']) ?></h3>

                    <div class="flex items-baseline gap-2 mb-3">
                        <span class="text-4xl font-display font-bold text-blue-400"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                        <span class="text-brand-muted text-sm">so'm</span>
                    </div>

                    <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                        <div class="line-through text-brand-muted text-sm mb-3">
                            <?= e(pul($tar['eski_narx'])) ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-sm text-brand-muted mb-5 min-h-[3rem]"><?= e($tar['tavsif']) ?></p>

                    <a href="<?= e(SAYT_URL) ?>/register" class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full">
                        <?= e(t('tarif_olish')) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============================================================
     FOYDALANUVCHILAR FIKRI
     ============================================================ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <h2 class="text-3xl md:text-4xl mb-3"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-brand-muted">Ular haqimizda nima deydi</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($fikrlar as $i => $fikr): ?>
            <div class="glass-card p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </div>
                    <div>
                        <div class="font-medium"><?= e($fikr['ism']) ?></div>
                        <div class="text-yellow-400 text-sm"><?= str_repeat('★', (int)$fikr['baho']) ?></div>
                    </div>
                </div>
                <p class="text-brand-muted text-sm leading-relaxed">
                    <?= e($fikr['matn']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CTA
     ============================================================ -->
<section class="max-w-5xl mx-auto px-4 py-16">
    <div class="glass-card p-10 sm:p-16 text-center relative overflow-hidden fade-up">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 via-transparent to-indigo-500/20 -z-10"></div>

        <h2 class="text-3xl md:text-5xl font-display font-bold mb-4">
            Bugun boshlang!
        </h2>
        <p class="text-brand-muted text-lg mb-8 max-w-xl mx-auto">
            Demo testni bepul yeching va platformaning qulayligini his qiling.
        </p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-lg py-4 px-8">
                <?= e(t('royxatdan_otish')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/login" class="btn-ghost text-lg py-4 px-8">
                <?= e(t('kirish')) ?>
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     ALOQA
     ============================================================ -->
<section id="aloqa" class="max-w-5xl mx-auto px-4 py-16">
    <div class="text-center mb-10 fade-up">
        <h2 class="text-3xl md:text-4xl mb-3"><?= e(t('aloqa')) ?></h2>
        <p class="text-brand-muted">Savol bormi? Bog'lanishingiz mumkin</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="glass-card glass-card-hover p-6 text-center fade-up">
            <div class="w-12 h-12 mx-auto rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center mb-3 text-xl">📞</div>
            <div class="text-sm text-brand-muted mb-1">Telefon</div>
            <div class="font-display"><?= e(sozlama('aloqa_telefon')) ?></div>
        </a>
        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="glass-card glass-card-hover p-6 text-center fade-up">
            <div class="w-12 h-12 mx-auto rounded-full bg-green-500/20 text-green-400 flex items-center justify-center mb-3 text-xl">✉️</div>
            <div class="text-sm text-brand-muted mb-1">Email</div>
            <div class="font-display"><?= e(sozlama('aloqa_email')) ?></div>
        </a>
        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="glass-card glass-card-hover p-6 text-center fade-up">
            <div class="w-12 h-12 mx-auto rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center mb-3 text-xl">📱</div>
            <div class="text-sm text-brand-muted mb-1">Telegram</div>
            <div class="font-display">Kanalga obuna</div>
        </a>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

// ============================================================
// KESHGA YOZISH (faqat mehmon foydalanuvchilar uchun)
// ============================================================
if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) {
        mkdir(CACHE_PATH, 0755, true);
    }
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
