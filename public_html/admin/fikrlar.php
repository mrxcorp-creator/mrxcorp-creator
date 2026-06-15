<?php
/**
 * VatanParvar Yaypan — Foydalanuvchi fikrlarini boshqarish
 * BUG FIX: kesh_tozala() bilan barcha til keshlari tozalanadi
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/fikrlar.php');
    }
    $harakat = post('harakat');
    $id      = (int) post('id');

    if ($harakat === 'tasdiq') {
        db_bajar('UPDATE fikrlar SET tasdiq = 1 WHERE id = ?', [$id]);
        kesh_tozala(); // BUG FIX: barcha til keshlari tozalanadi
        flash_qoy('muvaffaqiyat', 'Fikr tasdiqlandi');
    }

    if ($harakat === 'bekor') {
        db_bajar('UPDATE fikrlar SET tasdiq = 0 WHERE id = ?', [$id]);
        kesh_tozala();
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    if ($harakat === 'ochirish') {
        db_bajar('DELETE FROM fikrlar WHERE id = ?', [$id]);
        kesh_tozala();
        flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
    }

    if ($harakat === 'qoshish') {
        $ism  = trim(post('ism'));
        $matn = trim(post('matn'));
        $baho = max(1, min(5, (int) post('baho') ?: 5));
        if ($ism && mb_strlen($matn) >= 5) {
            db_bajar(
                'INSERT INTO fikrlar (ism, matn, baho, tasdiq) VALUES (?, ?, ?, 1)',
                [$ism, $matn, $baho]
            );
            kesh_tozala();
            flash_qoy('muvaffaqiyat', t('malumot_saqlandi'));
        } else {
            flash_qoy('xato', t('kerakli_maydon'));
        }
    }

    yonaltir(SAYT_URL . '/admin/fikrlar.php?holat=' . urlencode(olish('holat')));
}

$holat  = olish('holat');
$shart  = match($holat) {
    'kutilmoqda'  => ' WHERE tasdiq = 0',
    'tasdiqlangan'=> ' WHERE tasdiq = 1',
    default       => ''
};

$jami   = (int) db_qiymat("SELECT COUNT(*) FROM fikrlar {$shart}");
$royxat = db_barcha("SELECT * FROM fikrlar {$shart} ORDER BY yaratilgan DESC LIMIT 200");

$admin_sahifa    = 'fikrlar';
$sahifa_sarlavha = t('fikrlar');
require_once __DIR__ . '/_layout.php';
?>

<!-- Qo'lda fikr qo'shish -->
<form method="POST" class="glass-card p-5 mb-6 fade-up" x-data="{ open: false }">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="qoshish">
    <div class="flex items-center justify-between cursor-pointer select-none" @click="open = !open">
        <h2 class="font-display text-base">➕ Qo'lda fikr qo'shish</h2>
        <svg class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div x-show="open" x-transition class="mt-4 grid sm:grid-cols-3 gap-3">
        <input name="ism"  required placeholder="Ism" class="field">
        <select name="baho" class="field">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= str_repeat('★', $i) ?><?= str_repeat('☆', 5 - $i) ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn-primary"><?= e(t('qoshish')) ?></button>
        <textarea name="matn" required placeholder="Fikr matni (kamida 5 belgi)..." rows="3"
                  class="field sm:col-span-3"></textarea>
    </div>
</form>

<!-- Filtr -->
<div class="flex gap-2 mb-5 flex-wrap">
    <?php
    $filtlar = ['' => 'Barcha', 'kutilmoqda' => 'Kutilmoqda', 'tasdiqlangan' => 'Tasdiqlangan'];
    foreach ($filtlar as $f_kod => $f_nom):
        $faol = $holat === $f_kod;
    ?>
        <a href="?holat=<?= $f_kod ?>"
           class="px-3.5 py-1.5 rounded-xl text-sm font-medium transition <?= $faol ? 'bg-blue-500/20 text-blue-400' : 'bg-white/[0.05] text-brand-muted hover:text-white' ?>">
            <?= e($f_nom) ?>
            <?php if ($f_kod === 'kutilmoqda' && $kutmoqda_fikr > 0): ?>
                <span class="badge badge-yellow ml-1"><?= $kutmoqda_fikr ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
    <span class="ml-auto text-xs text-brand-muted self-center">Jami: <?= $jami ?></span>
</div>

<!-- Fikrlar grid -->
<?php if (empty($royxat)): ?>
    <div class="glass-card p-12 text-center text-brand-muted">
        <div class="text-4xl mb-3">💬</div>
        <p><?= e(t('malumot_yoq')) ?></p>
    </div>
<?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php foreach ($royxat as $r): ?>
            <div class="glass-card p-5 fade-up <?= !$r['tasdiq'] ? 'border-yellow-500/25' : '' ?>">
                <div class="flex items-start justify-between mb-3 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white text-sm flex-shrink-0">
                            <?= e(mb_strtoupper(mb_substr($r['ism'], 0, 1))) ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm truncate"><?= e($r['ism']) ?></p>
                            <div class="text-yellow-400 text-xs"><?= str_repeat('★', (int)$r['baho']) ?><?= str_repeat('☆', 5 - (int)$r['baho']) ?></div>
                        </div>
                    </div>
                    <span class="badge <?= $r['tasdiq'] ? 'badge-green' : 'badge-yellow' ?> text-xs flex-shrink-0">
                        <?= $r['tasdiq'] ? 'Tasdiqlangan' : 'Kutilmoqda' ?>
                    </span>
                </div>

                <p class="text-sm text-brand-muted leading-relaxed line-clamp-4 mb-4"><?= e($r['matn']) ?></p>

                <div class="flex items-center justify-between pt-3 border-t border-white/[0.07]">
                    <span class="text-xs text-brand-muted"><?= e(vaqt_oldin($r['yaratilgan'])) ?></span>
                    <div class="flex gap-2">
                        <?php if (!$r['tasdiq']): ?>
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="tasdiq">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn-success text-xs py-1.5 px-3">✓ Tasdiq</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="harakat" value="bekor">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn-ghost text-xs py-1.5 px-3">Bekor</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" class="inline" onsubmit="return confirm('O\'chirilsinmi?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="ochirish">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn-danger text-xs py-1.5 px-3"><?= e(t('ochirish')) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
