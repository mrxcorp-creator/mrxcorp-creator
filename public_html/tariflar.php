<?php
/**
 * VatanParvar Yaypan — Tariflar sahifasi (batafsil)
 *
 * Barcha tariflar, taqqoslash jadvali, FAQ va CTA.
 */
require_once __DIR__ . '/config/auth.php';

$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$f = joriy_foydalanuvchi();

$sahifa_sarlavha = "Tariflar — " . t('sayt_nomi');
$sahifa_tavsif = "VatanParvar Yaypan tariflari: bepul demo, kunlik, oylik va cheksiz to'plamlar.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="relative max-w-7xl mx-auto px-4 pt-12 pb-8">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] rounded-full bg-gradient-to-br from-sky-200 to-blue-300 opacity-25 blur-3xl -z-10" aria-hidden="true"></div>

    <div class="text-center fade-up">
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Tariflar</span>
        <h1 class="text-4xl md:text-6xl font-display font-bold text-brand-text mb-4">
            O'zingizga mos <span class="text-gradient">tarifni tanlang</span>
        </h1>
        <p class="text-brand-muted text-lg max-w-2xl mx-auto">
            Bepul demo'dan boshlab cheksiz tarifgacha — har bir o'quvchi uchun moslashtirilgan
        </p>
    </div>
</section>

<!-- Tariflar grid -->
<section class="max-w-7xl mx-auto px-4 py-10">
    <?php if (empty($tariflar)): ?>
        <div class="glass-card p-16 text-center text-brand-muted">
            <div class="text-5xl mb-4">📋</div>
            <p>Hozircha tariflar mavjud emas</p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($tariflar as $i => $tar):
                // Har bir tarif uchun afzalliklar (oddiy mantiq: turi va qiymatga qarab)
                $afzalliklar = ['Barcha biletlarga kirish', '500+ savol', 'Auto-save tizimi'];
                if ($tar['tur'] === 'oy' && $tar['qiymat'] >= 1) {
                    $afzalliklar[] = 'Statistika va tahlil';
                    $afzalliklar[] = 'Telegram bildirishnomalar';
                }
                if ($tar['qiymat'] >= 3 || $tar['nomi'] === 'Cheksiz') {
                    $afzalliklar[] = "Pullik biletlar";
                    $afzalliklar[] = "Aniq imtihon simulyatsiyasi";
                }
                if (str_contains(mb_strtolower($tar['nomi']), 'cheksiz')) {
                    $afzalliklar[] = "Imtihongacha cheksiz kirish";
                    $afzalliklar[] = "Premium qo'llab-quvvatlash";
                }
            ?>
                <div class="glass-card glass-card-hover p-7 relative <?= $tar['mashhur'] ? 'ring-2 ring-sky-400 ring-offset-4 ring-offset-brand-bg shadow-glow' : '' ?>"
                     data-animate style="animation-delay: <?= 0.08 * $i ?>s">

                    <?php if ($tar['mashhur']): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-sky-500 to-blue-600 text-white whitespace-nowrap shadow-soft">
                            ⭐ Eng mashhur
                        </span>
                    <?php endif; ?>

                    <h3 class="font-display text-xl font-bold text-brand-text mb-2"><?= e($tar['nomi']) ?></h3>
                    <p class="text-sm text-brand-muted mb-4 min-h-[2.5rem]"><?= e($tar['tavsif']) ?></p>

                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-4xl font-display font-bold text-gradient"><?= e(number_format($tar['narx'], 0, '.', ' ')) ?></span>
                        <span class="text-brand-muted text-sm font-medium">so'm</span>
                    </div>

                    <?php if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']): ?>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="line-through text-brand-light text-sm"><?= e(pul($tar['eski_narx'])) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">
                                -<?= round((1 - $tar['narx'] / $tar['eski_narx']) * 100) ?>%
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-xs text-brand-muted mb-4">
                            <?php
                            $kun = match ($tar['tur']) {
                                'kun' => $tar['qiymat'] . ' kun',
                                'oy'  => $tar['qiymat'] . ' oy',
                                default => 'cheksiz',
                            };
                            ?>
                            Davomiyligi: <strong class="text-brand-text"><?= $kun ?></strong>
                        </div>
                    <?php endif; ?>

                    <ul class="space-y-2 mb-6 text-sm">
                        <?php foreach ($afzalliklar as $a): ?>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                                <span class="text-brand-body"><?= e($a) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($f && $f['rol'] === 'user'): ?>
                        <a href="<?= e(SAYT_URL) ?>/tolov" class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full text-sm">
                            Tarifni olish
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    <?php else: ?>
                        <a href="<?= e(SAYT_URL) ?>/register" class="<?= $tar['mashhur'] ? 'btn-primary' : 'btn-ghost' ?> w-full text-sm">
                            Hoziroq boshlash
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Taqqoslash jadvali -->
<section class="max-w-6xl mx-auto px-4 py-16">
    <div class="text-center mb-10" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Taqqoslash</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold text-brand-text">Nimani olasiz?</h2>
    </div>

    <div class="glass-card p-2 overflow-x-auto" data-animate>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-brand-border">
                    <th class="py-4 px-4 text-left font-semibold text-brand-text">Imkoniyatlar</th>
                    <th class="py-4 px-4 text-center font-semibold text-brand-text">Bepul</th>
                    <th class="py-4 px-4 text-center font-semibold text-sky-600 bg-sky-50/50">Pullik tariflar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
                <?php
                $imkoniyatlar = [
                    ['Bepul biletlar', true, true],
                    ['Pullik biletlar (barcha)', false, true],
                    ['Auto-save javoblar', true, true],
                    ['Statistika va tahlil', false, true],
                    ['Telegram bildirishnomalar', false, true],
                    ['Aniq imtihon simulyatsiyasi', false, true],
                    ['Premium qo\'llab-quvvatlash', false, true],
                    ['Reklamalarsiz', false, true],
                ];
                foreach ($imkoniyatlar as $im):
                ?>
                    <tr class="hover:bg-sky-50/50 transition">
                        <td class="py-3 px-4 font-medium text-brand-text"><?= e($im[0]) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($im[1]): ?>
                                <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-brand-light mx-auto" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-center bg-sky-50/30">
                            <?php if ($im[2]): ?>
                                <svg class="w-5 h-5 text-sky-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-brand-light mx-auto" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- FAQ -->
