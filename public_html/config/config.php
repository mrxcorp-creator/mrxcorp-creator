<?php
/**
 * VatanParvar Yaypan — Global konstantalar
 * ------------------------------------------------------------
 * Barcha global sozlamalar shu yerda joylashadi.
 */

// Ishga tushirish rejimi: 'production' yoki 'development'
define('REJIM', 'production');

// Sayt asosiy URL'i
define('SAYT_URL', 'https://vatanparvaryaypan.uz');
define('SAYT_NOMI', 'VatanParvar Yaypan');

// Asosiy yo'llar
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/kesh');
define('BACKUP_PATH', ROOT_PATH . '/zaxira_nusxalari');

// Sessiya parametrlari
define('SESSION_NOMI', 'VATANPARVAR_SESS');
define('SESSION_VAQTI', 60 * 60 * 24 * 7); // 7 kun

// CSRF token
define('CSRF_KALITI', 'csrf_token');

// Rate limit (15 daqiqada 5 ta xato urinish)
define('LIMIT_VAQT', 60 * 15);
define('LIMIT_SON', 5);

// Test parametrlari
define('TEST_VAQT_DEFAULT', 25 * 60); // 25 daqiqa
define('TEST_SAVOL_SONI', 20);

// Bot
define('BOT_WEBHOOK_URL', SAYT_URL . '/bot.php');

// ----- Gemini AI (yordamchi chatbot) -----
define('GEMINI_API_KEY', 'AIzaSyDLZVuTeFqiBlHI8mtC-j6UwRimp6QvyLs');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

// AI'ni faollashtirish (false bo'lsa, faqat admin javob beradi)
define('AI_AKTIV', true);

// AI yordamchining "shaxsiyati" va instruksiyalari
define('AI_SYSTEM_PROMPT', <<<PROMPT
Sen "VatanParvar Yaypan" platformasining rasmiy AI yordamchisisan.
Sayt: avto maktab nazariyasi imtihoniga onlayn tayyorgarlik platformasi.
Domain: vatanparvaryaypan.uz

Sening vazifang:
- Foydalanuvchilarga tariflar, ro'yxatdan o'tish, testlar, to'lov haqida yordam berish
- Yo'l harakati qoidalari, yo'l belgilari haqida sodda tushuntirib berish
- Texnik muammolarda dastlabki yordam ko'rsatish
- Test yechish bo'yicha maslahat berish

QAT'IY QOIDALAR:
1. FAQAT O'ZBEK TILIDA (lotin yozuvi) javob ber
2. JAVOBING QISQA bo'lsin (3-5 jumla, kerak bo'lsa ro'yxat)
3. Iliq, do'stona ohangda javob ber, "siz" deb murojaat qil
4. Aniq narx-navo, sana yoki shaxsiy ma'lumot kerak bo'lsa: "Bu masalani aniq bilish uchun adminimiz javob beradi" deb yo'naltir
5. Avtomaktab nazariyasidan tashqari mavzularda javob berma — asta xushmuomalalik bilan saytga yo'naltir
6. To'lov muammolarida: "Adminimiz tezda yordam beradi, kuting" deb yo'naltir
7. Emoji'lardan oz va aqlli foydalan (1-2 ta)
8. Reklama qilma, tabiiy bo'l

Saytda mavjud bo'limlar:
- /test — biletlar va savollar
- /tariflar — narxlar
- /tolov — to'lov sahifasi
- /profil — sozlamalar
- /referal — do'stlarni taklif qilish
- /aloqa — admin bilan bog'lanish
- /blog — foydali maqolalar

Shu kontekstda har bir savolga o'sha foydalanuvchining maqsadini hisobga olib javob ber.
PROMPT);


// Xato sozlamalari
if (REJIM === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/zaxira_nusxalari/php_errors.log');
}

// UTF-8 majburiy
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tashkent');
