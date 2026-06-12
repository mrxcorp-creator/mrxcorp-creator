-- ============================================================
-- VatanParvar Yaypan — Migration 2: Lotin/Kirill qo'llab-quvvatlash
-- ------------------------------------------------------------
-- Bu fayl mavjud bazadagi kontent jadvallariga _cyrl ustunlarini
-- qo'shadi. _cyrl ustunlari kirill yozuvidagi nusxani saqlaydi.
-- Lotin asosiy ustunda saqlanishda davom etadi.
--
-- Yangi qator qo'shilganda — admin/api fayllari ikkala maydonni
-- bir vaqtning o'zida to'ldiradi (avto-transliteratsiya orqali).
--
-- Mavjud qatorlar uchun esa, bu fayl ulanish skriptini chaqirib
-- _cyrl ustunlarini avtomatik to'ldirish mumkin (PHP orqali).
--
-- Ishga tushirish:
--   mysql -u USER -p DBNAME < migration_2_til_qollab_quvvatlash.sql
--   keyin: php cron/sozlash.php --til-migratsiya
-- ============================================================

SET NAMES utf8mb4;

-- ----------- 1. BILETLAR -----------
ALTER TABLE `biletlar`
    ADD COLUMN `nomi_cyrl` VARCHAR(150) DEFAULT NULL AFTER `nomi`,
    ADD COLUMN `tavsif_cyrl` TEXT DEFAULT NULL AFTER `tavsif`;

-- ----------- 2. SAVOLLAR -----------
ALTER TABLE `savollar`
    ADD COLUMN `matn_cyrl` TEXT DEFAULT NULL AFTER `matn`,
    ADD COLUMN `variant_a_cyrl` TEXT DEFAULT NULL AFTER `variant_a`,
    ADD COLUMN `variant_b_cyrl` TEXT DEFAULT NULL AFTER `variant_b`,
    ADD COLUMN `variant_c_cyrl` TEXT DEFAULT NULL AFTER `variant_c`,
    ADD COLUMN `variant_d_cyrl` TEXT DEFAULT NULL AFTER `variant_d`,
    ADD COLUMN `izoh_cyrl` TEXT DEFAULT NULL AFTER `izoh`;

-- ----------- 3. TARIFLAR -----------
ALTER TABLE `tariflar`
    ADD COLUMN `nomi_cyrl` VARCHAR(100) DEFAULT NULL AFTER `nomi`,
    ADD COLUMN `tavsif_cyrl` TEXT DEFAULT NULL AFTER `tavsif`;

-- ----------- 4. FIKRLAR -----------
ALTER TABLE `fikrlar`
    ADD COLUMN `ism_cyrl` VARCHAR(100) DEFAULT NULL AFTER `ism`,
    ADD COLUMN `matn_cyrl` TEXT DEFAULT NULL AFTER `matn`;

-- ----------- 5. SOZLAMALAR (kalit-qiymat juftliklari uchun _cyrl variantli kalitlar) -----------
-- sozlamalar jadvalida struktura o'zgarmaydi:
-- shunchaki yangi kalit qo'shamiz: 'sayt_nomi_cyrl', 'sayt_shior_cyrl' va h.k.
-- Bu kalitlar mavjud kalitlarning kirill nusxasini saqlaydi.

INSERT IGNORE INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('sayt_nomi_cyrl',     '',  'Sayt nomi (kirill)'),
('sayt_shior_cyrl',    '',  'Sayt shiori (kirill)');

-- ----------- 6. FOYDALANUVCHILAR til ENUM ni yangilash -----------
-- Rus tilini olib tashlaymiz, faqat lotin va kirill qoldiramiz.
-- Eski 'ru' qiymatdagi foydalanuvchilar 'uz_latn' ga o'tadi.

UPDATE `foydalanuvchilar` SET `til` = 'uz_latn' WHERE `til` = 'ru';

ALTER TABLE `foydalanuvchilar`
    MODIFY COLUMN `til` ENUM('uz_latn','uz_cyrl') NOT NULL DEFAULT 'uz_latn';

-- ============================================================
-- ESLATMA: Mavjud qatorlardagi _cyrl ustunlarni to'ldirish uchun
-- quyidagi PHP skriptini ishga tushiring (transliteratsiya kerak):
--
--   /public_html/cron/sozlash.php?harakat=til_migratsiya
-- ============================================================
