<?php
/**
 * VatanParvar Yaypan — Profil sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$xato = '';
$muvaffaqiyat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $harakat = post('harakat');

        // ----- Shaxsiy ma'lumotlarni yangilash -----
        if ($harakat === 'malumot') {
            $ism = post('ism') ?: $f['ism'];
            $familiya = post('familiya');
            $email = post('email');
            $til = post('til');

            // Avatar
            $avatar = $f['avatar'];
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['avatar'], 'avatars', 400);
                if ($yangi) {
                    $avatar = $yangi;
                }
            }

            db_bajar(
                'UPDATE foydalanuvchilar
                 SET ism = ?, familiya = ?, email = ?, til = ?, avatar = ?
                 WHERE id = ?',
                [$ism, $familiya, $email ?: null, in_array($til, ['uz_latn','uz_cyrl'], true) ? $til : 'uz_latn', $avatar, $f['id']]
            );
            $_SESSION['til'] = $til;
            $muvaffaqiyat = t('malumot_saqlandi');
            $f = joriy_foydalanuvchi(); // yangilangan ma'lumot
        }

        // ----- Parolni o'zgartirish -----
        if ($harakat === 'parol') {
            $eski = post('eski_parol');
            $yangi = post('yangi_parol');
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
                    [password_hash($yangi, PASSWORD_BCRYPT), $f['id']]
                );
                $muvaffaqiyat = t('malumot_saqlandi');
            }
        }
    }
}

// Telegram bog'lash hash
if (empty($f['telegram_hash'])) {
    $hash = bin2hex(random_bytes(16));
    db_bajar('UPDATE foydalanuvchilar SET telegram_hash = ? WHERE id = ?', [$hash, $f['id']]);
    $f['telegram_hash'] = $hash;
}

$bot_username = sozlama('telegram_bot_username', 'vatanparvaryaypan_bot');

$sahifa_sarlavha = t('profil_sozlamalar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl mb-6"><?= e(t('profil_sozlamalar')) ?></h1>

    <?php if ($xato): ?>
        <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm"><?= e($xato) ?></div>
    <?php endif; ?>
    <?php if ($muvaffaqiyat): ?>
        <div class="mb-5 p-3 rounded-lg bg-green-500/10 border border-green-500/30 text-green-300 text-sm"><?= e($muvaffaqiyat) ?></div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Chap: profil kartasi -->
        <div class="glass-card p-6 fade-up text-center">
            <div class="w-28 h-28 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-4xl overflow-hidden">
                <?php if ($f['avatar'] && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </div>
            <h3 class="text-xl font-display"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></h3>
            <p class="text-brand-muted text-sm"><?= e($f['telefon']) ?></p>
            <span class="inline-block mt-3 px-3 py-1 rounded-full text-xs
                <?= $f['rol'] === 'developer' ? 'bg-purple-500/20 text-purple-400' :
                   ($f['rol'] === 'admin' ? 'bg-blue-500/20 text-blue-400' : 'bg-white/10 text-white/60') ?>">
                <?= e(strtoupper($f['rol'])) ?>
            </span>

            <hr class="my-5 border-white/10">

            <div class="text-left space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-brand-muted">A'zo bo'lgan:</span>
                    <span><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted"><?= e(t('bonus_balans')) ?>:</span>
                    <span class="font-bold text-green-400"><?= e(pul($f['bonus_balans'])) ?></span>
                </div>
            </div>
        </div>

        <!-- O'rta + o'ng: formalar -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ma'lumot formasi -->
            <form method="POST" enctype="multipart/form-data" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">

                <h2 class="text-xl font-display mb-5"><?= e(t('shaxsiy_malumot')) ?></h2>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('ism')) ?> *</label>
                        <input name="ism" required value="<?= e($f['ism']) ?>" class="field">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('familiya')) ?></label>
                        <input name="familiya" value="<?= e($f['familiya'] ?? '') ?>" class="field">
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input name="email" type="email" value="<?= e($f['email'] ?? '') ?>" class="field">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('til_tanlash')) ?></label>
                        <select name="til" class="field">
                            <option value="uz_latn" <?= $f['til'] === 'uz_latn' ? 'selected' : '' ?>>O'zbek (Lotin)</option>
                            <option value="uz_cyrl" <?= $f['til'] === 'uz_cyrl' ? 'selected' : '' ?>>Ўзбек (Кирилл)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="field-label"><?= e(t('avatar_yuklash')) ?></label>
                    <input name="avatar" type="file" accept="image/*"
                           class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-blue-500/20 file:text-blue-400 file:cursor-pointer">
                </div>

                <button type="submit" class="btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>

            <!-- Telegram bog'lash -->
            <div class="glass-card p-6 fade-up">
                <h2 class="text-xl font-display mb-3 flex items-center gap-2">
                    <span>📱</span> Telegram
                </h2>
                <?php if ($f['telegram_id']): ?>
                    <div class="p-3 rounded-lg bg-green-500/10 border border-green-500/30 text-green-300 text-sm">
                        ✓ Telegram bog'langan (ID: <?= (int)$f['telegram_id'] ?>)
                    </div>
                <?php else: ?>
                    <p class="text-brand-muted text-sm mb-3">
                        Bildirishnomalarni olish va parolni qaytarish uchun Telegram'ni bog'lang.
                    </p>
                    <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                       target="_blank" class="btn-primary">
                        Telegramga ulanish
                    </a>
                <?php endif; ?>
            </div>

            <!-- Parol formasi -->
            <form method="POST" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="parol">

                <h2 class="text-xl font-display mb-5"><?= e(t('parolni_ozgartirish')) ?></h2>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('eski_parol')) ?></label>
                        <input type="password" name="eski_parol" required class="field">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('yangi_parol')) ?></label>
                        <input type="password" name="yangi_parol" required minlength="6" class="field">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('parol_takror')) ?></label>
                        <input type="password" name="parol_takror" required minlength="6" class="field">
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
