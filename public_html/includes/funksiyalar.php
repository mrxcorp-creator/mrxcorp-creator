<?php
require_once __DIR__ . '/../config/database.php';

function pul(int|float $summa, string $valyuta = "so'm"): string {
    return number_format($summa, 0, '.', ' ') . ' ' . $valyuta;
}

function sana(?string $vaqt, string $format = 'd.m.Y H:i'): string {
    if (!$vaqt) return '—';
    return date($format, strtotime($vaqt));
}

function vaqt_oldin(string $vaqt): string {
    $diff = time() - strtotime($vaqt);
    if ($diff < 60)         return $diff . ' soniya oldin';
    if ($diff < 3600)       return floor($diff / 60) . ' daqiqa oldin';
    if ($diff < 86400)      return floor($diff / 3600) . ' soat oldin';
    if ($diff < 2592000)    return floor($diff / 86400) . ' kun oldin';
    return date('d.m.Y', strtotime($vaqt));
}

function rasm_saqla(array $fayl, string $papka = 'savollar', int $maks = 800): ?string {
    if (empty($fayl['tmp_name']) || !is_uploaded_file($fayl['tmp_name'])) {
        return null;
    }
    $papka_yoli = UPLOAD_PATH . '/' . $papka;
    if (!is_dir($papka_yoli)) {
        mkdir($papka_yoli, 0755, true);
    }
    $info = @getimagesize($fayl['tmp_name']);
    if (!$info) return null;

    $manba = match ($info['mime']) {
        'image/jpeg' => imagecreatefromjpeg($fayl['tmp_name']),
        'image/png'  => imagecreatefrompng($fayl['tmp_name']),
        'image/webp' => imagecreatefromwebp($fayl['tmp_name']),
        default      => null,
    };
    if (!$manba) return null;

    [$w, $h] = [$info[0], $info[1]];
    if ($w > $maks || $h > $maks) {
        $nisbat = $maks / max($w, $h);
        $yangi_w = (int) ($w * $nisbat);
        $yangi_h = (int) ($h * $nisbat);
        $yangi = imagecreatetruecolor($yangi_w, $yangi_h);
        imagecopyresampled($yangi, $manba, 0, 0, 0, 0, $yangi_w, $yangi_h, $w, $h);
        imagedestroy($manba);
        $manba = $yangi;
    }
    $nom = uniqid('img_', true) . '.webp';
    $yol = $papka_yoli . '/' . $nom;
    imagewebp($manba, $yol, 80);
    imagedestroy($manba);

    return $papka . '/' . $nom;
}

function maxfiy_qiymat(string $kalit, string $sozlama_kalit = ''): string {
    $sozlama_kalit = $sozlama_kalit ?: $kalit;
    $const = strtoupper($kalit);
    if (defined($const) && constant($const) !== '') {
        return (string) constant($const);
    }
    return (string) (sozlama($sozlama_kalit) ?? '');
}

function fonda_yakunla(): void {
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
        return;
    }
    if (!headers_sent()) {
        ignore_user_abort(true);
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
    }
    while (ob_get_level()) ob_end_flush();
    flush();
}

function telegram_navbatga(int|string $chat_id, string $matn, array $qoshimcha = []): void {
    try {
        db_bajar(
            'INSERT INTO telegram_navbat (chat_id, matn, qoshimcha_json) VALUES (?, ?, ?)',
            [(string) $chat_id, $matn, $qoshimcha ? json_encode($qoshimcha, JSON_UNESCAPED_UNICODE) : null]
        );
    } catch (Throwable $e) {
        @telegram_yubor_xom($chat_id, $matn, $qoshimcha);
    }
}

