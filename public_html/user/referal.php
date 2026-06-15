<?php
/**
 * AvtoTest Pro — Referal dasturi sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$havola      = SAYT_URL . '/register?ref=' . $f['referal_kod'];
$bonus_summa = (float) sozlama('referal_bonus', 5000);

$stat = db_qator(
    'SELECT
        COUNT(*)                                                              AS jami,
        COUNT(CASE WHEN holat = "tasdiq" THEN 1 END)                         AS tasdiq_son,
        COALESCE(SUM(CASE WHEN holat = "tasdiq" THEN bonus_summa ELSE 0 END), 0) AS tasdiq_summa
     FROM referallar WHERE referer_id = ?',
    [$f['id']]
);

$royxat = db_barcha(
    'SELECT r.*, fo.ism, fo.familiya, fo.telefon
     FROM referallar r
     JOIN foydalanuvchilar fo ON r.referal_id = fo.id
     WHERE r.referer_id = ?
     ORDER BY r.yaratilgan DESC
     LIMIT 50',
    [$f['id']]
);

$sahifa_sarlavha = t('referal');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 py-10">

    <div class="mb-8 fade-up">
        <h1 class="text-3xl font-display mb-1"><?= e(t('referal_sarlavha')) ?></h1>
        <p class="text-brand-muted"><?= e(t('referal_tavsif')) ?></p>
    </div>

    <!-- Havola va ulashish -->
    <div class="glass-card p-6 mb-6 fade-up border-blue-500/20 bg-blue-500/[0.03]"
         x-data="{ nusxa: false }">
        <div class="flex items-start gap-4 mb-5">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-3xl flex-shrink-0 shadow-lg">🎁</div>
            <div>
                <h2 class="font-display text-lg">
                    Har bir taklif uchun
                    <span class="text-green-400 font-bold"><?= e(pul($bonus_summa)) ?></span>
                </h2>
                <p class="text-sm text-brand-muted mt-1">
                    Do'stingiz havolangiz orqali ro'yxatdan o'tib, to'lov qilganida bonus balansingizga qo'shiladi.
                </p>
            </div>
        </div>

        <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
        <div class="flex gap-2">
            <input type="text" readonly value="<?= e($havola) ?>"
                   class="field flex-1 font-mono text-sm"
                   id="referal-havola"
                   @click="$el.select()">
            <button type="button"
                    @click="navigator.clipboard.writeText('<?= e($havola) ?>').then(() => { nusxa = true; setTimeout(() => nusxa = false, 2500); })"
                    class="btn-primary flex-shrink-0 transition-all"
                    :class="nusxa ? 'bg-green-600 hover:bg-green-500' : ''">
                <span x-show="!nusxa"><?= e(t('nusxa_olish')) ?></span>
                <span x-show="nusxa" x-cloak>✓ <?= e(t('nusxa_olindi')) ?></span>
            </button>
        </div>

        <!-- Ulashish tugmalari -->
        <div class="flex flex-wrap items-center gap-2 mt-4">
            <span class="text-xs text-brand-muted">Ulashing:</span>
            <a href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode("AvtoTest Pro — avto maktab imtihoniga tayyorgarlik platformasi! Birinchi urinishdan o'ting 🚗") ?>"
               target="_blank" rel="noopener"
               class="btn-ghost text-xs py-2 px-3 gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/></svg>
                Telegram
            </a>
            <a href="https://wa.me/?text=<?= urlencode($havola) ?>"
               target="_blank" rel="noopener"
               class="btn-ghost text-xs py-2 px-3 gap-1.5">
                💬 WhatsApp
            </a>
        </div>
    </div>

    <!-- Statistika kartalari -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="glass-card p-5 fade-up">
            <p class="text-xs text-brand-muted uppercase tracking-wide mb-2"><?= e(t('taklif_qilingan')) ?></p>
            <p class="text-3xl font-display font-bold"><?= (int)$stat['jami'] ?></p>
            <p class="text-xs text-brand-muted mt-1">jami foydalanuvchi</p>
        </div>
        <div class="glass-card p-5 fade-up">
            <p class="text-xs text-brand-muted uppercase tracking-wide mb-2">Tasdiqlangan</p>
            <p class="text-3xl font-display font-bold text-green-400"><?= (int)$stat['tasdiq_son'] ?></p>
            <p class="text-xs text-brand-muted mt-1">to'lov qilgan</p>
        </div>
        <div class="glass-card p-5 fade-up col-span-2 md:col-span-1">
            <p class="text-xs text-brand-muted uppercase tracking-wide mb-2"><?= e(t('bonus_balans')) ?></p>
            <p class="text-2xl font-display font-bold text-yellow-400"><?= e(pul($f['bonus_balans'])) ?></p>
            <p class="text-xs text-brand-muted mt-1">to'lovda ishlatish mumkin</p>
        </div>
    </div>

    <!-- Ro'yxat -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display text-xl mb-5"><?= e(t('taklif_qilingan')) ?></h2>

        <?php if (empty($royxat)): ?>
            <div class="py-16 text-center text-brand-muted">
                <div class="text-5xl mb-4">👥</div>
                <p class="font-medium mb-1">Hali hech kim taklif qilinmagan</p>
                <p class="text-sm">Havolangizni do'stlaringizga ulashing va bonus oling!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-brand-muted text-xs uppercase tracking-wide">
                            <th class="py-2.5 pr-4">Foydalanuvchi</th>
                            <th class="py-2.5 pr-4">Telefon</th>
                            <th class="py-2.5 pr-4">Bonus</th>
                            <th class="py-2.5 pr-4">Holat</th>
                            <th class="py-2.5">Sana</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <?php foreach ($royxat as $r): ?>
                            <tr class="hover:bg-white/[0.03] transition">
                                <td class="py-3 pr-4 font-medium">
                                    <?= e(trim($r['ism'] . ' ' . ($r['familiya'] ?? ''))) ?>
                                </td>
                                <td class="py-3 pr-4 font-mono text-xs text-brand-muted">
                                    <?= e(substr($r['telefon'], 0, 7) . '****' . substr($r['telefon'], -2)) ?>
                                </td>
                                <td class="py-3 pr-4 font-semibold"><?= e(pul($r['bonus_summa'])) ?></td>
                                <td class="py-3 pr-4">
                                    <span class="badge <?= match($r['holat']) {
                                        'tasdiq'    => 'badge-green',
                                        'bekor'     => 'badge-red',
                                        default     => 'badge-yellow'
                                    } ?>">
                                        <?= e($r['holat']) ?>
                                    </span>
                                </td>
                                <td class="py-3 text-xs text-brand-muted tabnum"><?= e(vaqt_oldin($r['yaratilgan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
