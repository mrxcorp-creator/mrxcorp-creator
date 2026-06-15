
<footer class="mt-24 border-t border-white/5 pt-12 pb-8 text-sm text-muted relative">
    <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-10">
        <div class="md:col-span-2">
            <a href="<?= e(SAYT_URL) ?>" class="flex items-center gap-3 mb-4">
                <img src="<?= e(SAYT_URL) ?>/assets/img/logo-mark.svg" alt="VatanParvar" class="w-10 h-10 rounded-xl" loading="lazy">
                <span class="font-display font-extrabold text-text text-xl">VatanParvar <span class="grad-text">Yaypan</span></span>
            </a>
            <p class="leading-relaxed max-w-md">
                <?= e(t('footer_haqida')) ?>
            </p>
            <div class="flex items-center gap-3 mt-5">
                <a href="<?= e(sozlama('telegram_kanal', '#')) ?>" target="_blank" rel="noopener"
                   class="w-10 h-10 rounded-xl flex items-center justify-center bg-cyan/10 text-cyan hover:bg-cyan/20 transition" aria-label="Telegram">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm5.6 8.2-1.9 8.8c-.1.6-.5.8-1 .5l-2.8-2.1-1.4 1.3c-.2.2-.3.3-.6.3l.2-2.9 5.3-4.8c.2-.2 0-.3-.3-.1l-6.6 4.2-2.8-.9c-.6-.2-.6-.6.1-.9l11-4.2c.5-.2.9.1.8.8z"/></svg>
                </a>
                <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>"
                   class="w-10 h-10 rounded-xl flex items-center justify-center bg-violet/10 text-violet hover:bg-violet/20 transition" aria-label="Telefon">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 0 1 1-1h2a1 1 0 0 1 .94.66l1.4 3.92a1 1 0 0 1-.32 1.13L5.2 9.07a11 11 0 0 0 5.74 5.74l1.36-1.83a1 1 0 0 1 1.13-.32l3.92 1.4A1 1 0 0 1 18 15v2a1 1 0 0 1-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                </a>
                <a href="mailto:<?= e(sozlama('aloqa_email')) ?>"
                   class="w-10 h-10 rounded-xl flex items-center justify-center bg-pink/10 text-pink hover:bg-pink/20 transition" aria-label="Email">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884 10 9.882l7.997-3.998A2 2 0 0 0 16 4H4a2 2 0 0 0-1.997 1.884z"/><path d="m18 8.118-8 4-8-4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.118z"/></svg>
                </a>
            </div>
        </div>

        <div>
            <h4 class="text-text font-display font-bold mb-4"><?= e(t('havolalar')) ?></h4>
            <ul class="space-y-2">
                <li><a href="<?= e(SAYT_URL) ?>/" class="hover:text-text transition"><?= e(t('bosh_sahifa')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/login" class="hover:text-text transition"><?= e(t('kirish')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/register" class="hover:text-text transition"><?= e(t('royxatdan_otish')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/#tariflar" class="hover:text-text transition"><?= e(t('tariflar')) ?></a></li>
                <li><a href="<?= e(SAYT_URL) ?>/#savollar" class="hover:text-text transition">FAQ</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-text font-display font-bold mb-4"><?= e(t('aloqa')) ?></h4>
            <ul class="space-y-2">
                <li><a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="hover:text-text transition"><?= e(sozlama('aloqa_telefon')) ?></a></li>
                <li><a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="hover:text-text transition break-all"><?= e(sozlama('aloqa_email')) ?></a></li>
                <li><a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="hover:text-text transition">Telegram kanal</a></li>
            </ul>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-10 pt-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-3">
        <p class="text-xs">© <?= date('Y') ?> <?= e(SAYT_NOMI) ?>. <?= e(t('barcha_huquqlar')) ?></p>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-muted mr-1"><?= e(t('til_tanlash')) ?>:</span>
            <a href="?til=uz_latn" class="px-3 py-1 rounded-lg transition <?= ($_SESSION['til'] ?? 'uz_latn') === 'uz_latn' ? 'grad-bg text-white font-semibold' : 'hover:bg-white/5' ?>">O'zbek</a>
            <a href="?til=uz_cyrl" class="px-3 py-1 rounded-lg transition <?= ($_SESSION['til'] ?? '') === 'uz_cyrl' ? 'grad-bg text-white font-semibold' : 'hover:bg-white/5' ?>">Ўзбек</a>
        </div>
    </div>
</footer>

</body>
</html>
