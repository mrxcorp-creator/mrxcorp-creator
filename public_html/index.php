<?php
/**
 * AvtoTest Pro — Bosh sahifa
 * Mehmon uchun 1 soatlik HTML kesh (til bo'yicha)
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

$til_kod    = $_SESSION['til'] ?? TIL_DEFAULT;
$kesh_fayli = CACHE_PATH . '/indeks_' . $til_kod . '.html';
$kesh_ttl   = 3600;

if (!joriy_foydalanuvchi() && file_exists($kesh_fayli) && (time() - filemtime($kesh_fayli)) < $kesh_ttl) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}
ob_start();

// ── Ma'lumotlar ─────────────────────────────────────────────
$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar  = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 9');
$bilet_son   = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son   = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

$sahifa_sarlavha = SAYT_NOMI . ' — ' . t('hero_sarlavha');
$sahifa_tavsif   = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="relative max-w-7xl mx-auto px-4 pt-20 pb-28 text-center">

    <!-- Floating badge -->
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full
                bg-blue-500/10 border border-blue-500/25 text-blue-300 text-sm
                mb-8 fade-up animate-float">
        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
        🚗 O'zbekistondagi #1 avto maktab tayyorgarlik platformasi
    </div>

    <!-- Heading -->
    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-display font-black tracking-tight leading-[1.08] mb-6 fade-up"
        style="animation-delay:.1s">
        <?= e(t('hero_sarlavha')) ?>
        <br>
        <span class="grad-text">birinchi urinishdan!</span>
    </h1>

    <p class="text-lg md:text-xl text-white/50 max-w-2xl mx-auto mb-10 leading-relaxed fade-up"
       style="animation-delay:.2s">
        <?= e(t('hero_tavsif')) ?>
    </p>

    <!-- CTA tugmalari -->
    <div class="flex flex-wrap items-center justify-center gap-3 mb-16 fade-up" style="animation-delay:.3s">
        <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-xl">
            🚀 <?= e(t('hero_tugma_boshla')) ?>
        </a>
        <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost btn-xl">
            <?= e(t('hero_tugma_demo')) ?> →
        </a>
    </div>

    <!-- Statistika kartalar -->
    <div class="grid grid-cols-3 max-w-2xl mx-auto gap-4">
        <?php
        $hero_stats = [
            [$bilet_son.'+',         'Bilet',          '.35s'],
            [$savol_son.'+',         'Real savol',      '.45s'],
            [$foydalanuvchi_son.'+', 'Foydalanuvchi',   '.55s'],
        ];
        foreach ($hero_stats as [$v, $n, $d]):
        ?>
        <div class="glass-card py-5 px-4 fade-up" style="animation-delay:<?= $d ?>">
            <div class="text-2xl sm:text-3xl font-display font-black text-blue-400 tabnum"><?= $v ?></div>
            <div class="text-xs text-white/40 mt-1 uppercase tracking-wide"><?= $n ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════ XUSUSIYATLAR ═══════════════ -->
<section class="max-w-7xl mx-auto px-4 py-24">
    <div class="text-center mb-16 fade-up">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-3">Nima uchun biz?</p>
        <h2 class="text-3xl md:text-4xl font-display font-black mb-4">Imtihonni o'tkazishga eng yaxshi yo'l</h2>
        <p class="text-white/50 max-w-xl mx-auto">Eng yangi savollar, kuchli texnologiya va qulay muhit</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php
        $xususiyatlar = [
            ['📚', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif'), 'from-blue-500 to-cyan-500',    'bg-blue-500/10 text-blue-400'],
            ['💾', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif'), 'from-emerald-500 to-teal-500', 'bg-emerald-500/10 text-emerald-400'],
            ['🤖', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif'), 'from-violet-500 to-purple-600','bg-violet-500/10 text-violet-400'],
            ['📊', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif'), 'from-amber-500 to-orange-500', 'bg-amber-500/10 text-amber-400'],
        ];
        foreach ($xususiyatlar as $i => [$ico, $nom, $tavsif, $grad, $cls]):
        ?>
        <div class="glass-card glass-card-hover p-6 fade-up group" style="animation-delay:<?= 0.07*($i+1) ?>s">
            <div class="w-14 h-14 rounded-2xl <?= $cls ?> flex items-center justify-center text-3xl mb-5
                        group-hover:scale-110 transition-transform duration-300">
                <?= $ico ?>
            </div>
            <h3 class="font-display font-bold text-lg mb-2"><?= e($nom) ?></h3>
            <p class="text-sm text-white/50 leading-relaxed"><?= e($tavsif) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════ TARIFLAR ═══════════════ -->
<section id="tariflar" class="max-w-7xl mx-auto px-4 py-24">
    <div class="text-center mb-16 fade-up">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-3">Narxlar</p>
        <h2 class="text-3xl md:text-4xl font-display font-black mb-4"><?= e(t('tariflar_sarlavha')) ?></h2>
        <p class="text-white/50"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-16 text-center text-white/40"><?= e(t('tariflar_yoq')) ?></div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php foreach ($tariflar as $i => $tar): ?>
        <div class="glass-card glass-card-hover p-6 fade-up relative flex flex-col
            <?= $tar['mashhur'] ? 'border-blue-500/40 bg-blue-500/[0.04]' : '' ?>"
             style="animation-delay:<?= 0.07*($i+1) ?>s">

            <?php if ($tar['mashhur']): ?>
            <div class="absolute -top-4 left-0 right-0 flex justify-center">
                <span class="px-4 py-1.5 rounded-full text-xs font-bold
                             bg-gradient-to-r from-blue-600 to-violet-600 text-white
                             shadow-lg shadow-blue-500/25">
                    ⭐ <?= e(t('mashhur')) ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <h3 class="font-display font-bold text-lg"><?= e($tar['nomi']) ?></h3>
                <?php if ((float)($tar['eski_narx']??0) > (float)$tar['narx']): ?>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-white/30 line-through text-sm"><?= e(pul($tar['eski_narx'])) ?></span>
                    <span class="badge badge-green text-xs">-<?= round((1-$tar['narx']/$tar['eski_narx'])*100) ?>%</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="text-4xl font-display font-black text-white mb-1 tabnum">
                <?= number_format((float)$tar['narx'],0,'.',' ') ?>
            </div>
            <div class="text-sm text-white/40 mb-5">so'm</div>

            <p class="text-sm text-white/50 leading-relaxed flex-1 mb-6"><?= e($tar['tavsif']) ?></p>

            <a href="<?= e(SAYT_URL) ?>/register"
               class="btn <?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full">
                <?= e(t('tarif_olish')) ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- ═══════════════ FIKRLAR ═══════════════ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" class="max-w-7xl mx-auto px-4 py-24">
    <div class="text-center mb-16 fade-up">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-3">Mijozlar haqida</p>
        <h2 class="text-3xl md:text-4xl font-display font-black mb-4"><?= e(t('fikrlar_sarlavha')) ?></h2>
        <p class="text-white/50">Ular nima deydi? O'qib ko'ring</p>
    </div>

    <div class="columns-1 sm:columns-2 lg:columns-3 gap-5 space-y-5">
        <?php foreach ($fikrlar as $i => $fikr): ?>
        <div class="glass-card p-5 break-inside-avoid fade-up" style="animation-delay:<?= 0.06*($i+1) ?>s">
            <!-- Stars -->
            <div class="flex gap-0.5 mb-3">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <svg class="w-4 h-4 <?= $s <= (int)$fikr['baho'] ? 'text-amber-400' : 'text-white/15' ?>"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <?php endfor; ?>
            </div>

            <p class="text-sm text-white/70 leading-relaxed mb-4 line-clamp-4">"<?= e($fikr['matn']) ?>"</p>

            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                            flex items-center justify-center font-bold text-white text-sm flex-shrink-0">
                    <?= e(mb_strtoupper(mb_substr($fikr['ism'],0,1))) ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white"><?= e($fikr['ism']) ?></p>
                    <p class="text-xs text-white/35"><?= e(sana($fikr['yaratilgan'],'d.m.Y')) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Fikr qoldirish formasi -->
    <div class="max-w-xl mx-auto mt-12">
        <div class="glass-card p-6 fade-up"
             x-data="{ baho: 5, matn: '', loading: false, ok: false }">
            <h3 class="font-display font-bold text-lg mb-1 text-center">💬 Fikr qoldiring</h3>
            <p class="text-sm text-white/40 text-center mb-5">Tajribangiz boshqalarga yordam beradi</p>

            <div x-show="ok" x-cloak
                 class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm text-center mb-4">
                ✅ Fikringiz uchun rahmat! Tez orada qo'shiladi.
            </div>

            <form x-show="!ok"
                  @submit.prevent="
                    if(!matn.trim()) return;
                    loading=true;
                    window.apiPost('<?= e(SAYT_URL) ?>/api/fikr_qoshish.php',{matn,baho})
                      .then(r => { if(r.ok){ok=true}else{alert(r.xato||'Xato');loading=false} })
                      .catch(()=>loading=false)
                  " class="space-y-4">
                <!-- Yulduzlar -->
                <div class="flex justify-center gap-1" x-data="">
                    <template x-for="i in [1,2,3,4,5]" :key="i">
                        <button type="button" @click="baho=i"
                                class="text-2xl transition-transform hover:scale-125"
                                :class="i<=baho ? 'text-amber-400' : 'text-white/20'">★</button>
                    </template>
                </div>
                <div>
                    <textarea x-model="matn" required
                              placeholder="Platforma va o'z tajribangiz haqida yozing..."
                              class="field" rows="3" maxlength="1000"></textarea>
                    <p class="text-xs text-white/30 mt-1 text-right" x-text="matn.length+'/1000'"></p>
                </div>
                <button type="submit"
                        class="btn btn-primary w-full"
                        :disabled="loading || matn.trim().length < 5">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading?'Yuborilmoqda...':'📤 Yuborish'">📤 Yuborish</span>
                </button>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="max-w-6xl mx-auto px-4 py-24">
    <div class="relative overflow-hidden rounded-3xl p-10 sm:p-16 text-center fade-up
                bg-gradient-to-br from-blue-600/20 via-violet-600/15 to-blue-600/20
                border border-blue-500/25">
        <!-- Glow orb -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="w-96 h-96 rounded-full bg-blue-600/15 filter blur-[80px]"></div>
        </div>

        <div class="relative">
            <div class="text-5xl mb-5">🏁</div>
            <h2 class="text-3xl md:text-5xl font-display font-black mb-4">Bugun boshla!</h2>
            <p class="text-white/60 text-lg max-w-lg mx-auto mb-8">
                Demo testni bepul yeching. Ro'yxatdan o'tish uchun 30 soniya yetarli.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-xl">
                    🚀 <?= e(t('royxatdan_otish')) ?>
                </a>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-ghost btn-xl">
                    <?= e(t('kirish')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════ ALOQA ═══════════════ -->
<section id="aloqa" class="max-w-5xl mx-auto px-4 py-24">
    <div class="text-center mb-14 fade-up">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-3">Yordam kerakmi?</p>
        <h2 class="text-3xl md:text-4xl font-display font-black mb-4"><?= e(t('aloqa')) ?></h2>
        <p class="text-white/50">Har qanday savolga javob berishga tayyormiz</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <?php
        $aloqa_items = [
            ['📞','Telefon',       sozlama('aloqa_telefon',''),  'tel:'.sozlama('aloqa_telefon',''),   'blue',   'bg-blue-500/10 text-blue-400'],
            ['✉️','Email',          sozlama('aloqa_email',''),    'mailto:'.sozlama('aloqa_email',''),  'emerald','bg-emerald-500/10 text-emerald-400'],
            ['📱','Telegram kanal', 'Obuna bo\'lish',             sozlama('telegram_kanal','#'),        'sky',    'bg-sky-500/10 text-sky-400'],
        ];
        foreach ($aloqa_items as $i => [$ico, $nom, $qiymat, $href, $rang, $cls]):
            if (!$qiymat || $qiymat === '#') continue;
        ?>
        <a href="<?= e($href) ?>"
           <?= $rang === 'sky' ? 'target="_blank" rel="noopener"' : '' ?>
           class="glass-card glass-card-hover p-6 text-center fade-up group"
           style="animation-delay:<?= 0.08*($i+1) ?>s">
            <div class="w-14 h-14 mx-auto rounded-2xl <?= $cls ?> flex items-center justify-center text-3xl mb-4
                        group-hover:scale-110 transition-transform duration-200">
                <?= $ico ?>
            </div>
            <p class="text-xs text-white/40 uppercase tracking-wide mb-1"><?= e($nom) ?></p>
            <p class="font-semibold text-white/90 truncate"><?= e($qiymat) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

// Keshga yozish
if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) mkdir(CACHE_PATH, 0755, true);
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
