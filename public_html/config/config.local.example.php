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
// 2. SAYT URL — protokol + domen, oxiridagi / SIZ
// ============================================================
define('SAYT_URL', 'https://vatanparvaryaypan.uz');

// ============================================================
// 3. MA'LUMOTLAR BAZASI
// ============================================================
// cPanel'da yaratilgan baza ma'lumotlari (To'liq nom prefiksi bilan)
define('DB_HOST', 'localhost');
define('DB_NAME', 'wbefkccz_avtomaktab');
define('DB_USER', 'wbefkccz_avtomaktab');
define('DB_PASS', 'BU_YERGA_HAQIQIY_PAROLNI_QO_YING');
define('DB_CHARSET', 'utf8mb4');
