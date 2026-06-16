-- ============================================================
-- VatanParvar Yaypan — MySQL sxema (v2 — professional)
-- Yangiliklar:
--   • Barcha zarur INDEX'lar qo'shildi
--   • fikrlar.fikr_ip ustuni (mavjud bo'lmasa ALTER TABLE bilan)
--   • sozlamalar'ga telegram_webhook_secret qo'shildi
--   • kirish_urinishlar avtomatik tozalanish EVENT'i
--   • ON DUPLICATE KEY UPDATE — idempotent import
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+05:00';

-- ───────────────────────────────────────────────────────────
-- 1. FOYDALANUVCHILAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `foydalanuvchilar` (
    `id`               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `ism`              VARCHAR(100)     NOT NULL,
    `familiya`         VARCHAR(100)     DEFAULT NULL,
    `telefon`          VARCHAR(20)      UNIQUE NOT NULL,
    `email`            VARCHAR(150)     DEFAULT NULL,
    `parol_hash`       VARCHAR(255)     NOT NULL,
    `rol`              ENUM('user','admin','developer') DEFAULT 'user',
    `avatar`           VARCHAR(255)     DEFAULT NULL,
    `referal_kod`      VARCHAR(20)      UNIQUE NOT NULL,
    `referal_orqali`   INT UNSIGNED     DEFAULT NULL,
    `bonus_balans`     DECIMAL(12,2)    DEFAULT 0,
    `telegram_id`      BIGINT           DEFAULT NULL,
    `telegram_hash`    VARCHAR(64)      DEFAULT NULL,
    `til`              ENUM('uz_latn','uz_cyrl','ru') DEFAULT 'uz_latn',
    `oxirgi_kirish`    DATETIME         DEFAULT NULL,
    `holat`            ENUM('faol','bloklangan') DEFAULT 'faol',
    `yaratilgan`       DATETIME         DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_telefon`    (`telefon`),
    INDEX `idx_telegram`   (`telegram_id`),
    INDEX `idx_holat`      (`holat`),
    INDEX `idx_yaratilgan` (`yaratilgan`),     -- N+1 fix uchun
    FOREIGN KEY (`referal_orqali`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 2. TARIFLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tariflar` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nomi`        VARCHAR(100) NOT NULL,
    `tavsif`      TEXT         DEFAULT NULL,
    `tur`         ENUM('kun','oy','bilet') DEFAULT 'oy',
    `qiymat`      INT UNSIGNED DEFAULT 1,
    `narx`        DECIMAL(12,2) NOT NULL,
    `eski_narx`   DECIMAL(12,2) DEFAULT NULL,
    `mashhur`     TINYINT(1)   DEFAULT 0,
    `tartib`      INT          DEFAULT 0,
    `holat`       ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_holat` (`holat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 3. OBUNALAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `obunalar` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id`  INT UNSIGNED NOT NULL,
    `tarif_id`          INT UNSIGNED NOT NULL,
    `boshlanish`        DATETIME     NOT NULL,
    `tugash`            DATETIME     NOT NULL,
    `holat`             ENUM('faol','tugagan','bekor') DEFAULT 'faol',
    `yaratilgan`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_foydalanuvchi` (`foydalanuvchi_id`),
    INDEX `idx_holat_tugash`  (`holat`, `tugash`),  -- subquery optimization
    INDEX `idx_tugash`        (`tugash`),
    FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tarif_id`)
        REFERENCES `tariflar`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 4. BILETLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `biletlar` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `raqam`      INT UNSIGNED UNIQUE NOT NULL,
    `nomi`       VARCHAR(150) NOT NULL,
    `tavsif`     TEXT         DEFAULT NULL,
    `tur`        ENUM('bepul','pullik') DEFAULT 'pullik',
    `holat`      ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_raqam`      (`raqam`),
    INDEX `idx_holat_raqam`(`holat`, `raqam`)   -- biletlar ro'yxati uchun
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 5. SAVOLLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `savollar` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bilet_id`    INT UNSIGNED NOT NULL,
    `matn`        TEXT         NOT NULL,
    `rasm`        VARCHAR(255) DEFAULT NULL,
    `variant_a`   TEXT         NOT NULL,
    `variant_b`   TEXT         NOT NULL,
    `variant_c`   TEXT         DEFAULT NULL,
    `variant_d`   TEXT         DEFAULT NULL,
    `togri_javob` ENUM('a','b','c','d') NOT NULL,
    `izoh`        TEXT         DEFAULT NULL,
    `tartib`      INT          DEFAULT 0,
    `yaratilgan`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bilet`        (`bilet_id`),
    INDEX `idx_bilet_tartib` (`bilet_id`, `tartib`, `id`),  -- ORDER BY optimization
    FOREIGN KEY (`bilet_id`)
        REFERENCES `biletlar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 6. NATIJALAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `natijalar` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id`  INT UNSIGNED NOT NULL,
    `bilet_id`          INT UNSIGNED NOT NULL,
    `javoblar_json`     MEDIUMTEXT   DEFAULT NULL,
    `togri_son`         INT          DEFAULT 0,
    `xato_son`          INT          DEFAULT 0,
    `umumiy_son`        INT          DEFAULT 0,
    -- qolgan_vaqt = testning BOSHLANG'ICH muddati (o'zgarmas!)
    -- Qolgan vaqt: max(0, qolgan_vaqt - (NOW - boshlangan))
    `qolgan_vaqt`       INT          DEFAULT 0,
    `holat`             ENUM('davom','tugagan','vaqt_tugadi','bekor') DEFAULT 'davom',
    `boshlangan`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `tugagan`           DATETIME     DEFAULT NULL,
    INDEX `idx_foydalanuvchi`       (`foydalanuvchi_id`),
    INDEX `idx_holat`               (`holat`),
    INDEX `idx_tugagan`             (`tugagan`),
    INDEX `idx_foyd_holat_tugagan`  (`foydalanuvchi_id`, `holat`, `tugagan`),
    FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bilet_id`)
        REFERENCES `biletlar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 7. TO'LOVLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tolovlar` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id`  INT UNSIGNED NOT NULL,
    `tarif_id`          INT UNSIGNED NOT NULL,
    `summa`             DECIMAL(12,2) NOT NULL,
    `tolov_turi`        ENUM('click','payme','manual','bonus') NOT NULL,
    `tashqi_id`         VARCHAR(150) DEFAULT NULL,
    `holat`             ENUM('kutilmoqda','muvaffaqiyatli','bekor','xato') DEFAULT 'kutilmoqda',
    `izoh`              TEXT         DEFAULT NULL,
    `yaratilgan`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `yangilangan`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_foydalanuvchi` (`foydalanuvchi_id`),
    INDEX `idx_tashqi`        (`tashqi_id`),
    INDEX `idx_holat`         (`holat`),
    INDEX `idx_yaratilgan`    (`yaratilgan`),
    INDEX `idx_holat_yaratilgan` (`holat`, `yaratilgan`),   -- dashboard grafik uchun
    FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tarif_id`)
        REFERENCES `tariflar`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 8. PROMO KODLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `promo_kodlar` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kod`            VARCHAR(30)   UNIQUE NOT NULL,
    `chegirma_foiz`  INT           DEFAULT 0,
    `chegirma_summa` DECIMAL(12,2) DEFAULT 0,
    `maks_ishlatish` INT           DEFAULT 1,
    `ishlatilgan`    INT           DEFAULT 0,
    `tugash_sanasi`  DATETIME      DEFAULT NULL,
    `holat`          ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_kod`   (`kod`),
    INDEX `idx_holat` (`holat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 9. REFERALLAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `referallar` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `referer_id`  INT UNSIGNED NOT NULL,
    `referal_id`  INT UNSIGNED NOT NULL,
    `bonus_summa` DECIMAL(10,2) DEFAULT 0,
    `holat`       ENUM('kutilmoqda','tasdiq','bekor') DEFAULT 'kutilmoqda',
    `yaratilgan`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_referal` (`referal_id`),  -- bir foydalanuvchi ikki marta referalda bo'lmasin
    INDEX `idx_referer` (`referer_id`),
    FOREIGN KEY (`referer_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`referal_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 10. SOZLAMALAR
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sozlamalar` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kalit`       VARCHAR(100) UNIQUE NOT NULL,
    `qiymat`      TEXT         DEFAULT NULL,
    `tavsif`      VARCHAR(255) DEFAULT NULL,
    `yangilangan` DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_kalit` (`kalit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 11. FIKRLAR (+fikr_ip ustuni)
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fikrlar` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED DEFAULT NULL,
    `ism`              VARCHAR(100) NOT NULL,
    `matn`             TEXT         NOT NULL,
    `baho`             TINYINT      DEFAULT 5,
    `tasdiq`           TINYINT(1)   DEFAULT 0,
    `fikr_ip`          VARCHAR(45)  DEFAULT NULL,
    `yaratilgan`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tasdiq` (`tasdiq`),
    FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────
-- 12. KIRISH URINISHLAR (rate-limit)
-- ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `kirish_urinishlar` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip`            VARCHAR(45)  NOT NULL,
    `telefon`       VARCHAR(20)  DEFAULT NULL,
    `muvaffaqiyat`  TINYINT(1)   DEFAULT 0,
    `yaratilgan`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip`      (`ip`),
    INDEX `idx_telefon` (`telefon`),       -- per-phone rate limit uchun (YANGI)
    INDEX `idx_vaqt`    (`yaratilgan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ───────────────────────────────────────────────────────────
-- MAVJUD JADVALGA fikr_ip ustunini qo'shish
-- (eski o'rnatishlarda ALTER TABLE)
-- ───────────────────────────────────────────────────────────
-- Bu satr xavfsiz: ustun mavjud bo'lsa xato bermaydi
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fikrlar'
      AND COLUMN_NAME  = 'fikr_ip'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `fikrlar` ADD COLUMN `fikr_ip` VARCHAR(45) DEFAULT NULL AFTER `tasdiq`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ───────────────────────────────────────────────────────────
-- AVTOMATIK CLEANUP EVENT
-- Har kecha 02:30 da 30 kundan eski kirish urinishlarini o'chiradi.
-- MySQL event_scheduler yoqilgan bo'lishi kerak.
-- ───────────────────────────────────────────────────────────
DROP EVENT IF EXISTS `evt_kirish_urinishlar_cleanup`;

CREATE EVENT `evt_kirish_urinishlar_cleanup`
    ON SCHEDULE EVERY 1 DAY
    STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 150 MINUTE)
    ON COMPLETION PRESERVE
    ENABLE
    COMMENT 'Eski kirish urinishlarini o\'chirish'
DO
    DELETE FROM `kirish_urinishlar`
    WHERE `yaratilgan` < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ───────────────────────────────────────────────────────────
-- BOSHLANG'ICH MA'LUMOTLAR
-- ───────────────────────────────────────────────────────────

INSERT INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('sayt_nomi',               'VatanParvar Yaypan',                         'Sayt nomi'),
('sayt_shior',              'O\'zbekistonda avto maktab nazariyasiga eng tezkor tayyorgarlik', 'Bosh sahifa shiori'),
('aloqa_telefon',           '+998 90 123 45 67',                    'Aloqa telefoni'),
('aloqa_email',             'info@vatanparvaryaypan.uz',                  'Aloqa email'),
('telegram_kanal',          'https://t.me/vatanparvar',             'Telegram kanal URL'),
('telegram_bot_token',      '',                                     'Bot tokeni (BotFather dan)'),
('telegram_bot_username',   'vatanparvar_bot',                      'Bot username (@siz)'),
('telegram_admin_id',       '',                                     'Admin Telegram ID'),
('telegram_webhook_secret', '',                                     'Telegram webhook himoya kodi (ixtiyoriy)'),
('click_merchant_id',       '',                                     'Click Merchant ID'),
('click_service_id',        '',                                     'Click Service ID'),
('click_secret',            '',                                     'Click Secret kaliti'),
('payme_merchant_id',       '',                                     'Payme Merchant ID'),
('payme_key',               '',                                     'Payme kaliti (test/prod)'),
('referal_bonus',           '5000',                                 'Referal bonus summasi (so\'m)'),
('test_vaqti_minut',        '25',                                   'Test vaqti (daqiqada)'),
('savol_soni_test',         '20',                                   'Bir testdagi savollar soni'),
('cron_kalit',              '',                                     'Cron himoya kaliti (tasodifiy string, 32+ belgi)')
ON DUPLICATE KEY UPDATE tavsif = VALUES(tavsif);

-- Demo tariflar
INSERT INTO `tariflar` (`nomi`, `tavsif`, `tur`, `qiymat`, `narx`, `eski_narx`, `mashhur`, `tartib`)
VALUES
    ('1 kunlik',  'Bir kunlik to''liq kirish',    'kun', 1,  5000,  8000,  0, 1),
    ('1 oylik',   'Bir oylik to''liq kirish',     'oy',  1,  25000, 40000, 1, 2),
    ('3 oylik',   'Uch oylik chegirmali tarif',   'oy',  3,  60000, 120000,0, 3),
    ('Cheksiz',   'Imtihon topshirilguncha',      'oy',  12, 99000, 200000,0, 4)
ON DUPLICATE KEY UPDATE nomi = nomi;

-- Demo bilet
INSERT INTO `biletlar` (`raqam`, `nomi`, `tavsif`, `tur`)
VALUES (1, 'Bilet №1 (demo)', 'Bepul tanishuv bileti', 'bepul')
ON DUPLICATE KEY UPDATE nomi = nomi;

-- Demo savol
INSERT INTO `savollar`
    (`bilet_id`,`matn`,`variant_a`,`variant_b`,`variant_c`,`variant_d`,`togri_javob`,`izoh`)
SELECT 1,
    'Yo''l harakati qoidalariga binoan, qaysi belgi xavf belgisini bildiradi?',
    'Uchburchak shaklidagi qizil hoshiyali belgilar',
    'Doira shaklidagi ko''k belgilar',
    'To''rtburchak shaklidagi yashil belgilar',
    'Sakkiztomonlama qizil belgi',
    'a',
    'Xavf belgilari uchburchak shaklida bo''lib, qizil hoshiya bilan o''ralgan.'
WHERE NOT EXISTS (SELECT 1 FROM `savollar` WHERE bilet_id = 1 LIMIT 1);

-- Developer akkaunt (parol: admin12345)
-- NOTE: ishga tushirishdan keyin ALBATTA parolni o'zgartiring!
INSERT INTO `foydalanuvchilar`
    (`ism`, `familiya`, `telefon`, `parol_hash`, `rol`, `referal_kod`)
VALUES
    ('Bosh', 'Admin', '+998900000000',
     '$2y$12$Iq2QwQ7yT9tFh1cP3wXJ8.6XK8Xz4w9Qm0o8A5xN3Yx9c2Yf3eL9G',
     'developer', 'DEVADMIN')
ON DUPLICATE KEY UPDATE rol = 'developer';
