<?php
/**
 * VatanParvar Yaypan — Bildirishnomalar sahifasi
 *
 * Foydalanuvchining barcha bildirishnomalari ro'yxati.
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

// Barcha bildirishnomalarni o'qilgan deb belgilash (sahifa ochilganda)
db_bajar('UPDATE bildirishnomalar SET oqilgan = 1 WHERE foydalanuvchi_id = ?', [$f['id']]);

$ruyhat = db_barcha(
    'SELECT * FROM bildirishnomalar WHERE foydalanuvchi_id = ? ORDER BY yaratilgan DESC LIMIT 100',
    [$f['id']]
);

$sahifa_sarlavha = "Bildirishnomalar";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 py-6">

    <!-- Sarlavha -->
    <div class="flex items-center justify-between mb-6 fade-up">
        <div>
            <h1 class="text-2xl md:text-3xl font-display font-bold text-brand-text flex items-center gap-2">
                <span>🔔</span> Bildirishnomalar
            </h1>
            <p class="text-brand-muted text-sm mt-1">Sayt va admin bildirishnomalari shu yerda</p>
        </div>
        <?php if (!empty($ruyhat)): ?>
            <form method="POST" action="<?= e(SAYT_URL) ?>/api/bildirishnoma.php?action=ochirish" id="ochir-forma" class="hidden">
                <?= csrf_input() ?>
            </form>
            <button type="button" onclick="if(confirm('O\'qilgan bildirishnomalarni o\'chirishni xohlaysizmi?')) { window.apiPost('<?= e(SAYT_URL) ?>/api/bildirishnoma.php?action=ochirish').then(() => location.reload()); }"
                    class="btn-ghost text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Tozalash
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($ruyhat)): ?>
        <div class="glass-card p-12 text-center">
            <div class="text-6xl mb-4 floating">🔕</div>
            <h2 class="font-display font-bold text-xl text-brand-text mb-2">Bildirishnomalar yo'q</h2>
            <p class="text-brand-muted">Yangi bildirishnomalar bu yerda paydo bo'ladi</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($ruyhat as $i => $b):
                $rang = match ($b['tur']) {
                    'muvaffaqiyat'  => 'from-emerald-100 to-teal-100 text-emerald-700',
                    'ogohlantirish' => 'from-amber-100 to-orange-100 text-amber-700',
                    'xato'          => 'from-rose-100 to-pink-100 text-rose-700',
                    default         => 'from-sky-100 to-blue-100 text-sky-700',
                };
            ?>
                <a href="<?= e($b['link'] ?: '#') ?>"
                   class="block glass-card glass-card-hover p-4 fade-up" style="animation-delay: <?= 0.03 * $i ?>s">
                    <div class="flex gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $rang ?> flex items-center justify-center text-xl flex-shrink-0">
                            <?= e($b['ikon'] ?: '🔔') ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-brand-text mb-0.5"><?= e($b['sarlavha']) ?></div>
                            <?php if ($b['matn']): ?>
                                <div class="text-sm text-brand-muted leading-relaxed"><?= nl2br(e($b['matn'])) ?></div>
                            <?php endif; ?>
                            <div class="text-xs text-brand-light mt-1.5 flex items-center gap-2">
                                <?= e(vaqt_oldin($b['yaratilgan'])) ?>
                                <?php if ($b['link']): ?>
                                    <span>·</span>
                                    <span class="text-sky-600 font-semibold flex items-center gap-1">
                                        Ko'rish
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
