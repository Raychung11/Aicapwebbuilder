-- Migration: add slug + meta_title + meta_description to packages.
-- Idempotent.
SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'packages' AND COLUMN_NAME = 'slug') = 0,
  'ALTER TABLE packages ADD COLUMN slug VARCHAR(190) DEFAULT NULL AFTER title',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'packages' AND COLUMN_NAME = 'meta_title') = 0,
  'ALTER TABLE packages ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER cta_url',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'packages' AND COLUMN_NAME = 'meta_description') = 0,
  'ALTER TABLE packages ADD COLUMN meta_description TEXT AFTER meta_title',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Unique index on (company_id, slug). Allows multiple NULLs.
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'packages' AND INDEX_NAME = 'uniq_pkg_slug') = 0,
  'ALTER TABLE packages ADD UNIQUE KEY uniq_pkg_slug (company_id, slug)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
