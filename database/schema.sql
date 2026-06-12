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
    `til` ENUM('uz_latn','uz_cyrl','ru') DEFAULT 'uz_latn',
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
    `tavsif` TEXT DEFAULT NULL,
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
    `tavsif` TEXT DEFAULT NULL,
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
    `rasm` VARCHAR(255) DEFAULT NULL,
    `variant_a` TEXT NOT NULL,
    `variant_b` TEXT NOT NULL,
    `variant_c` TEXT DEFAULT NULL,
    `variant_d` TEXT DEFAULT NULL,
    `togri_javob` ENUM('a','b','c','d') NOT NULL,
    `izoh` TEXT DEFAULT NULL,
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
    `matn` TEXT NOT NULL,
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
INSERT IGNORE INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('sayt_nomi', 'VatanParvar Yaypan', 'Sayt nomi'),
('sayt_shior', 'Avto maktab nazariyasiga eng tezkor tayyorgarlik', 'Bosh sahifa shiori'),
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
INSERT IGNORE INTO `tariflar` (`nomi`, `tavsif`, `tur`, `qiymat`, `narx`, `eski_narx`, `mashhur`, `tartib`) VALUES
('1 kunlik', 'Bir kunlik to''liq kirish', 'kun', 1, 5000, 8000, 0, 1),
('1 oylik', 'Bir oylik to''liq kirish', 'oy', 1, 25000, 40000, 1, 2),
('3 oylik', 'Uch oylik chegirmali tarif', 'oy', 3, 60000, 120000, 0, 3),
('Cheksiz', 'Imtihon topshirilguncha', 'oy', 12, 99000, 200000, 0, 4);

-- Demo bilet
INSERT IGNORE INTO `biletlar` (`raqam`, `nomi`, `tavsif`, `tur`) VALUES
(1, 'Bilet №1 (demo)', 'Bepul tanishuv bileti', 'bepul');

-- Demo savol
INSERT IGNORE INTO `savollar` (`bilet_id`, `matn`, `variant_a`, `variant_b`, `variant_c`, `variant_d`, `togri_javob`, `izoh`) VALUES
(1, 'Yo''l harakati qoidalariga binoan, qaysi belgi xavf belgilarini bildiradi?',
 'Uchburchak shaklidagi qizil hoshiyali belgilar',
 'Doira shaklidagi ko''k belgilar',
 'To''rtburchak shaklidagi yashil belgilar',
 'Sakkiztomonlama qizil belgi',
 'a',
 'Xavf belgilari uchburchak shaklida bo''lib, qizil hoshiya bilan o''ralgan.');

-- Developer akkaunt (parol: admin12345)
INSERT IGNORE INTO `foydalanuvchilar` (`ism`, `familiya`, `telefon`, `parol_hash`, `rol`, `referal_kod`)
VALUES ('Bosh', 'Dasturchi', '+998900000000',
'$2y$12$EaiIphQotqSMPzVymtHBBOwXEuI3iyopH4pN3Re4HdEG2bt6F10PO',
'developer', 'DEV0000');



-- ============================================================
-- 13. BLOGLAR — Maqolalar
-- ============================================================
CREATE TABLE IF NOT EXISTS `bloglar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `sarlavha` VARCHAR(255) NOT NULL,
    `qisqa` TEXT DEFAULT NULL,
    `matn` LONGTEXT NOT NULL,
    `rasm` VARCHAR(255) DEFAULT NULL,
    `muallif_id` INT UNSIGNED DEFAULT NULL,
    `kategoriya` VARCHAR(80) DEFAULT 'Umumiy',
    `koruv` INT UNSIGNED DEFAULT 0,
    `holat` ENUM('chop','qoralama') DEFAULT 'chop',
    `seo_keyword` VARCHAR(255) DEFAULT NULL,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `yangilangan` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_holat` (`holat`),
    FOREIGN KEY (`muallif_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. SO'ROVLAR — Aloqa formasidan kelgan xabarlar
-- ============================================================
CREATE TABLE IF NOT EXISTS `sorovlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ism` VARCHAR(100) NOT NULL,
    `telefon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `mavzu` VARCHAR(150) DEFAULT NULL,
    `xabar` TEXT NOT NULL,
    `holat` ENUM('yangi','korilgan','javoblangan') DEFAULT 'yangi',
    `admin_javob` TEXT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_holat` (`holat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Yangi sozlamalar: logo, banner, ijtimoiy tarmoqlar, manzil
-- ============================================================
INSERT IGNORE INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('logo_url', '', 'Sayt logosi (uploads/ ichidagi yo''l)'),
('banner_url', '', 'Bosh sahifa banner rasmi'),
('manzil', 'Yaypan shahri, Farg''ona viloyati', 'Aloqa manzili'),
('ish_vaqti', 'Du-Sha 09:00 - 18:00', 'Ish vaqti'),
('xarita_url', '', 'Google maps/Yandex iframe URL'),
('telegram_link', '', 'Telegram do''st aloqa havolasi'),
('instagram_link', '', 'Instagram havolasi'),
('youtube_link', '', 'YouTube havolasi'),
('blog_aktiv', '1', 'Blog bo''limini ko''rsatish (1/0)'),
('hero_video_url', '', 'Hero qismida ko''rinadigan YouTube video URL'),
('about_matn', 'Avto maktab nazariyasiga onlayn tayyorgarlik platformasi.', 'Sayt haqida qisqacha matn');

-- Demo blog post
INSERT IGNORE INTO `bloglar` (`slug`, `sarlavha`, `qisqa`, `matn`, `kategoriya`, `holat`) VALUES
('imtihon-tayyorgarlik-maslahatlar', 'Imtihonga tayyorgarlik: 5 ta muhim maslahat',
'Avto maktab imtihonidan birinchi urinishdan o''tish uchun amaliy maslahatlar.',
'<p>Avto maktab nazariyasi imtihoni — har bir haydovchi uchun muhim qadam. Quyidagi maslahatlarga amal qiling:</p><h3>1. Har kuni mashq qiling</h3><p>Kuniga 1 soat test yechish — eng yaxshi natija.</p><h3>2. Xato qilgan savollarni qayta ko''ring</h3><p>Bizning platformada barcha xato javoblar saqlanadi.</p><h3>3. Yo''l belgilarini yodlang</h3><p>Belgilar imtihonning 30% ini tashkil qiladi.</p><h3>4. Sokin xonada mashq qiling</h3><p>Konsentratsiya muhim.</p><h3>5. Imtihondan oldin yaxshi uxlang</h3><p>Charchoqsiz aql aniqroq ishlaydi.</p>',
'Maslahatlar', 'chop');
