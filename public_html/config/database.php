<?php
/**
 * VatanParvar Yaypan — Ma'lumotlar bazasi (PDO singleton)
 * Faqat Prepared Statements ishlatiladi — SQL injection yo'q.
 */

require_once __DIR__ . '/config.php';

// DB parametrlari (.env dan yoki to'g'ridan-to'g'ri)
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: 'wbefkccz_avtomaktab');
define('DB_USER',    getenv('DB_USER') ?: 'wbefkccz_avtomaktab');
define('DB_PASS',    getenv('DB_PASS') ?: 'FrHCuXUP6RfY4XnzDGBw');
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO ulanish — singleton.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_PERSISTENT         => false,
        ]);
    } catch (PDOException $e) {
        error_log('[VPY] DB xato: ' . $e->getMessage());
        http_response_code(503);
        exit('Server vaqtinchalik mavjud emas. Iltimos, keyinroq urinib ko\'ring.');
    }
    return $pdo;
}

/** Bitta qatorni qaytaradi, topilmasa null. */
function db_qator(string $sql, array $p = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetch() ?: null;
}

/** Barcha qatorlarni qaytaradi. */
function db_barcha(string $sql, array $p = []): array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

/** INSERT → lastInsertId, UPDATE/DELETE → rowCount. */
function db_bajar(string $sql, array $p = []): int
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return stripos(ltrim($sql), 'INSERT') === 0
        ? (int) db()->lastInsertId()
        : $st->rowCount();
}

/** Birinchi ustunning birinchi qiymati. */
function db_qiymat(string $sql, array $p = []): mixed
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
}

/**
 * Sozlamalardan qiymat olish.
 * Bir so'rovda barcha sozlamalar yuklanadi va keshlanadi.
 *
 * BUG FIX: sozlama_kesh_yangilanish() olib tashlandi —
 * sozlama_saqla() dan keyin yonaltirish bo'ladi, yangi so'rovda kesh tozalanadi.
 */
function sozlama(string $kalit, mixed $standart = null): mixed
{
    static $kesh = null;
    if ($kesh === null) {
        $kesh = [];
        try {
            foreach (db_barcha('SELECT kalit, qiymat FROM sozlamalar') as $s) {
                $kesh[$s['kalit']] = $s['qiymat'];
            }
        } catch (Throwable) {
            // Jadval hali yaratilmagan — silent fail
        }
    }
    return array_key_exists($kalit, $kesh) ? $kesh[$kalit] : $standart;
}

/** Sozlamani DB ga yozish (kesh keyingi so'rovda yangilanadi). */
function sozlama_saqla(string $kalit, mixed $qiymat): void
{
    db_bajar(
        'INSERT INTO sozlamalar (kalit, qiymat) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)',
        [$kalit, (string) $qiymat]
    );
}
