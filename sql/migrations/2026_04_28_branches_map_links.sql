-- Migration: add map / Waze deep-links to branches
-- Idempotent: safe to run on databases that already have these columns
-- (e.g. when install.php has already applied them).
--
-- We use information_schema + dynamic SQL because MySQL's
-- "ALTER TABLE ... ADD COLUMN IF NOT EXISTS" only exists on MySQL 8.0.29+
-- and isn't available on older Hostinger / shared MySQL setups.

SET @db := DATABASE();

-- ----- branches.google_map_link -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME   = 'branches'
      AND COLUMN_NAME  = 'google_map_link') = 0,
  'ALTER TABLE branches ADD COLUMN google_map_link VARCHAR(500) DEFAULT NULL AFTER google_map_embed',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- branches.waze_link -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME   = 'branches'
      AND COLUMN_NAME  = 'waze_link') = 0,
  'ALTER TABLE branches ADD COLUMN waze_link VARCHAR(500) DEFAULT NULL AFTER google_map_link',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
