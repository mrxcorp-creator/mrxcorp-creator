<?php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('METHOD');
}

http_response_code(204);
header('Content-Type: text/plain');

if (!csrf_tekshir(post('csrf_token'))) {
    exit;
}

$tur = post('tur');
$ruxsatli_turlar = [
    'right_click', 'copy_attempt', 'view_source_attempt',
    'print_attempt', 'devtools_shortcut', 'devtools_opened',
    'console_inspect', 'f12_press',
];
if (!in_array($tur, $ruxsatli_turlar, true)) {
    exit;
}

$f = joriy_foydalanuvchi();
if ($f && in_array($f['rol'], ['admin', 'developer'], true)) {
    exit;
}

$tafsilot_xom = post('tafsilot');
$tafsilot = null;
if ($tafsilot_xom) {
    $j = json_decode($tafsilot_xom, true);
    if ($j) $tafsilot = $j;
}

try {
    db_bajar(
        'INSERT INTO xavfsizlik_qaydlar (foydalanuvchi_id, tur, ip, user_agent, manzil, tafsilot)
         VALUES (?, ?, ?, ?, ?, ?)',
        [
            $f['id'] ?? null,
            $tur,
            ip_olish(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            substr(post('manzil'), 0, 255),
            $tafsilot ? json_encode($tafsilot, JSON_UNESCAPED_UNICODE) : null,
        ]
    );

    $ip = ip_olish();
    $ip_block_yoq = (int) sozlama('himoya_ip_block', 0);
    if ($ip_block_yoq) {
        $oxirgi_5min = (int) db_qiymat(
            'SELECT COUNT(*) FROM xavfsizlik_qaydlar
             WHERE ip = ? AND yaratilgan > DATE_SUB(NOW(), INTERVAL 5 MINUTE)',
            [$ip]
        );
        if ($oxirgi_5min > 15) {
            db_bajar(
                'INSERT INTO bloklangan_iplar (ip, sabab, tugash)
                 VALUES (?, "Anti-copy himoyasini buzishga urinish", DATE_ADD(NOW(), INTERVAL 1 HOUR))
                 ON DUPLICATE KEY UPDATE
                    tugash = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                    sabab = VALUES(sabab)',
                [$ip]
            );
        }
    }
} catch (Throwable $e) {
    error_log('Xavfsizlik qayd xato: ' . $e->getMessage());
}

exit;
