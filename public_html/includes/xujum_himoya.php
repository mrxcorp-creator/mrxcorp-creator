<?php
/**
 * VatanParvar Yaypan — Hujumlardan himoya (mini-WAF)
 * ------------------------------------------------------------
 * Har bir so'rovni tekshiradi va shubhali patternlarda 403 qaytaradi.
 *
 *  Tekshiriladi:
 *   - SQL injection signaturalari (UNION SELECT, OR 1=1, ...)
 *   - XSS signaturalari (<script>, javascript:, onerror=)
 *   - LFI/RFI urinishlari (../ , file://, php://)
 *   - Code injection (eval, base64_decode, system)
 *   - Bot/scanner User-Agent (sqlmap, nikto, acunetix, ...)
 *   - Avto-blok takroriy buzg'unchilarni
 *
 * Bu fayl `config/auth.php`ning eng boshida ulanadi.
 */

require_once __DIR__ . '/log.php';

// ============================================================
//  IP-blok tizimi (DB orqali)
// ============================================================

/**
 * IP bloklanganmi?
 */
function ip_bloklanganmi(string $ip): bool {
    if (!function_exists('db')) {
        return false;
    }
    try {
        return (bool) db_qiymat(
            'SELECT 1 FROM bloklangan_iplar
             WHERE ip = ? AND (tugash IS NULL OR tugash > NOW())
             LIMIT 1',
            [$ip]
        );
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * IPni vaqtinchalik yoki abadiy bloklash.
 *  $daqiqa = 0  => abadiy
 */
function ip_blokla(string $ip, string $sabab, int $daqiqa = 60): void {
    if (!function_exists('db')) {
        return;
    }
    try {
        $tugash = $daqiqa > 0
            ? date('Y-m-d H:i:s', time() + $daqiqa * 60)
            : null;
        db_bajar(
            'INSERT INTO bloklangan_iplar (ip, sabab, tugash)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
               sabab = VALUES(sabab),
               tugash = VALUES(tugash),
               yaratilgan = NOW()',
            [$ip, mb_substr($sabab, 0, 250), $tugash]
        );
    } catch (Throwable $e) {
        log_xato('xujum_himoya', "IP blokini yozib bo'lmadi: " . $e->getMessage());
    }
}

/**
 * Bloklangan IPlar ro'yxati (xak.php uchun).
 */
function bloklangan_iplar_royxat(int $cheklov = 200): array {
    if (!function_exists('db')) {
        return [];
    }
    try {
        return db_barcha(
            'SELECT * FROM bloklangan_iplar
             WHERE tugash IS NULL OR tugash > NOW()
             ORDER BY yaratilgan DESC LIMIT ' . (int) $cheklov
        );
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * IP blokini olib tashlash.
 */
function ip_blokni_yech(string $ip): void {
    if (!function_exists('db')) {
        return;
    }
    try {
        db_bajar('DELETE FROM bloklangan_iplar WHERE ip = ?', [$ip]);
    } catch (Throwable $e) {}
}

// ============================================================
//  Hujum signaturalari
// ============================================================

/**
 * SQL injection signaturalari.
 */
function _sql_inj_pattern(): array {
    return [
        '/\b(union\s+(all\s+)?select)\b/i',
        '/\b(select|insert|update|delete|drop|alter|create|truncate|exec|execute)\b\s+.*\bfrom\b/i',
        '/\b(or|and)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?/i',
        '/\b(or|and)\s+[\'"]?[a-z]+[\'"]?\s*=\s*[\'"]?[a-z]+[\'"]?/i',
        '/[\'"]\s*;\s*(drop|delete|update|insert)/i',
        '/\b(sleep|benchmark|pg_sleep|waitfor)\s*\(/i',
        '/\b(load_file|into\s+outfile|into\s+dumpfile)\b/i',
        '/\b(information_schema|mysql\.user|sysobjects)\b/i',
        '/--\s*$/m',                               // SQL comment in tail
        '/\/\*.*?\*\//s',                          // SQL block comment
        '/\bxp_cmdshell\b/i',
    ];
}

/**
 * XSS signaturalari.
 */
function _xss_pattern(): array {
    return [
        '/<\s*script\b[^>]*>/i',
        '/<\s*iframe\b[^>]*>/i',
        '/<\s*object\b[^>]*>/i',
        '/<\s*embed\b[^>]*>/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/on(load|error|click|mouseover|focus|blur|submit|change|keyup)\s*=/i',
        '/document\.(cookie|write|location)/i',
        '/window\.(location|open)/i',
        '/<\s*svg\b[^>]*\bonload/i',
    ];
}

/**
 * LFI/RFI signaturalari.
 */
function _lfi_pattern(): array {
    return [
        '/\.\.[\/\\\\]/',                     // Path traversal
        '/(php|file|data|expect|zip|phar):\/\//i',
        '/\/etc\/(passwd|shadow|hosts|group)/i',
        '/\/proc\/self\/(environ|cmdline)/i',
        '/\/var\/log\//i',
        '/c:\\\\windows\\\\/i',
    ];
}

/**
 * Code injection signaturalari.
 */
function _code_inj_pattern(): array {
    return [
        '/\b(eval|assert|system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i',
        '/\b(base64_decode|gzinflate|str_rot13)\s*\(/i',
        '/\b(create_function|preg_replace.*\/e)/i',
        '/\$\{\s*[a-z_]/i',                    // Variable injection
    ];
}

/**
 * Yomon User-Agent ro'yxati.
 */
function _yomon_ua_pattern(): array {
    return [
        '/sqlmap/i', '/nikto/i', '/acunetix/i', '/havij/i',
        '/nessus/i', '/openvas/i', '/whatweb/i', '/wpscan/i',
        '/jaeles/i', '/nuclei/i', '/zgrab/i', '/masscan/i',
        '/dirbuster/i', '/dirb\b/i', '/gobuster/i', '/feroxbuster/i',
        '/x-?ray/i', '/morfeus/i', '/sucuri/i',
        '/zmeu/i', '/baiduspider-render/i',
        '/python-requests/i', '/curl\/[0-7]/i',
        '/libwww-perl/i', '/wget\b/i',
    ];
}

/**
 * Berilgan matnda biror patternga to'g'ri kelishi.
 */
function _patternga_tushdimi(string $matn, array $patternlar): string {
    foreach ($patternlar as $p) {
        if (preg_match($p, $matn)) {
            return $p;
        }
    }
    return '';
}

/**
 * So'rovning qiymatlarini tekshirish va shubhali patternni qaytarish.
 */
function _sorovni_tekshir(): array {
    $manbalar = [
        'GET'    => $_GET    ?? [],
        'POST'   => $_POST   ?? [],
        'COOKIE' => $_COOKIE ?? [],
        'URI'    => [$_SERVER['REQUEST_URI'] ?? ''],
    ];
    $turlar = [
        'SQLI' => _sql_inj_pattern(),
        'XSS'  => _xss_pattern(),
        'LFI'  => _lfi_pattern(),
        'CODE' => _code_inj_pattern(),
    ];

    foreach ($manbalar as $mn => $arr) {
        foreach ($arr as $kalit => $qiymat) {
            $matn = is_array($qiymat) ? json_encode($qiymat) : (string) $qiymat;
            // Webhook va admin sessiyalari ba'zan katta bo'ladi — cheklov
            $matn = mb_substr($matn, 0, 5000);
            foreach ($turlar as $tur => $patternlar) {
                $hit = _patternga_tushdimi($matn, $patternlar);
                if ($hit) {
                    return [
                        'tur'     => $tur,
                        'pattern' => $hit,
                        'manba'   => $mn,
                        'kalit'   => (string) $kalit,
                        'qiymat'  => mb_substr($matn, 0, 500),
                    ];
                }
            }
        }
    }
    return [];
}

/**
 * User-Agent tekshirish.
 */
function _ua_tekshir(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') {
        return 'BO\'SH_UA';
    }
    $hit = _patternga_tushdimi($ua, _yomon_ua_pattern());
    return $hit ? 'YOMON_UA' : '';
}

// ============================================================
//  Asosiy guard funksiyasi
// ============================================================

/**
 * Hozirgi so'rovni tekshiradi va shubhali bo'lsa bloklaydi.
 *  $oq_royxat — IP'lar (masalan, click/payme webhook IPlari)
 */
function xujum_himoya_ishga_tushir(array $oq_royxat = []): void {
    // 1) Static fayllarga tegmaymiz
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|woff2?|ttf|ico|map)(\?|$)/i', $uri)) {
        return;
    }

    $ip = function_exists('ip_olish') ? ip_olish() : ($_SERVER['REMOTE_ADDR'] ?? '');

    // Oq ro'yxatdagi IPlar uchun tekshiruv yo'q (masalan, payme webhook)
    if (in_array($ip, $oq_royxat, true)) {
        return;
    }

    // 2) IP bloklangan bo'lsa — to'xtatamiz
    if (ip_bloklanganmi($ip)) {
        log_xavfsizlik('BLOKLANGAN_IP_URINISH', ['ip' => $ip]);
        _xujum_javob(403, 'Sizning IP manzilingiz vaqtincha bloklangan.');
    }

    // 3) Yomon User-Agent
    if ($yomon_ua = _ua_tekshir()) {
        log_xavfsizlik($yomon_ua, ['ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
        ip_blokla($ip, "Yomon UA: " . mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100), 60 * 24);
        _xujum_javob(403, 'Ruxsat berilmagan.');
    }

    // 4) Shubhali so'rov qiymatlari
    $hit = _sorovni_tekshir();
    if ($hit) {
        log_xavfsizlik('XUJUM_' . $hit['tur'], $hit);

        // 3 marta ushlasak — IPni 24 soatga bloklaymiz
        $sayqal = _xujum_sayqal_olish($ip);
        $sayqal++;
        _xujum_sayqal_saqlash($ip, $sayqal);

        if ($sayqal >= 3) {
            ip_blokla($ip, $hit['tur'] . ' (' . $sayqal . ' marta)', 60 * 24);
            log_xavfsizlik('IP_AVTO_BLOK', ['ip' => $ip, 'sayqal' => $sayqal]);
        }
        _xujum_javob(403, 'So\'rov xavfsiz emas deb topildi.');
    }
}

/**
 * Hujum sayqal hisobi (kesh fayl orqali — DB-ga bog'liq emas).
 */
function _xujum_sayqal_fayl(): string {
    $papka = LOG_PATH . '/sayqallar';
    if (!is_dir($papka)) @mkdir($papka, 0750, true);
    return $papka . '/' . date('Y-m-d') . '.json';
}

function _xujum_sayqal_olish(string $ip): int {
    $f = _xujum_sayqal_fayl();
    if (!is_file($f)) return 0;
    $data = json_decode(@file_get_contents($f) ?: '{}', true) ?: [];
    return (int) ($data[$ip] ?? 0);
}

function _xujum_sayqal_saqlash(string $ip, int $son): void {
    $f = _xujum_sayqal_fayl();
    $fp = @fopen($f, 'c+');
    if (!$fp) return;
    if (flock($fp, LOCK_EX)) {
        $data = json_decode(stream_get_contents($fp) ?: '{}', true) ?: [];
        $data[$ip] = $son;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

/**
 * 403 javob va to'xtatish.
 */
function _xujum_javob(int $kod, string $matn): void {
    if (!headers_sent()) {
        http_response_code($kod);
        header('Content-Type: text/html; charset=utf-8');
    }
    $_403 = dirname(__DIR__) . '/403.php';
    if (is_file($_403)) {
        require $_403;
    } else {
        echo '<!DOCTYPE html><html lang="uz"><head><meta charset="utf-8"><title>403</title></head>'
           . '<body style="font-family:system-ui;background:#0b1020;color:#fff;text-align:center;padding:60px">'
           . '<h1 style="font-size:64px;margin:0">403</h1><p>' . htmlspecialchars($matn) . '</p></body></html>';
    }
    exit;
}
