<?php
/**
 * Admin — Sayt sozlamalari
 */
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/sozlamalar.php');
    }

    // Lotin sozlamalarni saqlash + ularning kirill juftlarini avtomatik to'ldirish.
    // Agar admin _cyrl maydonni bo'sh qoldirsa — lotindan o'giriladi.
    $avto_translit_kalitlar = ['sayt_nomi', 'sayt_shior'];

    foreach ($_POST as $kalit => $qiymat) {
        if ($kalit === 'csrf_token' || !is_string($qiymat)) continue;
        sozlama_saqla($kalit, $qiymat);
    }

    // Avto-translit: agar lotin yangilangan, lekin _cyrl bo'sh bo'lsa — to'ldiramiz
    foreach ($avto_translit_kalitlar as $k) {
        $latn = trim((string) ($_POST[$k] ?? ''));
        $cyrl = trim((string) ($_POST[$k . '_cyrl'] ?? ''));
        if ($latn !== '' && $cyrl === '') {
            sozlama_saqla($k . '_cyrl', lotin_dan_kirill($latn));
        }
    }

    foreach (glob(CACHE_PATH . '/indeks_*.html') ?: [] as $fayl) @unlink($fayl);
    flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    yonaltir(SAYT_URL . '/admin/sozlamalar.php');
}

$barcha = db_barcha('SELECT * FROM sozlamalar ORDER BY kalit');

// Guruhlash
$guruhlar = [
    'Sayt' => ['sayt_nomi', 'sayt_nomi_cyrl', 'sayt_shior', 'sayt_shior_cyrl', 'aloqa_telefon', 'aloqa_email', 'telegram_kanal'],
    'Telegram' => ['telegram_bot_token', 'telegram_bot_username', 'telegram_admin_id'],
    "To'lov" => ['click_merchant_id', 'click_secret', 'payme_merchant_id', 'payme_key'],
    'Test' => ['test_vaqti_minut', 'savol_soni_test'],
    'Boshqa' => ['referal_bonus'],
];

$kesh = [];
foreach ($barcha as $s) $kesh[$s['kalit']] = $s;

$admin_sahifa = 'sozlamalar';
$sahifa_sarlavha = 'Sozlamalar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="space-y-6">
    <?= csrf_input() ?>

    <?php foreach ($guruhlar as $guruh => $kalitlar): ?>
        <div class="glass-card p-5 fade-up">
            <h2 class="font-display text-lg mb-4"><?= e($guruh) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($kalitlar as $k):
                    $s = $kesh[$k] ?? ['qiymat' => '', 'tavsif' => ''];
                    $maxfiy = str_contains($k, 'token') || str_contains($k, 'secret') || str_contains($k, 'key');
                    $is_cyrl_pair = in_array($k, ['sayt_nomi_cyrl', 'sayt_shior_cyrl'], true);
                    $is_translit_source = in_array($k, ['sayt_nomi', 'sayt_shior'], true);
                ?>
                    <div>
                        <label class="field-label">
                            <?= e($s['tavsif'] ?: $k) ?>
                            <?php if ($is_cyrl_pair): ?><span class="text-xs text-brand-muted">— avto</span><?php endif; ?>
                        </label>
                        <?php if ($k === 'sayt_shior' || $k === 'sayt_shior_cyrl'): ?>
                            <textarea name="<?= e($k) ?>" rows="2" class="field"
                                <?= $is_translit_source ? 'data-translit="' . e($k) . '_cyrl"' : '' ?>
                                <?= $is_cyrl_pair ? 'placeholder="Bo\'sh qoldirilsa avtomatik to\'ldiriladi"' : '' ?>><?= e($s['qiymat']) ?></textarea>
                        <?php else: ?>
                            <input name="<?= e($k) ?>" type="<?= $maxfiy ? 'password' : 'text' ?>"
                                   value="<?= e($s['qiymat']) ?>" class="field" autocomplete="off"
                                   <?= $is_translit_source ? 'data-translit="' . e($k) . '_cyrl"' : '' ?>
                                   <?= $is_cyrl_pair ? 'placeholder="Avto"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="glass-card p-5 fade-up">
        <h2 class="font-display text-lg mb-4">Vebhuk URL'lar</h2>
        <p class="text-sm text-brand-muted mb-3">To'lov tizimlarini sozlash uchun ushbu URL'larni ulardagi merchant kabinetiga kiriting.</p>
        <div class="space-y-2 text-sm font-mono">
            <div><span class="text-blue-400">Click:</span> <?= e(SAYT_URL) ?>/api/click_webhook.php</div>
            <div><span class="text-blue-400">Payme:</span> <?= e(SAYT_URL) ?>/api/payme_webhook.php</div>
            <div><span class="text-blue-400">Telegram bot:</span> <?= e(SAYT_URL) ?>/bot.php</div>
        </div>
    </div>

    <button type="submit" class="btn-primary"><?= e(t('saqlash')) ?></button>
</form>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
