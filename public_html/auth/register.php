<?php
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
    } elseif (!honeypot_tekshir()) {
        $xato = t('csrf_xato');
        sleep(1);
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
        $parol_test = parol_murakkabmi($parol);

        if (!$malumot['ism']) {
            $xato = t('kerakli_maydon');
        } elseif (!$telefon) {
            $xato = t('telefon_format');
        } elseif (!$parol_test['ok']) {
            $xato = $parol_test['xato'];
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

                db()->beginTransaction();
                try {
                    $yangi_id = db_bajar(
                        'INSERT INTO foydalanuvchilar
                         (ism, familiya, telefon, parol_hash, referal_kod, referal_orqali, til)
                         VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $malumot['ism'], $malumot['familiya'], $telefon,
                            $hash, $yangi_kod, $referer_id, $_SESSION['til'] ?? 'uz_latn'
                        ]
                    );

                    if ($referer_id) {
                        db_bajar(
                            'INSERT INTO referallar (referer_id, referal_id, bonus_summa)
                             VALUES (?, ?, ?)',
                            [$referer_id, $yangi_id, (float) sozlama('referal_bonus', 5000)]
                        );
                    }
                    db()->commit();
                } catch (Exception $exc) {
                    db()->rollBack();
                    $xato = 'Xato yuz berdi';
                    $yangi_id = 0;
                }

                if ($yangi_id) {
                    tizimga_kirgan($yangi_id);
                    kirish_qayd($telefon, true);

                    flash_qoy('muvaffaqiyat', t('royxat_muvaffaqiyat'));
                    yonaltir(SAYT_URL . '/dashboard');
                }
            }
        }
    }
}

try {
    $slaydlar = db_barcha("SELECT * FROM login_slaydlar WHERE holat = 'faol' ORDER BY tartib, id");
} catch (Throwable $e) {
    $slaydlar = [];
}
if (empty($slaydlar)) {
    $slaydlar = [
        ['sarlavha' => t('hero_sarlavha') . ' ' . t('hero_aksent'), 'matn' => t('hero_tavsif'), 'rasm' => ''],
    ];
}

$logo_yoli = sozlama('logo_yoli', '');
$logo_url = $logo_yoli && is_file(UPLOAD_PATH . '/' . $logo_yoli)
    ? SAYT_URL . '/uploads/' . $logo_yoli
    : SAYT_URL . '/assets/img/logo-mark.svg';
$chap_sarlavha = sozlama('login_chap_sarlavha', t('sayt_nomi'));

$sahifa_sarlavha = t('royxatdan_otish');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen grid lg:grid-cols-2">

    <div class="hidden lg:flex relative overflow-hidden">
        <div class="absolute inset-0 grad-bg opacity-95"></div>
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.3) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.3) 0%, transparent 50%);"></div>

        <div class="relative z-10 flex flex-col justify-between p-10 xl:p-16 w-full text-white">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-3 group w-fit">
                <img src="<?= e($logo_url) ?>" alt="" class="w-12 h-12 rounded-2xl bg-white/10 p-1 backdrop-blur group-hover:scale-110 transition">
                <span class="font-display font-extrabold text-xl"><?= e($chap_sarlavha) ?></span>
            </a>

            <div x-data="{slayd: 0, jami: <?= count($slaydlar) ?>, init() { setInterval(() => { this.slayd = (this.slayd + 1) % this.jami; }, 5000); }}" class="my-10">
                <div class="text-sm font-medium uppercase tracking-widest opacity-80 mb-4">
                    <?= e(t('hero_belgi')) ?>
                </div>
                <div class="relative min-h-[200px]">
                    <?php foreach ($slaydlar as $i => $s): ?>
                        <div x-show="slayd === <?= $i ?>" x-transition:enter="transition-all duration-700"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute inset-0">
                            <h2 class="text-3xl xl:text-5xl font-display font-extrabold mb-4 leading-tight max-w-lg">
                                <?= e($s['sarlavha']) ?>
                            </h2>
                            <p class="text-lg opacity-90 max-w-md leading-relaxed">
                                <?= e($s['matn']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-2 mt-10">
                    <?php foreach ($slaydlar as $i => $s): ?>
                        <button type="button"
                                :class="slayd === <?= $i ?> ? 'w-10 bg-white' : 'w-2 bg-white/40'"
                                class="h-2 rounded-full transition-all" aria-label="Slayd"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm opacity-80">
                <div>
                    <div class="font-bold mb-1">📞 <?= e(sozlama('aloqa_telefon')) ?></div>
                    <div class="text-xs"><?= e(sozlama('aloqa_email')) ?></div>
                </div>
                <div class="text-right text-xs">© <?= date('Y') ?></div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-center px-4 py-10 sm:px-8 relative">
        <a href="<?= e(SAYT_URL) ?>" class="absolute top-6 left-6 lg:hidden flex items-center gap-2 text-muted hover:text-text">
            <img src="<?= e($logo_url) ?>" alt="" class="w-9 h-9 rounded-xl">
            <span class="font-display font-extrabold">VatanParvar</span>
        </a>

        <button onclick="window.vpTheme.toggle()" aria-label="Mavzu o'zgartirish"
                class="absolute top-6 right-6 w-10 h-10 rounded-xl flex items-center justify-center hover:bg-white/5 transition text-muted hover:text-text">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
        </button>

        <div class="w-full max-w-md fade-up mt-10 lg:mt-0">
            <div class="mb-6">
                <h1 class="text-3xl font-display font-extrabold mb-2"><?= e(t('royxatdan_otish')) ?></h1>
                <p class="text-muted text-sm"><?= e(t('hero_tugma_boshla')) ?></p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>
                <?= honeypot_input() ?>

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
                    <label class="field-label" for="parol"><?= e(t('parol')) ?> * <span class="text-xs text-muted">(8+ harf+raqam)</span></label>
                    <input id="parol" name="parol" type="password" required minlength="8"
                           placeholder="••••••••"
                           class="field" autocomplete="new-password">
                </div>

                <div>
                    <label class="field-label" for="parol_takror"><?= e(t('parol_takror')) ?> *</label>
                    <input id="parol_takror" name="parol_takror" type="password" required minlength="8"
                           class="field" autocomplete="new-password">
                </div>

                <?php if ($referal_kod): ?>
                    <div class="p-3 rounded-xl bg-violet/10 border border-violet/30 text-violet text-sm flex items-center gap-2">
                        🎁 <?= e(t('referal')) ?>: <strong><?= e($referal_kod) ?></strong>
                    </div>
                    <input type="hidden" name="referal_kod" value="<?= e($referal_kod) ?>">
                <?php else: ?>
                    <details class="text-sm">
                        <summary class="text-muted cursor-pointer hover:text-text"><?= e(t('referal')) ?> (?)</summary>
                        <input name="referal_kod" placeholder="ABCD1234" class="field mt-2 uppercase">
                    </details>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-full text-base" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('royxatdan_otish')) ?>'"><?= e(t('royxatdan_otish')) ?></span>
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/10">
                <p class="text-muted text-sm">
                    <?= e(t('kirish')) ?>?
                    <a href="<?= e(SAYT_URL) ?>/login" class="grad-text font-semibold ml-1"><?= e(t('kirish')) ?></a>
                </p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
