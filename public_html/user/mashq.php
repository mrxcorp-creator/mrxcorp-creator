<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$rejim = olish('rejim') ?: 'tanlash';
$bilet_id = (int) olish('bilet');

if ($rejim === 'tanlash') {

    $xato_savollar = db_qiymat(
        "SELECT COUNT(DISTINCT s.id)
         FROM natijalar n
         JOIN savollar s ON s.bilet_id = n.bilet_id
         WHERE n.foydalanuvchi_id = ? AND n.holat = 'tugagan'",
        [$f['id']]
    );

    $obuna_faol = obuna_faolmi($f['id']) || in_array($f['rol'], ['admin', 'developer'], true);

    $biletlar = db_barcha(
        "SELECT b.*, (SELECT COUNT(*) FROM savollar s WHERE s.bilet_id = b.id) AS savol_son
         FROM biletlar b WHERE b.holat = 'faol' ORDER BY b.raqam"
    );

    $sahifa_sarlavha = 'Mashq rejimi';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <main class="max-w-5xl mx-auto px-4 py-8">
        <div class="mb-8 fade-up">
            <span class="chip chip-grad mb-3">📚 Mashq</span>
            <h1 class="text-3xl md:text-4xl mb-2 font-display font-extrabold">
                Bilimlaringizni <span class="grad-text">mustahkamlang</span>
            </h1>
            <p class="text-muted">Vaqt cheklovsiz, javoblar darhol ko'rinadi va izoh bilan</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <a href="?rejim=xatolar" class="ring-grad fade-up <?= $xato_savollar < 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                <div class="p-6 h-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-14 h-14 rounded-2xl bg-danger/15 text-danger flex items-center justify-center text-3xl">⚡</div>
                        <span class="chip text-xs !py-0.5 !px-2 bg-danger/15 text-danger border-danger/30">
                            <?= (int) $xato_savollar ?> savol
                        </span>
                    </div>
                    <h2 class="text-xl font-display font-bold mb-2">Xatolarim ustida</h2>
                    <p class="text-sm text-muted">
                        Avval xato javob bergan savollar. Eng tezkor o'rganish usuli.
                    </p>
                    <?php if ($xato_savollar < 1): ?>
                        <p class="text-xs text-amber mt-3">Avval test ishlash kerak</p>
                    <?php endif; ?>
                </div>
            </a>

            <a href="?rejim=tasodifiy" class="glass glass-hover p-6 fade-up <?= !$obuna_faol ? 'opacity-50 pointer-events-none' : '' ?>">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-violet/15 text-violet flex items-center justify-center text-3xl">🎲</div>
                    <span class="chip chip-grad text-xs !py-0.5 !px-2">PRO</span>
                </div>
                <h2 class="text-xl font-display font-bold mb-2">Tasodifiy 20 ta</h2>
                <p class="text-sm text-muted">
                    Barcha biletlardan tasodifiy 20 ta savol. Marafon mashqi.
                </p>
                <?php if (!$obuna_faol): ?>
                    <p class="text-xs text-amber mt-3">Tarif sotib oling</p>
                <?php endif; ?>
            </a>
        </div>

        <div class="glass p-5 fade-up">
            <h3 class="font-display font-bold mb-4">🎫 Bilet bo'yicha mashq</h3>
            <p class="text-sm text-muted mb-4">Tanlangan bilet savollarini vaqt cheklovsiz, javoblar bilan ishlash</p>

            <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-8 gap-2">
                <?php foreach ($biletlar as $b):
                    $bepul = $b['tur'] === 'bepul';
                    $kirish = $bepul || $obuna_faol;
                ?>
                    <?php if ($kirish): ?>
                        <a href="?rejim=bilet&bilet=<?= (int) $b['id'] ?>"
                           class="aspect-square rounded-xl flex flex-col items-center justify-center transition border
                                  <?= $bepul ? 'border-success/30 bg-success/5 hover:bg-success/10' : 'border-amber/30 bg-amber/5 hover:bg-amber/10' ?>">
                            <span class="font-display font-bold text-lg">№<?= (int) $b['raqam'] ?></span>
                            <span class="text-[10px] text-muted"><?= (int) $b['savol_son'] ?> savol</span>
                        </a>
                    <?php else: ?>
                        <div class="aspect-square rounded-xl flex flex-col items-center justify-center bg-white/3 border border-white/10 opacity-50">
                            <span class="text-lg">🔒</span>
                            <span class="font-display font-bold text-xs">№<?= (int) $b['raqam'] ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($rejim === 'xatolar') {
    $natijalar = db_barcha(
        "SELECT id, bilet_id, javoblar_json FROM natijalar
         WHERE foydalanuvchi_id = ? AND holat = 'tugagan' AND xato_son > 0
         ORDER BY tugagan DESC LIMIT 30",
        [$f['id']]
    );

    $xato_id_lar = [];
    foreach ($natijalar as $n) {
        $javoblar = json_decode($n['javoblar_json'] ?? '{}', true) ?: [];
        if (!$javoblar) continue;
        $savollar = db_barcha(
            'SELECT id, togri_javob FROM savollar WHERE bilet_id = ?',
            [$n['bilet_id']]
        );
        foreach ($savollar as $s) {
            if (isset($javoblar[$s['id']]) && $javoblar[$s['id']] !== $s['togri_javob']) {
                $xato_id_lar[$s['id']] = true;
            }
        }
    }
    $xato_id_lar = array_keys($xato_id_lar);
    if (empty($xato_id_lar)) {
        flash_qoy('xato', 'Xato javoblar yo\'q. Avval test ishlang.');
        yonaltir(SAYT_URL . '/mashq');
    }
    shuffle($xato_id_lar);
    $xato_id_lar = array_slice($xato_id_lar, 0, 30);
    $placeholder = implode(',', array_fill(0, count($xato_id_lar), '?'));
    $savollar = db_barcha(
        "SELECT * FROM savollar WHERE id IN ($placeholder)",
        $xato_id_lar
    );
    $sarlavha_data = ['🔥 Xatolarim ustida', count($savollar), 'danger'];

} elseif ($rejim === 'tasodifiy') {
    if (!obuna_faolmi($f['id']) && !in_array($f['rol'], ['admin', 'developer'], true)) {
        flash_qoy('xato', t('pullik_bilet'));
        yonaltir(SAYT_URL . '/tolov');
    }
    $savollar = db_barcha(
        "SELECT s.* FROM savollar s
         JOIN biletlar b ON s.bilet_id = b.id
         WHERE b.holat = 'faol'
         ORDER BY RAND() LIMIT 20"
    );
    $sarlavha_data = ['🎲 Tasodifiy 20 ta', count($savollar), 'violet'];

} elseif ($rejim === 'bilet' && $bilet_id) {
    $bilet = db_qator('SELECT * FROM biletlar WHERE id = ? AND holat = "faol"', [$bilet_id]);
    if (!$bilet) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/mashq');
    }
    if ($bilet['tur'] === 'pullik' && !obuna_faolmi($f['id']) && $f['rol'] === 'user') {
        flash_qoy('xato', t('pullik_bilet'));
        yonaltir(SAYT_URL . '/tolov');
    }
    $savollar = db_barcha(
        'SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$bilet_id]
    );
    $sarlavha_data = ["🎫 Bilet №{$bilet['raqam']} mashqi", count($savollar), 'cyan'];
} else {
    yonaltir(SAYT_URL . '/mashq');
}

