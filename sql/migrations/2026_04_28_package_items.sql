-- Migration: package sections gain a 'kind' flag + a new
-- package_section_items table linking sections to products
-- so the retail price can be auto-computed from the catalog.

SET @db := DATABASE();

-- ----- package_sections.kind -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'package_sections' AND COLUMN_NAME = 'kind') = 0,
  "ALTER TABLE package_sections ADD COLUMN kind ENUM('included','choice') NOT NULL DEFAULT 'included' AFTER title",
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- package_section_items -----
CREATE TABLE IF NOT EXISTS package_section_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_id INT UNSIGNED NOT NULL,
  company_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_psi_section_product (section_id, product_id),
  KEY idx_psi_section (section_id),
  KEY idx_psi_company (company_id),
  KEY idx_psi_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
