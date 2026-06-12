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

<div class="min-h-screen flex items-center justify-center px-4 py-10 relative">

    <!-- Floating dekorativ shakllar -->
    <div class="absolute top-20 right-10 w-32 h-32 rounded-full bg-gradient-to-br from-emerald-200 to-sky-200 opacity-40 blur-2xl floating" aria-hidden="true"></div>
    <div class="absolute bottom-20 left-10 w-40 h-40 rounded-full bg-gradient-to-br from-sky-200 to-violet-200 opacity-40 blur-2xl floating" style="animation-delay:1.5s" aria-hidden="true"></div>

    <div class="w-full max-w-md scale-in relative">
        <div class="text-center mb-8 fade-up">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5 group">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white text-2xl shadow-glow group-hover:scale-110 transition-all duration-300">V</span>
                <span class="font-display font-bold text-brand-text text-2xl">VatanParvar</span>
            </a>
        </div>

        <div class="glass-card p-8 fade-up stagger-1 shadow-medium">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-brand-text mb-2"><?= e(t('royxatdan_otish')) ?> ✨</h1>
                <p class="text-brand-muted">Bepul akkaunt yaratib, testlarni boshlang</p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-start gap-2 animate-shake">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                    <span><?= e($xato) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="ism"><?= e(t('ism')) ?> <span class="text-rose-500">*</span></label>
                        <input id="ism" name="ism" required
                               value="<?= e($malumot['ism']) ?>"
                               class="field" autocomplete="given-name" placeholder="Ali">
                    </div>
                    <div>
                        <label class="field-label" for="familiya"><?= e(t('familiya')) ?></label>
                        <input id="familiya" name="familiya"
                               value="<?= e($malumot['familiya']) ?>"
                               class="field" autocomplete="family-name" placeholder="Aliyev">
                    </div>
                </div>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?> <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <input id="telefon" name="telefon" type="tel" required
                               value="<?= e($malumot['telefon']) ?>"
                               placeholder="+998 90 123 45 67"
                               class="field pl-11" autocomplete="tel">
                    </div>
                </div>

                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?> <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input id="parol" name="parol" type="password" required minlength="6"
                               placeholder="Kamida 6 belgi"
                               class="field pl-11" autocomplete="new-password">
                    </div>
                </div>

                <div>
                    <label class="field-label" for="parol_takror"><?= e(t('parol_takror')) ?> <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <input id="parol_takror" name="parol_takror" type="password" required minlength="6"
                               placeholder="Yana parolni kiriting"
                               class="field pl-11" autocomplete="new-password">
                    </div>
                </div>

                <?php if ($referal_kod): ?>
                    <div class="p-3.5 rounded-xl bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 text-sky-800 text-sm flex items-center gap-2.5">
                        <span class="text-2xl">🎁</span>
                        <div>
                            <div class="font-semibold">Referal kod qo'llanildi</div>
                            <div class="text-xs text-sky-700">Kod: <strong><?= e($referal_kod) ?></strong></div>
                        </div>
                    </div>
                    <input type="hidden" name="referal_kod" value="<?= e($referal_kod) ?>">
                <?php else: ?>
                    <details class="text-sm group">
                        <summary class="text-brand-muted cursor-pointer hover:text-sky-600 transition list-none flex items-center gap-1.5">
                            <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Referal kodingiz bormi?
                        </summary>
                        <input name="referal_kod" placeholder="ABCD1234"
                               value="<?= e($referal_kod) ?>"
                               class="field mt-2 uppercase font-mono">
                    </details>
                <?php endif; ?>

                <button type="submit" class="btn-primary w-full py-3.5 mt-2" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('royxatdan_otish')) ?>'"><?= e(t('royxatdan_otish')) ?></span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </form>

            <p class="text-center text-brand-muted mt-6 text-sm">
                Akkauntingiz bormi?
                <a href="<?= e(SAYT_URL) ?>/login" class="text-sky-600 hover:text-sky-700 font-semibold ml-1 transition link-anim"><?= e(t('kirish')) ?></a>
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-brand-muted fade-up stagger-3">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-sky-600 transition inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <?= e(t('bosh_sahifa')) ?>
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
