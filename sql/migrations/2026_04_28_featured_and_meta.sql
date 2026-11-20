-- Migration: featured products + SEO meta tags. Idempotent.

SET @db := DATABASE();

-- ----- products.is_featured -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_featured') = 0,
  'ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_status',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_prod_featured') = 0,
  'ALTER TABLE products ADD INDEX idx_prod_featured (company_id, is_featured)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- products.meta_title / meta_description -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_title') = 0,
  'ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER is_featured',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_description') = 0,
  'ALTER TABLE products ADD COLUMN meta_description TEXT AFTER meta_title',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- companies.meta_title / meta_description / og_image -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'meta_title') = 0,
  'ALTER TABLE companies ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER operating_hours',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'meta_description') = 0,
  'ALTER TABLE companies ADD COLUMN meta_description TEXT AFTER meta_title',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'og_image') = 0,
  'ALTER TABLE companies ADD COLUMN og_image VARCHAR(255) DEFAULT NULL AFTER meta_description',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
