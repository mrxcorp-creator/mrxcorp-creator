<?php
/**
 * VatanParvar Yaypan — Sahifa oxiri (footer)
 */
?>

<footer class="mt-20 border-t border-white/10 pt-10 pb-8 text-sm text-brand-muted">
    <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-8">
        <div class="md:col-span-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-2 mb-3">
                <span class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-display font-bold text-white">V</span>
                <span class="font-display font-bold text-white text-lg">VatanParvar Yaypan</span>
            </a>
            <p class="leading-relaxed max-w-md">
                <?= e(t('footer_haqida') ?: "Avto maktab nazariyasiga onlayn tayyorgarlik platformasi. Imtihonni birinchi urinishdan topshirish uchun barcha biletlar va testlar.") ?>
            </p>
        </div>
        <div>
            <h4 class="text-white font-display mb-3"><?= e(t('havolalar')) ?></h4>
            <ul class="space-y-2">
                <li><a href="<?= e(SAYT_URL) ?>/" class="hover:text-white transition"><?= e(t('bosh_sahifa')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/login" class="hover:text-white transition"><?= e(t('kirish')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/register" class="hover:text-white transition"><?= e(t('royxatdan_otish')) ?></a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-display mb-3"><?= e(t('aloqa')) ?></h4>
            <ul class="space-y-2">
                <li><a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="hover:text-white transition"><?= e(sozlama('aloqa_telefon')) ?></a></li>
                <li><a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="hover:text-white transition"><?= e(sozlama('aloqa_email')) ?></a></li>
                <li><a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="hover:text-white transition">Telegram kanal</a></li>
            </ul>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 mt-8 pt-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-3">
        <p>© <?= date('Y') ?> <?= e(SAYT_NOMI) ?>. <?= e(t('barcha_huquqlar')) ?></p>
        <div class="flex gap-3 items-center">
            <span class="text-xs"><?= e(t('til_tanlash')) ?>:</span>
            <a href="?til=uz_latn" class="hover:text-white <?= ($_SESSION['til'] ?? '') === 'uz_latn' ? 'text-white font-semibold' : '' ?>">Lat</a>
            <span class="text-white/20">|</span>
            <a href="?til=uz_cyrl" class="hover:text-white <?= ($_SESSION['til'] ?? '') === 'uz_cyrl' ? 'text-white font-semibold' : '' ?>">Кир</a>
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
            // Agar foydalanuvchi kirill maydonga qo'lda yozgan bo'lsa va belgilagan bo'lsa — tegmaymiz
            if (cyrlEl.dataset.translitManual === '1') return;
            cyrlEl.value = lotinDanKirill(el.value);
        });

        // Foydalanuvchi kirill maydonga qo'lda yozsa — manual rejimga o'tadi
        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!el.name) return;
            // Bu element boshqa elementning translit maqsadimi?
            const manba = document.querySelector('[data-translit="' + el.name + '"]');
            if (!manba) return;
            // Avval yozilgan bo'lsa va auto bilan tugri kelmasa — manual rejim
            if (e.isTrusted) {
                el.dataset.translitManual = '1';
            }
        });
    })();
</script>

</body>
</html>
