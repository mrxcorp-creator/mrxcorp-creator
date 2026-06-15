<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');

$xato = '';
$muvaffaqiyat = '';
$promo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_form_tekshir('tolov_yaratish', post('csrf_forma_token'))) {
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

            if ($tolov_turi === 'bonus') {
                if ((float)$f['bonus_balans'] < $jami) {
                    $xato = t('bonus_yetmaydi') . ' (' . pul($f['bonus_balans']) . ')';
                } else {
                    db()->beginTransaction();
                    try {
                        db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans - ? WHERE id = ?',
                                 [$jami, $f['id']]);

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
                            'INSERT INTO obunalar (foydalanuvchi_id, tarif_id, tolov_id, boshlanish, tugash, holat)
                             VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "faol")',
                            [$f['id'], $tarif_id, $tolov_id, $kun]
                        );

                        if ($promo) {
                            db_bajar('UPDATE promo_kodlar SET ishlatilgan = ishlatilgan + 1 WHERE id = ?',
                                     [$promo['id']]);
                        }

                        db()->commit();

                        bonus_yoz(
                            (int) $f['id'],
                            -$jami,
                            'tolov',
                            'Tarif: ' . $tarif['nomi'],
                            (int) $tolov_id
                        );

                        if ($f['telegram_id']) {
                            telegram_yubor($f['telegram_id'],
                                "✅ <b>To'lov muvaffaqiyatli!</b>\n\nTarif: <b>{$tarif['nomi']}</b>\nSumma: <b>" . pul($jami) . "</b>");
                        }

                        flash_qoy('muvaffaqiyat', t('tolov_muvaffaqiyat'));
                        yonaltir(SAYT_URL . '/dashboard');
                    } catch (Exception $exc) {
                        db()->rollBack();
                        $xato = t('tolov_xato');
                    }
                }
            } else {
                $tolov_id = db_bajar(
                    'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, ?, "kutilmoqda", ?)',
                    [$f['id'], $tarif_id, $jami, $tolov_turi, $promo_kod ? "Promo: $promo_kod" : null]
                );

                if ($tolov_turi === 'click') {
                    $merchant = sozlama('click_merchant_id');
                    $service  = sozlama('click_service_id');
                    $url = ($merchant && $service)
                        ? "https://my.click.uz/services/pay?service_id={$service}&merchant_id={$merchant}&amount={$jami}&transaction_param={$tolov_id}&return_url=" . urlencode(SAYT_URL . '/dashboard')
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
    'SELECT o.*, t.nomi AS tarif_nomi FROM obunalar o
     JOIN tariflar t ON o.tarif_id = t.id
     WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
     ORDER BY o.tugash DESC LIMIT 1',
    [$f['id']]
);

$sahifa_sarlavha = t('tariflar');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8 text-center fade-up">
        <span class="chip chip-grad mb-4">💎 <?= e(t('tariflar')) ?></span>
        <h1 class="text-3xl md:text-4xl font-display font-extrabold mb-2"><?= e(t('tolov_sarlavha')) ?></h1>
        <p class="text-muted"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if ($xato): ?>
        <div class="max-w-md mx-auto mb-6 p-3 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm">
            <?= e($xato) ?>
        </div>
    <?php endif; ?>

    <?php if ($obuna): ?>
        <div class="ring-grad max-w-2xl mx-auto mb-8 fade-up">
            <div class="p-5 flex items-center gap-3">
                <span class="text-3xl">🎉</span>
                <div>
                    <strong class="text-success"><?= e(t('obuna_faol')) ?>: <?= e($obuna['tarif_nomi']) ?></strong><br>
                    <span class="text-sm text-muted"><?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'])) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" x-data="{tarif: null, tolov: 'click', loading: false}" @submit="loading = true">
        <?= csrf_form_input('tolov_yaratish') ?>
        <input type="hidden" name="tarif_id" :value="tarif">
        <input type="hidden" name="tolov_turi" :value="tolov">

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php foreach ($tariflar as $t): ?>
                <div @click="tarif = <?= (int)$t['id'] ?>"
                     :class="tarif === <?= (int)$t['id'] ?> ? 'ring-2 ring-violet bg-violet/5 border-violet' : ''"
                     class="glass glass-hover p-6 cursor-pointer fade-up relative">
                    <?php if ($t['mashhur']): ?>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 chip chip-grad font-bold whitespace-nowrap">
                            ⭐ <?= e(t('mashhur')) ?>
                        </span>
                    <?php endif; ?>
                    <h3 class="text-xl font-display font-bold mb-2"><?= e($t['nomi']) ?></h3>
                    <?php if ($t['eski_narx'] && $t['eski_narx'] > $t['narx']): ?>
                        <span class="line-through text-muted text-sm"><?= e(pul($t['eski_narx'])) ?></span>
                    <?php endif; ?>
                    <div class="text-3xl font-display font-extrabold grad-text mb-3"><?= e(pul($t['narx'])) ?></div>
                    <p class="text-sm text-muted"><?= e($t['tavsif']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($tariflar)): ?>
            <div class="glass p-12 text-center text-muted">
                <p><?= e(t('tariflar_yoq')) ?></p>
            </div>
        <?php endif; ?>

        <div x-show="tarif" x-transition class="max-w-md mx-auto ring-grad fade-up" x-cloak>
            <div class="p-6">
                <h3 class="font-display font-bold text-lg mb-4"><?= e(t('tolov_usuli')) ?></h3>

                <div class="space-y-2 mb-5">
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="tolov === 'click' ? 'border-violet bg-violet/10' : 'border-white/10 hover:border-white/20'">
                        <input type="radio" name="tolov_turi_radio" value="click" x-model="tolov" class="hidden">
                        <span class="w-10 h-10 rounded-xl bg-cyan/15 text-cyan flex items-center justify-center font-bold text-sm">CLK</span>
                        <span class="flex-1"><?= e(t('click_orqali')) ?></span>
                        <svg x-show="tolov === 'click'" class="w-5 h-5 text-violet" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="tolov === 'payme' ? 'border-violet bg-violet/10' : 'border-white/10 hover:border-white/20'">
                        <input type="radio" name="tolov_turi_radio" value="payme" x-model="tolov" class="hidden">
                        <span class="w-10 h-10 rounded-xl bg-pink/15 text-pink flex items-center justify-center font-bold text-sm">PM</span>
                        <span class="flex-1"><?= e(t('payme_orqali')) ?></span>
                        <svg x-show="tolov === 'payme'" class="w-5 h-5 text-violet" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"/></svg>
                    </label>

                    <?php if ((float)$f['bonus_balans'] > 0): ?>
                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                               :class="tolov === 'bonus' ? 'border-violet bg-violet/10' : 'border-white/10 hover:border-white/20'">
                            <input type="radio" name="tolov_turi_radio" value="bonus" x-model="tolov" class="hidden">
                            <span class="w-10 h-10 rounded-xl bg-success/15 flex items-center justify-center text-lg">🎁</span>
                            <span class="flex-1"><?= e(t('bonus_balans')) ?>: <?= e(pul($f['bonus_balans'])) ?></span>
                        </label>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="field-label"><?= e(t('promo_kod')) ?></label>
                    <input name="promo_kod" placeholder="ABCD2025" class="field uppercase">
                </div>

                <button type="submit" class="btn btn-primary w-full mt-5" :disabled="loading">
                    <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'"><?= e(t('tasdiqlash')) ?></span>
                </button>
            </div>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
