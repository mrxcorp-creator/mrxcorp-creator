CREATE TABLE IF NOT EXISTS `xavfsizlik_qaydlar` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED DEFAULT NULL,
    `tur` VARCHAR(32) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `manzil` VARCHAR(255) DEFAULT NULL,
    `tafsilot` JSON DEFAULT NULL,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tur_y` (`tur`, `yaratilgan`),
    INDEX `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bloklangan_iplar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) UNIQUE NOT NULL,
    `sabab` VARCHAR(255) DEFAULT NULL,
    `tugash` DATETIME DEFAULT NULL,
    `bloklangan_paytda` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tugash` (`tugash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
