<?php
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato = '';
$telefon_kiritildi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } elseif (!honeypot_tekshir()) {
        $xato = t('csrf_xato');
        sleep(1);
    } elseif (!rate_limit_tekshir()) {
        $xato = t('rate_limit');
    } else {
        $telefon = telefon_tozala(post('telefon'));
        $parol   = post('parol');
        $telefon_kiritildi = post('telefon');

        if (!$telefon) {
            $xato = t('telefon_format');
        } elseif (!$parol) {
            $xato = t('kerakli_maydon');
        } else {
            $f = db_qator(
                'SELECT * FROM foydalanuvchilar WHERE telefon = ? AND holat = "faol"',
                [$telefon]
            );
            if ($f && password_verify($parol, $f['parol_hash'])) {
                kirish_qayd($telefon, true);

                $tfa_yoq = !empty($f['tfa_yoq']) && !empty($f['telegram_id']);

                if ($tfa_yoq) {
                    try {
                        $oxirgi_otp = db_qator(
                            'SELECT * FROM tfa_otp WHERE foydalanuvchi_id = ?
                             ORDER BY id DESC LIMIT 1',
                            [$f['id']]
                        );
                        $eskidan = $oxirgi_otp && (time() - strtotime($oxirgi_otp['yaratilgan'])) > 60;

                        if (!$oxirgi_otp || $eskidan) {
                            $kod = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            $tugash = date('Y-m-d H:i:s', time() + 600);
                            db_bajar(
                                'INSERT INTO tfa_otp (foydalanuvchi_id, kod, ip, tugash) VALUES (?, ?, ?, ?)',
                                [$f['id'], password_hash($kod, PASSWORD_DEFAULT), ip_olish(), $tugash]
                            );
                            telegram_yubor_xom($f['telegram_id'],
                                "🔐 <b>Kirish kodi</b>\n\n<code>{$kod}</code>\n\nKod 10 daqiqa amal qiladi.\nIP: " . ip_olish());
                        }

                        $_SESSION['tfa_kutilmoqda'] = [
                            'foydalanuvchi_id' => (int) $f['id'],
                            'vaqt' => time(),
                        ];
                        yonaltir(SAYT_URL . '/auth/tfa.php');
                    } catch (Throwable $e) {
                        error_log('2FA xato: ' . $e->getMessage());
                        tizimga_kirgan($f['id']);
                        $manzil = in_array($f['rol'], ['admin', 'developer'], true) ? '/admin/' : '/dashboard';
                        flash_qoy('muvaffaqiyat', t('salom') . ', ' . $f['ism'] . '!');
                        yonaltir(SAYT_URL . $manzil);
                    }
                } else {
                    tizimga_kirgan($f['id']);
                    $manzil = in_array($f['rol'], ['admin', 'developer'], true)
                              ? '/admin/' : '/dashboard';
                    flash_qoy('muvaffaqiyat', t('salom') . ', ' . $f['ism'] . '!');
                    yonaltir(SAYT_URL . $manzil);
                }
            } else {
                kirish_qayd($telefon, false);
                $xato = t('kirish_xato');
            }
        }
    }
}

try {
    $slaydlar = db_barcha(
        "SELECT * FROM login_slaydlar WHERE holat = 'faol' ORDER BY tartib, id"
    );
} catch (Throwable $e) {
    $slaydlar = [];
}
if (empty($slaydlar)) {
    $slaydlar = [
        [
            'sarlavha' => t('hero_sarlavha') . ' ' . t('hero_aksent'),
            'matn' => t('hero_tavsif'),
            'rasm' => '',
        ],
        [
            'sarlavha' => t('xususiyat_1_sarlavha'),
            'matn' => t('xususiyat_1_tavsif'),
            'rasm' => '',
        ],
        [
            'sarlavha' => t('xususiyat_3_sarlavha'),
            'matn' => t('xususiyat_3_tavsif'),
            'rasm' => '',
        ],
    ];
}

$logo_yoli = sozlama('logo_yoli', '');
$logo_url = $logo_yoli && is_file(UPLOAD_PATH . '/' . $logo_yoli)
    ? SAYT_URL . '/uploads/' . $logo_yoli
    : SAYT_URL . '/assets/img/logo-mark.svg';

