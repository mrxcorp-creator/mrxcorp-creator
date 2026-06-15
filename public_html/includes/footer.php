<?php
/**
 * AvtoTest Pro — Sahifa oxiri (footer + global JS)
 */
?>

<footer class="mt-20 border-t border-white/[0.07] pt-12 pb-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 mb-10">

            <!-- Logo & tavsif -->
            <div class="md:col-span-2">
                <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-2.5 mb-4">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-display font-black text-white text-lg shadow-glow-sm">A</span>
                    <span class="font-display font-bold text-white text-xl">AvtoTest <span class="text-blue-400">Pro</span></span>
                </a>
                <p class="text-brand-muted text-sm leading-relaxed max-w-sm">
                    <?= e(t('footer_haqida')) ?>
                </p>
                <div class="flex items-center gap-3 mt-4">
                    <?php $tg_kanal = sozlama('telegram_kanal'); if ($tg_kanal): ?>
                    <a href="<?= e($tg_kanal) ?>" target="_blank" rel="noopener noreferrer"
                       class="w-9 h-9 rounded-lg bg-sky-500/15 text-sky-400 hover:bg-sky-500/25 flex items-center justify-center transition"
                       aria-label="Telegram">
                        <svg class="w-4.5 h-4.5 w-[18px] h-[18px]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php $email = sozlama('aloqa_email'); if ($email): ?>
                    <a href="mailto:<?= e($email) ?>"
                       class="w-9 h-9 rounded-lg bg-green-500/15 text-green-400 hover:bg-green-500/25 flex items-center justify-center transition"
                       aria-label="Email">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Havolalar -->
            <div>
                <h4 class="text-white font-display font-semibold mb-4"><?= e(t('havolalar')) ?></h4>
                <ul class="space-y-2.5 text-sm text-brand-muted">
                    <li><a href="<?= e(SAYT_URL) ?>/" class="hover:text-white transition"><?= e(t('bosh_sahifa')) ?></a></li>
                    <li><a href="<?= e(SAYT_URL) ?>/#tariflar" class="hover:text-white transition"><?= e(t('tariflar')) ?></a></li>
                    <li><a href="<?= e(SAYT_URL) ?>/#fikrlar" class="hover:text-white transition"><?= e(t('fikrlar')) ?></a></li>
                    <li><a href="<?= e(SAYT_URL) ?>/register" class="hover:text-white transition"><?= e(t('royxatdan_otish')) ?></a></li>
                    <li><a href="<?= e(SAYT_URL) ?>/login" class="hover:text-white transition"><?= e(t('kirish')) ?></a></li>
                </ul>
            </div>

            <!-- Aloqa -->
            <div>
                <h4 class="text-white font-display font-semibold mb-4"><?= e(t('aloqa')) ?></h4>
                <ul class="space-y-2.5 text-sm text-brand-muted">
                    <?php $tel = sozlama('aloqa_telefon'); if ($tel): ?>
                    <li>
                        <a href="tel:<?= e($tel) ?>" class="hover:text-white transition flex items-center gap-2">
                            <span>📞</span><?= e($tel) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($email): ?>
                    <li>
                        <a href="mailto:<?= e($email) ?>" class="hover:text-white transition flex items-center gap-2">
                            <span>✉️</span><?= e($email) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($tg_kanal ?? false): ?>
                    <li>
                        <a href="<?= e($tg_kanal) ?>" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-2">
                            <span>📱</span>Telegram kanal
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quyi qator -->
        <div class="pt-6 border-t border-white/[0.07] flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-xs text-brand-muted">
                © <?= date('Y') ?> <span class="text-white"><?= e(SAYT_NOMI) ?></span>. <?= e(t('barcha_huquqlar')) ?>
            </p>
            <!-- Til tanlash -->
            <div class="flex items-center gap-1 text-xs">
                <span class="text-brand-muted mr-1"><?= e(t('til_tanlash')) ?>:</span>
                <?php
                $til_manzillari = ['uz_latn' => 'Lat', 'uz_cyrl' => 'Кир', 'ru' => 'Рус'];
                $joriy_til = $_SESSION['til'] ?? TIL_DEFAULT;
                foreach ($til_manzillari as $til_kod => $til_nom):
                    $faol = $joriy_til === $til_kod;
                ?>
                    <a href="?til=<?= $til_kod ?>"
                       class="px-2 py-1 rounded-md transition <?= $faol ? 'bg-blue-500/20 text-blue-400 font-semibold' : 'text-brand-muted hover:text-white' ?>">
                        <?= $til_nom ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>

<!-- ===== Global JavaScript ===== -->
<script>
(function() {
    'use strict';

    // CSRF token (AJAX uchun)
    window.csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /**
     * Universal AJAX POST yordamchisi.
     * @param {string} url
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    window.apiPost = async function(url, data = {}) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken);
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (err) {
            console.error('apiPost xato:', err);
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };

    // Test sahifasida anti-copy himoya
    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.key === 'F12') { e.preventDefault(); return; }
            if ((e.ctrlKey || e.metaKey) && 'usp'.includes(e.key.toLowerCase())) { e.preventDefault(); return; }
            if (e.ctrlKey && e.shiftKey && 'IJC'.includes(e.key)) { e.preventDefault(); }
        });
    }

    // Scroll animatsiyasi: IntersectionObserver bilan fade-up
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-up').forEach(el => {
            el.style.animationPlayState = 'paused';
            io.observe(el);
        });
    }
})();
</script>

</body>
</html>
