<?php
/**
 * VatanParvar Yaypan — Telegram bot vebhuk (KENGAYTIRILGAN)
 *
 * Imkoniyatlar:
 *   /start <hash>    — akkauntni botga ulash
 *   /start ref_KOD   — referal orqali kelish
 *   /help            — yordam
 *   /obuna           — obuna holati
 *   /natijalar       — oxirgi 5 natija
 *   /yutuqlar        — mening yutuqlarim
 *   /reyting         — top 10 reyting
 *   /imtihon         — imtihon haqida (link)
 *   /tariflar        — tariflar ro'yxati
 *   /aloqa           — aloqa qilish (so'rov yuborish)
 *
 * Admin uchun:
 *   /admin           — admin panel (inline tugmalar)
 *   /stat            — statistika
 *   /backup          — DB zaxiralash
 *   /xabar <text>    — barcha foydalanuvchilarga ommaviy xabar
 *   /xabar_obuna <text> — faol obunadagilarga
 */
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
    $from_name = trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));

    bot_xabar_qayta_ishla($chat_id, $matn, $from_id, $from_name);
} elseif ($callback) {
    bot_callback($callback);
}

http_response_code(200);
exit('OK');

// ============================================================
// ASOSIY HANDLER
// ============================================================
function bot_xabar_qayta_ishla(int $chat_id, string $matn, int $from_id, string $from_name): void {
    $admin_id = (int) sozlama('telegram_admin_id');
    $is_admin = $admin_id && $from_id === $admin_id;

    $foydalanuvchi = db_qator(
        'SELECT * FROM foydalanuvchilar WHERE telegram_id = ?',
        [$from_id]
    );

    // ----- /start <hash> — akkaunt ulash -----
    if (preg_match('/^\/start\s+([a-f0-9]{32})/', $matn, $m)) {
        $hash = $m[1];
        $f = db_qator('SELECT * FROM foydalanuvchilar WHERE telegram_hash = ?', [$hash]);
        if ($f) {
            db_bajar('UPDATE foydalanuvchilar SET telegram_id = ? WHERE id = ?', [$from_id, $f['id']]);
            telegram_yubor($chat_id, bot_xush_kelibsiz_matn($f['ism']));
        } else {
            telegram_yubor($chat_id, "❌ Noto'g'ri yoki muddati o'tgan havola.");
        }
        return;
    }

    // ----- /start ref_KOD — referal orqali kelish -----
    if (preg_match('/^\/start\s+ref_([A-Z0-9]+)/i', $matn, $m)) {
        $kod = strtoupper($m[1]);
        $referer = db_qator('SELECT id, ism FROM foydalanuvchilar WHERE referal_kod = ?', [$kod]);
        if ($referer) {
            $havola = SAYT_URL . '/register?ref=' . $kod;
            telegram_yubor($chat_id,
                "🎁 <b>Salom, " . htmlspecialchars($from_name, ENT_QUOTES) . "!</b>\n\n" .
                htmlspecialchars($referer['ism'], ENT_QUOTES) . " sizni VatanParvar Yaypan platformasiga taklif qildi!\n\n" .
                "📚 Bizning sayt — avto maktab nazariyasi imtihoniga eng yaxshi tayyorgarlik.\n\n" .
                "Ro'yxatdan o'tish uchun:\n{$havola}");
        } else {
            telegram_yubor($chat_id, bot_help_matn());
        }
        return;
    }

    // ----- /start (oddiy) -----
    if ($matn === '/start' || $matn === '/help') {
        telegram_yubor($chat_id, bot_help_matn());
        return;
    }

    // ============================================================
    // FOYDALANUVCHI BUYRUQLARI
    // ============================================================
    if (!$foydalanuvchi) {
        telegram_yubor($chat_id, "❌ Akkauntingiz hali bog'lanmagan.\n\nIltimos, " . SAYT_URL . " saytiga kiring va profil sahifasidagi ulanish havolasini ishlating.");
        return;
    }

    if ($matn === '/obuna') {
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
                "⏳ Qolgan: <b>{$kun_qoldi} kun</b>");
        } else {
            telegram_yubor($chat_id, "❌ Faol obuna mavjud emas.\n\n💎 Sotib olish: " . SAYT_URL . "/tariflar");
        }
        return;
    }

    if ($matn === '/natijalar') {
        $natijalar = db_barcha(
            'SELECT n.*, b.raqam FROM natijalar n
             LEFT JOIN biletlar b ON n.bilet_id = b.id
             WHERE n.foydalanuvchi_id = ? AND n.holat = "tugagan"
             ORDER BY n.tugagan DESC LIMIT 5',
            [$foydalanuvchi['id']]
        );
        if (!$natijalar) {
            telegram_yubor($chat_id, "📊 Hali yechilgan testlar yo'q.\n\n📝 Boshlash: " . SAYT_URL . "/test");
            return;
        }
        $matn_javob = "<b>📊 Oxirgi 5 ta natija:</b>\n\n";
        foreach ($natijalar as $r) {
            $foiz = $r['umumiy_son'] > 0 ? round($r['togri_son'] / $r['umumiy_son'] * 100) : 0;
            $emoji = $foiz >= 90 ? '🟢' : ($foiz >= 70 ? '🔵' : ($foiz >= 50 ? '🟡' : '🔴'));
            $tur = ($r['tur'] ?? 'mashq') === 'imtihon' ? '🎓' : '📝';
            $matn_javob .= "{$emoji} {$tur} ";
            $matn_javob .= $r['raqam'] ? "№{$r['raqam']} — " : '';
            $matn_javob .= "{$r['togri_son']}/{$r['umumiy_son']} ({$foiz}%)\n";
            $matn_javob .= "   <i>" . date('d.m.Y H:i', strtotime($r['tugagan'])) . "</i>\n\n";
        }
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($matn === '/yutuqlar') {
        $jami_yut = (int) db_qiymat('SELECT COUNT(*) FROM yutuqlar');
        $olingan = db_barcha(
            'SELECT y.* FROM yutuqlar y
             JOIN foydalanuvchi_yutuqlar fy ON y.id = fy.yutuq_id
             WHERE fy.foydalanuvchi_id = ? ORDER BY fy.olingan DESC',
            [$foydalanuvchi['id']]
        );
        $xp = foydalanuvchi_xp($foydalanuvchi['id']);
        $matn_javob = "<b>🏅 Sizning yutuqlaringiz</b>\n\n";
        $matn_javob .= "⚡ XP: <b>{$xp}</b>\n";
        $matn_javob .= "🎖 Olingan: <b>" . count($olingan) . "</b> / {$jami_yut}\n\n";
        if (empty($olingan)) {
            $matn_javob .= "<i>Hali yutuq olmagansiz. Test ishlash bilan boshlang!</i>";
        } else {
            foreach (array_slice($olingan, 0, 10) as $y) {
                $matn_javob .= "{$y['ikon']} <b>" . htmlspecialchars($y['nomi'], ENT_QUOTES) . "</b>\n";
                $matn_javob .= "   <i>" . htmlspecialchars($y['tavsif'], ENT_QUOTES) . "</i>\n\n";
            }
        }
        $matn_javob .= "\n🔗 Hammasi: " . SAYT_URL . "/profil#yutuqlar";
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($matn === '/reyting') {
        $top = db_barcha(
            "SELECT fo.ism, fo.familiya, SUM(n.togri_son) AS togri, COUNT(*) AS test_son
             FROM natijalar n JOIN foydalanuvchilar fo ON n.foydalanuvchi_id = fo.id
             WHERE n.holat = 'tugagan' AND n.tugagan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY fo.id ORDER BY togri DESC LIMIT 10"
        );
        $matn_javob = "<b>🏆 Haftalik reyting (Top 10)</b>\n\n";
        foreach ($top as $i => $t) {
            $emoji = ['🥇','🥈','🥉'][$i] ?? '   ';
            $matn_javob .= "{$emoji} #" . ($i+1) . ". <b>" . htmlspecialchars(trim($t['ism'] . ' ' . ($t['familiya'] ?? '')), ENT_QUOTES) . "</b>\n";
            $matn_javob .= "       ✓ " . (int)$t['togri'] . " · 📝 " . (int)$t['test_son'] . " test\n\n";
        }
        if (empty($top)) {
            $matn_javob .= "<i>Hozircha bu hafta natija yo'q.</i>";
        }
        $matn_javob .= "\n🔗 Hammasi: " . SAYT_URL . "/reyting";
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($matn === '/imtihon') {
        telegram_yubor($chat_id,
            "<b>🎓 Real Imtihon Simulyatori</b>\n\n" .
            "📝 20 ta savol\n⏱️ 25 daqiqa\n⚠️ Maks 2 ta xato\n\n" .
            "Aniq YHXBB imtihoni qoidalari bilan o'zingizni sinab ko'ring va tayyorlik darajangizni bilib oling!\n\n" .
            "🚀 " . SAYT_URL . "/imtihon");
        return;
    }

    if ($matn === '/tariflar') {
        $tariflar = db_barcha('SELECT * FROM tariflar WHERE holat = "faol" ORDER BY tartib, narx LIMIT 10');
        $matn_javob = "<b>💎 Bizning tariflar</b>\n\n";
        foreach ($tariflar as $tar) {
            $matn_javob .= "▫️ <b>" . htmlspecialchars($tar['nomi'], ENT_QUOTES) . "</b>";
            if ($tar['mashhur']) $matn_javob .= " ⭐";
            $matn_javob .= "\n   💰 " . pul($tar['narx']);
            if ($tar['eski_narx'] && $tar['eski_narx'] > $tar['narx']) {
                $matn_javob .= " <s>" . pul($tar['eski_narx']) . "</s>";
            }
            $matn_javob .= "\n\n";
        }
        $matn_javob .= "🛒 Sotib olish: " . SAYT_URL . "/tariflar";
        telegram_yubor($chat_id, $matn_javob);
        return;
    }

    if ($matn === '/aloqa' || $matn === '/admin_chat') {
        telegram_yubor($chat_id,
            "💬 <b>Admin bilan bog'lanish</b>\n\n" .
            "Saytimizdagi chat orqali yozing — AI yoki admin javob beradi.\n\n" .
            "🔗 " . SAYT_URL . "/chat\n\n" .
            "Yoki shaxsiy savolingizni shu yerga yozing — admin'ga yetkazib beraman.");
        return;
    }

    // ============================================================
    // ADMIN BUYRUQLARI
    // ============================================================
    if ($is_admin) {
        if ($matn === '/admin') {
            $klaviatura = ['inline_keyboard' => [
                [
                    ['text' => '📊 Statistika', 'callback_data' => 'stat'],
                    ['text' => '💾 Backup', 'callback_data' => 'backup'],
                ],
                [
                    ['text' => '📩 Yangi so\'rovlar', 'callback_data' => 'sorovlar'],
                    ['text' => '💳 Kutilayotgan to\'lovlar', 'callback_data' => 'tolovlar'],
                ],
                [
                    ['text' => '🌐 Saytga o\'tish', 'url' => SAYT_URL . '/admin/'],
                ]
            ]];
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

        // /xabar yoki /xabar_obuna — ommaviy xabar
        if (preg_match('/^\/xabar(_obuna)?\s+(.+)/s', $matn, $m)) {
            $faqat_obuna = !empty($m[1]);
            $broadcast_matn = trim($m[2]);

            if ($faqat_obuna) {
                $oluvchilar = db_barcha(
                    'SELECT DISTINCT fo.id, fo.telegram_id FROM foydalanuvchilar fo
                     JOIN obunalar o ON o.foydalanuvchi_id = fo.id
                     WHERE fo.telegram_id IS NOT NULL AND fo.holat = "faol"
                       AND o.holat = "faol" AND o.tugash > NOW()'
                );
            } else {
                $oluvchilar = db_barcha(
                    'SELECT id, telegram_id FROM foydalanuvchilar
                     WHERE telegram_id IS NOT NULL AND holat = "faol"'
                );
            }
            $jami = count($oluvchilar);
            telegram_yubor($chat_id, "⏳ <b>{$jami}</b> ta foydalanuvchiga yuborilmoqda...");

            $yetkazildi = 0;
            foreach ($oluvchilar as $o) {
                if (telegram_yubor($o['telegram_id'], $broadcast_matn)) {
                    $yetkazildi++;
                }
                usleep(50000); // 50ms — flood control
            }

            telegram_yubor($chat_id, "✅ Yuborildi: <b>{$yetkazildi}</b> / {$jami}");
            return;
        }
    }

    // ----- Hech qaysi buyruqqa to'g'ri kelmadi: foydalanuvchi xabarini chatga yuborish -----
    if (!$is_admin && $foydalanuvchi) {
        // Foydalanuvchining "ochiq" suhbati sifatida saqlash
        $xabar_id = db_bajar(
            'INSERT INTO chat_xabarlar (foydalanuvchi_id, kimdan, matn, oqilgan)
             VALUES (?, "user", ?, 0)',
            [$foydalanuvchi['id'], $matn]
        );

        // Adminga bildirish
        $admin_tg = sozlama('telegram_admin_id');
        if ($admin_tg && $admin_tg !== (string) $from_id) {
            $bot_matn = "💬 <b>Telegram orqali xabar</b>\n\n";
            $bot_matn .= "👤 " . htmlspecialchars($foydalanuvchi['ism'], ENT_QUOTES) . "\n";
            $bot_matn .= "📞 " . htmlspecialchars($foydalanuvchi['telefon'], ENT_QUOTES) . "\n\n";
            $bot_matn .= "💬 " . htmlspecialchars(mb_substr($matn, 0, 500), ENT_QUOTES) . "\n\n";
            $bot_matn .= "🔗 Javob: " . SAYT_URL . "/admin/chat.php?fid=" . $foydalanuvchi['id'];
            telegram_yubor($admin_tg, $bot_matn);
        }

        telegram_yubor($chat_id, "✅ Xabaringiz adminimizga yetkazildi.\n\nJavobni Telegram'da yoki saytda olasiz: " . SAYT_URL . "/chat");
        return;
    }

    telegram_yubor($chat_id, "❓ Buyruq tushunilmadi. /help yozing.");
}

// ============================================================
// CALLBACK QUERY (inline tugmalar)
// ============================================================
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
    } elseif ($data === 'sorovlar') {
        $son = (int) db_qiymat('SELECT COUNT(*) FROM sorovlar WHERE holat = "yangi"');
        $ruyhat = db_barcha('SELECT * FROM sorovlar WHERE holat = "yangi" ORDER BY yaratilgan DESC LIMIT 5');
        $matn = "📩 <b>Yangi so'rovlar: {$son}</b>\n\n";
        foreach ($ruyhat as $s) {
            $matn .= "• <b>" . htmlspecialchars($s['ism'], ENT_QUOTES) . "</b> · " . htmlspecialchars($s['telefon'], ENT_QUOTES) . "\n";
            $matn .= "  <i>" . htmlspecialchars(mb_substr($s['xabar'], 0, 80), ENT_QUOTES) . "</i>\n\n";
        }
        $matn .= "🔗 " . SAYT_URL . "/admin/sorovlar.php";
        telegram_yubor($chat_id, $matn);
    } elseif ($data === 'tolovlar') {
        $son = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');
        $matn = "💳 <b>Kutilayotgan to'lovlar: {$son}</b>\n\n🔗 " . SAYT_URL . "/admin/tolovlar.php?holat=kutilmoqda";
        telegram_yubor($chat_id, $matn);
    }
}

// ============================================================
// MATN TAYYORLOVCHI YORDAMCHILAR
// ============================================================
function bot_help_matn(): string {
    return "<b>👋 VatanParvar Yaypan botiga xush kelibsiz!</b>\n\n" .
        "Bu bot orqali siz:\n" .
        "💬 Admin bilan yozishasiz\n" .
        "🔔 Bildirishnomalar olasiz\n" .
        "📊 Natijalarni tekshirasiz\n\n" .
        "<b>Asosiy buyruqlar:</b>\n" .
        "/obuna — obuna holati\n" .
        "/natijalar — oxirgi natijalar\n" .
        "/yutuqlar — mening yutuqlarim 🏅\n" .
        "/reyting — haftalik reyting 🏆\n" .
        "/imtihon — imtihon haqida 🎓\n" .
        "/tariflar — narxlar 💎\n" .
        "/aloqa — admin bilan suhbat 💬\n\n" .
        "🌐 " . SAYT_URL;
}

function bot_xush_kelibsiz_matn(string $ism): string {
    return "✅ <b>Akkauntingiz bog'landi!</b>\n\n" .
        "Salom, <b>" . htmlspecialchars($ism, ENT_QUOTES) . "</b>! 👋\n\n" .
        "Endi siz to'lov, obuna tugashi va admin xabarlarini shu yerda olasiz.\n\n" .
        "📋 Buyruqlarni ko'rish: /help";
}

function bot_stat_matni(): string {
    $foyd = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar');
    $foyd_24 = (int) db_qiymat('SELECT COUNT(*) FROM foydalanuvchilar WHERE yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $tolov = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli"');
    $tolov_24 = (float) db_qiymat('SELECT COALESCE(SUM(summa),0) FROM tolovlar WHERE holat = "muvaffaqiyatli" AND yaratilgan > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $obuna = (int) db_qiymat('SELECT COUNT(*) FROM obunalar WHERE holat = "faol" AND tugash > NOW()');
    $test = (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE holat = "tugagan"');
    $imtihon_pass = (int) db_qiymat('SELECT COUNT(*) FROM natijalar WHERE tur = "imtihon" AND otdimi = 1');
    $kutayotgan_sorov = (int) db_qiymat('SELECT COUNT(*) FROM sorovlar WHERE holat = "yangi"');
    $kutayotgan_tolov = (int) db_qiymat('SELECT COUNT(*) FROM tolovlar WHERE holat = "kutilmoqda"');

    return "<b>📊 Sayt statistikasi</b>\n\n" .
           "👥 Foydalanuvchilar: <b>{$foyd}</b> (+{$foyd_24} bugun)\n" .
           "💎 Faol obunalar: <b>{$obuna}</b>\n" .
           "💳 Daromad: <b>" . pul($tolov) . "</b>\n" .
           "💰 Bugungi: <b>" . pul($tolov_24) . "</b>\n" .
           "📝 Yechilgan testlar: <b>{$test}</b>\n" .
           "🎓 Imtihondan o'tgan: <b>{$imtihon_pass}</b>\n\n" .
           ($kutayotgan_sorov > 0 ? "⚠️ Yangi so'rovlar: <b>{$kutayotgan_sorov}</b>\n" : '') .
           ($kutayotgan_tolov > 0 ? "⚠️ Kutilayotgan to'lovlar: <b>{$kutayotgan_tolov}</b>\n" : '') .
           "\n<i>" . date('d.m.Y H:i') . "</i>";
}
