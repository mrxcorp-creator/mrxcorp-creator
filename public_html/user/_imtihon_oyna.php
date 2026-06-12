<?php /** Imtihon ishlash oynasi */ ?>
<div x-data="imtihonRejim(<?= htmlspecialchars(json_encode([
        'natija_id'   => (int) $davom['id'],
        'qolgan'      => (int) $davom['qolgan_vaqt'],
        'savollar'    => $savollar,
        'javoblar'    => $javoblar,
        'xato_limit'  => $IMTIHON_XATO_LIMIT,
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" x-cloak>

    <div class="sticky top-0 z-30 backdrop-blur-xl bg-white/95 border-b border-brand-border shadow-soft">
        <div class="max-w-4xl mx-auto px-3 sm:px-4 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="if (confirm('Imtihondan chiqsangiz, u to\'xtatiladi va fail hisoblanadi.')) chiqib() ;"
                        class="p-2 -ml-1 rounded-lg hover:bg-rose-50 text-brand-muted hover:text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div>
                    <div class="font-display font-bold text-brand-text text-sm">🎓 IMTIHON</div>
                    <div class="text-xs text-brand-muted">
                        Savol <span class="font-bold" x-text="joriy + 1"></span> / <span x-text="savollar.length"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <template x-for="i in xato_limit + 1" :key="i">
                    <span class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                          :class="(i - 1) < xato_son ? 'bg-rose-500 scale-110' : 'bg-emerald-500/30'"></span>
                </template>
            </div>

            <span class="font-mono font-bold tabular-nums px-3 py-1.5 rounded-lg text-sm"
                  :class="qolgan < 60 ? 'bg-rose-100 text-rose-700 animate-pulse' : (qolgan < 300 ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700')"
                  x-text="vaqtFormat(qolgan)"></span>
        </div>
        <div class="h-1 bg-brand-border">
            <div class="h-full bg-gradient-to-r from-sky-500 to-blue-600 transition-all duration-500"
                 :style="`width:${(joriy + 1) / savollar.length * 100}%`"></div>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-3 sm:px-4 py-6">
        <template x-for="(s, i) in savollar" :key="s.id">
            <div x-show="i === joriy" class="glass-card p-5 sm:p-6 fade-up">
                <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"/></svg>
                    <span><strong>Imtihon:</strong> Aniq tanlang. Javob bergach orqaga qaytarib bo'lmaydi.</span>
                </div>

                <p class="text-base sm:text-lg mb-4 text-brand-text font-medium" x-text="s.matn"></p>

                <template x-if="s.rasm">
                    <img :src="`<?= e(SAYT_URL) ?>/uploads/${s.rasm}`"
                         class="rounded-xl mb-4 max-w-md w-full mx-auto">
                </template>

                <div class="space-y-2">
                    <template x-for="v in ['a','b','c','d']" :key="v">
                        <button x-show="s['variant_' + v]"
                                @click="javobBer(s.id, v)"
                                :disabled="javoblar[s.id]"
                                :class="{
                                    'bg-emerald-100 border-emerald-500 text-emerald-900': javoblar[s.id] === v && javobNatija[s.id] === 'togri',
                                    'bg-rose-100 border-rose-500 text-rose-900': javoblar[s.id] === v && javobNatija[s.id] === 'xato',
                                    'bg-emerald-50 border-emerald-300': javoblar[s.id] && javoblar[s.id] !== v && v === togri_javob_joriy,
                                    'bg-white border-brand-border hover:border-sky-400 hover:bg-sky-50': !javoblar[s.id]
                                }"
                                class="w-full text-left p-3.5 rounded-xl border-2 transition-all flex items-start gap-3 disabled:cursor-not-allowed">
                            <span class="w-7 h-7 rounded-md bg-white flex items-center justify-center text-xs font-bold flex-shrink-0 uppercase border border-brand-border" x-text="v"></span>
                            <span x-text="s['variant_' + v]" class="flex-1"></span>
                            <span x-show="javoblar[s.id] === v && javobNatija[s.id] === 'togri'" class="text-emerald-600 font-bold">✓</span>
                            <span x-show="javoblar[s.id] === v && javobNatija[s.id] === 'xato'" class="text-rose-600 font-bold">✗</span>
                            <span x-show="javoblar[s.id] && javoblar[s.id] !== v && v === togri_javob_joriy" class="text-emerald-600">←</span>
                        </button>
                    </template>
                </div>

                <div x-show="javoblar[s.id] && izoh_joriy" x-cloak class="mt-4 p-3 rounded-xl bg-sky-50 border border-sky-200 text-sm fade-up">
                    <strong class="text-sky-700">💡</strong> <span x-text="izoh_joriy"></span>
                </div>

                <button x-show="javoblar[s.id]" @click="keyingiSavol()" class="btn-primary w-full mt-4 fade-up">
                    <span x-text="joriy === savollar.length - 1 ? 'Imtihonni tugatish' : 'Keyingi savol'"></span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </div>
        </template>
    </main>

    <div x-show="modal" x-transition x-cloak class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="glass-card p-7 max-w-sm w-full text-center">
            <div class="text-5xl mb-3">⏳</div>
            <h3 class="text-2xl font-display font-bold text-brand-text mb-2">Imtihon yakunlanmoqda...</h3>
            <p class="text-brand-muted mb-2">Iltimos, kuting</p>
            <svg class="w-8 h-8 animate-spin mx-auto text-sky-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        </div>
    </div>
