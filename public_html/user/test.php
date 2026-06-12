<?php
/**
 * VatanParvar Yaypan — Test ishlash sahifasi
 *
 * Uch rejimda ishlaydi:
 *  - Biletlar ro'yxati: /test
 *  - Test ishlash:      /test?bilet=N
 *  - Natijani ko'rish:  /test?natija=ID
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$bilet_id  = (int) olish('bilet');
$natija_id = (int) olish('natija');

// ============================================================
// REJIM 1: Tugatilgan natijani ko'rish
// ============================================================
if ($natija_id) {
    $natija = db_qator(
        'SELECT n.*, b.raqam, b.nomi, b.nomi_cyrl FROM natijalar n
         JOIN biletlar b ON n.bilet_id = b.id
         WHERE n.id = ? AND n.foydalanuvchi_id = ?',
        [$natija_id, $f['id']]
    );
    if (!$natija) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/dashboard');
    }
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];
    $savollar = db_barcha('SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id', [$natija['bilet_id']]);
    $foiz = $natija['umumiy_son'] > 0 ? round($natija['togri_son'] / $natija['umumiy_son'] * 100) : 0;

    $sahifa_sarlavha = t('natija') . ' № ' . (int)$natija['raqam'];
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>
    <main class="max-w-4xl mx-auto px-4 py-8 fade-up">
        <a href="<?= e(SAYT_URL) ?>/dashboard" class="text-brand-muted hover:text-white text-sm mb-4 inline-block">← <?= e(t('orqaga')) ?></a>

        <!-- Natija sarlavha -->
        <div class="glass-card p-8 text-center mb-6">
            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center text-3xl font-display font-bold mb-4
                <?= $foiz >= 90 ? 'bg-green-500/20 text-green-400' :
                   ($foiz >= 70 ? 'bg-blue-500/20 text-blue-400' :
                   ($foiz >= 50 ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400')) ?>">
                <?= $foiz ?>%
            </div>
            <h1 class="text-2xl mb-2">№<?= (int)$natija['raqam'] ?> — <?= e(tk($natija, 'nomi')) ?></h1>
            <p class="text-brand-muted"><?= e(t('siz_togri')) ?>: <?= (int)$natija['togri_son'] ?> · <?= e(t('siz_xato')) ?>: <?= (int)$natija['xato_son'] ?></p>
            <div class="flex justify-center gap-3 mt-6">
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$natija['bilet_id'] ?>&qaytadan=1" class="btn-primary"><?= e(t('qaytadan_yechish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost"><?= e(t('biletlar_royxati')) ?></a>
            </div>
        </div>

        <!-- Har bir savol -->
        <?php foreach ($savollar as $i => $s):
            $j = $javoblar[$s['id']] ?? null;
            $togri = $j === $s['togri_javob'];
        ?>
            <div class="glass-card p-5 mb-3 <?= $togri ? 'border-green-500/30' : ($j ? 'border-red-500/30' : '') ?>">
                <div class="flex gap-3 mb-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold
                        <?= $togri ? 'bg-green-500/20 text-green-400' : ($j ? 'bg-red-500/20 text-red-400' : 'bg-white/10 text-white/40') ?>">
                        <?= $i + 1 ?>
                    </span>
                    <div class="flex-1">
                        <p class="font-medium"><?= e(tk($s, 'matn')) ?></p>
                        <?php if ($s['rasm'] && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" class="mt-3 rounded-lg max-w-md w-full">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-2 ml-11">
                    <?php foreach (['a', 'b', 'c', 'd'] as $v):
                        $matn = tk($s, 'variant_' . $v);
                        if (!$matn) continue;
                        $bu_togri = $v === $s['togri_javob'];
                        $bu_javob = $v === $j;
                    ?>
                        <div class="p-2.5 rounded-lg text-sm border
                            <?= $bu_togri ? 'bg-green-500/10 border-green-500/40' :
                               ($bu_javob ? 'bg-red-500/10 border-red-500/40' : 'bg-white/3 border-white/10') ?>">
                            <span class="font-bold mr-2 uppercase"><?= $v ?>)</span><?= e($matn) ?>
                            <?php if ($bu_togri): ?> ✓<?php elseif ($bu_javob): ?> ✗<?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($s['izoh'])): ?>
                    <div class="mt-3 ml-11 p-3 rounded-lg bg-blue-500/10 border border-blue-500/30 text-sm">
                        <strong class="text-blue-400">💡 Izoh:</strong> <?= e(tk($s, 'izoh')) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ============================================================
// REJIM 2: Test ishlash
// ============================================================
if ($bilet_id) {
    $bilet = db_qator('SELECT * FROM biletlar WHERE id = ? AND holat = "faol"', [$bilet_id]);
    if (!$bilet) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/test');
    }

    // Pullik bilet uchun obuna tekshiruvi
    if ($bilet['tur'] === 'pullik' && !obuna_faolmi($f['id']) && $f['rol'] === 'user') {
        flash_qoy('xato', 'Ushbu bilet pullik. Avval tarif sotib oling.');
        yonaltir(SAYT_URL . '/tolov');
    }

    // Qaytadan boshlash so'rovi
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
        $savollar_son = db_qiymat('SELECT COUNT(*) FROM savollar WHERE bilet_id = ?', [$bilet_id]);
        $vaqt = (int) sozlama('test_vaqti_minut', 25) * 60;
        $yangi_id = db_bajar(
            'INSERT INTO natijalar
             (foydalanuvchi_id, bilet_id, javoblar_json, umumiy_son, qolgan_vaqt)
             VALUES (?, ?, "{}", ?, ?)',
            [$f['id'], $bilet_id, $savollar_son, $vaqt]
        );
        $natija = db_qator('SELECT * FROM natijalar WHERE id = ?', [$yangi_id]);
    }

    $savollar = db_barcha(
        'SELECT id, matn, matn_cyrl, rasm,
                variant_a, variant_a_cyrl, variant_b, variant_b_cyrl,
                variant_c, variant_c_cyrl, variant_d, variant_d_cyrl
         FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$bilet_id]
    );

    // Tilga qarab savollarni JS uchun qisqartirilgan formada tayyorlash
    $til_cyrl = ($_SESSION['til'] ?? 'uz_latn') === 'uz_cyrl';
    $savollar_js = array_map(function ($s) use ($til_cyrl) {
        $tanla = function ($maydon) use ($s, $til_cyrl) {
            if ($til_cyrl) {
                $cyrl = $s[$maydon . '_cyrl'] ?? '';
                if ($cyrl !== '') return $cyrl;
                return $s[$maydon] ? lotin_dan_kirill($s[$maydon]) : '';
            }
            return $s[$maydon] ?? '';
        };
        return [
            'id'        => (int) $s['id'],
            'matn'      => $tanla('matn'),
            'rasm'      => $s['rasm'],
            'variant_a' => $tanla('variant_a'),
            'variant_b' => $tanla('variant_b'),
            'variant_c' => $tanla('variant_c'),
            'variant_d' => $tanla('variant_d'),
        ];
    }, $savollar);
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];

    $sahifa_sarlavha = tk($bilet, 'nomi');
    $body_class = 'test-page no-select';
    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div x-data="testIshlash(<?= htmlspecialchars(json_encode([
            'natija_id' => (int) $natija['id'],
            'qolgan'    => (int) $natija['qolgan_vaqt'],
            'savollar'  => $savollar_js,
            'javoblar'  => $javoblar,
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" x-cloak>

        <!-- Yuqori panel -->
        <div class="sticky top-0 z-30 backdrop-blur-xl bg-brand-bg/90 border-b border-white/10">
            <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="<?= e(SAYT_URL) ?>/dashboard" class="text-brand-muted hover:text-white">←</a>
                    <span class="font-display font-semibold truncate">№<?= (int)$bilet['raqam'] ?> — <?= e(tk($bilet, 'nomi')) ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-brand-muted hidden sm:inline"><?= e(t('qolgan_vaqt')) ?>:</span>
                    <span class="font-mono font-bold tabular-nums px-3 py-1 rounded-lg"
                          :class="qolgan < 60 ? 'bg-red-500/20 text-red-400 animate-pulse' : 'bg-white/5'"
                          x-text="vaqtFormat(qolgan)"></span>
                </div>
            </div>
            <!-- Progress bar -->
            <div class="h-1 bg-white/5">
                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-300"
                     :style="`width:${(joriy + 1) / savollar.length * 100}%`"></div>
            </div>
        </div>

        <main class="max-w-3xl mx-auto px-4 py-8">
            <!-- Savollar paginatori -->
            <div class="flex flex-wrap gap-2 mb-6">
                <template x-for="(s, i) in savollar" :key="s.id">
                    <button @click="joriy = i"
                            :class="i === joriy ? 'ring-2 ring-blue-500 bg-blue-500/30 text-white' :
                                    (javoblar[s.id] ? 'bg-blue-500/15 text-blue-300' : 'bg-white/5 text-brand-muted')"
                            class="w-10 h-10 rounded-lg text-sm font-bold transition-all hover:bg-white/10 active:scale-95"
                            x-text="i + 1"></button>
                </template>
            </div>

            <!-- Joriy savol -->
            <template x-for="(s, i) in savollar" :key="s.id">
                <div x-show="i === joriy" class="glass-card p-6 fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-brand-muted text-sm"><?= e(t('savol')) ?> <span x-text="i + 1"></span> / <span x-text="savollar.length"></span></span>
                        <span class="text-xs px-2 py-1 rounded bg-white/5 text-brand-muted"
                              x-show="javoblar[s.id]" x-text="javoblar[s.id].toUpperCase()"></span>
                    </div>

                    <p class="text-lg mb-4" x-text="s.matn"></p>

                    <template x-if="s.rasm">
                        <img :src="`<?= e(SAYT_URL) ?>/uploads/${s.rasm}`"
                             class="rounded-xl mb-4 max-w-md w-full mx-auto">
                    </template>

                    <div class="space-y-2">
                        <template x-for="v in ['a','b','c','d']" :key="v">
                            <button x-show="s['variant_' + v]"
                                    @click="javobBer(s.id, v)"
                                    :class="javoblar[s.id] === v ? 'bg-blue-500/20 border-blue-500 text-white' : 'bg-white/3 border-white/10 hover:border-white/30'"
                                    class="w-full text-left p-3 rounded-xl border transition-all flex items-start gap-3 active:scale-[.98]">
                                <span class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-bold flex-shrink-0 uppercase"
                                      :class="javoblar[s.id] === v ? 'bg-blue-500 text-white' : 'bg-white/10'"
                                      x-text="v"></span>
                                <span x-text="s['variant_' + v]" class="flex-1"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Navigatsiya tugmalari -->
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
                            <button @click="testTugat()" class="btn-primary flex-1 sm:flex-none bg-green-600 hover:bg-green-500">
                                <?= e(t('tugatish')) ?> ✓
                            </button>
                        </template>
                    </div>

                    <!-- Saqlash holati -->
                    <div class="mt-4 text-xs text-center"
                         :class="saqlash === 'saqlandi' ? 'text-green-400' : (saqlash === 'xato' ? 'text-red-400' : 'text-brand-muted')">
                        <span x-show="saqlash === 'saqlanmoqda'">⏳ Saqlanmoqda...</span>
                        <span x-show="saqlash === 'saqlandi'">✓ Saqlandi</span>
                        <span x-show="saqlash === 'xato'">⚠ Saqlash xatosi (qaytadan urinmoqdamiz)</span>
                    </div>
                </div>
            </template>
        </main>

        <!-- Tugatish modal -->
        <div x-show="modal" x-transition
             @click.self="modal = false"
             class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card p-8 max-w-sm w-full">
                <h3 class="text-xl font-display mb-2"><?= e(t('tasdiqlaysizmi')) ?></h3>
                <p class="text-brand-muted mb-5">Javob bermagan savollar:
                    <strong class="text-white" x-text="savollar.length - Object.keys(javoblar).length"></strong>
                </p>
                <div class="flex gap-3">
                    <button @click="modal = false" class="btn-ghost flex-1"><?= e(t('bekor_qilish')) ?></button>
                    <button @click="haqiqattanTugat()" class="btn-primary flex-1 bg-green-600 hover:bg-green-500"><?= e(t('tugatish')) ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testIshlash(boshlangich) {
            return {
                natija_id: boshlangich.natija_id,
                qolgan: boshlangich.qolgan,
                savollar: boshlangich.savollar,
                javoblar: boshlangich.javoblar || {},
                joriy: 0,
                modal: false,
                saqlash: '',
                _timer: null,

                init() {
                    // Taymer
                    this._timer = setInterval(() => {
                        this.qolgan--;
                        if (this.qolgan <= 0) {
                            clearInterval(this._timer);
                            this.haqiqattanTugat(true);
                        }
                    }, 1000);

                    // localStorage'dan tiklash
                    const saqlangan = localStorage.getItem('test_' + this.natija_id);
                    if (saqlangan) {
                        try {
                            const j = JSON.parse(saqlangan);
                            // Server javoblari bilan birlashtirish
                            this.javoblar = { ...j, ...this.javoblar };
                        } catch (e) {}
                    }
                },

                async javobBer(savol_id, variant) {
                    this.javoblar[savol_id] = variant;
                    localStorage.setItem('test_' + this.natija_id, JSON.stringify(this.javoblar));
                    this.saqlash = 'saqlanmoqda';

                    const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/save_answer.php', {
                        natija_id: this.natija_id,
                        savol_id: savol_id,
                        variant: variant
                    });

                    if (r && r.ok) {
                        this.saqlash = 'saqlandi';
                        if (typeof r.qolgan_vaqt === 'number') this.qolgan = r.qolgan_vaqt;
                        setTimeout(() => { if (this.saqlash === 'saqlandi') this.saqlash = ''; }, 1500);
                    } else {
                        this.saqlash = 'xato';
                    }
                },

                testTugat() {
                    this.modal = true;
                },

                async haqiqattanTugat(vaqtTugadi = false) {
                    clearInterval(this._timer);
                    const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/save_answer.php', {
                        natija_id: this.natija_id,
                        tugatish: '1',
                        vaqt_tugadi: vaqtTugadi ? '1' : '0'
                    });
                    if (r && r.ok) {
                        localStorage.removeItem('test_' + this.natija_id);
                        window.location = '<?= e(SAYT_URL) ?>/test?natija=' + this.natija_id;
                    } else {
                        alert(r.xato || 'Xato yuz berdi');
                        this.modal = false;
                    }
                },

                vaqtFormat(s) {
                    if (s < 0) s = 0;
                    const m = Math.floor(s / 60);
                    const ss = s % 60;
                    return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
                }
            };
        }
    </script>
    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ============================================================
// REJIM 3: Biletlar ro'yxati
// ============================================================
$biletlar = db_barcha(
    'SELECT b.*,
        (SELECT COUNT(*) FROM savollar s WHERE s.bilet_id = b.id) AS savol_son,
        (SELECT MAX(togri_son/umumiy_son*100)
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
    <div class="mb-6">
        <h1 class="text-3xl mb-1"><?= e(t('biletlar_royxati')) ?></h1>
        <p class="text-brand-muted">Bilet tanlang va testni boshlang.</p>
    </div>

    <?php if (!$obuna_faol && $f['rol'] === 'user'): ?>
        <div class="glass-card p-5 mb-6 border-yellow-500/30 bg-yellow-500/5 flex items-center justify-between flex-wrap gap-3">
            <div>
                <strong class="text-yellow-400">⚡ Faol obuna yo'q.</strong>
                <span class="text-brand-muted">Faqat bepul biletlarni yechishingiz mumkin.</span>
            </div>
            <a href="<?= e(SAYT_URL) ?>/tolov" class="btn-primary"><?= e(t('tarif_olish')) ?></a>
        </div>
    <?php endif; ?>

    <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($biletlar as $b):
            $bepul = $b['tur'] === 'bepul';
            $kirish_mumkin = $bepul || $obuna_faol || in_array($f['rol'], ['admin', 'developer'], true);
            $eng = $b['eng_yaxshi'] !== null ? round($b['eng_yaxshi']) : null;
        ?>
            <div class="glass-card glass-card-hover p-5 fade-up relative">
                <?php if (!$bepul): ?>
                    <span class="absolute top-3 right-3 text-xs px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400">PRO</span>
                <?php else: ?>
                    <span class="absolute top-3 right-3 text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400">Bepul</span>
                <?php endif; ?>
                <div class="text-3xl font-display font-bold text-blue-400 mb-1">№<?= (int)$b['raqam'] ?></div>
                <div class="font-medium mb-1 truncate"><?= e(tk($b, 'nomi')) ?></div>
                <div class="text-xs text-brand-muted mb-3"><?= (int)$b['savol_son'] ?> savol</div>

                <?php if ($eng !== null): ?>
                    <div class="text-xs mb-3 flex items-center gap-2">
                        <span class="text-brand-muted">Eng yaxshi:</span>
                        <span class="font-bold <?= $eng >= 90 ? 'text-green-400' : ($eng >= 70 ? 'text-blue-400' : 'text-yellow-400') ?>"><?= $eng ?>%</span>
                    </div>
                <?php endif; ?>

                <?php if ($kirish_mumkin): ?>
                    <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$b['id'] ?>" class="btn-primary w-full text-sm py-2"><?= e(t('test_boshlash')) ?></a>
                <?php else: ?>
                    <button class="btn-ghost w-full text-sm py-2 opacity-50 cursor-not-allowed" disabled>🔒 Yopiq</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($biletlar)): ?>
        <div class="glass-card p-12 text-center text-brand-muted">
            <p><?= e(t('malumot_yoq')) ?></p>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
