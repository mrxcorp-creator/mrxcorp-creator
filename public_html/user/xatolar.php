<?php
require_once __DIR__ . '/../config/auth.php';
$f = kirgan_bolish_kerak();

$bilet_filt = (int) olish('bilet');

$where_bilet = $bilet_filt ? 'AND n.bilet_id = ' . $bilet_filt : '';
$natijalar = db_barcha(
    "SELECT n.id, n.bilet_id, n.javoblar_json, n.tugagan, b.raqam, b.nomi
     FROM natijalar n
     JOIN biletlar b ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = 'tugagan' AND n.xato_son > 0 $where_bilet
     ORDER BY n.tugagan DESC LIMIT 30",
    [$f['id']]
);

$xatolar_bilet_bo_yicha = [];
$jami_xato = 0;

foreach ($natijalar as $n) {
    $javoblar = json_decode($n['javoblar_json'] ?? '{}', true) ?: [];
    if (!$javoblar) continue;

    $savollar = db_barcha(
        'SELECT * FROM savollar WHERE bilet_id = ? ORDER BY tartib, id',
        [$n['bilet_id']]
    );

    foreach ($savollar as $s) {
        $foydalanuvchi_javobi = $javoblar[$s['id']] ?? null;
        if ($foydalanuvchi_javobi && $foydalanuvchi_javobi !== $s['togri_javob']) {
            $kalit = $n['bilet_id'];
            if (!isset($xatolar_bilet_bo_yicha[$kalit])) {
                $xatolar_bilet_bo_yicha[$kalit] = [
                    'raqam' => $n['raqam'],
                    'nomi'  => $n['nomi'],
                    'savollar' => [],
                ];
            }
            $sav_kalit = $s['id'];
            if (!isset($xatolar_bilet_bo_yicha[$kalit]['savollar'][$sav_kalit])) {
                $xatolar_bilet_bo_yicha[$kalit]['savollar'][$sav_kalit] = [
                    'savol' => $s,
                    'javoblar' => [],
                ];
                $jami_xato++;
            }
            $xatolar_bilet_bo_yicha[$kalit]['savollar'][$sav_kalit]['javoblar'][] = [
                'sana' => $n['tugagan'],
                'javob' => $foydalanuvchi_javobi,
            ];
        }
    }
}

uksort($xatolar_bilet_bo_yicha, function ($a, $b) use ($xatolar_bilet_bo_yicha) {
    return count($xatolar_bilet_bo_yicha[$b]['savollar']) <=> count($xatolar_bilet_bo_yicha[$a]['savollar']);
});

$biletlar = db_barcha(
    "SELECT DISTINCT b.id, b.raqam, b.nomi
     FROM biletlar b
     JOIN natijalar n ON n.bilet_id = b.id
     WHERE n.foydalanuvchi_id = ? AND n.holat = 'tugagan' AND n.xato_son > 0
     ORDER BY b.raqam",
    [$f['id']]
);

