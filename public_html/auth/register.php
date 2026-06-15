<?php
/**
 * VatanParvar Yaypan — Ro'yxatdan o'tish
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato    = '';
$ma      = ['ism' => '', 'familiya' => '', 'telefon' => ''];
$ref_kod = strtoupper(preg_replace('/[^A-Z0-9]/i', '', olish('ref')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
        $ma['ism']      = post('ism');
        $ma['familiya'] = post('familiya');
        $ma['telefon']  = post('telefon');
        $parol          = post('parol');
        $parol2         = post('parol_takror');
        $ref_kod        = strtoupper(preg_replace('/[^A-Z0-9]/i', '', post('referal_kod')));
        $telefon        = telefon_tozala($ma['telefon']);

        if (mb_strlen(trim($ma['ism'])) < 2) {
            $xato = t('kerakli_maydon');
        } elseif (!$telefon) {
            $xato = t('telefon_format');
        } elseif (mb_strlen($parol) < 6) {
            $xato = t('parol_qisqa');
        } elseif ($parol !== $parol2) {
            $xato = t('parollar_mos_emas');
        } elseif (db_qiymat('SELECT 1 FROM foydalanuvchilar WHERE telefon = ?', [$telefon])) {
            $xato = t('telefon_band');
        } else {
            $referer_id = null;
            if ($ref_kod) {
                $referer_id = db_qiymat(
                    'SELECT id FROM foydalanuvchilar WHERE referal_kod = ? AND holat = "faol"',
                    [$ref_kod]
                ) ?: null;
            }
            $kod  = referal_kod_unikal();
            $hash = password_hash($parol, PASSWORD_BCRYPT, ['cost' => 12]);
            $yid  = db_bajar(
                'INSERT INTO foydalanuvchilar (ism, familiya, telefon, parol_hash, referal_kod, referal_orqali)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [trim($ma['ism']), trim($ma['familiya']) ?: null, $telefon, $hash, $kod, $referer_id]
            );
            if ($referer_id && $yid) {
                db_bajar(
                    'INSERT INTO referallar (referer_id, referal_id, bonus_summa) VALUES (?, ?, ?)',
                    [$referer_id, $yid, (float) sozlama('referal_bonus', 5000)]
                );
            }
            tizimga_kirgan($yid);
            kirish_qayd($telefon, true);
            flash_qoy('muvaffaqiyat', t('royxat_muvaffaqiyat'));
            yonaltir(SAYT_URL . '/dashboard');
        }
    }
}

$sahifa_sarlavha = t('royxatdan_otish');
$body_class      = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding: 2rem 1.25rem;">
    <div style="width:100%; max-width: 30rem;">

        <!-- Logo -->
        <div style="text-align:center; margin-bottom: 2rem;">
            <a href="<?= e(SAYT_URL) ?>"
               style="display:inline-flex; flex-direction:column; align-items:center; gap:.5rem;
                      text-decoration:none;">
                <span style="display:flex; align-items:center; justify-content:center;
                             width:56px; height:56px; border:1px solid #000;
                             font-family:Georgia,serif; font-weight:700; font-size:1.5rem;">V</span>
                <span style="font-family:Georgia,serif; font-weight:700;
                             font-size:1.25rem; color:#000;">
                    <?= e(SAYT_NOMI) ?>
                </span>
            </a>
        </div>

        <div class="b-card" style="padding: 2rem;">
            <h1 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.75rem; margin-bottom: .35rem;">
                <?= e(t('royxatdan_otish')) ?>
            </h1>
            <p style="font-size:.9rem; color:#666; margin-bottom: 1.5rem;">
                Bepul akkaunt yarating va boshlang
            </p>

            <?php if ($xato): ?>
            <div style="padding: .75rem 1rem; border: 1px solid #000;
                        background: #000; color: #fff; font-size:.85rem;
                        margin-bottom: 1.25rem;">
                <?= e($xato) ?>
            </div>
            <?php endif; ?>

            <form method="POST"
                  x-data="{
                    loading: false, showPass: false, parol: '',
                    strength() { let s=0,p=this.parol; if(!p)return 0;
                        if(p.length>=6)s++; if(p.length>=10)s++;
                        if(/[A-Z]/.test(p))s++; if(/[0-9]/.test(p))s++;
                        if(/[^A-Za-z0-9]/.test(p))s++; return s; },
                    label() { return ['','Juda zaif','Zaif','O\'rtacha','Kuchli','Juda kuchli'][this.strength()]||''; }
                  }"
                  @submit="loading = true">
                <?= csrf_input() ?>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:.75rem; margin-bottom: 1rem;">
                    <div>
                        <label class="field-label"><?= e(t('ism')) ?> *</label>
                        <input name="ism" required value="<?= e($ma['ism']) ?>"
                               class="field" autocomplete="given-name"
                               placeholder="Akbar">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('familiya')) ?></label>
                        <input name="familiya" value="<?= e($ma['familiya']) ?>"
                               class="field" autocomplete="family-name"
                               placeholder="Yusupov">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="field-label"><?= e(t('telefon')) ?> *</label>
                    <input name="telefon" type="tel" required
                           value="<?= e($ma['telefon']) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="field-label"><?= e(t('parol')) ?> *</label>
                    <div style="position:relative;">
                        <input :type="showPass ? 'text' : 'password'"
                               name="parol" x-model="parol" required minlength="6"
                               placeholder="••••••••"
                               class="field" style="padding-right:5rem;"
                               autocomplete="new-password">
                        <button type="button"
                                @click="showPass = !showPass"
                                style="position:absolute; right:.5rem; top:50%;
                                       transform:translateY(-50%);
                                       background:none; border:none; padding:.4rem;
                                       cursor:pointer; font-size:.78rem;
                                       color:#666; text-decoration:underline;
                                       text-underline-offset:3px;">
                            <span x-text="showPass ? 'Yashirish' : 'Ko\'rsatish'">Ko'rsatish</span>
                        </button>
                    </div>
                    <!-- Parol kuchi -->
                    <div x-show="parol.length > 0" x-cloak style="margin-top:.5rem;">
                        <div style="display:flex; gap:.2rem;">
                            <template x-for="i in 5" :key="i">
                                <div style="flex:1; height:3px;"
                                     :style="i <= strength()
                                         ? 'background:#000;'
                                         : 'background:#E5E5E5;'"></div>
                            </template>
                        </div>
                        <p style="font-size:.72rem; color:#666; margin-top:.3rem;"
                           x-text="label()"></p>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label class="field-label"><?= e(t('parol_takror')) ?> *</label>
                    <input type="password" name="parol_takror" required minlength="6"
                           class="field" autocomplete="new-password"
                           placeholder="••••••••">
                </div>

                <!-- Referal -->
                <?php if ($ref_kod): ?>
                <div style="padding:.65rem .85rem; border:1px solid #000;
                            background: #F5F5F5; font-size:.85rem; margin-bottom: 1.25rem;">
                    Referal kod: <strong style="font-family:monospace;"><?= e($ref_kod) ?></strong>
                </div>
                <input type="hidden" name="referal_kod" value="<?= e($ref_kod) ?>">
                <?php else: ?>
                <details style="margin-bottom: 1.25rem;">
                    <summary style="font-size:.82rem; color:#666; cursor:pointer;
                                    user-select:none; text-decoration:underline;
                                    text-underline-offset:3px;">
                        Referal kodingiz bormi?
                    </summary>
                    <input name="referal_kod" placeholder="ABCD1234"
                           class="field" maxlength="10"
                           style="margin-top:.5rem; text-transform:uppercase;
                                  letter-spacing:.1em; font-family:monospace;">
                </details>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-lg"
                        style="width:100%;"
                        :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('royxatdan_otish')) ?> →'">
                        <?= e(t('royxatdan_otish')) ?> →
                    </span>
                </button>
            </form>

            <div style="border-top: 1px solid #E5E5E5; margin: 1.5rem 0; padding-top: 1rem;
                        text-align: center;">
                <p style="font-size:.875rem; color:#666;">
                    Akkauntingiz bormi?
                    <a href="<?= e(SAYT_URL) ?>/login"
                       style="color:#000; font-weight:500; text-decoration:underline;
                              text-underline-offset:3px; margin-left:.25rem;">
                        <?= e(t('kirish')) ?>
                    </a>
                </p>
            </div>
        </div>

        <p style="text-align:center; margin-top: 1rem;">
            <a href="<?= e(SAYT_URL) ?>"
               style="font-size:.78rem; color:#666; text-decoration:none;">
                ← <?= e(t('bosh_sahifa')) ?>
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
