<?php
/**
 * AvtoTest Pro — Telegram bot webhook va xizmat sozlamalari
 * Faqat cron_kalit bilan kirishga ruxsat
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funksiyalar.php';

$kalit  = sozlama('cron_kalit', '');
$kelgan = $_GET['kalit'] ?? '';
if (!$kalit || !hash_equals($kalit, $kelgan)) {
    http_response_code(403);
    exit('Forbidden');
}

$token    = sozlama('telegram_bot_token', '');
$harakat  = $_GET['harakat'] ?? '';

if (!$token) {
    echo '❌ Telegram bot tokeni sozlanmagan. Admin paneldan kiriting.';
    exit;
}

$api = "https://api.telegram.org/bot{$token}";

switch ($harakat) {

    case 'webhook_set':
        $url  = SAYT_URL . '/bot.php';
        $resp = file_get_contents("{$api}/setWebhook?url=" . urlencode($url)
                                 . '&allowed_updates=["message","callback_query"]'
                                 . '&drop_pending_updates=true');
        echo "<b>Webhook o'rnatildi:</b><br>";
        echo "<code>{$url}</code><br><br>";
        echo "<pre>" . htmlspecialchars($resp) . "</pre>";
        break;

    case 'webhook_del':
        $resp = file_get_contents("{$api}/deleteWebhook?drop_pending_updates=true");
        echo "<b>Webhook o'chirildi:</b><br>";
        echo "<pre>" . htmlspecialchars($resp) . "</pre>";
        break;

    case 'webhook_info':
        $resp = file_get_contents("{$api}/getWebhookInfo");
        echo "<b>Webhook ma'lumoti:</b><br>";
        echo "<pre>" . htmlspecialchars($resp) . "</pre>";
        break;

    case 'me':
        $resp = file_get_contents("{$api}/getMe");
        echo "<b>Bot ma'lumoti:</b><br>";
        echo "<pre>" . htmlspecialchars($resp) . "</pre>";
        break;

    case 'kesh_tozala':
        kesh_tozala();
        echo "✅ Barcha kesh fayllar tozalandi.";
        break;

    default:
        echo "<h2>🤖 AvtoTest Pro — Bot sozlash</h2>";
        echo "<ul>";
        $amallar = [
            'webhook_set'  => "Webhookni o'rnatish",
            'webhook_del'  => "Webhookni o'chirish",
            'webhook_info' => "Webhook holati",
            'me'           => "Bot ma'lumoti",
            'kesh_tozala'  => "Keshni tozalash",
        ];
        foreach ($amallar as $a => $nom) {
            echo "<li><a href='?kalit={$kelgan}&harakat={$a}'>{$nom}</a></li>";
        }
        echo "</ul>";
        break;
}
