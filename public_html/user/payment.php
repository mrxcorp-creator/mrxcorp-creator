<?php
/**
 * VatanParvar Yaypan — Tarif tanlash va to'lov sahifasi
 */
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');

$xato = '';
$muvaffaqiyat = '';
$promo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $tarif_id = (int) post('tarif_id');
        $tolov_turi = post('tolov_turi');
        $promo_kod = trim(post('promo_kod'));

        $tarif = db_qator('SELECT * FROM tariflar WHERE id = ? AND holat = "faol"', [$tarif_id]);
        if (!$tarif) {
            $xato = t('malumot_yoq');
        } elseif (!in_array($tolov_turi, ['click', 'payme', 'bonus'], true)) {
            $xato = t('tolov_xato');
        } else {
            // Promo kod tekshiruvi
            $jami = (float) $tarif['narx'];
            $chegirma = 0;
            if ($promo_kod) {
                $promo = db_qator(
                    'SELECT * FROM promo_kodlar
                     WHERE kod = ? AND holat = "faol"
                       AND (tugash_sanasi IS NULL OR tugash_sanasi > NOW())
                       AND ishlatilgan < maks_ishlatish',
                    [$promo_kod]
                );
                if ($promo) {
                    if ($promo['chegirma_foiz'] > 0) {
                        $chegirma = round($jami * $promo['chegirma_foiz'] / 100);
                    } else {
                        $chegirma = (float) $promo['chegirma_summa'];
                    }
                    $jami = max(0, $jami - $chegirma);
                }
            }

            // Bonus to'lov — atomik tekshiruv (race-condition'siz)
            if ($tolov_turi === 'bonus') {
                db()->beginTransaction();
                try {
                    // Atomik UPDATE — agar balans yetarli bo'lmasa, 0 qator yangilanadi
                    $afected = db_bajar(
                        'UPDATE foydalanuvchilar SET bonus_balans = bonus_balans - ?
                         WHERE id = ? AND bonus_balans >= ?',
                        [$jami, $f['id'], $jami]
                    );
                    if (!$afected) {
                        db()->rollBack();
                        $f = joriy_foydalanuvchi();
                        $xato = "Bonus balansi yetarli emas (" . pul($f['bonus_balans']) . ")";
                    } else {
                        $tolov_id = db_bajar(
                            'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                             VALUES (?, ?, ?, "bonus", "muvaffaqiyatli", ?)',
                            [$f['id'], $tarif_id, $jami, $promo_kod ? "Promo: $promo_kod" : null]
                        );

                        $kun = (int) match ($tarif['tur']) {
                            'kun' => $tarif['qiymat'],
                            'oy'  => $tarif['qiymat'] * 30,
                            default => 365,
                        };
                        db_bajar(
                            'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, boshlanish, tugash, holat)
                             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                            [$f['id'], $tarif_id, $kun]
                        );

                        if ($promo) {
                            db_bajar('UPDATE promo_kodlar SET ishlatilgan = ishlatilgan + 1 WHERE id = ?',
                                     [$promo['id']]);
                        }

                        db()->commit();

                        if ($f['telegram_id']) {
                            $tarif_nomi = ($f['til'] ?? 'uz_latn') === 'uz_cyrl'
                                ? ($tarif['nomi_cyrl'] ?: $tarif['nomi'])
                                : $tarif['nomi'];
                            telegram_yubor($f['telegram_id'],
                                "✅ <b>To'lov muvaffaqiyatli!</b>\n\nTarif: <b>{$tarif_nomi}</b>\nSumma: <b>" . pul($jami) . "</b>");
                        }

                        flash_qoy('muvaffaqiyat', t('tolov_muvaffaqiyat'));
                        yonaltir(SAYT_URL . '/dashboard');
                    }
                } catch (Exception $exc) {
                    db()->rollBack();
                    $xato = t('tolov_xato');
                }
            } else {
                // Click yoki Payme — kutilayotgan to'lov yaratamiz
                $tolov_id = db_bajar(
                    'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, ?, "kutilmoqda", ?)',
                    [$f['id'], $tarif_id, $jami, $tolov_turi, $promo_kod ? "Promo: $promo_kod" : null]
                );

                if ($tolov_turi === 'click') {
                    $merchant   = sozlama('click_merchant_id');
                    $service_id = sozlama('click_service_id');
                    $url = ($merchant && $service_id)
                        ? "https://my.click.uz/services/pay?service_id={$service_id}&merchant_id={$merchant}&amount={$jami}&transaction_param={$tolov_id}&return_url=" . urlencode(SAYT_URL . '/dashboard')
                        : SAYT_URL . '/dashboard';
                    yonaltir($url);
                } else {
                    $merchant = sozlama('payme_merchant_id');
                    $params = base64_encode("m={$merchant};ac.tolov_id={$tolov_id};a=" . ($jami * 100));
                    yonaltir("https://checkout.paycom.uz/{$params}");
                }
            }
        }
    }
}

