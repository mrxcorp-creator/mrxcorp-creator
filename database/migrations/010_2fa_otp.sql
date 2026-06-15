SET @col_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'foydalanuvchilar' AND COLUMN_NAME = 'tfa_yoq');

SET @sql := IF(@col_bor = 0,
    'ALTER TABLE foydalanuvchilar ADD COLUMN tfa_yoq TINYINT(1) NOT NULL DEFAULT 0 AFTER kirish_bildirish',
    'SELECT "ustun_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `tfa_otp` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `kod` VARCHAR(10) NOT NULL,
    `urinish` TINYINT NOT NULL DEFAULT 0,
    `ip` VARCHAR(45) NOT NULL,
    `tugash` DATETIME NOT NULL,
    `ishlatildi` TINYINT(1) NOT NULL DEFAULT 0,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_foyd_yaratilgan` (`foydalanuvchi_id`, `yaratilgan`),
    INDEX `idx_tugash` (`tugash`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
