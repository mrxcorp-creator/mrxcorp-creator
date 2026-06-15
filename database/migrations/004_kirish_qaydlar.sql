CREATE TABLE IF NOT EXISTS `kirish_qaydlar` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `qurilma` VARCHAR(64) NULL,
    `yaratilgan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_foyd` (`foydalanuvchi_id`),
    KEY `idx_yaratilgan` (`yaratilgan`),
    CONSTRAINT `fk_kqayd_foyd` FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'foydalanuvchilar' AND COLUMN_NAME = 'kirish_bildirish');

SET @sql := IF(@col_bor = 0,
    'ALTER TABLE foydalanuvchilar ADD COLUMN kirish_bildirish TINYINT(1) NOT NULL DEFAULT 1 AFTER til',
    'SELECT "ustun_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
