<?php
/**
 * VatanParvar Yaypan — Footer + Global JS
 * Rus tili olib tashlandi. Light/dark toggle qo'shildi.
 */
$_tel   = sozlama('aloqa_telefon', '');
$_email = sozlama('aloqa_email', '');
$_tg    = sozlama('telegram_kanal', '');
?>

<footer style="margin-top:5rem; border-top: 1px solid var(--border); padding-top: 3rem; padding-bottom: 2rem;">
    <div style="max-width:1280px; margin:0 auto; padding:0 1.25rem;">

        <div style="display:grid; grid-template-columns: 1fr; gap:2.5rem;"
             class="sm:grid-cols-2 lg:grid-cols-12">

            <!-- Brand -->
            <div class="lg:col-span-5">
                <a href="<?= e(SAYT_URL) ?>" style="display:inline-flex; align-items:center; gap:.75rem; margin-bottom:1rem; text-decoration:none;">
                    <span style="width:40px;height:40px;border-radius:12px;
                                 background:linear-gradient(135deg,#2563EB,#7C3AED);
                                 display:flex;align-items:center;justify-content:center;
                                 font-weight:900;color:#fff;font-size:1.1rem;
                                 box-shadow:0 4px 14px rgba(37,99,235,0.35);">V</span>
                    <span style="font-weight:800;font-size:1.2rem;color:var(--text);">
                        <?= e(SAYT_NOMI) ?>
                    </span>
                </a>
                <p style="font-size:.875rem;color:var(--muted);line-height:1.7;max-width:22rem;">
                    <?= e(t('footer_haqida')) ?>
                </p>

                <!-- Ijtimoiy havolalar -->
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <?php if ($_tg): ?>
                    <a href="<?= e($_tg) ?>" target="_blank" rel="noopener"
                       aria-label="Telegram"
                       class="theme-btn" style="text-decoration:none;">
                        <svg style="width:17px;height:17px;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.93 6.627-1.676 7.908c-.127.555-.46.69-.93.43l-2.57-1.895-1.24 1.195c-.138.137-.253.253-.52.253l.186-2.628 4.8-4.336c.21-.186-.044-.29-.323-.103l-5.934 3.736-2.557-.797c-.555-.172-.566-.555.117-.823l9.97-3.843c.464-.168.87.106.677.903z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($_email): ?>
                    <a href="mailto:<?= e($_email) ?>"
                       aria-label="Email"
                       class="theme-btn" style="text-decoration:none;">
                        <svg style="width:17px;height:17px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Havolalar -->
            <div class="lg:col-span-3">
                <h5 style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:1rem;">
                    <?= e(t('havolalar')) ?>
                </h5>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:.6rem;">
                    <?php
                    $links = [
                        [SAYT_URL.'/',          t('bosh_sahifa')],
                        [SAYT_URL.'/#tariflar', t('tariflar')],
                        [SAYT_URL.'/#fikrlar',  t('fikrlar')],
                        [SAYT_URL.'/register',  t('royxatdan_otish')],
                        [SAYT_URL.'/login',     t('kirish')],
                    ];
                    foreach ($links as [$href, $nom]):
                    ?>
                    <li>
                        <a href="<?= e($href) ?>"
                           style="font-size:.875rem;color:var(--muted);text-decoration:none;
                                  display:flex;align-items:center;gap:.5rem;transition:color .15s;"
                           onmouseover="this.style.color='var(--text)'"
                           onmouseout="this.style.color='var(--muted)'">
                            <span style="width:4px;height:4px;border-radius:50%;background:var(--border2);flex-shrink:0;"></span>
                            <?= e($nom) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Aloqa -->
            <div class="lg:col-span-4">
                <h5 style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:1rem;">
                    <?= e(t('aloqa')) ?>
                </h5>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:.75rem;">
                    <?php if ($_tel): ?>
                    <li>
                        <a href="tel:<?= e($_tel) ?>"
                           style="display:flex;align-items:center;gap:.75rem;font-size:.875rem;color:var(--muted);text-decoration:none;transition:color .15s;"
                           onmouseover="this.style.color='var(--text)'"
                           onmouseout="this.style.color='var(--muted)'">
                            <span style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.1);color:#60A5FA;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;">📞</span>
                            <?= e($_tel) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_email): ?>
                    <li>
                        <a href="mailto:<?= e($_email) ?>"
                           style="display:flex;align-items:center;gap:.75rem;font-size:.875rem;color:var(--muted);text-decoration:none;transition:color .15s;"
                           onmouseover="this.style.color='var(--text)'"
                           onmouseout="this.style.color='var(--muted)'">
                            <span style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.1);color:#34D399;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;">✉️</span>
                            <?= e($_email) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_tg): ?>
                    <li>
                        <a href="<?= e($_tg) ?>" target="_blank" rel="noopener"
                           style="display:flex;align-items:center;gap:.75rem;font-size:.875rem;color:var(--muted);text-decoration:none;transition:color .15s;"
                           onmouseover="this.style.color='var(--text)'"
                           onmouseout="this.style.color='var(--muted)'">
                            <span style="width:32px;height:32px;border-radius:8px;background:rgba(6,182,212,0.1);color:#67E8F9;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;">📱</span>
                            Telegram kanal
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Alt qator -->
        <div style="margin-top:2.5rem;padding-top:1.5rem;
                    border-top:1px solid var(--border);
                    display:flex;flex-wrap:wrap;
                    align-items:center;justify-content:space-between;gap:1rem;">

            <p style="font-size:.8rem;color:var(--muted);">
                © <?= date('Y') ?> <strong style="color:var(--text2)"><?= e(SAYT_NOMI) ?></strong>.
                <?= e(t('barcha_huquqlar')) ?>
            </p>

            <!-- Til + Tema toggli -->
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">

                <!-- Til tanlash (faqat 2 til: lotin + kiril) -->
                <div style="display:flex;align-items:center;background:var(--surface2);
                            border:1px solid var(--border);border-radius:9px;padding:.25rem;gap:.25rem;">
                    <span style="font-size:.72rem;color:var(--muted);padding:0 .4rem;"><?= e(t('til_tanlash')) ?>:</span>
                    <?php
                    $joriy_til = $_SESSION['til'] ?? TIL_DEFAULT;
                    $tillar    = ['uz_latn' => 'Lat', 'uz_cyrl' => 'Кир'];
                    foreach ($tillar as $kod => $nom):
                        $faol = $joriy_til === $kod;
                    ?>
                    <a href="?til=<?= $kod ?>"
                       style="padding:.3rem .65rem;border-radius:7px;font-size:.78rem;font-weight:600;
                              text-decoration:none;transition:all .15s;
                              <?= $faol
                                  ? 'background:var(--blue);color:#fff;'
                                  : 'color:var(--muted);' ?>"
                       <?= !$faol ? 'onmouseover="this.style.color=\'var(--text)\'" onmouseout="this.style.color=\'var(--muted)\'"' : '' ?>>
                        <?= $nom ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Light/Dark toggle -->
                <button id="theme-toggle"
                        onclick="toggleTheme()"
                        class="theme-btn"
                        title="Tema almashtirish">
                    <span id="theme-icon">🌙</span>
                </button>
            </div>
        </div>
    </div>
