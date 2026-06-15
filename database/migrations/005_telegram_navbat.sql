CREATE TABLE IF NOT EXISTS `telegram_navbat` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `chat_id` VARCHAR(32) NOT NULL,
    `matn` TEXT NOT NULL,
    `qoshimcha_json` TEXT NULL,
    `holat` ENUM('kutilmoqda','jonatildi','xato') DEFAULT 'kutilmoqda',
    `urinish` TINYINT NOT NULL DEFAULT 0,
    `xato_matn` TEXT NULL,
    `yaratilgan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `yangilangan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_holat_yaratilgan` (`holat`, `yaratilgan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
