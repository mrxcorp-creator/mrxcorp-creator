<?php
/**
 * VatanParvar Yaypan — Referal dasturi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();

$havola      = SAYT_URL . '/register?ref=' . $f['referal_kod'];
$bonus_summa = (float) sozlama('referal_bonus', 5000);

$stat = db_qator(
    'SELECT
        COUNT(*) AS jami,
        COUNT(CASE WHEN holat = "tasdiq" THEN 1 END) AS tasdiq_son,
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

<main class="max-w-5xl mx-auto px-4 py-10 pb-20">

    <!-- ── Sarlavha ──────────────────────────────────────── -->
    <div class="mb-8 fade-up">
        <h1 class="text-3xl font-display font-black mb-1"><?= e(t('referal_sarlavha')) ?></h1>
        <p class="text-white/45"><?= e(t('referal_tavsif')) ?></p>
    </div>

    <!-- ── Havola kartasi ────────────────────────────────── -->
    <div class="glass-card p-6 mb-6 border-blue-500/20 bg-blue-500/[0.03] fade-up"
         x-data="{ nusxa: false }">

        <div class="flex items-start gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600
                        flex items-center justify-center text-3xl flex-shrink-0
                        shadow-lg shadow-emerald-500/20">
                🎁
            </div>
            <div>
                <h2 class="font-display font-bold text-xl">
                    Har bir taklif uchun
                    <span class="text-emerald-400"><?= e(pul($bonus_summa)) ?></span>
                </h2>
                <p class="text-sm text-white/50 mt-1 leading-relaxed">
                    Do'stingiz havolangiz orqali ro'yxatdan o'tib, to'lov qilganida
                    bonus balansingizga qo'shiladi.
                </p>
            </div>
        </div>

        <!-- Havola -->
        <div class="mb-4">
            <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
            <div class="flex gap-2">
                <input type="text" readonly value="<?= e($havola) ?>"
                       class="field flex-1 font-mono text-sm" @click="$el.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText('<?= e($havola) ?>')
                                    .then(() => { nusxa = true; setTimeout(() => nusxa = false, 2500); })"
                        :class="nusxa ? 'btn-success' : 'btn-primary'"
                        class="btn flex-shrink-0 transition-all">
                    <svg x-show="!nusxa" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="nusxa" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="nusxa ? '<?= e(t('nusxa_olindi')) ?>' : '<?= e(t('nusxa_olish')) ?>'">
                        <?= e(t('nusxa_olish')) ?>
                    </span>
                </button>
            </div>
        </div>

        <!-- Ulashish tugmalari -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-white/35">Ulashing:</span>
            <a href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode('VatanParvar Yaypan — avto maktab imtihoniga tayyorgarlik! 🚗') ?>"
               target="_blank" rel="noopener"
               class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/>
                </svg>
                Telegram
            </a>
            <a href="https://wa.me/?text=<?= urlencode($havola) ?>"
               target="_blank" rel="noopener"
               class="btn btn-ghost btn-sm">
                💬 WhatsApp
            </a>
        </div>
    </div>

    <!-- ── Statistika ────────────────────────────────────── -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="glass-card p-5 fade-up text-center">
            <p class="text-xs text-white/40 uppercase tracking-wide mb-2">
                <?= e(t('taklif_qilingan')) ?>
            </p>
            <p class="text-3xl font-display font-black tabnum">
                <?= (int)$stat['jami'] ?>
            </p>
            <p class="text-xs text-white/30 mt-1">jami foydalanuvchi</p>
        </div>

        <div class="glass-card p-5 fade-up text-center" style="animation-delay:.07s">
            <p class="text-xs text-white/40 uppercase tracking-wide mb-2">Tasdiqlangan</p>
            <p class="text-3xl font-display font-black text-emerald-400 tabnum">
                <?= (int)$stat['tasdiq_son'] ?>
            </p>
            <p class="text-xs text-white/30 mt-1">to'lov qilgan</p>
        </div>

        <div class="glass-card p-5 fade-up col-span-2 md:col-span-1 text-center"
             style="animation-delay:.14s">
            <p class="text-xs text-white/40 uppercase tracking-wide mb-2">
                <?= e(t('bonus_balans')) ?>
            </p>
            <p class="text-2xl font-display font-black text-amber-400 tabnum">
                <?= e(pul($f['bonus_balans'])) ?>
            </p>
            <p class="text-xs text-white/30 mt-1">to'lovda ishlatish mumkin</p>
        </div>
    </div>

    <!-- ── Taklif qilinganlar ro'yxati ───────────────────── -->
    <div class="glass-card overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-white/[0.06]">
            <h2 class="font-display font-bold text-base"><?= e(t('taklif_qilingan')) ?></h2>
        </div>

        <?php if (empty($royxat)): ?>
        <div class="py-16 text-center">
            <div class="text-5xl mb-4">👥</div>
            <p class="font-semibold text-white/50 mb-1">Hali hech kim taklif qilinmagan</p>
            <p class="text-sm text-white/30">Havolangizni do'stlaringizga ulashing!</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/[0.06] text-xs text-white/35 uppercase tracking-wide">
                        <th class="py-3 px-6 text-left font-medium">Foydalanuvchi</th>
                        <th class="py-3 px-4 text-left font-medium">Telefon</th>
                        <th class="py-3 px-4 text-left font-medium">Bonus</th>
                        <th class="py-3 px-4 text-left font-medium">Holat</th>
                        <th class="py-3 px-6 text-left font-medium">Sana</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($royxat as $r): ?>
                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.02] transition">
                        <td class="py-3 px-6 font-medium">
                            <?= e(trim($r['ism'] . ' ' . ($r['familiya'] ?? ''))) ?>
                        </td>
                        <td class="py-3 px-4 font-mono text-xs text-white/40">
                            <?= e(substr($r['telefon'], 0, 7) . '****' . substr($r['telefon'], -2)) ?>
                        </td>
                        <td class="py-3 px-4 font-semibold tabnum text-emerald-400">
                            <?= e(pul($r['bonus_summa'])) ?>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge <?= match($r['holat']) {
                                'tasdiq'    => 'badge-green',
                                'bekor'     => 'badge-red',
                                default     => 'badge-yellow'
                            } ?>">
                                <?= e($r['holat']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-xs text-white/35 tabnum">
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
