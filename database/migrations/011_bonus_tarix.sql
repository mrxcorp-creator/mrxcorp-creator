CREATE TABLE IF NOT EXISTS `bonus_tarix` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `summa` DECIMAL(10,2) NOT NULL,
    `tur` ENUM('referal','admin','tolov','xarid') NOT NULL,
    `izoh` VARCHAR(255) DEFAULT NULL,
    `bog_lik_id` BIGINT DEFAULT NULL,
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_foyd_y` (`foydalanuvchi_id`, `yaratilgan`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marafonlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT UNSIGNED NOT NULL,
    `savollar_json` TEXT NOT NULL,
    `javoblar_json` TEXT DEFAULT NULL,
    `togri_son` INT DEFAULT 0,
    `xato_son` INT DEFAULT 0,
    `umumiy_son` INT NOT NULL DEFAULT 50,
    `holat` ENUM('davom','tugagan','vaqt_tugadi') DEFAULT 'davom',
    `boshlangan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `tugagan` DATETIME DEFAULT NULL,
    INDEX `idx_foyd` (`foydalanuvchi_id`),
    FOREIGN KEY (`foydalanuvchi_id`) REFERENCES `foydalanuvchilar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
