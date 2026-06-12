-- ============================================================
-- VatanParvar Yaypan — Migration 3: Dizayn (logo+banner) + Reklama
-- ------------------------------------------------------------
-- 1. Sozlamalarga logo va banner kalitlari qo'shiladi
-- 2. Yangi `reklamalar` jadvali yaratiladi
--
-- Ishga tushirish:
--   mysql -u USER -p DBNAME < migration_3_dizayn_reklama.sql
-- ============================================================

SET NAMES utf8mb4;

-- ----------- 1. Sozlamalarga yangi kalitlar -----------
INSERT IGNORE INTO `sozlamalar` (`kalit`, `qiymat`, `tavsif`) VALUES
('sayt_logo',         '', "Sayt logo fayli (uploads/dizayn/ ichida)"),
('sayt_favicon',      '', "Favicon fayli"),
('bosh_banner',       '', "Bosh sahifa hero banner rasmi"),
('bosh_banner_aktiv', '0', "Banner faol (1) yoki yo'q (0)"),
('bosh_banner_havola','', "Banner bosilganda ochiladigan havola");

-- ----------- 2. Reklamalar jadvali -----------
CREATE TABLE IF NOT EXISTS `reklamalar` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nomi`         VARCHAR(150) NOT NULL,
    `rasm`         VARCHAR(255) NOT NULL,
    `havola`       VARCHAR(500) DEFAULT NULL,
    `havola_yangi_oyna` TINYINT(1) DEFAULT 1,
    `joylashuv`    ENUM('bosh_yuqori','bosh_pastki','user_yon','test_oraligi','sidebar') NOT NULL,
    `boshlanish`   DATE DEFAULT NULL,
    `tugash`       DATE DEFAULT NULL,
    `holat`        ENUM('faol','nofaol') DEFAULT 'faol',
    `tartib`       INT DEFAULT 0,
    `korish_soni`  INT UNSIGNED DEFAULT 0,
    `bosish_soni`  INT UNSIGNED DEFAULT 0,
    `yaratilgan`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_joylashuv_holat` (`joylashuv`, `holat`),
    INDEX `idx_sana_oraliq` (`boshlanish`, `tugash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
