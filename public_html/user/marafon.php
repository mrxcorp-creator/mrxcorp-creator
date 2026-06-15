<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$obuna_faol = obuna_faolmi($f['id']) || in_array($f['rol'], ['admin', 'developer'], true);
if (!$obuna_faol) {
    flash_qoy('xato', 'Marafon rejimi PRO obuna talab qiladi');
    yonaltir(SAYT_URL . '/tolov');
}

$rejim = olish('rejim') ?: 'tanlash';

if ($rejim === 'tanlash') {

    try {
        $stat = db_qator(
            "SELECT COUNT(*) AS jami,
                    AVG(togri_son/umumiy_son*100) AS ortacha,
                    MAX(togri_son/umumiy_son*100) AS eng_yaxshi
             FROM marafonlar WHERE foydalanuvchi_id = ? AND holat = 'tugagan'",
            [$f['id']]
        );
    } catch (Throwable $e) {
        $stat = ['jami' => 0, 'ortacha' => 0, 'eng_yaxshi' => 0];
    }

    $sahifa_sarlavha = 'Marafon rejimi';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8 fade-up">
            <span class="chip chip-grad mb-3">🏃 Marafon</span>
            <h1 class="text-3xl md:text-4xl mb-2 font-display font-extrabold">
                Imtihon <span class="grad-text">simulatsiyasi</span>
            </h1>
            <p class="text-muted">Real imtihonga o'xshatilgan rejim. 50 ta savol, 50 daqiqa, qat'iy tartib.</p>
        </div>

        <?php if ((int) ($stat['jami'] ?? 0) > 0): ?>
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="glass p-4 fade-up">
                    <div class="text-xs text-muted uppercase mb-1">Jami marafon</div>
                    <div class="text-3xl font-display font-extrabold"><?= (int) $stat['jami'] ?></div>
                </div>
                <div class="glass p-4 fade-up">
                    <div class="text-xs text-muted uppercase mb-1">O'rtacha</div>
                    <div class="text-3xl font-display font-extrabold grad-text"><?= round($stat['ortacha']) ?>%</div>
                </div>
                <div class="glass p-4 fade-up">
                    <div class="text-xs text-muted uppercase mb-1">Eng yaxshi</div>
                    <div class="text-3xl font-display font-extrabold text-success"><?= round($stat['eng_yaxshi']) ?>%</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="ring-grad fade-up">
            <div class="p-8">
                <div class="grid sm:grid-cols-2 gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-5xl mb-2">⏱️</div>
                        <div class="text-3xl font-display font-extrabold">50:00</div>
                        <div class="text-xs text-muted uppercase tracking-widest mt-1">Daqiqa</div>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl mb-2">📝</div>
                        <div class="text-3xl font-display font-extrabold">50</div>
                        <div class="text-xs text-muted uppercase tracking-widest mt-1">Savol</div>
                    </div>
                </div>

                <div class="space-y-3 mb-6 text-sm">
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-amber/5 border border-amber/30">
                        <span class="text-amber text-xl flex-shrink-0">⚠️</span>
                        <div>
                            <strong class="text-amber">Diqqat!</strong>
                            <p class="text-muted mt-1">Marafon boshlangach to'xtatish mumkin emas. Vaqt tugaganda yoki yakunlaganingizda tugaydi.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-cyan/5 border border-cyan/30">
                        <span class="text-cyan text-xl flex-shrink-0">📊</span>
                        <div>
                            <strong class="text-cyan">Imtihon shartlari</strong>
                            <p class="text-muted mt-1">5+ xato javob = imtihon "topshirilmadi" deb belgilanadi (haqiqiy YHXBB qoidasi).</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="?rejim=boshla" x-data="{tasdiq: false}">
                    <?= csrf_input() ?>
                    <label class="flex items-start gap-3 mb-5 cursor-pointer">
                        <input type="checkbox" required x-model="tasdiq"
                               class="w-5 h-5 mt-0.5 rounded border-white/20 bg-white/5 text-violet">
                        <span class="text-sm">
                            Tushundim va tayyorman. Boshlanganidan keyin to'xtatib bo'lmaydi.
                        </span>
                    </label>
                    <button type="submit" class="btn btn-primary w-full text-base" :disabled="!tasdiq">
                        🚀 Marafonni boshlash
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($rejim === 'boshla') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_tekshir(post('csrf_token'))) {
        yonaltir(SAYT_URL . '/marafon');
    }

    try {
        db()->exec("CREATE TABLE IF NOT EXISTS marafonlar (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            foydalanuvchi_id INT UNSIGNED NOT NULL,
            savollar_json TEXT NOT NULL,
            javoblar_json TEXT DEFAULT NULL,
            togri_son INT DEFAULT 0,
            xato_son INT DEFAULT 0,
            umumiy_son INT NOT NULL DEFAULT 50,
            holat ENUM('davom','tugagan','vaqt_tugadi') DEFAULT 'davom',
            boshlangan DATETIME DEFAULT CURRENT_TIMESTAMP,
            tugagan DATETIME DEFAULT NULL,
            INDEX idx_foyd (foydalanuvchi_id),
            FOREIGN KEY (foydalanuvchi_id) REFERENCES foydalanuvchilar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }

    $davom = db_qator(
        "SELECT id FROM marafonlar WHERE foydalanuvchi_id = ? AND holat = 'davom'",
        [$f['id']]
    );
    if ($davom) {
        yonaltir(SAYT_URL . "/marafon?rejim=ishla&id=" . (int) $davom['id']);
    }

    $savollar = db_barcha(
        "SELECT id FROM savollar s
         JOIN biletlar b ON s.bilet_id = b.id
         WHERE b.holat = 'faol'
         ORDER BY RAND() LIMIT 50"
    );
    if (count($savollar) < 50) {
        flash_qoy('xato', 'Bazada 50 ta savol yetarli emas');
        yonaltir(SAYT_URL . '/marafon');
    }

    $savollar_idlar = array_column($savollar, 'id');

    $marafon_id = db_bajar(
        "INSERT INTO marafonlar (foydalanuvchi_id, savollar_json, umumiy_son)
         VALUES (?, ?, 50)",
        [$f['id'], json_encode($savollar_idlar)]
    );

    audit_yoz('marafon_boshlandi', 'marafon', $marafon_id);
    yonaltir(SAYT_URL . "/marafon?rejim=ishla&id=" . $marafon_id);
}

