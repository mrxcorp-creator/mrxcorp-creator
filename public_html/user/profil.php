<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$xato = '';
$muvaffaqiyat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma_nomi = post('csrf_forma_nom');
    $forma_token = post('csrf_forma_token');
    $forma_ok = $forma_nomi
        ? csrf_form_tekshir($forma_nomi, $forma_token)
        : csrf_tekshir(post('csrf_token'));

    if (!$forma_ok) {
        $xato = t('csrf_xato');
    } else {
        $harakat = post('harakat');

        if ($harakat === 'malumot') {
            $ism = post('ism') ?: $f['ism'];
            $familiya = post('familiya');
            $email = post('email');
            $til = post('til');

            $avatar = $f['avatar'];
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['avatar'], 'avatars', 400);
                if ($yangi) {
                    $avatar = $yangi;
                }
            }

            $til_ok = in_array($til, ['uz_latn','uz_cyrl'], true) ? $til : 'uz_latn';
            db_bajar(
                'UPDATE foydalanuvchilar
                 SET ism = ?, familiya = ?, email = ?, til = ?, avatar = ?
                 WHERE id = ?',
                [$ism, $familiya, $email ?: null, $til_ok, $avatar, $f['id']]
            );
            $_SESSION['til'] = $til_ok;
            $muvaffaqiyat = t('malumot_saqlandi');
            $f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$f['id']]);
        }

        if ($harakat === 'parol') {
            $eski = post('eski_parol');
            $yangi = post('yangi_parol');
            $takror = post('parol_takror');

            $tekshir = parol_murakkabmi($yangi);

            if (!password_verify($eski, $f['parol_hash'])) {
                $xato = t('kirish_xato');
            } elseif (!$tekshir['ok']) {
                $xato = $tekshir['xato'];
            } elseif ($yangi !== $takror) {
                $xato = t('parollar_mos_emas');
            } else {
                db_bajar(
                    'UPDATE foydalanuvchilar SET parol_hash = ? WHERE id = ?',
                    [password_hash($yangi, PASSWORD_BCRYPT), $f['id']]
                );
                audit_yoz('parol_ozgartirildi', 'foydalanuvchi', (int) $f['id']);
                if (!empty($f['telegram_id'])) {
                    telegram_yubor($f['telegram_id'],
                        "🔑 <b>Parolingiz o'zgartirildi</b>\nVaqt: " . date('d.m.Y H:i') . "\nIP: " . ip_olish() . "\n\nAgar bu siz bo'lmasangiz, darhol bizga murojaat qiling.");
                }
                $muvaffaqiyat = t('malumot_saqlandi');
            }
        }

        if ($harakat === 'bildirishnoma') {
            $yangi_holat = post('kirish_bildirish') === '1' ? 1 : 0;
            db_bajar(
                'UPDATE foydalanuvchilar SET kirish_bildirish = ? WHERE id = ?',
                [$yangi_holat, $f['id']]
            );
            $f['kirish_bildirish'] = $yangi_holat;
            $muvaffaqiyat = t('malumot_saqlandi');
        }

        if ($harakat === 'tfa') {
            $yangi = post('tfa_yoq') === '1' ? 1 : 0;
            if ($yangi && empty($f['telegram_id'])) {
                $xato = '2FA uchun avval Telegram ulanishi kerak';
            } else {
                try {
                    db_bajar('UPDATE foydalanuvchilar SET tfa_yoq = ? WHERE id = ?', [$yangi, $f['id']]);
                    $f['tfa_yoq'] = $yangi;
                    audit_yoz('tfa_' . ($yangi ? 'yoqildi' : 'ochirildi'), 'foydalanuvchi', (int) $f['id']);
                    if ($yangi && !empty($f['telegram_id'])) {
                        telegram_yubor($f['telegram_id'],
                            "✅ <b>2FA yoqildi</b>\n\nEndi har kirganda Telegram'ga 6 raqamli kod yuboriladi.");
                    }
                    $muvaffaqiyat = t('malumot_saqlandi');
                } catch (Throwable $e) {
                    $xato = 'Server xatosi (migrationni bajaring)';
                }
            }
        }
    }
}

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

