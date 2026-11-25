
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'program'
       AND COLUMN_NAME = 'jenis') = 0,
    "ALTER TABLE program ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'Bootcamp' AFTER kategori_id",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'program'
       AND COLUMN_NAME = 'tanggal_mulai') = 0,
    "ALTER TABLE program ADD COLUMN tanggal_mulai DATE NULL AFTER durasi",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'program'
       AND COLUMN_NAME = 'gambar') = 0,
    "ALTER TABLE program ADD COLUMN gambar VARCHAR(255) NOT NULL DEFAULT ''",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
