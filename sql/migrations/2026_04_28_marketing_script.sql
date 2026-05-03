-- Migration: marketing script field on vouchers. Idempotent.
SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'vouchers' AND COLUMN_NAME = 'marketing_script') = 0,
  'ALTER TABLE vouchers ADD COLUMN marketing_script TEXT AFTER description',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
