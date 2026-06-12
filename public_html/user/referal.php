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

<main class="max-w-5xl mx-auto px-4 py-10">
    <div class="mb-8 fade-up">
        <h1 class="text-3xl md:text-4xl mb-2"><?= e(t('referal_sarlavha')) ?></h1>
        <p class="text-app-2 text-base"><?= e(t('referal_tavsif')) ?></p>
    </div>

    <!-- Asosiy banner — havola va kod -->
    <div class="glass-card-premium p-7 mb-6 fade-up relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-60 h-60 rounded-full opacity-25 blur-3xl"
             style="background: var(--gradient-primary);"></div>
        <div class="absolute -bottom-20 -left-20 w-60 h-60 rounded-full opacity-15 blur-3xl"
             style="background: var(--gradient-warm);"></div>

        <div class="relative">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg flex-shrink-0"
                     style="background: var(--gradient-primary); box-shadow: 0 12px 32px var(--accent-glow);">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-display text-lg text-app">Har bir do'st uchun <span class="gradient-text font-extrabold"><?= e(pul($bonus_summa)) ?></span></div>
                    <div class="text-sm text-app-2">Do'stingiz to'lov qilganda bonusingiz balansga qo'shiladi</div>
                </div>
            </div>

            <div x-data="{nusxa: false}">
                <label class="field-label"><?= e(t('sizning_havolangiz')) ?></label>
                <div class="flex gap-2 flex-wrap sm:flex-nowrap">
                    <input type="text" readonly value="<?= e($havola) ?>"
                           class="field flex-1 font-mono text-xs sm:text-sm">
                    <button type="button"
                            @click="navigator.clipboard.writeText('<?= e($havola) ?>'); nusxa=true; setTimeout(()=>nusxa=false, 2000)"
                            class="btn-primary flex-shrink-0 min-w-[140px]"
                            :class="nusxa ? '!bg-emerald-600' : ''">
                        <span x-show="!nusxa" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                            <?= e(t('nusxa_olish')) ?>
                        </span>
                        <span x-show="nusxa" x-cloak class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            <?= e(t('nusxa_olindi')) ?>
                        </span>
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 items-center">
                    <span class="text-app-2 text-sm">Yoki ulashing:</span>
                    <a target="_blank" href="https://t.me/share/url?url=<?= urlencode($havola) ?>&text=<?= urlencode("Avto maktab nazariyasiga onlayn tayyorgarlik!") ?>"
                       class="btn-ghost text-sm py-2 px-3.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        Telegram
                    </a>
                    <a target="_blank" href="https://wa.me/?text=<?= urlencode($havola) ?>"
                       class="btn-ghost text-sm py-2 px-3.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.51 5.26l-.999 3.648 3.978-1.607zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.371-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistika kartalar -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20 blur-2xl"
                 style="background: linear-gradient(135deg, #3B82F6, #2563EB);"></div>
            <div class="relative">
                <div class="text-xs text-muted-app uppercase tracking-wider font-medium mb-2"><?= e(t('taklif_qilingan')) ?></div>
                <div class="text-3xl font-display font-extrabold text-app"><?= (int)$stat['jami'] ?></div>
            </div>
        </div>
        <div class="glass-card glass-card-hover p-5 fade-up relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-20 blur-2xl"
                 style="background: linear-gradient(135deg, #10B981, #16A34A);"></div>
            <div class="relative">
                <div class="text-xs text-muted-app uppercase tracking-wider font-medium mb-2">Tasdiqlangan</div>
                <div class="text-3xl font-display font-extrabold text-success"><?= (int)$stat['tasdiq_son'] ?></div>
            </div>
        </div>
        <div class="glass-card glass-card-hover p-5 fade-up col-span-2 md:col-span-1 relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full opacity-25 blur-2xl"
                 style="background: linear-gradient(135deg, #F59E0B, #EF4444);"></div>
            <div class="relative">
                <div class="text-xs text-muted-app uppercase tracking-wider font-medium mb-2"><?= e(t('bonus_balans')) ?></div>
                <div class="text-3xl font-display font-extrabold gradient-text"><?= e(pul($f['bonus_balans'])) ?></div>
            </div>
        </div>
    </div>

    <!-- Ro'yxat -->
    <div class="glass-card p-6 fade-up">
        <h2 class="font-display text-xl mb-5 text-app"><?= e(t('taklif_qilingan')) ?></h2>

        <?php if (empty($royxat)): ?>
            <div class="py-16 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4 opacity-50"
                     style="background: var(--bg-elevated);">
                    <svg class="w-8 h-8 text-app-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0z"/></svg>
                </div>
                <p class="text-app-2">Hali hech kim taklif qilinmagan.</p>
                <p class="text-app-2 text-sm mt-1">Havolani do'stlaringizga ulashing!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-app text-xs uppercase tracking-wider border-b border-app">
                            <th class="py-3 pr-3">Foydalanuvchi</th>
                            <th class="py-3 pr-3">Telefon</th>
                            <th class="py-3 pr-3">Bonus</th>
                            <th class="py-3 pr-3">Holat</th>
                            <th class="py-3">Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($royxat as $r):
                            $ism = trim(($r['ism'] ?? '') . ' ' . ($r['familiya'] ?? ''));
                            if (($_SESSION['til'] ?? 'uz_latn') === 'uz_cyrl') {
                                $ism = lotin_dan_kirill($ism);
                            }
                        ?>
                            <tr class="hover:bg-glass border-b border-app last:border-0">
                                <td class="py-3 pr-3 text-app font-medium"><?= e($ism) ?></td>
                                <td class="py-3 pr-3 font-mono text-xs text-app-2"><?= e(substr($r['telefon'], 0, 7) . '****' . substr($r['telefon'], -2)) ?></td>
                                <td class="py-3 pr-3 font-bold text-app"><?= e(pul($r['bonus_summa'])) ?></td>
                                <td class="py-3 pr-3">
                                    <span class="badge <?= $r['holat'] === 'tasdiq' ? 'badge-success' : ($r['holat'] === 'bekor' ? 'badge-error' : 'badge-warning') ?>">
                                        <?= e($r['holat']) ?>
                                    </span>
                                </td>
                                <td class="py-3 text-app-2 text-xs"><?= e(vaqt_oldin($r['yaratilgan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