function telegram_yubor_xom(int|string $chat_id, string $matn, array $qoshimcha = []): bool {
    $token = maxfiy_qiymat('TELEGRAM_BOT_TOKEN', 'telegram_bot_token');
    if (!$token || !$chat_id) return false;

    $data = array_merge([
        'chat_id'    => $chat_id,
        'text'       => $matn,
        'parse_mode' => 'HTML',
    ], $qoshimcha);

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $javob = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

function telegram_yubor(int|string $chat_id, string $matn, array $qoshimcha = []): bool {
    if (PHP_SAPI === 'cli' || (defined('TELEGRAM_SYNC') && TELEGRAM_SYNC)) {
        return telegram_yubor_xom($chat_id, $matn, $qoshimcha);
    }
    telegram_navbatga($chat_id, $matn, $qoshimcha);
    return true;
}

function telegram_navbatni_jonat(int $maks = 20): array {
    $natija = ['jonatildi' => 0, 'xato' => 0];

    $xabarlar = db_barcha(
        'SELECT * FROM telegram_navbat
         WHERE holat IN ("kutilmoqda","xato") AND urinish < 3
         ORDER BY id ASC LIMIT ' . (int) $maks
    );

    foreach ($xabarlar as $x) {
        $qosh = $x['qoshimcha_json'] ? (json_decode($x['qoshimcha_json'], true) ?: []) : [];
        $ok = telegram_yubor_xom($x['chat_id'], $x['matn'], $qosh);

        if ($ok) {
            db_bajar('UPDATE telegram_navbat SET holat = "jonatildi" WHERE id = ?', [$x['id']]);
            $natija['jonatildi']++;
        } else {
            db_bajar(
                'UPDATE telegram_navbat
                 SET urinish = urinish + 1,
                     holat = IF(urinish + 1 >= 3, "xato", "kutilmoqda"),
                     xato_matn = "API javob bermadi"
                 WHERE id = ?',
                [$x['id']]
            );
            $natija['xato']++;
        }
    }

    db_bajar('DELETE FROM telegram_navbat WHERE holat = "jonatildi" AND yangilangan < DATE_SUB(NOW(), INTERVAL 7 DAY)');

    return $natija;
}

function telegram_fayl_yubor(int|string $chat_id, string $fayl_yoli, string $izoh = ''): bool {
    $token = maxfiy_qiymat('TELEGRAM_BOT_TOKEN', 'telegram_bot_token');
    if (!$token || !is_file($fayl_yoli)) return false;

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendDocument");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'chat_id'  => $chat_id,
            'caption'  => $izoh,
            'document' => new CURLFile($fayl_yoli),
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $javob = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($javob, true);
    return !empty($j['ok']);
}

function audit_yoz(string $harakat, ?string $obyekt_turi = null, ?int $obyekt_id = null, array $tafsilot = []): void {
    try {
        $foyd_id = $_SESSION['foydalanuvchi_id'] ?? null;
        db_bajar(
            'INSERT INTO auditlar (foydalanuvchi_id, harakat, obyekt_turi, obyekt_id, tafsilot, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $foyd_id,
                $harakat,
                $obyekt_turi,
                $obyekt_id,
                $tafsilot ? json_encode($tafsilot, JSON_UNESCAPED_UNICODE) : null,
                ip_olish(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]
        );
    } catch (Throwable $e) {
        error_log('Audit log xato: ' . $e->getMessage());
    }
}

function parol_murakkabmi(string $parol): array {
    if (mb_strlen($parol) < 8) {
        return ['ok' => false, 'xato' => 'Parol kamida 8 belgi bo\'lishi kerak'];
    }
    if (!preg_match('/[A-Za-z]/', $parol)) {
        return ['ok' => false, 'xato' => 'Parol kamida 1 ta harf bo\'lishi kerak'];
    }
    if (!preg_match('/[0-9]/', $parol)) {
        return ['ok' => false, 'xato' => 'Parol kamida 1 ta raqam bo\'lishi kerak'];
    }
    return ['ok' => true];
}

function qurilma_aniqla(string $ua): string {
    if (preg_match('/iPhone|iPad/i', $ua)) return 'iOS';
    if (preg_match('/Android/i', $ua)) return 'Android';
    if (preg_match('/Macintosh/i', $ua)) return 'Mac';
    if (preg_match('/Windows/i', $ua)) return 'Windows';
    if (preg_match('/Linux/i', $ua)) return 'Linux';
    return 'Boshqa';
}

function brauzer_aniqla(string $ua): string {
    if (preg_match('/Edg\//i', $ua)) return 'Edge';
    if (preg_match('/OPR\/|Opera/i', $ua)) return 'Opera';
    if (preg_match('/Firefox/i', $ua)) return 'Firefox';
    if (preg_match('/Chrome/i', $ua)) return 'Chrome';
    if (preg_match('/Safari/i', $ua)) return 'Safari';
    return 'Boshqa';
}

function honeypot_input(): string {
    return '<div class="honeypot" aria-hidden="true">' .
           '<label>Bu maydonni bo\'sh qoldiring</label>' .
           '<input type="text" name="website" tabindex="-1" autocomplete="off">' .
           '</div>';
}

function honeypot_tekshir(): bool {
    return empty($_POST['website']);
}

function csrf_form_token(string $forma): string {
    sessiya_boshla();
    if (empty($_SESSION['csrf_form_tokens'][$forma])) {
        $_SESSION['csrf_form_tokens'][$forma] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_form_tokens'][$forma];
}

function csrf_form_tekshir(string $forma, ?string $token): bool {
    sessiya_boshla();
    if (empty($_SESSION['csrf_form_tokens'][$forma]) || empty($token)) {
        return false;
    }
    $ok = hash_equals($_SESSION['csrf_form_tokens'][$forma], $token);
    if ($ok) {
        unset($_SESSION['csrf_form_tokens'][$forma]);
    }
    return $ok;
}

function csrf_form_input(string $forma): string {
    return '<input type="hidden" name="csrf_forma_token" value="' .
           htmlspecialchars(csrf_form_token($forma), ENT_QUOTES) . '">' .
           '<input type="hidden" name="csrf_forma_nom" value="' .
           htmlspecialchars($forma, ENT_QUOTES) . '">';
}

function json_javob(array $data, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function olish(string $kalit, string $standart = ''): string {
    return isset($_GET[$kalit]) ? trim((string) $_GET[$kalit]) : $standart;
}

function post(string $kalit, string $standart = ''): string {
    return isset($_POST[$kalit]) ? trim((string) $_POST[$kalit]) : $standart;
}

function avatar_url(?array $f): string {
    if (!empty($f['avatar']) && is_file(UPLOAD_PATH . '/' . $f['avatar'])) {
        return SAYT_URL . '/uploads/' . $f['avatar'];
    }
    return '';
}

function bosh_harflar(?array $f): string {
    if (!$f) return '?';
    $i = mb_substr($f['ism'] ?? '?', 0, 1);
    $fa = mb_substr($f['familiya'] ?? '', 0, 1);
    return mb_strtoupper($i . $fa);
}
