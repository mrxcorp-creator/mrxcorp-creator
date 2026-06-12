-- ============================================================
-- VatanParvar Yaypan — MySQL sxema
-- Ma'lumotlar bazasi: wbefkccz_avtomaktab
-- Kodlash: utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------- 1. FOYDALANUVCHILAR -----------
CREATE TABLE IF NOT EXISTS `foydalanuvchilar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ism` VARCHAR(100) NOT NULL,
    `familiya` VARCHAR(100) DEFAULT NULL,
    `telefon` VARCHAR(20) UNIQUE NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `parol_hash` VARCHAR(255) NOT NULL,
    `rol` ENUM('user','admin','developer') DEFAULT 'user',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `referal_kod` VARCHAR(20) UNIQUE NOT NULL,
    `referal_orqali` INT UNSIGNED DEFAULT NULL,
    `bonus_balans` DECIMAL(10,2) DEFAULT 0,
    `telegram_id` BIGINT DEFAULT NULL,
    `telegram_hash` VARCHAR(64) DEFAULT NULL,
    `til` ENUM('uz_latn','uz_cyrl') DEFAULT 'uz_latn',
    `oxirgi_kirish` DATETIME DEFAULT NULL,
    `holat` ENUM('faol','bloklangan') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_telefon` (`telefon`),
    INDEX `idx_telegram` (`telegram_id`),
    FOREIGN KEY (`referal_orqali`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 2. TARIFLAR -----------
CREATE TABLE IF NOT EXISTS `tariflar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nomi` VARCHAR(100) NOT NULL,
    `nomi_cyrl` VARCHAR(100) DEFAULT NULL,
    `tavsif` TEXT DEFAULT NULL,
    `tavsif_cyrl` TEXT DEFAULT NULL,
    `tur` ENUM('kun','oy','bilet') DEFAULT 'oy',
    `qiymat` INT UNSIGNED DEFAULT 1,
    `narx` DECIMAL(12,2) NOT NULL,
    `eski_narx` DECIMAL(12,2) DEFAULT NULL,
    `mashhur` TINYINT(1) DEFAULT 0,
    `tartib` INT DEFAULT 0,
    `holat` ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 3. OBUNALAR -----------
CREATE TABLE IF NOT EXISTS `obunalar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `tarif_id` INT UNSIGNED NOT NULL,
    `boshlanish` DATETIME NOT NULL,
    `tugash` DATETIME NOT NULL,
    `holat` ENUM('faol','tugagan','bekor') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_foydalanuvchi` (`foydalanuvchi_id`),
    INDEX `idx_holat` (`holat`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tarif_id`) REFERENCES `tariflar`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 4. BILETLAR -----------
