SET @idx_bor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kirish_urinishlar' AND INDEX_NAME = 'idx_yaratilgan');

SET @sql := IF(@idx_bor = 0,
    'ALTER TABLE kirish_urinishlar ADD INDEX idx_yaratilgan (yaratilgan)',
    'SELECT "indeks_mavjud" AS holat');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
