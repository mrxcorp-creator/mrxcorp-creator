<?php
/**
 * AvtoTest Pro — Profil sozlamalari
 * BUG FIX: joriy_foydalanuvchi(true) — kesh yangilanadi, yangilangan ma'lumot ko'rinadi
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

        // ===== Shaxsiy ma'lumotlar =====
        if ($harakat === 'malumot') {
            $ism      = trim(post('ism')) ?: $f['ism'];
            $familiya = trim(post('familiya')) ?: null;
            $email    = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null;
            $til      = in_array(post('til'), TILLAR, true) ? post('til') : TIL_DEFAULT;

            $avatar = $f['avatar'];
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['avatar'], 'avatars', 400);
                if ($yangi) {
                    // Eski avatarni o'chirish
                    if ($avatar && is_file(UPLOAD_PATH . '/' . $avatar)) {
                        @unlink(UPLOAD_PATH . '/' . $avatar);
                    }
                    $avatar = $yangi;
                }
            }

            db_bajar(
                'UPDATE foydalanuvchilar SET ism=?, familiya=?, email=?, til=?, avatar=? WHERE id=?',
                [mb_substr($ism, 0, 100), $familiya, $email, $til, $avatar, $f['id']]
            );

            $_SESSION['til'] = $til;
            $f  = joriy_foydalanuvchi(true); // BUG FIX: keshni yangilash
            $ok = t('malumot_saqlandi');
        }

        // ===== Parolni o'zgartirish =====
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
                    'UPDATE foydalanuvchilar SET parol_hash=? WHERE id=?',
                    [password_hash($yangi, PASSWORD_BCRYPT, ['cost' => 12]), $f['id']]
                );
                $ok = t('malumot_saqlandi');
            }
        }
    }
}

// Telegram bog'lash hash (yaratib qo'yamiz)
if (empty($f['telegram_hash'])) {
    $hash = bin2hex(random_bytes(16));
    db_bajar('UPDATE foydalanuvchilar SET telegram_hash=? WHERE id=?', [$hash, $f['id']]);
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

<main class="max-w-5xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-display mb-8 fade-up"><?= e(t('profil_sozlamalar')) ?></h1>

    <?php if ($xato): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2 animate-shake fade-up">
            ⚠️ <?= e($xato) ?>
        </div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-green-500/10 border border-green-500/30 text-green-300 text-sm flex items-center gap-2 fade-up">
            ✅ <?= e($ok) ?>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Profil kartasi -->
        <div class="glass-card p-6 fade-up text-center h-fit">
            <!-- Avatar -->
            <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-display font-bold text-white text-3xl overflow-hidden ring-4 ring-white/10">
                <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>"
                         class="w-full h-full object-cover" alt="Avatar">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </div>

            <h3 class="text-xl font-display font-semibold"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></h3>
            <p class="text-brand-muted text-sm mt-1"><?= e($f['telefon']) ?></p>

            <span class="badge mt-3 <?= match($f['rol']) {
                'developer' => 'badge-purple',
                'admin'     => 'badge-blue',
                default     => 'badge-gray'
            } ?>">
                <?= strtoupper(e($f['rol'])) ?>
            </span>

            <div class="mt-5 pt-5 border-t border-white/[0.07] space-y-3 text-sm text-left">
                <div class="flex justify-between">
                    <span class="text-brand-muted">A'zo bo'lgan</span>
                    <span><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted">Oxirgi kirish</span>
                    <span><?= $f['oxirgi_kirish'] ? e(sana($f['oxirgi_kirish'], 'd.m.Y')) : '—' ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-brand-muted"><?= e(t('bonus_balans')) ?></span>
                    <span class="font-semibold text-green-400"><?= e(pul($f['bonus_balans'])) ?></span>
                </div>
            </div>

            <?php if ($obuna): ?>
                <div class="mt-4 p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/25 text-left">
                    <p class="text-xs text-yellow-400 font-semibold">⭐ Faol obuna</p>
                    <p class="text-sm font-medium mt-0.5"><?= e($obuna['tarif_nomi']) ?></p>
                    <p class="text-xs text-brand-muted"><?= e(sana($obuna['tugash'], 'd.m.Y')) ?> gacha</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formalar -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Shaxsiy ma'lumotlar -->
            <form method="POST" enctype="multipart/form-data" class="glass-card p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">
                <h2 class="text-xl font-display mb-5"><?= e(t('shaxsiy_malumot')) ?></h2>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('ism')) ?> *</label>
                        <input name="ism" required value="<?= e($f['ism']) ?>" class="field" autocomplete="given-name">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('familiya')) ?></label>
                        <input name="familiya" value="<?= e($f['familiya'] ?? '') ?>" class="field" autocomplete="family-name">
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input name="email" type="email" value="<?= e($f['email'] ?? '') ?>" class="field" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('til_tanlash')) ?></label>
                        <select name="til" class="field">
                            <option value="uz_latn" <?= ($f['til'] ?? 'uz_latn') === 'uz_latn' ? 'selected' : '' ?>>O'zbek (Lotin)</option>
                            <option value="uz_cyrl" <?= ($f['til'] ?? '') === 'uz_cyrl'         ? 'selected' : '' ?>>Ўзбек (Кирилл)</option>
                            <option value="ru"      <?= ($f['til'] ?? '') === 'ru'              ? 'selected' : '' ?>>Русский</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label"><?= e(t('avatar_yuklash')) ?> (JPEG/PNG/WebP, maks 5 MB)</label>
                        <input name="avatar" type="file" accept="image/*"
                               class="field file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-blue-500/20 file:text-blue-400 file:cursor-pointer hover:file:bg-blue-500/30">
                    </div>
                </div>

                <button type="submit" class="btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>

            <!-- Telegram bog'lash -->
            <div class="glass-card p-6 fade-up">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-400 flex items-center justify-center text-xl">📱</span>
                    <h2 class="text-xl font-display">Telegram</h2>
                </div>
                <?php if ($f['telegram_id']): ?>
                    <div class="p-3.5 rounded-xl bg-green-500/10 border border-green-500/25 text-green-300 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Telegram bog'langan (ID: <?= (int)$f['telegram_id'] ?>)
                    </div>
                <?php elseif ($bot_username): ?>
                    <p class="text-brand-muted text-sm mb-4">
                        Bildirishnomalar va parolni tiklash uchun Telegram'ni bog'lang.
                    </p>
                    <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                       target="_blank" rel="noopener"
                       class="btn-primary inline-flex items-center gap-2">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/></svg>
                        Telegramga ulanish
                    </a>
                <?php else: ?>
                    <p class="text-brand-muted text-sm">Telegram bot hali sozlanmagan.</p>
                <?php endif; ?>
            </div>

            <!-- Parolni o'zgartirish -->
            <form method="POST" class="glass-card p-6 fade-up" x-data="{showPass: false}">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="parol">
                <h2 class="text-xl font-display mb-5"><?= e(t('parolni_ozgartirish')) ?></h2>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('eski_parol')) ?></label>
                        <input type="password" name="eski_parol" required class="field" autocomplete="current-password" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('yangi_parol')) ?></label>
                        <input :type="showPass ? 'text' : 'password'" name="yangi_parol" required minlength="6"
                               class="field" autocomplete="new-password" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('parol_takror')) ?></label>
                        <input type="password" name="parol_takror" required minlength="6"
                               class="field" autocomplete="new-password" placeholder="••••••••">
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-5">
                    <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
                    <label class="flex items-center gap-2 text-sm text-brand-muted cursor-pointer select-none">
                        <input type="checkbox" @change="showPass = $event.target.checked" class="rounded">
                        Ko'rsatish
                    </label>
                </div>
            </form>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
