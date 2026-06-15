CREATE TABLE IF NOT EXISTS `login_slaydlar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sarlavha` VARCHAR(150) NOT NULL,
    `matn` TEXT DEFAULT NULL,
    `rasm` VARCHAR(255) DEFAULT NULL,
    `tartib` INT NOT NULL DEFAULT 0,
    `holat` ENUM('faol','nofaol') DEFAULT 'faol',
    `yaratilgan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_holat_tartib` (`holat`, `tartib`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `login_slaydlar` (`id`, `sarlavha`, `matn`, `tartib`, `holat`) VALUES
(1, 'Imtihonni birinchi urinishdan toping', 'Real imtihon savollari, batafsil tushuntirishlar va vaqt cheklovi bilan testlar', 1, 'faol'),
(2, '500+ real savol', "YHXBB imtihonidan eng so'nggi yangilangan savollar bazasi", 2, 'faol'),
(3, 'Mobil va kompyuterda bemalol', 'Telefon, planshet, kompyuter — istalgan qurilmadan kirib testlarni davom ettirishingiz mumkin', 3, 'faol');
