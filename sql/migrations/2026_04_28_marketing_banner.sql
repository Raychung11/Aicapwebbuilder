-- Migration: add marketing banner fields to companies. Idempotent.
SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'banner_image') = 0,
  'ALTER TABLE companies ADD COLUMN banner_image VARCHAR(255) DEFAULT NULL AFTER og_image',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'banner_title') = 0,
  'ALTER TABLE companies ADD COLUMN banner_title VARCHAR(255) DEFAULT NULL AFTER banner_image',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'banner_subtitle') = 0,
  'ALTER TABLE companies ADD COLUMN banner_subtitle TEXT AFTER banner_title',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'banner_cta_text') = 0,
  'ALTER TABLE companies ADD COLUMN banner_cta_text VARCHAR(120) DEFAULT NULL AFTER banner_subtitle',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'banner_cta_url') = 0,
  'ALTER TABLE companies ADD COLUMN banner_cta_url VARCHAR(500) DEFAULT NULL AFTER banner_cta_text',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
