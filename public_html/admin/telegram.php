<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/telegram.php');
    }
    $harakat = post('harakat');

    if ($harakat === 'broadcast') {
        $matn = trim(post('matn'));
        $maqsad = post('maqsad');
        if (mb_strlen($matn) < 3) {
            flash_qoy('xato', 'Matn juda qisqa');
        } else {
            $shart = match ($maqsad) {
                'obuna_faol'   => 'AND EXISTS (SELECT 1 FROM obunalar o WHERE o.foydalanuvchi_id = fo.id AND o.holat = "faol" AND o.tugash > NOW())',
                'obuna_yoq'    => 'AND NOT EXISTS (SELECT 1 FROM obunalar o WHERE o.foydalanuvchi_id = fo.id AND o.holat = "faol" AND o.tugash > NOW())',
                'admin_dev'    => 'AND fo.rol IN ("admin", "developer")',
                default        => '',
            };

            $foyd = db_barcha(
                "SELECT fo.id, fo.telegram_id, fo.ism FROM foydalanuvchilar fo
                 WHERE fo.telegram_id IS NOT NULL AND fo.holat = 'faol' $shart"
            );

            $jami = 0;
            foreach ($foyd as $u) {
                try {
                    $shaxsiy = str_replace('{ism}', $u['ism'], $matn);
                    telegram_navbatga($u['telegram_id'], $shaxsiy);
                    $jami++;
                } catch (Throwable $e) {
                }
            }

            audit_yoz('telegram_broadcast', null, null, [
                'maqsad' => $maqsad,
                'jami'   => $jami,
                'matn_uzunligi' => mb_strlen($matn),
            ]);
            flash_qoy('muvaffaqiyat', "$jami ta xabar navbatga qo'yildi. Cron yuboradi (har minut).");
        }
    }

    if ($harakat === 'jonat_hozir') {
        $natija = telegram_navbatni_jonat(50);
        flash_qoy('muvaffaqiyat', "Jonatildi: {$natija['jonatildi']}, xato: {$natija['xato']}");
    }

    if ($harakat === 'ochir_xato') {
        $tozalandi = db_bajar('DELETE FROM telegram_navbat WHERE holat = "xato"');
        flash_qoy('muvaffaqiyat', "$tozalandi ta xato xabarlar o'chirildi");
    }

    yonaltir(SAYT_URL . '/admin/telegram.php');
}

try {
    $stat = [
        'kutilmoqda' => (int) db_qiymat('SELECT COUNT(*) FROM telegram_navbat WHERE holat = "kutilmoqda"'),
        'jonatildi'  => (int) db_qiymat('SELECT COUNT(*) FROM telegram_navbat WHERE holat = "jonatildi"'),
        'xato'       => (int) db_qiymat('SELECT COUNT(*) FROM telegram_navbat WHERE holat = "xato"'),
    ];
} catch (Throwable $e) {
    $stat = ['kutilmoqda' => 0, 'jonatildi' => 0, 'xato' => 0];
}

$jami_obunachilar = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE telegram_id IS NOT NULL AND holat = "faol"');
$obuna_faol_son = (int) db_qiymat(
    'SELECT COUNT(*) FROM foydalanuvchilar fo
     WHERE fo.telegram_id IS NOT NULL AND fo.holat = "faol"
       AND EXISTS (SELECT 1 FROM obunalar o WHERE o.foydalanuvchi_id = fo.id AND o.holat = "faol" AND o.tugash > NOW())'
);

try {
    $oxirgi_xabarlar = db_barcha(
        'SELECT * FROM telegram_navbat
         ORDER BY id DESC LIMIT 20'
    );
} catch (Throwable $e) {
    $oxirgi_xabarlar = [];
}

$admin_sahifa = 'telegram';
$sahifa_sarlavha = 'Telegram xabarlar';
require_once __DIR__ . '/_layout.php';
?>

<div class="grid grid-cols-3 gap-3 mb-6">
    <div class="glass p-4 fade-up">
        <div class="text-xs text-muted uppercase mb-1">Kutilmoqda</div>
        <div class="text-2xl font-display font-extrabold text-amber"><?= $stat['kutilmoqda'] ?></div>
    </div>
    <div class="glass p-4 fade-up">
        <div class="text-xs text-muted uppercase mb-1">Jonatildi</div>
        <div class="text-2xl font-display font-extrabold text-success"><?= $stat['jonatildi'] ?></div>
    </div>
    <div class="glass p-4 fade-up">
        <div class="text-xs text-muted uppercase mb-1">Xato</div>
        <div class="text-2xl font-display font-extrabold text-danger"><?= $stat['xato'] ?></div>
    </div>
