<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funksiyalar.php';

$tana = file_get_contents('php://input');
$yangilanish = json_decode($tana, true);

if (!$yangilanish) {
    http_response_code(200);
    exit('OK');
}

$message = $yangilanish['message'] ?? $yangilanish['edited_message'] ?? null;
$callback = $yangilanish['callback_query'] ?? null;

if ($message) {
    $chat_id = $message['chat']['id'];
    $matn    = trim($message['text'] ?? '');
    $from_id = $message['from']['id'];

    bot_xabar_qayta_ishla($chat_id, $matn, $from_id);
} elseif ($callback) {
    bot_callback($callback);
}

http_response_code(200);
exit('OK');

function bot_xabar_qayta_ishla(int $chat_id, string $matn, int $from_id): void {
    $admin_id = (int) sozlama('telegram_admin_id');
    $is_admin = $admin_id && $from_id === $admin_id;

    $foydalanuvchi = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE telegram_id = ?',
        [$from_id]
    );

    if (preg_match('/^\/start\s+([a-f0-9]{32})/', $matn, $m)) {
        $hash = $m[1];
        $f = db_qator('SELECT * FROM foydalanuvchilar WHERE telegram_hash = ?', [$hash]);
        if ($f) {
            db_bajar('UPDATE foydalanuvchilar SET telegram_id = ? WHERE id = ?', [$from_id, $f['id']]);
            telegram_yubor($chat_id,
                "✅ <b>Akkauntingiz bog'landi!</b>\n\nSalom, <b>" . e_bot($f['ism']) . "</b>!\n" .
                "Endi siz to'lov, obuna tugashi va boshqa bildirishnomalarni shu yerda olasiz."
            );
        } else {
            telegram_yubor($chat_id, "❌ Noto'g'ri yoki muddati o'tgan havola.");
        }
        return;
    }

    if ($matn === '/start' || $matn === '/help') {
        $matn_javob = "<b>👋 VatanParvar Yaypan botiga xush kelibsiz!</b>\n\n" .
            "Bu bot orqali siz:\n" .
            "• To'lov bildirishnomalarini olasiz\n" .
            "• Obuna tugashi haqida xabar olasiz\n" .
            "• Parolni qayta tiklashingiz mumkin\n\n" .
            "<b>Buyruqlar:</b>\n" .
            "/obuna — obuna holati\n" .
            "/natijalar — oxirgi natijalar\n" .
            "/help — yordam\n\n" .
            "Saytga o'tish: " . SAYT_URL;
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($matn === '/obuna') {
        if (!$foydalanuvchi) {
            telegram_yubor($chat_id, "❌ Akkauntingiz hali bog'lanmagan.\nSaytga kirib, profil sahifasidagi ulanish havolasini ishlating.");
            return;
        }
        $obuna = db_qator(
            'SELECT o.*, t.nomi FROM obunalar o JOIN tariflar t ON o.tarif_id = t.id
             WHERE o.foydalanuvchi_id = ? AND o.holat = "faol" AND o.tugash > NOW()
             ORDER BY o.tugash DESC LIMIT 1',
            [$foydalanuvchi['id']]
        );
        if ($obuna) {
            $kun_qoldi = (int) ((strtotime($obuna['tugash']) - time()) / 86400);
            telegram_yubor($chat_id,
                "✅ <b>Faol obuna:</b> {$obuna['nomi']}\n" .
                "📅 Tugash: " . date('d.m.Y', strtotime($obuna['tugash'])) . "\n" .
                "⏳ Qolgan: <b>{$kun_qoldi} kun</b>"
            );
        } else {
            telegram_yubor($chat_id,
                "❌ Faol obuna mavjud emas.\nSotib olish uchun: " . SAYT_URL . "/tolov"
            );
        }
        return;
    }

    if ($matn === '/natijalar') {
        if (!$foydalanuvchi) {
            telegram_yubor($chat_id, "❌ Akkauntingiz bog'lanmagan");
            return;
        }
        $natijalar = db_barcha(
            'SELECT n.*, b.raqam, b.nomi FROM natijalar n
             JOIN biletlar b ON n.bilet_id = b.id
             WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
             ORDER BY n.tugagan DESC LIMIT 5',
            [$foydalanuvchi['id']]
        );
        if (!$natijalar) {
            telegram_yubor($chat_id, "📊 Hali yechilgan testlar yo'q.");
            return;
        }
        $matn_javob = "<b>📊 Oxirgi natijalar:</b>\n\n";
        foreach ($natijalar as $r) {
            $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
            $emoji = $foiz >= 90 ? '🟢' : ($foiz >= 70 ? '🔵' : ($foiz >= 50 ? '🟡' : '🔴'));
            $matn_javob .= "{$emoji} №{$r['raqam']} — {$r['togri_son']}/{$r['umumiy_son']} ({$foiz}%)\n";
            $matn_javob .= "   <i>" . date('d.m.Y H:i', strtotime($r['tugagan'])) . "</i>\n\n";
        }
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($is_admin) {
        if ($matn === '/admin') {
            $klaviatura = [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Statistika', 'callback_data' => 'stat'],
                        ['text' => '💾 Backup',    'callback_data' => 'backup'],
                    ],
                    [
                        ['text' => '🌐 Saytga o\'tish', 'url' => SAYT_URL . '/admin/'],
                    ]
                ]
            ];
            telegram_yubor($chat_id, "<b>⚙️ Admin paneli</b>\nKerakli amalni tanlang:", [
                'reply_markup' => json_encode($klaviatura),
            ]);
            return;
        }
        if ($matn === '/stat') {
            telegram_yubor($chat_id, bot_stat_matni());
            return;
        }
        if ($matn === '/backup') {
            telegram_yubor($chat_id, "⏳ Backup yaratilmoqda...");
            require_once __DIR__ . '/cron/backup.php';
            return;
        }
    }

    telegram_yubor($chat_id, "❓ Buyruq tushunilmadi. /help yozing.");
}

function bot_callback(array $cb): void {
    $admin_id = (int) sozlama('telegram_admin_id');
    $from_id  = $cb['from']['id'];
    $chat_id  = $cb['message']['chat']['id'];
    $data     = $cb['data'] ?? '';
    $cb_id    = $cb['id'];
    $token    = sozlama('telegram_bot_token');

    @file_get_contents("https://api.telegram.org/bot{$token}/answerCallbackQuery?callback_query_id={$cb_id}");

    if ($from_id !== $admin_id) return;

    if ($data === 'stat') {
        telegram_yubor($chat_id, bot_stat_matni());
    } elseif ($data === 'backup') {
        telegram_yubor($chat_id, "⏳ Backup yaratilmoqda...");
        require_once __DIR__ . '/cron/backup.php';
    }
}

function bot_stat_matni(): string {
    $foyd = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
    $foyd_24 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $tolov = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
    $tolov_24 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $obuna = (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()');
    $test = (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"');

    return "<b>📊 Sayt statistikasi</b>\n\n" .
           "👥 Foydalanuvchilar: <b>{$foyd}</b> (+{$foyd_24} bugun)\n" .
           "💎 Faol obunalar: <b>{$obuna}</b>\n" .
           "💳 Daromad: <b>" . pul($tolov) . "</b>\n" .
           "💰 Bugungi: <b>" . pul($tolov_24) . "</b>\n" .
           "📝 Yechilgan testlar: <b>{$test}</b>\n\n" .
           "<i>" . date('d.m.Y H:i') . "</i>";
}

function e_bot(string $matn): string {
    return htmlspecialchars($matn, ENT_QUOTES, 'UTF-8');
}