CREATE TABLE IF NOT EXISTS `biletlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `raqam` INT UNSIGNED UNIQUE NOT NULL,
    `nomi` VARCHAR(150) NOT NULL,
    `nomi_cyrl` VARCHAR(150) DEFAULT NULL,
    `tavsif` TEXT DEFAULT NULL,
    `tavsif_cyrl` TEXT DEFAULT NULL,
    `tur` ENUM('bepul','pullik') DEFAULT 'pullik',
    `holat` ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_raqam` (`raqam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 5. SAVOLLAR -----------
CREATE TABLE IF NOT EXISTS `savollar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bilet_id` INT UNSIGNED NOT NULL,
    `matn` TEXT NOT NULL,
    `matn_cyrl` TEXT DEFAULT NULL,
    `rasm` VARCHAR(255) DEFAULT NULL,
    `variant_a` TEXT NOT NULL,
    `variant_a_cyrl` TEXT DEFAULT NULL,
    `variant_b` TEXT NOT NULL,
    `variant_b_cyrl` TEXT DEFAULT NULL,
    `variant_c` TEXT DEFAULT NULL,
    `variant_c_cyrl` TEXT DEFAULT NULL,
    `variant_d` TEXT DEFAULT NULL,
    `variant_d_cyrl` TEXT DEFAULT NULL,
    `togri_javob` ENUM('a','b','c','d') NOT NULL,
    `izoh` TEXT DEFAULT NULL,
    `izoh_cyrl` TEXT DEFAULT NULL,
    `tartib` INT DEFAULT 0,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bilet` (`bilet_id`),
    FOREIGN KEY (`bilet_id`) REFERENCES `biletlar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 6. NATIJALAR -----------
CREATE TABLE IF NOT EXISTS `natijalar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `bilet_id` INT UNSIGNED NOT NULL,
    `javoblar_json` TEXT DEFAULT NULL,
    `togri_son` INT DEFAULT 0,
    `xato_son` INT DEFAULT 0,
    `umumiy_son` INT DEFAULT 0,
    `qolgan_vaqt` INT DEFAULT 0,
    `holat` ENUM('davom','tugagan','vaqt_tugadi') DEFAULT 'davom',
    `boshlangan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `tugagan` DATETIME DEFAULT NULL,
    INDEX `idx_foydalanuvchi` (`foydalanuvchi_id`),
    INDEX `idx_holat` (`holat`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bilet_id`) REFERENCES `biletlar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 7. TO'LOVLAR -----------
CREATE TABLE IF NOT EXISTS `tolovlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `tarif_id` INT UNSIGNED NOT NULL,
    `summa` DECIMAL(12,2) NOT NULL,
    `tolov_turi` ENUM('click','payme','manual','bonus') NOT NULL,
    `tashqi_id` VARCHAR(150) DEFAULT NULL,
    `holat` ENUM('kutilmoqda','muvaffaqiyatli','bekor','xato') DEFAULT 'kutilmoqda',
    `izoh` TEXT DEFAULT NULL,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `yangilangan` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_foydalanuvchi` (`foydalanuvchi_id`),
    INDEX `idx_tashqi` (`tashqi_id`),
    INDEX `idx_holat` (`holat`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tarif_id`) REFERENCES `tariflar`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 8. PROMO KODLAR -----------
CREATE TABLE IF NOT EXISTS `promo_kodlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kod` VARCHAR(30) UNIQUE NOT NULL,
    `chegirma_foiz` INT DEFAULT 0,
    `chegirma_summa` DECIMAL(12,2) DEFAULT 0,
    `maks_ishlatish` INT DEFAULT 1,
    `ishlatilgan` INT DEFAULT 0,
    `tugash_sanasi` DATETIME DEFAULT NULL,
    `holat` ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 9. REFERALLAR -----------
CREATE TABLE IF NOT EXISTS `referallar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `referer_id` INT UNSIGNED NOT NULL,
    `referal_id` INT UNSIGNED NOT NULL,
    `bonus_summa` DECIMAL(10,2) DEFAULT 0,
    `holat` ENUM('kutilmoqda','tasdiq','bekor') DEFAULT 'kutilmoqda',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`referer_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`referal_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 10. SOZLAMALAR -----------
CREATE TABLE IF NOT EXISTS `sozlamalar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kalit` VARCHAR(100) UNIQUE NOT NULL,
    `qiymat` TEXT DEFAULT NULL,
    `tavsif` VARCHAR(255) DEFAULT NULL,
    `yangilangan` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 11. FIKRLAR -----------
CREATE TABLE IF NOT EXISTS `fikrlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED DEFAULT NULL,
    `ism` VARCHAR(100) NOT NULL,
    `ism_cyrl` VARCHAR(100) DEFAULT NULL,
    `matn` TEXT NOT NULL,
    `matn_cyrl` TEXT DEFAULT NULL,
    `baho` TINYINT DEFAULT 5,
    `tasdiq` TINYINT(1) DEFAULT 0,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------- 12. KIRISH UCHUN URINISHLAR (rate-limit) -----------
