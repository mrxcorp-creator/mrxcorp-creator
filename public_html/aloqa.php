<?php
/**
 * VatanParvar Yaypan — Aloqa sahifasi
 *
 * So'rov qoldirish formasi + aloqa ma'lumotlari + xarita.
 */
require_once __DIR__ . '/config/auth.php';

$f = joriy_foydalanuvchi();

$sahifa_sarlavha = "Aloqa — " . t('sayt_nomi');
$sahifa_tavsif = "VatanParvar Yaypan bilan bog'lanish: telefon, email, manzil, so'rov qoldirish.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="relative max-w-7xl mx-auto px-4 pt-12 pb-8">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] rounded-full bg-gradient-to-br from-emerald-200 to-sky-200 opacity-25 blur-3xl -z-10" aria-hidden="true"></div>

    <div class="text-center fade-up">
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Aloqa</span>
        <h1 class="text-4xl md:text-6xl font-display font-bold text-brand-text mb-4">
            Biz bilan <span class="text-gradient">bog'laning</span>
        </h1>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">
            Savol bormi? So'rov qoldiring — tez orada javob beramiz
        </p>
    </div>
</section>

<!-- Aloqa kartalari -->
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="tel:<?= e(sozlama('aloqa_telefon')) ?>" class="glass-card glass-card-hover p-6 text-center group" data-animate>
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Telefon</div>
            <div class="font-display font-bold text-brand-text"><?= e(sozlama('aloqa_telefon')) ?></div>
        </a>

        <a href="mailto:<?= e(sozlama('aloqa_email')) ?>" class="glass-card glass-card-hover p-6 text-center group" data-animate style="animation-delay:.05s">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-violet-100 to-purple-100 text-violet-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Email</div>
            <div class="font-display font-bold text-brand-text text-sm break-all"><?= e(sozlama('aloqa_email')) ?></div>
        </a>

        <a href="<?= e(sozlama('telegram_kanal')) ?>" target="_blank" rel="noopener" class="glass-card glass-card-hover p-6 text-center group" data-animate style="animation-delay:.1s">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-sky-100 to-blue-100 text-sky-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Telegram</div>
            <div class="font-display font-bold text-brand-text">Kanal</div>
        </a>

        <div class="glass-card p-6 text-center" data-animate style="animation-delay:.15s">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 text-amber-600 flex items-center justify-center mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Ish vaqti</div>
            <div class="font-display font-bold text-brand-text text-sm"><?= e(sozlama('ish_vaqti', 'Du-Sha 09:00 - 18:00')) ?></div>
        </div>
    </div>
</section>

