<?php
/**
 * VatanParvar Yaypan — Profil sozlamalari
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$xato = '';
$ok   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $harakat = post('harakat');

        /* ── Ma'lumot yangilash ── */
        if ($harakat === 'malumot') {
            $ism      = mb_substr(trim(post('ism')) ?: $f['ism'], 0, 100);
            $familiya = mb_substr(trim(post('familiya')), 0, 100) ?: null;
            $email    = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null;
            $til      = in_array(post('til'), TILLAR, true) ? post('til') : TIL_DEFAULT;

            $avatar = $f['avatar'];
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['avatar'], 'avatars', 400);
                if ($yangi) {
                    if ($avatar && is_file(UPLOAD_PATH . '/' . $avatar)) {
                        @unlink(UPLOAD_PATH . '/' . $avatar);
                    }
                    $avatar = $yangi;
                }
            }

            db_bajar(
                'UPDATE foydalanuvchilar
                 SET ism = ?, familiya = ?, email = ?, til = ?, avatar = ?
                 WHERE id = ?',
                [$ism, $familiya, $email, $til, $avatar, $f['id']]
            );

            $_SESSION['til'] = $til;
            $f  = joriy_foydalanuvchi(true); // keshni yangilash
            $ok = t('malumot_saqlandi');
        }

        /* ── Parol o'zgartirish ── */
        if ($harakat === 'parol') {
            $eski   = post('eski_parol');
            $yangi  = post('yangi_parol');
            $takror = post('parol_takror');

            if (!password_verify($eski, $f['parol_hash'])) {
                $xato = t('kirish_xato');
            } elseif (mb_strlen($yangi) < 6) {
                $xato = t('parol_qisqa');
            } elseif ($yangi !== $takror) {
                $xato = t('parollar_mos_emas');
            } else {
                db_bajar(
                    'UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?',
                    [password_hash($yangi, PASSWORD_BCRYPT, ['cost' => 12]), $f['id']]
                );
                $ok = t('malumot_saqlandi');
            }
        }
    }
}

// Telegram hash
if (empty($f['telegram_hash'])) {
    $hash = bin2hex(random_bytes(16));
    db_bajar('UPDATE foydalanuvchilar SET telegram_hash = ? WHERE id = ?', [$hash, $f['id']]);
    $f['telegram_hash'] = $hash;
}

