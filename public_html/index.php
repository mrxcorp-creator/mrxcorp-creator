<?php
/**
 * VatanParvar Yaypan — Bosh sahifa (brutalizm uslubida)
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/funksiyalar.php';

$til_kod    = $_SESSION['til'] ?? TIL_DEFAULT;
$kesh_fayli = CACHE_PATH . '/indeks_' . $til_kod . '.html';
$kesh_ttl   = 3600;

if (!joriy_foydalanuvchi() && file_exists($kesh_fayli) && (time() - filemtime($kesh_fayli)) < $kesh_ttl) {
    header('X-Cache: HIT');
    readfile($kesh_fayli);
    exit;
}
ob_start();

$tariflar          = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx');
$fikrlar           = db_barcha('SELECT * FROM fikrlar WHERE tasdiq = 1 ORDER BY yaratilgan DESC LIMIT 6');
$bilet_son         = (int) db_qiymat('SELECT COUNT(*) FROM biletlar WHERE holat = "faol"');
$savol_son         = (int) db_qiymat('SELECT COUNT(*) FROM savollar');
$foydalanuvchi_son = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');

$sahifa_sarlavha = SAYT_NOMI . ' — ' . t('hero_sarlavha');
$sahifa_tavsif   = t('hero_tavsif');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section style="border-bottom: 1px solid #000;">
    <div style="max-width:1200px; margin:0 auto; padding: 5rem 1.25rem 6rem;">

        <!-- Tepa label -->
        <div style="display:inline-block; padding:.4rem .8rem;
                    border:1px solid #000; font-size:.78rem; font-weight:500;
                    margin-bottom:2rem; letter-spacing:.02em;">
            № 1 — Avto maktab tayyorgarlik platformasi
        </div>

        <!-- Sarlavha -->
        <h1 style="font-family:Georgia,serif; font-weight:700;
                   font-size: clamp(2.5rem, 6vw, 5rem);
                   line-height: 1.05; letter-spacing: -0.03em;
                   margin-bottom: 1.5rem; max-width: 24ch;">
            <?= e(t('hero_sarlavha')) ?>
            <span style="display:inline-block; border-bottom: 4px solid #000;
                         padding-bottom: .15em;">birinchi urinishdan</span>.
        </h1>

        <p style="font-size: 1.15rem; line-height: 1.6;
                  color: #333; max-width: 38rem; margin-bottom: 2.5rem;">
            <?= e(t('hero_tavsif')) ?>
        </p>

        <!-- Tugmalar -->
        <div style="display:flex; flex-wrap:wrap; gap:.75rem;">
            <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-xl">
                <?= e(t('hero_tugma_boshla')) ?> →
            </a>
            <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-ghost btn-xl">
                <?= e(t('hero_tugma_demo')) ?>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════ STATISTIKA ═══════════════ -->
<section style="border-bottom: 1px solid #000;">
    <div style="max-width:1200px; margin:0 auto;
                display:grid; grid-template-columns: 1fr;"
         class="md:grid-cols-3">
        <?php
        $stats = [
            [$bilet_son . '+',         t('biletlar_royxati')],
            [$savol_son . '+',         'Real savollar'],
            [$foydalanuvchi_son . '+', 'Foydalanuvchilar'],
        ];
        foreach ($stats as $i => [$v, $n]):
        ?>
        <div style="padding: 2.5rem 1.25rem; text-align:center;
                    <?= $i > 0 ? 'border-top: 1px solid #E5E5E5;' : '' ?>"
             class="md:border-t-0 <?= $i > 0 ? 'md:border-l md:border-l-black' : '' ?>">
            <div style="font-family:Georgia,serif; font-weight:700;
                        font-size: 3rem; line-height: 1;
                        margin-bottom: .5rem;" class="tabnum">
                <?= $v ?>
            </div>
            <div style="font-size: .85rem; color: #666;
                        text-transform: uppercase; letter-spacing: .08em;">
                <?= e($n) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════ XUSUSIYATLAR ═══════════════ -->
<section style="border-bottom: 1px solid #000;">
    <div style="max-width:1200px; margin:0 auto; padding: 5rem 1.25rem;">

        <div style="margin-bottom: 3rem;">
            <span style="font-size:.78rem; font-weight:600;
                         text-transform:uppercase; letter-spacing:.1em; color:#666;">
                Imkoniyatlar
            </span>
            <h2 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.75rem, 4vw, 2.5rem);
                       margin-top: .5rem; max-width: 30rem;">
                Mukammal tayyorgarlik uchun barcha vositalar.
            </h2>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                    border:1px solid #000;"
             class="sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $xususiyatlar = [
                ['01', t('xususiyat_1_sarlavha'), t('xususiyat_1_tavsif')],
                ['02', t('xususiyat_2_sarlavha'), t('xususiyat_2_tavsif')],
                ['03', t('xususiyat_3_sarlavha'), t('xususiyat_3_tavsif')],
                ['04', t('xususiyat_4_sarlavha'), t('xususiyat_4_tavsif')],
            ];
            foreach ($xususiyatlar as $i => [$num, $nom, $tavsif]):
                $border = '';
                if ($i % 2 !== 0) $border .= 'border-left: 1px solid #E5E5E5;';
                if ($i >= 2) $border .= 'border-top: 1px solid #E5E5E5;';
            ?>
            <div style="padding: 2rem 1.5rem; <?= $border ?>
                        transition: background-color .15s;"
                 class="lg:border-l-0 lg:[&:nth-child(n+2)]:border-l lg:[&:nth-child(n+2)]:border-l-[#E5E5E5] lg:border-t-0"
                 onmouseover="this.style.background='#F5F5F5'"
                 onmouseout="this.style.background='#fff'">
                <div style="font-family:Georgia,serif; font-weight:700;
                            font-size: 2.5rem; margin-bottom: 1rem;
                            color: #000; line-height: 1;">
                    <?= $num ?>
                </div>
                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.15rem; margin-bottom: .5rem;">
                    <?= e($nom) ?>
                </h3>
                <p style="font-size: .875rem; color: #555; line-height: 1.55;">
                    <?= e($tavsif) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════ TARIFLAR ═══════════════ -->
<section id="tariflar" style="border-bottom: 1px solid #000;">
    <div style="max-width:1200px; margin:0 auto; padding: 5rem 1.25rem;">

        <div style="margin-bottom: 3rem;">
            <span style="font-size:.78rem; font-weight:600;
                         text-transform:uppercase; letter-spacing:.1em; color:#666;">
                Narxlar
            </span>
            <h2 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.75rem, 4vw, 2.5rem);
                       margin-top: .5rem; max-width: 30rem;">
                <?= e(t('tariflar_sarlavha')) ?>
            </h2>
        </div>

        <?php if (empty($tariflar)): ?>
        <div class="b-card" style="padding: 4rem 2rem; text-align:center;">
            <p style="color: #666;"><?= e(t('tariflar_yoq')) ?></p>
        </div>
        <?php else: ?>
        <div style="display:grid; grid-template-columns: 1fr; gap: 0;
                    border: 1px solid #000;"
             class="md:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($tariflar as $i => $tar):
                $is_pro = !empty($tar['mashhur']);
                $border_cls = '';
                if ($i > 0) $border_cls = 'border-top: 1px solid #E5E5E5;';
            ?>
            <div style="padding: 2rem 1.5rem; display:flex; flex-direction:column;
                        <?= $border_cls ?>
                        <?= $is_pro ? 'background: #000; color: #fff;' : 'background: #fff;' ?>"
                 class="md:border-t-0 md:[&:nth-child(n+2)]:border-l md:[&:nth-child(n+2)]:border-l-[#E5E5E5] md:[&:nth-child(3)]:border-t-[#E5E5E5] md:[&:nth-child(4)]:border-t-[#E5E5E5] lg:[&:nth-child(3)]:border-t-0 lg:[&:nth-child(4)]:border-t-0">

                <?php if ($is_pro): ?>
                <div style="display:inline-block; padding:.2rem .6rem;
                            border:1px solid #fff; font-size:.7rem; font-weight:500;
                            margin-bottom: 1rem; align-self:flex-start;
                            text-transform:uppercase; letter-spacing:.08em;">
                    Tavsiya etiladi
                </div>
                <?php endif; ?>

                <h3 style="font-family:Georgia,serif; font-weight:700;
                           font-size: 1.35rem; margin-bottom: .5rem;
                           color: <?= $is_pro ? '#fff' : '#000' ?>;">
                    <?= e($tar['nomi']) ?>
                </h3>

                <?php if ((float)($tar['eski_narx'] ?? 0) > (float)$tar['narx']): ?>
                <div style="font-size:.85rem; text-decoration: line-through;
                            color: <?= $is_pro ? '#999' : '#999' ?>; margin-bottom:.25rem;">
                    <?= e(pul($tar['eski_narx'])) ?>
                </div>
                <?php endif; ?>

                <div style="font-family:Georgia,serif; font-weight:700;
                            font-size: 2.5rem; line-height: 1;
                            margin-bottom: .25rem;
                            color: <?= $is_pro ? '#fff' : '#000' ?>;" class="tabnum">
                    <?= number_format((float)$tar['narx'], 0, '.', ' ') ?>
                </div>
                <div style="font-size:.85rem;
                            color: <?= $is_pro ? '#999' : '#666' ?>;
                            margin-bottom: 1.5rem;">
                    so'm
                </div>

                <p style="font-size:.875rem; line-height: 1.55; flex: 1;
                          margin-bottom: 1.5rem;
                          color: <?= $is_pro ? '#ccc' : '#555' ?>;">
                    <?= e($tar['tavsif']) ?>
                </p>

                <a href="<?= e(SAYT_URL) ?>/register"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          padding: .8rem 1.25rem; font-size:.9rem; font-weight:500;
                          border: 1px solid <?= $is_pro ? '#fff' : '#000' ?>;
                          background: <?= $is_pro ? '#fff' : '#000' ?>;
                          color: <?= $is_pro ? '#000' : '#fff' ?>;
                          text-decoration: none; transition: all .15s;"
                   onmouseover="this.style.background='<?= $is_pro ? '#000' : '#fff' ?>';this.style.color='<?= $is_pro ? '#fff' : '#000' ?>'"
                   onmouseout="this.style.background='<?= $is_pro ? '#fff' : '#000' ?>';this.style.color='<?= $is_pro ? '#000' : '#fff' ?>'">
                    <?= e(t('tarif_olish')) ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════ FIKRLAR ═══════════════ -->
<?php if (!empty($fikrlar)): ?>
<section id="fikrlar" style="border-bottom: 1px solid #000;">
    <div style="max-width:1200px; margin:0 auto; padding: 5rem 1.25rem;">

        <div style="margin-bottom: 3rem;">
            <span style="font-size:.78rem; font-weight:600;
                         text-transform:uppercase; letter-spacing:.1em; color:#666;">
                Fikrlar
            </span>
            <h2 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.75rem, 4vw, 2.5rem);
                       margin-top: .5rem;">
                <?= e(t('fikrlar_sarlavha')) ?>
            </h2>
        </div>

        <div style="display:grid; grid-template-columns:1fr; gap: 1rem;"
             class="md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($fikrlar as $fikr): ?>
            <div class="b-card" style="padding: 1.5rem;">
                <!-- Yulduzlar -->
                <div style="font-size: 1.05rem; letter-spacing: .15em;
                            margin-bottom: .85rem;">
                    <?= str_repeat('★', (int)$fikr['baho']) . str_repeat('☆', 5 - (int)$fikr['baho']) ?>
                </div>

                <p style="font-size: .9rem; line-height: 1.6;
                          color: #000; margin-bottom: 1.25rem;
                          min-height: 4.5rem;" class="line-clamp-4">
                    "<?= e($fikr['matn']) ?>"
                </p>

                <div style="border-top: 1px solid #E5E5E5; padding-top: .85rem;
                            display:flex; align-items:center; gap:.75rem;">
                    <span style="display:flex; align-items:center; justify-content:center;
                                 width:36px; height:36px; border:1px solid #000;
                                 font-family:Georgia,serif; font-weight:700;
                                 font-size:.9rem; flex-shrink:0;">
                        <?= e(mb_strtoupper(mb_substr($fikr['ism'], 0, 1))) ?>
                    </span>
                    <div>
                        <div style="font-weight:600; font-size:.875rem;">
                            <?= e($fikr['ism']) ?>
                        </div>
                        <div style="font-size:.75rem; color:#666;">
                            <?= e(sana($fikr['yaratilgan'], 'd.m.Y')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Fikr qoldirish -->
        <div class="b-card" style="margin-top: 3rem; padding: 2rem; max-width: 36rem;"
             x-data="{ baho: 5, matn: '', loading: false, ok: false }">
            <h3 style="font-family:Georgia,serif; font-weight:700;
                       font-size: 1.25rem; margin-bottom: .35rem;">
                Fikr qoldiring
            </h3>
            <p style="font-size:.85rem; color:#666; margin-bottom: 1.5rem;">
                Tajribangiz boshqalarga yordam beradi.
            </p>

            <div x-show="ok" x-cloak class="b-card"
                 style="padding: 1rem; background: #F5F5F5; margin-bottom: 1rem;
                        font-size: .875rem;">
                Fikringiz uchun rahmat. Tez orada qo'shiladi.
            </div>

            <form x-show="!ok"
                  @submit.prevent="
                    if (!matn.trim() || matn.trim().length < 5) return;
                    loading = true;
                    window.apiPost('<?= e(SAYT_URL) ?>/api/fikr_qoshish.php', { matn, baho })
                      .then(r => { if (r.ok) { ok = true; } else { alert(r.xato || 'Xato'); loading = false; } })
                      .catch(() => loading = false);">

                <div style="margin-bottom: 1rem;">
                    <label class="field-label">Baho</label>
                    <div style="display:flex; gap:.25rem; font-size:1.5rem; line-height:1;">
                        <template x-for="i in [1,2,3,4,5]" :key="i">
                            <button type="button" @click="baho = i"
                                    :style="i <= baho ? 'color:#000;' : 'color:#ccc;'"
                                    style="background:none; border:none; cursor:pointer; padding: 0 .15rem; font-family: inherit;"
                                    onmouseover="this.style.transform='scale(1.15)'"
                                    onmouseout="this.style.transform='scale(1)'">
                                ★
                            </button>
                        </template>
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="field-label">Fikringiz</label>
                    <textarea x-model="matn" rows="3" maxlength="1000" required
                              class="field"
                              placeholder="Platformani sinab ko'rdingiz..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary"
                        :disabled="loading || matn.trim().length < 5">
                    <span x-text="loading ? 'Yuborilmoqda...' : 'Yuborish'">Yuborish</span>
                </button>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════ CTA ═══════════════ -->
<section>
    <div style="max-width:1200px; margin:0 auto; padding: 5rem 1.25rem;">
        <div class="b-card" style="padding: 3rem 2rem; text-align: center;">
            <h2 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.75rem, 4vw, 2.5rem);
                       margin-bottom: 1rem; max-width: 24ch; margin-inline: auto;">
                Bugun boshlang. Imtihonni bir urinishdan toping.
            </h2>
            <p style="font-size: 1rem; color: #555; max-width: 32rem;
                      margin: 0 auto 2rem; line-height: 1.6;">
                Demo testni bepul yeching, platforma qulayligini his qiling.
                Ro'yxatdan o'tish 30 soniya.
            </p>
            <div style="display:flex; flex-wrap:wrap; gap:.75rem; justify-content:center;">
                <a href="<?= e(SAYT_URL) ?>/register" class="btn btn-primary btn-xl">
                    <?= e(t('royxatdan_otish')) ?> →
                </a>
                <a href="<?= e(SAYT_URL) ?>/login" class="btn btn-ghost btn-xl">
                    <?= e(t('kirish')) ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════ ALOQA ═══════════════ -->
<section id="aloqa" style="border-top: 1px solid #000; background: #FAFAFA;">
    <div style="max-width:1200px; margin:0 auto; padding: 4rem 1.25rem;">

        <div style="margin-bottom: 2.5rem;">
            <span style="font-size:.78rem; font-weight:600;
                         text-transform:uppercase; letter-spacing:.1em; color:#666;">
                Yordam
            </span>
            <h2 style="font-family:Georgia,serif; font-weight:700;
                       font-size: clamp(1.5rem, 3.5vw, 2rem); margin-top: .5rem;">
                <?= e(t('aloqa')) ?>
            </h2>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap: 1rem;"
             class="md:grid-cols-3">
            <?php
            $aloqa_items = [
                ['Telefon',       sozlama('aloqa_telefon',''), 'tel:' . sozlama('aloqa_telefon','')],
                ['Email',         sozlama('aloqa_email',''),   'mailto:' . sozlama('aloqa_email','')],
                ['Telegram',      'Kanalga obuna',             sozlama('telegram_kanal','#')],
            ];
            foreach ($aloqa_items as [$nom, $val, $href]):
                if (!$val || $href === 'mailto:' || $href === 'tel:') continue;
            ?>
            <a href="<?= e($href) ?>"
               <?= str_starts_with($href, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
               style="display:block; padding: 1.5rem; border:1px solid #000;
                      background: #fff; text-decoration:none; transition: background-color .15s;"
               onmouseover="this.style.background='#F5F5F5'"
               onmouseout="this.style.background='#fff'">
                <div style="font-size:.78rem; font-weight:600;
                            text-transform:uppercase; letter-spacing:.08em;
                            color:#666; margin-bottom:.5rem;">
                    <?= e($nom) ?>
                </div>
                <div style="font-family:Georgia,serif; font-weight:700;
                            font-size: 1.05rem; color: #000;">
                    <?= e($val) ?> →
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';

if (!joriy_foydalanuvchi()) {
    if (!is_dir(CACHE_PATH)) mkdir(CACHE_PATH, 0755, true);
    @file_put_contents($kesh_fayli, ob_get_contents());
}
header('X-Cache: MISS');
ob_end_flush();
