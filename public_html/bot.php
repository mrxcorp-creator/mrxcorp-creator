<?php
/**
 * VatanParvar Yaypan — Telegram bot webhook
 *
 * SECURITY FIX: Telegram "X-Telegram-Bot-Api-Secret-Token" header tekshiriladi.
 * Admin paneli → Sozlamalar → telegram_webhook_secret kalitiga
 * tasodifiy string kiriting va Telegram webhook'ni qayta o'rnating.
 *
 * BUG FIX: /backup endi shell_exec orqali asinxron ishlaydi
 * (require_once backup.php edi, u exit bilan tugar va botga javob ketmas edi)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funksiyalar.php';

/* ── Telegram webhook secret token tekshiruvi ────────────── *
 * Sozlamasida bo'sh bo'lsa — eski usulda ishlaydi.           *
 * (xavfsizroq qilish uchun sozlamaga qiymat kiriting)         *
 * ─────────────────────────────────────────────────────────── */
$webhook_secret = sozlama('telegram_webhook_secret', '');
if ($webhook_secret !== '') {
    $gelen = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($webhook_secret, $gelen)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/* ── So'rovni o'qish ─────────────────────────────────────── */
$tana        = file_get_contents('php://input');
$yangilanish = json_decode($tana, true);

if (!$yangilanish || !is_array($yangilanish)) {
    http_response_code(200);
    exit('OK');
}

$message  = $yangilanish['message']          ?? $yangilanish['edited_message'] ?? null;
$callback = $yangilanish['callback_query']   ?? null;

if ($message)  { bot_xabar($message); }
elseif ($callback) { bot_callback($callback); }

http_response_code(200);
exit('OK');

/* ═══════════════════════════════════════════════════════════ */
function bot_xabar(array $msg): void
{
    $chat_id  = (int)   $msg['chat']['id'];
    $from_id  = (int)   $msg['from']['id'];
    $matn     = trim($msg['text'] ?? '');

    $admin_id = (int) sozlama('telegram_admin_id', 0);
    $is_admin = ($admin_id > 0 && $from_id === $admin_id);

    $foydalanuvchi = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE telegram_id = ?',
        [$from_id]
    );

    /* ── /start <hash> — akkauntni ulash ── */
    if (preg_match('/^\/start\s+([a-f0-9]{32})$/', $matn, $m)) {
        $fo = db_qator(
            'SELECT * FROM foydalanuvchilar WHERE telegram_hash = ?',
            [$m[1]]
        );
        if ($fo) {
            db_bajar(
                'UPDATE foydalanuvchilar SET telegram_id = ? WHERE id = ?',
                [$from_id, $fo['id']]
            );
            telegram_yubor($chat_id,
                "✅ <b>Akkauntingiz muvaffaqiyatli bog'landi!</b>\n\n"
                . "Salom, <b>" . htmlspecialchars($fo['ism'], ENT_QUOTES) . "</b>! 🎉\n"
                . "Endi to'lov va obuna bildirishnomalari shu yerga keladi.\n\n"
                . "🌐 Sayt: " . SAYT_URL
            );
        } else {
            telegram_yubor($chat_id,
                "❌ Noto'g'ri yoki muddati o'tgan havola.\n"
                . "Profilingizdan yangi havola oling: " . SAYT_URL . '/profil'
            );
        }
        return;
    }

    /* ── /start | /help ── */
    if ($matn === '/start' || $matn === '/help') {
        telegram_yubor($chat_id,
            "👋 <b>VatanParvar Yaypan botiga xush kelibsiz!</b>\n\n"
            . "Bu bot orqali siz:\n"
            . "• To'lov va obuna bildirishnomalarini olasiz\n"
            . "• Obuna tugashi haqida eslatma olasiz\n"
            . "• Parolni tiklashingiz mumkin\n\n"
            . "<b>Buyruqlar:</b>\n"
            . "/obuna — joriy obuna holati\n"
            . "/natijalar — oxirgi 5 test natijasi\n"
            . "/help — yordam\n\n"
            . "🌐 " . SAYT_URL
        );
        return;
    }

    /* ── /obuna ── */
    if ($matn === '/obuna') {
        if (!$foydalanuvchi) {
            telegram_yubor($chat_id,
                "❌ Akkauntingiz bog'lanmagan.\n"
                . "Bog'lash: " . SAYT_URL . '/profil'
            );
            return;
        }
        $ob = db_qator(
            'SELECT o.*, t.nomi FROM obunalar o
             JOIN tariflar t ON o.tarif_id = t.id
             WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
             ORDER BY o.tugash DESC LIMIT 1',
            [$foydalanuvchi['id']]
        );
        if ($ob) {
            $kun   = max(0, (int) ((strtotime($ob['tugash']) - time()) / 86400));
            $emoji = $kun <= 3 ? '🔴' : ($kun <= 7 ? '🟡' : '🟢');
            telegram_yubor($chat_id,
                "✅ <b>Faol obuna:</b> {$ob['nomi']}\n"
                . "📅 Tugash: " . date('d.m.Y', strtotime($ob['tugash'])) . "\n"
                . "⏳ Qolgan: {$emoji} <b>{$kun} kun</b>"
            );
        } else {
            telegram_yubor($chat_id,
                "❌ Faol obuna mavjud emas.\n"
                . "Sotib olish: " . SAYT_URL . '/tolov'
            );
        }
        return;
    }

    /* ── /natijalar ── */
    if ($matn === '/natijalar') {
        if (!$foydalanuvchi) {
            telegram_yubor($chat_id, "❌ Akkauntingiz bog'lanmagan.");
            return;
        }
        $royxat = db_barcha(
            'SELECT n.*, b.raqam, b.nomi FROM natijalar n
             JOIN biletlar b ON n.bilet_id = b.id
             WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
             ORDER BY n.tugagan DESC LIMIT 5',
            [$foydalanuvchi['id']]
        );
        if (!$royxat) {
            telegram_yubor($chat_id, "📊 Hali yechilgan testlar yo'q.");
            return;
        }
        $m = "<b>📊 Oxirgi natijalar:</b>\n\n";
        foreach ($royxat as $r) {
            $foiz  = $r['umumiy_son'] > 0
                ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
            $emoji = natija_emoji($foiz);
            $m    .= "{$emoji} №{$r['raqam']} — {$r['togri_son']}/{$r['umumiy_son']} ({$foiz}%)\n";
            $m    .= "   <i>" . date('d.m.Y H:i', strtotime($r['tugagan'])) . "</i>\n\n";
        }
        telegram_yubor($chat_id, $m);
        return;
    }

    /* ── ADMIN buyruqlari ── */
    if ($is_admin) {
        if ($matn === '/admin') {
            $token     = sozlama('telegram_bot_token', '');
            $klaviatura = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Statistika', 'callback_data' => 'stat'],
                        ['text' => '💾 Backup',      'callback_data' => 'backup'],
                    ],
                    [
                        ['text' => '🌐 Admin panelga', 'url' => SAYT_URL . '/admin/'],
                    ],
                ]
            ]);
            telegram_yubor($chat_id, "⚙️ <b>Admin paneli</b>\nKerakli amalni tanlang:",
                           ['reply_markup' => $klaviatura]);
            return;
        }
        if ($matn === '/stat') {
            telegram_yubor($chat_id, bot_stat_matni());
            return;
        }
        if ($matn === '/backup') {
            telegram_yubor($chat_id, "⏳ Backup boshlandi, bir oz kuting...");
            // ASINXRON: exit muammosini hal qiladi
            $php  = PHP_BINARY ?: '/usr/bin/php';
            $skript = ROOT_PATH . '/cron/backup.php';
            if (is_file($skript)) {
                shell_exec("{$php} " . escapeshellarg($skript) . " > /dev/null 2>&1 &");
            }
            return;
        }
    }

    telegram_yubor($chat_id, "❓ Buyruq tushunilmadi. /help yozing.");
}