</div>

<form method="POST" class="ring-grad mb-6 fade-up">
    <div class="p-6">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="broadcast">

        <h2 class="font-display font-bold text-xl mb-4">📢 Ommaviy xabar yuborish</h2>

        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="field-label">Maqsadli auditoriya</label>
                <select name="maqsad" class="field" x-data x-on:change="$el.parentElement.parentElement.querySelector('.son-info').textContent = {
                    'barcha': '<?= $jami_obunachilar ?>',
                    'obuna_faol': '<?= $obuna_faol_son ?>',
                    'obuna_yoq': '<?= $jami_obunachilar - $obuna_faol_son ?>',
                    'admin_dev': '?'
                }[$event.target.value] + ' ta'">
                    <option value="barcha"><?= $jami_obunachilar ?> ta — Telegram bog'langanlar</option>
                    <option value="obuna_faol"><?= $obuna_faol_son ?> ta — Faol obunalilar</option>
                    <option value="obuna_yoq"><?= $jami_obunachilar - $obuna_faol_son ?> ta — Obunasizlar</option>
                    <option value="admin_dev">Faqat admin/developer</option>
                </select>
                <p class="text-xs text-muted mt-1"><span class="son-info"><?= $jami_obunachilar ?> ta</span> qabul qiladi</p>
            </div>
        </div>

        <div>
            <label class="field-label">Xabar matni (HTML qo'llab-quvvatlanadi)</label>
            <textarea name="matn" required rows="6" class="field font-mono text-sm"
                      placeholder="🎉 Salom {ism}!&#10;&#10;Yangi tarif e'lon qildik..."></textarea>
            <p class="text-xs text-muted mt-2">
                💡 Maslahat: <code>{ism}</code> avtomatik foydalanuvchi ismi bilan almashtiriladi.<br>
                📎 HTML teglar: <code>&lt;b&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;a href=""&gt;</code>, <code>&lt;code&gt;</code>
            </p>
        </div>

        <button type="submit" class="btn btn-primary mt-5"
                onclick="return confirm('Rostdan ham yuborilsinmi?')">
            📤 Yuborish (navbatga qo'yiladi)
        </button>
    </div>
</form>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="jonat_hozir">

        <h3 class="font-display font-bold mb-2">⚡ Hozir jonatish</h3>
        <p class="text-sm text-muted mb-3">Cron'ni kutmasdan, navbatda 50 ta xabarni darhol yuborish</p>
        <button type="submit" class="btn btn-primary text-sm">⚡ 50 ta xabarni jonat</button>
    </form>

    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="ochir_xato">

        <h3 class="font-display font-bold mb-2">🧹 Xato xabarlarni o'chirish</h3>
        <p class="text-sm text-muted mb-3">3 marta urinishdan keyin yubora olmagan xabarlar (<?= $stat['xato'] ?> ta)</p>
        <button type="submit" class="btn btn-ghost text-sm" <?= $stat['xato'] === 0 ? 'disabled' : '' ?>>
            🧹 O'chirish
        </button>
    </form>
</div>

<?php if (!empty($oxirgi_xabarlar)): ?>
    <div class="glass p-5 fade-up">
        <h3 class="font-display font-bold mb-4">📋 Oxirgi xabarlar</h3>
        <div class="space-y-2">
            <?php foreach ($oxirgi_xabarlar as $x): ?>
                <div class="flex items-start gap-3 p-3 rounded-xl bg-white/3">
                    <span class="chip text-xs !py-0.5 !px-2 flex-shrink-0
                        <?= $x['holat'] === 'jonatildi' ? 'bg-success/15 text-success border-success/30' :
                           ($x['holat'] === 'kutilmoqda' ? 'bg-amber/15 text-amber border-amber/30' : 'bg-danger/15 text-danger border-danger/30') ?>">
                        <?= e($x['holat']) ?>
                    </span>
                    <div class="flex-1 min-w-0">
                        <code class="text-xs text-muted"><?= e($x['chat_id']) ?></code>
                        <div class="text-sm truncate"><?= e(mb_substr(strip_tags($x['matn']), 0, 100)) ?></div>
                    </div>
                    <span class="text-xs text-muted flex-shrink-0"><?= e(vaqt_oldin($x['yaratilgan'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
