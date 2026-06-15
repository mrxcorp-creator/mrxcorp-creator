<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

$kesh_fayli = CACHE_PATH . '/indeks_' . ($_SESSION['til'] ?? 'uz_latn') . '.html';
$kesh_muddat = 3600;

if (!joriy_foydalanuvchi() && file_exists($kesh_fayli) && (time() - filemtime($kesh_fayli)) < $kesh_muddat) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}

ob_start();

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

<section class="relative max-w-7xl mx-auto px-4 pt-10 sm:pt-16 pb-20 text-center">
    <div class="fade-up">
        <span class="chip chip-grad mb-6">
            <span class="w-2 h-2 rounded-full bg-pink animate-pulse"></span>
            <?= e(t('hero_belgi')) ?>
        </span>

        <h1 class="text-4xl sm:text-6xl md:text-7xl font-display font-extrabold tracking-tight mb-6 leading-[1.05]">
            <?= e(t('hero_sarlavha')) ?>
            <br><span class="grad-text"><?= e(t('hero_aksent')) ?></span>
        </h1>

        <p class="text-lg md:text-xl text-muted max-w-2xl mx-auto mb-10 leading-relaxed">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary text-base py-4 px-8">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/></svg>
                <?= e(t('hero_tugma_boshla')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost text-base py-4 px-8">
                <?= e(t('hero_tugma_demo')) ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-3 max-w-3xl mx-auto mt-16 gap-3 sm:gap-5">
            <div class="glass p-5 fade-up" style="animation-delay:.1s">
                <div class="text-3xl md:text-4xl font-display font-extrabold grad-text"><?= $bilet_son ?>+</div>
                <div class="text-xs sm:text-sm text-muted mt-1"><?= e(t('stat_bilet')) ?></div>
            </div>
            <div class="glass p-5 fade-up" style="animation-delay:.2s">
                <div class="text-3xl md:text-4xl font-display font-extrabold grad-text"><?= $savol_son ?>+</div>
                <div class="text-xs sm:text-sm text-muted mt-1"><?= e(t('stat_savol')) ?></div>
            </div>
            <div class="glass p-5 fade-up" style="animation-delay:.3s">
                <div class="text-3xl md:text-4xl font-display font-extrabold grad-text"><?= $foydalanuvchi_son ?>+</div>
                <div class="text-xs sm:text-sm text-muted mt-1"><?= e(t('stat_foydalanuvchi')) ?></div>
            </div>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-12 fade-up">
    <div class="ring-grad mx-auto max-w-5xl">
        <img src="<?= e(SAYT_URL) ?>/assets/img/banner.svg" alt="VatanParvar Yaypan" loading="lazy"
             class="w-full h-auto rounded-[1.2rem]">
    </div>
</section>

<section id="xususiyatlar" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <span class="chip chip-grad mb-4">⚡ <?= e(t('xususiyatlar')) ?></span>
        <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-3"><?= e(t('xus_sarlavha')) ?></h2>
        <p class="text-muted text-lg"><?= e(t('xus_tavsif')) ?></p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $xususiyatlar = [
            ['📚', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif'), 'cyan'],
            ['💾', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif'), 'success'],
            ['🤖', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif'), 'violet'],
            ['📊', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif'), 'amber'],
            ['📱', t('xususiyat_5_sarlavha'), t('xususiyat_5_tavsif'), 'pink'],
            ['🔒', t('xususiyat_6_sarlavha'), t('xususiyat_6_tavsif'), 'cyan'],
        ];
        foreach ($xususiyatlar as $i => $x):
        ?>
            <div class="glass glass-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <div class="w-14 h-14 rounded-2xl bg-<?= $x[3] ?>/15 text-<?= $x[3] ?> flex items-center justify-center text-3xl mb-4">
                    <?= $x[0] ?>
                </div>
                <h3 class="font-display font-bold text-lg mb-2"><?= e($x[1]) ?></h3>
                <p class="text-sm text-muted leading-relaxed"><?= e($x[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="tariflar" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <span class="chip chip-grad mb-4">💎 <?= e(t('tariflar')) ?></span>
        <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-3"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-muted text-lg"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass p-12 text-center text-muted">
            <p><?= e(t('tariflar_yoq')) ?></p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($tariflar as $i => $tar): ?>
                <?php if ($tar['mashhur']): ?>
                    <div class="ring-grad fade-up relative" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                        <div class="p-6 relative">
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 chip chip-grad font-bold whitespace-nowrap">
                                ⭐ <?= e(t('mashhur')) ?>
                            </span>
                            <h3 class="font-display font-bold text-xl mb-2"><?= e($tar['nomi']) ?></h3>
                            <div class="flex items-baseline gap-2 mb-1">
                                <span class="text-4xl font-display font-extrabold grad-text"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                                <span class="text-muted text-sm">so'm</span>
                            </div>
                            <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                                <div class="line-through text-muted text-sm mb-3"><?= e(pul($tar['eski_narx'])) ?></div>
                            <?php endif; ?>
                            <p class="text-sm text-muted mb-5 min-h-[3rem]"><?= e($tar['tavsif']) ?></p>
                            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary w-full"><?= e(t('tarif_olish')) ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="glass glass-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                        <h3 class="font-display font-bold text-xl mb-2"><?= e($tar['nomi']) ?></h3>
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-4xl font-display font-extrabold text-white"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                            <span class="text-muted text-sm">so'm</span>
                        </div>
                        <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                            <div class="line-through text-muted text-sm mb-3"><?= e(pul($tar['eski_narx'])) ?></div>
                        <?php endif; ?>
                        <p class="text-sm text-muted mb-5 min-h-[3rem]"><?= e($tar['tavsif']) ?></p>
                        <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-ghost w-full"><?= e(t('tarif_olish')) ?></a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12 fade-up">
        <span class="chip chip-grad mb-4">💬 <?= e(t('fikrlar')) ?></span>
        <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-3"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-muted text-lg"><?= e(t('fikrlar_tavsif')) ?></p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($fikrlar as $i => $fikr): ?>
            <div class="glass glass-hover p-6 fade-up" style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-2xl grad-bg flex items-center justify-center font-bold text-white">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </div>
                    <div>
                        <div class="font-medium"><?= e($fikr['ism']) ?></div>
                        <div class="text-amber text-sm tracking-wider"><?= str_repeat('★', (int)$fikr['baho']) ?></div>
                    </div>
                </div>
                <p class="text-muted text-sm leading-relaxed"><?= e($fikr['matn']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="max-w-5xl mx-auto px-4 py-16">
    <div class="ring-grad fade-up">
        <div class="p-10 sm:p-16 text-center relative overflow-hidden">
            <div class="absolute inset-0 grad-bg-soft -z-10"></div>
            <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-4"><?= e(t('cta_sarlavha')) ?></h2>
            <p class="text-muted text-lg mb-8 max-w-xl mx-auto"><?= e(t('cta_tavsif')) ?></p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary text-base py-4 px-8"><?= e(t('royxatdan_otish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-ghost text-base py-4 px-8"><?= e(t('kirish')) ?></a>
            </div>
        </div>
    </div>
</section>

<section id="aloqa" class="max-w-5xl mx-auto px-4 py-16">
    <div class="text-center mb-10 fade-up">
        <span class="chip chip-grad mb-4">📞 <?= e(t('aloqa_sarlavha')) ?></span>
        <h2 class="text-3xl md:text-5xl font-display font-extrabold mb-3"><?= e(t('aloqa')) ?></h2>
        <p class="text-muted text-lg"><?= e(t('aloqa_tavsif')) ?></p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="glass glass-hover p-6 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan/15 text-cyan flex items-center justify-center mb-3 text-2xl">📞</div>
            <div class="text-sm text-muted mb-1">Telefon</div>
            <div class="font-display font-bold"><?= e(sozlama('aloqa_telefon')) ?></div>
        </a>
        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="glass glass-hover p-6 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet/15 text-violet flex items-center justify-center mb-3 text-2xl">✉️</div>
            <div class="text-sm text-muted mb-1">Email</div>
            <div class="font-display font-bold break-all"><?= e(sozlama('aloqa_email')) ?></div>
        </a>
        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="glass glass-hover p-6 text-center fade-up">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-pink/15 text-pink flex items-center justify-center mb-3 text-2xl">📱</div>
            <div class="text-sm text-muted mb-1">Telegram</div>
            <div class="font-display font-bold">Kanalga obuna</div>
        </a>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) {
        mkdir(CACHE_PATH, 0755, true);
    }
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