CREATE TABLE IF NOT EXISTS `kirish_urinishlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `telefon` VARCHAR(20) DEFAULT NULL,
    `muvaffaqiyat` TINYINT(1) DEFAULT 0,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip`),
    INDEX `idx_vaqt` (`yaratilgan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- BOSHLANG'ICH MA'LUMOTLAR
-- ============================================================

-- Standart sozlamalar
INSERT INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('sayt_nomi', 'VatanParvar Yaypan', 'Sayt nomi'),
('sayt_nomi_cyrl', 'ВатанПарвар Яйпан', 'Sayt nomi (kirill)'),
('sayt_shior', 'Avto maktab nazariyasiga eng tezkor tayyorgarlik', 'Bosh sahifa shiori'),
('sayt_shior_cyrl', 'Авто мактаб назариясига энг тезкор тайёргарлик', 'Bosh sahifa shiori (kirill)'),
('aloqa_telefon', '+998 90 123 45 67', 'Aloqa telefoni'),
('aloqa_email', 'info@vatanparvaryaypan.uz', 'Aloqa elektron pochtasi'),
('telegram_kanal', 'https://t.me/vatanparvaryaypan', 'Telegram kanal'),
('telegram_bot_token', '', 'Telegram bot tokeni'),
('telegram_admin_id', '', 'Admin Telegram ID'),
('click_merchant_id', '', 'Click Merchant ID'),
('click_secret', '', 'Click Secret'),
('payme_merchant_id', '', 'Payme Merchant ID'),
('payme_key', '', 'Payme test/prod kaliti'),
('referal_bonus', '5000', 'Referal bonus summasi'),
('test_vaqti_minut', '25', 'Bitta test uchun vaqt (daqiqada)'),
('savol_soni_test', '20', 'Bitta testdagi savollar soni');

-- Standart tariflar
INSERT INTO `tariflar` (`nomi`, `nomi_cyrl`, `tavsif`, `tavsif_cyrl`, `tur`, `qiymat`, `narx`, `eski_narx`, `mashhur`, `tartib`) VALUES
('1 kunlik', '1 кунлик', 'Bir kunlik to''liq kirish',  'Бир кунлик тўлиқ кириш',     'kun', 1,  5000,  8000, 0, 1),
('1 oylik',  '1 ойлик',  'Bir oylik to''liq kirish',   'Бир ойлик тўлиқ кириш',      'oy',  1, 25000, 40000, 1, 2),
('3 oylik',  '3 ойлик',  'Uch oylik chegirmali tarif', 'Уч ойлик чегирмали тариф',   'oy',  3, 60000, 120000, 0, 3),
('Cheksiz',  'Чексиз',   'Imtihon topshirilguncha',     'Имтиҳон топширилгунча',     'oy', 12, 99000, 200000, 0, 4);

-- Demo bilet
INSERT INTO `biletlar` (`raqam`, `nomi`, `nomi_cyrl`, `tavsif`, `tavsif_cyrl`, `tur`) VALUES
(1, 'Bilet №1 (demo)', 'Билет №1 (демо)', 'Bepul tanishuv bileti', 'Бепул танишув билети', 'bepul');

-- Demo savol
INSERT INTO `savollar` (`bilet_id`, `matn`, `matn_cyrl`,
    `variant_a`, `variant_a_cyrl`,
    `variant_b`, `variant_b_cyrl`,
    `variant_c`, `variant_c_cyrl`,
    `variant_d`, `variant_d_cyrl`,
    `togri_javob`, `izoh`, `izoh_cyrl`) VALUES
(1,
 'Yo''l harakati qoidalariga binoan, qaysi belgi xavf belgilarini bildiradi?',
 'Йўл ҳаракати қоидаларига биноан, қайси белги хавф белгиларини билдиради?',
 'Uchburchak shaklidagi qizil hoshiyali belgilar',
 'Учбурчак шаклидаги қизил ҳошияли белгилар',
 'Doira shaklidagi ko''k belgilar',
 'Доира шаклидаги кўк белгилар',
 'To''rtburchak shaklidagi yashil belgilar',
 'Тўртбурчак шаклидаги яшил белгилар',
 'Sakkiztomonlama qizil belgi',
 'Саккизтомонлама қизил белги',
 'a',
 'Xavf belgilari uchburchak shaklida bo''lib, qizil hoshiya bilan o''ralgan.',
 'Хавф белгилари учбурчак шаклида бўлиб, қизил ҳошия билан ўралган.');

-- Developer akkaunt (parol: admin12345)
INSERT INTO `foydalanuvchilar` (`ism`, `familiya`, `telefon`, `parol_hash`, `rol`, `referal_kod`)
VALUES ('Bosh', 'Dasturchi', '+998900000000',
'$2y$10$Iq2QwQ7yT9tFh1cP3wXJ8.6XK8Xz4w9Qm0o8A5xN3Yx9c2Yf3eL9G',
'developer', 'DEV0000');
