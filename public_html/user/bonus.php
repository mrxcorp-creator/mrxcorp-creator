<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

try {
    $tarix = db_barcha(
        'SELECT * FROM bonus_tarix WHERE foydalanuvchi_id = ?
         ORDER BY id DESC LIMIT 100',
        [$f['id']]
    );
    $jami_olingan = (float) db_qiymat(
        'SELECT COALESCE(SUM(summa), 0) FROM bonus_tarix
         WHERE foydalanuvchi_id = ? AND summa > 0',
        [$f['id']]
    );
    $jami_ishlatilgan = (float) db_qiymat(
        'SELECT COALESCE(SUM(ABS(summa)), 0) FROM bonus_tarix
         WHERE foydalanuvchi_id = ? AND summa < 0',
        [$f['id']]
    );
} catch (Throwable $e) {
    $tarix = [];
    $jami_olingan = 0;
    $jami_ishlatilgan = 0;
}

$sahifa_sarlavha = t('bonus_tarix');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-8 fade-up">
        <span class="chip chip-grad mb-3">💰 <?= e(t('bonus_balans')) ?></span>
        <h1 class="text-3xl md:text-4xl mb-2 font-display font-extrabold">
            <?= e(t('bonus_tarix')) ?>
        </h1>
        <p class="text-muted"><?= e(t('bonus_tarix_tavsif')) ?></p>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="ring-grad fade-up">
            <div class="p-5">
                <div class="text-xs text-muted uppercase mb-1"><?= e(t('joriy_balans')) ?></div>
                <div class="text-2xl font-display font-extrabold grad-text"><?= e(pul($f['bonus_balans'])) ?></div>
            </div>
        </div>
        <div class="glass p-5 fade-up">
            <div class="text-xs text-muted uppercase mb-1"><?= e(t('jami_olingan')) ?></div>
            <div class="text-2xl font-display font-extrabold text-success"><?= e(pul($jami_olingan)) ?></div>
        </div>
        <div class="glass p-5 fade-up">
            <div class="text-xs text-muted uppercase mb-1"><?= e(t('ishlatilgan')) ?></div>
            <div class="text-2xl font-display font-extrabold text-amber"><?= e(pul($jami_ishlatilgan)) ?></div>
        </div>
    </div>

    <div class="glass p-5 fade-up">
        <h2 class="font-display font-bold text-lg mb-4">📋 <?= e(t('harakatlar_tarixi')) ?></h2>

        <?php if (empty($tarix)): ?>
            <div class="py-12 text-center">
                <div class="text-5xl mb-3 opacity-40">💸</div>
                <p class="text-muted text-sm"><?= e(t('bonus_harakatlari_yoq')) ?></p>
                <a href="<?= e(SAYT_URL) ?>/referal" class="btn btn-primary mt-4 text-sm py-2 px-4">
                    🎁 <?= e(t('dostlarni_taklif')) ?>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($tarix as $tt):
                    $musbat = $tt['summa'] > 0;
                    $emoji = match ($tt['tur']) {
                        'referal' => '🎁',
                        'admin'   => '👑',
                        'tolov'   => '💸',
                        'xarid'   => '🛒',
                        default   => '💰',
                    };
                    $tur_matn = match ($tt['tur']) {
                        'referal' => t('bonus_referal'),
                        'admin'   => t('bonus_admin'),
                        'tolov'   => t('bonus_tolov'),
                        'xarid'   => t('bonus_xarid'),
                        default   => t('bonus_balans'),
                    };
                ?>
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/3 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl
                                        <?= $musbat ? 'bg-success/15' : 'bg-amber/15' ?>">
                                <?= $emoji ?>
                            </div>
                            <div>
                                <div class="text-sm"><?= e($tur_matn) ?></div>
                                <?php if ($tt['izoh']): ?>
                                    <div class="text-xs text-muted truncate max-w-xs"><?= e($tt['izoh']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-display font-bold <?= $musbat ? 'text-success' : 'text-amber' ?>">
                                <?= $musbat ? '+' : '' ?><?= e(pul($tt['summa'])) ?>
                            </div>
                            <div class="text-xs text-muted"><?= e(vaqt_oldin($tt['yaratilgan'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
