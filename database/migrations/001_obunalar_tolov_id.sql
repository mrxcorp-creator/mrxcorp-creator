SET @col_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'obunalar' AND COLUMN_NAME = 'tolov_id');

SET @sql := IF(@col_bor = 0,
    'ALTER TABLE obunalar ADD COLUMN tolov_id INT NULL AFTER tarif_id',
    'SELECT "ustun_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'obunalar' AND INDEX_NAME = 'uniq_tolov_id');

SET @sql := IF(@idx_bor = 0,
    'ALTER TABLE obunalar ADD UNIQUE KEY uniq_tolov_id (tolov_id)',
    'SELECT "indeks_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'obunalar' AND CONSTRAINT_NAME = 'fk_obuna_tolov');

SET @sql := IF(@fk_bor = 0,
    'ALTER TABLE obunalar ADD CONSTRAINT fk_obuna_tolov FOREIGN KEY (tolov_id) REFERENCES tolovlar(id) ON DELETE SET NULL',
    'SELECT "fk_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
