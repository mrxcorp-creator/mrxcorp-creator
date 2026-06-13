<?php
/**
 * VatanParvar Yaypan — Markaziy log tizimi
 * ------------------------------------------------------------
 *  - JSONL formatida kunlik fayllarga yozadi
 *  - Kategoriyalar: xato, kirish, xavfsizlik, soriqlar, info
 *  - Atomar yozish (LOCK_EX) — multi-process safe
 *  - 30 kundan eski fayllarni avto-tozalaydi
 *
 *  Foydalanish:
 *      log_xato('login.php', 'PDO bog\'lanish xatosi', ['exception' => $e->getMessage()]);
 *      log_xavfsizlik('SQL_INJECTION_URINISHI', ['sorov' => $_GET['q']]);
 *      log_kirish($telefon, $muvaffaqiyat);
 */

// Asosiy log papkasi
if (!defined('LOG_PATH')) {
    define('LOG_PATH', dirname(__DIR__) . '/loglar');
}

/**
 * Log yozish uchun ichki yordamchi.
 */
function _log_yoz(string $kategoriya, array $malumot): bool {
    $papka = LOG_PATH . '/' . $kategoriya;
    if (!is_dir($papka)) {
        @mkdir($papka, 0750, true);
    }
    if (!is_writable($papka)) {
        // Fallback: PHP error log
        error_log('[VatanParvar log] ' . json_encode($malumot, JSON_UNESCAPED_UNICODE));
        return false;
    }
    $fayl = $papka . '/' . date('Y-m-d') . '.jsonl';

    $malumot['vaqt']    = date('c');
    $malumot['ip']      = function_exists('ip_olish') ? ip_olish() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $malumot['url']     = $_SERVER['REQUEST_URI'] ?? '';
    $malumot['usul']    = $_SERVER['REQUEST_METHOD'] ?? '';
    $malumot['ua']      = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
    $malumot['user_id'] = $_SESSION['foydalanuvchi_id'] ?? null;

    $satr = json_encode($malumot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    return (bool) @file_put_contents($fayl, $satr, FILE_APPEND | LOCK_EX);
}

/**
 * Xato logini yozish.
 */
function log_xato(string $manba, string $matn, array $kontekst = []): void {
    _log_yoz('xato', [
        'manba'    => $manba,
        'matn'     => $matn,
        'kontekst' => $kontekst,
        'daraja'   => $kontekst['daraja'] ?? 'ERROR',
    ]);
}

/**
 * Xavfsizlik hodisasini yozish (hujum, anomaliya, blok).
 */
function log_xavfsizlik(string $tur, array $kontekst = []): void {
    _log_yoz('xavfsizlik', [
        'tur'      => $tur,
        'kontekst' => $kontekst,
    ]);
}

/**
 * Kirish urinishi.
 */
function log_kirish(string $telefon, bool $muvaffaqiyat, string $sabab = ''): void {
    _log_yoz('kirish', [
        'telefon'      => $telefon,
        'muvaffaqiyat' => $muvaffaqiyat,
        'sabab'        => $sabab,
    ]);
}

/**
 * So'rov logini yozish (faqat DEBUG / shubhali).
 */
function log_sorov(string $izoh, array $malumot = []): void {
    _log_yoz('sorov', [
        'izoh'     => $izoh,
        'malumot'  => $malumot,
    ]);
}

/**
 * Umumiy info log.
 */
function log_info(string $manba, string $matn, array $kontekst = []): void {
    _log_yoz('info', [
        'manba'    => $manba,
        'matn'     => $matn,
        'kontekst' => $kontekst,
    ]);
}

/**
 * Eski log fayllarini tozalash (cron orqali kuniga 1 marta).
 */
function log_tozalash(int $kun = 30): int {
    $ochirildi = 0;
    foreach (glob(LOG_PATH . '/*', GLOB_ONLYDIR) ?: [] as $papka) {
        foreach (glob($papka . '/*.jsonl') ?: [] as $fayl) {
            if (filemtime($fayl) < time() - $kun * 86400) {
                if (@unlink($fayl)) {
                    $ochirildi++;
                }
            }
        }
    }
    return $ochirildi;
}

/**
 * Loglarni o'qish (xak.php uchun).
 */
function log_oq(string $kategoriya, string $sana = '', int $maks = 500): array {
    $sana = $sana ?: date('Y-m-d');
    $fayl = LOG_PATH . '/' . preg_replace('/[^a-z]/i', '', $kategoriya) . '/' .
            preg_replace('/[^0-9-]/', '', $sana) . '.jsonl';
    if (!is_file($fayl)) {
        return [];
    }
    $satrlar = @file($fayl, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $satrlar = array_slice(array_reverse($satrlar), 0, $maks);
    $natija = [];
    foreach ($satrlar as $s) {
        $j = json_decode($s, true);
        if (is_array($j)) {
            $natija[] = $j;
        }
    }
    return $natija;
}

/**
 * Ma'lum kategoriyada qancha log fayl borligi va o'lchami.
 */
function log_statistika(): array {
    $stat = [];
    foreach (['xato', 'kirish', 'xavfsizlik', 'soriqlar', 'info'] as $kat) {
        $papka = LOG_PATH . '/' . $kat;
        $fayllar = glob($papka . '/*.jsonl') ?: [];
        $jami_olcham = 0;
        $jami_satr = 0;
        foreach ($fayllar as $f) {
            $jami_olcham += filesize($f);
            $jami_satr   += @count(@file($f) ?: []);
        }
        $stat[$kat] = [
            'fayllar' => count($fayllar),
            'olcham'  => $jami_olcham,
            'satrlar' => $jami_satr,
        ];
    }
    return $stat;
}