$bot_username = sozlama('telegram_bot_username', '');
$obuna        = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$sahifa_sarlavha = t('profil_sozlamalar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 py-10 pb-20">

    <div class="mb-8 fade-up">
        <h1 class="text-3xl font-display font-black mb-1"><?= e(t('profil_sozlamalar')) ?></h1>
        <p class="text-white/45">Akkauntingiz ma'lumotlari va sozlamalarini boshqaring</p>
    </div>

    <!-- Xabarlar -->
    <?php if ($xato): ?>
    <div class="mb-5 flex items-center gap-2.5 p-3.5 rounded-xl
                bg-red-500/10 border border-red-500/25 text-red-300 text-sm animate-shake fade-up">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= e($xato) ?>
    </div>
    <?php endif; ?>
    <?php if ($ok): ?>
    <div class="mb-5 flex items-center gap-2.5 p-3.5 rounded-xl
                bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm fade-up">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= e($ok) ?>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">

        <!-- ── Chap: Profil kartasi ────────────────────────── -->
        <div class="lg:col-span-1 space-y-4">

            <!-- Avatar + Info -->
            <div class="glass-card p-6 fade-up text-center">
                <!-- Avatar -->
                <div class="relative inline-block mb-4">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-violet-600
                                flex items-center justify-center font-display font-black text-white text-3xl
                                overflow-hidden ring-4 ring-white/10 mx-auto">
                        <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                            <img src="<?= e(SAYT_URL . '/uploads/' . $f['avatar']) ?>"
                                 class="w-full h-full object-cover" alt="">
                        <?php else: ?>
                            <?= e(bosh_harflar($f)) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <h3 class="font-display font-bold text-xl">
                    <?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?>
                </h3>
                <p class="text-white/45 text-sm mt-1"><?= e($f['telefon']) ?></p>
                <?php if ($f['email']): ?>
                <p class="text-white/35 text-xs mt-0.5"><?= e($f['email']) ?></p>
                <?php endif; ?>

                <div class="flex items-center justify-center mt-3">
                    <span class="badge <?= match($f['rol']) {
                        'developer' => 'badge-purple',
                        'admin'     => 'badge-blue',
                        default     => 'badge-gray'
                    } ?> uppercase">
                        <?= e($f['rol']) ?>
                    </span>
                </div>

                <!-- Statistika -->
                <div class="mt-5 pt-5 border-t border-white/[0.07] space-y-2.5 text-sm text-left">
                    <div class="flex justify-between">
                        <span class="text-white/45">A'zo bo'lgan:</span>
                        <span class="font-medium"><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/45">Oxirgi kirish:</span>
                        <span><?= $f['oxirgi_kirish'] ? e(sana($f['oxirgi_kirish'], 'd.m.Y')) : '—' ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/45"><?= e(t('bonus_balans')) ?>:</span>
                        <span class="font-bold text-emerald-400"><?= e(pul($f['bonus_balans'])) ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/45">Telegram:</span>
                        <?php if ($f['telegram_id']): ?>
                        <span class="badge badge-green">Ulangan</span>
                        <?php else: ?>
                        <span class="badge badge-red">Ulanmagan</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Obuna -->
            <?php if ($obuna): ?>
            <div class="glass-card p-4 border-amber-500/25 bg-amber-500/[0.04] fade-up">
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="text-xl">⭐</span>
                    <div>
                        <p class="text-xs text-white/45 uppercase tracking-wide">Faol obuna</p>
                        <p class="font-semibold text-amber-400"><?= e($obuna['tarif_nomi']) ?></p>
                    </div>
                </div>
                <p class="text-xs text-white/40">
                    <?= e(sana($obuna['tugash'], 'd.m.Y')) ?> gacha
                </p>
            </div>
            <?php else: ?>
            <div class="glass-card p-4 fade-up">
                <p class="text-sm text-white/45 mb-3">Faol obuna yo'q</p>
                <a href="<?= e(SAYT_URL) ?>/tolov"
                   class="btn btn-primary w-full btn-sm">
                    💎 Tarif olish →
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── O'ng: Formalar ─────────────────────────────── -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Ma'lumot yangilash -->
            <form method="POST" enctype="multipart/form-data" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">

                <div class="flex items-center gap-2.5 mb-5">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/15 text-blue-400
                                flex items-center justify-center text-base">👤</div>
                    <h2 class="font-display font-bold text-lg"><?= e(t('shaxsiy_malumot')) ?></h2>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('ism')) ?> *</label>
                        <input name="ism" required value="<?= e($f['ism']) ?>"
                               class="field" autocomplete="given-name">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('familiya')) ?></label>
                        <input name="familiya" value="<?= e($f['familiya'] ?? '') ?>"
                               class="field" autocomplete="family-name">
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input name="email" type="email"
                               value="<?= e($f['email'] ?? '') ?>"
                               class="field" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('til_tanlash')) ?></label>
                        <select name="til" class="field">
                            <option value="uz_latn" <?= ($f['til']??'uz_latn')==='uz_latn' ? 'selected':'' ?>>
                                O'zbek (Lotin)
                            </option>
                            <option value="uz_cyrl" <?= ($f['til']??'')==='uz_cyrl' ? 'selected':'' ?>>
                                Ўзбек (Кирилл)
                            </option>
                            <option value="ru" <?= ($f['til']??'')==='ru' ? 'selected':'' ?>>
                                Русский
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label">
                            <?= e(t('avatar_yuklash')) ?>
                            <span class="text-white/30">(JPEG/PNG/WebP, maks 5 MB)</span>
                        </label>
                        <input name="avatar" type="file" accept="image/*" class="field">
                        <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>"
                                 class="w-12 h-12 rounded-full object-cover"
                                 alt="">
                            <span class="text-xs text-white/40">Yangi rasm yuklasangiz, eski almashinadi</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-5">
                    <?= e(t('saqlash')) ?>
                </button>
            </form>

            <!-- Telegram ulash -->
            <div class="glass-card p-6 fade-up">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-sky-500/15 text-sky-400
                                flex items-center justify-center text-base">📱</div>
                    <h2 class="font-display font-bold text-lg">Telegram</h2>
                </div>

                <?php if ($f['telegram_id']): ?>
                <div class="flex items-center gap-2.5 p-3.5 rounded-xl
                            bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Telegram muvaffaqiyatli ulangan
                    <span class="text-white/40 text-xs ml-1">(ID: <?= (int)$f['telegram_id'] ?>)</span>
                </div>
                <?php elseif ($bot_username): ?>
                <p class="text-sm text-white/50 mb-4 leading-relaxed">
                    Bildirishnomalar olish va parolni tiklash uchun Telegram'ni ulang.
                </p>
                <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                   target="_blank" rel="noopener"
                   class="btn btn-ghost inline-flex items-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/>
                    </svg>
                    Telegramga ulanish
                </a>
                <?php else: ?>
                <p class="text-sm text-white/40">Telegram bot hali sozlanmagan.</p>
                <?php endif; ?>
            </div>

            <!-- Parol o'zgartirish -->
            <form method="POST" class="glass-card p-6 fade-up"
                  x-data="{ showPass: false }">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="parol">

                <div class="flex items-center gap-2.5 mb-5">
                    <div class="w-8 h-8 rounded-lg bg-violet-500/15 text-violet-400
                                flex items-center justify-center text-base">🔑</div>
                    <h2 class="font-display font-bold text-lg"><?= e(t('parolni_ozgartirish')) ?></h2>
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('eski_parol')) ?></label>
                        <input :type="showPass ? 'text' : 'password'"
                               name="eski_parol" required
                               class="field" autocomplete="current-password"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('yangi_parol')) ?></label>
                        <input :type="showPass ? 'text' : 'password'"
                               name="yangi_parol" required minlength="6"
                               class="field" autocomplete="new-password"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('parol_takror')) ?></label>
                        <input :type="showPass ? 'text' : 'password'"
                               name="parol_takror" required minlength="6"
                               class="field" autocomplete="new-password"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-5">
                    <button type="submit" class="btn btn-primary">
                        <?= e(t('saqlash')) ?>
                    </button>
                    <label class="flex items-center gap-2 text-sm text-white/45
                                  cursor-pointer select-none">
                        <input type="checkbox" @change="showPass = $event.target.checked"
                               class="rounded">
                        Ko'rsatish
                    </label>
                </div>
            </form>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
