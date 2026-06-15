<?php
/**
 * VatanParvar Yaypan — Ro'yxatdan o'tish sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

$xato   = '';
$ma     = ['ism' => '', 'familiya' => '', 'telefon' => ''];
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
            $kod   = referal_kod_unikal();
            $hash  = password_hash($parol, PASSWORD_BCRYPT, ['cost' => 12]);
            $yid   = db_bajar(
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

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-lg fade-up">

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600
                             flex items-center justify-center font-display font-black text-white text-2xl
                             shadow-xl shadow-blue-500/30">A</span>
                <span class="font-display font-bold text-white text-2xl">VatanParvar <span class="text-blue-400">Yaypan</span></span>
            </a>
            <p class="text-white/40 text-sm mt-2"><?= e(t('sayt_shior')) ?></p>
        </div>

        <div class="glass-card p-7 md:p-8">
            <h1 class="text-2xl font-display font-black mb-1"><?= e(t('royxatdan_otish')) ?></h1>
            <p class="text-white/45 text-sm mb-6">Bepul akkaunt yarating va boshlang 🚀</p>

            <!-- Xato -->
            <?php if ($xato): ?>
            <div class="flex items-center gap-2.5 p-3.5 rounded-xl
                        bg-red-500/10 border border-red-500/25 text-red-300 text-sm mb-5 animate-shake">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <?= e($xato) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4"
                  x-data="{
                    loading: false,
                    showPass: false,
                    parol: '',
                    strength() {
                        let s=0, p=this.parol;
                        if(!p) return 0;
                        if(p.length>=6)  s++;
                        if(p.length>=10) s++;
                        if(/[A-Z]/.test(p)) s++;
                        if(/[0-9]/.test(p)) s++;
                        if(/[^A-Za-z0-9]/.test(p)) s++;
                        return s;
                    },
                    strengthLabel() {
                        return ['','Juda zaif','Zaif','O\'rtacha','Kuchli','Juda kuchli'][this.strength()]||'';
                    },
                    strengthColor() {
                        return ['','bg-red-500','bg-orange-500','bg-amber-500','bg-blue-500','bg-emerald-500'][this.strength()]||'';
                    }
                  }"
                  @submit="loading = true">
                <?= csrf_input() ?>

                <!-- Ism / Familiya -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="ism"><?= e(t('ism')) ?> *</label>
                        <input id="ism" name="ism" required
                               value="<?= e($ma['ism']) ?>"
                               class="field" autocomplete="given-name" placeholder="Akbar">
                    </div>
                    <div>
                        <label class="field-label" for="familiya"><?= e(t('familiya')) ?></label>
                        <input id="familiya" name="familiya"
                               value="<?= e($ma['familiya']) ?>"
                               class="field" autocomplete="family-name" placeholder="Yusupov">
                    </div>
                </div>

                <!-- Telefon -->
                <div>
                    <label class="field-label" for="telefon"><?= e(t('telefon')) ?> *</label>
                    <input id="telefon" name="telefon" type="tel" required
                           value="<?= e($ma['telefon']) ?>"
                           placeholder="+998 90 123 45 67"
                           class="field" autocomplete="tel">
                </div>

                <!-- Parol -->
                <div>
                    <label class="field-label" for="parol"><?= e(t('parol')) ?> *</label>
                    <div class="relative">
                        <input id="parol" name="parol"
                               :type="showPass ? 'text' : 'password'"
                               x-model="parol"
                               required minlength="6" placeholder="••••••••"
                               class="field pr-11" autocomplete="new-password">
                        <button type="button"
                                @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/35 hover:text-white/70 transition p-1">
                            <svg x-show="!showPass" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Parol kuchi ko'rsatkichi -->
                    <div x-show="parol.length > 0" x-cloak class="mt-2">
                        <div class="flex gap-1">
                            <template x-for="i in 5" :key="i">
                                <div class="flex-1 h-1.5 rounded-full transition-all duration-300"
                                     :class="i <= strength() ? strengthColor() : 'bg-white/10'"></div>
                            </template>
                        </div>
                        <p class="text-xs text-white/40 mt-1" x-text="strengthLabel()"></p>
                    </div>
                </div>

                <!-- Parol takror -->
                <div>
                    <label class="field-label" for="parol2"><?= e(t('parol_takror')) ?> *</label>
                    <input id="parol2" name="parol_takror" type="password" required minlength="6"
                           class="field" autocomplete="new-password" placeholder="••••••••">
                </div>

                <!-- Referal kod -->
                <?php if ($ref_kod): ?>
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-blue-500/10 border border-blue-500/25 text-blue-300 text-sm">
                    🎁 Referal kod: <strong class="font-mono tracking-widest"><?= e($ref_kod) ?></strong>
                </div>
                <input type="hidden" name="referal_kod" value="<?= e($ref_kod) ?>">
                <?php else: ?>
                <details class="text-sm group">
                    <summary class="text-white/35 cursor-pointer hover:text-white/60 transition select-none flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        🎁 Referal kodingiz bormi?
                    </summary>
                    <input name="referal_kod" placeholder="ABCD1234"
                           class="field mt-2 uppercase tracking-widest font-mono"
                           maxlength="10">
                </details>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-full btn-lg" :disabled="loading">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading?'<?= e(t('yuklanmoqda')) ?>':'🚀 <?= e(t('royxatdan_otish')) ?>'"><?= e(t('royxatdan_otish')) ?></span>
                </button>
            </form>

            <p class="text-center text-sm text-white/40 mt-5">
                Akkauntingiz bormi?
                <a href="<?= e(SAYT_URL) ?>/login"
                   class="text-blue-400 hover:text-blue-300 font-semibold ml-1 transition"><?= e(t('kirish')) ?> →</a>
            </p>
        </div>

        <p class="text-center mt-5 text-xs text-white/25">
            <a href="<?= e(SAYT_URL) ?>" class="hover:text-white/50 transition">← <?= e(t('bosh_sahifa')) ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
