<?php /** Imtihon natija ko'rinishi */ ?>
<main class="max-w-4xl mx-auto px-4 py-8 fade-up">
    <a href="<?= e(SAYT_URL) ?>/imtihon" class="inline-flex items-center gap-1 text-brand-muted hover:text-sky-600 text-sm mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Imtihonlarga qaytish
    </a>

    <div class="glass-card p-8 md:p-12 text-center mb-6 relative overflow-hidden <?= $otdimi ? 'border-emerald-300' : 'border-rose-300' ?>">
        <div class="relative">
            <?php if ($otdimi): ?>
                <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-5xl mb-4 shadow-glow animate-pulse-soft">🎉</div>
                <h1 class="text-3xl md:text-5xl font-display font-bold text-emerald-700 mb-3">Tabriklaymiz!</h1>
                <p class="text-xl text-brand-text mb-2">Siz imtihondan muvaffaqiyatli o'tdingiz</p>
                <p class="text-brand-muted">Real imtihonga to'liq tayyorsiz. Omad tilaymiz! 🚗</p>
            <?php else: ?>
                <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center text-5xl mb-4 shadow-soft">😔</div>
                <h1 class="text-3xl md:text-5xl font-display font-bold text-rose-700 mb-3">Bu safar bo'lmadi</h1>
                <p class="text-xl text-brand-text mb-2"><?= $xato_son ?> ta xato qildingiz (limit: <?= $IMTIHON_XATO_LIMIT ?>)</p>
                <p class="text-brand-muted">Mashq qiling va qaytadan urinib ko'ring!</p>
            <?php endif; ?>

            <div class="grid grid-cols-3 gap-3 max-w-md mx-auto mt-6">
                <div class="glass-card p-3 bg-emerald-50/50">
                    <div class="text-2xl font-display font-bold text-emerald-700"><?= (int)$nat['togri_son'] ?></div>
                    <div class="text-xs text-emerald-600 uppercase">To'g'ri</div>
                </div>
                <div class="glass-card p-3 bg-rose-50/50">
                    <div class="text-2xl font-display font-bold text-rose-700"><?= $xato_son ?></div>
                    <div class="text-xs text-rose-600 uppercase">Xato</div>
                </div>
                <div class="glass-card p-3 bg-sky-50/50">
                    <div class="text-2xl font-display font-bold text-sky-700"><?= (int)$nat['umumiy_son'] ?></div>
                    <div class="text-xs text-sky-600 uppercase">Jami</div>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 mt-7">
                <a href="<?= e(SAYT_URL) ?>/imtihon?bosh=1" class="btn-primary">
                    <?= $otdimi ? "Yana sinab ko'rish" : "Qaytadan urinish" ?>
                </a>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost">Mashq qilish</a>
            </div>
        </div>
    </div>

    <h2 class="font-display font-bold text-xl text-brand-text mb-3">Savollar tahlili</h2>
    <?php foreach ($savollar as $i => $s):
        $j = $javoblar[$s['id']] ?? null;
        $togri = $j === $s['togri_javob'];
    ?>
        <div class="glass-card p-5 mb-3 <?= $togri ? 'border-emerald-300' : ($j ? 'border-rose-300' : 'border-amber-300') ?>" data-animate>
            <div class="flex gap-3 mb-3">
                <span class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold
                    <?= $togri ? 'bg-emerald-100 text-emerald-700' : ($j ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') ?>">
                    <?= $i + 1 ?>
                </span>
                <p class="flex-1 font-medium text-brand-text"><?= e($s['matn']) ?></p>
            </div>
            <?php if ($s['rasm'] && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>" class="ml-12 mt-2 mb-3 rounded-lg max-w-md w-full">
            <?php endif; ?>
            <div class="grid sm:grid-cols-2 gap-2 ml-12">
                <?php foreach (['a','b','c','d'] as $v):
                    $matn = $s['variant_' . $v] ?? null;
                    if (!$matn) continue;
                    $bu_togri = $v === $s['togri_javob'];
                    $bu_javob = $v === $j;
                ?>
                    <div class="p-2.5 rounded-lg text-sm border
                        <?= $bu_togri ? 'bg-emerald-50 border-emerald-300' : ($bu_javob ? 'bg-rose-50 border-rose-300' : 'bg-white border-brand-border') ?>">
                        <span class="font-bold mr-2 uppercase"><?= $v ?>)</span><?= e($matn) ?>
                        <?php if ($bu_togri): ?> ✓<?php elseif ($bu_javob): ?> ✗<?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($s['izoh'])): ?>
                <div class="ml-12 mt-3 p-3 rounded-lg bg-sky-50 border border-sky-200 text-sm">
                    <strong class="text-sky-700">💡 Izoh:</strong> <?= e($s['izoh']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</main>