</footer>

<!-- ═══════════ GLOBAL JAVASCRIPT ═══════════ -->
<script>
(function() {
    'use strict';

    /* ── CSRF va apiPost ── */
    var m = document.querySelector('meta[name="csrf-token"]');
    window.csrfToken = m ? m.content : '';

    window.apiPost = async function(url, data) {
        data = data || {};
        var fd = new FormData();
        fd.append('csrf_token', window.csrfToken);
        Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
        try {
            var res = await fetch(url, {
                method: 'POST', body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch(e) {
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };

    /* ── TEMA boshqaruvi ── */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('vpy_theme', theme);
        var icon = document.getElementById('theme-icon');
        if (icon) icon.textContent = theme === 'dark' ? '🌙' : '☀️';
        // Meta theme-color yangilash
        var meta = document.getElementById('meta-theme');
        if (meta) meta.setAttribute('content', theme === 'dark' ? '#0A0F1E' : '#F0F4FF');
    }

    window.toggleTheme = function() {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    };

    // Sahifa yuklanganda to'g'ri ikonani o'rnatish
    document.addEventListener('DOMContentLoaded', function() {
        var theme = localStorage.getItem('vpy_theme') || 'dark';
        applyTheme(theme);
    });

    /* ── Test sahifasida copy-paste himoya ── */
    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') { e.preventDefault(); return; }
            if ((e.ctrlKey || e.metaKey) && 'usp'.indexOf(e.key.toLowerCase()) > -1) {
                e.preventDefault();
            }
        });
        document.addEventListener('copy', function(e) { e.preventDefault(); });
    }

    /* ── IntersectionObserver: fade-up ── */
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) {
                    e.target.style.animationPlayState = 'running';
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.fade-up').forEach(function(el) {
            el.style.animationPlayState = 'paused';
            io.observe(el);
        });
    }
})();
</script>

</body>
</html>