$chap_sarlavha = sozlama('login_chap_sarlavha', t('sayt_nomi'));
$chap_matn     = sozlama('login_chap_matn', t('sayt_shior'));

$sahifa_sarlavha = t('kirish');
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
                <span class="font-display font-extrabold text-xl">
                    <?= e($chap_sarlavha) ?>
                </span>
            </a>

            <div x-data="{
                slayd: 0,
                jami: <?= count($slaydlar) ?>,
                interval: null,
                init() {
                    this.interval = setInterval(() => { this.slayd = (this.slayd + 1) % this.jami; }, 5000);
                },
                kel(i) { this.slayd = i; clearInterval(this.interval); }
            }" class="my-10">

                <div class="text-sm font-medium uppercase tracking-widest opacity-80 mb-4">
                    <?= e(t('hero_belgi')) ?>
                </div>

                <div class="relative min-h-[200px]">
                    <?php foreach ($slaydlar as $i => $s): ?>
                        <div x-show="slayd === <?= $i ?>" x-transition:enter="transition-all duration-700"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute inset-0">
                            <?php if (!empty($s['rasm']) && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" alt=""
                                     class="w-full max-w-md h-48 object-cover rounded-2xl mb-6 shadow-2xl">
                            <?php endif; ?>
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
                        <button @click="kel(<?= $i ?>)" type="button"
                                :class="slayd === <?= $i ?> ? 'w-10 bg-white' : 'w-2 bg-white/40'"
                                class="h-2 rounded-full transition-all hover:bg-white/70" aria-label="Slayd"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm opacity-80">
                <div>
                    <div class="font-bold mb-1">📞 <?= e(sozlama('aloqa_telefon')) ?></div>
                    <div class="text-xs"><?= e(sozlama('aloqa_email')) ?></div>
                </div>
                <div class="text-right text-xs">
                    © <?= date('Y') ?>
                </div>
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

            <div class="mb-8">
                <h1 class="text-3xl font-display font-extrabold mb-2">
                    <?= e(t('kirish')) ?>
                </h1>
                <p class="text-muted">
                    Davom etish uchun akkauntingizga kiring
                </p>
            </div>

            <?php if ($xato): ?>
                <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm shake">
                    <?= e($xato) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" x-data="{loading:false}" @submit="loading=true">
                <?= csrf_input() ?>
                <?= honeypot_input() ?>

                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?></label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($telefon_kiritildi) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?></label>
                    <div x-data="{show:false}" class="relative">
                        <input id="parol" name="parol" required
                               :type="show ? 'text' : 'password'"
                               placeholder="••••••••"
                               class="field pr-12" autocomplete="current-password">
                        <button type="button" @click="show=!show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-text" aria-label="Parolni ko'rsatish">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 102.8 2.8M16.7 16.7A9.7 9.7 0 0112 18c-6 0-9.5-6-9.5-6a17 17 0 014.3-4.7M9.4 5.2A10 10 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-2.5 3.4"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <a href="<?= e(SAYT_URL) ?>/forgot-password" class="text-violet hover:text-pink transition">
                        <?= e(t('parolni_unutdim')) ?>
                    </a>
                    <div class="flex gap-1 text-xs">
                        <a href="?til=uz_latn" class="px-2 py-1 rounded transition <?= ($_SESSION['til'] ?? 'uz_latn') === 'uz_latn' ? 'grad-bg text-white' : 'hover:bg-white/5 text-muted' ?>">UZ</a>
                        <a href="?til=uz_cyrl" class="px-2 py-1 rounded transition <?= ($_SESSION['til'] ?? '') === 'uz_cyrl' ? 'grad-bg text-white' : 'hover:bg-white/5 text-muted' ?>">КР</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full text-base" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('kirish')) ?>'"><?= e(t('kirish')) ?></span>
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/10">
                <p class="text-muted text-sm">
                    <?= e(t('royxatdan_otish')) ?>?
                    <a href="<?= e(SAYT_URL) ?>/register" class="grad-text font-semibold ml-1"><?= e(t('royxatdan_otish')) ?></a>
                </p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
