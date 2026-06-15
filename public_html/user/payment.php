<?php
/**
 * VatanParvar Yaypan — Tarif tanlash va to'lov sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();
// Foydalanuvchini yangilab olamiz (bonus_balans uchun)
$f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$f['id']]);

$tariflar = db_barcha(
    'SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx'
);
$xato = '';

/* ── Obunani aktivlashtirish yordamchisi ─────────────────── */
function _obuna_ochish(int $uid, array $tarif): void
{
    $kun = match ($tarif['tur']) {
        'kun'   => (int) $tarif['qiymat'],
        'oy'    => (int) $tarif['qiymat'] * 30,
        default => 365,
    };
    db_bajar(
        'INSERT INTO obunalar
         (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
         VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
        [$uid, $tarif['id'], $kun]
    );
}

/* ── POST: to'lov boshlash ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $tarif_id  = (int) post('tarif_id');
        $tolov_turi = post('tolov_turi');
        $promo_kod  = strtoupper(trim(post('promo_kod')));

        $tarif = $tarif_id
            ? db_qator('SELECT * FROM tariflar WHERE id = ? AND holat = "faol"', [$tarif_id])
            : null;

        if (!$tarif) {
            $xato = t('malumot_yoq');
        } elseif (!in_array($tolov_turi, ['click','payme','bonus'], true)) {
            $xato = t('tolov_xato');
        } else {
            $jami     = (float) $tarif['narx'];
            $chegirma = 0;
            $promo    = null;

            // Promo kod
            if ($promo_kod) {
                $promo = db_qator(
                    'SELECT * FROM promo_kodlar
                     WHERE kod = ? AND holat = "faol"
                       AND (tugash_sanasi IS NULL OR tugash_sanasi > NOW())
                       AND ishlatilgan < maks_ishlatish',
                    [$promo_kod]
                );
                if ($promo) {
                    $chegirma = (float)$promo['chegirma_foiz'] > 0
                        ? round($jami * (float)$promo['chegirma_foiz'] / 100)
                        : min((float)$promo['chegirma_summa'], $jami);
                    $jami = max(0, $jami - $chegirma);
                }
            }

            // Bonus to'lov
            if ($tolov_turi === 'bonus') {
                if ((float)$f['bonus_balans'] < $jami) {
                    $xato = 'Bonus balansi yetarli emas (' . pul($f['bonus_balans']) . ')';
                } else {
                    db()->beginTransaction();
                    try {
                        db_bajar(
                            'UPDATE foydalanuvchilar SET bonus_balans = bonus_balans - ? WHERE id = ?',
                            [$jami, $f['id']]
                        );
                        $tid = db_bajar(
                            'INSERT INTO tolovlar
                             (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                             VALUES (?, ?, ?, "bonus", "muvaffaqiyatli", ?)',
                            [$f['id'], $tarif_id, $jami, $promo_kod ?: null]
                        );
                        _obuna_ochish($f['id'], $tarif);
                        if ($promo) {
                            db_bajar(
                                'UPDATE promo_kodlar SET ishlatilgan = ishlatilgan + 1 WHERE id = ?',
                                [$promo['id']]
                            );
                        }
                        db()->commit();
                        if ($f['telegram_id']) {
                            telegram_yubor(
                                (int)$f['telegram_id'],
                                "✅ <b>To'lov muvaffaqiyatli!</b>\n"
                                . "Tarif: <b>{$tarif['nomi']}</b>\n"
                                . "Summa: <b>" . pul($jami) . '</b>'
                            );
                        }
                        flash_qoy('muvaffaqiyat', t('tolov_muvaffaqiyat'));
                        yonaltir(SAYT_URL . '/dashboard');
                    } catch (Throwable $e) {
                        db()->rollBack();
                        error_log('[VPY] Bonus tolov: ' . $e->getMessage());
                        $xato = t('tolov_xato');
                    }
                }
            } else {
                // Click / Payme
                $tolov_id = db_bajar(
                    'INSERT INTO tolovlar
                     (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, ?, "kutilmoqda", ?)',
                    [$f['id'], $tarif_id, $jami, $tolov_turi, $promo_kod ?: null]
                );

                if ($tolov_turi === 'click') {
                    $merchant_id = sozlama('click_merchant_id', '');
                    $service_id  = sozlama('click_service_id',  '');
                    if ($merchant_id && $service_id) {
                        $url = "https://my.click.uz/services/pay"
                            . "?service_id={$service_id}"
                            . "&merchant_id={$merchant_id}"
                            . "&amount={$jami}"
                            . "&transaction_param={$tolov_id}"
                            . "&return_url=" . urlencode(SAYT_URL . '/dashboard');
                        yonaltir($url);
                    } else {
                        $xato = 'Click to\'lov tizimi sozlanmagan. Admin bilan bog\'laning.';
                    }
                } else {
                    $merchant_id = sozlama('payme_merchant_id', '');
                    if ($merchant_id) {
                        $params = base64_encode(
                            "m={$merchant_id};ac.tolov_id={$tolov_id};a=" . (int)($jami * 100)
                        );
                        yonaltir("https://checkout.paycom.uz/{$params}");
                    } else {
                        $xato = 'Payme to\'lov tizimi sozlanmagan.';
                    }
                }
            }
        }
    }
}

// Mavjud faol obuna
$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$sahifa_sarlavha = t('tolov_sarlavha');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 py-10 pb-20">

    <!-- ── Sarlavha ──────────────────────────────────────── -->
    <div class="text-center mb-12 fade-up">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-widest mb-3">
            Narxlar
        </p>
        <h1 class="text-3xl md:text-4xl font-display font-black mb-3">
            <?= e(t('tolov_sarlavha')) ?>
        </h1>
        <p class="text-white/50 max-w-md mx-auto"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <!-- ── Xato ──────────────────────────────────────────── -->
    <?php if ($xato): ?>
    <div class="max-w-xl mx-auto mb-7 flex items-center gap-2.5 p-4 rounded-xl
                bg-red-500/10 border border-red-500/25 text-red-300 text-sm animate-shake">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= e($xato) ?>
    </div>
    <?php endif; ?>

    <!-- ── Faol obuna ────────────────────────────────────── -->
    <?php if ($obuna): ?>
    <div class="glass-card p-5 mb-8 border-amber-500/25 bg-amber-500/[0.04]
                max-w-2xl mx-auto flex items-center gap-4 fade-up">
        <span class="text-3xl flex-shrink-0">🎉</span>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-amber-400">
                Faol obuna: <?= e($obuna['tarif_nomi']) ?>
            </p>
            <p class="text-sm text-white/50">
                <?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'], 'd.m.Y')) ?>
            </p>
        </div>
        <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost btn-sm flex-shrink-0">
            Testga →
        </a>
    </div>
    <?php endif; ?>

    <!-- ── Forma ──────────────────────────────────────────── -->
    <form method="POST"
          x-data="{
              tarif:     null,
              tolov:     'click',
              loading:   false,
              promo:     '',
              promoMsg:  '',
              promoOk:   false,
              tariflar:  <?= json_encode(
                  array_map(
                      fn($t) => ['id' => (int)$t['id'], 'narx' => (float)$t['narx'], 'nomi' => $t['nomi']],
                      $tariflar
                  ),
                  JSON_UNESCAPED_UNICODE
              ) ?>
          }"
          @submit="loading = true">
        <?= csrf_input() ?>
        <input type="hidden" name="tarif_id"   :value="tarif">
        <input type="hidden" name="tolov_turi" :value="tolov">

        <!-- ── Tarif kartalari ────────────────────────────── -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
            <?php foreach ($tariflar as $i => $tar): ?>
            <div @click="tarif = <?= (int)$tar['id'] ?>"
                 :class="tarif === <?= (int)$tar['id'] ?>
                     ? 'border-blue-500 ring-2 ring-blue-500/30 bg-blue-500/[0.05]'
                     : 'hover:border-white/20 cursor-pointer'"
                 class="glass-card p-6 relative flex flex-col transition-all duration-200 select-none
                        <?= $tar['mashhur'] ? 'border-blue-500/35' : '' ?>"
                 style="animation-delay:<?= 0.07 * $i ?>s">

                <?php if ($tar['mashhur']): ?>
                <div class="absolute -top-4 left-0 right-0 flex justify-center">
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold
                                 bg-gradient-to-r from-blue-600 to-violet-600 text-white
                                 shadow-lg shadow-blue-500/20">
                        ⭐ <?= e(t('mashhur')) ?>
                    </span>
                </div>
                <?php endif; ?>

                <h3 class="font-display font-bold text-lg mb-2"><?= e($tar['nomi']) ?></h3>

                <?php if ((float)($tar['eski_narx']??0) > (float)$tar['narx']): ?>
                <div class="flex items-center gap-2 mb-1">
                    <span class="line-through text-white/30 text-sm">
                        <?= e(pul($tar['eski_narx'])) ?>
                    </span>
                    <span class="badge badge-green text-xs">
                        -<?= round((1 - $tar['narx']/$tar['eski_narx']) * 100) ?>%
                    </span>
                </div>
                <?php endif; ?>

                <div class="text-4xl font-display font-black text-white mb-1 tabnum">
                    <?= number_format((float)$tar['narx'], 0, '.', ' ') ?>
                </div>
                <div class="text-white/40 text-sm mb-4">so'm</div>

                <p class="text-sm text-white/50 leading-relaxed flex-1 mb-5">
                    <?= e($tar['tavsif']) ?>
                </p>

                <!-- Tanlash indikator -->
                <div class="flex items-center justify-center">
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                         :class="tarif === <?= (int)$tar['id'] ?>
                             ? 'border-blue-500 bg-blue-500'
                             : 'border-white/25'">
                        <svg x-show="tarif === <?= (int)$tar['id'] ?>"
                             class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($tariflar)): ?>
            <div class="col-span-full glass-card p-12 text-center text-white/35">
                <?= e(t('tariflar_yoq')) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── To'lov usuli paneli ────────────────────────── -->
        <div x-show="tarif !== null"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="max-w-md mx-auto glass-card p-6 fade-up"
             x-cloak>

            <h3 class="font-display font-bold text-base mb-5 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-blue-500/15 text-blue-400
                             flex items-center justify-center text-sm">💳</span>
                <?= e(t('tolov_usuli')) ?>
            </h3>

            <!-- Variantlar -->
            <div class="space-y-2.5 mb-5">

                <!-- Click -->
                <label class="flex items-center gap-3 p-3.5 rounded-xl border-[1.5px]
                               cursor-pointer transition-all"
                       :class="tolov === 'click'
                           ? 'border-blue-500 bg-blue-500/[0.07]'
                           : 'border-white/[0.09] hover:border-white/20'">
                    <input type="radio" name="_r" value="click"
                           x-model="tolov" class="hidden">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center
                                justify-center font-bold text-blue-400 text-xs flex-shrink-0">
                        CLK
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= e(t('click_orqali')) ?></p>
                        <p class="text-xs text-white/35">Click.uz orqali to'lash</p>
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                         :class="tolov === 'click' ? 'border-blue-500 bg-blue-500' : 'border-white/25'">
                        <svg x-show="tolov === 'click'" class="w-3 h-3 text-white"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </label>

                <!-- Payme -->
                <label class="flex items-center gap-3 p-3.5 rounded-xl border-[1.5px]
                               cursor-pointer transition-all"
                       :class="tolov === 'payme'
                           ? 'border-cyan-500 bg-cyan-500/[0.07]'
                           : 'border-white/[0.09] hover:border-white/20'">
                    <input type="radio" name="_r" value="payme"
                           x-model="tolov" class="hidden">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 flex items-center
                                justify-center font-bold text-cyan-400 text-xs flex-shrink-0">
                        PM
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= e(t('payme_orqali')) ?></p>
                        <p class="text-xs text-white/35">Payme.uz orqali to'lash</p>
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                         :class="tolov === 'payme' ? 'border-cyan-500 bg-cyan-500' : 'border-white/25'">
                        <svg x-show="tolov === 'payme'" class="w-3 h-3 text-white"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </label>

                <!-- Bonus (faqat balans bo'lsa) -->
                <?php if ((float)($f['bonus_balans'] ?? 0) > 0): ?>
                <label class="flex items-center gap-3 p-3.5 rounded-xl border-[1.5px]
                               cursor-pointer transition-all"
                       :class="tolov === 'bonus'
                           ? 'border-emerald-500 bg-emerald-500/[0.07]'
                           : 'border-white/[0.09] hover:border-white/20'">
                    <input type="radio" name="_r" value="bonus"
                           x-model="tolov" class="hidden">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center
                                justify-center text-emerald-400 text-xl flex-shrink-0">
                        🎁
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= e(t('bonus_balans')) ?></p>
                        <p class="text-xs text-emerald-400 font-semibold">
                            <?= e(pul($f['bonus_balans'])) ?> mavjud
                        </p>
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                         :class="tolov === 'bonus' ? 'border-emerald-500 bg-emerald-500' : 'border-white/25'">
                        <svg x-show="tolov === 'bonus'" class="w-3 h-3 text-white"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </label>
                <?php endif; ?>
            </div>

            <!-- Promo kod -->
            <div class="mb-5">
                <label class="field-label"><?= e(t('promo_kod')) ?> (ixtiyoriy)</label>
                <input name="promo_kod"
                       placeholder="PROMO2025"
                       class="field uppercase tracking-widest font-mono"
                       maxlength="30">
            </div>

            <!-- Jami narx -->
            <div class="p-3.5 rounded-xl bg-white/[0.04] border border-white/[0.08] mb-5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/60">Tanlangan tarif:</span>
                    <span class="font-semibold text-white" x-text="tariflar.find(t => t.id === tarif)?.nomi || '—'"></span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1.5">
                    <span class="text-white/60">Jami to'lov:</span>
                    <span class="font-display font-bold text-blue-400 tabnum text-lg"
                          x-text="tariflar.find(t => t.id === tarif)
                              ? new Intl.NumberFormat('ru').format(tariflar.find(t => t.id === tarif).narx) + ' so\'m'
                              : '—'">
                    </span>
                </div>
            </div>

            <!-- Submit tugma -->
            <button type="submit"
                    class="btn btn-primary w-full btn-lg"
                    :disabled="loading || tarif === null">
                <svg x-show="loading" class="w-5 h-5 animate-spin"
                     fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span x-text="loading
                    ? '<?= e(t('yuklanmoqda')) ?>'
                    : '🔒 <?= e(t('tasdiqlash')) ?>'">
                    🔒 <?= e(t('tasdiqlash')) ?>
                </span>
            </button>

            <p class="text-center text-xs text-white/30 mt-3">
                SSL orqali himoyalangan xavfsiz to'lov
            </p>
        </div>

    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
