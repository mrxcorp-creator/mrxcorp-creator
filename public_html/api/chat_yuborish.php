<?php
/**
 * Chat xabar yuborish (foydalanuvchi tomonidan)
 *
 * Mantiq:
 *  - Xabar saqlanadi.
 *  - Agar oxirgi 30 daqiqada admin yozgan bo'lsa, AI'ni chaqirmaymiz
 *    (admin bilan suhbat davom etmoqda).
 *  - Aks holda Gemini AI'ga yuboramiz va javobini saqlaymiz.
 *  - Bir vaqtning o'zida adminlar uchun bildirishnoma chiqaradi.
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

$matn = trim(post('matn'));
if (mb_strlen($matn) < 1 || mb_strlen($matn) > 2000) {
    json_javob(['ok' => false, 'xato' => 'Xabar uzunligi 1–2000 belgi orasida bo\'lishi kerak'], 400);
}

// Rate limit: bir daqiqada 10 ta xabardan ko'p emas
$son_1m = (int) db_qiymat(
    'SELECT COUNT(*) FROM chat_xabarlar
     WHERE foydalanuvchi_id = ? AND kimdan = "user"
       AND yaratilgan > DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
    [$f['id']]
);
if ($son_1m >= 10) {
    json_javob(['ok' => false, 'xato' => 'Juda tez yuboryapsiz, biroz kuting'], 429);
}

// Foydalanuvchi xabarini saqlaymiz
$user_xabar_id = db_bajar(
    'INSERT INTO chat_xabarlar (foydalanuvchi_id, kimdan, matn, oqilgan)
     VALUES (?, "user", ?, 0)',
    [$f['id'], $matn]
);

// Admin bu suhbatda faolmi?
$admin_aktiv = chat_admin_aktivmi($f['id'], 30);

$ai_javob = null;
$ai_xabar_id = null;

if (!$admin_aktiv && AI_AKTIV) {
    // Suhbat tarixi (oxirgi 8 ta xabar — kontekst uchun)
    $tarix_db = db_barcha(
        'SELECT kimdan, matn FROM chat_xabarlar
         WHERE foydalanuvchi_id = ? AND id < ?
         ORDER BY id DESC LIMIT 8',
        [$f['id'], $user_xabar_id]
    );
    $tarix = array_reverse($tarix_db);

    // Foydalanuvchi konteksti (AI ko'proq tushunsin)
    $kontekst = "Foydalanuvchi nomi: " . $f['ism'];
    if (obuna_faolmi($f['id'])) {
        $kontekst .= "\nObuna holati: faol";
    } else {
        $kontekst .= "\nObuna holati: faol obuna yo'q (bepul foydalanuvchi)";
    }
    $kontekst .= "\n---\nFoydalanuvchining yangi xabari: " . $matn;

    $ai_javob = ai_javob_olish($kontekst, $tarix);

    if ($ai_javob) {
        $ai_xabar_id = db_bajar(
            'INSERT INTO chat_xabarlar (foydalanuvchi_id, kimdan, matn, oqilgan)
             VALUES (?, "ai", ?, 0)',
            [$f['id'], $ai_javob]
        );
    }
}

// Adminlarni xabardor qilish — birinchi xabar yoki ohirgi 1 soatda yangi
if (!$admin_aktiv) {
    $oxirgi_xabar = (int) db_qiymat(
        'SELECT COUNT(*) FROM chat_xabarlar
         WHERE foydalanuvchi_id = ? AND yaratilgan > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND id != ?',
        [$f['id'], $user_xabar_id]
    );
    if ($oxirgi_xabar === 0) {
        // Telegram orqali admin'ni xabardor qilish
        $admin_telegram = sozlama('telegram_admin_id');
        if ($admin_telegram) {
            $bot_matn = "💬 <b>Yangi chat xabar</b>\n\n";
            $bot_matn .= "👤 <b>" . htmlspecialchars($f['ism'] . ' ' . ($f['familiya'] ?? ''), ENT_QUOTES) . "</b>\n";
            $bot_matn .= "📞 " . htmlspecialchars($f['telefon'], ENT_QUOTES) . "\n\n";
            $bot_matn .= "💬 <i>" . htmlspecialchars(mb_substr($matn, 0, 300), ENT_QUOTES) . "</i>\n\n";
            $bot_matn .= "🔗 " . SAYT_URL . "/admin/chat.php?fid=" . $f['id'];
            telegram_yubor($admin_telegram, $bot_matn);
        }
    }
}

json_javob([
    'ok' => true,
    'user_xabar' => [
        'id' => $user_xabar_id,
        'kimdan' => 'user',
        'matn' => $matn,
        'vaqt' => date('H:i'),
    ],
    'ai_xabar' => $ai_javob ? [
        'id' => $ai_xabar_id,
        'kimdan' => 'ai',
        'matn' => $ai_javob,
        'vaqt' => date('H:i'),
    ] : null,
    'admin_aktiv' => $admin_aktiv,
]);
