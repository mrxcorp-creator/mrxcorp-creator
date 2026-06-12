<?php
/**
 * Admin — chat boshqaruvi
 *
 * Chap panel: barcha foydalanuvchilar suhbatlari (so'nggi xabar bo'yicha)
 * O'ng panel: tanlangan foydalanuvchining xabarlari + javob yozish
 */
require_once __DIR__ . '/../config/auth.php';
$admin = admin_bolish_kerak();

$tanlangan_id = (int) olish('fid');

$admin_sahifa = 'chat';
$sahifa_sarlavha = "Chat";
require_once __DIR__ . '/_layout.php';
?>

<style>
    .admin-chat-cont { height: calc(100vh - 6.5rem); }
    .bubble-user-a { background: white; color: #0F172A; border: 1.5px solid #E2E8F0; border-radius: 1.25rem 1.25rem 1.25rem 0.35rem; }
    .bubble-admin-a { background: linear-gradient(135deg, #38BDF8, #0284C7); color: white; border-radius: 1.25rem 1.25rem 0.35rem 1.25rem; }
    .bubble-ai-a { background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(56,189,248,0.08)); color: #0F172A; border: 1.5px solid rgba(139,92,246,0.20); border-radius: 1.25rem 1.25rem 1.25rem 0.35rem; }
</style>

<div class="admin-chat-cont -mx-4 sm:-mx-6 -mt-4 sm:-mt-6 grid lg:grid-cols-[340px_1fr] bg-white"
     x-data="adminChat(<?= $tanlangan_id ?>)">

    <!-- Chap: Suhbatlar ro'yxati -->
    <aside class="border-r border-brand-border flex flex-col"
           :class="(tanlanganId && innerWidth < 1024) ? 'hidden' : 'flex'">
        <div class="p-3 border-b border-brand-border">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-brand-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="qidiruv" @input.debounce.400ms="suhbatlarniOlish()"
                       placeholder="Foydalanuvchi qidirish..." class="field pl-10 text-sm">
            </div>
        </div>

        <div class="overflow-y-auto flex-1">
            <template x-if="suhbatlar.length === 0 && !boshYuklanmoqda">
                <div class="p-8 text-center text-brand-muted text-sm">
                    <div class="text-4xl mb-2">💬</div>
                    Hali suhbatlar yo'q
                </div>
            </template>

            <template x-for="s in suhbatlar" :key="s.id">
                <button @click="suhbatTanla(s.id)"
                        :class="tanlanganId === s.id ? 'bg-sky-50 border-l-4 border-l-sky-500' : 'border-l-4 border-l-transparent hover:bg-sky-50/50'"
                        class="w-full text-left p-3 border-b border-brand-border/50 transition flex gap-3 items-start">
                    <div class="relative flex-shrink-0">
                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white text-base shadow-soft">
                            <span x-text="s.ism[0].toUpperCase()"></span>
                        </div>
                        <span x-show="s.oqilmagan > 0"
                              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center"
                              x-text="s.oqilmagan > 9 ? '9+' : s.oqilmagan"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-brand-text truncate" x-text="s.ism"></span>
                            <span class="text-[10px] text-brand-muted flex-shrink-0" x-text="s.kun + ' ' + s.vaqt"></span>
                        </div>
                        <div class="text-xs text-brand-muted truncate flex items-center gap-1">
                            <span x-show="s.oxirgi_kimdan === 'admin'" class="text-sky-600">↗</span>
                            <span x-show="s.oxirgi_kimdan === 'ai'" class="text-violet-600">🤖</span>
                            <span x-text="s.oxirgi"></span>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </aside>

    <!-- O'ng: Suhbat oynasi -->
    <main class="flex flex-col" :class="(!tanlanganId && innerWidth < 1024) ? 'hidden' : 'flex'">

        <!-- Suhbat tanlanmagan -->
        <template x-if="!tanlanganId">
            <div class="flex-1 flex items-center justify-center text-center p-8">
                <div>
                    <div class="text-6xl mb-4 floating">💬</div>
                    <h2 class="font-display font-bold text-xl text-brand-text mb-2">Suhbatni tanlang</h2>
                    <p class="text-brand-muted">Chap tomondan foydalanuvchini tanlab, javob yozing</p>
                </div>
            </div>
        </template>

        <template x-if="tanlanganId && foydalanuvchi">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-brand-border flex items-center gap-3 bg-white">
                    <button @click="tanlanganId = null; foydalanuvchi = null"
                            class="lg:hidden p-2 -ml-1 rounded-lg hover:bg-sky-50 text-brand-text">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center font-bold text-white shadow-soft">
                        <span x-text="foydalanuvchi.ism[0].toUpperCase()"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-bold text-brand-text truncate" x-text="foydalanuvchi.ism"></div>
                        <div class="text-xs text-brand-muted flex items-center gap-2">
                            <a :href="'tel:' + foydalanuvchi.telefon" class="hover:text-sky-600 font-mono" x-text="foydalanuvchi.telefon"></a>
                            <template x-if="foydalanuvchi.obuna">
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-[10px]">
                                    💎 <span x-text="foydalanuvchi.obuna.nomi"></span> · <span x-text="foydalanuvchi.obuna.tugash"></span>
                                </span>
                            </template>
                            <template x-if="!foydalanuvchi.obuna">
                                <span class="px-2 py-0.5 rounded-full bg-brand-bg-soft text-brand-muted font-semibold text-[10px]">Obuna yo'q</span>
                            </template>
                        </div>
                    </div>
                    <a :href="'tel:' + foydalanuvchi.telefon" class="p-2 rounded-lg hover:bg-sky-50 text-emerald-600" title="Qo'ng'iroq qilish">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </a>
                </div>

                <!-- Xabarlar -->
                <div class="flex-1 overflow-y-auto bg-gradient-to-b from-sky-50/30 to-white p-4 space-y-3"
                     x-ref="xabarlarBox">

                    <template x-for="x in xabarlar" :key="x.id">
                        <div>
                            <!-- Foydalanuvchi xabari -->
                            <template x-if="x.kimdan === 'user'">
                                <div class="flex justify-start gap-2">
                                    <div class="max-w-[80%]">
                                        <div class="bubble-user-a px-4 py-2.5 shadow-soft" x-html="formatMatn(x.matn)"></div>
                                        <div class="text-[10px] text-brand-muted mt-1 ml-2" x-text="x.vaqt"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- AI xabari -->
                            <template x-if="x.kimdan === 'ai'">
                                <div class="flex justify-end gap-2">
                                    <div class="max-w-[80%]">
                                        <div class="text-xs text-violet-600 font-semibold mb-1 text-right">
                                            🤖 AI yordamchi
                                        </div>
                                        <div class="bubble-ai-a px-4 py-2.5" x-html="formatMatn(x.matn)"></div>
                                        <div class="text-[10px] text-brand-muted text-right mt-1 mr-2" x-text="x.vaqt"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- Admin xabari -->
                            <template x-if="x.kimdan === 'admin'">
                                <div class="flex justify-end gap-2">
                                    <div class="max-w-[80%]">
                                        <div class="text-xs text-sky-700 font-semibold mb-1 text-right" x-text="x.admin"></div>
                                        <div class="bubble-admin-a px-4 py-2.5 shadow-soft" x-html="formatMatn(x.matn)"></div>
                                        <div class="text-[10px] text-brand-muted text-right mt-1 mr-2" x-text="x.vaqt"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="xabarlar.length === 0">
                        <div class="text-center py-8 text-brand-muted text-sm">
                            Hali xabar yo'q. Birinchi javobni yozing!
                        </div>
                    </template>
                </div>

                <!-- Forma -->
                <form @submit.prevent="javobYubor()" class="border-t border-brand-border bg-white p-3">
                    <div x-show="xato" x-cloak class="mb-2 text-xs text-rose-600" x-text="xato"></div>
                    <div class="flex gap-2 items-end">
                        <textarea x-ref="javobMatn" x-model="javobMatn"
                                  @keydown.enter.prevent="if (!$event.shiftKey) javobYubor(); else javobMatn += '\n'"
                                  rows="1" maxlength="2000"
                                  x-init="$watch('javobMatn', () => {$refs.javobMatn.style.height = 'auto'; $refs.javobMatn.style.height = Math.min($refs.javobMatn.scrollHeight, 120) + 'px'})"
                                  placeholder="Javobni yozing... (Enter — yuborish, Shift+Enter — yangi qator)"
                                  class="field resize-none flex-1 py-3"
                                  :disabled="yuborilmoqda"></textarea>
                        <button type="submit" :disabled="!javobMatn.trim() || yuborilmoqda"
                                class="btn-primary !p-3 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!yuborilmoqda" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <svg x-show="yuborilmoqda" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </main>
</div>

<script>
function adminChat(boshFid) {
    return {
        suhbatlar: [],
        qidiruv: '',
        tanlanganId: boshFid || null,
        foydalanuvchi: null,
        xabarlar: [],
        javobMatn: '',
        yuborilmoqda: false,
        boshYuklanmoqda: true,
        xato: '',
        innerWidth: window.innerWidth,
        _suhbat_int: null,
        _xabar_int: null,

        async init() {
            window.addEventListener('resize', () => this.innerWidth = window.innerWidth);
            await this.suhbatlarniOlish();
            this.boshYuklanmoqda = false;
            // Har 10 soniyada suhbatlar ro'yxatini yangilash
            this._suhbat_int = setInterval(() => this.suhbatlarniOlish(), 10000);

            if (this.tanlanganId) {
                await this.suhbatTanla(this.tanlanganId);
            }
        },

        async suhbatlarniOlish() {
            const url = `<?= e(SAYT_URL) ?>/api/chat_admin.php?action=ruyhat&q=${encodeURIComponent(this.qidiruv)}`;
            const r = await window.apiGet(url);
            if (r.ok) this.suhbatlar = r.suhbatlar;
        },

        async suhbatTanla(fid) {
            this.tanlanganId = fid;
            history.replaceState(null, '', '?fid=' + fid);

            const r = await window.apiGet(`<?= e(SAYT_URL) ?>/api/chat_admin.php?action=xabarlar&fid=${fid}`);
            if (!r.ok) { this.xato = r.xato; return; }
            this.foydalanuvchi = r.foydalanuvchi;
            this.xabarlar = r.xabarlar;
            this.scrollPastga();

            // O'qilgan deb belgilash
            await window.apiPost('<?= e(SAYT_URL) ?>/api/chat_admin.php?action=oqildi', { fid });
            this.suhbatlarniOlish();

            // Xabarlarni har 5 soniyada yangilash
            if (this._xabar_int) clearInterval(this._xabar_int);
            this._xabar_int = setInterval(() => this.xabarlarYangila(), 5000);
        },

        async xabarlarYangila() {
            if (!this.tanlanganId) return;
            const r = await window.apiGet(`<?= e(SAYT_URL) ?>/api/chat_admin.php?action=xabarlar&fid=${this.tanlanganId}`);
            if (r.ok) {
                const eskiSon = this.xabarlar.length;
                this.xabarlar = r.xabarlar;
                if (this.xabarlar.length > eskiSon) {
                    this.scrollPastga();
                    // Foydalanuvchi yangi xabar yuborgan bo'lsa
                    const yangi = this.xabarlar[this.xabarlar.length - 1];
                    if (yangi.kimdan === 'user' && document.hidden) {
                        window.brauzerXabar(this.foydalanuvchi.ism, yangi.matn.slice(0, 120));
                    }
                }
            }
        },

        async javobYubor() {
            const matn = this.javobMatn.trim();
            if (!matn || this.yuborilmoqda) return;
            this.yuborilmoqda = true;
            this.xato = '';
            const matnNusxa = matn;
            this.javobMatn = '';

            const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/chat_admin.php?action=yuborish', {
                fid: this.tanlanganId,
                matn: matnNusxa,
            });

            this.yuborilmoqda = false;
            if (!r.ok) {
                this.xato = r.xato || 'Xato';
                this.javobMatn = matnNusxa;
                return;
            }
            this.xabarlar.push({...r.xabar, sana: ''});
            this.scrollPastga();
            this.suhbatlarniOlish();
            this.$refs.javobMatn.focus();
        },

        formatMatn(m) {
            const xss = m.replace(/[<>"']/g, c => ({'<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            return xss
                .replace(/\n/g, '<br>')
                .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" class="underline">$1</a>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        },

        scrollPastga() {
            this.$nextTick(() => {
                const k = this.$refs.xabarlarBox;
                if (k) k.scrollTop = k.scrollHeight;
            });
        },
    };
}
</script>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
