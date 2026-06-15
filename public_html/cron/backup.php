<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if (php_sapi_name() !== 'cli') {
    $kalit = sozlama('cron_kalit', '');
    $kelgan = $_GET['kalit'] ?? '';
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$sana = date('Y-m-d_H-i');
$papka = BACKUP_PATH;
if (!is_dir($papka)) mkdir($papka, 0755, true);
$fayl = "$papka/db_$sana.sql";

$cnf = $papka . '/.my.cnf';
file_put_contents($cnf, "[client]\nuser=" . DB_USER . "\npassword=\"" . DB_PASS . "\"\nhost=" . DB_HOST . "\n");
chmod($cnf, 0600);

$cmd = sprintf(
    'mysqldump --defaults-extra-file=%s --no-tablespaces %s > %s 2>&1',
    escapeshellarg($cnf),
    escapeshellarg(DB_NAME),
    escapeshellarg($fayl)
);
exec($cmd, $chiqish, $kod);
@unlink($cnf);

if ($kod !== 0 || !file_exists($fayl) || filesize($fayl) < 100) {
    @unlink($fayl);
    php_orqali_zahirala($fayl);
}

if (!file_exists($fayl) || filesize($fayl) < 100) {
    error_log('Backup yaratib bo\'lmadi: ' . $fayl);
    exit('Backup xato');
}

$gz = $fayl . '.gz';
$ko = fopen($fayl, 'rb');
$go = gzopen($gz, 'wb9');
while (!feof($ko)) gzwrite($go, fread($ko, 8192));
fclose($ko);
gzclose($go);
@unlink($fayl);

$admin_id = sozlama('telegram_admin_id');
if ($admin_id) {
    $hajm = round(filesize($gz) / 1024, 2);
    telegram_fayl_yubor(
        $admin_id,
        $gz,
        "💾 <b>DB Backup</b>\n📅 " . date('d.m.Y H:i') . "\n📦 Hajm: {$hajm} KB"
    );
}

foreach (glob($papka . '/db_*.gz') as $eski) {
    if (filemtime($eski) < time() - 60 * 60 * 24 * 7) {
        @unlink($eski);
    }
}

db_bajar('DELETE FROM kirish_urinishlar WHERE yaratilgan < DATE_SUB(NOW(), INTERVAL 1 DAY)');

echo "Backup tayyor: $gz\n";
exit;

function php_orqali_zahirala(string $fayl): void {
    $fp = fopen($fayl, 'w');
    fwrite($fp, "-- VatanParvar Yaypan zaxira\n-- Sana: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($fp, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $jadvallar = db_barcha("SHOW TABLES");
    foreach ($jadvallar as $j) {
        $jadval = reset($j);
        $tuzilma = db()->query("SHOW CREATE TABLE `$jadval`")->fetch();
        fwrite($fp, "DROP TABLE IF EXISTS `$jadval`;\n");
        fwrite($fp, $tuzilma['Create Table'] . ";\n\n");

        $qatorlar = db()->query("SELECT * FROM `$jadval`");
        foreach ($qatorlar as $qator) {
            $qiymatlar = array_map(function ($v) {
                if ($v === null) return 'NULL';
                return db()->quote((string) $v);
            }, array_values($qator));
            fwrite($fp, "INSERT INTO `$jadval` VALUES (" . implode(',', $qiymatlar) . ");\n");
        }
        fwrite($fp, "\n");
    }
    fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fp);
}
