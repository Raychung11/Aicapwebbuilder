-- Migration: add sort_order to product_variants for position toggle.
-- Idempotent.
SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'sort_order') = 0,
  'ALTER TABLE product_variants ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER image',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'idx_pv_sort') = 0,
  'ALTER TABLE product_variants ADD INDEX idx_pv_sort (product_id, sort_order)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- One-off: backfill sort_order so existing rows keep their current visual order
-- and become reorder-able (10, 20, 30, …).
SET @rownum := 0;
UPDATE product_variants pv
JOIN (
  SELECT id, (@rownum := @rownum + 10) AS new_sort
    FROM product_variants
   ORDER BY product_id, id
) ordered ON ordered.id = pv.id
SET pv.sort_order = ordered.new_sort
WHERE pv.sort_order = 0;
