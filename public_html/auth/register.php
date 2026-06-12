<?php
/**
 * VatanParvar Yaypan — Ro'yxatdan o'tish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
$malumot = ['ism' => '', 'familiya' => '', 'telefon' => ''];

// Referal kod (URL'dan)
$referal_kod = preg_replace('/[^A-Z0-9]/i', '', olish('ref'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
        $malumot['ism']      = post('ism');
        $malumot['familiya'] = post('familiya');
        $malumot['telefon']  = post('telefon');
        $parol               = post('parol');
        $parol_takror        = post('parol_takror');
        $referal_kod         = preg_replace('/[^A-Z0-9]/i', '', post('referal_kod'));

        $telefon = telefon_tozala($malumot['telefon']);

        if (!$malumot['ism']) {
            $xato = t('kerakli_maydon');
        } elseif (!$telefon) {
            $xato = t('telefon_format');
        } elseif (mb_strlen($parol) < 6) {
            $xato = t('parol_qisqa');
        } elseif ($parol !== $parol_takror) {
            $xato = t('parollar_mos_emas');
        } else {
            // Telefon band emasligini tekshirish
            $bor = db_qiymat('SELECT id FROM foydalanuvchilar WHERE telefon = ?', [$telefon]);
            if ($bor) {
                $xato = t('telefon_band');
            } else {
                // Referal beruvchini topish
                $referer_id = null;
                if ($referal_kod) {
                    $referer_id = db_qiymat(
                        'SELECT id FROM foydalanuvchilar WHERE referal_kod = ?',
                        [$referal_kod]
                    ) ?: null;
                }

                $yangi_kod = referal_kod_unikal();
                $hash = password_hash($parol, PASSWORD_BCRYPT);

                $yangi_id = db_bajar(
                    'INSERT INTO foydalanuvchilar
                     (ism, familiya, telefon, parol_hash, referal_kod, referal_orqali)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $malumot['ism'], $malumot['familiya'], $telefon,
                        $hash, $yangi_kod, $referer_id
                    ]
                );

                // Referal yozuvi
                if ($referer_id) {
                    db_bajar(
                        'INSERT INTO referallar (referer_id, referal_id, bonus_summa)
                         VALUES (?, ?, ?)',
                        [$referer_id, $yangi_id, (float) sozlama('referal_bonus', 5000)]
                    );
                }

                tizimga_kirgan($yangi_id);
                kirish_qayd($telefon, true);

                flash_qoy('muvaffaqiyat', t('royxat_muvaffaqiyat'));
                yonaltir(SAYT_URL . '/dashboard');
            }
        }
    }
}

$sahifa_sarlavha = t('royxatdan_otish');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-xl shadow-xl shadow-blue-500/30">V</span>
                <span class="font-display font-bold text-white text-2xl">VatanParvar</span>
            </a>
        </div>

        <div class="glass-card p-8">
            <h1 class="text-2xl mb-2 text-white"><?= e(t('royxatdan_otish')) ?></h1>
            <p class="text-brand-muted mb-6"><?= e(t('hero_tugma_boshla')) ?></p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm animate-shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="ism"><?= e(t('ism')) ?> *</label>
                        <input id="ism" name="ism" required
                               value="<?= e($malumot['ism']) ?>"
                               class="field" autocomplete="given-name">
                    </div>
                    <div>
                        <label class="field-label" for="familiya"><?= e(t('familiya')) ?></label>
                        <input id="familiya" name="familiya"
                               value="<?= e($malumot['familiya']) ?>"
                               class="field" autocomplete="family-name">
                    </div>
                </div>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?> *</label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($malumot['telefon']) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?> *</label>
                    <input id="parol" name="parol" type="password" required minlength="6"
                           placeholder="••••••••"
                           class="field" autocomplete="new-password">
                </div>

                <div>
                    <label class="field-label" for="parol_takror"><?= e(t('parol_takror')) ?> *</label>
                    <input id="parol_takror" name="parol_takror" type="password" required minlength="6"
                           class="field" autocomplete="new-password">
                </div>

                <?php if ($referal_kod): ?>
                    <div class="p-3 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-300 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9V6h2v3h3v2h-3v3H9v-3H6V9h3z"/></svg>
                        <?= e(t('referal')) ?>: <strong><?= e($referal_kod) ?></strong>
                    </div>
                    <input type="hidden" name="referal_kod" value="<?= e($referal_kod) ?>">
                <?php else: ?>
                    <details class="text-sm">
                        <summary class="text-brand-muted cursor-pointer hover:text-white"><?= e(t('referal')) ?> (?)</summary>
                        <input name="referal_kod" placeholder="ABCD1234"
                               value="<?= e($referal_kod) ?>"
                               class="field mt-2 uppercase">
                    </details>
                <?php endif; ?>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('royxatdan_otish')) ?>'"><?= e(t('royxatdan_otish')) ?></span>
                </button>
            </form>

            <p class="text-center text-brand-muted mt-6 text-sm">
                <?= e(t('kirish')) ?>?
                <a href="<?= e(SAYT_URL) ?>/login" class="text-blue-400 hover:text-blue-300 font-medium ml-1"><?= e(t('kirish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-brand-muted">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-white transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
