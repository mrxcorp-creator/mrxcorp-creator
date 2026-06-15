<?php
require_once __DIR__ . '/config/auth.php';
$f = developer_bolish_kerak();

$ROOT = __DIR__;
$migrations_dir = $ROOT . '/../database/migrations';
if (!is_dir($migrations_dir)) {
    $migrations_dir = $ROOT . '/database/migrations';
}

$xato = '';
$natija = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_tekshir(post('csrf_token'))) {
        $xato = t('csrf_xato');
    } else {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS migratsiyalar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(255) UNIQUE NOT NULL,
                bajarilgan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $bajarilgan = db()->query('SELECT nom FROM migratsiyalar')
                              ->fetchAll(PDO::FETCH_COLUMN);

            $fayllar = glob($migrations_dir . '/*.sql') ?: [];
            sort($fayllar);

            foreach ($fayllar as $fayl) {
                $nom = basename($fayl);
                if (in_array($nom, $bajarilgan, true)) {
                    $natija[] = ['nom' => $nom, 'holat' => 'oldin', 'xato' => null];
                    continue;
                }
                try {
                    $sql = file_get_contents($fayl);
                    db()->exec($sql);
                    $st = db()->prepare('INSERT INTO migratsiyalar (nom) VALUES (?)');
                    $st->execute([$nom]);
                    $natija[] = ['nom' => $nom, 'holat' => 'tugadi', 'xato' => null];
                } catch (PDOException $e) {
                    $natija[] = ['nom' => $nom, 'holat' => 'xato', 'xato' => $e->getMessage()];
                    break;
                }
            }
            audit_yoz('migrations_ishga_tushirildi', 'tizim', null, ['natija' => $natija]);
        } catch (Throwable $e) {
            $xato = $e->getMessage();
        }
    }
}

try {
    db()->exec("CREATE TABLE IF NOT EXISTS migratsiyalar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) UNIQUE NOT NULL,
        bajarilgan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $bajarilgan = db()->query('SELECT nom FROM migratsiyalar')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $bajarilgan = [];
}

$fayllar = glob($migrations_dir . '/*.sql') ?: [];
sort($fayllar);
$kutilayotgan = [];
foreach ($fayllar as $fayl) {
    $nom = basename($fayl);
    if (!in_array($nom, $bajarilgan, true)) {
        $kutilayotgan[] = $nom;
    }
}

$sahifa_sarlavha = 'Migrations';
require_once __DIR__ . '/admin/_layout.php';
?>

<div class="ring-grad mb-6">
    <div class="p-6">
        <h2 class="text-xl font-display font-bold mb-2">🗄️ DB Migratsiyalar</h2>
        <p class="text-sm text-muted">Yangi <code>database/migrations/*.sql</code> fayllarini bazaga qo'llaydi. Faqat bir marta bajariladi.</p>
    </div>
</div>

<?php if ($xato): ?>
    <div class="mb-5 p-4 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm">
        <strong>Xato:</strong> <?= e($xato) ?>
    </div>
<?php endif; ?>

<?php if ($natija): ?>
    <div class="glass p-5 mb-6 fade-up">
        <h3 class="font-display font-bold mb-4">📋 Bajarilgan natijalar</h3>
        <div class="space-y-2">
            <?php foreach ($natija as $r): ?>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                    <code class="text-sm"><?= e($r['nom']) ?></code>
                    <?php if ($r['holat'] === 'tugadi'): ?>
                        <span class="text-success font-bold text-sm">✓ Bajarildi</span>
                    <?php elseif ($r['holat'] === 'oldin'): ?>
                        <span class="text-muted text-sm">⊝ Avval bajarilgan</span>
                    <?php else: ?>
                        <span class="text-danger text-sm">✗ Xato</span>
                    <?php endif; ?>
                </div>
                <?php if ($r['xato']): ?>
                    <div class="p-3 rounded-xl bg-danger/5 border border-danger/20 text-xs text-danger ml-4">
                        <?= e($r['xato']) ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="glass p-5">
        <h3 class="font-display font-bold mb-3">✅ Bajarilgan (<?= count($bajarilgan) ?>)</h3>
        <?php if (empty($bajarilgan)): ?>
            <p class="text-muted text-sm">Hali bajarilgan migration yo'q.</p>
        <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($bajarilgan as $nom): ?>
                    <div class="text-xs font-mono text-success flex items-center gap-2">
                        <span>✓</span> <?= e($nom) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass p-5 <?= $kutilayotgan ? '!border-amber/40' : '' ?>">
        <h3 class="font-display font-bold mb-3">⏳ Kutayotgan (<?= count($kutilayotgan) ?>)</h3>
        <?php if (empty($kutilayotgan)): ?>
            <p class="text-success text-sm">✓ Hammasi bajarilgan!</p>
        <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($kutilayotgan as $nom): ?>
                    <div class="text-xs font-mono text-amber flex items-center gap-2">
                        <span>⏳</span> <?= e($nom) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($kutilayotgan): ?>
    <form method="POST">
        <?= csrf_input() ?>
        <button type="submit" class="btn btn-primary">
            🚀 Kutayotgan <?= count($kutilayotgan) ?> ta migrationni bajarish
        </button>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/admin/_layout_end.php'; ?>
