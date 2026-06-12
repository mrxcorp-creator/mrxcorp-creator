<?php
/**
 * VatanParvar Yaypan — Sahifa oxiri (footer)
 * Yorqin (oq + havorang) tema.
 */
?>

<footer class="mt-24 relative">
    <!-- Yumshoq gradient ajratuvchi -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-px bg-gradient-to-r from-transparent via-sky-300 to-transparent"></div>

    <div class="bg-gradient-to-b from-transparent via-sky-50/40 to-sky-50/80 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4">

            <!-- Asosiy grid -->
            <div class="grid md:grid-cols-4 gap-10 mb-10">

                <!-- Logo + tavsif -->
                <div class="md:col-span-2">
                    <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5 mb-4 group">
                        <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-sky-400 via-sky-500 to-blue-600 flex items-center justify-center font-display font-bold text-white text-lg shadow-soft group-hover:shadow-glow transition-all">V</span>
                        <span class="font-display font-bold text-brand-text text-xl">VatanParvar Yaypan</span>
                    </a>
                    <p class="leading-relaxed max-w-md text-brand-muted text-sm">
                        <?= e(t('footer_haqida') ?: "Avto maktab nazariyasiga onlayn tayyorgarlik platformasi. Imtihonni birinchi urinishdan topshirish uchun barcha biletlar va testlar.") ?>
                    </p>

                    <!-- Ijtimoiy tarmoqlar -->
                    <div class="flex gap-2 mt-5">
                        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener"
                           class="w-10 h-10 rounded-xl bg-white border border-brand-border flex items-center justify-center text-sky-600 hover:bg-sky-500 hover:text-white hover:border-sky-500 hover:-translate-y-0.5 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005-.001l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.643.135-.953l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>
                        </a>
                        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>"
                           class="w-10 h-10 rounded-xl bg-white border border-brand-border flex items-center justify-center text-emerald-600 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 hover:-translate-y-0.5 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </a>
                        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>"
                           class="w-10 h-10 rounded-xl bg-white border border-brand-border flex items-center justify-center text-violet-600 hover:bg-violet-500 hover:text-white hover:border-violet-500 hover:-translate-y-0.5 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Havolalar -->
                <div>
                    <h4 class="text-brand-text font-display font-bold mb-4 text-sm uppercase tracking-wider"><?= e(t('havolalar')) ?></h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="<?= e(SAYT_URL) ?>/" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2 group"><span class="w-1 h-1 rounded-full bg-sky-400 group-hover:w-3 transition-all"></span><?= e(t('bosh_sahifa')) ?></a></li>
                        <li><a href="<?= e(SAYT_URL) ?>/login" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2 group"><span class="w-1 h-1 rounded-full bg-sky-400 group-hover:w-3 transition-all"></span><?= e(t('kirish')) ?></a></li>
                        <li><a href="<?= e(SAYT_URL) ?>/register" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2 group"><span class="w-1 h-1 rounded-full bg-sky-400 group-hover:w-3 transition-all"></span><?= e(t('royxatdan_otish')) ?></a></li>
                        <li><a href="<?= e(SAYT_URL) ?>/#tariflar" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2 group"><span class="w-1 h-1 rounded-full bg-sky-400 group-hover:w-3 transition-all"></span><?= e(t('tariflar')) ?></a></li>
                    </ul>
                </div>

                <!-- Aloqa -->
                <div>
                    <h4 class="text-brand-text font-display font-bold mb-4 text-sm uppercase tracking-wider"><?= e(t('aloqa')) ?></h4>
                    <ul class="space-y-2.5 text-sm">
                        <li>
                            <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?= e(sozlama('aloqa_telefon')) ?>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <?= e(sozlama('aloqa_email')) ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="text-brand-muted hover:text-sky-600 transition flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.643.135-.953l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>
                                Telegram kanal
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Pastki qator -->
            <div class="pt-6 border-t border-brand-border flex flex-col sm:flex-row justify-between items-center gap-4 text-sm">
                <p class="text-brand-muted">
                    © <?= date('Y') ?> <span class="font-semibold text-brand-text"><?= e(SAYT_NOMI) ?></span>. <?= e(t('barcha_huquqlar')) ?>
                </p>
                <div class="flex gap-1.5 items-center bg-white rounded-xl p-1 border border-brand-border shadow-sm">
                    <span class="text-xs text-brand-muted px-2"><?= e(t('til_tanlash')) ?>:</span>
                    <a href="?til=uz_latn" class="px-3 py-1 rounded-lg text-xs font-semibold transition <?= ($_SESSION['til'] ?? '') === 'uz_latn' ? 'bg-gradient-to-r from-sky-500 to-blue-500 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50' ?>">Lat</a>
                    <a href="?til=uz_cyrl" class="px-3 py-1 rounded-lg text-xs font-semibold transition <?= ($_SESSION['til'] ?? '') === 'uz_cyrl' ? 'bg-gradient-to-r from-sky-500 to-blue-500 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50' ?>">Кир</a>
                    <a href="?til=ru" class="px-3 py-1 rounded-lg text-xs font-semibold transition <?= ($_SESSION['til'] ?? '') === 'ru' ? 'bg-gradient-to-r from-sky-500 to-blue-500 text-white shadow-soft' : 'text-brand-muted hover:bg-sky-50' ?>">Рус</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
    // ----- Anti-copy va F12 himoyasi (faqat test sahifasida) -----
    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.key === 'F12') e.preventDefault();
            if ((e.ctrlKey || e.metaKey) && ['u','s','p'].includes(e.key.toLowerCase())) e.preventDefault();
            if (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key)) e.preventDefault();
        });
    }

    // ----- Global AJAX yordamchisi -----
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.apiPost = async (url, data = {}) => {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken);
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));
        try {
            const r = await fetch(url, { method: 'POST', body: formData, credentials: 'same-origin' });
            return await r.json();
        } catch (e) {
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };

    // ----- Scroll-da fade-up animatsiyasi -----
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-up');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '50px' });

        document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
    }
</script>

</body>
</html>
