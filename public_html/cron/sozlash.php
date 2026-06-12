<?php
/**
 * Telegram bot vebhukini o'rnatish (faqat developer ishga tushiradi)
 *
 * URL: https://vatanparvaryaypan.uz/cron/sozlash.php?kalit=...
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$kalit = sozlama('cron_kalit', '');
$kelgan = $_GET['kalit'] ?? '';
if (!$kalit || !hash_equals($kalit, $kelgan)) {
    http_response_code(403);
    exit('Forbidden');
}

$harakat = $_GET['harakat'] ?? '';
$token = sozlama('telegram_bot_token');

if (!$token) exit('Telegram bot tokeni belgilanmagan');

// ----- Vebhukni o'rnatish -----
if ($harakat === 'webhook_set') {
    $url = SAYT_URL . '/bot.php';
    $j = file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url));
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

// ----- Vebhukni o'chirish -----
if ($harakat === 'webhook_del') {
    $j = file_get_contents("https://api.telegram.org/bot{$token}/deleteWebhook");
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

// ----- Bot info -----
if ($harakat === 'me') {
    $j = file_get_contents("https://api.telegram.org/bot{$token}/getMe");
    echo "<pre>" . htmlspecialchars($j) . "</pre>";
    exit;
}

echo "<h1>Bot sozlash</h1>";
echo "<ul>";
echo "<li><a href='?kalit={$kelgan}&harakat=webhook_set'>Vebhukni o'rnatish</a></li>";
echo "<li><a href='?kalit={$kelgan}&harakat=webhook_del'>Vebhukni o'chirish</a></li>";
echo "<li><a href='?kalit={$kelgan}&harakat=me'>Bot info</a></li>";
echo "</ul>";
