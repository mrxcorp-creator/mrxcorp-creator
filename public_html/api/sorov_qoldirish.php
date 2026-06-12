<?php
/**
 * VatanParvar Yaypan — So'rov qoldirish API
 * ------------------------------------------------------------
 * Aloqa sahifasidan kelgan formani saqlaydi va
 * admin Telegram'ga bildirishnoma yuboradi.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_javob(['ok' => false, 'xato' => 'POST kerak'], 405);
}
if (!csrf_tekshir(post('csrf_token'))) {
    json_javob(['ok' => false, 'xato' => t('csrf_xato')], 403);
}

// ----- Rate limit (5 daqiqada 3 ta so'rov) -----
$ip = ip_olish();
$son = (int) db_qiymat(
    'SELECT COUNT(*) FROM sorovlar WHERE ip = ? AND yaratilgan > DATE_SUB(NOW(), INTERVAL 5 MINUTE)',
    [$ip]
);
if ($son >= 3) {
    json_javob(['ok' => false, 'xato' => 'Juda ko\'p so\'rov. 5 daqiqadan so\'ng urinib ko\'ring'], 429);
}

// ----- Maydonlarni olish va validatsiya -----
$ism     = trim(post('ism'));
$telefon = telefon_tozala(post('telefon'));
$email   = trim(post('email'));
$mavzu   = trim(post('mavzu'));
$xabar   = trim(post('xabar'));

if (mb_strlen($ism) < 2) {
    json_javob(['ok' => false, 'xato' => 'Ismingizni kiriting'], 400);
}
if (!$telefon) {
    json_javob(['ok' => false, 'xato' => 'Telefon raqami noto\'g\'ri formatda'], 400);
}
if (mb_strlen($xabar) < 5) {
    json_javob(['ok' => false, 'xato' => 'Xabar juda qisqa (kamida 5 belgi)'], 400);
}
if (mb_strlen($xabar) > 2000) {
    json_javob(['ok' => false, 'xato' => 'Xabar juda uzun (maks 2000 belgi)'], 400);
}

// ----- Saqlash -----
$sorov_id = db_bajar(
    'INSERT INTO sorovlar (ism, telefon, email, mavzu, xabar, ip)
     VALUES (?, ?, ?, ?, ?, ?)',
    [$ism, $telefon, $email ?: null, $mavzu ?: null, $xabar, $ip]
);

// ----- Adminlarga Telegram bildirishnoma -----
$admin_id = sozlama('telegram_admin_id');
if ($admin_id) {
    $matn = "📩 <b>Yangi so'rov!</b>\n\n";
    $matn .= "👤 <b>" . htmlspecialchars($ism, ENT_QUOTES) . "</b>\n";
    $matn .= "📞 " . htmlspecialchars($telefon, ENT_QUOTES) . "\n";
    if ($email) $matn .= "📧 " . htmlspecialchars($email, ENT_QUOTES) . "\n";
    if ($mavzu) $matn .= "📝 <i>" . htmlspecialchars($mavzu, ENT_QUOTES) . "</i>\n";
    $matn .= "\n💬 " . htmlspecialchars(mb_substr($xabar, 0, 500), ENT_QUOTES);
    $matn .= "\n\n🔗 " . SAYT_URL . "/admin/sorovlar.php";
    telegram_yubor($admin_id, $matn);
}

json_javob([
    'ok' => true,
    'xabar' => 'So\'rovingiz qabul qilindi! Tez orada siz bilan bog\'lanamiz.',
    'sorov_id' => $sorov_id,
]);
