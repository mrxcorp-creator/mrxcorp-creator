<?php
/**
 * VatanParvar Yaypan — Tarif tanlash va to'lov
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = kirgan_bolish_kerak();
$f = db_qator('SELECT * FROM foydalanuvchilar WHERE id = ?', [$f['id']]);

$tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$xato = '';

function _obuna_ochish_p(int $uid, array $tarif): void
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        $tarif_id   = (int) post('tarif_id');
        $tolov_turi = post('tolov_turi');
        $promo_kod  = strtoupper(trim(post('promo_kod')));
        $tarif      = $tarif_id
            ? db_qator('SELECT * FROM tariflar WHERE id = ? AND holat = "faol"', [$tarif_id])
            : null;

        if (!$tarif) {
            $xato = t('malumot_yoq');
        } elseif (!in_array($tolov_turi, ['click','payme','bonus'], true)) {
            $xato = t('tolov_xato');
        } else {
            $jami = (float) $tarif['narx'];
            $promo = null;
            if ($promo_kod) {
                $promo = db_qator(
                    'SELECT * FROM promo_kodlar
                     WHERE kod = ? AND holat = "faol"
                       AND (tugash_sanasi IS NULL OR tugash_sanasi > NOW())
                       AND ishlatilgan < maks_ishlatish',
                    [$promo_kod]
                );
                if ($promo) {
                    $cheg = (float)$promo['chegirma_foiz'] > 0
                        ? round($jami * (float)$promo['chegirma_foiz'] / 100)
                        : min((float)$promo['chegirma_summa'], $jami);
                    $jami = max(0, $jami - $cheg);
                }
            }

            if ($tolov_turi === 'bonus') {
                if ((float)$f['bonus_balans'] < $jami) {
                    $xato = 'Bonus yetarli emas (' . pul($f['bonus_balans']) . ')';
                } else {
                    db()->beginTransaction();
                    try {
                        db_bajar('UPDATE foydalanuvchilar SET bonus_balans = bonus_balans - ? WHERE id = ?', [$jami, $f['id']]);
                        db_bajar(
                            'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                             VALUES (?, ?, ?, "bonus", "muvaffaqiyatli", ?)',
                            [$f['id'], $tarif_id, $jami, $promo_kod ?: null]
                        );
                        _obuna_ochish_p($f['id'], $tarif);
                        if ($promo) db_bajar('UPDATE promo_kodlar SET ishlatilgan = ishlatilgan + 1 WHERE id = ?', [$promo['id']]);
                        db()->commit();
                        if ($f['telegram_id']) {
                            telegram_yubor((int)$f['telegram_id'],
                                "✓ <b>To'lov muvaffaqiyatli!</b>\nTarif: {$tarif['nomi']}\n" . pul($jami));
                        }
                        flash_qoy('muvaffaqiyat', t('tolov_muvaffaqiyat'));
                        yonaltir(SAYT_URL . '/dashboard');
                    } catch (Throwable $e) {
                        db()->rollBack();
                        $xato = t('tolov_xato');
                    }
                }
            } else {
                $tolov_id = db_bajar(
                    'INSERT INTO tolovlar (foydalanuvchi_id, tarif_id, summa, tolov_turi, holat, izoh)
                     VALUES (?, ?, ?, ?, "kutilmoqda", ?)',
                    [$f['id'], $tarif_id, $jami, $tolov_turi, $promo_kod ?: null]
                );
                if ($tolov_turi === 'click') {
                    $merch = sozlama('click_merchant_id', '');
                    $serv  = sozlama('click_service_id', '');
                    if ($merch && $serv) {
                        $url = "https://my.click.uz/services/pay?service_id={$serv}&merchant_id={$merch}&amount={$jami}&transaction_param={$tolov_id}&return_url=" . urlencode(SAYT_URL.'/dashboard');
                        yonaltir($url);
                    } else $xato = "Click sozlanmagan";
                } else {
                    $merch = sozlama('payme_merchant_id', '');
                    if ($merch) {
                        $params = base64_encode("m={$merch};ac.tolov_id={$tolov_id};a=" . (int)($jami * 100));
                        yonaltir("https://checkout.paycom.uz/{$params}");
                    } else $xato = "Payme sozlanmagan";
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

$sahifa_sarlavha = t('tolov_sarlavha');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main style="max-width:1100px; margin:0 auto; padding: 2.5rem 1.25rem 4rem;">

    <div style="text-align:center; margin-bottom: 2.5rem;">
        <p style="font-size:.78rem; color:#666; text-transform:uppercase;
                  letter-spacing:.08em; margin-bottom:.5rem;">Narxlar</p>
        <h1 style="font-family:Georgia,serif; font-weight:700;
                   font-size: clamp(1.75rem, 4vw, 2.5rem); margin-bottom: .5rem;">
            <?= e(t('tolov_sarlavha')) ?>
        </h1>
        <p style="color:#555;"><?= e(t('tariflar_tavsif')) ?></p>
    </div>

    <?php if ($xato): ?>
    <div style="max-width: 32rem; margin: 0 auto 1.5rem;
                padding:.75rem 1rem; background:#000; color:#fff;
                font-size:.85rem;">
        <?= e($xato) ?>
    </div>
    <?php endif; ?>

    <?php if ($obuna): ?>
    <div class="b-card" style="max-width: 32rem; margin: 0 auto 2rem; padding: 1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;
                    gap:1rem; flex-wrap:wrap;">
            <div>
                <div style="font-size:.78rem; color:#666;
                            text-transform:uppercase; letter-spacing:.08em;
                            margin-bottom:.3rem;">
                    Faol obuna
                </div>
                <strong style="font-family:Georgia,serif; font-size:1.1rem;">
                    <?= e($obuna['tarif_nomi']) ?>
                </strong>
                <div style="font-size:.85rem; color:#666; margin-top:.25rem;">
                    <?= e(t('tugaydigan_sana')) ?>: <?= e(sana($obuna['tugash'], 'd.m.Y')) ?>
                </div>
            </div>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-sm">Testga →</a>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST"
          x-data="{
            tarif: null, tolov: 'click', loading: false,
            tariflar: <?= json_encode(
                array_map(fn($t) => ['id'=>(int)$t['id'],'narx'=>(float)$t['narx'],'nomi'=>$t['nomi']], $tariflar),
                JSON_UNESCAPED_UNICODE
            ) ?>
          }"
          @submit="loading = true">
        <?= csrf_input() ?>
        <input type="hidden" name="tarif_id" :value="tarif">
        <input type="hidden" name="tolov_turi" :value="tolov">

        <!-- Tariflar -->
        <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                    border:1px solid #000; margin-bottom: 2rem;"
             class="md:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($tariflar as $i => $tar):
                $is_pro = !empty($tar['mashhur']);
            ?>
            <label @click="tarif = <?= (int)$tar['id'] ?>"
                   :style="tarif === <?= (int)$tar['id'] ?> ? 'background:#000; color:#fff;' : 'background:#fff; color:#000;'"
                   style="padding: 1.5rem; cursor:pointer;
                          display:flex; flex-direction:column;
                          <?= $i > 0 ? 'border-top: 1px solid #E5E5E5;' : '' ?>
                          transition: background-color .15s, color .15s;"
                   class="md:border-t-0 md:[&:not(:first-child)]:border-l md:[&:not(:first-child)]:border-l-[#E5E5E5]">
                <input type="radio" name="_t" value="<?= (int)$tar['id'] ?>"
                       style="display:none;">

                <?php if ($is_pro): ?>
                <span style="display:inline-block; padding:.15rem .55rem;
                             border:1px solid currentColor;
                             font-size:.7rem; margin-bottom: .85rem;
                             align-self:flex-start; text-transform:uppercase;
                             letter-spacing:.06em;">
                    Tavsiya
                </span>
                <?php endif; ?>

                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.25rem; margin-bottom: .5rem;">
                    <?= e($tar['nomi']) ?>
                </h3>

                <?php if ((float)($tar['eski_narx'] ?? 0) > (float)$tar['narx']): ?>
                <div style="font-size:.82rem; text-decoration:line-through; opacity:.6;">
                    <?= e(pul($tar['eski_narx'])) ?>
                </div>
                <?php endif; ?>

                <div style="font-family:Georgia,serif; font-weight:700;
                            font-size: 2rem; line-height:1;
                            margin-bottom: .25rem;" class="tabnum">
                    <?= number_format((float)$tar['narx'], 0, '.', ' ') ?>
                </div>
                <div style="font-size:.82rem; opacity:.7; margin-bottom: 1rem;">
                    so'm
                </div>

                <p style="font-size:.85rem; line-height:1.55; flex:1; opacity:.85;">
                    <?= e($tar['tavsif']) ?>
                </p>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- To'lov usuli -->
        <div x-show="tarif !== null" x-transition x-cloak class="b-card"
             style="max-width: 28rem; margin: 0 auto; padding: 2rem;">

            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.15rem; padding-bottom: .65rem;
                       border-bottom: 1px solid #000; margin-bottom: 1.25rem;">
                <?= e(t('tolov_usuli')) ?>
            </h3>

            <div style="display:flex; flex-direction:column; gap:.5rem;
                        margin-bottom: 1.25rem;">
                <?php
                $usul = [['click','Click orqali'], ['payme','Payme orqali']];
                foreach ($usul as [$v, $nom]):
                ?>
                <label :style="tolov === '<?= $v ?>' ? 'background:#000;color:#fff;' : 'background:#fff;color:#000;'"
                       style="display:flex; align-items:center; gap:.75rem;
                              padding: .85rem 1rem; border: 1px solid #000;
                              cursor:pointer; transition: background-color .15s;">
                    <input type="radio" name="_r" value="<?= $v ?>" x-model="tolov" style="display:none;">
                    <span style="display:flex; align-items:center; justify-content:center;
                                 width:18px; height:18px; border:1px solid currentColor;
                                 flex-shrink:0;">
                        <span x-show="tolov === '<?= $v ?>'" style="width:8px;height:8px;background:currentColor;"></span>
                    </span>
                    <span style="font-size:.9rem; font-weight:500;"><?= $nom ?></span>
                </label>
                <?php endforeach; ?>

                <?php if ((float)($f['bonus_balans'] ?? 0) > 0): ?>
                <label :style="tolov === 'bonus' ? 'background:#000;color:#fff;' : 'background:#fff;color:#000;'"
                       style="display:flex; align-items:center; gap:.75rem;
                              padding: .85rem 1rem; border: 1px solid #000;
                              cursor:pointer;">
                    <input type="radio" name="_r" value="bonus" x-model="tolov" style="display:none;">
                    <span style="display:flex; align-items:center; justify-content:center;
                                 width:18px; height:18px; border:1px solid currentColor;
                                 flex-shrink:0;">
                        <span x-show="tolov === 'bonus'" style="width:8px;height:8px;background:currentColor;"></span>
                    </span>
                    <span style="font-size:.9rem; font-weight:500;">
                        Bonus balans (<?= e(pul($f['bonus_balans'])) ?>)
                    </span>
                </label>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="field-label"><?= e(t('promo_kod')) ?> (ixtiyoriy)</label>
                <input name="promo_kod" placeholder="PROMO2025"
                       class="field" style="text-transform:uppercase;
                       letter-spacing:.1em; font-family:monospace;" maxlength="30">
            </div>

            <!-- Jami -->
            <div style="padding: .85rem 1rem; background: #FAFAFA;
                        border:1px solid #E5E5E5; margin-bottom: 1.25rem;">
                <div style="display:flex; justify-content:space-between;
                            align-items:center; font-size:.875rem;">
                    <span style="color:#666;">Tanlangan tarif:</span>
                    <strong x-text="tariflar.find(t => t.id === tarif)?.nomi || '—'"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;
                            align-items:center; margin-top:.5rem;
                            padding-top:.5rem; border-top:1px solid #E5E5E5;">
                    <span style="color:#666;">Jami:</span>
                    <strong style="font-family:Georgia,serif; font-size:1.25rem;"
                            class="tabnum"
                            x-text="tariflar.find(t => t.id === tarif)
                                ? new Intl.NumberFormat('ru').format(tariflar.find(t => t.id === tarif).narx) + ' so\'m'
                                : '—'"></strong>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg"
                    style="width:100%;"
                    :disabled="loading || tarif === null">
                <span x-text="loading ? '<?= e(t('yuklanmoqda')) ?>' : '<?= e(t('tasdiqlash')) ?>'">
                    <?= e(t('tasdiqlash')) ?>
                </span>
            </button>

            <p style="font-size:.78rem; color:#666; text-align:center;
                      margin-top: .85rem;">
                Xavfsiz SSL to'lov
            </p>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
