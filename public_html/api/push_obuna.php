<?php
/**
 * Web Push obuna saqlash (foydalanuvchi tomonidan)
 *
 * Service Worker chaqirib, foydalanuvchi push xabar olishga ruxsat bersa,
 * brauzer endpoint + kalitlarini yuboradi. Bizning serverda saqlanadi.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

$f = joriy_foydalanuvchi();
if (!$f) {
    json_javob(['ok' => false, 'xato' => t('avval_kiring')], 401);
}

$endpoint = trim(post('endpoint'));
$p256dh   = trim(post('p256dh'));
$auth_key = trim(post('auth'));

if (!$endpoint || !$p256dh || !$auth_key) {
    json_javob(['ok' => false, 'xato' => 'Maydonlar to\'liq emas'], 400);
}

// Mavjud bo'lsa yangilaymiz
$bor = db_qiymat('SELECT id FROM push_obuna WHERE endpoint = ? AND foydalanuvchi_id = ?', [$endpoint, $f['id']]);
if ($bor) {
    db_bajar(
        'UPDATE push_obuna SET p256dh = ?, auth_key = ?, user_agent = ? WHERE id = ?',
        [$p256dh, $auth_key, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250), $bor]
    );
} else {
    db_bajar(
        'INSERT INTO push_obuna (foydalanuvchi_id, endpoint, p256dh, auth_key, user_agent) VALUES (?, ?, ?, ?, ?)',
        [$f['id'], $endpoint, $p256dh, $auth_key, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250)]
    );
}

json_javob(['ok' => true]);
