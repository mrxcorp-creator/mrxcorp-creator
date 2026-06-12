<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi chat sahifasi
 *
 * - AI yordamchi (admin javob bermagan paytda)
 * - Admin javob bersa, AI o'chiriladi va admin nomi ko'rinadi
 * - Mobil va desktop uchun moslashtirilgan
 * - localStorage'da xabar qoralamasi saqlanadi
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$sahifa_sarlavha = "Yordam — chat";
$body_class = 'chat-page';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
    /* Mobilda navbar/footer chat ekranni eyamsin uchun */
    .chat-page footer { display: none; }
    .chat-cont { height: calc(100vh - 4rem); }
    @media (min-width: 1024px) { .chat-cont { height: calc(100vh - 6rem); max-height: 800px; } }

    /* Xabar bubble'lari */
    .bubble-user {
        background: linear-gradient(135deg, #38BDF8, #0284C7);
        color: white;
        border-radius: 1.25rem 1.25rem 0.35rem 1.25rem;
    }
    .bubble-admin {
        background: white;
        color: #0F172A;
        border: 1.5px solid #E2E8F0;
        border-radius: 1.25rem 1.25rem 1.25rem 0.35rem;
    }
    .bubble-ai {
        background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(56,189,248,0.08));
        color: #0F172A;
        border: 1.5px solid rgba(139,92,246,0.20);
        border-radius: 1.25rem 1.25rem 1.25rem 0.35rem;
    }

    /* Typing indikator */
    .typing-dot {
        display: inline-block; width: 6px; height: 6px;
        margin: 0 1.5px; border-radius: 50%; background: #94A3B8;
        animation: bounce 1.2s infinite ease-in-out;
    }
    .typing-dot:nth-child(2) { animation-delay: .15s; }
    .typing-dot:nth-child(3) { animation-delay: .3s; }
    @keyframes bounce {
        0%,80%,100% { transform: translateY(0); opacity: .4; }
        40% { transform: translateY(-6px); opacity: 1; }
    }

    /* Linklar bubble ichida */
    .bubble a { text-decoration: underline; }
    .bubble-user a { color: #E0F2FE; }
    .bubble-admin a, .bubble-ai a { color: #0EA5E9; }
</style>

<div class="chat-cont max-w-4xl mx-auto px-2 sm:px-4 lg:py-6 flex flex-col"
     x-data="chatPanel()" @keydown.window.escape="$refs.matn?.focus()">

    <!-- Header -->
    <div class="bg-white border-b border-brand-border lg:border lg:rounded-t-2xl px-4 py-3 flex items-center gap-3 lg:shadow-soft">
        <a href="<?= e(SAYT_URL) ?>/dashboard"
           class="p-2 rounded-lg hover:bg-sky-50 -ml-1 lg:hidden text-brand-text" aria-label="Ortga">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>

        <!-- Suhbat avatari -->
        <div class="relative">
            <template x-if="adminAktiv && adminInfo">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white shadow-soft text-base">
                    <span x-text="adminInfo.harf"></span>
                </div>
            </template>
            <template x-if="!adminAktiv">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-400 to-sky-500 flex items-center justify-center text-xl shadow-soft">
                    🤖
                </div>
            </template>
            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
        </div>

        <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-brand-text truncate"
                 x-text="adminAktiv && adminInfo ? adminInfo.tolaq + ' (admin)' : 'AI yordamchi'"></div>
            <div class="text-xs text-brand-muted flex items-center gap-1.5">
                <span x-show="!yozmoqda" class="text-emerald-600 font-medium">● Onlayn</span>
                <span x-show="yozmoqda" class="text-sky-600 font-medium">yozmoqda...</span>
            </div>
        </div>

        <button @click="tarixOchir()" x-show="xabarlar.length > 0"
                class="p-2 rounded-lg hover:bg-rose-50 text-brand-muted hover:text-rose-600 transition" title="Tarixni tozalash">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>

    <!-- Xabarlar bo'limi -->
    <div class="flex-1 overflow-y-auto bg-gradient-to-b from-sky-50/30 to-white lg:border-l lg:border-r border-brand-border px-3 sm:px-5 py-4"
         x-ref="xabarlarKonteyneri" id="xabarlarKonteyneri">

        <!-- Yuklanish skeleton -->
        <template x-if="boshYuklanmoqda">
            <div class="space-y-3 animate-pulse">
                <div class="flex justify-start"><div class="shimmer w-3/4 h-12 rounded-2xl"></div></div>
                <div class="flex justify-end"><div class="shimmer w-2/3 h-10 rounded-2xl"></div></div>
                <div class="flex justify-start"><div class="shimmer w-3/4 h-16 rounded-2xl"></div></div>
            </div>
        </template>

        <!-- Salomlashish (xabar yo'q bo'lsa) -->
        <template x-if="!boshYuklanmoqda && xabarlar.length === 0">
            <div class="text-center py-10">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-br from-violet-100 to-sky-100 flex items-center justify-center text-4xl mb-4 shadow-soft floating">🤖</div>
                <h2 class="text-xl font-display font-bold text-brand-text mb-2">Salom, <?= e($f['ism']) ?>! 👋</h2>
                <p class="text-brand-muted text-sm max-w-md mx-auto mb-6">
                    Men sizning AI yordamchingizman. Tariflar, testlar, to'lov yoki yo'l qoidalari haqida savol bering.
                    Murakkab masala bo'lsa, adminimiz ham qo'shiladi.
                </p>
                <div class="flex flex-wrap gap-2 justify-center">
                    <template x-for="t in tavsiyalar" :key="t">
                        <button @click="matn = t; $refs.matn.focus()"
                                class="px-3 py-2 rounded-xl bg-white border border-brand-border hover:border-sky-300 hover:bg-sky-50 text-sm transition" x-text="t"></button>
                    </template>
                </div>
            </div>
        </template>

        <!-- Xabarlar -->
        <div class="space-y-3">
            <template x-for="(xabar, i) in xabarlar" :key="xabar.id">
                <div>
                    <!-- Sana ajratuvchi -->
                    <template x-if="kunOzgarganmi(i)">
                        <div class="flex items-center gap-3 my-4">
                            <div class="flex-1 h-px bg-brand-border"></div>
                            <span class="text-xs text-brand-muted font-medium" x-text="xabar.sana.split(' ')[0]"></span>
                            <div class="flex-1 h-px bg-brand-border"></div>
                        </div>
                    </template>

                    <!-- Foydalanuvchi xabari -->
                    <template x-if="xabar.kimdan === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="bubble bubble-user px-4 py-2.5 shadow-soft" x-html="formatMatn(xabar.matn)"></div>
                                <div class="text-[10px] text-brand-muted text-right mt-1 mr-2" x-text="xabar.vaqt"></div>
                            </div>
                        </div>
                    </template>

                    <!-- AI xabari -->
                    <template x-if="xabar.kimdan === 'ai'">
                        <div class="flex justify-start gap-2">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-400 to-sky-500 flex items-center justify-center text-base flex-shrink-0">🤖</div>
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="text-xs text-violet-600 font-semibold mb-1 flex items-center gap-1">
                                    AI yordamchi
                                    <span class="px-1.5 py-0.5 rounded-full bg-violet-100 text-[9px] uppercase">bot</span>
                                </div>
                                <div class="bubble bubble-ai px-4 py-2.5" x-html="formatMatn(xabar.matn)"></div>
                                <div class="text-[10px] text-brand-muted mt-1 ml-2" x-text="xabar.vaqt"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Admin xabari -->
                    <template x-if="xabar.kimdan === 'admin'">
                        <div class="flex justify-start gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                <span x-text="(xabar.admin?.ism || 'A')[0].toUpperCase()"></span>
                            </div>
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="text-xs text-sky-600 font-semibold mb-1 flex items-center gap-1">
                                    <span x-text="xabar.admin?.tolaq || 'Admin'"></span>
                                    <span class="px-1.5 py-0.5 rounded-full bg-sky-100 text-[9px] uppercase">admin</span>
                                </div>
                                <div class="bubble bubble-admin px-4 py-2.5 shadow-soft" x-html="formatMatn(xabar.matn)"></div>
                                <div class="text-[10px] text-brand-muted mt-1 ml-2" x-text="xabar.vaqt"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Typing indikator -->
            <div x-show="yozmoqda" x-cloak class="flex justify-start gap-2">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-400 to-sky-500 flex items-center justify-center text-base flex-shrink-0">🤖</div>
                <div class="bubble bubble-ai px-5 py-3.5">
                    <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pastki forma -->
    <form @submit.prevent="yubor()"
          class="bg-white border-t border-brand-border lg:border lg:rounded-b-2xl p-3 lg:shadow-soft">
        <div x-show="xato" x-cloak class="mb-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
            <span x-text="xato"></span>
        </div>

        <div class="flex gap-2 items-end">
            <div class="flex-1 relative">
                <textarea x-ref="matn"
                          x-model="matn"
                          @keydown.enter.prevent="if (!$event.shiftKey) yubor(); else matn += '\n'"
                          @input="qoralamaSaqla()"
                          rows="1"
                          maxlength="2000"
                          x-init="$watch('matn', () => {$refs.matn.style.height = 'auto'; $refs.matn.style.height = Math.min($refs.matn.scrollHeight, 120) + 'px'})"
                          placeholder="Xabar yozing..."
                          class="field resize-none py-3 pr-10"
                          :disabled="yuborilmoqda"></textarea>
                <span class="absolute bottom-2 right-2 text-[10px] text-brand-light pointer-events-none"
                      x-show="matn.length > 0" x-cloak x-text="matn.length + '/2000'"></span>
            </div>
            <button type="submit" :disabled="!matn.trim() || yuborilmoqda"
                    class="btn-primary !p-3 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Yuborish">
                <svg x-show="!yuborilmoqda" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <svg x-show="yuborilmoqda" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
            </button>
        </div>
        <p class="text-[10px] text-brand-muted mt-2 px-1 hidden sm:block">
            <kbd class="px-1.5 py-0.5 bg-brand-border/50 rounded text-[9px] font-mono">Enter</kbd> yuborish ·
            <kbd class="px-1.5 py-0.5 bg-brand-border/50 rounded text-[9px] font-mono">Shift+Enter</kbd> yangi qator
        </p>
    </form>
</div>

<script>
function chatPanel() {
    return {
        xabarlar: [],
        matn: '',
        oxirgi_id: 0,
        yuborilmoqda: false,
        boshYuklanmoqda: true,
        yozmoqda: false,
        adminAktiv: false,
        adminInfo: null,
        xato: '',
        _interval: null,
        _eski_oqilmagan: 0,
        tavsiyalar: [
            'Tariflar haqida ma\'lumot',
            'Test qanday ishlaydi?',
            'Yo\'l belgilari haqida',
            'To\'lov muammosi'
        ],

        async init() {
            // localStorage'dan qoralamani tiklash
            const qoralama = window.LS.olish('chat_qoralama_<?= (int)$f['id'] ?>', '');
            if (qoralama) this.matn = qoralama;

            await this.xabarlarOlish(true);
            this._interval = setInterval(() => this.xabarlarOlish(false), 5000);

            // Sahifa fokusda bo'lsa, oqilgan deb belgilash
            window.addEventListener('focus', () => this.xabarlarOlish(false, true));
        },

        async xabarlarOlish(bosh = false, kor = false) {
            const url = `<?= e(SAYT_URL) ?>/api/chat_olish.php?oxirgi_id=${this.oxirgi_id}${kor ? '&kor=1' : ''}`;
            const r = await window.apiGet(url);
            if (!r.ok) return;

            const eskiSon = this.xabarlar.length;

            if (bosh) {
                this.xabarlar = r.xabarlar;
                this.boshYuklanmoqda = false;
            } else if (r.xabarlar.length > 0) {
                // Yangi xabarlarni qo'shamiz
                r.xabarlar.forEach(x => {
                    if (!this.xabarlar.find(e => e.id === x.id)) {
                        this.xabarlar.push(x);
                    }
                });
            }

            // Oxirgi ID
            if (this.xabarlar.length > 0) {
                this.oxirgi_id = Math.max(...this.xabarlar.map(x => x.id));
            }

            // Admin aktivligini aniqlash (oxirgi 30 daqiqada admin xabari bormi)
            const yarim_soat = 30 * 60 * 1000;
            const hozir = Date.now();
            const songgi_admin = [...this.xabarlar].reverse().find(x => x.kimdan === 'admin');
            if (songgi_admin) {
                const adminVaqti = new Date(songgi_admin.sana.split('.').reverse().slice(0,2).join('-') + '-' + songgi_admin.sana.split('.')[0] + 'T' + songgi_admin.vaqt).getTime();
                this.adminAktiv = (hozir - adminVaqti) < yarim_soat;
                if (this.adminAktiv && songgi_admin.admin) {
                    this.adminInfo = {
                        tolaq: songgi_admin.admin.tolaq,
                        harf: (songgi_admin.admin.ism || 'A')[0].toUpperCase(),
                    };
                }
            } else {
                this.adminAktiv = false;
                this.adminInfo = null;
            }

            // Yangi admin xabari kelgan bo'lsa — brauzer notification
            if (!bosh && this.xabarlar.length > eskiSon) {
                const yangi = this.xabarlar.slice(eskiSon);
                const adminYangi = yangi.find(x => x.kimdan === 'admin');
                if (adminYangi && document.hidden) {
                    window.brauzerXabar(
                        adminYangi.admin?.tolaq + ' (admin)',
                        adminYangi.matn.slice(0, 120)
                    );
                }
            }

            this.scrollPastga();
        },

        async yubor() {
            const matn = this.matn.trim();
            if (!matn || this.yuborilmoqda) return;

            this.yuborilmoqda = true;
            this.xato = '';
            const matnNusxasi = matn;
            this.matn = '';
            window.LS.ochir('chat_qoralama_<?= (int)$f['id'] ?>');

            // Vaqtinchalik foydalanuvchi xabari
            const vaqtinchalik = {
                id: 'tmp-' + Date.now(),
                kimdan: 'user',
                matn: matnNusxasi,
                vaqt: new Date().toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' }),
                sana: new Date().toLocaleString('uz-UZ'),
            };
            this.xabarlar.push(vaqtinchalik);
            this.scrollPastga();

            // AI yozayotganini ko'rsatamiz
            this.yozmoqda = true;

            const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/chat_yuborish.php', { matn: matnNusxasi });

            // Vaqtinchalik xabarni real bilan almashtirish
            this.xabarlar = this.xabarlar.filter(x => x.id !== vaqtinchalik.id);

            this.yuborilmoqda = false;
            this.yozmoqda = false;

            if (!r.ok) {
                this.xato = r.xato || 'Xato yuz berdi';
                this.matn = matnNusxasi; // qaytaramiz
                return;
            }

            // Real javoblarni qo'shamiz
            if (r.user_xabar) this.xabarlar.push({...r.user_xabar, sana: vaqtinchalik.sana});
            if (r.ai_xabar) this.xabarlar.push({...r.ai_xabar, sana: vaqtinchalik.sana});

            this.oxirgi_id = Math.max(...this.xabarlar.filter(x => typeof x.id === 'number').map(x => x.id));
            this.scrollPastga();
            this.$refs.matn.focus();
        },

        async tarixOchir() {
            if (!confirm('Suhbat tarixini tozalashni xohlaysizmi? Bu lokal — server tarixi saqlanadi.')) return;
            // Faqat lokalda yashiramiz
            this.xabarlar = [];
            this.oxirgi_id = 0;
        },

        qoralamaSaqla() {
            window.LS.saqla('chat_qoralama_<?= (int)$f['id'] ?>', this.matn);
        },

        formatMatn(matn) {
            // Linklarni avtomatik aniqlash + xavfsiz HTML
            const xss = matn.replace(/[<>"']/g, c => ({'<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            return xss
                .replace(/\n/g, '<br>')
                .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');
        },

        kunOzgarganmi(i) {
            if (i === 0) return false;
            return this.xabarlar[i].sana.split(' ')[0] !== this.xabarlar[i-1].sana.split(' ')[0];
        },

        scrollPastga() {
            this.$nextTick(() => {
                const k = this.$refs.xabarlarKonteyneri;
                if (k) k.scrollTop = k.scrollHeight;
            });
        },
    };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
