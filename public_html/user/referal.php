<?php
/**
 * VatanParvar Yaypan — Referal
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$havola      = SAYT_URL . '/register?ref=' . $f['referal_kod'];
$bonus_summa = (float) sozlama('referal_bonus', 5000);

$stat = db_qator(
    'SELECT COUNT(*) AS jami,
            COUNT(CASE WHEN holat = "tasdiq" THEN 1 END) AS tasdiq_son
     FROM referallar WHERE referer_id = ?',
    [$f['id']]
);

$royxat = db_barcha(
    'SELECT r.*, fo.ism, fo.familiya, fo.telefon
     FROM referallar r JOIN foydalanuvchilar fo ON r.referal_id = fo.id
     WHERE r.referer_id = ? ORDER BY r.yaratilgan DESC LIMIT 50',
    [$f['id']]
);

$sahifa_sarlavha = t('referal');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main style="max-width:1100px; margin:0 auto; padding: 2.5rem 1.25rem 4rem;">

    <div style="margin-bottom: 2rem;">
        <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                  letter-spacing:.08em; margin-bottom:.5rem;">Referal</p>
        <h1 style="font-family:Georgia,serif; font-weight:700; font-size: 2.25rem;
                   margin-bottom:.5rem;">
            <?= e(t('referal_sarlavha')) ?>
        </h1>
        <p style="color:#555; max-width: 36rem; line-height:1.6;">
            <?= e(t('referal_tavsif')) ?>
        </p>
    </div>

    <!-- Bonus va havola -->
    <div class="b-card" style="padding: 2rem; margin-bottom: 1.5rem;"
         x-data="{ nusxa: false }">

        <div style="display:flex; justify-content:space-between; align-items:flex-start;
                    flex-wrap:wrap; gap:1rem; margin-bottom: 1.5rem;
                    padding-bottom: 1.25rem; border-bottom: 1px solid #000;">
            <div>
                <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                          letter-spacing:.08em; margin-bottom:.4rem;">
                    Har bir taklif uchun
                </p>
                <h2 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 2rem;" class="tabnum">
                    <?= e(pul($bonus_summa)) ?>
                </h2>
            </div>
        </div>

        <div style="margin-bottom: 1rem;">
            <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
            <div style="display:flex; gap:.5rem;">
                <input type="text" readonly value="<?= e($havola) ?>"
                       class="field" style="font-family:monospace; font-size:.85rem;"
                       @click="$el.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText('<?= e($havola) ?>')
                            .then(() => { nusxa = true; setTimeout(() => nusxa = false, 2000); })"
                        class="btn"
                        :class="nusxa ? 'btn-primary' : ''"
                        style="white-space:nowrap;">
                    <span x-text="nusxa ? '✓ Olindi' : '<?= e(t('nusxa_olish')) ?>'">
                        <?= e(t('nusxa_olish')) ?>
                    </span>
                </button>
            </div>
        </div>

        <!-- Ulashish tugmalari -->
        <div style="display:flex; flex-wrap:wrap; gap:.5rem;
                    padding-top: 1rem; border-top: 1px solid #E5E5E5;">
            <span style="font-size:.78rem; color:#666; align-self:center;">Ulashing:</span>
            <a href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode('VatanParvar Yaypan — avto maktab tayyorgarlik') ?>"
               target="_blank" rel="noopener" class="btn btn-sm">Telegram</a>
            <a href="https://wa.me/?text=<?= urlencode($havola) ?>"
               target="_blank" rel="noopener" class="btn btn-sm">WhatsApp</a>
        </div>
    </div>

    <!-- Statistika -->
    <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                border:1px solid #000; margin-bottom: 1.5rem;"
         class="grid-cols-3">
        <?php
        $cards = [
            ['Taklif qilingan', (int)$stat['jami']],
            ['Tasdiqlangan',    (int)$stat['tasdiq_son']],
            ['Bonus balans',    pul($f['bonus_balans'])],
        ];
        foreach ($cards as $i => [$nom, $val]):
            $bl = $i > 0 ? 'border-left: 1px solid #E5E5E5;' : '';
        ?>
        <div style="padding: 1.5rem; <?= $bl ?>" class="grid-cols-3:border-l grid-cols-3:border-l-[#E5E5E5]">
            <div style="font-size:.75rem; color:#666; text-transform:uppercase;
                        letter-spacing:.08em; margin-bottom:.65rem;">
                <?= e($nom) ?>
            </div>
            <div style="font-family:Georgia,serif; font-weight:700;
                        font-size: 1.75rem; line-height:1;" class="tabnum">
                <?= e($val) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Ro'yxat -->
    <div class="b-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #000;">
            <h2 style="font-family:Georgia,serif; font-weight:700; font-size: 1.15rem;">
                <?= e(t('taklif_qilingan')) ?>
            </h2>
        </div>

        <?php if (empty($royxat)): ?>
        <div style="padding: 3rem 1.5rem; text-align: center;">
            <p style="font-size:.95rem; color:#666; margin-bottom: .5rem;">
                Hali hech kim taklif qilinmagan.
            </p>
            <p style="font-size:.85rem; color:#999;">
                Havolani do'stlaringizga ulashing.
            </p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="b-table">
                <thead>
                    <tr>
                        <th>Foydalanuvchi</th>
                        <th>Telefon</th>
                        <th>Bonus</th>
                        <th>Holat</th>
                        <th>Sana</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($royxat as $r): ?>
                    <tr>
                        <td style="font-weight:500;">
                            <?= e(trim($r['ism'].' '.($r['familiya'] ?? ''))) ?>
                        </td>
                        <td style="font-family:monospace; font-size:.82rem; color:#666;">
                            <?= e(substr($r['telefon'], 0, 7) . '****' . substr($r['telefon'], -2)) ?>
                        </td>
                        <td style="font-weight:600;" class="tabnum">
                            <?= e(pul($r['bonus_summa'])) ?>
                        </td>
                        <td>
                            <span class="badge <?= $r['holat'] === 'tasdiq' ? 'badge-filled' : 'badge-light' ?>">
                                <?= e($r['holat']) ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem; color:#666;">
                            <?= e(vaqt_oldin($r['yaratilgan'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
