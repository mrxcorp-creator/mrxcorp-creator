<?php
/**
 * VatanParvar Yaypan — Brutalizm footer
 */
$_tel   = sozlama('aloqa_telefon', '');
$_email = sozlama('aloqa_email', '');
$_tg    = sozlama('telegram_kanal', '');
?>

<footer style="margin-top: 4rem; border-top: 1px solid #000; background: #fff;">
    <div style="max-width:1200px; margin:0 auto; padding: 3rem 1.25rem 2rem;">

        <div class="grid grid-cols-1 md:grid-cols-12" style="gap: 2.5rem; margin-bottom: 2rem;">

            <!-- Brand -->
            <div class="md:col-span-5">
                <a href="<?= e(SAYT_URL) ?>"
                   style="display:inline-flex; align-items:center; gap:.65rem;
                          text-decoration:none; margin-bottom:1rem;">
                    <span style="display:inline-flex; align-items:center; justify-content:center;
                                 width:38px; height:38px;
                                 border:1px solid #000; background:#fff;
                                 font-family:Georgia,serif; font-weight:700;
                                 font-size:1.05rem; color:#000;">V</span>
                    <span style="font-family:Georgia,serif; font-weight:700;
                                 font-size:1.1rem; color:#000;">
                        <?= e(SAYT_NOMI) ?>
                    </span>
                </a>
                <p style="font-size:.9rem; line-height:1.65; color:#000; max-width:22rem;">
                    <?= e(t('footer_haqida')) ?>
                </p>
            </div>

            <!-- Havolalar -->
            <div class="md:col-span-3">
                <h5 style="font-size:.78rem; font-weight:600; color:#000;
                           text-transform:uppercase; letter-spacing:.08em;
                           margin-bottom:1rem; padding-bottom:.5rem;
                           border-bottom: 1px solid #000;">
                    <?= e(t('havolalar')) ?>
                </h5>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:.55rem;">
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
                           style="font-size:.875rem; color:#000;
                                  text-decoration: underline; text-underline-offset: 4px;"
                           onmouseover="this.style.color='#666'"
                           onmouseout="this.style.color='#000'">
                            <?= e($nom) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Aloqa -->
            <div class="md:col-span-4">
                <h5 style="font-size:.78rem; font-weight:600; color:#000;
                           text-transform:uppercase; letter-spacing:.08em;
                           margin-bottom:1rem; padding-bottom:.5rem;
                           border-bottom: 1px solid #000;">
                    <?= e(t('aloqa')) ?>
                </h5>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:.55rem;">
                    <?php if ($_tel): ?>
                    <li>
                        <a href="tel:<?= e($_tel) ?>"
                           style="font-size:.875rem; color:#000;
                                  text-decoration:underline; text-underline-offset:4px;"
                           onmouseover="this.style.color='#666'"
                           onmouseout="this.style.color='#000'">
                            <?= e($_tel) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_email): ?>
                    <li>
                        <a href="mailto:<?= e($_email) ?>"
                           style="font-size:.875rem; color:#000;
                                  text-decoration:underline; text-underline-offset:4px;"
                           onmouseover="this.style.color='#666'"
                           onmouseout="this.style.color='#000'">
                            <?= e($_email) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_tg): ?>
                    <li>
                        <a href="<?= e($_tg) ?>" target="_blank" rel="noopener"
                           style="font-size:.875rem; color:#000;
                                  text-decoration:underline; text-underline-offset:4px;"
                           onmouseover="this.style.color='#666'"
                           onmouseout="this.style.color='#000'">
                            Telegram kanal →
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Alt qism -->
        <div style="border-top: 1px solid #000; padding-top: 1.5rem;
                    display:flex; flex-wrap:wrap; align-items:center;
                    justify-content:space-between; gap:1rem;">
            <p style="font-size:.8rem; color:#000;">
                © <?= date('Y') ?> <?= e(SAYT_NOMI) ?>. <?= e(t('barcha_huquqlar')) ?>
            </p>

            <!-- Til -->
            <div style="display:flex; align-items:center; gap:.5rem;">
                <span style="font-size:.78rem; color:#666;"><?= e(t('til_tanlash')) ?>:</span>
                <?php
                $joriy_til = $_SESSION['til'] ?? TIL_DEFAULT;
                $tillar    = ['uz_latn' => 'Latin', 'uz_cyrl' => 'Кирилл'];
                foreach ($tillar as $kod => $nom):
                    $faol = $joriy_til === $kod;
                ?>
                <a href="?til=<?= $kod ?>"
                   style="padding:.25rem .65rem; font-size:.78rem; font-weight:500;
                          text-decoration:none;
                          border:1px solid #000;
                          background: <?= $faol ? '#000' : '#fff' ?>;
                          color: <?= $faol ? '#fff' : '#000' ?>;
                          transition: all .15s;"
                   onmouseover="if(this.style.background==='rgb(255, 255, 255)'){this.style.background='#F5F5F5'}"
                   onmouseout="if(this.style.background==='rgb(245, 245, 245)'){this.style.background='#fff'}">
                    <?= $nom ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>

<!-- Global JavaScript -->
<script>
(function() {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    window.csrfToken = meta ? meta.content : '';

    window.apiPost = async function(url, data) {
        data = data || {};
        var fd = new FormData();
        fd.append('csrf_token', window.csrfToken);
        Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
        try {
            var r = await fetch(url, {
                method: 'POST', body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return await r.json();
        } catch(e) {
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };

    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') { e.preventDefault(); }
            if ((e.ctrlKey||e.metaKey) && 'usp'.indexOf(e.key.toLowerCase())>-1) e.preventDefault();
        });
        document.addEventListener('copy', function(e) { e.preventDefault(); });
    }
})();
</script>

</body>
</html>
