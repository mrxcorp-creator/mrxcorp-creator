<?php
/**
 * AvtoTest Pro — Footer + global JS
 * FIX: Barcha o'zgaruvchilar xavfsiz null-check bilan
 */

// Sozlamalar oldindan yuklanishi kerak
$_tel   = sozlama('aloqa_telefon', '');
$_email = sozlama('aloqa_email', '');
$_tg    = sozlama('telegram_kanal', '');
?>

<footer class="relative mt-24 overflow-hidden">
    <!-- Top separator -->
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>

    <div class="max-w-7xl mx-auto px-4 pt-16 pb-8">
        <div class="grid md:grid-cols-12 gap-10 mb-12">

            <!-- Brand -->
            <div class="md:col-span-5">
                <a href="<?= e(SAYT_URL) ?>" class="inline-flex items-center gap-3 mb-5 group">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 flex items-center justify-center font-display font-black text-white text-lg flex-shrink-0 shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">A</span>
                    <span class="font-display font-bold text-white text-xl">AvtoTest <span class="text-blue-400">Pro</span></span>
                </a>
                <p class="text-sm text-white/50 leading-relaxed max-w-xs mb-6">
                    <?= e(t('footer_haqida')) ?>
                </p>
                <!-- Social links -->
                <div class="flex items-center gap-2">
                    <?php if ($_tg): ?>
                    <a href="<?= e($_tg) ?>" target="_blank" rel="noopener noreferrer"
                       class="w-9 h-9 rounded-lg bg-white/5 hover:bg-sky-500/15 hover:text-sky-400 text-white/40 flex items-center justify-center transition-all duration-200 hover:scale-105"
                       aria-label="Telegram">
                        <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($_email): ?>
                    <a href="mailto:<?= e($_email) ?>"
                       class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500/15 hover:text-emerald-400 text-white/40 flex items-center justify-center transition-all duration-200 hover:scale-105"
                       aria-label="Email">
                        <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($_tel): ?>
                    <a href="tel:<?= e($_tel) ?>"
                       class="w-9 h-9 rounded-lg bg-white/5 hover:bg-blue-500/15 hover:text-blue-400 text-white/40 flex items-center justify-center transition-all duration-200 hover:scale-105"
                       aria-label="Telefon">
                        <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Havolalar -->
            <div class="md:col-span-3">
                <h5 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-4"><?= e(t('havolalar')) ?></h5>
                <ul class="space-y-2.5">
                    <?php
                    $links = [
                        [SAYT_URL . '/', t('bosh_sahifa')],
                        [SAYT_URL . '/#tariflar', t('tariflar')],
                        [SAYT_URL . '/#fikrlar', t('fikrlar')],
                        [SAYT_URL . '/register', t('royxatdan_otish')],
                        [SAYT_URL . '/login', t('kirish')],
                    ];
                    foreach ($links as [$href, $nom]):
                    ?>
                    <li>
                        <a href="<?= e($href) ?>"
                           class="text-sm text-white/50 hover:text-white transition-colors duration-150 flex items-center gap-1.5 group">
                            <span class="w-1 h-1 rounded-full bg-white/20 group-hover:bg-blue-400 transition-colors"></span>
                            <?= e($nom) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Aloqa -->
            <div class="md:col-span-4">
                <h5 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-4"><?= e(t('aloqa')) ?></h5>
                <ul class="space-y-3">
                    <?php if ($_tel): ?>
                    <li>
                        <a href="tel:<?= e($_tel) ?>"
                           class="flex items-center gap-3 text-sm text-white/50 hover:text-white transition-colors group">
                            <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-500/20 transition">📞</span>
                            <span><?= e($_tel) ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_email): ?>
                    <li>
                        <a href="mailto:<?= e($_email) ?>"
                           class="flex items-center gap-3 text-sm text-white/50 hover:text-white transition-colors group">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-500/20 transition">✉️</span>
                            <span class="truncate"><?= e($_email) ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_tg): ?>
                    <li>
                        <a href="<?= e($_tg) ?>" target="_blank" rel="noopener"
                           class="flex items-center gap-3 text-sm text-white/50 hover:text-white transition-colors group">
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center flex-shrink-0 group-hover:bg-sky-500/20 transition">📱</span>
                            <span>Telegram kanal</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="pt-6 border-t border-white/[0.06] flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-white/30">
                © <?= date('Y') ?> <span class="text-white/50"><?= e(SAYT_NOMI) ?></span>.
                <?= e(t('barcha_huquqlar')) ?>
            </p>

            <!-- Til tanlash -->
            <div class="flex items-center gap-1 bg-white/[0.04] rounded-lg p-0.5">
                <?php
                $joriy_til = $_SESSION['til'] ?? TIL_DEFAULT;
                $tillar    = ['uz_latn' => 'Lat', 'uz_cyrl' => 'Кир', 'ru' => 'Рус'];
                foreach ($tillar as $kod => $nom):
                    $faol = $joriy_til === $kod;
                ?>
                <a href="?til=<?= $kod ?>"
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-150
                       <?= $faol ? 'bg-blue-500 text-white shadow-sm' : 'text-white/40 hover:text-white' ?>">
                    <?= $nom ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>

<!-- ═══════ GLOBAL JAVASCRIPT ═══════ -->
<script>
(function () {
    'use strict';

    /* CSRF tokeni — AJAX so'rovlar uchun */
    var meta = document.querySelector('meta[name="csrf-token"]');
    window.csrfToken = meta ? meta.content : '';

    /**
     * Universal POST yordamchisi (FormData bilan)
     * @param {string} url
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    window.apiPost = async function (url, data) {
        data = data || {};
        var fd = new FormData();
        fd.append('csrf_token', window.csrfToken);
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        try {
            var res = await fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (err) {
            console.error('[AvtoTest] apiPost xato:', err);
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };

    /* Test sahifasida copy-paste himoya */
    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F12') { e.preventDefault(); return; }
            if ((e.ctrlKey || e.metaKey) && 'USPusp'.indexOf(e.key) > -1) { e.preventDefault(); return; }
            if (e.ctrlKey && e.shiftKey && 'IJCijc'.indexOf(e.key) > -1) { e.preventDefault(); }
        });
        document.addEventListener('copy', function (e) { e.preventDefault(); });
        document.addEventListener('selectstart', function (e) { e.preventDefault(); });
    }

    /* IntersectionObserver: fade-up animatsiyalar */
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.fade-up').forEach(function (el) {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    }
})();
</script>

</body>
</html>
