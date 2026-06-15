<?php
require_once __DIR__ . '/config.php';

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'wbefkccz_avtomaktab');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'wbefkccz_avtomaktab');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

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
            error_log('DB ulanish xatosi: ' . $e->getMessage());
            http_response_code(500);
            exit('Server vaqtinchalik mavjud emas. Iltimos, keyinroq urinib ko\'ring.');
        }
    }
    return $pdo;
}

function db_qator(string $sql, array $params = []): ?array {
    $st = db()->prepare($sql);
    $st->execute($params);
    $natija = $st->fetch();
    return $natija ?: null;
}

function db_barcha(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_bajar(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    if (stripos(trim($sql), 'INSERT') === 0) {
        return (int) db()->lastInsertId();
    }
    return $st->rowCount();
}

function db_qiymat(string $sql, array $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

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

function sozlama_saqla(string $kalit, $qiymat): void {
    db_bajar(
        'INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)',
        [$kalit, (string) $qiymat]
    );
}