if (empty($savollar)) {
    flash_qoy('xato', 'Savollar topilmadi');
    yonaltir(SAYT_URL . '/mashq');
}

$sahifa_sarlavha = 'Mashq';
$body_class = 'test-page no-select';
require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="mashq(<?= htmlspecialchars(json_encode([
    'savollar' => array_map(function ($s) {
        return [
            'id' => (int) $s['id'],
            'matn' => $s['matn'],
            'rasm' => $s['rasm'],
            'a' => $s['variant_a'],
            'b' => $s['variant_b'],
            'c' => $s['variant_c'],
            'd' => $s['variant_d'],
            'togri' => $s['togri_javob'],
            'izoh' => $s['izoh'] ?? '',
        ];
    }, $savollar),
], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" x-cloak>

    <div class="sticky top-0 z-30 backdrop-blur-2xl bg-bg/90 border-b border-white/10">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= e(SAYT_URL) ?>/mashq" class="text-muted hover:text-text text-xl">←</a>
                <span class="font-display font-bold truncate"><?= e($sarlavha_data[0]) ?></span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-success font-bold" x-text="`✓ ${togri}`"></span>
                <span class="text-danger font-bold" x-text="`✗ ${xato}`"></span>
                <span class="text-muted hidden sm:inline" x-text="`${joriy + 1}/${savollar.length}`"></span>
            </div>
        </div>
        <div class="h-1 bg-white/5">
            <div class="h-full grad-bg transition-all duration-300" :style="`width:${(joriy + 1) / savollar.length * 100}%`"></div>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-8">

        <template x-if="!tugadi">
            <template x-for="(s, i) in savollar" :key="s.id">
                <div x-show="i === joriy" class="glass p-6 fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-muted text-sm">Savol <span class="font-bold text-text" x-text="i + 1"></span> / <span x-text="savollar.length"></span></span>
                    </div>

                    <p class="text-lg mb-4 leading-relaxed" x-text="s.matn"></p>

                    <template x-if="s.rasm">
                        <img :src="`<?= e(SAYT_URL) ?>/uploads/${s.rasm}`" loading="lazy"
                             class="rounded-xl mb-4 max-w-md w-full mx-auto">
                    </template>

                    <div class="space-y-2">
                        <template x-for="v in ['a','b','c','d']" :key="v">
                            <button x-show="s[v]" @click="javobBer(v)" :disabled="javob !== null"
                                    :class="
                                        javob === null
                                            ? 'bg-white/3 border-white/10 hover:border-white/30'
                                            : (v === s.togri ? 'bg-success/15 border-success/40 text-success' :
                                              (v === javob ? 'bg-danger/15 border-danger/40 text-danger' : 'bg-white/3 border-white/5 opacity-50'))
                                    "
                                    class="w-full text-left p-3 rounded-xl border transition-all flex items-start gap-3">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 uppercase bg-white/10"
                                      x-text="v"></span>
                                <span x-text="s[v]" class="flex-1"></span>
                                <template x-if="javob !== null && v === s.togri">
                                    <span class="text-success">✓</span>
                                </template>
                                <template x-if="javob !== null && v === javob && v !== s.togri">
                                    <span class="text-danger">✗</span>
                                </template>
                            </button>
                        </template>
                    </div>

                    <div x-show="javob !== null" x-transition class="mt-4 p-4 rounded-xl"
                         :class="javob === s.togri ? 'bg-success/10 border border-success/30' : 'bg-violet/10 border border-violet/30'">
                        <p class="font-bold mb-1" x-text="javob === s.togri ? '✓ To\'g\'ri javob!' : '💡 To\'g\'ri javob — ' + s.togri.toUpperCase()"></p>
                        <p class="text-sm text-muted" x-show="s.izoh" x-text="s.izoh"></p>
                    </div>

                    <div class="flex justify-between mt-6 gap-3">
                        <button @click="oldingiSavol()" :disabled="joriy === 0"
                                class="btn btn-ghost flex-1 sm:flex-none">← Oldingi</button>
                        <template x-if="joriy < savollar.length - 1">
                            <button @click="keyingiSavol()" :disabled="javob === null"
                                    class="btn btn-primary flex-1 sm:flex-none">Keyingi →</button>
                        </template>
                        <template x-if="joriy === savollar.length - 1">
                            <button @click="tugat()" :disabled="javob === null"
                                    class="btn flex-1 sm:flex-none" style="background: linear-gradient(135deg,#10B981,#06B6D4); color: white;">
                                Tugatish ✓
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </template>

        <div x-show="tugadi" x-cloak class="ring-grad fade-up">
            <div class="p-10 text-center">
                <div class="w-24 h-24 mx-auto rounded-3xl flex items-center justify-center text-5xl mb-4"
                     :class="togri / savollar.length >= 0.9 ? 'bg-success/15 text-success' :
                             (togri / savollar.length >= 0.7 ? 'bg-cyan/15 text-cyan' :
                             (togri / savollar.length >= 0.5 ? 'bg-amber/15 text-amber' : 'bg-danger/15 text-danger'))">
                    <span x-text="Math.round(togri / savollar.length * 100) + '%'"></span>
                </div>
                <h2 class="text-3xl font-display font-extrabold mb-2 grad-text">Mashq tugadi!</h2>
                <p class="text-muted mb-6">
                    <span class="text-success font-bold" x-text="togri"></span> to'g'ri ·
                    <span class="text-danger font-bold" x-text="xato"></span> xato
                </p>
                <div class="flex justify-center gap-3 flex-wrap">
                    <button @click="qaytadan()" class="btn btn-primary">🔄 Qaytadan</button>
                    <a href="<?= e(SAYT_URL) ?>/mashq" class="btn btn-ghost">📚 Boshqa mashq</a>
                    <a href="<?= e(SAYT_URL) ?>/dashboard" class="btn btn-ghost">🏠 Dashboard</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function mashq(boshlangich) {
            return {
                savollar: boshlangich.savollar,
                joriy: 0,
                javob: null,
                togri: 0,
                xato: 0,
                tugadi: false,
                javoblar_tarixi: {},

                javobBer(v) {
                    if (this.javob !== null) return;
                    this.javob = v;
                    this.javoblar_tarixi[this.savollar[this.joriy].id] = v;
                    if (v === this.savollar[this.joriy].togri) {
                        this.togri++;
                    } else {
                        this.xato++;
                    }
                },

                keyingiSavol() {
                    if (this.joriy < this.savollar.length - 1) {
                        this.joriy++;
                        this.javob = this.javoblar_tarixi[this.savollar[this.joriy].id] || null;
                    }
                },

                oldingiSavol() {
                    if (this.joriy > 0) {
                        this.joriy--;
                        this.javob = this.javoblar_tarixi[this.savollar[this.joriy].id] || null;
                    }
                },

                tugat() {
                    this.tugadi = true;
                },

                qaytadan() {
                    this.joriy = 0;
                    this.javob = null;
                    this.togri = 0;
                    this.xato = 0;
                    this.tugadi = false;
                    this.javoblar_tarixi = {};
                    this.savollar = [...this.savollar].sort(() => Math.random() - 0.5);
                }
            };
        }
    </script>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
