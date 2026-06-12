<?php
/**
 * VatanParvar Yaypan — Referal dasturi sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$havola = SAYT_URL . '/register?ref=' . $f['referal_kod'];

// Statistika
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
        <h1 class="text-3xl mb-1"><?= e(t('referal_sarlavha')) ?></h1>
        <p class="text-brand-muted"><?= e(t('referal_tavsif')) ?></p>
    </div>

    <!-- Havola va kod -->
    <div class="glass-card p-6 mb-6 fade-up">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl">🎁</span>
            <div>
                <div class="font-display text-lg">Har bir do'st uchun <span class="text-green-400 font-bold"><?= e(pul($bonus_summa)) ?></span></div>
                <div class="text-sm text-brand-muted">Do'stingiz to'lov qilganda bonusingiz balansga qo'shiladi</div>
            </div>
        </div>

        <div x-data="{nusxa: false}">
            <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
            <div class="flex gap-2">
                <input type="text" readonly value="<?= e($havola) ?>"
                       class="field flex-1 font-mono text-sm" id="havola-input">
                <button type="button"
                        @click="navigator.clipboard.writeText('<?= e($havola) ?>'); nusxa=true; setTimeout(()=>nusxa=false, 2000)"
                        class="btn-primary"
                        :class="nusxa ? 'bg-green-600' : ''">
                    <span x-show="!nusxa"><?= e(t('nusxa_olish')) ?></span>
                    <span x-show="nusxa" x-cloak>✓ <?= e(t('nusxa_olindi')) ?></span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="text-brand-muted text-sm self-center">Yoki ulashing:</span>
                <a target="_blank" href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode("Avto maktab nazariyasiga onlayn tayyorgarlik!") ?>"
                   class="btn-ghost text-sm py-2 px-3">📤 Telegram</a>
                <a target="_blank" href="https://wa.me/?text=<?= urlencode($havola) ?>"
                   class="btn-ghost text-sm py-2 px-3">💬 WhatsApp</a>
            </div>
        </div>
    </div>

    <!-- Statistika -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="glass-card p-5 fade-up">
            <div class="text-xs text-brand-muted uppercase mb-2"><?= e(t('taklif_qilingan')) ?></div>
            <div class="text-3xl font-display font-bold"><?= (int)$stat['jami'] ?></div>
        </div>
        <div class="glass-card p-5 fade-up">
            <div class="text-xs text-brand-muted uppercase mb-2">Tasdiqlangan</div>
            <div class="text-3xl font-display font-bold text-green-400"><?= (int)$stat['tasdiq_son'] ?></div>
        </div>
        <div class="glass-card p-5 fade-up col-span-2 md:col-span-1">
            <div class="text-xs text-brand-muted uppercase mb-2"><?= e(t('bonus_balans')) ?></div>
            <div class="text-3xl font-display font-bold text-yellow-400"><?= e(pul($f['bonus_balans'])) ?></div>
        </div>
    </div>

    <!-- Ro'yxat -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display text-xl mb-4"><?= e(t('taklif_qilingan')) ?></h2>

        <?php if (empty($royxat)): ?>
            <div class="py-12 text-center text-brand-muted text-sm">
                Hali hech kim taklif qilinmagan. Havolani do'stlaringizga ulashing!
            </div>
        <?php else: ?>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-brand-muted text-xs uppercase">
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
                                    <span class="text-xs px-2 py-0.5 rounded-full
                                        <?= $r['holat'] === 'tasdiq' ? 'bg-green-500/20 text-green-400' :
                                           ($r['holat'] === 'bekor' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') ?>">
                                        <?= e($r['holat']) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 text-brand-muted"><?= e(vaqt_oldin($r['yaratilgan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