<section class="max-w-4xl mx-auto px-4 py-16">
    <div class="text-center mb-10" data-animate>
        <span class="inline-block px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-wider mb-3">Savollar</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold text-brand-text">Ko'p so'raladigan savollar</h2>
    </div>

    <div class="space-y-3" x-data="{ ochiq: 0 }">
        <?php
        $faq = [
            ['Tarifni qanday yangilash mumkin?', 'Yangi tarif sotib olganingizda eski tarif avtomatik yopiladi va yangisi 100% kuchga kiradi. Foydalanilmagan kunlar yo\'qoladi.'],
            ['To\'lov qanday usulda amalga oshiriladi?', 'Click va Payme orqali to\'lov qabul qilamiz. To\'lov tugashi bilan obuna avtomatik faollashadi.'],
            ['Bonus balans nima uchun ishlatiladi?', 'Bonusni har qanday tarifni sotib olishda chegirma sifatida ishlatishingiz mumkin. Do\'stlaringizni taklif qilib bonus oling.'],
            ['Telefonda ham ishlaydimi?', 'Ha, sayt to\'liq mobil moslashgan. Barcha funksiyalar telefonda ham ishlaydi.'],
            ['Pulim qaytariladimi?', 'Tarif faollashgandan keyin pul qaytarilmaydi, lekin texnik muammo bo\'lsa, biz bilan bog\'laning.'],
            ['Demo testni qancha marta yechishim mumkin?', 'Bepul biletlarni cheksiz marta qayta yechish mumkin.'],
        ];
        foreach ($faq as $i => $q):
        ?>
            <div class="glass-card overflow-hidden" data-animate style="animation-delay: <?= 0.04 * $i ?>s">
                <button @click="ochiq = ochiq === <?= $i ?> ? null : <?= $i ?>"
                        class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-sky-50/50 transition">
                    <span class="font-semibold text-brand-text pr-4"><?= e($q[0]) ?></span>
                    <svg class="w-5 h-5 text-sky-500 flex-shrink-0 transition-transform duration-300"
                         :class="ochiq === <?= $i ?> ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="ochiq === <?= $i ?>"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-cloak
                     class="px-6 pb-5 pt-0 text-brand-muted leading-relaxed border-t border-brand-border">
                    <?= e($q[1]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA -->
<section class="max-w-4xl mx-auto px-4 py-16">
    <div class="glass-card p-10 text-center shadow-medium" data-animate>
        <h2 class="text-2xl md:text-3xl font-display font-bold text-brand-text mb-3">
            Hali shubhalanyapsizmi?
        </h2>
        <p class="text-brand-muted mb-6">Bepul demo testni yeching va o'zingiz tekshiring</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn-primary">
                Bepul ro'yxatdan o'tish
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="<?= e(SAYT_URL) ?>/aloqa" class="btn-ghost">Bog'lanish</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
