<?php
require_once __DIR__ . '/../config/auth.php';

if (joriy_foydalanuvchi()) {
    yonaltir(SAYT_URL . '/dashboard');
}

if (empty($_SESSION['tfa_kutilmoqda'])) {
    yonaltir(SAYT_URL . '/login');
}

$kutgan_id = (int) $_SESSION['tfa_kutilmoqda']['foydalanuvchi_id'];
$kutgan_vaqt = $_SESSION['tfa_kutilmoqda']['vaqt'] ?? 0;

if (time() - $kutgan_vaqt > 600) {
    unset($_SESSION['tfa_kutilmoqda']);
    flash_qoy('xato', 'Sessiya tugadi. Qayta kiring.');
    yonaltir(SAYT_URL . '/login');
}

$f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$kutgan_id]);
if (!$f) {
    unset($_SESSION['tfa_kutilmoqda']);
    yonaltir(SAYT_URL . '/login');
}

$xato = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $kod = trim(post('kod'));

        if (post('action') === 'qayta_yubor') {
            try {
                $oxirgi = db_qator(
                    'SELECT * FROM tfa_otp WHERE foydalanuvchi_id = ?
                     ORDER BY id DESC LIMIT 1',
                    [$kutgan_id]
                );
                if ($oxirgi && (time() - strtotime($oxirgi['yaratilgan'])) < 60) {
                    $xato = '60 soniya kutib yana urinib ko\'ring';
                } else {
                    $yangi_kod = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $tugash = date('Y-m-d H:i:s', time() + 600);
                    db_bajar(
                        'INSERT INTO tfa_otp (foydalanuvchi_id, kod, ip, tugash) VALUES (?, ?, ?, ?)',
                        [$kutgan_id, password_hash($yangi_kod, PASSWORD_DEFAULT), ip_olish(), $tugash]
                    );
                    if ($f['telegram_id']) {
                        telegram_yubor_xom($f['telegram_id'],
                            "🔐 <b>Kirish kodi</b>\n\n<code>{$yangi_kod}</code>\n\nKod 10 daqiqa amal qiladi.\nIP: " . ip_olish());
                    }
                    flash_qoy('muvaffaqiyat', 'Yangi kod yuborildi');
                }
            } catch (Throwable $e) {
                $xato = 'Server xatosi';
            }
        } elseif ($kod) {
            try {
                $otplar = db_barcha(
                    'SELECT * FROM tfa_otp
                     WHERE foydalanuvchi_id = ? AND ishlatildi = 0 AND tugash > NOW() AND urinish < 5
                     ORDER BY id DESC LIMIT 3',
                    [$kutgan_id]
                );

                $topildi = false;
                foreach ($otplar as $otp) {
                    db_bajar('UPDATE tfa_otp SET urinish = urinish + 1 WHERE id = ?', [$otp['id']]);
                    if (password_verify($kod, $otp['kod'])) {
                        db_bajar('UPDATE tfa_otp SET ishlatildi = 1 WHERE id = ?', [$otp['id']]);
                        $topildi = true;
                        break;
                    }
                }

                if ($topildi) {
                    unset($_SESSION['tfa_kutilmoqda']);
                    tizimga_kirgan($f['id']);
                    audit_yoz('tfa_muvaffaqiyatli', 'auth', (int) $f['id']);
                    $manzil = in_array($f['rol'], ['admin', 'developer'], true) ? '/admin/' : '/dashboard';
                    flash_qoy('muvaffaqiyat', t('salom') . ', ' . $f['ism'] . '!');
                    yonaltir(SAYT_URL . $manzil);
                } else {
                    $xato = 'Kod noto\'g\'ri';
                    audit_yoz('tfa_xato', 'auth', (int) $f['id']);
                }
            } catch (Throwable $e) {
                $xato = 'Server xatosi';
            }
        } else {
            $xato = 'Kodni kiriting';
        }
    }
}

$logo_yoli = sozlama('logo_yoli', '');
$logo_url = $logo_yoli && is_file(UPLOAD_PATH . '/' . $logo_yoli)
    ? SAYT_URL . '/uploads/' . $logo_yoli
    : SAYT_URL . '/assets/img/logo-mark.svg';

$sahifa_sarlavha = '2FA tasdiqlash';
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md fade-up">
        <div class="text-center mb-8">
            <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-3">
                <img src="<?= e($logo_url) ?>" alt="" class="w-12 h-12 rounded-2xl">
                <span class="font-display font-extrabold text-2xl">VatanParvar <span class="grad-text">Yaypan</span></span>
            </a>
        </div>

        <div class="ring-grad">
            <div class="p-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl grad-bg-soft flex items-center justify-center text-3xl">🔐</div>
                <h1 class="text-2xl font-display font-bold mb-2 text-center">2FA tasdiqlash</h1>
                <p class="text-muted text-sm mb-6 text-center">
                    Telegram'ga yuborilgan 6 raqamli kodni kiriting
                </p>

                <?php if ($xato): ?>
                    <div class="mb-5 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm shake">
                        <?= e($xato) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <?= csrf_input() ?>

                    <input type="text" name="kod" maxlength="6" required autofocus inputmode="numeric"
                           pattern="[0-9]{6}" placeholder="123456"
                           class="field text-center text-2xl tracking-[0.5em] font-mono"
                           autocomplete="one-time-code">

                    <button type="submit" class="btn btn-primary w-full text-base">
                        ✓ Tasdiqlash
                    </button>
                </form>

                <form method="POST" class="mt-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="qayta_yubor">
                    <button type="submit" class="btn btn-ghost w-full text-sm">
                        🔄 Kodni qaytadan yuborish
                    </button>
                </form>

                <div class="text-center mt-6 pt-6 border-t border-white/10">
                    <a href="<?= e(SAYT_URL) ?>/login"
                       class="text-muted hover:text-text text-sm">← Ortga qaytish</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