/* ═══════════════════════════════════════════════════════════ */
function bot_callback(array $cb): void
{
    $admin_id = (int) sozlama('telegram_admin_id', 0);
    $from_id  = (int) $cb['from']['id'];
    $chat_id  = (int) $cb['message']['chat']['id'];
    $data     = $cb['data'] ?? '';
    $token    = sozlama('telegram_bot_token', '');

    // Callback javob (spinner'ni o'chirish)
    if ($token) {
        @file_get_contents(
            "https://api.telegram.org/bot{$token}/answerCallbackQuery"
            . "?callback_query_id=" . urlencode($cb['id'])
        );
    }

    if ($from_id !== $admin_id) return;

    if ($data === 'stat') {
        telegram_yubor($chat_id, bot_stat_matni());
    } elseif ($data === 'backup') {
        telegram_yubor($chat_id, "⏳ Backup boshlandi...");
        $php    = PHP_BINARY ?: '/usr/bin/php';
        $skript = ROOT_PATH . '/cron/backup.php';
        if (is_file($skript)) {
            shell_exec("{$php} " . escapeshellarg($skript) . " > /dev/null 2>&1 &");
        }
    }
}

/* ═══════════════════════════════════════════════════════════ */
function bot_stat_matni(): string
{
    $foyd     = (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
    $foyd_24  = (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $tolov    = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
    $tolov_24 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $obuna    = (int)   db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()');
    $test     = (int)   db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"');
    $kutilmoqda = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');

    return "<b>📊 VatanParvar Yaypan — Statistika</b>\n\n"
         . "👥 Foydalanuvchilar: <b>{$foyd}</b> (+{$foyd_24} bugun)\n"
         . "⭐ Faol obunalar: <b>{$obuna}</b>\n"
         . "💳 Jami daromad: <b>" . pul($tolov) . "</b>\n"
         . "💰 Bugungi: <b>" . pul($tolov_24) . "</b>\n"
         . ($kutilmoqda ? "⏳ Kutilmoqda: <b>{$kutilmoqda}</b>\n" : '')
         . "📝 Testlar: <b>{$test}</b>\n\n"
         . "<i>" . date('d.m.Y H:i') . "</i>";
}
