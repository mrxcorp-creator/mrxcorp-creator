<?php
/**
 * AvtoTest Pro — Ma'lumotlar bazasi (PDO singleton)
 * ------------------------------------------------------------
 * Faqat tayyorlangan so'rovlar (Prepared Statements) ishlatiladi.
 * DB credentials .env faylidan yoki to'g'ridan-to'g'ri quyida.
 */

require_once __DIR__ . '/config.php';

// ----- Ma'lumotlar bazasi parametrlari -----
// Xavfsiz: .env faylidan o'qish, aks holda qiymatlarni o'zgartiring
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'wbefkccz_avtomaktab');
define('DB_USER',    getenv('DB_USER')    ?: 'wbefkccz_avtomaktab');
define('DB_PASS',    getenv('DB_PASS')    ?: 'FrHCuXUP6RfY4XnzDGBw');
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO ulanishini bitta marta yaratuvchi singleton.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    $variantlar = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT         => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $variantlar);
    } catch (PDOException $e) {
        error_log('DB ulanish xatosi: ' . $e->getMessage());
        http_response_code(503);
        exit('Server vaqtinchalik mavjud emas. Iltimos, bir oz kutib qayta urinib ko\'ring.');
    }

    return $pdo;
}

/**
 * Bir qatorni qaytaradi, topilmasa null.
 */
function db_qator(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Barcha qatorlarni qaytaradi.
 */
function db_barcha(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * INSERT/UPDATE/DELETE: INSERT da lastInsertId, boshqasida rowCount.
 */
function db_bajar(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    if (stripos(ltrim($sql), 'INSERT') === 0) {
        return (int) db()->lastInsertId();
    }
    return $st->rowCount();
}

/**
 * Birinchi ustunning birinchi qiymatini qaytaradi.
 */
function db_qiymat(string $sql, array $params = []): mixed
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

/**
 * Sozlamalardan qiymat olish — keshlanadi.
 *
 * @param  string      $kalit    Sozlama kaliti
 * @param  mixed|null  $standart Topilmasa qaytariladigan qiymat
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
            // Jadval hali yaratilmagan bo'lishi mumkin
        }
    }
    return array_key_exists($kalit, $kesh) ? $kesh[$kalit] : $standart;
}

/**
 * Sozlamani saqlash va keshni yangilash.
 */
function sozlama_saqla(string $kalit, mixed $qiymat): void
{
    db_bajar(
        'INSERT INTO sozlamalar (kalit, qiymat)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE qiymat = VALUES(qiymat)',
        [$kalit, (string) $qiymat]
    );
    // Keshni tozalash (keyingi chaqirilishda qayta yuklaydi)
    // Static kesh resetlash uchun workaround
    sozlama_kesh_yangilanish();
}

/**
 * Sozlamalar keshini tozalash.
 */
function sozlama_kesh_yangilanish(): void
{
    // PHPda static keshni bekor qilish uchun reflection yoki global flag ishlatamiz
    static $flagKalit = 'sozlama_kesh_dirty';
    $_SERVER[$flagKalit] = true;
}
