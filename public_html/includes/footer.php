<?php
/**
 * VatanParvar Yaypan — Sahifa oxiri (footer)
 */
$joriy_til ??= ($_SESSION['til'] ?? 'uz_latn');
$bilan_til = function (string $til): string {
    $q = array_merge($_GET, ['til' => $til]);
    return '?' . http_build_query($q);
};
?>

<footer class="mt-24 border-t border-app pt-12 pb-8 text-sm text-app-2 relative">
    <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-8">

        <div class="md:col-span-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2.5 mb-4">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-bold text-white text-lg shadow-md"
                      style="background: var(--gradient-primary); box-shadow: 0 8px 24px var(--accent-glow);">V</span>
                <span class="font-display font-bold text-app text-lg">VatanParvar Yaypan</span>
            </a>
            <p class="leading-relaxed max-w-md text-app-2">
                <?= e(t('footer_haqida') ?: "Avto maktab nazariyasiga onlayn tayyorgarlik platformasi. Imtihonni birinchi urinishdan topshirish uchun barcha biletlar va testlar.") ?>
            </p>
        </div>

        <div>
            <h4 class="text-app font-display mb-4 text-base"><?= e(t('havolalar')) ?></h4>
            <ul class="space-y-2.5">
                <li><a href="<?= e(SAYT_URL) ?>/" class="hover:text-app transition"><?= e(t('bosh_sahifa')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/login" class="hover:text-app transition"><?= e(t('kirish')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/register" class="hover:text-app transition"><?= e(t('royxatdan_otish')) ?></a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-app font-display mb-4 text-base"><?= e(t('aloqa')) ?></h4>
            <ul class="space-y-2.5">
                <li><a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="hover:text-app transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z"/></svg>
                    <?= e(sozlama('aloqa_telefon')) ?>
                </a></li>
                <li><a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="hover:text-app transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    <?= e(sozlama('aloqa_email')) ?>
                </a></li>
                <li><a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="hover:text-app transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Telegram
                </a></li>
            </ul>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-10 pt-6 border-t border-app flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-app-2">© <?= date('Y') ?> <?= e(SAYT_NOMI) ?>. <?= e(t('barcha_huquqlar')) ?></p>
        <div class="flex gap-3 items-center">
            <span class="text-xs text-muted-app"><?= e(t('til_tanlash')) ?>:</span>
            <a href="<?= e($bilan_til('uz_latn')) ?>" class="text-sm transition <?= $joriy_til === 'uz_latn' ? 'text-app font-semibold' : 'text-app-2 hover:text-app' ?>">Lat</a>
            <span class="text-muted-app">|</span>
            <a href="<?= e($bilan_til('uz_cyrl')) ?>" class="text-sm transition <?= $joriy_til === 'uz_cyrl' ? 'text-app font-semibold' : 'text-app-2 hover:text-app' ?>">Кир</a>
        </div>
    </div>
</footer>

<script>
    // ----- Anti-copy va F12 himoyasi (test sahifasidagina) -----
    if (document.body.classList.contains('test-page')) {
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.key === 'F12') e.preventDefault();
            if ((e.ctrlKey || e.metaKey) && ['u','s','p'].includes(e.key.toLowerCase())) e.preventDefault();
            if (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key)) e.preventDefault();
        });
    }

    // ----- Global AJAX yordamchisi -----
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        window.csrfToken = meta ? meta.content : '';
    })();
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

    // ============================================================
    // LOTIN → KIRILL avto-transliteratsiya (admin formalar uchun)
    // <input data-translit="kirill_maydon_nomi"> bo'lsa, javobida
    // shu nomdagi input yoki textareani avto to'ldiradi.
    // Foydalanuvchi qo'lda kirill maydonni o'zgartirsa — to'xtaydi.
    // ============================================================
    (function () {
        const APOSTROFLAR = /[\u02BB\u02BC\u2019`]/g;

        function lotinDanKirill(matn) {
            if (!matn) return '';
            matn = matn.replace(APOSTROFLAR, "'");

            const apostrof = { "O'": 'Ў', "o'": 'ў', "G'": 'Ғ', "g'": 'ғ' };
            for (const k in apostrof) matn = matn.split(k).join(apostrof[k]);

            const kop = {
                'CH': 'Ч', 'SH': 'Ш', 'YO': 'Ё', 'YU': 'Ю', 'YA': 'Я', 'YE': 'Е', 'TS': 'Ц',
                'Ch': 'Ч', 'Sh': 'Ш', 'Yo': 'Ё', 'Yu': 'Ю', 'Ya': 'Я', 'Ye': 'Е', 'Ts': 'Ц',
                'ch': 'ч', 'sh': 'ш', 'yo': 'ё', 'yu': 'ю', 'ya': 'я', 'ye': 'е', 'ts': 'ц',
            };
            for (const k in kop) matn = matn.split(k).join(kop[k]);

            // So'z boshidagi "e" → "э"
            matn = matn.replace(/\bE/gu, 'Э').replace(/\be/gu, 'э');

            const bir = {
                'A':'А','a':'а','B':'Б','b':'б','D':'Д','d':'д','E':'Е','e':'е',
                'F':'Ф','f':'ф','G':'Г','g':'г','H':'Ҳ','h':'ҳ','I':'И','i':'и',
                'J':'Ж','j':'ж','K':'К','k':'к','L':'Л','l':'л','M':'М','m':'м',
                'N':'Н','n':'н','O':'О','o':'о','P':'П','p':'п','Q':'Қ','q':'қ',
                'R':'Р','r':'р','S':'С','s':'с','T':'Т','t':'т','U':'У','u':'у',
                'V':'В','v':'в','W':'В','w':'в','X':'Х','x':'х','Y':'Й','y':'й',
                'Z':'З','z':'з','C':'С','c':'с'
            };
            return matn.split('').map(ch => bir[ch] ?? ch).join('');
        }

        // Manba (lotin) maydon o'zgarsa — kirill juftini to'ldiramiz
        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!el.dataset || !el.dataset.translit) return;
            const cyrlNom = el.dataset.translit;
            const cyrlEl = document.querySelector('[name="' + cyrlNom + '"]');
            if (!cyrlEl) return;
            if (cyrlEl.dataset.translitManual === '1') return;
            cyrlEl.value = lotinDanKirill(el.value);
        });

        // Foydalanuvchi kirill maydonga qo'lda yozsa — manual rejimga o'tadi
        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!el.name) return;
            const manba = document.querySelector('[data-translit="' + el.name + '"]');
            if (!manba) return;
            if (e.isTrusted) {
                el.dataset.translitManual = '1';
            }
        });
    })();
</script>

</body>
</html>