$sahifa_sarlavha = 'Xatolarni o\'rganish';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="max-w-5xl mx-auto px-4 py-8">

    <div class="mb-8 fade-up">
        <span class="chip chip-grad mb-3">📚 O'rganish</span>
        <h1 class="text-3xl md:text-4xl mb-2 font-display font-extrabold">
            Xatolarni <span class="grad-text">o'rganish</span>
        </h1>
        <p class="text-muted">Oxirgi 30 ta tugatilgan testlardagi xatolaringiz to'g'ri javoblar va izoh bilan</p>
    </div>

    <?php if (empty($xatolar_bilet_bo_yicha)): ?>
        <div class="ring-grad fade-up">
            <div class="p-12 text-center">
                <div class="w-24 h-24 mx-auto rounded-3xl bg-success/15 text-success flex items-center justify-center text-5xl mb-4">🎉</div>
                <h2 class="text-2xl font-display font-bold mb-2 grad-text">Tabriklayman!</h2>
                <p class="text-muted">Sizda hech qanday xato javob yo'q yoki hali test yechmagansiz.</p>
                <a href="<?= e(SAYT_URL) ?>/test" class="btn btn-primary mt-6 inline-flex">
                    🚗 Testni boshlash
                </a>
            </div>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 fade-up">
            <div class="glass p-4">
                <div class="text-xs text-muted uppercase mb-1">Jami xatolar</div>
                <div class="text-3xl font-display font-extrabold text-danger"><?= $jami_xato ?></div>
            </div>
            <div class="glass p-4">
                <div class="text-xs text-muted uppercase mb-1">Biletlar</div>
                <div class="text-3xl font-display font-extrabold"><?= count($xatolar_bilet_bo_yicha) ?></div>
            </div>
            <div class="glass p-4 col-span-2">
                <div class="text-xs text-muted uppercase mb-1">Eng ko'p xato</div>
                <?php $eng = reset($xatolar_bilet_bo_yicha); ?>
                <div class="text-base font-display font-bold truncate">№<?= (int) $eng['raqam'] ?> — <?= e($eng['nomi']) ?></div>
            </div>
        </div>

        <?php if (count($biletlar) > 1): ?>
            <div class="glass p-3 mb-6 fade-up flex flex-wrap gap-1">
                <a href="?" class="px-3 py-1.5 rounded-lg text-sm <?= !$bilet_filt ? 'grad-bg text-white font-bold' : 'hover:bg-white/5' ?>">Barchasi</a>
                <?php foreach ($biletlar as $b): ?>
                    <a href="?bilet=<?= (int) $b['id'] ?>"
                       class="px-3 py-1.5 rounded-lg text-sm <?= $bilet_filt === (int) $b['id'] ? 'grad-bg text-white font-bold' : 'hover:bg-white/5' ?>">
                        №<?= (int) $b['raqam'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php foreach ($xatolar_bilet_bo_yicha as $bilet_id => $b):
                $savollar_son = count($b['savollar']);
            ?>
                <div class="glass p-5 fade-up" x-data="{open: false}">
                    <div @click="open = !open" class="flex items-center justify-between cursor-pointer">
                        <div>
                            <h2 class="font-display font-bold text-lg">
                                №<?= (int) $b['raqam'] ?> — <?= e($b['nomi']) ?>
                            </h2>
                            <p class="text-sm text-muted">
                                <?= $savollar_son ?> ta xato savol
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="chip text-xs !py-0.5 !px-2 bg-danger/15 text-danger border-danger/30">
                                <?= $savollar_son ?>
                            </span>
                            <svg class="w-5 h-5 text-muted transition-transform" :class="open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <div x-show="open" x-transition class="mt-5 space-y-4" x-cloak>
                        <?php foreach ($b['savollar'] as $sav_data):
                            $s = $sav_data['savol'];
                            $oxirgi = end($sav_data['javoblar']);
                            $necha_marta = count($sav_data['javoblar']);
                        ?>
                            <div class="border border-white/10 rounded-xl p-4 bg-white/3">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="flex-1">
                                        <p class="font-medium leading-relaxed"><?= e($s['matn']) ?></p>
                                        <?php if ($s['rasm'] && is_file(UPLOAD_PATH . '/' . $s['rasm'])): ?>
                                            <img src="<?= e(SAYT_URL) ?>/uploads/<?= e($s['rasm']) ?>"
                                                 loading="lazy"
                                                 class="mt-3 rounded-xl max-w-md w-full">
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($necha_marta > 1): ?>
                                        <span class="chip text-xs !py-0.5 !px-2 bg-amber/15 text-amber border-amber/30 flex-shrink-0">
                                            <?= $necha_marta ?>x xato
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-2">
                                    <?php foreach (['a', 'b', 'c', 'd'] as $v):
                                        $matn = $s['variant_' . $v] ?? null;
                                        if (!$matn) continue;
                                        $bu_togri = $v === $s['togri_javob'];
                                        $bu_xato_javob = $v === $oxirgi['javob'];
                                    ?>
                                        <div class="p-3 rounded-xl text-sm border
                                            <?= $bu_togri ? 'bg-success/15 border-success/40' :
                                               ($bu_xato_javob ? 'bg-danger/10 border-danger/40' : 'bg-white/3 border-white/10') ?>">
                                            <span class="font-bold mr-2 uppercase"><?= $v ?>)</span>
                                            <?= e($matn) ?>
                                            <?php if ($bu_togri): ?>
                                                <span class="float-right text-success font-bold">✓ To'g'ri</span>
                                            <?php elseif ($bu_xato_javob): ?>
                                                <span class="float-right text-danger font-bold">✗ Sizning javobingiz</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (!empty($s['izoh'])): ?>
                                    <div class="mt-3 p-3 rounded-xl bg-violet/10 border border-violet/30 text-sm">
                                        <strong class="text-violet">💡 Izoh:</strong> <?= e($s['izoh']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center pt-3">
                            <a href="<?= e(SAYT_URL) ?>/test?bilet=<?= (int) $bilet_id ?>&qaytadan=1"
                               class="btn btn-primary">
                                🔄 Bu biletni qaytadan ishlash
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