<main class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-3xl md:text-4xl mb-6 font-display font-extrabold"><?= e(t('profil_sozlamalar')) ?></h1>

    <?php if ($xato): ?>
        <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm"><?= e($xato) ?></div>
    <?php endif; ?>
    <?php if ($muvaffaqiyat): ?>
        <div class="mb-5 p-3 rounded-xl bg-success/10 border border-success/30 text-success text-sm"><?= e($muvaffaqiyat) ?></div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">

        <div class="ring-grad fade-up">
            <div class="p-6 text-center">
                <div class="w-28 h-28 mx-auto mb-4 rounded-3xl grad-bg flex items-center justify-center font-display font-extrabold text-white text-4xl overflow-hidden">
                    <?php if ($f['avatar'] && is_file(UPLOAD_PATH . '/' . $f['avatar'])): ?>
                        <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($f['avatar']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(bosh_harflar($f)) ?>
                    <?php endif; ?>
                </div>
                <h3 class="text-xl font-display font-bold"><?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?></h3>
                <p class="text-muted text-sm"><?= e($f['telefon']) ?></p>
                <span class="chip mt-3
                    <?= $f['rol'] === 'developer' ? 'bg-violet/15 text-violet border-violet/30' :
                       ($f['rol'] === 'admin' ? 'bg-cyan/15 text-cyan border-cyan/30' : '') ?>">
                    <?= e(strtoupper($f['rol'])) ?>
                </span>

                <hr class="my-5 border-white/10">

                <div class="text-left space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted"><?= e(t('azo_bolgan')) ?>:</span>
                        <span><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted"><?= e(t('bonus_balans')) ?>:</span>
                        <span class="font-bold text-success"><?= e(pul($f['bonus_balans'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <form method="POST" enctype="multipart/form-data" class="glass p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">

                <h2 class="text-xl font-display font-bold mb-5"><?= e(t('shaxsiy_malumot')) ?></h2>

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
                           class="field file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-violet/20 file:text-violet file:cursor-pointer">
                </div>

                <button type="submit" class="btn btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>

            <div class="glass p-6 fade-up">
                <h2 class="text-xl font-display font-bold mb-3 flex items-center gap-2">
                    <span>📱</span> Telegram
                </h2>
                <?php if ($f['telegram_id']): ?>
                    <div class="p-3 rounded-xl bg-success/10 border border-success/30 text-success text-sm">
                        ✓ <?= e(t('telegram_boglangan')) ?> (ID: <?= (int)$f['telegram_id'] ?>)
                    </div>
                <?php else: ?>
                    <p class="text-muted text-sm mb-3"><?= e(t('telegram_haqida')) ?></p>
                    <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                       target="_blank" class="btn btn-primary">
                        <?= e(t('telegram_boglash')) ?>
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" class="glass p-6 fade-up">
                <?= csrf_form_input('parol_ozgartirish') ?>
                <input type="hidden" name="harakat" value="parol">

                <h2 class="text-xl font-display font-bold mb-5"><?= e(t('parolni_ozgartirish')) ?></h2>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="field-label"><?= e(t('eski_parol')) ?></label>
                        <input type="password" name="eski_parol" required class="field" autocomplete="current-password">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('yangi_parol')) ?> <span class="text-xs">(8+ harf+raqam)</span></label>
                        <input type="password" name="yangi_parol" required minlength="8" class="field" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('parol_takror')) ?></label>
                        <input type="password" name="parol_takror" required minlength="8" class="field" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-5"><?= e(t('saqlash')) ?></button>
            </form>

            <form method="POST" class="glass p-6 fade-up">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="bildirishnoma">

                <h2 class="text-xl font-display font-bold mb-3 flex items-center gap-2">
                    <span>🔔</span> Xavfsizlik bildirishnomalari
                </h2>
                <p class="text-sm text-muted mb-4">
                    Akkauntingizga yangi qurilmadan kirish aniqlansa Telegram orqali xabar olasizmi?
                </p>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="kirish_bildirish" value="1" <?= !empty($f['kirish_bildirish']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-violet focus:ring-violet">
                    <span class="text-sm">Yangi kirishlar haqida Telegram'ga xabar yuborilsin</span>
                </label>
                <button type="submit" class="btn btn-primary mt-4 text-sm py-2 px-4"><?= e(t('saqlash')) ?></button>
            </form>

            <form method="POST" class="glass p-6 fade-up <?= !$f['telegram_id'] ? 'opacity-60' : '' ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="tfa">

                <h2 class="text-xl font-display font-bold mb-3 flex items-center gap-2">
                    <span>🔐</span> 2FA — Ikki bosqichli tasdiqlash
                </h2>
                <p class="text-sm text-muted mb-4">
                    <?php if (!$f['telegram_id']): ?>
                        ⚠️ Avval Telegram'ni ulang. 2FA Telegram orqali ishlaydi.
                    <?php else: ?>
                        Har kirganda Telegram'ga 6 raqamli kod yuboriladi. Bu xavfsizlikni sezilarli oshiradi.
                    <?php endif; ?>
                </p>
                <label class="flex items-center gap-3 cursor-pointer <?= !$f['telegram_id'] ? 'opacity-50 pointer-events-none' : '' ?>">
                    <input type="checkbox" name="tfa_yoq" value="1" <?= !empty($f['tfa_yoq']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-white/20 bg-white/5 text-violet focus:ring-violet">
                    <span class="text-sm">2FA yoqilgan bo'lsin</span>
                </label>
                <button type="submit" class="btn btn-primary mt-4 text-sm py-2 px-4" <?= !$f['telegram_id'] ? 'disabled' : '' ?>>
                    <?= e(t('saqlash')) ?>
                </button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
