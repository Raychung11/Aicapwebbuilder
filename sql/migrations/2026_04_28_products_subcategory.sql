-- Migration: add subcategory to products. Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME   = 'products'
      AND COLUMN_NAME  = 'subcategory') = 0,
  'ALTER TABLE products ADD COLUMN subcategory VARCHAR(120) DEFAULT NULL AFTER category',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME   = 'products'
      AND INDEX_NAME   = 'idx_prod_subcategory') = 0,
  'ALTER TABLE products ADD INDEX idx_prod_subcategory (company_id, category, subcategory)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
