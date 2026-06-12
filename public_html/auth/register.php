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
            $bor = db_qiymat('SELECT id FROM foydalanuvchilar WHERE telefon = ?', [$telefon]);
            if ($bor) {
                $xato = t('telefon_band');
            } else {
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

        <div class="flex justify-end gap-2 mb-6">
            <button type="button" onclick="vpToggleTheme()"
                    class="w-10 h-10 rounded-xl border border-app bg-glass hover:bg-surface transition flex items-center justify-center text-app">
                <svg class="w-4 h-4 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="w-4 h-4 theme-icon-dark"  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>
            <?php $joriy_til = $_SESSION['til'] ?? 'uz_latn'; ?>
            <div class="flex items-center text-xs border border-app bg-glass rounded-xl p-1">
                <a href="?til=uz_latn" class="px-2.5 py-1.5 rounded-lg <?= $joriy_til === 'uz_latn' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_latn' ? 'style="background: var(--gradient-primary);"' : '' ?>>Lat</a>
                <a href="?til=uz_cyrl" class="px-2.5 py-1.5 rounded-lg <?= $joriy_til === 'uz_cyrl' ? 'text-white font-semibold' : 'text-app-2' ?>"
                   <?= $joriy_til === 'uz_cyrl' ? 'style="background: var(--gradient-primary);"' : '' ?>>Кир</a>
            </div>
        </div>

        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-block">
                <?= logo_html('lg', false) ?>
            </a>
        </div>

        <div class="glass-card-premium p-8">
            <h1 class="text-2xl mb-2 text-app"><?= e(t('royxatdan_otish')) ?></h1>
            <p class="text-app-2 mb-6"><?= e(t('hero_tugma_boshla')) ?></p>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl border text-sm animate-shake flex items-start gap-2"
                     style="background: var(--error-soft); border-color: color-mix(in srgb, var(--error) 30%, transparent); color: var(--error);">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
                    <span><?= e($xato) ?></span>
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
                    <div class="p-3.5 rounded-xl border text-sm flex items-center gap-2"
                         style="background: var(--accent-soft); border-color: color-mix(in srgb, var(--accent) 30%, transparent); color: var(--accent);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9V6h2v3h3v2h-3v3H9v-3H6V9h3z"/></svg>
                        <?= e(t('referal')) ?>: <strong><?= e($referal_kod) ?></strong>
                    </div>
                    <input type="hidden" name="referal_kod" value="<?= e($referal_kod) ?>">
                <?php else: ?>
                    <details class="text-sm">
                        <summary class="text-app-2 cursor-pointer hover:text-app"><?= e(t('referal')) ?> (?)</summary>
                        <input name="referal_kod" placeholder="ABCD1234"
                               value="<?= e($referal_kod) ?>"
                               class="field mt-2 uppercase">
                    </details>
                <?php endif; ?>

                <button type="submit" class="btn-primary w-full text-base py-3.5" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('royxatdan_otish')) ?>'"><?= e(t('royxatdan_otish')) ?></span>
                </button>
            </form>

            <p class="text-center text-app-2 mt-6 text-sm">
                <?= e(t('kirish')) ?>?
                <a href="<?= e(SAYT_URL) ?>/login" class="text-accent hover:opacity-80 font-medium ml-1"><?= e(t('kirish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-app-2">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-app transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