</div>

<script>
function imtihonRejim(boshlangich) {
    return {
        natija_id: boshlangich.natija_id,
        qolgan: boshlangich.qolgan,
        savollar: boshlangich.savollar,
        javoblar: boshlangich.javoblar || {},
        xato_limit: boshlangich.xato_limit,
        xato_son: 0,
        joriy: 0,
        modal: false,
        javobNatija: {},
        togri_javob_joriy: null,
        izoh_joriy: '',
        _timer: null,

        init() {
            for (let i = 0; i < this.savollar.length; i++) {
                if (!this.javoblar[this.savollar[i].id]) { this.joriy = i; break; }
            }
            this._timer = setInterval(() => {
                this.qolgan--;
                if (this.qolgan <= 0) { clearInterval(this._timer); this.tugat(true); }
            }, 1000);
        },

        async javobBer(savol_id, variant) {
            if (this.javoblar[savol_id]) return;
            this.javoblar[savol_id] = variant;
            const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/save_answer.php', {
                natija_id: this.natija_id,
                savol_id: savol_id,
                variant: variant,
                imtihon: '1',
            });
            if (r && r.ok) {
                this.qolgan = r.qolgan_vaqt;
                this.togri_javob_joriy = r.togri_javob || null;
                this.izoh_joriy = r.izoh || '';

                if (variant === r.togri_javob) {
                    this.javobNatija[savol_id] = 'togri';
                } else {
                    this.javobNatija[savol_id] = 'xato';
                    this.xato_son++;
                    if (this.xato_son > this.xato_limit) {
                        setTimeout(() => this.tugat(false), 2000);
                    }
                }
            }
        },

        keyingiSavol() {
            if (this.joriy === this.savollar.length - 1) { this.tugat(); return; }
            this.joriy++;
            this.togri_javob_joriy = null;
            this.izoh_joriy = '';
        },

        async tugat(vaqtTugadi = false) {
            if (this._timer) clearInterval(this._timer);
            this.modal = true;
            const r = await window.apiPost('<?= e(SAYT_URL) ?>/api/save_answer.php', {
                natija_id: this.natija_id,
                tugatish: '1',
                vaqt_tugadi: vaqtTugadi ? '1' : '0',
                imtihon: '1',
            });
            if (r && r.ok) {
                window.location = '<?= e(SAYT_URL) ?>/imtihon?nat=' + this.natija_id;
            } else {
                this.modal = false;
                alert(r.xato || 'Xato yuz berdi');
            }
        },

        async chiqib() {
            if (this._timer) clearInterval(this._timer);
            await window.apiPost('<?= e(SAYT_URL) ?>/api/save_answer.php', {
                natija_id: this.natija_id, tugatish: '1', vaqt_tugadi: '1', imtihon: '1',
            });
            window.location = '<?= e(SAYT_URL) ?>/imtihon';
        },

        vaqtFormat(s) {
            if (s < 0) s = 0;
            const m = Math.floor(s / 60);
            const ss = s % 60;
            return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
        }
    };
}
</script>
