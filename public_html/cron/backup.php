<?php
/**
 * VatanParvar Yaypan — Tungi DB zahiralash + Telegram orqali yuborish
 *
 * Cron sozlanmasi (har kuni soat 03:00 da):
 *   0 3 * * * /usr/bin/php /home/USER/public_html/cron/backup.php
 *
 * Yoki saytdan: /cron/backup.php?kalit=...  (sozlamalardan o'qiladi)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

// ----- Web orqali kirilgan bo'lsa, kalit talab qilamiz -----
if (php_sapi_name() !== 'cli') {
    $kalit = sozlama('cron_kalit', '');
    $kelgan = $_GET['kalit'] ?? '';
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ----- Backup faylini yaratish -----
$sana = date('Y-m-d_H-i');
$papka = BACKUP_PATH;
if (!is_dir($papka)) mkdir($papka, 0755, true);
$fayl = "$papka/db_$sana.sql";

// mysqldump ishlatish (xost-1000'da bor)
$cmd = sprintf(
    'mysqldump --no-tablespaces -h%s -u%s -p%s %s > %s 2>&1',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($fayl)
);
exec($cmd, $chiqish, $kod);

// Agar mysqldump ishlamasa — PHP orqali zaxiralash
if ($kod !== 0 || !file_exists($fayl) || filesize($fayl) < 100) {
    @unlink($fayl);
    php_orqali_zahirala($fayl);
}

if (!file_exists($fayl) || filesize($fayl) < 100) {
    error_log('Backup yaratib bo\'lmadi: ' . $fayl);
    exit('Backup xato');
}

// ----- Gzip siqish -----
$gz = $fayl . '.gz';
$ko = fopen($fayl, 'rb');
$go = gzopen($gz, 'wb9');
while (!feof($ko)) gzwrite($go, fread($ko, 8192));
fclose($ko);
gzclose($go);
@unlink($fayl);

// ----- Telegramga yuborish -----
$admin_id = sozlama('telegram_admin_id');
if ($admin_id) {
    $hajm = round(filesize($gz) / 1024, 2);
    telegram_fayl_yubor(
        $admin_id,
        $gz,
        "💾 <b>DB Backup</b>\n📅 " . date('d.m.Y H:i') . "\n📦 Hajm: {$hajm} KB"
    );
}

// ----- Eskirgan zaxiralarni o'chirish (7 kundan eski) -----
foreach (glob($papka . '/db_*.gz') as $f) {
    if (filemtime($f) < time() - 60 * 60 * 24 * 7) {
        @unlink($f);
    }
}

echo "Backup tayyor: $gz\n";
exit;

// ============================================================
// PHP orqali zahiralash (mysqldump bo'lmasa)
// ============================================================
function php_orqali_zahirala(string $fayl): void {
    $fp = fopen($fayl, 'w');
    fwrite($fp, "-- VatanParvar Yaypan zaxira\n-- Sana: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($fp, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $jadvallar = db_barcha("SHOW TABLES");
    foreach ($jadvallar as $j) {
        $jadval = reset($j);
        // Tuzilma
        $tuzilma = db()->query("SHOW CREATE TABLE `$jadval`")->fetch();
        fwrite($fp, "DROP TABLE IF EXISTS `$jadval`;\n");
        fwrite($fp, $tuzilma['Create Table'] . ";\n\n");

        // Ma'lumot
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
