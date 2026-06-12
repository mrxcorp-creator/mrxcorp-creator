<?php
/**
 * VatanParvar Yaypan — Ma'lumotlar bazasi (PDO)
 * ------------------------------------------------------------
 * Faqat tayyorlangan so'rovlar (Prepared Statements) ishlatiladi.
 */

require_once __DIR__ . '/config.php';

// ----- Ma'lumotlar bazasi parametrlari (Xost-1000) -----
define('DB_HOST', 'localhost');
define('DB_NAME', 'wbefkccz_avtomaktab');
define('DB_USER', 'wbefkccz_avtomaktab');
define('DB_PASS', 'FrHCuXUP6RfY4XnzDGBw');
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO ulanishini bitta marta yaratuvchi singleton.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $variantlar = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $variantlar);
        } catch (PDOException $e) {
            // Xatoni yashirin holda qaydga olamiz
            error_log('DB ulanish xatosi: ' . $e->getMessage());
            http_response_code(500);
            exit('Server vaqtinchalik mavjud emas. Iltimos, keyinroq urinib ko\'ring.');
        }
    }
    return $pdo;
}

/**
 * Bir qatorni qaytaruvchi yordamchi.
 */
function db_qator(string $sql, array $params = []): ?array {
    $st = db()->prepare($sql);
    $st->execute($params);
    $natija = $st->fetch();
    return $natija ?: null;
}

/**
 * Bir nechta qatorni qaytaruvchi yordamchi.
 */
function db_barcha(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * INSERT/UPDATE/DELETE yordamchisi. Yangi ID yoki ta'sirlangan qator sonini qaytaradi.
 */
function db_bajar(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    if (stripos(trim($sql), 'INSERT') === 0) {
        return (int) db()->lastInsertId();
    }
    return $st->rowCount();
}

/**
 * Bitta qiymatni qaytaruvchi yordamchi.
 */
function db_qiymat(string $sql, array $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

/**
 * Sozlamalardan qiymat olish.
 */
function sozlama(string $kalit, $standart = null) {
    static $kesh = null;
    if ($kesh === null) {
        $kesh = [];
        foreach (db_barcha('SELECT kalit, qiymat FROM sozlamalar') as $s) {
            $kesh[$s['kalit']] = $s['qiymat'];
        }
    }
    return $kesh[$kalit] ?? $standart;
}

/**
 * Sozlamani saqlash.
 */
function sozlama_saqla(string $kalit, $qiymat): void {
    db_bajar(
        'INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)',
        [$kalit, (string) $qiymat]
    );
}
