<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$havola = SAYT_URL . '/register?ref=' . $f['referal_kod'];

$stat = db_qator(
    'SELECT
        COUNT(*) AS jami,
        COALESCE(SUM(CASE WHEN holat="tasdiq" THEN bonus_summa ELSE 0 END), 0) AS tasdiq_summa,
        COUNT(CASE WHEN holat="tasdiq" THEN 1 END) AS tasdiq_son
     FROM referallar WHERE referer_id = ?',
    [$f['id']]
);

$royxat = db_barcha(
    'SELECT r.*, fo.ism, fo.familiya, fo.telefon
     FROM referallar r
     JOIN foydalanuvchilar fo ON r.referal_id = fo.id
     WHERE r.referer_id = ?
     ORDER BY r.yaratilgan DESC LIMIT 50',
    [$f['id']]
);

$bonus_summa = (float) sozlama('referal_bonus', 5000);

$sahifa_sarlavha = t('referal');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 py-8">
    <div class="mb-8 fade-up">
        <span class="chip chip-grad mb-3">🎁 <?= e(t('referal')) ?></span>
        <h1 class="text-3xl md:text-4xl mb-1 font-display font-extrabold"><?= e(t('referal_sarlavha')) ?></h1>
        <p class="text-muted"><?= e(t('referal_tavsif')) ?></p>
    </div>

    <div class="ring-grad mb-6 fade-up">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-16 h-16 rounded-2xl grad-bg flex items-center justify-center text-3xl">🎁</div>
                <div>
                    <div class="font-display font-bold text-lg"><?= e(t('har_dost_uchun')) ?> <span class="grad-text"><?= e(pul($bonus_summa)) ?></span></div>
                    <div class="text-sm text-muted"><?= e(t('dost_tolagach')) ?></div>
                </div>
            </div>

            <div x-data="{nusxa: false}">
                <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
                <div class="flex gap-2">
                    <input type="text" readonly value="<?= e($havola) ?>"
                           class="field flex-1 font-mono text-sm" id="havola-input"
                           onclick="this.select()">
                    <button type="button"
                            @click="navigator.clipboard.writeText('<?= e($havola) ?>'); nusxa=true; setTimeout(()=>nusxa=false, 2000)"
                            class="btn btn-primary"
                            :class="nusxa ? '!bg-success' : ''">
                        <span x-show="!nusxa"><?= e(t('nusxa_olish')) ?></span>
                        <span x-show="nusxa" x-cloak>✓ <?= e(t('nusxa_olindi')) ?></span>
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-muted text-sm self-center">Yoki ulashing:</span>
                    <a target="_blank" href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode("Avto maktab nazariyasiga onlayn tayyorgarlik!") ?>"
                       class="btn btn-ghost text-sm py-2 px-3">📤 Telegram</a>
                    <a target="_blank" href="https://wa.me/?text=<?= urlencode($havola) ?>"
                       class="btn btn-ghost text-sm py-2 px-3">💬 WhatsApp</a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="glass p-5 fade-up">
            <div class="text-xs text-muted uppercase mb-2"><?= e(t('taklif_qilingan')) ?></div>
            <div class="text-3xl font-display font-extrabold"><?= (int)$stat['jami'] ?></div>
        </div>
        <div class="glass p-5 fade-up">
            <div class="text-xs text-muted uppercase mb-2"><?= e(t('tasdiqlangan')) ?></div>
            <div class="text-3xl font-display font-extrabold text-success"><?= (int)$stat['tasdiq_son'] ?></div>
        </div>
        <div class="glass p-5 fade-up col-span-2 md:col-span-1">
            <div class="text-xs text-muted uppercase mb-2"><?= e(t('bonus_balans')) ?></div>
            <div class="text-3xl font-display font-extrabold grad-text"><?= e(pul($f['bonus_balans'])) ?></div>
        </div>
    </div>

    <div class="glass p-6 fade-up">
        <h2 class="font-display font-bold text-xl mb-4"><?= e(t('taklif_qilingan')) ?></h2>

        <?php if (empty($royxat)): ?>
            <div class="py-12 text-center text-muted text-sm">
                <?= e(t('royxat_bosh')) ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted text-xs uppercase">
                            <th class="py-2 pr-3">Foydalanuvchi</th>
                            <th class="py-2 pr-3">Telefon</th>
                            <th class="py-2 pr-3">Bonus</th>
                            <th class="py-2 pr-3">Holat</th>
                            <th class="py-2">Sana</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($royxat as $r): ?>
                            <tr class="hover:bg-white/3">
                                <td class="py-2.5 pr-3"><?= e($r['ism'] . ' ' . ($r['familiya'] ?? '')) ?></td>
                                <td class="py-2.5 pr-3 font-mono text-xs"><?= e(substr($r['telefon'], 0, 7) . '****' . substr($r['telefon'], -2)) ?></td>
                                <td class="py-2.5 pr-3 font-bold"><?= e(pul($r['bonus_summa'])) ?></td>
                                <td class="py-2.5 pr-3">
                                    <span class="chip text-xs !py-0.5 !px-2
                                        <?= $r['holat'] === 'tasdiq' ? 'bg-success/15 text-success border-success/30' :
                                           ($r['holat'] === 'bekor' ? 'bg-danger/15 text-danger border-danger/30' : 'bg-amber/15 text-amber border-amber/30') ?>">
                                        <?= e($r['holat']) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 text-muted"><?= e(vaqt_oldin($r['yaratilgan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
