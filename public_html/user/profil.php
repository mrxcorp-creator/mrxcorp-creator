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
                [$ism, $familiya, $email ?: null, in_array($til, ['uz_latn','uz_cyrl','ru'], true) ? $til : 'uz_latn', $avatar, $f['id']]
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

// Yutuqlar va statistika
$yutuqlar_olingan = foydalanuvchi_yutuqlari($f['id']);
$yutuqlar_jami = db_barcha('SELECT * FROM yutuqlar ORDER BY tartib');
$xp = foydalanuvchi_xp($f['id']);

$bot_username = sozlama('telegram_bot_username', 'vatanparvaryaypan_bot');

$sahifa_sarlavha = t('profil_sozlamalar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl mb-6"><?= e(t('profil_sozlamalar')) ?></h1>

    <?php if ($xato): ?>
        <div class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-300 text-red-300 text-sm"><?= e($xato) ?></div>
    <?php endif; ?>
    <?php if ($muvaffaqiyat): ?>
        <div class="mb-5 p-3 rounded-lg bg-green-500/10 border border-emerald-300 text-green-300 text-sm"><?= e($muvaffaqiyat) ?></div>
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
                <?= $f['rol'] === 'developer' ? 'bg-violet-100 text-violet-700' :
                   ($f['rol'] === 'admin' ? 'bg-sky-100 text-sky-700' : 'bg-sky-100 text-brand-muted') ?>">
                <?= e(strtoupper($f['rol'])) ?>
            </span>

            <hr class="my-5 border-brand-border">

            <div class="text-left space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-brand-muted">A'zo bo'lgan:</span>
                    <span><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted"><?= e(t('bonus_balans')) ?>:</span>
                    <span class="font-bold text-emerald-600"><?= e(pul($f['bonus_balans'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted">XP:</span>
                    <span class="font-bold text-amber-600"><?= $xp ?> ⚡</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted">Yutuqlar:</span>
                    <span class="font-bold text-brand-text"><?= count($yutuqlar_olingan) ?> / <?= count($yutuqlar_jami) ?> 🏅</span>
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
                            <option value="ru" <?= $f['til'] === 'ru' ? 'selected' : '' ?>>Русский</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="field-label"><?= e(t('avatar_yuklash')) ?></label>
                    <input name="avatar" type="file" accept="image/*"
                           class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-blue-500/20 file:text-sky-600 file:cursor-pointer">
                </div>

                <button type="submit" class="btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>

            <!-- Yutuqlar -->
            <div id="yutuqlar" class="glass-card p-6 fade-up">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-display flex items-center gap-2">
                        <span>🏅</span> Mening yutuqlarim
                    </h2>
                    <span class="text-sm text-brand-muted"><?= count($yutuqlar_olingan) ?> / <?= count($yutuqlar_jami) ?></span>
                </div>

                <?php if (count($yutuqlar_jami) === 0): ?>
                    <p class="text-brand-muted text-sm">Hozircha yutuqlar yo'q</p>
                <?php else:
                    $olingan_idlar = array_column($yutuqlar_olingan, 'id');
                ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                        <?php foreach ($yutuqlar_jami as $y):
                            $olingan = in_array($y['id'], $olingan_idlar);
                            $rang = match ($y['daraja']) {
                                'platina' => 'from-violet-300 to-fuchsia-400',
                                'oltin'   => 'from-amber-300 to-orange-400',
                                'kumush'  => 'from-slate-300 to-slate-400',
                                default   => 'from-orange-300 to-amber-400',
                            };
                        ?>
                            <div class="relative group" title="<?= e($y['nomi'] . ': ' . $y['tavsif']) ?>">
                                <div class="aspect-square rounded-2xl flex flex-col items-center justify-center text-center p-2 transition-all
                                    <?= $olingan
                                        ? 'bg-gradient-to-br ' . $rang . ' shadow-soft hover:scale-110 hover:shadow-glow cursor-pointer'
                                        : 'bg-brand-bg-soft border border-brand-border opacity-50 grayscale' ?>">
                                    <div class="text-3xl mb-1"><?= $y['ikon'] ?></div>
                                    <div class="text-[9px] font-bold uppercase tracking-wider <?= $olingan ? 'text-white/95' : 'text-brand-muted' ?>">
                                        <?= e($y['nomi']) ?>
                                    </div>
                                </div>
                                <?php if (!$olingan): ?>
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <svg class="w-8 h-8 text-brand-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    </div>
                                <?php endif; ?>

                                <!-- Tooltip -->
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-brand-text text-white text-xs rounded-lg w-44 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none shadow-medium z-10">
                                    <div class="font-bold mb-0.5"><?= e($y['nomi']) ?></div>
                                    <div class="text-white/80 text-[10px]"><?= e($y['tavsif']) ?></div>
                                    <div class="mt-1 flex items-center gap-1 text-amber-300">
                                        <span>⚡ <?= (int)$y['xp'] ?> XP</span>
                                        <?php if ($olingan): ?><span class="text-emerald-300 ml-auto">✓ Olingan</span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Telegram bog'lash -->
            <div class="glass-card p-6 fade-up">
                <h2 class="text-xl font-display mb-3 flex items-center gap-2">
                    <span>📱</span> Telegram
                </h2>
                <?php if ($f['telegram_id']): ?>
                    <div class="p-3 rounded-lg bg-green-500/10 border border-emerald-300 text-green-300 text-sm">
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