if ($rejim === 'natija') {
    $id = (int) olish('id');
    $marafon = db_qator('SELECT * FROM marafonlar WHERE id = ? AND foydalanuvchi_id = ?', [$id, $f['id']]);
    if (!$marafon) {
        yonaltir(SAYT_URL . '/marafon');
    }
    $togri = (int) $marafon['togri_son'];
    $xato = (int) $marafon['xato_son'];
    $umumiy = (int) $marafon['umumiy_son'];
    $foiz = $umumiy > 0 ? round($togri / $umumiy * 100) : 0;
    $topshirildi = $xato <= 4;

    $sahifa_sarlavha = 'Marafon natijasi';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <main class="max-w-3xl mx-auto px-4 py-8 fade-up">
        <a href="<?= e(SAYT_URL) ?>/dashboard" class="text-muted hover:text-text text-sm mb-4 inline-block">← <?= e(t('orqaga')) ?></a>

        <div class="ring-grad mb-6">
            <div class="p-10 text-center">
                <div class="w-32 h-32 mx-auto rounded-full flex items-center justify-center text-4xl font-display font-extrabold mb-5
                            <?= $topshirildi ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' ?>">
                    <?= $foiz ?>%
                </div>
                <h1 class="text-3xl font-display font-extrabold mb-2">
                    <?php if ($topshirildi): ?>
                        🎉 Tabriklayman!
                    <?php else: ?>
                        Imtihon topshirilmadi
                    <?php endif; ?>
                </h1>
                <p class="text-muted mb-6">
                    <?php if ($topshirildi): ?>
                        Marafonni muvaffaqiyatli yakunladingiz. YHXBB imtihoniga tayyormisiz!
                    <?php else: ?>
                        5 yoki ko'p xato javob bo'ldi. Mashq qilib qayta urinib ko'ring.
                    <?php endif; ?>
                </p>

                <div class="grid grid-cols-3 gap-3 mb-6 max-w-md mx-auto">
                    <div class="p-4 rounded-xl bg-success/5 border border-success/20">
                        <div class="text-xs text-muted uppercase mb-1">To'g'ri</div>
                        <div class="text-2xl font-display font-extrabold text-success"><?= $togri ?></div>
                    </div>
                    <div class="p-4 rounded-xl bg-danger/5 border border-danger/20">
                        <div class="text-xs text-muted uppercase mb-1">Xato</div>
                        <div class="text-2xl font-display font-extrabold text-danger"><?= $xato ?></div>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <div class="text-xs text-muted uppercase mb-1">Umumiy</div>
                        <div class="text-2xl font-display font-extrabold"><?= $umumiy ?></div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="<?= e(SAYT_URL) ?>/marafon" class="btn btn-primary">🔄 Yana marafon</a>
                    <a href="<?= e(SAYT_URL) ?>/mashq?rejim=xatolar" class="btn btn-ghost">📚 Xatolarim ustida</a>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = (int) olish('id');
$marafon = db_qator('SELECT * FROM marafonlar WHERE id = ? AND foydalanuvchi_id = ?', [$id, $f['id']]);
if (!$marafon || $marafon['holat'] !== 'davom') {
    yonaltir(SAYT_URL . '/marafon');
}

$boshlangan_t = strtotime($marafon['boshlangan']);
$qolgan = 50 * 60 - (time() - $boshlangan_t);
if ($qolgan <= 0) {
    yonaltir(SAYT_URL . '/api/marafon_yakunla.php?id=' . $id);
}

$savollar_idlar = json_decode($marafon['savollar_json'], true) ?: [];
$javoblar = json_decode($marafon['javoblar_json'] ?? '{}', true) ?: [];

$placeholder = implode(',', array_fill(0, count($savollar_idlar), '?'));
$savollar = db_barcha(
    "SELECT id, matn, rasm, variant_a, variant_b, variant_c, variant_d
     FROM savollar WHERE id IN ($placeholder)",
    $savollar_idlar
);

$xarita = [];
foreach ($savollar as $s) $xarita[$s['id']] = $s;
$tartibli_savollar = [];
foreach ($savollar_idlar as $sid) {
    if (isset($xarita[$sid])) $tartibli_savollar[] = $xarita[$sid];
}

$sahifa_sarlavha = 'Marafon';
$body_class = 'test-page no-select';
require_once __DIR__ . '/../includes/header.php';
?>

<div x-data="marafon(<?= htmlspecialchars(json_encode([
    'id' => (int) $marafon['id'],
    'qolgan' => $qolgan,
    'savollar' => $tartibli_savollar,
    'javoblar' => $javoblar,
], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" x-cloak>

    <div class="sticky top-0 z-30 backdrop-blur-2xl bg-bg/95 border-b border-amber/30">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-amber animate-pulse">🏃</span>
                <span class="font-display font-bold">MARAFON</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-muted hidden sm:inline">Qolgan vaqt:</span>
                <span class="font-mono font-bold tabular-nums px-3 py-1.5 rounded-lg"
                      :class="qolgan < 300 ? 'bg-danger/30 text-danger animate-pulse' : 'bg-amber/20 text-amber'"
                      x-text="vaqtFormat(qolgan)"></span>
            </div>
        </div>
        <div class="h-1 bg-white/5">
            <div class="h-full grad-bg transition-all duration-300" :style="`width:${(joriy + 1) / savollar.length * 100}%`"></div>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-8">

        <div class="grid grid-cols-10 sm:grid-cols-12 gap-1.5 mb-6 lg:gap-2">
            <template x-for="(s, i) in savollar" :key="s.id">
                <button @click="joriy = i"
                        :class="i === joriy ? 'ring-2 ring-violet bg-violet/30 text-white' :
                                (javoblar[s.id] ? 'bg-violet/15 text-violet' : 'bg-white/5 text-muted')"
                        class="aspect-square rounded-lg text-xs font-bold transition-all hover:bg-white/10 active:scale-95"
                        x-text="i + 1"></button>
            </template>
        </div>

        <template x-for="(s, i) in savollar" :key="s.id">
            <div x-show="i === joriy" class="glass p-6 fade-up">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-muted text-sm">Savol <span class="font-bold text-text" x-text="i + 1"></span> / 50</span>
                </div>

                <p class="text-lg mb-4 leading-relaxed" x-text="s.matn"></p>

                <template x-if="s.rasm">
                    <img :src="`<?= e(SAYT_URL) ?>/uploads/${s.rasm}`" loading="lazy"
                         class="rounded-xl mb-4 max-w-md w-full mx-auto">
                </template>

                <div class="space-y-2">
                    <template x-for="v in ['a','b','c','d']" :key="v">
                        <button x-show="s['variant_' + v]"
                                @click="javobBer(s.id, v)"
                                :class="javoblar[s.id] === v ? 'bg-violet/20 border-violet text-white' : 'bg-white/3 border-white/10 hover:border-white/30'"
                                class="w-full text-left p-3 rounded-xl border transition-all flex items-start gap-3 active:scale-[.98]">
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 uppercase"
                                  :class="javoblar[s.id] === v ? 'grad-bg text-white' : 'bg-white/10'"
                                  x-text="v"></span>
                            <span x-text="s['variant_' + v]" class="flex-1"></span>
                        </button>
                    </template>
                </div>

                <div class="flex justify-between mt-6 gap-3">
                    <button @click="joriy = Math.max(0, joriy - 1)" :disabled="joriy === 0"
                            class="btn btn-ghost flex-1 sm:flex-none">← Oldingi</button>
                    <template x-if="joriy < savollar.length - 1">
                        <button @click="joriy++" class="btn btn-primary flex-1 sm:flex-none">Keyingi →</button>
                    </template>
                    <template x-if="joriy === savollar.length - 1">
                        <button @click="modal = true" class="btn flex-1 sm:flex-none" style="background: linear-gradient(135deg,#10B981,#06B6D4); color: white;">
                            Yakunlash ✓
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </main>

    <div x-show="modal" x-transition @click.self="modal = false"
         class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="ring-grad max-w-sm w-full">
            <div class="p-8">
                <h3 class="text-xl font-display font-bold mb-2">Marafonni yakunlaysizmi?</h3>
                <p class="text-muted mb-5">Javob bermagan: <strong class="text-white" x-text="savollar.length - Object.keys(javoblar).length"></strong></p>
                <div class="flex gap-3">
                    <button @click="modal = false" class="btn btn-ghost flex-1">Bekor</button>
                    <button @click="yakunla()" class="btn btn-primary flex-1">Yakunlash</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function marafon(boshlangich) {
            return {
                id: boshlangich.id,
                qolgan: boshlangich.qolgan,
                savollar: boshlangich.savollar,
                javoblar: boshlangich.javoblar || {},
                joriy: 0,
                modal: false,
                _timer: null,

                init() {
                    this._timer = setInterval(() => {
                        this.qolgan--;
                        if (this.qolgan <= 0) {
                            clearInterval(this._timer);
                            this.yakunla(true);
                        }
                    }, 1000);
                },

                async javobBer(savol_id, variant) {
                    this.javoblar[savol_id] = variant;
                    await window.apiPost('<?= e(SAYT_URL) ?>/api/marafon_javob.php', {
                        id: this.id,
                        savol_id: savol_id,
                        variant: variant
                    });
                },

                async yakunla(vaqt_tugadi = false) {
                    clearInterval(this._timer);
                    const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/marafon_javob.php', {
                        id: this.id,
                        yakunla: '1'
                    });
                    if (r && r.ok) {
                        location.href = '<?= e(SAYT_URL) ?>/marafon?rejim=natija&id=' + this.id;
                    }
                },

                vaqtFormat(s) {
                    if (s < 0) s = 0;
                    const m = Math.floor(s / 60);
                    const ss = s % 60;
                    return String(m).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
                }
            };
        }
    </script>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
