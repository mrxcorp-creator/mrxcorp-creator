<?php
/**
 * AvtoTest Pro — Test ishlash sahifasi
 *
 * 3 rejim:
 *  1) /test               — biletlar ro'yxati
 *  2) /test?bilet=N       — test ishlash (timer + auto-save)
 *  3) /test?natija=ID     — natijani ko'rish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$bilet_id  = (int) olish('bilet');
$natija_id = (int) olish('natija');

// ═══════════════════════════════════════════════════════════
// REJIM 3: Natijani ko'rish
// ═══════════════════════════════════════════════════════════
if ($natija_id) {
    $natija = db_qator(
        'SELECT n.*, b.raqam, b.nomi AS bilet_nomi FROM natijalar n
         JOIN biletlar b ON n.bilet_id = b.id
         WHERE n.id = ? AND n.foydalanuvchi_id = ?',
        [$natija_id, $f['id']]
    );
    if (!$natija) { flash_qoy('xato', t('malumot_yoq')); yonaltir(SAYT_URL.'/dashboard'); }

    $savollar = db_barcha('SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id', [$natija['bilet_id']]);
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];
    $foiz     = $natija['umumiy_son'] > 0 ? round($natija['togri_son'] / $natija['umumiy_son'] * 100) : 0;
    $rang     = natija_rang($foiz);

    $rang_data = [
        'green'  => ['bg-emerald-500/15', 'text-emerald-400', 'ring-emerald-500/30', '🎉 Ajoyib! Siz tayyor.'],
        'blue'   => ['bg-blue-500/15',    'text-blue-400',    'ring-blue-500/30',    '👍 Yaxshi! Mashq qiling.'],
        'yellow' => ['bg-amber-500/15',   'text-amber-400',   'ring-amber-500/30',   '📚 O\'rtacha. Ko\'proq o\'qing.'],
        'red'    => ['bg-red-500/15',      'text-red-400',     'ring-red-500/30',     '💪 Tushkunlikka tushmang!'],
    ][$rang];

    $sahifa_sarlavha = t('natija') . ' — №' . (int)$natija['raqam'];
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <main class="max-w-3xl mx-auto px-4 py-8 pb-16">
        <a href="<?= e(SAYT_URL) ?>/dashboard"
           class="inline-flex items-center gap-2 text-sm text-white/40 hover:text-white transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <?= e(t('orqaga')) ?>
        </a>

        <!-- Natija xulosa -->
        <div class="glass-card p-8 text-center mb-6 fade-up">
            <div class="w-24 h-24 mx-auto rounded-2xl <?= $rang_data[0] ?> <?= $rang_data[1] ?>
                        flex items-center justify-center font-display font-black text-3xl tabnum mb-5
                        ring-4 <?= $rang_data[2] ?>">
                <?= $foiz ?>%
            </div>
            <h1 class="text-2xl font-display font-black mb-1">
                №<?= (int)$natija['raqam'] ?> — <?= e($natija['bilet_nomi']) ?>
            </h1>
            <p class="text-sm <?= $rang_data[1] ?> mb-4"><?= $rang_data[3] ?></p>

            <!-- Progress bar katta -->
            <div class="max-w-xs mx-auto h-2.5 bg-white/[0.07] rounded-full overflow-hidden mb-4">
                <div class="h-full <?= ['green'=>'bg-emerald-400','blue'=>'bg-blue-400','yellow'=>'bg-amber-400','red'=>'bg-red-400'][$rang] ?> rounded-full transition-all duration-1000"
                     style="width:<?= $foiz ?>%"></div>
            </div>

            <div class="flex items-center justify-center gap-6 text-sm text-white/60 mb-6">
                <span>✅ <strong class="text-emerald-400"><?= (int)$natija['togri_son'] ?></strong> to'g'ri</span>
                <span class="text-white/20">·</span>
                <span>❌ <strong class="text-red-400"><?= (int)$natija['xato_son'] ?></strong> xato</span>
                <span class="text-white/20">·</span>
                <span>📊 <strong class="text-white/80"><?= (int)$natija['umumiy_son'] ?></strong> jami</span>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$natija['bilet_id'] ?>&qaytadan=1"
                   class="btn btn-primary"><?= e(t('qaytadan_yechish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test"
                   class="btn btn-ghost"><?= e(t('biletlar_royxati')) ?></a>
            </div>
        </div>

        <!-- Savol tahlili -->
        <div class="space-y-3">
        <?php foreach ($savollar as $i => $s):
            $j      = $javoblar[$s['id']] ?? null;
            $togri_ = $j === $s['togri_javob'];
            $border_cls = $togri_ ? 'border-emerald-500/30 bg-emerald-500/[0.02]' : ($j ? 'border-red-500/30 bg-red-500/[0.02]' : '');
        ?>
        <div class="glass-card p-5 <?= $border_cls ?> fade-up" style="animation-delay:<?= min($i*0.03, 0.5) ?>s">
            <div class="flex gap-3 mb-3">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs flex-shrink-0
                    <?= $togri_ ? 'bg-emerald-500/20 text-emerald-400' : ($j ? 'bg-red-500/20 text-red-400' : 'bg-white/[0.06] text-white/40') ?>">
                    <?= $i + 1 ?>
                </span>
                <p class="flex-1 text-sm text-white/90 leading-relaxed font-medium"><?= e($s['matn']) ?></p>
            </div>

            <?php if ($s['rasm'] && is_file(UPLOAD_PATH.'/'.$s['rasm'])): ?>
            <img src="<?= e(SAYT_URL.'/uploads/'.$s['rasm']) ?>"
                 class="rounded-xl mb-3 max-w-sm w-full" loading="lazy" alt="">
            <?php endif; ?>

            <div class="grid sm:grid-cols-2 gap-2 ml-11">
                <?php foreach (['a','b','c','d'] as $v):
                    $mv = $s['variant_'.$v] ?? null;
                    if (!$mv) continue;
                    $isTog = $v === $s['togri_javob'];
                    $isJav = $v === $j;
                    $cls = $isTog
                        ? 'bg-emerald-500/12 border-emerald-500/40 text-emerald-300'
                        : ($isJav ? 'bg-red-500/12 border-red-500/40 text-red-300' : 'bg-white/[0.03] border-white/[0.08] text-white/55');
                ?>
                <div class="p-2.5 rounded-xl text-xs border <?= $cls ?>">
                    <span class="font-bold uppercase mr-1.5"><?= $v ?>)</span><?= e($mv) ?>
                    <?php if ($isTog): ?><span class="ml-1">✓</span><?php endif; ?>
                    <?php if ($isJav && !$isTog): ?><span class="ml-1">✗</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($s['izoh'])): ?>
            <div class="mt-3 ml-11 p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-300 flex items-start gap-2">
                <span class="flex-shrink-0">💡</span>
                <span><?= e($s['izoh']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit; ?>
<?php } // end natija ?>

// ═══════════════════════════════════════════════════════════
// REJIM 2: Test ishlash
// ═══════════════════════════════════════════════════════════
<?php if ($bilet_id) { ?>
<?php
    $bilet = db_qator('SELECT * FROM biletlar WHERE id = ? AND holat = "faol"', [$bilet_id]);
    if (!$bilet) { flash_qoy('xato', t('malumot_yoq')); yonaltir(SAYT_URL.'/test'); }

    if ($bilet['tur'] === 'pullik' && $f['rol'] === 'user' && !obuna_faolmi($f['id'])) {
        flash_qoy('xato', 'Bu bilet pullik. Avval tarif sotib oling.');
        yonaltir(SAYT_URL.'/tolov');
    }

    if (olish('qaytadan') === '1') {
        db_bajar('UPDATE natijalar SET holat = "bekor" WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom"',
                 [$f['id'], $bilet_id]);
    }

    $natija = db_qator(
        'SELECT * FROM natijalar WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom" ORDER BY id DESC LIMIT 1',
        [$f['id'], $bilet_id]
    );

    if (!$natija) {
        $test_vaqt = (int) sozlama('test_vaqti_minut', 25) * 60;
        $savol_son = (int) db_qiymat('SELECT COUNT(*) FROM savollar WHERE bilet_id = ?', [$bilet_id]);
        $yid = db_bajar(
            'INSERT INTO natijalar (foydalanuvchi_id, bilet_id, javoblar_json, umumiy_son, qolgan_vaqt) VALUES (?, ?, "{}", ?, ?)',
            [$f['id'], $bilet_id, $savol_son, $test_vaqt]
        );
        $natija = db_qator('SELECT * FROM natijalar WHERE id = ?', [$yid]);
    }

    $savollar = db_barcha(
        'SELECT id, matn, rasm, variant_a, variant_b, variant_c, variant_d FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$bilet_id]
    );
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];
    $qolgan   = max(0, (int)$natija['qolgan_vaqt'] - (time() - strtotime($natija['boshlangan'])));

    $sahifa_sarlavha = $bilet['nomi'];
    $body_class      = 'test-page no-select';
    require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="testApp(<?= htmlspecialchars(json_encode([
    'natija_id' => (int)$natija['id'],
    'qolgan'    => $qolgan,
    'savollar'  => $savollar,
    'javoblar'  => $javoblar,
    'apiUrl'    => SAYT_URL . '/api/save_answer.php',
], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" x-cloak>

    <!-- ── Sticky header ─────────────────────────────────── -->
    <header class="sticky top-0 z-40 bg-[#070C1A]/90 backdrop-blur-2xl border-b border-white/[0.06]">
        <div class="max-w-4xl mx-auto px-4 h-14 flex items-center gap-4">
            <!-- Back + title -->
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <a href="<?= e(SAYT_URL) ?>/dashboard"
                   class="text-white/40 hover:text-white transition flex-shrink-0 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="font-display font-bold text-sm truncate">
                    №<?= (int)$bilet['raqam'] ?> — <?= e($bilet['nomi']) ?>
                </span>
            </div>

            <!-- Javoblar soni -->
            <span class="text-xs text-white/35 hidden sm:block tabnum"
                  x-text="Object.keys(javoblar).length + '/' + savollar.length + ' javob'"></span>

            <!-- Taymer -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-mono font-bold tabnum text-sm px-3 py-1 rounded-lg transition-all duration-300"
                      :class="qolgan < 60 ? 'bg-red-500/20 text-red-400 animate-pulse' : 'bg-white/[0.07] text-white'"
                      x-text="vaqtFormat(qolgan)"></span>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="h-0.5 bg-white/[0.06]">
            <div class="h-full bg-gradient-to-r from-blue-500 to-violet-500 transition-all duration-300"
                 :style="'width:' + (Object.keys(javoblar).length / savollar.length * 100) + '%'"></div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6 pb-16">

        <!-- Savol paginatori -->
        <div class="flex flex-wrap gap-1.5 mb-6">
            <template x-for="(s, i) in savollar" :key="s.id">
                <button @click="joriy = i"
                        :class="{
                            'ring-2 ring-blue-500 bg-blue-500/25 text-white shadow-glow-sm': i === joriy,
                            'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20': javoblar[s.id] && i !== joriy,
                            'bg-white/[0.05] text-white/45 hover:bg-white/[0.09] hover:text-white': !javoblar[s.id] && i !== joriy
                        }"
                        class="w-9 h-9 rounded-lg text-xs font-bold transition-all duration-150 active:scale-95"
                        x-text="i + 1"></button>
            </template>
        </div>

        <!-- Joriy savol -->
        <template x-for="(s, i) in savollar" :key="s.id">
            <div x-show="i === joriy" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="glass-card p-6">

                <!-- Savol raqami + holati -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-blue-500/15 text-blue-400 flex items-center justify-center font-bold text-xs tabnum"
                              x-text="i + 1"></span>
                        <span class="text-xs text-white/40">
                            / <span x-text="savollar.length"></span> savol
                        </span>
                    </div>
                    <span x-show="javoblar[s.id]"
                          class="badge badge-blue text-xs uppercase font-mono"
                          x-text="javoblar[s.id]"></span>
                </div>

                <!-- Savol matni -->
                <p class="text-base text-white/90 leading-relaxed mb-5 font-medium" x-text="s.matn"></p>

                <!-- Rasm -->
                <template x-if="s.rasm">
                    <img :src="'<?= e(SAYT_URL) ?>/uploads/' + s.rasm"
                         class="rounded-2xl mb-5 max-w-sm w-full mx-auto block" loading="lazy" alt="">
                </template>

                <!-- Variantlar -->
                <div class="space-y-2.5">
                    <template x-for="v in ['a','b','c','d']" :key="v">
                        <button x-show="s['variant_' + v]"
                                @click="javobBer(s.id, v)"
                                :class="javoblar[s.id] === v
                                    ? 'bg-blue-500/18 border-blue-500 shadow-lg shadow-blue-500/15 text-white'
                                    : 'bg-white/[0.03] border-white/[0.09] hover:bg-white/[0.07] hover:border-white/20 text-white/75'"
                                class="w-full text-left p-4 rounded-xl border-[1.5px] transition-all duration-150 flex items-center gap-3 group active:scale-[0.99]">
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold uppercase flex-shrink-0 transition-all"
                                  :class="javoblar[s.id] === v
                                      ? 'bg-blue-500 text-white'
                                      : 'bg-white/[0.07] text-white/50 group-hover:text-white/70'"
                                  x-text="v"></span>
                            <span x-text="s['variant_' + v]" class="flex-1 text-sm leading-relaxed"></span>
                        </button>
                    </template>
                </div>

                <!-- Navigatsiya tugmalari -->
                <div class="flex gap-3 mt-6">
                    <button @click="joriy = Math.max(0, joriy - 1)"
                            :disabled="joriy === 0"
                            class="btn btn-ghost flex-1 sm:flex-none disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <?= e(t('oldingi')) ?>
                    </button>

                    <template x-if="joriy < savollar.length - 1">
                        <button @click="joriy++" class="btn btn-primary flex-1 sm:flex-none">
                            <?= e(t('keyingi')) ?>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </template>
                    <template x-if="joriy === savollar.length - 1">
                        <button @click="modal = true" class="btn btn-success flex-1 sm:flex-none">
                            <?= e(t('tugatish')) ?> ✓
                        </button>
                    </template>
                </div>

                <!-- Saqlash holati -->
                <div class="mt-3 h-4 text-xs text-center transition-all"
                     :class="saqlash==='saqlandi'?'text-emerald-400':saqlash==='xato'?'text-red-400':'text-white/30'">
                    <span x-show="saqlash==='saqlanmoqda'">⏳ Saqlanmoqda...</span>
                    <span x-show="saqlash==='saqlandi'" x-cloak>✓ Saqlandi</span>
                    <span x-show="saqlash==='xato'" x-cloak>⚠ Saqlash xatosi</span>
                </div>
            </div>
        </template>
    </main>

    <!-- ── Tugatish modali ────────────────────────────────── -->
    <div x-show="modal" x-transition
         @click.self="modal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="glass-card p-7 max-w-sm w-full scale-in" @click.stop>
            <div class="text-4xl text-center mb-4">🏁</div>
            <h3 class="text-xl font-display font-black text-center mb-2"><?= e(t('tasdiqlaysizmi')) ?></h3>

            <div class="space-y-2 mb-5 text-sm text-center text-white/60">
                <p>Javob berilgan: <strong class="text-white" x-text="Object.keys(javoblar).length"></strong> / <span x-text="savollar.length"></span></p>
                <p x-show="savollar.length - Object.keys(javoblar).length > 0"
                   class="text-amber-400 font-medium"
                   x-text="'⚠ ' + (savollar.length - Object.keys(javoblar).length) + ' ta savol javobsiz!'"></p>
            </div>

            <div class="flex gap-3">
                <button @click="modal = false" class="btn btn-ghost flex-1"><?= e(t('bekor_qilish')) ?></button>
                <button @click="testTugat(false)" class="btn btn-success flex-1" :disabled="loading">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <?= e(t('tugatish')) ?>
                </button>
            </div>
        </div>
    </div>

</div><!-- /x-data testApp -->

<script>
function testApp(d) {
    return {
        natija_id : d.natija_id,
        qolgan    : d.qolgan,
        savollar  : d.savollar,
        javoblar  : Object.assign({}, d.javoblar),
        apiUrl    : d.apiUrl,
        joriy     : 0,
        modal     : false,
        loading   : false,
        saqlash   : '',
        _timer    : null,
        _lsKey    : 'atp_' + d.natija_id,

        init() {
            // Lokal nusxa
            try {
                var saved = localStorage.getItem(this._lsKey);
                if (saved) {
                    var j = JSON.parse(saved);
                    this.javoblar = Object.assign({}, j, this.javoblar);
                }
            } catch(e){}

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
            try { localStorage.setItem(this._lsKey, JSON.stringify(this.javoblar)); } catch(e){}
            this.saqlash = 'saqlanmoqda';

            var r = await window.apiPost(this.apiUrl, { natija_id: this.natija_id, savol_id, variant });
            this.saqlash = (r && r.ok) ? 'saqlandi' : 'xato';
            setTimeout(() => { if (this.saqlash !== 'saqlanmoqda') this.saqlash = ''; }, 2000);
        },

        async testTugat(vaqtTugadi) {
            vaqtTugadi = vaqtTugadi || false;
            clearInterval(this._timer);
            this.loading = true;
            this.modal   = false;

            var r = await window.apiPost(this.apiUrl, {
                natija_id: this.natija_id, tugatish: '1', vaqt_tugadi: vaqtTugadi ? '1' : '0'
            });
            this.loading = false;

            if (r && r.ok) {
                try { localStorage.removeItem(this._lsKey); } catch(e){}
                window.location.href = '<?= e(SAYT_URL) ?>/test?natija=' + this.natija_id;
            } else {
                alert(r.xato || 'Xato yuz berdi');
                this.modal = true;
            }
        },

        vaqtFormat(s) {
            if (s <= 0) return '00:00';
            var m = Math.floor(s / 60), ss = s % 60;
            return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
        }
    };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; exit; } ?>

<?php
// ═══════════════════════════════════════════════════════════
// REJIM 1: Biletlar ro'yxati
// ═══════════════════════════════════════════════════════════
$biletlar = db_barcha(
    'SELECT b.*,
        (SELECT COUNT(*) FROM savollar WHERE bilet_id = b.id) AS savol_son,
        (SELECT ROUND(MAX(togri_son/NULLIF(umumiy_son,0)*100))
         FROM natijalar WHERE foydalanuvchi_id = ? AND bilet_id = b.id AND holat = "tugagan") AS eng_yaxshi
     FROM biletlar b WHERE b.holat = "faol" ORDER BY b.raqam',
    [$f['id']]
);
$obuna_faol = obuna_faolmi($f['id']);

$sahifa_sarlavha = t('biletlar_royxati');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8 pb-16">
    <div class="flex items-start justify-between flex-wrap gap-4 mb-8 fade-up">
        <div>
            <h1 class="text-3xl font-display font-black mb-1"><?= e(t('biletlar_royxati')) ?></h1>
            <p class="text-white/45">Bilet tanlang va testni boshlang</p>
        </div>
    </div>

    <?php if (!$obuna_faol && $f['rol'] === 'user'): ?>
    <div class="glass-card p-5 mb-6 border-amber-500/25 bg-amber-500/[0.04]
                flex items-center justify-between flex-wrap gap-3 fade-up">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚡</span>
            <div>
                <p class="font-semibold text-amber-400">Faol obuna yo'q</p>
                <p class="text-sm text-white/50">Faqat bepul biletlarni yechishingiz mumkin.</p>
            </div>
        </div>
        <a href="<?= e(SAYT_URL) ?>/tolov" class="btn btn-primary btn-sm flex-shrink-0">
            💎 <?= e(t('tarif_olish')) ?> →
        </a>
    </div>
    <?php endif; ?>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($biletlar as $b):
            $bepul  = $b['tur'] === 'bepul';
            $kirish = $bepul || $obuna_faol || in_array($f['rol'],['admin','developer'],true);
            $eng    = $b['eng_yaxshi'] !== null ? (int)$b['eng_yaxshi'] : null;
            $rang   = $eng !== null ? natija_rang($eng) : null;
            $bar_c  = ['green'=>'bg-emerald-400','blue'=>'bg-blue-400','yellow'=>'bg-amber-400','red'=>'bg-red-400'][$rang ?? 'blue'] ?? 'bg-blue-400';
        ?>
        <div class="glass-card <?= $kirish ? 'glass-card-hover' : 'opacity-60' ?> p-5 fade-up relative flex flex-col">
            <!-- Tur badge -->
            <div class="absolute top-3.5 right-3.5">
                <span class="badge <?= $bepul ? 'badge-green' : 'badge-yellow' ?> text-xs">
                    <?= $bepul ? 'Bepul' : 'PRO' ?>
                </span>
            </div>

            <!-- Raqam -->
            <div class="text-3xl font-display font-black text-blue-400 tabnum mb-1 pr-14">
                №<?= (int)$b['raqam'] ?>
            </div>

            <p class="font-semibold text-sm text-white/85 mb-1 pr-2 leading-tight"><?= e($b['nomi']) ?></p>
            <p class="text-xs text-white/40 mb-4"><?= (int)$b['savol_son'] ?> savol</p>

            <!-- Eng yaxshi natija -->
            <?php if ($eng !== null): ?>
            <div class="mb-4">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-white/40">Eng yaxshi:</span>
                    <span class="font-bold text-<?= $rang ?>-400 tabnum"><?= $eng ?>%</span>
                </div>
                <div class="h-1.5 bg-white/[0.06] rounded-full overflow-hidden">
                    <div class="h-full <?= $bar_c ?> rounded-full" style="width:<?= $eng ?>%"></div>
                </div>
            </div>
            <?php else: ?>
            <div class="flex-1"></div>
            <?php endif; ?>

            <!-- Tugma -->
            <?php if ($kirish): ?>
            <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$b['id'] ?>"
               class="btn btn-primary w-full btn-sm mt-auto"><?= e(t('test_boshlash')) ?></a>
            <?php else: ?>
            <a href="<?= e(SAYT_URL) ?>/tolov"
               class="btn btn-ghost w-full btn-sm mt-auto opacity-60">🔒 Tarif olish</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if (empty($biletlar)): ?>
        <div class="col-span-full glass-card p-16 text-center text-white/35">
            <div class="text-5xl mb-4">📋</div>
            <p><?= e(t('malumot_yoq')) ?></p>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
