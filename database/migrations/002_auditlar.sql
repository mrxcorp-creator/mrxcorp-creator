CREATE TABLE IF NOT EXISTS `auditlar` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `foydalanuvchi_id` INT NULL,
    `harakat` VARCHAR(64) NOT NULL,
    `obyekt_turi` VARCHAR(32) NULL,
    `obyekt_id` BIGINT NULL,
    `tafsilot` JSON NULL,
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(255) NULL,
    `yaratilgan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_foydalanuvchi` (`foydalanuvchi_id`),
    KEY `idx_harakat` (`harakat`),
    KEY `idx_yaratilgan` (`yaratilgan`),
    CONSTRAINT `fk_audit_foyd` FOREIGN KEY (`foydalanuvchi_id`)
        REFERENCES `foydalanuvchilar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
