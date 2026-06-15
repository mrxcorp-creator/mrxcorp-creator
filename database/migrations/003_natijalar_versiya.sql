SET @col_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'natijalar' AND COLUMN_NAME = 'versiya');

SET @sql := IF(@col_bor = 0,
    'ALTER TABLE natijalar ADD COLUMN versiya INT NOT NULL DEFAULT 0 AFTER javoblar_json',
    'SELECT "ustun_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'natijalar' AND INDEX_NAME = 'idx_natija_holat_foyd');

SET @sql := IF(@idx_bor = 0,
    'ALTER TABLE natijalar ADD INDEX idx_natija_holat_foyd (foydalanuvchi_id, holat)',
    'SELECT "indeks_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
