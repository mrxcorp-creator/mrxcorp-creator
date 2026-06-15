<?php
/**
 * VatanParvar Yaypan — Tungi DB zahiralash + Telegram orqali yuborish
 *
 * CLI yoki veb orqali chaqirish:
 *   CLI: /usr/bin/php /home/USER/public_html/cron/backup.php
 *   Web: https://vatanparvaryaypan.uz/cron/backup.php?kalit=KALIT
 *
 * Cron (03:00 da):
 *   0 3 * * * /usr/bin/php /home/USER/public_html/cron/backup.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

// Web orqali kirishni himoyalash
if (php_sapi_name() !== 'cli') {
    $kalit  = sozlama('cron_kalit', '');
    $kelgan = $_GET['kalit'] ?? '';
    if (!$kalit || !hash_equals($kalit, $kelgan)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function backup_ishga_tushir(): array
{
    $sana  = date('Y-m-d_H-i');
    $papka = BACKUP_PATH;

    if (!is_dir($papka)) {
        mkdir($papka, 0750, true);
    }

    $fayl = "{$papka}/db_{$sana}.sql";

    // mysqldump urinish
    $cmd = sprintf(
        'mysqldump --no-tablespaces --single-transaction -h%s -u%s -p%s %s > %s 2>&1',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($fayl)
    );
    exec($cmd, $out, $kod);

    // mysqldump ishlamasa PHP fallback
    if ($kod !== 0 || !is_file($fayl) || filesize($fayl) < 100) {
        @unlink($fayl);
        _php_backup($fayl);
    }

    if (!is_file($fayl) || filesize($fayl) < 100) {
        return ['ok' => false, 'xato' => 'Backup fayli yaratilmadi'];
    }

    // Gzip siqish
    $gz = $fayl . '.gz';
    $fi = fopen($fayl, 'rb');
    $fo = gzopen($gz, 'wb9');
    while (!feof($fi)) {
        gzwrite($fo, fread($fi, 8192));
    }
    fclose($fi);
    gzclose($fo);
    @unlink($fayl);

    if (!is_file($gz)) {
        return ['ok' => false, 'xato' => 'Gzip siqish muvaffaqiyatsiz'];
    }

    // Telegramga yuborish
    $admin_id = sozlama('telegram_admin_id');
    $yuborildi = false;
    if ($admin_id) {
        $hajm = round(filesize($gz) / 1024, 1);
        $yuborildi = telegram_fayl_yubor(
            (int) $admin_id,
            $gz,
            "💾 <b>VatanParvar Yaypan — DB Backup</b>\n📅 " . date('d.m.Y H:i') . "\n📦 Hajm: {$hajm} KB"
        );
    }

    // 7 kundan eski backup'larni o'chirish
    foreach (glob("{$papka}/db_*.gz") ?: [] as $eski) {
        if (filemtime($eski) < time() - 7 * 86400) {
            @unlink($eski);
        }
    }

    return ['ok' => true, 'fayl' => $gz, 'telegram' => $yuborildi];
}

function _php_backup(string $fayl): void
{
    $fp = fopen($fayl, 'w');
    fwrite($fp, "-- VatanParvar Yaypan DB Backup\n-- Sana: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($fp, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach (db_barcha('SHOW TABLES') as $j) {
        $jadval  = reset($j);
        $tuzilma = db()->query("SHOW CREATE TABLE `{$jadval}`")->fetch();
        fwrite($fp, "DROP TABLE IF EXISTS `{$jadval}`;\n");
        fwrite($fp, $tuzilma['Create Table'] . ";\n\n");

        $qatorlar = db()->query("SELECT * FROM `{$jadval}`");
        foreach ($qatorlar as $q) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), array_values($q));
            fwrite($fp, "INSERT INTO `{$jadval}` VALUES (" . implode(',', $vals) . ");\n");
        }
        fwrite($fp, "\n");
    }

    fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fp);
}

$natija = backup_ishga_tushir();

if ($natija['ok']) {
    echo "✅ Backup tayyor: {$natija['fayl']}\n";
    echo "📱 Telegram: " . ($natija['telegram'] ? 'yuborildi' : 'yuborilmadi') . "\n";
} else {
    echo "❌ Xato: {$natija['xato']}\n";
    error_log('VatanParvar Yaypan backup xato: ' . $natija['xato']);
}
