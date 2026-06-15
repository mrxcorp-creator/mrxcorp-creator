<?php
/**
 * AvtoTest Pro — Test ishlash sahifasi
 *
 * BUG FIX (timer): qolgan_vaqt DB da hech qachon yangilanmaydi
 * (boshlangan + qolgan_vaqt = tugash deadline).
 * Qolgan vaqt har doim: max(0, $natija['qolgan_vaqt'] - (time() - strtotime($natija['boshlangan'])))
 *
 * Uch rejim:
 *  1) /test              — biletlar ro'yxati
 *  2) /test?bilet=N      — testni ishlash
 *  3) /test?natija=ID    — natijani ko'rish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$bilet_id  = (int) olish('bilet');
$natija_id = (int) olish('natija');

// ================================================================
// REJIM 3: Tugatilgan natijani ko'rish
// ================================================================
if ($natija_id) {
    $natija = db_qator(
        'SELECT n.*, b.raqam, b.nomi AS bilet_nomi FROM natijalar n
         JOIN biletlar b ON n.bilet_id = b.id
         WHERE n.id = ? AND n.foydalanuvchi_id = ?',
        [$natija_id, $f['id']]
    );
    if (!$natija) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/dashboard');
    }

    $savollar = db_barcha(
        'SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$natija['bilet_id']]
    );
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];
    $foiz     = $natija['umumiy_son'] > 0
        ? round($natija['togri_son'] / $natija['umumiy_son'] * 100)
        : 0;
    $rang     = natija_rang($foiz);

    $sahifa_sarlavha = t('natija') . ' — №' . (int)$natija['raqam'];
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>
    <main class="max-w-4xl mx-auto px-4 py-8">
        <a href="<?= e(SAYT_URL) ?>/dashboard"
           class="inline-flex items-center gap-2 text-brand-muted hover:text-white text-sm mb-6 transition">
            ← <?= e(t('orqaga')) ?>
        </a>

        <!-- Natija xulosa kartasi -->
        <div class="glass-card p-8 text-center mb-6 fade-up">
            <div class="w-28 h-28 mx-auto rounded-full bg-<?= $rang ?>-500/15 text-<?= $rang ?>-400 flex items-center justify-center text-4xl font-display font-bold mb-4 ring-4 ring-<?= $rang ?>-500/20 tabnum">
                <?= $foiz ?>%
            </div>
            <h1 class="text-2xl font-display mb-1">№<?= (int)$natija['raqam'] ?> — <?= e($natija['bilet_nomi']) ?></h1>
            <p class="text-brand-muted mb-2">
                ✅ <?= e(t('siz_togri')) ?>: <strong class="text-green-400"><?= (int)$natija['togri_son'] ?></strong> &nbsp;·&nbsp;
                ❌ <?= e(t('siz_xato')) ?>: <strong class="text-red-400"><?= (int)$natija['xato_son'] ?></strong> &nbsp;·&nbsp;
                Jami: <strong><?= (int)$natija['umumiy_son'] ?></strong>
            </p>
            <p class="text-xs text-brand-muted mb-6">
                <?= match(true) {
                    $foiz >= 90 => '🎉 Ajoyib natija! Imtihonga tayyorsiz.',
                    $foiz >= 70 => '👍 Yaxshi natija! Bir oz mashq qiling.',
                    $foiz >= 50 => '📚 O\'rtacha. Ko\'proq mashq kerak.',
                    default     => '💪 Tushkunlikka tushmang, qayta urinib ko\'ring!'
                } ?>
            </p>

            <!-- Progress bar -->
            <div class="max-w-sm mx-auto h-3 bg-white/10 rounded-full overflow-hidden mb-6">
                <div class="h-full bg-<?= $rang ?>-400 rounded-full transition-all duration-1000"
                     style="width:<?= $foiz ?>%"></div>
            </div>

            <div class="flex justify-center gap-3 flex-wrap">
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$natija['bilet_id'] ?>&qaytadan=1"
                   class="btn-primary"><?= e(t('qaytadan_yechish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost"><?= e(t('biletlar_royxati')) ?></a>
            </div>
        </div>

        <!-- Har bir savol tahlili -->
        <?php foreach ($savollar as $i => $s):
            $j      = $javoblar[$s['id']] ?? null;
            $togri  = $j === $s['togri_javob'];
            $border = $togri ? 'border-green-500/30' : ($j ? 'border-red-500/30' : 'border-white/[0.07]');
        ?>
            <div class="glass-card p-5 mb-3 <?= $border ?> fade-up">
                <div class="flex gap-3 mb-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold
                        <?= $togri ? 'bg-green-500/20 text-green-400' : ($j ? 'bg-red-500/20 text-red-400' : 'bg-white/10 text-white/40') ?>">
                        <?= $i + 1 ?>
                    </span>
                    <p class="flex-1 font-medium leading-relaxed"><?= e($s['matn']) ?></p>
                </div>

                <?php if ($s['rasm'] && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>"
                         class="rounded-xl mb-3 max-w-md w-full" loading="lazy" alt="Savol rasmi">
                <?php endif; ?>

                <div class="grid sm:grid-cols-2 gap-2 ml-11">
                    <?php foreach (['a','b','c','d'] as $v):
                        $matn_v   = $s['variant_' . $v] ?? null;
                        if (!$matn_v) continue;
                        $bu_togri = $v === $s['togri_javob'];
                        $bu_javob = $v === $j;
                        $cls = $bu_togri
                            ? 'bg-green-500/10 border-green-500/40 text-green-300'
                            : ($bu_javob ? 'bg-red-500/10 border-red-500/40 text-red-300' : 'bg-white/[0.03] border-white/10 text-brand-muted');
                    ?>
                        <div class="p-2.5 rounded-xl text-sm border <?= $cls ?>">
                            <span class="font-bold uppercase mr-1.5"><?= $v ?>)</span><?= e($matn_v) ?>
                            <?php if ($bu_togri): ?><span class="ml-1 text-green-400">✓</span><?php endif; ?>
                            <?php if ($bu_javob && !$bu_togri): ?><span class="ml-1 text-red-400">✗</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($s['izoh'])): ?>
                    <div class="mt-3 ml-11 p-3 rounded-xl bg-blue-500/10 border border-blue-500/25 text-sm text-blue-300">
                        💡 <strong>Izoh:</strong> <?= e($s['izoh']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ================================================================
// REJIM 2: Test ishlash
// ================================================================
if ($bilet_id) {
    $bilet = db_qator('SELECT * FROM biletlar WHERE id = ? AND holat = "faol"', [$bilet_id]);
    if (!$bilet) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/test');
    }

    // Pullik bilet uchun obuna tekshiruvi
    if ($bilet['tur'] === 'pullik' && $f['rol'] === 'user' && !obuna_faolmi($f['id'])) {
        flash_qoy('xato', 'Ushbu bilet pullik. Avval tarif sotib oling.');
        yonaltir(SAYT_URL . '/tolov');
    }

    // Qaytadan boshlash
    if (olish('qaytadan') === '1') {
        db_bajar(
            'UPDATE natijalar SET holat = "bekor"
             WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom"',
            [$f['id'], $bilet_id]
        );
    }

    // Davom etayotgan natija bormi?
    $natija = db_qator(
        'SELECT * FROM natijalar
         WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom"
         ORDER BY id DESC LIMIT 1',
        [$f['id'], $bilet_id]
    );

    // Yangi test boshlash
    if (!$natija) {
        $test_vaqt   = (int) sozlama('test_vaqti_minut', 25) * 60;
        $savol_soni  = (int) db_qiymat('SELECT COUNT(*) FROM savollar WHERE bilet_id = ?', [$bilet_id]);
        $yangi_id = db_bajar(
            'INSERT INTO natijalar (foydalanuvchi_id, bilet_id, javoblar_json, umumiy_son, qolgan_vaqt)
             VALUES (?, ?, "{}", ?, ?)',
            [$f['id'], $bilet_id, $savol_soni, $test_vaqt]
        );
        $natija = db_qator('SELECT * FROM natijalar WHERE id = ?', [$yangi_id]);
    }

    $savollar = db_barcha(
        'SELECT id, matn, rasm, variant_a, variant_b, variant_c, variant_d
         FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$bilet_id]
    );
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];

    // BUG FIX: Qolgan vaqtni BOSHLANGAN dan hisoblaymiz
    $qolgan = max(0, (int)$natija['qolgan_vaqt'] - (time() - strtotime($natija['boshlangan'])));

    $sahifa_sarlavha = $bilet['nomi'];
    $body_class      = 'test-page no-select';
    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div x-data="testMashina(<?= htmlspecialchars(json_encode([
        'natija_id' => (int)$natija['id'],
        'qolgan'    => $qolgan,
        'savollar'  => $savollar,
        'javoblar'  => $javoblar,
        'apiUrl'    => SAYT_URL . '/api/save_answer.php',
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" x-cloak>

        <!-- Test yuqori paneli -->
        <div class="sticky top-0 z-30 backdrop-blur-xl bg-brand-bg/90 border-b border-white/[0.07]">
            <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="<?= e(SAYT_URL) ?>/dashboard" class="text-brand-muted hover:text-white transition">←</a>
                    <span class="font-display font-semibold truncate">
                        №<?= (int)$bilet['raqam'] ?> — <?= e($bilet['nomi']) ?>
                    </span>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="text-xs text-brand-muted hidden sm:inline"><?= e(t('qolgan_vaqt')) ?>:</span>
                    <span class="font-mono font-bold tabnum px-3 py-1 rounded-lg transition-colors"
                          :class="qolgan < 60 ? 'bg-red-500/20 text-red-400 animate-pulse' : 'bg-white/[0.06] text-white'"
                          x-text="vaqtFormat(qolgan)"></span>
                    <span class="text-xs text-brand-muted hidden sm:inline" x-text="Object.keys(javoblar).length + '/' + savollar.length"></span>
                </div>
            </div>
            <!-- Progress bar -->
            <div class="h-0.5 bg-white/[0.05]">
                <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-500 transition-all duration-300"
                     :style="`width:${(joriy + 1) / savollar.length * 100}%`"></div>
            </div>
        </div>

        <main class="max-w-3xl mx-auto px-4 py-8">
            <!-- Savollar paginatori -->
            <div class="flex flex-wrap gap-1.5 mb-6">
                <template x-for="(s, i) in savollar" :key="s.id">
                    <button @click="joriy = i"
                            :class="{
                                'ring-2 ring-blue-500 bg-blue-500/30 text-white': i === joriy,
                                'bg-blue-500/15 text-blue-300': javoblar[s.id] && i !== joriy,
                                'bg-white/[0.05] text-brand-muted': !javoblar[s.id] && i !== joriy
                            }"
                            class="w-9 h-9 rounded-lg text-sm font-bold transition-all hover:bg-white/10 active:scale-95"
                            x-text="i + 1"></button>
                </template>
            </div>

            <!-- Joriy savol -->
            <template x-for="(s, i) in savollar" :key="s.id">
                <div x-show="i === joriy" class="glass-card p-6 fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-brand-muted text-sm">
                            <?= e(t('savol')) ?>
                            <span x-text="i + 1" class="tabnum"></span>/<span x-text="savollar.length" class="tabnum"></span>
                        </span>
                        <span x-show="javoblar[s.id]"
                              class="badge badge-blue text-xs"
                              x-text="(javoblar[s.id] || '').toUpperCase()"></span>
                    </div>

                    <p class="text-lg leading-relaxed mb-4" x-text="s.matn"></p>

                    <template x-if="s.rasm">
                        <img :src="`<?= e(SAYT_URL) ?>/uploads/${s.rasm}`"
                             class="rounded-xl mb-4 max-w-md w-full mx-auto" loading="lazy" alt="">
                    </template>

                    <div class="space-y-2">
                        <template x-for="v in ['a','b','c','d']" :key="v">
                            <button x-show="s['variant_' + v]"
                                    @click="javobBer(s.id, v)"
                                    :class="javoblar[s.id] === v
                                        ? 'bg-blue-500/20 border-blue-500 text-white shadow-glow-sm'
                                        : 'bg-white/[0.03] border-white/10 hover:border-white/30 hover:bg-white/[0.06]'"
                                    class="w-full text-left p-3.5 rounded-xl border transition-all flex items-start gap-3 active:scale-[.99]">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 uppercase transition-colors"
                                      :class="javoblar[s.id] === v ? 'bg-blue-500 text-white' : 'bg-white/10 text-brand-muted'"
                                      x-text="v"></span>
                                <span x-text="s['variant_' + v]" class="flex-1 leading-relaxed"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Navigatsiya -->
                    <div class="flex justify-between mt-6 gap-3">
                        <button @click="joriy = Math.max(0, joriy - 1)"
                                :disabled="joriy === 0"
                                class="btn-ghost flex-1 sm:flex-none disabled:opacity-30 disabled:cursor-not-allowed">
                            ← <?= e(t('oldingi')) ?>
                        </button>
                        <template x-if="joriy < savollar.length - 1">
                            <button @click="joriy++" class="btn-primary flex-1 sm:flex-none">
                                <?= e(t('keyingi')) ?> →
                            </button>
                        </template>
                        <template x-if="joriy === savollar.length - 1">
                            <button @click="modalOch()" class="btn-success flex-1 sm:flex-none">
                                <?= e(t('tugatish')) ?> ✓
                            </button>
                        </template>
                    </div>

                    <!-- Saqlash holati -->
                    <div class="mt-3 text-xs text-center min-h-[1rem]"
                         :class="{'text-green-400': saqlash==='saqlandi','text-red-400': saqlash==='xato','text-brand-muted': saqlash==='saqlanmoqda'}">
                        <span x-show="saqlash === 'saqlanmoqda'">⏳ Saqlanmoqda...</span>
                        <span x-show="saqlash === 'saqlandi'" x-cloak>✓ Saqlandi</span>
                        <span x-show="saqlash === 'xato'"     x-cloak>⚠ Saqlash xatosi</span>
                    </div>
                </div>
            </template>
        </main>

        <!-- Tasdiqlash modali -->
        <div x-show="modal" x-transition
             @click.self="modal = false"
             class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card p-8 max-w-sm w-full" @click.stop>
                <h3 class="text-xl font-display mb-3"><?= e(t('tasdiqlaysizmi')) ?></h3>
                <p class="text-brand-muted text-sm mb-2">
                    Javob berilgan: <strong class="text-white" x-text="Object.keys(javoblar).length"></strong> / <span x-text="savollar.length"></span>
                </p>
                <p x-show="savollar.length - Object.keys(javoblar).length > 0"
                   class="text-yellow-400 text-sm mb-5">
                    ⚠ <span x-text="savollar.length - Object.keys(javoblar).length"></span> ta savol javobsiz qoldi!
                </p>
                <div class="flex gap-3 mt-5">
                    <button @click="modal = false" class="btn-ghost flex-1"><?= e(t('bekor_qilish')) ?></button>
                    <button @click="testTugat(false)" class="btn-success flex-1" :disabled="loading">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <?= e(t('tugatish')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function testMashina(boshlangich) {
        return {
            natija_id: boshlangich.natija_id,
            qolgan:    boshlangich.qolgan,
            savollar:  boshlangich.savollar,
            javoblar:  { ...boshlangich.javoblar },
            apiUrl:    boshlangich.apiUrl,
            joriy:     0,
            modal:     false,
            saqlash:   '',
            loading:   false,
            _timer:    null,
            _ls_key:   'atp_test_' + boshlangich.natija_id,

            init() {
                // localStorage dan tiklash
                try {
                    const saved = localStorage.getItem(this._ls_key);
                    if (saved) {
                        const j = JSON.parse(saved);
                        // Server javoblari ustunligi saqlanadi
                        this.javoblar = { ...j, ...this.javoblar };
                    }
                } catch(e) {}

                // Vaqt taymer
                this._timer = setInterval(() => {
                    if (this.qolgan > 0) {
                        this.qolgan--;
                    } else {
                        clearInterval(this._timer);
                        this.testTugat(true);
                    }
                }, 1000);
            },

            async javobBer(savol_id, variant) {
                this.javoblar[savol_id] = variant;
                // Lokal saqlash (offline himoya)
                try { localStorage.setItem(this._ls_key, JSON.stringify(this.javoblar)); } catch(e) {}
                this.saqlash = 'saqlanmoqda';

                const r = await window.apiPost(this.apiUrl, {
                    natija_id: this.natija_id,
                    savol_id,
                    variant
                });
                this.saqlash = (r && r.ok) ? 'saqlandi' : 'xato';
                setTimeout(() => { if (this.saqlash !== 'saqlanmoqda') this.saqlash = ''; }, 1800);
            },

            modalOch() { this.modal = true; },

            async testTugat(vaqtTugadi = false) {
                clearInterval(this._timer);
                this.loading = true;
                this.modal   = false;

                const r = await window.apiPost(this.apiUrl, {
                    natija_id:  this.natija_id,
                    tugatish:   '1',
                    vaqt_tugadi: vaqtTugadi ? '1' : '0'
                });

                this.loading = false;
                if (r && r.ok) {
                    try { localStorage.removeItem(this._ls_key); } catch(e) {}
                    window.location.href = '<?= e(SAYT_URL) ?>/test?natija=' + this.natija_id;
                } else {
                    alert(r?.xato || 'Xato yuz berdi. Sahifani yangilang.');
                    this.modal = true;
                }
            },

            vaqtFormat(s) {
                if (s <= 0) return '00:00';
                const m  = Math.floor(s / 60);
                const ss = s % 60;
                return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
            }
        };
    }
    </script>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ================================================================
// REJIM 1: Biletlar ro'yxati
// ================================================================
$biletlar = db_barcha(
    'SELECT b.*,
        (SELECT COUNT(*) FROM savollar s WHERE s.bilet_id = b.id) AS savol_son,
        (SELECT ROUND(MAX(togri_son/umumiy_son*100))
         FROM natijalar WHERE foydalanuvchi_id = ? AND bilet_id = b.id AND holat = "tugagan") AS eng_yaxshi
     FROM biletlar b WHERE b.holat = "faol"
     ORDER BY b.raqam',
    [$f['id']]
);
$obuna_faol = obuna_faolmi($f['id']);

$sahifa_sarlavha = t('biletlar_royxati');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6 fade-up">
        <h1 class="text-3xl font-display mb-1"><?= e(t('biletlar_royxati')) ?></h1>
        <p class="text-brand-muted">Bilet tanlang va testni boshlang.</p>
    </div>

    <?php if (!$obuna_faol && $f['rol'] === 'user'): ?>
        <div class="glass-card p-5 mb-6 border-yellow-500/30 bg-yellow-500/[0.04] flex items-center justify-between flex-wrap gap-3 fade-up">
            <div>
                <p class="font-semibold text-yellow-400">⚡ Faol obuna yo'q</p>
                <p class="text-sm text-brand-muted">Faqat bepul biletlarni yechishingiz mumkin.</p>
            </div>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="btn-primary"><?= e(t('tarif_olish')) ?> →</a>
        </div>
    <?php endif; ?>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($biletlar as $b):
            $bepul          = $b['tur'] === 'bepul';
            $kirish_mumkin  = $bepul || $obuna_faol || in_array($f['rol'], ['admin','developer'], true);
            $eng            = $b['eng_yaxshi'] !== null ? (int)$b['eng_yaxshi'] : null;
            $eng_rang       = $eng !== null ? natija_rang($eng) : null;
        ?>
            <div class="glass-card <?= $kirish_mumkin ? 'glass-card-hover' : 'opacity-75' ?> p-5 fade-up relative">
                <!-- Tur badge -->
                <span class="absolute top-3 right-3 badge <?= $bepul ? 'badge-green' : 'badge-yellow' ?> text-xs">
                    <?= $bepul ? 'Bepul' : 'PRO' ?>
                </span>

                <div class="text-3xl font-display font-bold text-blue-400 mb-1 tabnum">
                    №<?= (int)$b['raqam'] ?>
                </div>
                <p class="font-medium mb-1 pr-12 leading-tight"><?= e($b['nomi']) ?></p>
                <p class="text-xs text-brand-muted mb-3"><?= (int)$b['savol_son'] ?> ta savol</p>

                <?php if ($eng !== null): ?>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="flex-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-<?= $eng_rang ?>-400 rounded-full" style="width:<?= $eng ?>%"></div>
                        </div>
                        <span class="text-xs font-semibold text-<?= $eng_rang ?>-400 tabnum"><?= $eng ?>%</span>
                    </div>
                <?php endif; ?>

                <?php if ($kirish_mumkin): ?>
                    <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$b['id'] ?>"
                       class="btn-primary w-full text-sm py-2">
                        <?= e(t('test_boshlash')) ?>
                    </a>
                <?php else: ?>
                    <a href="<?= e(SAYT_URL) ?>/tolov"
                       class="btn-ghost w-full text-sm py-2 opacity-70">
                        🔒 Yopiq — tarif olish
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($biletlar)): ?>
            <div class="col-span-full glass-card p-12 text-center text-brand-muted">
                <p><?= e(t('malumot_yoq')) ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
