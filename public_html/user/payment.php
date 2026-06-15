<?php
/**
 * AvtoTest Pro — Tarif tanlash va to'lov sahifasi
 * BUG FIX: Click URL da service_id qo'shildi (sozlamalardan o'qiladi)
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();
// Foydalanuvchini yangilab olamiz (bonus_balans uchun)
$f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$f['id']]);

$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$xato = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $tarif_id  = (int) post('tarif_id');
        $tolov_turi = post('tolov_turi');
        $promo_kod  = strtoupper(trim(post('promo_kod')));

        $tarif = $tarif_id ? db_qator(
            'SELECT * FROM tariflar WHERE id = ? AND holat = "faol"', [$tarif_id]
        ) : null;

        if (!$tarif) {
            $xato = t('malumot_yoq');
        } elseif (!in_array($tolov_turi, ['click', 'payme', 'bonus'], true)) {
            $xato = t('tolov_xato');
        } else {
            $jami     = (float) $tarif['narx'];
            $chegirma = 0;
            $promo    = null;

            // Promo kod tekshiruvi
            if ($promo_kod) {
                $promo = db_qator(
                    'SELECT * FROM promo_kodlar
                     WHERE kod = ? AND holat = "faol"
                       AND (tugash_sanasi IS NULL OR tugash_sanasi > NOW())
                       AND ishlatilgan < maks_ishlatish',
                    [$promo_kod]
                );
                if ($promo) {
                    if ((float)$promo['chegirma_foiz'] > 0) {
                        $chegirma = round($jami * (float)$promo['chegirma_foiz'] / 100);
                    } else {
                        $chegirma = min((float)$promo['chegirma_summa'], $jami);
                    }
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
                        $tolov_id = db_bajar(
                            'INSERT INTO tolovlar
                             (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                             VALUES (?, ?, ?, "bonus", "muvaffaqiyatli", ?)',
                            [$f['id'], $tarif_id, $jami, $promo_kod ?: null]
                        );
                        _obuna_ochish($f['id'], $tarif, $tolov_id);
                        if ($promo) {
                            db_bajar('UPDATE promo_kodlar SET ishlatilgan = ishlatilgan + 1 WHERE id = ?', [$promo['id']]);
                        }
                        db()->commit();
                        if ($f['telegram_id']) {
                            telegram_yubor($f['telegram_id'],
                                "✅ <b>To'lov muvaffaqiyatli!</b>\n\nTarif: <b>{$tarif['nomi']}</b>\nSumma: <b>" . pul($jami) . '</b>');
                        }
                        flash_qoy('muvaffaqiyat', t('tolov_muvaffaqiyat'));
                        yonaltir(SAYT_URL . '/dashboard');
                    } catch (Throwable $e) {
                        db()->rollBack();
                        error_log('Bonus tolov xato: ' . $e->getMessage());
                        $xato = t('tolov_xato');
                    }
                }
            } else {
                // Click / Payme — kutilayotgan to'lov yozuvi
                $tolov_id = db_bajar(
                    'INSERT INTO tolovlar
                     (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, ?, "kutilmoqda", ?)',
                    [$f['id'], $tarif_id, $jami, $tolov_turi, $promo_kod ?: null]
                );

                if ($tolov_turi === 'click') {
                    // BUG FIX: service_id endi sozlamalardan o'qiladi
                    $merchant_id = sozlama('click_merchant_id', '');
                    $service_id  = sozlama('click_service_id', '');
                    if ($merchant_id && $service_id) {
                        $return_url = urlencode(SAYT_URL . '/dashboard');
                        $url = "https://my.click.uz/services/pay"
                             . "?service_id={$service_id}"
                             . "&merchant_id={$merchant_id}"
                             . "&amount={$jami}"
                             . "&transaction_param={$tolov_id}"
                             . "&return_url={$return_url}";
                    } else {
                        $url = SAYT_URL . '/dashboard';
                    }
                    yonaltir($url);
                } else {
                    $merchant_id = sozlama('payme_merchant_id', '');
                    if ($merchant_id) {
                        $params = base64_encode("m={$merchant_id};ac.tolov_id={$tolov_id};a=" . (int)($jami * 100));
                        yonaltir("https://checkout.paycom.uz/{$params}");
                    } else {
                        $xato = 'Payme sozlanmagan. Admin bilan bog\'laning.';
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

// Obuna yaratish yordamchi funksiyasi
function _obuna_ochish(int $uid, array $tarif, int $tolov_id): void
{
    $kun = match ($tarif['tur']) {
        'kun'   => (int) $tarif['qiymat'],
        'oy'    => (int) $tarif['qiymat'] * 30,
        default => 365,
    };
    db_bajar(
        'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
         VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
        [$uid, $tarif['id'], $kun]
    );
}

$sahifa_sarlavha = t('tolov_sarlavha');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 py-10">
    <div class="text-center mb-10 fade-up">
        <h1 class="text-3xl md:text-4xl font-display mb-2"><?= e(t('tolov_sarlavha')) ?></h1>
        <p class="text-brand-muted"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if ($xato): ?>
        <div class="max-w-lg mx-auto mb-6 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2">
            ⚠️ <?= e($xato) ?>
        </div>
    <?php endif; ?>

    <?php if ($obuna): ?>
        <div class="glass-card p-5 mb-8 border-green-500/30 bg-green-500/[0.04] max-w-2xl mx-auto">
            <div class="flex items-center gap-3">
                <span class="text-3xl flex-shrink-0">🎉</span>
                <div>
                    <p class="font-semibold text-green-400">Faol obuna: <?= e($obuna['tarif_nomi']) ?></p>
                    <p class="text-sm text-brand-muted"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'])) ?></p>
                </div>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn-ghost text-sm py-2 px-4 ml-auto">Test →</a>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST"
          x-data="{
            tarif: null,
            tolov: 'click',
            loading: false,
            promo: '',
            chegirma: 0,
            tariflar: <?= json_encode(array_map(fn($t) => ['id'=>(int)$t['id'],'narx'=>(float)$t['narx']], $tariflar), JSON_UNESCAPED_UNICODE) ?>
          }"
          @submit="loading = true">
        <?= csrf_input() ?>
        <input type="hidden" name="tarif_id"  :value="tarif">
        <input type="hidden" name="tolov_turi" :value="tolov">

        <!-- Tarif kartalari -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php foreach ($tariflar as $i => $tar): ?>
                <div @click="tarif = <?= (int)$tar['id'] ?>"
                     :class="tarif === <?= (int)$tar['id'] ?>
                         ? 'border-blue-500 ring-2 ring-blue-500/40 bg-blue-500/[0.06]'
                         : 'hover:border-white/20 cursor-pointer'"
                     class="glass-card p-6 fade-up relative transition-all select-none"
                     style="animation-delay:<?= 0.05 * ($i + 1) ?>s">

                    <?php if ($tar['mashhur']): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white whitespace-nowrap shadow-glow-sm">
                            ⭐ <?= e(t('mashhur')) ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="font-display text-lg mb-1"><?= e($tar['nomi']) ?></h3>

                    <?php if ($tar['eski_narx'] && (float)$tar['eski_narx'] > (float)$tar['narx']): ?>
                        <div class="line-through text-brand-muted text-sm mb-1"><?= e(pul($tar['eski_narx'])) ?></div>
                        <div class="badge badge-green text-xs mb-2">
                            -<?= round((1 - $tar['narx']/$tar['eski_narx']) * 100) ?>%
                        </div>
                    <?php endif; ?>

                    <div class="text-3xl font-display font-bold text-blue-400 mb-3">
                        <?= e(number_format((float)$tar['narx'], 0, '.', ' ')) ?>
                        <span class="text-sm font-normal text-brand-muted">so'm</span>
                    </div>

                    <p class="text-xs text-brand-muted leading-relaxed"><?= e($tar['tavsif']) ?></p>

                    <!-- Tanlash indikatori -->
                    <div class="mt-4 flex items-center justify-center">
                        <div :class="tarif === <?= (int)$tar['id'] ?> ? 'bg-blue-500 border-blue-500' : 'border-white/20'"
                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all">
                            <div x-show="tarif === <?= (int)$tar['id'] ?>" class="w-2 h-2 bg-white rounded-full" x-cloak></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($tariflar)): ?>
            <div class="glass-card p-12 text-center text-brand-muted"><?= e(t('tariflar_yoq')) ?></div>
        <?php endif; ?>

        <!-- To'lov usuli + promo + tugma -->
        <div x-show="tarif !== null" x-transition x-cloak
             class="max-w-md mx-auto glass-card p-6 fade-up">
            <h3 class="font-display text-lg mb-5"><?= e(t('tolov_usuli')) ?></h3>

            <div class="space-y-2 mb-6">
                <?php
                $tolov_usullari = [
                    ['click', 'CLK', 'bg-blue-500/20 text-blue-400', t('click_orqali')],
                    ['payme', 'PM',  'bg-cyan-500/20 text-cyan-400',  t('payme_orqali')],
                ];
                foreach ($tolov_usullari as [$val, $label, $bgClass, $nom]):
                ?>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                           :class="tolov === '<?= $val ?>'
                               ? 'border-blue-500 bg-blue-500/[0.07]'
                               : 'border-white/10 hover:border-white/25'">
                        <input type="radio" name="_tolov_radio" value="<?= $val ?>" x-model="tolov" class="hidden">
                        <span class="w-10 h-10 rounded-xl <?= $bgClass ?> flex items-center justify-center font-bold text-xs flex-shrink-0"><?= $label ?></span>
                        <span class="flex-1 font-medium"><?= e($nom) ?></span>
                        <svg x-show="tolov === '<?= $val ?>'" class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </label>
                <?php endforeach; ?>

                <?php if ((float)($f['bonus_balans'] ?? 0) > 0): ?>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                           :class="tolov === 'bonus' ? 'border-green-500 bg-green-500/[0.07]' : 'border-white/10 hover:border-white/25'">
                        <input type="radio" name="_tolov_radio" value="bonus" x-model="tolov" class="hidden">
                        <span class="w-10 h-10 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center text-lg flex-shrink-0">🎁</span>
                        <span class="flex-1 font-medium"><?= e(t('bonus_balans')) ?>: <strong class="text-green-400"><?= e(pul($f['bonus_balans'])) ?></strong></span>
                        <svg x-show="tolov === 'bonus'" class="w-5 h-5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </label>
                <?php endif; ?>
            </div>

            <!-- Promo kod -->
            <div class="mb-5">
                <label class="field-label"><?= e(t('promo_kod')) ?> (ixtiyoriy)</label>
                <input name="promo_kod" placeholder="PROMO2025"
                       class="field uppercase tracking-widest font-mono"
                       maxlength="30">
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-base" :disabled="loading || tarif === null">
                <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '💳 <?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
            </button>

            <p class="text-xs text-brand-muted text-center mt-3">
                🔒 To'lov xavfsiz SSL orqali amalga oshiriladi
            </p>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
