<?php
require_once __DIR__ . '/../config/auth.php';
$f = admin_bolish_kerak();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        flash_qoy('xato', t('csrf_xato'));
        yonaltir(SAYT_URL . '/admin/bloklangan_iplar.php');
    }
    $harakat = post('harakat');

    if ($harakat === 'block') {
        $ip = trim(post('ip'));
        $sabab = trim(post('sabab')) ?: 'Admin tomonidan';
        $vaqt = post('vaqt');
        $tugash = $vaqt === 'doim'
            ? null
            : date('Y-m-d H:i:s', strtotime("+$vaqt"));
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            try {
                db_bajar(
                    'INSERT INTO bloklangan_iplar (ip, sabab, tugash) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE sabab = VALUES(sabab), tugash = VALUES(tugash)',
                    [$ip, $sabab, $tugash]
                );
                audit_yoz('ip_bloklandi', 'ip', null, ['ip' => $ip, 'sabab' => $sabab]);
                flash_qoy('muvaffaqiyat', "IP $ip bloklandi");
            } catch (Throwable $e) {
                flash_qoy('xato', $e->getMessage());
            }
        } else {
            flash_qoy('xato', "IP manzili noto'g'ri");
        }
    }

    if ($harakat === 'ochir') {
        $id = (int) post('id');
        $ip = db_qiymat('SELECT ip FROM bloklangan_iplar WHERE id = ?', [$id]);
        db_bajar('DELETE FROM bloklangan_iplar WHERE id = ?', [$id]);
        audit_yoz('ip_blok_olindi', 'ip', $id, ['ip' => $ip]);
        flash_qoy('muvaffaqiyat', "IP $ip blokirovkadan chiqarildi");
    }

    if ($harakat === 'tozala_eski') {
        $tozalandi = db_bajar('DELETE FROM bloklangan_iplar WHERE tugash IS NOT NULL AND tugash < NOW()');
        audit_yoz('eski_iplar_tozalandi', 'ip', null, ['son' => $tozalandi]);
        flash_qoy('muvaffaqiyat', "$tozalandi ta eski blok tozalandi");
    }

    yonaltir(SAYT_URL . '/admin/bloklangan_iplar.php');
}

try {
    $bloklangan = db_barcha(
        'SELECT * FROM bloklangan_iplar
         WHERE tugash IS NULL OR tugash > NOW()
         ORDER BY id DESC LIMIT 200'
    );
} catch (Throwable $e) {
    $bloklangan = [];
}

try {
    $oxirgi_qaydlar = db_barcha(
        'SELECT ip, COUNT(*) AS son, MAX(yaratilgan) AS oxirgi
         FROM xavfsizlik_qaydlar
         WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY ip ORDER BY son DESC LIMIT 20'
    );
} catch (Throwable $e) {
    $oxirgi_qaydlar = [];
}

$admin_sahifa = 'bloklangan_iplar';
$sahifa_sarlavha = 'Bloklangan IP\'lar';
require_once __DIR__ . '/_layout.php';
?>

<form method="POST" class="glass p-5 mb-6 fade-up">
    <?= csrf_input() ?>
    <input type="hidden" name="harakat" value="block">

    <h2 class="font-display font-bold text-lg mb-4">🚫 Yangi IP'ni bloklash</h2>

    <div class="grid sm:grid-cols-4 gap-3">
        <div>
            <label class="field-label">IP manzil *</label>
            <input name="ip" required class="field font-mono" placeholder="192.168.1.1">
        </div>
        <div>
            <label class="field-label">Sabab</label>
            <input name="sabab" class="field" placeholder="Spam, hujum, ...">
        </div>
        <div>
            <label class="field-label">Muddat</label>
            <select name="vaqt" class="field">
                <option value="1 hour">1 soat</option>
                <option value="6 hours">6 soat</option>
                <option value="1 day" selected>1 kun</option>
                <option value="7 days">7 kun</option>
                <option value="30 days">30 kun</option>
                <option value="doim">Doimiy</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn btn-danger w-full">🚫 Bloklash</button>
        </div>
    </div>
</form>

<div class="glass p-5 mb-6 fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-bold text-lg">🛑 Bloklangan IP'lar (<?= count($bloklangan) ?>)</h2>
        <form method="POST" class="inline" onsubmit="return confirm('Tugagan bloklarni tozalashtirilsinmi?')">
            <?= csrf_input() ?>
            <input type="hidden" name="harakat" value="tozala_eski">
            <button type="submit" class="btn btn-ghost text-xs py-2 px-3">🧹 Eskilarini tozalash</button>
        </form>
    </div>

    <?php if (empty($bloklangan)): ?>
        <div class="py-12 text-center">
            <div class="text-5xl mb-3 opacity-50">✓</div>
            <p class="text-muted text-sm">Bloklangan IP yo'q.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs uppercase">
                        <th class="py-2 pr-3">IP</th>
                        <th class="py-2 pr-3">Sabab</th>
                        <th class="py-2 pr-3">Bloklangan</th>
                        <th class="py-2 pr-3">Tugash</th>
                        <th class="py-2 text-right">Amal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($bloklangan as $b): ?>
                        <tr class="hover:bg-white/3">
                            <td class="py-2.5 pr-3 font-mono"><?= e($b['ip']) ?></td>
                            <td class="py-2.5 pr-3 text-xs"><?= e($b['sabab'] ?? '—') ?></td>
                            <td class="py-2.5 pr-3 text-xs text-muted"><?= e(sana($b['bloklangan_paytda'])) ?></td>
                            <td class="py-2.5 pr-3 text-xs">
                                <?php if ($b['tugash']): ?>
                                    <?php $qoldi = strtotime($b['tugash']) - time(); ?>
                                    <?php if ($qoldi > 86400): ?>
                                        <?= round($qoldi / 86400) ?> kun
                                    <?php elseif ($qoldi > 3600): ?>
                                        <?= round($qoldi / 3600) ?> soat
                                    <?php else: ?>
                                        <?= round($qoldi / 60) ?> daqiqa
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-danger">Doimiy</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 text-right">
                                <form method="POST" class="inline" onsubmit="return confirm('Blokdan chiqarilsinmi?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="harakat" value="ochir">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="text-success hover:underline text-xs">✓ Blokdan chiqar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($oxirgi_qaydlar)): ?>
    <div class="glass p-5 fade-up">
        <h2 class="font-display font-bold text-lg mb-4">⚠️ Shubhali IP'lar (24 soat)</h2>
        <p class="text-sm text-muted mb-4">Anti-copy / DevTools urinishlari ko'p bo'lgan IP'lar</p>

        <div class="space-y-2">
            <?php foreach ($oxirgi_qaydlar as $q): ?>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/3">
                    <div>
                        <code class="font-mono"><?= e($q['ip']) ?></code>
                        <div class="text-xs text-muted">Oxirgi: <?= e(vaqt_oldin($q['oxirgi'])) ?></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="chip <?= $q['son'] > 20 ? 'bg-danger/15 text-danger border-danger/30' : 'bg-amber/15 text-amber border-amber/30' ?>">
                            <?= (int)$q['son'] ?> urinish
                        </span>
                        <form method="POST" class="inline">
                            <?= csrf_input() ?>
                            <input type="hidden" name="harakat" value="block">
                            <input type="hidden" name="ip" value="<?= e($q['ip']) ?>">
                            <input type="hidden" name="vaqt" value="1 day">
                            <input type="hidden" name="sabab" value="Anti-copy 24h">
                            <button class="btn btn-danger text-xs py-1 px-2">🚫 Bloklash</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_layout_end.php'; ?>