<!-- Forma + manzil -->
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-5 gap-6">

        <!-- Forma -->
        <div class="lg:col-span-3 glass-card p-6 md:p-8 fade-up">
            <h2 class="text-2xl md:text-3xl font-display font-bold text-brand-text mb-2">So'rov qoldiring</h2>
            <p class="text-brand-muted mb-6">Formani to'ldiring — biz tez orada siz bilan bog'lanamiz</p>

            <form id="sorov-forma"
                  x-data="sorovForma()"
                  @submit.prevent="yubor()"
                  class="space-y-4">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Ismingiz <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <input x-model="data.ism" name="ism" required minlength="2"
                                   value="<?= e($f ? trim($f['ism'] . ' ' . ($f['familiya'] ?? '')) : '') ?>"
                                   placeholder="Ali Aliyev" class="field pl-11">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Telefon <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </span>
                            <input x-model="data.telefon" name="telefon" type="tel" required
                                   value="<?= e($f['telefon'] ?? '') ?>"
                                   placeholder="+998 90 123 45 67" class="field pl-11">
                        </div>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Email (ixtiyoriy)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input x-model="data.email" name="email" type="email"
                                   value="<?= e($f['email'] ?? '') ?>"
                                   placeholder="email@misol.uz" class="field pl-11">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Mavzu</label>
                        <select x-model="data.mavzu" name="mavzu" class="field">
                            <option value="">— tanlang —</option>
                            <option>Tarif haqida ma'lumot</option>
                            <option>Texnik muammo</option>
                            <option>To'lov masalasi</option>
                            <option>Hamkorlik takliflari</option>
                            <option>Boshqa</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="field-label">Xabar <span class="text-rose-500">*</span></label>
                    <textarea x-model="data.xabar" name="xabar" required minlength="5" maxlength="2000" rows="5"
                              placeholder="O'zingizni qiziqtirayotgan savolingizni yozing..."
                              class="field"></textarea>
                    <div class="text-xs text-brand-muted mt-1 text-right">
                        <span x-text="data.xabar.length"></span> / 2000
                    </div>
                </div>

                <div x-show="xato" x-transition x-cloak
                     class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-start gap-2 animate-shake">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                    <span x-text="xato"></span>
                </div>

                <div x-show="muvaffaqiyat" x-transition x-cloak
                     class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2.5">
                    <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    <span x-text="muvaffaqiyat" class="font-medium"></span>
                </div>

                <button type="submit" class="btn-primary w-full py-3.5" :disabled="loading">
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? 'Yuborilmoqda...' : 'So\'rov yuborish'">So'rov yuborish</span>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </button>

                <p class="text-xs text-brand-muted text-center">
                    Maxfiylik kafolatlanadi. Ma'lumotlaringiz uchinchi shaxslarga berilmaydi.
                </p>
            </form>
        </div>

        <!-- Manzil + ijtimoiy tarmoqlar -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Manzil -->
            <div class="glass-card p-6 fade-up">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-100 to-pink-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-brand-muted uppercase tracking-wider mb-1">Manzil</div>
                        <div class="font-display font-bold text-brand-text"><?= e(sozlama('manzil', "Yaypan shahri, Farg'ona viloyati")) ?></div>
                    </div>
                </div>
            </div>

            <!-- Ijtimoiy tarmoqlar -->
            <div class="glass-card p-6 fade-up">
                <h3 class="font-display font-bold text-brand-text mb-3 text-sm uppercase tracking-wider">Bizni kuzating</h3>
                <div class="space-y-2">
                    <?php
                    $linklar = [
                        ['telegram_kanal', 'Telegram kanal', 'sky', '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.643.135-.953l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>'],
                        ['telegram_link', 'Bot bilan yozish', 'cyan', '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'],
                        ['instagram_link', 'Instagram', 'rose', '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>'],
                        ['youtube_link', 'YouTube', 'red', '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'],
                    ];
                    foreach ($linklar as [$kalit, $nom, $rang, $svg]):
                        $url = sozlama($kalit);
                        if (!$url) continue;
                    ?>
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener"
                           class="flex items-center gap-3 p-3 rounded-xl border border-brand-border hover:border-<?= $rang ?>-300 hover:bg-<?= $rang ?>-50 transition group">
                            <span class="w-9 h-9 rounded-lg bg-<?= $rang ?>-100 text-<?= $rang ?>-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <?= $svg ?>
                            </span>
                            <span class="font-medium text-brand-text flex-1"><?= e($nom) ?></span>
                            <svg class="w-4 h-4 text-brand-light group-hover:text-sky-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tezkor info -->
            <div class="glass-card p-6 bg-gradient-to-br from-sky-50 to-blue-50 fade-up">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white text-sky-600 flex items-center justify-center flex-shrink-0 shadow-soft">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-display font-bold text-brand-text mb-1">Tez javob beramiz</h4>
                        <p class="text-sm text-brand-muted">Odatda 1-3 soat ichida javob beramiz. Shoshilinch holatlarda telefon orqali bog'laning.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Xarita -->
<?php $xarita = sozlama('xarita_url'); ?>
<?php if ($xarita): ?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="text-center mb-8" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Xarita</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold text-brand-text">Bizni qanday topish mumkin</h2>
    </div>
    <div class="glass-card p-2 overflow-hidden" data-animate>
        <div class="rounded-2xl overflow-hidden aspect-[16/9] bg-sky-50">
            <iframe src="<?= e($xarita) ?>"
                    class="w-full h-full"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function sorovForma() {
    return {
        data: {
            ism: '<?= e($f ? trim($f['ism'] . ' ' . ($f['familiya'] ?? '')) : '') ?>',
            telefon: '<?= e($f['telefon'] ?? '') ?>',
            email: '<?= e($f['email'] ?? '') ?>',
            mavzu: '',
            xabar: '',
        },
        loading: false,
        xato: '',
        muvaffaqiyat: '',
        async yubor() {
            this.loading = true;
            this.xato = '';
            this.muvaffaqiyat = '';

            const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/sorov_qoldirish.php', this.data);

            this.loading = false;
            if (r.ok) {
                this.muvaffaqiyat = r.xabar;
                this.data = { ism: '', telefon: '', email: '', mavzu: '', xabar: '' };
            } else {
                this.xato = r.xato || 'Xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring';
            }
        }
    };
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
