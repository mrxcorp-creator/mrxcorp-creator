<?php /** Imtihon boshlanish sahifasi */ ?>
<main class="max-w-4xl mx-auto px-4 py-8">

    <div class="text-center mb-8 fade-up">
        <div class="text-6xl mb-3 floating">🎓</div>
        <h1 class="text-3xl md:text-5xl font-display font-bold text-brand-text mb-3">
            Real <span class="text-gradient">Imtihon Simulyatori</span>
        </h1>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">
            Aniq YHXBB imtihoni qoidalari bilan o'zingizni sinab ko'ring
        </p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        <div class="glass-card p-5 text-center fade-up stagger-1">
            <div class="text-4xl mb-2">📝</div>
            <div class="text-2xl font-display font-bold text-brand-text"><?= $IMTIHON_SAVOL ?></div>
            <div class="text-xs text-brand-muted uppercase">savol</div>
        </div>
        <div class="glass-card p-5 text-center fade-up stagger-2">
            <div class="text-4xl mb-2">⏱️</div>
            <div class="text-2xl font-display font-bold text-brand-text"><?= $IMTIHON_VAQTI / 60 ?> daq</div>
            <div class="text-xs text-brand-muted uppercase">vaqt</div>
        </div>
        <div class="glass-card p-5 text-center fade-up stagger-3">
            <div class="text-4xl mb-2">⚠️</div>
            <div class="text-2xl font-display font-bold text-brand-text"><?= $IMTIHON_XATO_LIMIT ?></div>
            <div class="text-xs text-brand-muted uppercase">maks xato</div>
        </div>
    </div>

    <?php if ($jami > 0): ?>
        <div class="glass-card p-6 mb-6 fade-up">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display font-bold text-brand-text">Sizning tayyorlik darajangiz</h3>
                <span class="text-2xl font-display font-bold <?= $tayyorlik_foiz >= 80 ? 'text-emerald-600' : ($tayyorlik_foiz >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                    <?= $tayyorlik_foiz ?>%
                </span>
            </div>
            <div class="h-3 bg-brand-border rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r <?= $tayyorlik_foiz >= 80 ? 'from-emerald-400 to-teal-500' : ($tayyorlik_foiz >= 50 ? 'from-amber-400 to-orange-500' : 'from-rose-400 to-pink-500') ?> rounded-full transition-all duration-1000"
                     style="width: <?= $tayyorlik_foiz ?>%"></div>
            </div>
            <p class="text-xs text-brand-muted mt-2">
                <strong class="text-brand-text"><?= $otgan ?></strong> / <?= $jami ?> imtihondan o'tgan
            </p>
        </div>
    <?php endif; ?>

    <?php if (!$aktiv_obuna && $f['rol'] === 'user'): ?>
        <div class="glass-card p-6 mb-6 border-amber-300 bg-gradient-to-br from-amber-50 to-orange-50 fade-up">
            <div class="flex items-start gap-3">
                <span class="text-3xl">🔒</span>
                <div class="flex-1">
                    <h3 class="font-display font-bold text-brand-text">Imtihon rejimi pullik</h3>
                    <p class="text-sm text-brand-muted mb-3">Imtihondan o'tish uchun avval tarif sotib oling.</p>
                    <a href="<?= e(SAYT_URL) ?>/tolov" class="btn-primary text-sm">Tariflarni ko'rish</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <a href="?bosh=1"
           onclick="return confirm('Imtihonni boshlashga tayyormisiz?\n\n⏱️ <?= $IMTIHON_VAQTI/60 ?> daqiqa vaqtingiz bor\n⚠️ Faqat <?= $IMTIHON_XATO_LIMIT ?> ta xato qilishingiz mumkin');"
           class="btn-primary w-full justify-center text-lg py-5 mb-6 fade-up">
            🚀 Imtihonni Boshlash
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    <?php endif; ?>

    <?php if (!empty($oxirgi)): ?>
        <div class="glass-card p-6 fade-up">
            <h3 class="font-display font-bold text-brand-text mb-4">Oxirgi imtihonlar</h3>
            <div class="space-y-2">
                <?php foreach ($oxirgi as $r):
                    $r_otdimi = (int) ($r['otdimi'] ?? 0);
                ?>
                    <a href="?nat=<?= (int)$r['id'] ?>"
                       class="flex items-center justify-between p-3 rounded-xl border <?= $r_otdimi ? 'border-emerald-200 bg-emerald-50/30 hover:bg-emerald-50' : 'border-rose-200 bg-rose-50/30 hover:bg-rose-50' ?> transition group">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl flex items-center justify-center text-xl <?= $r_otdimi ? 'bg-emerald-100' : 'bg-rose-100' ?>">
                                <?= $r_otdimi ? '✅' : '❌' ?>
                            </span>
                            <div>
                                <div class="font-semibold text-brand-text">
                                    <?= $r_otdimi ? "O'tdi" : "O'tmadi" ?>
                                    <span class="text-brand-muted text-sm font-normal">— <?= (int)$r['togri_son'] ?> to'g'ri / <?= (int)$r['xato_son'] ?> xato</span>
                                </div>
                                <div class="text-xs text-brand-muted"><?= e(vaqt_oldin($r['tugagan'])) ?></div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-brand-light group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
