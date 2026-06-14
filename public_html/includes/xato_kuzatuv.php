<?php
/**
 * VatanParvar Yaypan — Global xato kuzatuvchi
 * ------------------------------------------------------------
 * Bu fayl set_error_handler / set_exception_handler /
 * register_shutdown_function orqali HAR QANDAY xatoni
 * markaziy log tizimiga yo'naltiradi.
 *
 * Shuningdek, har bir so'rovning bajarilish vaqtini o'lchaydi
 * va 500ms+ bo'lsa "sekin" log'ga yozadi (xak.php yuklama tabi uchun).
 *
 * `config/auth.php` ning eng boshida ulanadi.
 */

require_once __DIR__ . '/log.php';

// So'rov boshlanish vaqti
if (!defined('SOROV_BOSHI')) {
    define('SOROV_BOSHI', microtime(true));
}

// ---------- 1. Oddiy PHP xatolari ----------
set_error_handler(function (int $kod, string $matn, string $fayl, int $satr): bool {
    if (!(error_reporting() & $kod)) {
        return false;
    }
    $turlar = [
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'PARSE',
        E_NOTICE            => 'NOTICE',
        E_CORE_ERROR        => 'CORE_ERROR',
        E_CORE_WARNING      => 'CORE_WARNING',
        E_COMPILE_ERROR     => 'COMPILE_ERROR',
        E_COMPILE_WARNING   => 'COMPILE_WARNING',
        E_USER_ERROR        => 'USER_ERROR',
        E_USER_WARNING      => 'USER_WARNING',
        E_USER_NOTICE       => 'USER_NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE',
        E_DEPRECATED        => 'DEPRECATED',
        E_USER_DEPRECATED   => 'USER_DEPRECATED',
    ];
    log_xato(basename($fayl), $matn, [
        'daraja' => $turlar[$kod] ?? 'UNKNOWN',
        'fayl'   => $fayl,
        'satr'   => $satr,
    ]);
    // false qaytarsak — PHP'ning standart xato qayd qilishi davom etadi
    return false;
});

// ---------- 2. Tutilmagan exceptionlar ----------
set_exception_handler(function (Throwable $e): void {
    log_xato(basename($e->getFile()), $e->getMessage(), [
        'daraja' => 'EXCEPTION',
        'sinf'   => get_class($e),
        'fayl'   => $e->getFile(),
        'satr'   => $e->getLine(),
        'trace'  => mb_substr($e->getTraceAsString(), 0, 2000),
    ]);

    // Production'da chiroyli sahifa
    if (defined('REJIM') && REJIM === 'production') {
        if (!headers_sent()) {
            http_response_code(500);
        }
        $_500 = dirname(__DIR__) . '/500.php';
        if (is_file($_500)) {
            require $_500;
        } else {
            echo '<h1>Server xatosi</h1><p>Iltimos, keyinroq urinib ko\'ring.</p>';
        }
        exit;
    }
    // Development'da batafsil chiqadi
    throw $e;
});

// ---------- 3. Fatal xatolar (parse, OOM, ...) ----------
register_shutdown_function(function (): void {
    $oxirgi = error_get_last();
    if ($oxirgi && in_array($oxirgi['type'], [
        E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR,
    ], true)) {
        log_xato(basename($oxirgi['file'] ?? 'shutdown'),
            'FATAL: ' . ($oxirgi['message'] ?? ''),
            [
                'daraja' => 'FATAL',
                'fayl'   => $oxirgi['file'] ?? null,
                'satr'   => $oxirgi['line'] ?? null,
                'turi'   => $oxirgi['type'] ?? null,
            ]);
    }

    // ---------- 4. Sekin so'rovlarni qayd qilish ----------
    // Static fayllar va ajax-pollinglar uchun emas
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\.(css|js|png|jpe?g|gif|webp|svg|woff2?|ico|map)(\?|$)/i', $uri)) {
        return;
    }
    $vaqt_ms = (int) ((microtime(true) - SOROV_BOSHI) * 1000);
    if ($vaqt_ms > 500) {
        log_info('sekin_sorov', 'Sekin so\'rov', [
            'vaqt_ms' => $vaqt_ms,
            'memory'  => memory_get_peak_usage(true),
        ]);
    }
});
