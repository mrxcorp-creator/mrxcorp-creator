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
    <h1 class="text-3xl md:text-4xl mb-2 fade-up"><?= e(t('profil_sozlamalar')) ?></h1>
    <p class="text-app-2 mb-8">Hisobingizni boshqaring va ma'lumotlarni yangilang</p>

    <?php if ($xato): ?>
        <div class="mb-5 p-3.5 rounded-xl border text-sm flex items-start gap-2"
             style="background: var(--error-soft); border-color: color-mix(in srgb, var(--error) 30%, transparent); color: var(--error);">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
            <span><?= e($xato) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($muvaffaqiyat): ?>
        <div class="mb-5 p-3.5 rounded-xl border text-sm flex items-start gap-2"
             style="background: var(--success-soft); border-color: color-mix(in srgb, var(--success) 30%, transparent); color: var(--success);">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
            <span><?= e($muvaffaqiyat) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Chap: profil kartasi -->
        <div class="glass-card-premium p-6 fade-up text-center">
            <div class="w-28 h-28 mx-auto mb-4 rounded-2xl flex items-center justify-center font-display font-extrabold text-white text-4xl overflow-hidden shadow-xl"
                 style="background: var(--gradient-primary); box-shadow: 0 16px 40px var(--accent-glow);">
                <?php if ($f['avatar'] && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </div>
            <h3 class="text-xl font-display text-app"><?= e(fu_ism($f) ?: $f['ism']) ?></h3>
            <p class="text-app-2 text-sm mb-3"><?= e($f['telefon']) ?></p>
            <span class="badge <?= $f['rol'] === 'developer' ? 'badge-warning' : ($f['rol'] === 'admin' ? 'badge-accent' : 'badge-success') ?>">
                <?= e(mb_strtoupper($f['rol'])) ?>
            </span>

            <hr class="divider my-5">

            <div class="text-left space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-app-2">A'zo bo'lgan:</span>
                    <span class="text-app font-medium"><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-app-2"><?= e(t('bonus_balans')) ?>:</span>
                    <span class="font-bold gradient-text text-base"><?= e(pul($f['bonus_balans'])) ?></span>
                </div>
            </div>
        </div>

        <!-- O'rta + o'ng: formalar -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ma'lumot formasi -->
            <form method="POST" enctype="multipart/form-data" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">

                <h2 class="text-xl font-display mb-5 text-app"><?= e(t('shaxsiy_malumot')) ?></h2>

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
                           class="field file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:cursor-pointer file:text-white file:font-medium"
                           style="--tw-file-bg: var(--gradient-primary);">
                </div>

                <button type="submit" class="btn-primary mt-6">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    <?= e(t('saqlash')) ?>
                </button>
            </form>

            <!-- Telegram bog'lash -->
            <div class="glass-card p-6 fade-up">
                <h2 class="text-xl font-display mb-3 flex items-center gap-2.5 text-app">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white"
                          style="background: linear-gradient(135deg, #06B6D4, #0891B2);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </span>
                    Telegram
                </h2>
                <?php if ($f['telegram_id']): ?>
                    <div class="p-3.5 rounded-xl border text-sm flex items-center gap-2"
                         style="background: var(--success-soft); border-color: color-mix(in srgb, var(--success) 30%, transparent); color: var(--success);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z"/></svg>
                        Telegram bog'langan (ID: <?= (int)$f['telegram_id'] ?>)
                    </div>
                <?php else: ?>
                    <p class="text-app-2 text-sm mb-4">
                        Bildirishnomalarni olish va parolni qaytarish uchun Telegram'ni bog'lang.
                    </p>
                    <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                       target="_blank" class="btn-primary">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        Telegramga ulanish
                    </a>
                <?php endif; ?>
            </div>

            <!-- Parol formasi -->
            <form method="POST" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="parol">

                <h2 class="text-xl font-display mb-5 text-app"><?= e(t('parolni_ozgartirish')) ?></h2>

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

                <button type="submit" class="btn-primary mt-6">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <?= e(t('saqlash')) ?>
                </button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
