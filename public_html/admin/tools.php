<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/tools.php');
    }
    $harakat = post('harakat');

    if ($harakat === 'kesh_tozala') {
        $tozalandi = 0;
        foreach (glob(CACHE_PATH . '/*.html') ?: [] as $fayl) {
            if (@unlink($fayl)) $tozalandi++;
        }
        audit_yoz('kesh_tozalandi', null, null, ['son' => $tozalandi]);
        flash_qoy('muvaffaqiyat', "$tozalandi ta kesh fayli o'chirildi");
    }

    if ($harakat === 'kirish_urinishlar_tozala') {
        $tozalandi = db_bajar('DELETE FROM kirish_urinishlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        flash_qoy('muvaffaqiyat', "$tozalandi ta yozuv tozalandi");
    }

    if ($harakat === 'auditlar_tozala') {
        $tozalandi = db_bajar('DELETE FROM auditlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        flash_qoy('muvaffaqiyat', "$tozalandi ta audit yozuvi tozalandi");
    }

    if ($harakat === 'tugagan_obunalar') {
        $tugatildi = db_bajar('UPDATE obunalar SET holat = "tugagan" WHERE holat = "faol" AND tugash <= NOW()');
        flash_qoy('muvaffaqiyat', "$tugatildi ta obuna tugatilgan deb belgilandi");
    }

    if ($harakat === 'session_yangi') {
        $eski = $_SESSION;
        session_regenerate_id(true);
        $_SESSION = $eski;
        flash_qoy('muvaffaqiyat', 'Sessiya identifikatori yangilandi');
    }

    yonaltir(SAYT_URL . '/admin/tools.php');
}

$kesh_son = count(glob(CACHE_PATH . '/*.html') ?: []);
$kesh_hajm = 0;
foreach (glob(CACHE_PATH . '/*.html') ?: [] as $fayl) {
    $kesh_hajm += filesize($fayl);
}

$kirish_urinishlar_son = (int) db_qiymat('SELECT COUNT(*) FROM kirish_urinishlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
try {
    $auditlar_eski = (int) db_qiymat('SELECT COUNT(*) FROM auditlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 30 DAY)');
} catch (Throwable $e) {
    $auditlar_eski = 0;
}
$tugashi_kerak_obunalar = (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash <= NOW()');

$db_hajm = (float) db_qiymat(
    "SELECT SUM(data_length + index_length) / 1024 / 1024
     FROM information_schema.tables WHERE table_schema = DATABASE()"
);

$jadvallar = db_barcha(
    "SELECT table_name AS nom, table_rows AS qatorlar,
            ROUND((data_length + index_length) / 1024, 2) AS hajm_kb
     FROM information_schema.tables
     WHERE table_schema = DATABASE()
     ORDER BY (data_length + index_length) DESC"
);

$admin_sahifa = 'tools';
$sahifa_sarlavha = 'Tizim asboblari';
require_once __DIR__ . '/_layout.php';
?>

<div class="grid md:grid-cols-2 gap-4 mb-6">

    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="kesh_tozala">
        <h3 class="font-display font-bold mb-2 flex items-center gap-2">
            <span class="text-2xl">🧹</span> Bosh sahifa keshi
        </h3>
        <p class="text-sm text-muted mb-3">
            <strong><?= $kesh_son ?></strong> ta kesh fayli, <strong><?= round($kesh_hajm / 1024, 1) ?></strong> KB
        </p>
        <button type="submit" class="btn btn-ghost text-sm" <?= $kesh_son === 0 ? 'disabled' : '' ?>>
            🗑️ Tozalash
        </button>
    </form>

    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="kirish_urinishlar_tozala">
        <h3 class="font-display font-bold mb-2 flex items-center gap-2">
            <span class="text-2xl">🔐</span> Eski kirish urinishlari
        </h3>
        <p class="text-sm text-muted mb-3">
            1 soatdan eski: <strong><?= $kirish_urinishlar_son ?></strong> ta
        </p>
        <button type="submit" class="btn btn-ghost text-sm" <?= $kirish_urinishlar_son === 0 ? 'disabled' : '' ?>>
            🗑️ Tozalash
        </button>
    </form>

    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="auditlar_tozala">
        <h3 class="font-display font-bold mb-2 flex items-center gap-2">
            <span class="text-2xl">📜</span> Eski audit yozuvlar
        </h3>
        <p class="text-sm text-muted mb-3">
            30 kundan eski: <strong><?= $auditlar_eski ?></strong> ta
        </p>
        <button type="submit" class="btn btn-ghost text-sm" <?= $auditlar_eski === 0 ? 'disabled' : '' ?>>
            🗑️ Tozalash
        </button>
    </form>

    <form method="POST" class="glass p-5">
        <?= csrf_input() ?>
        <input type="hidden" name="harakat" value="tugagan_obunalar">
        <h3 class="font-display font-bold mb-2 flex items-center gap-2">
            <span class="text-2xl">⏰</span> Vaqti tugagan obunalar
        </h3>
        <p class="text-sm text-muted mb-3">
            Hali "faol" status: <strong><?= $tugashi_kerak_obunalar ?></strong> ta
        </p>
        <button type="submit" class="btn btn-ghost text-sm" <?= $tugashi_kerak_obunalar === 0 ? 'disabled' : '' ?>>
            🔄 Belgilash
        </button>
    </form>

    <?php if ($f['rol'] === 'developer'): ?>
        <form method="POST" class="glass p-5 !border-amber/30">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="session_yangi">
            <h3 class="font-display font-bold mb-2 flex items-center gap-2">
                <span class="text-2xl">🔑</span> Sessiya yangilash
            </h3>
            <p class="text-sm text-muted mb-3">
                Joriy session ID'ni yangilash (xavfsizlik uchun)
            </p>
            <button type="submit" class="btn btn-ghost text-sm">🔄 Yangilash</button>
        </form>
    <?php endif; ?>
</div>

<div class="ring-grad mb-6">
    <div class="p-5">
        <h3 class="font-display font-bold mb-3 flex items-center gap-2">
            <span class="text-xl">💾</span> Ma'lumotlar bazasi
        </h3>
        <div class="grid sm:grid-cols-3 gap-3 text-sm">
            <div class="p-3 rounded-xl bg-white/5">
                <div class="text-xs text-muted">Umumiy hajm</div>
                <div class="text-lg font-display font-bold grad-text"><?= number_format($db_hajm, 2) ?> MB</div>
            </div>
            <div class="p-3 rounded-xl bg-white/5">
                <div class="text-xs text-muted">Jami jadvallar</div>
                <div class="text-lg font-display font-bold"><?= count($jadvallar) ?></div>
            </div>
            <div class="p-3 rounded-xl bg-white/5">
                <div class="text-xs text-muted">MySQL versiya</div>
                <div class="text-lg font-display font-bold"><?= e(db()->query('SELECT VERSION()')->fetchColumn()) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="glass p-5 fade-up">
    <h3 class="font-display font-bold mb-4">📊 Jadval o'lchamlari</h3>

    <div class="overflow-x-auto -mx-5 px-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted text-xs uppercase">
                    <th class="py-2 pr-3">Jadval</th>
                    <th class="py-2 pr-3 text-right">Qatorlar</th>
                    <th class="py-2 text-right">Hajm</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($jadvallar as $j): ?>
                    <tr class="hover:bg-white/3">
                        <td class="py-2 pr-3 font-mono text-xs"><?= e($j['nom']) ?></td>
                        <td class="py-2 pr-3 text-right"><?= number_format((int)$j['qatorlar']) ?></td>
                        <td class="py-2 text-right">
                            <?= $j['hajm_kb'] > 1024
                                ? number_format($j['hajm_kb'] / 1024, 2) . ' MB'
                                : number_format($j['hajm_kb'], 1) . ' KB' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
