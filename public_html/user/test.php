<?php
/**
 * VatanParvar Yaypan — Test sahifasi (3 rejim)
 *
 *  /test               → biletlar ro'yxati
 *  /test?bilet=N       → test ishlash
 *  /test?natija=ID     → natijani ko'rish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$f         = kirgan_bolish_kerak();
$bilet_id  = (int) olish('bilet');
$natija_id = (int) olish('natija');

/* ──────────────────────────────────────────────────────────
   REJIM 3: Natijani ko'rish
   ────────────────────────────────────────────────────────── */
if ($natija_id > 0) {
    $natija = db_qator(
        'SELECT n.*, b.raqam, b.nomi AS bilet_nomi
         FROM natijalar n JOIN biletlar b ON n.bilet_id = b.id
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
        ? round($natija['togri_son'] / $natija['umumiy_son'] * 100) : 0;

    $sahifa_sarlavha = t('natija') . ' — №' . (int)$natija['raqam'];
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <main style="max-width: 800px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem;">

        <a href="<?= e(SAYT_URL) ?>/dashboard"
           style="display:inline-block; font-size:.85rem; color:#666;
                  text-decoration:underline; text-underline-offset:3px;
                  margin-bottom: 1.5rem;">
            ← <?= e(t('orqaga')) ?>
        </a>

        <!-- Natija xulosa -->
        <div class="b-card" style="padding: 2.5rem 2rem; text-align:center;
                                   margin-bottom: 1.5rem;">
            <div style="font-family:Georgia,serif; font-weight:700;
                        font-size: clamp(4rem, 12vw, 6rem); line-height:1;
                        margin-bottom: 1rem;" class="tabnum">
                <?= $foiz ?>%
            </div>
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.5rem; margin-bottom: .35rem;">
                №<?= (int)$natija['raqam'] ?> — <?= e($natija['bilet_nomi']) ?>
            </h1>
            <p style="font-size:.95rem; color:#555; margin-bottom: 1.5rem;">
                <?= match(true) {
                    $foiz >= 90 => "Ajoyib natija. Imtihonga tayyorsiz.",
                    $foiz >= 70 => "Yaxshi natija. Bir oz mashq qiling.",
                    $foiz >= 50 => "O'rtacha. Ko'proq mashq qiling.",
                    default     => "Tushkunlikka tushmang, qayta urinib ko'ring."
                } ?>
            </p>

            <!-- Progress -->
            <div style="max-width: 18rem; margin: 0 auto 1.5rem;
                        height: 6px; border:1px solid #000;">
                <div style="height: 100%; background:#000; width:<?= $foiz ?>%;"></div>
            </div>

            <!-- Statistika qatori -->
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr;
                        gap: 0; border:1px solid #000; margin-bottom: 1.5rem;
                        font-size:.85rem;">
                <div style="padding: .9rem;">
                    <div style="color:#666; font-size:.72rem; text-transform:uppercase;
                                letter-spacing:.06em; margin-bottom:.3rem;">
                        To'g'ri
                    </div>
                    <div style="font-family:Georgia,serif; font-weight:700;
                                font-size: 1.35rem;" class="tabnum">
                        <?= (int)$natija['togri_son'] ?>
                    </div>
                </div>
                <div style="padding: .9rem; border-left: 1px solid #E5E5E5;">
                    <div style="color:#666; font-size:.72rem; text-transform:uppercase;
                                letter-spacing:.06em; margin-bottom:.3rem;">
                        Xato
                    </div>
                    <div style="font-family:Georgia,serif; font-weight:700;
                                font-size: 1.35rem;" class="tabnum">
                        <?= (int)$natija['xato_son'] ?>
                    </div>
                </div>
                <div style="padding: .9rem; border-left: 1px solid #E5E5E5;">
                    <div style="color:#666; font-size:.72rem; text-transform:uppercase;
                                letter-spacing:.06em; margin-bottom:.3rem;">
                        Jami
                    </div>
                    <div style="font-family:Georgia,serif; font-weight:700;
                                font-size: 1.35rem;" class="tabnum">
                        <?= (int)$natija['umumiy_son'] ?>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:.75rem; justify-content:center; flex-wrap:wrap;">
                <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int)$natija['bilet_id'] ?>&qaytadan=1"
                   class="btn btn-primary"><?= e(t('qaytadan_yechish')) ?></a>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost"><?= e(t('biletlar_royxati')) ?></a>
            </div>
        </div>

        <!-- Savollar tahlili -->
        <h2 style="font-family:Georgia,serif; font-weight:700;
                   font-size: 1.25rem; margin-bottom: 1rem;
                   padding-bottom: .65rem; border-bottom: 1px solid #000;">
            Savol tahlili
        </h2>

        <?php foreach ($savollar as $i => $s):
            $j = $javoblar[$s['id']] ?? null;
            $togri_ = ($j === $s['togri_javob']);
        ?>
        <div class="b-card" style="padding: 1.25rem; margin-bottom: .75rem;">

            <!-- Sarlavha -->
            <div style="display:flex; align-items:flex-start; gap:.85rem;
                        margin-bottom: .85rem;">
                <span style="display:inline-flex; align-items:center; justify-content:center;
                             width: 32px; height: 32px; border:1px solid #000;
                             font-family:Georgia,serif; font-weight:700;
                             font-size:.85rem; flex-shrink:0;
                             <?= $togri_ ? 'background:#000; color:#fff;' :
                                ($j ? 'background:#fff; color:#000;' : 'background:#F5F5F5; color:#666;') ?>">
                    <?= $i + 1 ?>
                </span>
                <p style="font-size:.95rem; line-height:1.55; flex:1; font-weight:500;">
                    <?= e($s['matn']) ?>
                </p>
                <span class="badge <?= $togri_ ? 'badge-filled' : 'badge-light' ?>"
                      style="flex-shrink:0;">
                    <?= $togri_ ? '✓ To\'g\'ri' : ($j ? '✗ Xato' : '— Javobsiz') ?>
                </span>
            </div>

            <?php if ($s['rasm'] && is_file(UPLOAD_PATH.'/'.$s['rasm'])): ?>
            <img src="<?= e(SAYT_URL.'/uploads/'.$s['rasm']) ?>"
                 style="max-width: 22rem; width:100%; border:1px solid #000;
                        margin: 0 0 1rem 2.85rem;" loading="lazy" alt="">
            <?php endif; ?>

            <!-- Variantlar -->
            <div style="margin-left: 2.85rem;
                        display:grid; grid-template-columns: 1fr; gap:.4rem;"
                 class="sm:grid-cols-2">
                <?php foreach (['a','b','c','d'] as $v):
                    $mv = $s['variant_'.$v] ?? null;
                    if (!$mv) continue;
                    $isTog = ($v === $s['togri_javob']);
                    $isJav = ($v === $j);
                ?>
                <div style="padding:.6rem .85rem; border:1px solid #000;
                            font-size:.85rem; display:flex; align-items:flex-start; gap:.5rem;
                            <?= $isTog ? 'background:#000; color:#fff;'
                                : ($isJav ? 'background:#F5F5F5;' : '') ?>">
                    <strong style="text-transform:uppercase; flex-shrink:0;">
                        <?= $v ?>)
                    </strong>
                    <span style="flex:1;"><?= e($mv) ?></span>
                    <?php if ($isTog): ?><span style="flex-shrink:0;">✓</span><?php endif; ?>
                    <?php if ($isJav && !$isTog): ?><span style="flex-shrink:0;">✗</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($s['izoh'])): ?>
            <div style="margin-left: 2.85rem; margin-top: .85rem;
                        padding: .65rem .85rem; background: #F5F5F5;
                        border: 1px solid #E5E5E5; font-size:.82rem;
                        line-height: 1.55;">
                <strong>Izoh:</strong> <?= e($s['izoh']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </main>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

/* ──────────────────────────────────────────────────────────
   REJIM 2: Test ishlash
   ────────────────────────────────────────────────────────── */
if ($bilet_id > 0) {
    $bilet = db_qator('SELECT * FROM biletlar WHERE id = ? AND holat = "faol"', [$bilet_id]);
    if (!$bilet) {
        flash_qoy('xato', t('malumot_yoq'));
        yonaltir(SAYT_URL . '/test');
    }

    if ($bilet['tur'] === 'pullik' && $f['rol'] === 'user' && !obuna_faolmi($f['id'])) {
        flash_qoy('xato', 'Bu bilet pullik. Avval tarif sotib oling.');
        yonaltir(SAYT_URL . '/tolov');
    }

    if (olish('qaytadan') === '1') {
        db_bajar(
            'UPDATE natijalar SET holat = "bekor"
             WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom"',
            [$f['id'], $bilet_id]
        );
    }

    $natija = db_qator(
        'SELECT * FROM natijalar
         WHERE foydalanuvchi_id = ? AND bilet_id = ? AND holat = "davom"
         ORDER BY id DESC LIMIT 1',
        [$f['id'], $bilet_id]
    );

    if (!$natija) {
        $test_vaqt = (int) sozlama('test_vaqti_minut', 25) * 60;
        $savol_son = (int) db_qiymat('SELECT COUNT(*) FROM savollar WHERE bilet_id = ?', [$bilet_id]);
        $yid = db_bajar(
            'INSERT INTO natijalar (foydalanuvchi_id, bilet_id, javoblar_json, umumiy_son, qolgan_vaqt)
             VALUES (?, ?, "{}", ?, ?)',
            [$f['id'], $bilet_id, $savol_son, $test_vaqt]
        );
        $natija = db_qator('SELECT * FROM natijalar WHERE id = ?', [$yid]);
    }

    $savollar = db_barcha(
        'SELECT id, matn, rasm, variant_a, variant_b, variant_c, variant_d
         FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$bilet_id]
    );
    $javoblar = json_decode($natija['javoblar_json'] ?? '{}', true) ?: [];
    $qolgan   = max(0, (int)$natija['qolgan_vaqt'] - (time() - strtotime($natija['boshlangan'])));

    $sahifa_sarlavha = $bilet['nomi'];
    $body_class      = 'test-page no-select';
    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div x-data="testApp(<?= htmlspecialchars(json_encode([
        'natija_id' => (int) $natija['id'],
        'qolgan'    => $qolgan,
        'savollar'  => $savollar,
        'javoblar'  => $javoblar,
        'apiUrl'    => SAYT_URL . '/api/save_answer.php',
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" x-cloak>

        <!-- Sticky header -->
        <header style="position:sticky; top:0; z-index:40;
                       background:#fff; border-bottom: 1px solid #000;">
            <div style="max-width: 900px; margin: 0 auto; padding: 0 1.25rem;
                        height: 60px; display:flex; align-items:center; gap:1rem;">
                <a href="<?= e(SAYT_URL) ?>/dashboard"
                   style="font-size:.85rem; color:#000;
                          text-decoration:underline; text-underline-offset:3px;
                          flex-shrink:0;">←</a>

                <span style="font-family:Georgia,serif; font-weight:700;
                             font-size:.95rem; flex:1; min-width:0;
                             overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    №<?= (int)$bilet['raqam'] ?> — <?= e($bilet['nomi']) ?>
                </span>

                <span style="font-size:.78rem; color:#666; flex-shrink:0;
                             display:none;" class="sm:inline tabnum"
                      x-text="Object.keys(javoblar).length + '/' + savollar.length"></span>

                <!-- Taymer -->
                <span style="font-family:Georgia,serif; font-weight:700;
                             padding: .35rem .8rem; border:1px solid #000;
                             font-size:.95rem; flex-shrink:0;"
                      class="tabnum"
                      :style="qolgan < 60 ? 'background:#000;color:#fff;' : ''"
                      x-text="vaqtFormat(qolgan)"></span>
            </div>

            <!-- Progress -->
            <div style="height: 2px; background:#F5F5F5;">
                <div style="height: 100%; background: #000;
                            transition: width .3s;"
                     :style="'width:' + (Object.keys(javoblar).length / savollar.length * 100) + '%'"></div>
            </div>
        </header>

        <main style="max-width: 800px; margin: 0 auto; padding: 1.5rem 1.25rem 4rem;">

            <!-- Savol paginatori -->
            <div style="display:flex; flex-wrap:wrap; gap:.3rem; margin-bottom: 1.5rem;">
                <template x-for="(s, i) in savollar" :key="s.id">
                    <button @click="joriy = i"
                            :style="i === joriy
                                ? 'background:#000;color:#fff;border-color:#000;'
                                : (javoblar[s.id]
                                    ? 'background:#F5F5F5;color:#000;border-color:#000;'
                                    : 'background:#fff;color:#666;border-color:#E5E5E5;')"
                            style="width: 36px; height: 36px;
                                   border: 1px solid; cursor:pointer;
                                   font-family: Georgia, serif; font-weight: 700;
                                   font-size: .82rem;
                                   transition: background-color .12s, border-color .12s;"
                            class="tabnum"
                            x-text="i + 1"></button>
                </template>
            </div>

            <!-- Joriy savol -->
            <template x-for="(s, i) in savollar" :key="s.id">
                <div x-show="i === joriy" class="b-card"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     style="padding: 1.75rem;">

                    <!-- Savol raqami -->
                    <div style="display:flex; justify-content:space-between;
                                align-items:center; margin-bottom: 1rem;
                                padding-bottom: .85rem; border-bottom: 1px solid #E5E5E5;">
                        <div style="font-size:.78rem; color:#666;
                                    text-transform:uppercase; letter-spacing:.08em;">
                            Savol <span x-text="i + 1" class="tabnum"></span> /
                            <span x-text="savollar.length" class="tabnum"></span>
                        </div>
                        <span x-show="javoblar[s.id]" class="badge badge-filled"
                              x-text="(javoblar[s.id] || '').toUpperCase()"></span>
                    </div>

                    <!-- Savol matni -->
                    <p style="font-family:Georgia,serif; font-weight:500;
                              font-size: 1.1rem; line-height: 1.55;
                              margin-bottom: 1.25rem;"
                       x-text="s.matn"></p>

                    <template x-if="s.rasm">
                        <img :src="'<?= e(SAYT_URL) ?>/uploads/' + s.rasm"
                             style="max-width: 22rem; width:100%; border:1px solid #000;
                                    margin: 0 auto 1.25rem; display:block;"
                             loading="lazy" alt="">
                    </template>

                    <!-- Variantlar -->
                    <div style="display:flex; flex-direction:column; gap:.5rem;">
                        <template x-for="v in ['a','b','c','d']" :key="v">
                            <button x-show="s['variant_' + v]"
                                    @click="javobBer(s.id, v)"
                                    :style="javoblar[s.id] === v
                                        ? 'background:#000;color:#fff;border-color:#000;'
                                        : 'background:#fff;color:#000;border-color:#000;'"
                                    style="display:flex; align-items:flex-start;
                                           gap:.85rem; padding: .85rem 1rem;
                                           border: 1px solid #000; cursor:pointer;
                                           text-align:left; font-family:inherit;
                                           font-size:.95rem; line-height:1.5;
                                           transition: background-color .12s, color .12s;"
                                    onmouseover="if(this.style.background.includes('rgb(255'))this.style.background='#F5F5F5'"
                                    onmouseout="if(this.style.background==='rgb(245, 245, 245)')this.style.background='#fff'">
                                <span style="font-family:Georgia,serif; font-weight:700;
                                             font-size:1rem; flex-shrink:0; min-width:1.25rem;"
                                      x-text="v.toUpperCase() + ')'"></span>
                                <span x-text="s['variant_' + v]" style="flex:1;"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Navigatsiya -->
                    <div style="display:flex; gap:.75rem; margin-top: 1.5rem;
                                padding-top: 1.25rem; border-top: 1px solid #E5E5E5;">
                        <button @click="joriy = Math.max(0, joriy - 1)"
                                :disabled="joriy === 0"
                                class="btn btn-ghost" style="flex:1;"
                                :style="joriy === 0 ? 'opacity:.4;cursor:not-allowed;' : ''">
                            ← <?= e(t('oldingi')) ?>
                        </button>

                        <template x-if="joriy < savollar.length - 1">
                            <button @click="joriy++" class="btn btn-primary" style="flex:1;">
                                <?= e(t('keyingi')) ?> →
                            </button>
                        </template>
                        <template x-if="joriy === savollar.length - 1">
                            <button @click="modal = true" class="btn btn-primary" style="flex:1;">
                                <?= e(t('tugatish')) ?> ✓
                            </button>
                        </template>
                    </div>

                    <!-- Saqlash holati -->
                    <p style="margin-top: .85rem; height: 1rem; text-align:center;
                              font-size:.78rem; color:#666;">
                        <span x-show="saqlash === 'saqlanmoqda'">⏳ Saqlanmoqda...</span>
                        <span x-show="saqlash === 'saqlandi'" x-cloak>✓ Saqlandi</span>
                        <span x-show="saqlash === 'xato'" x-cloak
                              style="color:#000; font-weight:600;">⚠ Saqlash xatosi</span>
                    </p>
                </div>
            </template>
        </main>

        <!-- Tugatish modali -->
        <div x-show="modal" x-transition
             @click.self="modal = false"
             style="position:fixed; inset:0; z-index:50;
                    background:rgba(0,0,0,.7);
                    display:flex; align-items:center; justify-content:center;
                    padding: 1rem;">
            <div class="b-card" @click.stop
                 style="background: #fff; max-width: 24rem; width:100%; padding: 2rem;">
                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.5rem; margin-bottom: 1rem;
                           padding-bottom: .85rem; border-bottom: 1px solid #000;">
                    <?= e(t('tasdiqlaysizmi')) ?>
                </h3>

                <p style="font-size:.9rem; margin-bottom: .75rem;">
                    Javob berilgan:
                    <strong x-text="Object.keys(javoblar).length"></strong>
                    /
                    <span x-text="savollar.length"></span>
                </p>

                <p x-show="savollar.length - Object.keys(javoblar).length > 0"
                   style="font-size:.85rem; padding:.65rem .85rem;
                          background:#000; color:#fff; margin-bottom: 1.25rem;"
                   x-text="(savollar.length - Object.keys(javoblar).length) + ' ta savol javobsiz!'">
                </p>

                <div style="display:flex; gap:.75rem; margin-top: 1rem;">
                    <button @click="modal = false" class="btn btn-ghost" style="flex:1;">
                        <?= e(t('bekor_qilish')) ?>
                    </button>
                    <button @click="testTugat(false)" class="btn btn-primary"
                            style="flex:1;" :disabled="loading">
                        <span x-text="loading ? '...' : '<?= e(t('tugatish')) ?>'">
                            <?= e(t('tugatish')) ?>
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </div>

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
            _lsKey    : 'vpy_' + d.natija_id,

            init() {
                try {
                    var saved = localStorage.getItem(this._lsKey);
                    if (saved) this.javoblar = Object.assign({}, JSON.parse(saved), this.javoblar);
                } catch(e) {}
                this._timer = setInterval(() => {
                    if (this.qolgan > 0) this.qolgan--;
                    else { clearInterval(this._timer); this.testTugat(true); }
                }, 1000);
            },

            async javobBer(savol_id, variant) {
                this.javoblar[savol_id] = variant;
                try { localStorage.setItem(this._lsKey, JSON.stringify(this.javoblar)); } catch(e) {}
                this.saqlash = 'saqlanmoqda';
                var r = await window.apiPost(this.apiUrl, {
                    natija_id: this.natija_id, savol_id, variant
                });
                this.saqlash = (r && r.ok) ? 'saqlandi' : 'xato';
                setTimeout(() => { if (this.saqlash !== 'saqlanmoqda') this.saqlash = ''; }, 2000);
            },

            async testTugat(vaqtTugadi) {
                vaqtTugadi = !!vaqtTugadi;
                clearInterval(this._timer);
                this.loading = true;
                this.modal = false;
                var r = await window.apiPost(this.apiUrl, {
                    natija_id: this.natija_id, tugatish: '1',
                    vaqt_tugadi: vaqtTugadi ? '1' : '0'
                });
                this.loading = false;
                if (r && r.ok) {
                    try { localStorage.removeItem(this._lsKey); } catch(e) {}
                    window.location.href = '<?= e(SAYT_URL) ?>/test?natija=' + this.natija_id;
                } else {
                    alert(r ? r.xato : 'Xato yuz berdi.');
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

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

/* ──────────────────────────────────────────────────────────
   REJIM 1: Biletlar ro'yxati
   ────────────────────────────────────────────────────────── */
$biletlar = db_barcha(
    'SELECT b.*,
        (SELECT COUNT(*) FROM savollar WHERE bilet_id = b.id) AS savol_son,
        (SELECT ROUND(MAX(togri_son / NULLIF(umumiy_son, 0) * 100))
         FROM natijalar
         WHERE foydalanuvchi_id = ? AND bilet_id = b.id AND holat = "tugagan") AS eng_yaxshi
     FROM biletlar b
     WHERE b.holat = "faol"
     ORDER BY b.raqam',
    [$f['id']]
);
$obuna_faol = obuna_faolmi($f['id']);

$sahifa_sarlavha = t('biletlar_royxati');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main style="max-width:1200px; margin:0 auto; padding: 2.5rem 1.25rem 4rem;">

    <div style="margin-bottom: 2rem;">
        <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                  letter-spacing:.08em; margin-bottom:.5rem;">Testlar</p>
        <h1 style="font-family:Georgia,serif; font-weight:700;
                   font-size: clamp(1.75rem, 4vw, 2.5rem);">
            <?= e(t('biletlar_royxati')) ?>
        </h1>
    </div>

    <?php if (!$obuna_faol && $f['rol'] === 'user'): ?>
    <div class="b-card" style="padding: 1.25rem; margin-bottom: 1.5rem;
                                display:flex; justify-content:space-between;
                                align-items:center; flex-wrap:wrap; gap:1rem;
                                background: #000; color: #fff;">
        <div>
            <strong style="font-family:Georgia,serif; font-size:1rem;
                            margin-bottom:.25rem; display:block;">
                Faol obuna yo'q
            </strong>
            <span style="font-size:.85rem; color:#ccc;">
                Faqat bepul biletlar mavjud
            </span>
        </div>
        <a href="<?= e(SAYT_URL) ?>/tolov"
           style="display:inline-flex; align-items:center;
                  padding:.65rem 1.25rem; background:#fff; color:#000;
                  border:1px solid #fff; text-decoration:none;
                  font-size:.9rem; font-weight:500;"
           onmouseover="this.style.background='transparent';this.style.color='#fff'"
           onmouseout="this.style.background='#fff';this.style.color='#000'">
            <?= e(t('tarif_olish')) ?> →
        </a>
    </div>
    <?php endif; ?>

    <?php if (empty($biletlar)): ?>
    <div class="b-card" style="padding: 4rem 2rem; text-align:center;">
        <p style="color:#666;"><?= e(t('malumot_yoq')) ?></p>
    </div>
    <?php else: ?>
    <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                border:1px solid #000;"
         class="sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <?php foreach ($biletlar as $i => $b):
            $bepul  = ($b['tur'] === 'bepul');
            $kirish = $bepul || $obuna_faol || in_array($f['rol'], ['admin','developer'], true);
            $eng    = ($b['eng_yaxshi'] !== null) ? (int)$b['eng_yaxshi'] : null;
        ?>
        <a <?= $kirish ? 'href="' . e(SAYT_URL) . '/test?bilet=' . (int)$b['id'] . '"' : 'href="' . e(SAYT_URL) . '/tolov"' ?>
           style="display:flex; flex-direction:column;
                  padding: 1.5rem; text-decoration:none; color:#000;
                  border-top: 1px solid #E5E5E5;
                  transition: background-color .15s;
                  <?= !$kirish ? 'opacity:.55;' : '' ?>"
           class="<?= $i % 2 !== 0 ? 'sm:[&]:border-l sm:[&]:border-l-[#E5E5E5]' : '' ?>"
           onmouseover="this.style.background='#F5F5F5'"
           onmouseout="this.style.background='#fff'">

            <div style="display:flex; justify-content:space-between;
                        align-items:flex-start; margin-bottom: .75rem;">
                <span style="font-family:Georgia,serif; font-weight:700;
                             font-size: 2rem; line-height: 1;" class="tabnum">
                    №<?= (int)$b['raqam'] ?>
                </span>
                <span class="badge <?= $bepul ? '' : 'badge-filled' ?>">
                    <?= $bepul ? 'Bepul' : 'PRO' ?>
                </span>
            </div>

            <p style="font-size:.9rem; font-weight:500; line-height:1.4;
                      margin-bottom:.5rem; min-height: 2.5rem;" class="line-clamp-2">
                <?= e($b['nomi']) ?>
            </p>

            <p style="font-size:.78rem; color:#666; margin-bottom: 1rem;">
                <?= (int)$b['savol_son'] ?> ta savol
            </p>

            <?php if ($eng !== null): ?>
            <div style="margin-top:auto; padding-top: .85rem;
                        border-top: 1px solid #E5E5E5;">
                <div style="display:flex; justify-content:space-between;
                            align-items:center; font-size:.78rem; margin-bottom:.4rem;">
                    <span style="color:#666;">Eng yaxshi:</span>
                    <strong class="tabnum"><?= $eng ?>%</strong>
                </div>
                <div style="height: 4px; background: #F5F5F5;
                            border:1px solid #E5E5E5;">
                    <div style="height:100%; background:#000; width:<?= $eng ?>%;"></div>
                </div>
            </div>
            <?php else: ?>
            <div style="margin-top:auto; padding-top: .85rem;
                        border-top: 1px solid #E5E5E5;
                        font-size: .78rem; color: #666;">
                <?= $kirish ? 'Boshlash uchun bosing →' : '🔒 Tarif olish kerak' ?>
            </div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
