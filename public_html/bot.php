<?php
/**
 * AvtoTest Pro — Telegram bot webhook
 * BUG FIX: backup endi shell_exec orqali asinxron ishlatiladi
 * (require_once backup.php exit bilan tugashi muammoni hal qiladi)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funksiyalar.php';

$tana       = file_get_contents('php://input');
$yangilanish = json_decode($tana, true);

if (!$yangilanish) {
    http_response_code(200);
    exit('OK');
}

$message  = $yangilanish['message'] ?? $yangilanish['edited_message'] ?? null;
$callback = $yangilanish['callback_query'] ?? null;

if ($message) {
    $chat_id = (int) $message['chat']['id'];
    $matn    = trim($message['text'] ?? '');
    $from_id = (int) $message['from']['id'];
    bot_xabar_qayta_ishla($chat_id, $matn, $from_id);
} elseif ($callback) {
    bot_callback($callback);
}

http_response_code(200);
exit('OK');

// ============================================================
function bot_xabar_qayta_ishla(int $chat_id, string $matn, int $from_id): void
{
    $admin_id = (int) sozlama('telegram_admin_id', 0);
    $is_admin = $admin_id && $from_id === $admin_id;

    $foydalanuvchi = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE telegram_id = ?',
        [$from_id]
    );

    // /start <hash>
    if (preg_match('/^\/start\s+([a-f0-9]{32})$/', $matn, $m)) {
        $hash = $m[1];
        $fo   = db_qator('SELECT * FROM foydalanuvchilar WHERE telegram_hash = ?', [$hash]);
        if ($fo) {
            db_bajar('UPDATE foydalanuvchilar SET telegram_id = ? WHERE id = ?', [$from_id, $fo['id']]);
            telegram_yubor($chat_id,
                "✅ <b>Akkauntingiz muvaffaqiyatli bog'landi!</b>\n\n"
                . "Salom, <b>" . htmlspecialchars($fo['ism'], ENT_QUOTES) . "</b>! 🎉\n"
                . "Endi to'lov va obuna bildirishnomalari shu yerga keladi.\n\n"
                . "📱 Saytga o'tish: " . SAYT_URL
            );
        } else {
            telegram_yubor($chat_id, "❌ Noto'g'ri yoki muddati o'tgan havola.\nQayta urinish uchun saytdan profil sahifasiga kiring.");
        }
        return;
    }

    // /start yoki /help
    if ($matn === '/start' || $matn === '/help') {
        telegram_yubor($chat_id,
            "👋 <b>AvtoTest Pro botiga xush kelibsiz!</b>\n\n"
            . "Bu bot sizga:\n"
            . "• To'lov bildirishnomalarini yetkazadi\n"
            . "• Obuna tugashi haqida ogohlantiradi\n"
            . "• Parolni tiklashga yordam beradi\n\n"
            . "<b>Buyruqlar:</b>\n"
            . "/obuna — obuna holati\n"
            . "/natijalar — oxirgi test natijalari\n"
            . "/help — yordam\n\n"
            . "🌐 Sayt: " . SAYT_URL
        );
        return;
    }

    // /obuna
    if ($matn === '/obuna') {
        if (!$foydalanuvchi) {
            telegram_yubor($chat_id,
                "❌ Akkauntingiz hali bog'lanmagan.\n"
                . "Saytga kirib, profil sahifasidan bog'lang: " . SAYT_URL . "/profil"
            );
            return;
        }
        $obuna = db_qator(
            'SELECT o.*, t.nomi FROM obunalar o
             JOIN tariflar t ON o.tarif_id = t.id
             WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
             ORDER BY o.tugash DESC LIMIT 1',
            [$foydalanuvchi['id']]
        );
        if ($obuna) {
            $kun = max(0, (int)((strtotime($obuna['tugash']) - time()) / 86400));
            $rang = $kun <= 3 ? '🔴' : ($kun <= 7 ? '🟡' : '🟢');
            telegram_yubor($chat_id,
                "✅ <b>Faol obuna:</b> {$obuna['nomi']}\n"
                . "📅 Tugash: " . date('d.m.Y', strtotime($obuna['tugash'])) . "\n"
                . "⏳ Qolgan: {$rang} <b>{$kun} kun</b>"
            );
        } else {
            telegram_yubor($chat_id,
                "❌ Faol obuna mavjud emas.\n"
                . "Sotib olish: " . SAYT_URL . "/tolov"
            );
        }
        return;
    }

    // /natijalar
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
            $foiz  = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
            $emoji = natija_emoji($foiz);
            $m    .= "{$emoji} №{$r['raqam']} — {$r['togri_son']}/{$r['umumiy_son']} ({$foiz}%)\n";
            $m    .= "   <i>" . date('d.m.Y H:i', strtotime($r['tugagan'])) . "</i>\n\n";
        }
        telegram_yubor($chat_id, $m);
        return;
    }

    // ADMIN buyruqlari
    if ($is_admin) {
        if ($matn === '/admin') {
            $token = sozlama('telegram_bot_token', '');
            $klaviatura = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Statistika', 'callback_data' => 'stat'],
                        ['text' => '💾 Backup',      'callback_data' => 'backup'],
                    ],
                    [['text' => '🌐 Admin panelga', 'url' => SAYT_URL . '/admin/']],
                ]
            ]);
            telegram_yubor($chat_id, "⚙️ <b>Admin paneli</b>", ['reply_markup' => $klaviatura]);
            return;
        }
        if ($matn === '/stat') {
            telegram_yubor($chat_id, bot_stat_matni());
            return;
        }
        if ($matn === '/backup') {
            telegram_yubor($chat_id, "⏳ Backup boshlandi, bir oz kuting...");
            // BUG FIX: asinxron ishlatish (exit muammosidan xalos bo'lish)
            $php = PHP_BINARY ?: '/usr/bin/php';
            $fayl = ROOT_PATH . '/cron/backup.php';
            if (is_file($fayl)) {
                shell_exec("{$php} {$fayl} > /dev/null 2>&1 &");
            }
            return;
        }
    }

    // Noma'lum buyruq
    telegram_yubor($chat_id, "❓ Buyruq tushunilmadi. /help yozing.");
}

// ============================================================
function bot_callback(array $cb): void
{
    $admin_id = (int) sozlama('telegram_admin_id', 0);
    $from_id  = (int) $cb['from']['id'];
    $chat_id  = (int) $cb['message']['chat']['id'];
    $data     = $cb['data'] ?? '';
    $cb_id    = $cb['id'];
    $token    = sozlama('telegram_bot_token', '');

    // Callback javobi (loading indikatorini to'xtatish)
    if ($token) {
        @file_get_contents("https://api.telegram.org/bot{$token}/answerCallbackQuery?callback_query_id={$cb_id}");
    }

    if ($from_id !== $admin_id) return;

    if ($data === 'stat') {
        telegram_yubor($chat_id, bot_stat_matni());
    } elseif ($data === 'backup') {
        telegram_yubor($chat_id, "⏳ Backup boshlandi...");
        // BUG FIX: asinxron ishlatish
        $php  = PHP_BINARY ?: '/usr/bin/php';
        $fayl = ROOT_PATH . '/cron/backup.php';
        if (is_file($fayl)) {
            shell_exec("{$php} {$fayl} > /dev/null 2>&1 &");
        }
    }
}

// ============================================================
function bot_stat_matni(): string
{
    $foyd    = (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
    $foyd_24 = (int)   db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $tolov   = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
    $tolov_24= (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $obuna   = (int)   db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()');
    $test    = (int)   db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"');
    $kutilmoqda = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');

    return "<b>📊 AvtoTest Pro — Statistika</b>\n\n"
         . "👥 Foydalanuvchilar: <b>{$foyd}</b> (+{$foyd_24} bugun)\n"
         . "⭐ Faol obunalar: <b>{$obuna}</b>\n"
         . "💳 Jami daromad: <b>" . pul($tolov) . "</b>\n"
         . "💰 Bugungi: <b>" . pul($tolov_24) . "</b>\n"
         . ($kutilmoqda ? "⏳ Kutilmoqda: <b>{$kutilmoqda}</b>\n" : '')
         . "📝 Testlar: <b>{$test}</b>\n\n"
         . "<i>" . date('d.m.Y H:i') . "</i>";
}
