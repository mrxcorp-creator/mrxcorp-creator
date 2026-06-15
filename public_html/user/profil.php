<?php
/**
 * VatanParvar Yaypan — Profil
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

        if ($harakat === 'malumot') {
            $ism      = mb_substr(trim(post('ism')) ?: $f['ism'], 0, 100);
            $familiya = mb_substr(trim(post('familiya')), 0, 100) ?: null;
            $email    = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: null;
            $til      = in_array(post('til'), TILLAR, true) ? post('til') : TIL_DEFAULT;

            $avatar = $f['avatar'];
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $yangi = rasm_saqla($_FILES['avatar'], 'avatars', 400);
                if ($yangi) {
                    if ($avatar && is_file(UPLOAD_PATH.'/'.$avatar)) {
                        @unlink(UPLOAD_PATH.'/'.$avatar);
                    }
                    $avatar = $yangi;
                }
            }
            db_bajar(
                'UPDATE foydalanuvchilar SET ism=?,familiya=?,email=?,til=?,avatar=? WHERE id=?',
                [$ism, $familiya, $email, $til, $avatar, $f['id']]
            );
            $_SESSION['til'] = $til;
            $f  = joriy_foydalanuvchi(true);
            $ok = t('malumot_saqlandi');
        }

        if ($harakat === 'parol') {
            $eski   = post('eski_parol');
            $yangi  = post('yangi_parol');
            $takror = post('parol_takror');
            if (!password_verify($eski, $f['parol_hash']))    $xato = t('kirish_xato');
            elseif (mb_strlen($yangi) < 6)                     $xato = t('parol_qisqa');
            elseif ($yangi !== $takror)                        $xato = t('parollar_mos_emas');
            else {
                db_bajar('UPDATE foydalanuvchilar SET parol_hash=? WHERE id=?',
                    [password_hash($yangi, PASSWORD_BCRYPT, ['cost' => 12]), $f['id']]);
                $ok = t('malumot_saqlandi');
            }
        }
    }
}

if (empty($f['telegram_hash'])) {
    $hash = bin2hex(random_bytes(16));
    db_bajar('UPDATE foydalanuvchilar SET telegram_hash=? WHERE id=?', [$hash, $f['id']]);
    $f['telegram_hash'] = $hash;
}

$bot_username = sozlama('telegram_bot_username', '');

$sahifa_sarlavha = t('profil_sozlamalar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main style="max-width:1100px; margin:0 auto; padding: 2.5rem 1.25rem 4rem;">

    <div style="margin-bottom: 2rem;">
        <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                  letter-spacing:.08em; margin-bottom:.5rem;">Sozlamalar</p>
        <h1 style="font-family:Georgia,serif; font-weight:700; font-size: 2.25rem;">
            <?= e(t('profil_sozlamalar')) ?>
        </h1>
    </div>

    <?php if ($xato): ?>
    <div style="padding:.75rem 1rem; background:#000; color:#fff;
                border:1px solid #000; font-size:.85rem; margin-bottom: 1rem;">
        <?= e($xato) ?>
    </div>
    <?php endif; ?>
    <?php if ($ok): ?>
    <div style="padding:.75rem 1rem; background:#F5F5F5; color:#000;
                border:1px solid #000; font-size:.85rem; margin-bottom: 1rem;">
        <?= e($ok) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr; gap: 1rem;"
         class="lg:grid-cols-3">

        <!-- Profil kartasi -->
        <div class="b-card" style="padding: 1.5rem; height: fit-content;">
            <!-- Avatar -->
            <div style="display:flex; align-items:center; justify-content:center;
                        width:80px; height:80px; border:1px solid #000;
                        font-family:Georgia,serif; font-weight:700;
                        font-size:2rem; margin: 0 auto 1rem;
                        overflow:hidden;">
                <?php if (!empty($f['avatar']) && is_file(UPLOAD_PATH.'/'.$f['avatar'])): ?>
                    <img src="<?= e(SAYT_URL.'/uploads/'.$f['avatar']) ?>"
                         style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                    <?= e(bosh_harflar($f)) ?>
                <?php endif; ?>
            </div>

            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.25rem; text-align:center; margin-bottom: .25rem;">
                <?= e($f['ism']) ?> <?= e($f['familiya'] ?? '') ?>
            </h3>
            <p style="font-size:.875rem; color:#666; text-align:center;
                      margin-bottom: 1rem;">
                <?= e($f['telefon']) ?>
            </p>

            <div style="text-align:center; margin-bottom: 1.25rem;">
                <span class="badge badge-filled"><?= strtoupper(e($f['rol'])) ?></span>
            </div>

            <div style="border-top: 1px solid #000; padding-top: 1rem;
                        font-size:.85rem; display:flex; flex-direction:column; gap:.5rem;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#666;">A'zo:</span>
                    <span style="font-weight:500;"><?= e(sana($f['yaratilgan'], 'd.m.Y')) ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#666;">Bonus:</span>
                    <span style="font-weight:600;"><?= e(pul($f['bonus_balans'])) ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#666;">Telegram:</span>
                    <span class="badge <?= $f['telegram_id'] ? 'badge-filled' : 'badge-light' ?>">
                        <?= $f['telegram_id'] ? 'Ulangan' : 'Yo\'q' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Formalar -->
        <div style="display:flex; flex-direction:column; gap:1rem;"
             class="lg:col-span-2">

            <!-- Ma'lumotlar -->
            <form method="POST" enctype="multipart/form-data" class="b-card"
                  style="padding: 1.5rem;">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="malumot">

                <h2 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.15rem; padding-bottom: .65rem;
                           border-bottom: 1px solid #000; margin-bottom: 1.25rem;">
                    <?= e(t('shaxsiy_malumot')) ?>
                </h2>

                <div style="display:grid; grid-template-columns: 1fr 1fr;
                            gap: .85rem; margin-bottom: .85rem;">
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
                        <input type="email" name="email"
                               value="<?= e($f['email'] ?? '') ?>" class="field">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('til_tanlash')) ?></label>
                        <select name="til" class="field">
                            <option value="uz_latn" <?= ($f['til']??'uz_latn')==='uz_latn' ? 'selected' : '' ?>>O'zbek (Lotin)</option>
                            <option value="uz_cyrl" <?= ($f['til']??'')==='uz_cyrl' ? 'selected' : '' ?>>Ўзбек (Кирилл)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label class="field-label">
                        <?= e(t('avatar_yuklash')) ?>
                        <span style="color:#999; font-weight:normal;">(JPEG/PNG/WebP, maks 5 MB)</span>
                    </label>
                    <input type="file" name="avatar" accept="image/*" class="field">
                </div>

                <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            </form>

            <!-- Telegram -->
            <div class="b-card" style="padding: 1.5rem;">
                <h2 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.15rem; padding-bottom: .65rem;
                           border-bottom: 1px solid #000; margin-bottom: 1.25rem;">
                    Telegram
                </h2>
                <?php if ($f['telegram_id']): ?>
                <div style="padding:.75rem 1rem; background:#F5F5F5;
                            border:1px solid #000; font-size:.875rem;">
                    Telegram muvaffaqiyatli ulangan
                    <span style="color:#666;">(ID: <?= (int)$f['telegram_id'] ?>)</span>
                </div>
                <?php elseif ($bot_username): ?>
                <p style="font-size:.875rem; color:#444; margin-bottom: 1rem; line-height:1.6;">
                    Bildirishnomalar va parolni tiklash uchun Telegram'ni ulang.
                </p>
                <a href="https://t.me/<?= e($bot_username) ?>?start=<?= e($f['telegram_hash']) ?>"
                   target="_blank" rel="noopener" class="btn btn-primary">
                    Telegramga ulanish →
                </a>
                <?php else: ?>
                <p style="font-size:.875rem; color:#666;">Telegram bot hali sozlanmagan.</p>
                <?php endif; ?>
            </div>

            <!-- Parol -->
            <form method="POST" class="b-card" style="padding: 1.5rem;">
                <?= csrf_input() ?>
                <input type="hidden" name="harakat" value="parol">

                <h2 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.15rem; padding-bottom: .65rem;
                           border-bottom: 1px solid #000; margin-bottom: 1.25rem;">
                    <?= e(t('parolni_ozgartirish')) ?>
                </h2>

                <div style="display:grid; grid-template-columns: 1fr;
                            gap: .85rem; margin-bottom: 1rem;"
                     class="md:grid-cols-3">
                    <div>
                        <label class="field-label"><?= e(t('eski_parol')) ?></label>
                        <input type="password" name="eski_parol" required class="field"
                               autocomplete="current-password">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('yangi_parol')) ?></label>
                        <input type="password" name="yangi_parol" required minlength="6"
                               class="field" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="field-label"><?= e(t('parol_takror')) ?></label>
                        <input type="password" name="parol_takror" required minlength="6"
                               class="field" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?= e(t('saqlash')) ?></button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