$obuna = db_qator(
    'SELECT o.*, t.nomi AS tarif_nomi, t.nomi_cyrl AS tarif_nomi_cyrl FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$sahifa_sarlavha = t('tariflar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 py-10">
    <div class="mb-10 text-center fade-up">
        <span class="badge badge-accent mb-3"><?= e(t('tariflar')) ?></span>
        <h1 class="text-3xl md:text-4xl mb-3"><?= e(t('tolov_sarlavha')) ?></h1>
        <p class="text-app-2 text-base"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if ($xato): ?>
        <div class="max-w-md mx-auto mb-6 p-3.5 rounded-xl border text-sm flex items-start gap-2"
             style="background: var(--error-soft); border-color: color-mix(in srgb, var(--error) 30%, transparent); color: var(--error);">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.7 7.3a1 1 0 0 0-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 1 0 1.4 1.4L10 11.4l1.3 1.3a1 1 0 1 0 1.4-1.4L11.4 10l1.3-1.3a1 1 0 1 0-1.4-1.4L10 8.6 8.7 7.3z"/></svg>
            <span><?= e($xato) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($obuna): ?>
        <div class="glass-card-premium p-5 mb-8 max-w-2xl mx-auto fade-up relative overflow-hidden"
             style="border-color: color-mix(in srgb, var(--success) 35%, transparent);">
            <div class="absolute -top-16 -right-16 w-40 h-40 rounded-full opacity-25 blur-3xl"
                 style="background: var(--gradient-success);"></div>
            <div class="relative flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-md flex-shrink-0"
                     style="background: var(--gradient-success);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                </div>
                <div>
                    <strong class="text-success">Faol obuna: <?= e(tk(['nomi' => $obuna['tarif_nomi'], 'nomi_cyrl' => $obuna['tarif_nomi_cyrl']], 'nomi')) ?></strong>
                    <div class="text-sm text-app-2"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'])) ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" x-data="{tarif: null, tolov: 'click', loading: false}"
          @submit="loading = true">
        <?= csrf_input() ?>
        <input type="hidden" name="tarif_id" :value="tarif">
        <input type="hidden" name="tolov_turi" :value="tolov">

        <!-- Tarif kartalari -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <?php foreach ($tariflar as $i => $t): ?>
                <div @click="tarif = <?= (int)$t['id'] ?>"
                     :class="tarif === <?= (int)$t['id'] ?> ? 'ring-2 shadow-xl' : ''"
                     :style="tarif === <?= (int)$t['id'] ?> ? '--tw-ring-color: var(--accent); box-shadow: 0 16px 40px var(--accent-glow);' : ''"
                     class="<?= $t['mashhur'] ? 'gradient-border' : 'glass-card glass-card-hover' ?> p-6 cursor-pointer fade-up relative"
                     style="animation-delay:<?= 0.05 * ($i + 1) ?>s">
                    <?php if ($t['mashhur']): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold text-white shadow-md whitespace-nowrap"
                             style="background: var(--gradient-primary);">
                            ⭐ <?= e(t('mashhur')) ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="text-xl font-display mb-3 text-app"><?= e(tk($t, 'nomi')) ?></h3>
                    <?php if ($t['eski_narx'] && $t['eski_narx'] > $t['narx']): ?>
                        <span class="line-through text-app-2 text-sm"><?= e(pul($t['eski_narx'])) ?></span>
                    <?php endif; ?>
                    <div class="text-3xl font-display font-extrabold gradient-text mb-3"><?= e(pul($t['narx'])) ?></div>
                    <p class="text-sm text-app-2 leading-relaxed"><?= e(tk($t, 'tavsif')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($tariflar)): ?>
            <div class="glass-card p-12 text-center text-app-2">
                <p><?= e(t('tariflar_yoq')) ?></p>
            </div>
        <?php endif; ?>

        <!-- To'lov usuli + promo + tugma -->
        <div x-show="tarif" x-transition class="max-w-md mx-auto glass-card-premium p-7 fade-up" x-cloak>
            <h3 class="font-display text-lg mb-5 text-app"><?= e(t('tolov_usuli')) ?></h3>

            <div class="space-y-2.5 mb-5">
                <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                       :class="tolov === 'click' ? 'border-app' : 'border-app hover:border-strong'"
                       :style="tolov === 'click' ? 'background: var(--accent-soft); border-color: var(--accent);' : ''">
                    <input type="radio" name="tolov_radio" value="click" x-model="tolov" class="hidden">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-sm"
                          style="background: linear-gradient(135deg, #3B82F6, #2563EB);">CLICK</span>
                    <span class="flex-1 text-app font-medium"><?= e(t('click_orqali')) ?></span>
                    <svg x-show="tolov === 'click'" class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                </label>

                <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                       :class="tolov === 'payme' ? 'border-app' : 'border-app hover:border-strong'"
                       :style="tolov === 'payme' ? 'background: var(--accent-soft); border-color: var(--accent);' : ''">
                    <input type="radio" name="tolov_radio" value="payme" x-model="tolov" class="hidden">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-sm"
                          style="background: linear-gradient(135deg, #06B6D4, #0891B2);">PAYME</span>
                    <span class="flex-1 text-app font-medium"><?= e(t('payme_orqali')) ?></span>
                    <svg x-show="tolov === 'payme'" class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                </label>

                <?php if ((float)$f['bonus_balans'] > 0): ?>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                           :class="tolov === 'bonus' ? 'border-app' : 'border-app hover:border-strong'"
                           :style="tolov === 'bonus' ? 'background: var(--accent-soft); border-color: var(--accent);' : ''">
                        <input type="radio" name="tolov_radio" value="bonus" x-model="tolov" class="hidden">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white shadow-sm"
                              style="background: var(--gradient-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                        </span>
                        <span class="flex-1 text-app font-medium"><?= e(t('bonus_balans')) ?>: <?= e(pul($f['bonus_balans'])) ?></span>
                    </label>
                <?php endif; ?>
            </div>

            <div>
                <label class="field-label"><?= e(t('promo_kod')) ?></label>
                <input name="promo_kod" placeholder="ABCD2025" class="field uppercase">
            </div>

            <button type="submit" class="btn-primary w-full mt-6 text-base py-3.5" :disabled="loading">
                <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
            </button>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
