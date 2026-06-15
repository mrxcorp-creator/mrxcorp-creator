<?php
/**
 * AvtoTest Pro — Bosh sahifa
 * Mehmon foydalanuvchilar uchun til bo'yicha 1 soatlik kesh.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

// ── KESH ──────────────────────────────────────────────────────
$til_kod     = $_SESSION['til'] ?? TIL_DEFAULT;
$kesh_fayli  = CACHE_PATH . '/indeks_' . $til_kod . '.html';
$kesh_muddat = 3600;

if (!joriy_foydalanuvchi()
    && file_exists($kesh_fayli)
    && (time() - filemtime($kesh_fayli)) < $kesh_muddat
) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}

ob_start();

// ── MA'LUMOTLAR ───────────────────────────────────────────────
$tariflar          = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar           = db_barcha('SELECT * FROM fikrlar  WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 9');
$bilet_son         = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son         = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

$sahifa_sarlavha = SAYT_NOMI . ' — ' . t('hero_sarlavha');
$sahifa_tavsif   = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="relative max-w-7xl mx-auto px-4 pt-16 pb-24 text-center">
    <div class="fade-up">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-400 text-sm mb-8">
            🚗 O'zbekistondagi #1 avto maktab tayyorgarlik platformasi
        </span>

        <h1 class="text-4xl sm:text-6xl md:text-7xl font-display font-black tracking-tight mb-6 leading-[1.1]">
            <?= e(t('hero_sarlavha')) ?>
            <br>
            <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-500 bg-clip-text text-transparent">birinchi urinishdan!</span>
        </h1>

        <p class="text-lg md:text-xl text-brand-muted max-w-2xl mx-auto mb-10 leading-relaxed">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-4 px-8">
                🚀 <?= e(t('hero_tugma_boshla')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost text-base py-4 px-8">
                <?= e(t('hero_tugma_demo')) ?> →
            </a>
        </div>
    </div>

    <!-- Statistika -->
    <div class="grid grid-cols-3 max-w-2xl mx-auto mt-20 gap-4">
        <?php
        $stats = [
            [$bilet_son . '+',         'Bilet',           '.1s'],
            [$savol_son . '+',         'Real savol',      '.2s'],
            [$foydalanuvchi_son . '+', 'Foydalanuvchi',   '.3s'],
        ];
        foreach ($stats as [$qiymat, $nom, $delay]):
        ?>
            <div class="glass-card p-5 fade-up" style="animation-delay:<?= $delay ?>">
                <div class="text-2xl md:text-3xl font-display font-bold text-blue-400 tabnum"><?= $qiymat ?></div>
                <div class="text-xs text-brand-muted mt-1 uppercase tracking-wide"><?= $nom ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════ XUSUSIYATLAR ═══════════════════ -->
<section class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <h2 class="text-3xl md:text-4xl font-display mb-3">Nima uchun bizni tanlashadi?</h2>
        <p class="text-brand-muted max-w-xl mx-auto">Eng yangi ma'lumotlar, kuchli texnologiya va qulay interfeys</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $xususiyatlar = [
            ['📚', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif'), 'blue'],
            ['💾', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif'), 'green'],
            ['🤖', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif'), 'purple'],
            ['📊', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif'), 'yellow'],
        ];
        foreach ($xususiyatlar as $i => [$emoji, $nom, $tavsif, $rang]):
        ?>
            <div class="glass-card glass-card-hover p-6 fade-up" style="animation-delay:<?= 0.06*($i+1) ?>s">
                <div class="w-14 h-14 rounded-2xl bg-<?= $rang ?>-500/15 text-<?= $rang ?>-400 flex items-center justify-center text-3xl mb-5">
                    <?= $emoji ?>
                </div>
                <h3 class="font-display text-lg mb-2"><?= e($nom) ?></h3>
                <p class="text-sm text-brand-muted leading-relaxed"><?= e($tavsif) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════ TARIFLAR ═══════════════════ -->
<section id="tariflar" class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <h2 class="text-3xl md:text-4xl font-display mb-3"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-brand-muted"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-12 text-center text-brand-muted"><?= e(t('tariflar_yoq')) ?></div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($tariflar as $i => $tar): ?>
                <div class="glass-card glass-card-hover p-6 fade-up relative
                    <?= $tar['mashhur'] ? 'border-blue-500/50 bg-blue-500/[0.05] shadow-glow-blue' : '' ?>"
                     style="animation-delay:<?= 0.06*($i+1) ?>s">

                    <?php if ($tar['mashhur']): ?>
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white whitespace-nowrap shadow-glow-sm">
                            ⭐ <?= e(t('mashhur')) ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="font-display text-xl mb-2"><?= e($tar['nomi']) ?></h3>

                    <?php if ((float)($tar['eski_narx'] ?? 0) > (float)$tar['narx']): ?>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="line-through text-brand-muted text-sm"><?= e(pul($tar['eski_narx'])) ?></span>
                            <span class="badge badge-green text-xs">-<?= round((1 - $tar['narx']/$tar['eski_narx'])*100) ?>%</span>
                        </div>
                    <?php endif; ?>

                    <div class="text-4xl font-display font-black text-blue-400 mb-1 tabnum">
                        <?= e(number_format((float)$tar['narx'], 0, '.', ' ')) ?>
                    </div>
                    <div class="text-brand-muted text-sm mb-5">so'm</div>

                    <p class="text-sm text-brand-muted leading-relaxed mb-6 min-h-[3rem]"><?= e($tar['tavsif']) ?></p>

                    <a href="<?= e(SAYT_URL) ?>/register"
                       class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full">
                        <?= e(t('tarif_olish')) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ═══════════════════ FIKRLAR ═══════════════════ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-14 fade-up">
        <h2 class="text-3xl md:text-4xl font-display mb-3"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-brand-muted">Ular haqimizda nima deydi</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($fikrlar as $i => $fikr): ?>
            <div class="glass-card p-6 fade-up" style="animation-delay:<?= 0.05*($i+1) ?>s">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-lg flex-shrink-0">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </div>
                    <div>
                        <p class="font-semibold"><?= e($fikr['ism']) ?></p>
                        <div class="text-yellow-400 text-sm">
                            <?= str_repeat('★', (int)$fikr['baho']) ?><?= str_repeat('☆', 5-(int)$fikr['baho']) ?>
                        </div>
                    </div>
                </div>
                <p class="text-brand-muted text-sm leading-relaxed line-clamp-4"><?= e($fikr['matn']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Fikr qoldirish (AJAX) -->
    <div class="max-w-lg mx-auto mt-10 glass-card p-6 fade-up"
         x-data="{ loading: false, ok: false, matn: '', baho: 5 }">
        <h3 class="font-display text-lg mb-4 text-center">💬 Fikr qoldiring</h3>
        <div x-show="ok" x-cloak class="p-3 rounded-xl bg-green-500/10 border border-green-500/25 text-green-300 text-sm text-center mb-4">
            ✅ Fikringiz uchun rahmat! Moderatsiyadan so'ng ko'rinadi.
        </div>
        <form x-show="!ok" @submit.prevent="
            loading = true;
            window.apiPost('<?= e(SAYT_URL) ?>/api/fikr_qoshish.php', { matn, baho })
                .then(r => { if(r.ok) { ok=true; } else { alert(r.xato || 'Xato'); loading=false; } })
                .catch(() => { loading=false; })">

            <div class="mb-3">
                <label class="field-label">Baho</label>
                <div class="flex gap-2 mt-1">
                    <template x-for="i in [5,4,3,2,1]" :key="i">
                        <button type="button" @click="baho = i"
                                :class="baho >= i ? 'text-yellow-400' : 'text-white/20'"
                                class="text-2xl transition-colors">★</button>
                    </template>
                </div>
            </div>
            <div class="mb-4">
                <label class="field-label">Fikringiz</label>
                <textarea x-model="matn" required rows="3" class="field"
                          placeholder="Platformadan foydalanish tajribangiz haqida yozing..."></textarea>
            </div>
            <button type="submit" class="btn-primary w-full" :disabled="loading || matn.length < 5">
                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span x-text="loading ? 'Yuborilmoqda...' : '📤 Yuborish'">📤 Yuborish</span>
            </button>
        </form>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════ CTA ═══════════════════ -->
<section class="max-w-5xl mx-auto px-4 py-20">
    <div class="glass-card p-10 sm:p-16 text-center relative overflow-hidden fade-up">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 via-transparent to-indigo-600/20 pointer-events-none"></div>
        <h2 class="text-3xl md:text-5xl font-display font-black mb-5">Bugun boshlang!</h2>
        <p class="text-brand-muted text-lg mb-8 max-w-xl mx-auto">
            Demo testni bepul yeching va platformaning qulayligini his qiling.
        </p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary text-base py-4 px-8">
                🚀 <?= e(t('royxatdan_otish')) ?>
            </a>
            <a href="<?= e(SAYT_URL) ?>/login" class="btn-ghost text-base py-4 px-8">
                <?= e(t('kirish')) ?>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════ ALOQA ═══════════════════ -->
<section id="aloqa" class="max-w-5xl mx-auto px-4 py-20">
    <div class="text-center mb-12 fade-up">
        <h2 class="text-3xl md:text-4xl font-display mb-3"><?= e(t('aloqa')) ?></h2>
        <p class="text-brand-muted">Savol bormi? Bog'lanishingiz mumkin</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <?php
        $aloqa = [
            ['📞', 'Telefon',       sozlama('aloqa_telefon'), 'tel:' . sozlama('aloqa_telefon'),  'blue'],
            ['✉️', 'Email',          sozlama('aloqa_email'),   'mailto:' . sozlama('aloqa_email'), 'green'],
            ['📱', 'Telegram kanal', 'Obuna bo\'lish',         sozlama('telegram_kanal') ?? '#',  'sky'],
        ];
        foreach ($aloqa as $i => [$emoji, $nom, $qiymat, $href, $rang]):
            if (!$qiymat) continue;
        ?>
            <a href="<?= e($href) ?>" <?= $rang === 'sky' ? 'target="_blank" rel="noopener"' : '' ?>
               class="glass-card glass-card-hover p-6 text-center fade-up"
               style="animation-delay:<?= 0.08*($i+1) ?>s">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-<?= $rang ?>-500/15 text-<?= $rang ?>-400 flex items-center justify-center text-3xl mb-4">
                    <?= $emoji ?>
                </div>
                <p class="text-brand-muted text-sm mb-1"><?= e($nom) ?></p>
                <p class="font-display font-semibold truncate"><?= e($qiymat) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

// ── KESHGA YOZISH ─────────────────────────────────────────────
if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) {
        mkdir(CACHE_PATH, 0755, true);
    }
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
