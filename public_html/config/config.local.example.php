<?php
/**
 * VatanParvar Yaypan — Lokal konfiguratsiya
 * ============================================
 *
 * Bu faylni nusxa olib, "config.local.php" deb saqlang va o'zingizning
 * server qiymatlaringizni kiriting. Bu fayl git'ga commit qilinmaydi
 * (`.gitignore`'da yopilgan).
 *
 * O'rnatuvchi (install.php) ham shu faylni avtomatik yaratadi.
 */

// ============================================================
// 1. SERVER REJIMI
// ============================================================
// 'production' — onlayn server (xatolar yashiriladi)
// 'development' — lokal kompyuter (xatolar ko'rsatiladi)
define('REJIM', 'production');

// ============================================================
// 2. SAYT URL — protokol + domen, oxirida slash bo'lmasin
// ============================================================
define('SAYT_URL', 'https://vatanparvaryaypan.uz');

// ============================================================
// 3. MA'LUMOTLAR BAZASI
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'wbefkccz_avtomaktab');
define('DB_USER', 'wbefkccz_avtomaktab');
define('DB_PASS', 'BU_YERGA_HAQIQIY_PAROLNI_QO_YING');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// 4. SIRLAR (TAVSIYA: shu yerda saqlang, DB'da emas)
// ============================================================
// Quyidagi konstantalar agar belgilangan bo'lsa, DB'dagi sozlamalardan
// ustunlik qiladi. Bu sirlar SQL injection yoki DB dump xatolaridan
// himoyalanadi (chunki `config/` papkasi web orqali ochilmaydi).
//
// Ishga tushirish: izohlardan chiqaring va o'z qiymatingizni qo'ying.

// define('TELEGRAM_BOT_TOKEN', '1234567890:AAH...');
// define('CLICK_SECRET',       'click_secret_key_here');
// define('PAYME_KEY',          'payme_test_or_prod_key_here');

// ============================================================
// 5. QO'SHIMCHA SOZLAMALAR (ixtiyoriy)
// ============================================================
// Telegram xabarlarini sinxron yuborish (fonda emas):
// define('TELEGRAM_SYNC', true);
