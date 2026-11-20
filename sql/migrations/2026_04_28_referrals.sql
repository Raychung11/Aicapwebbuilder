-- Migration: agent referral tracking. Idempotent.
SET @db := DATABASE();

-- ----- salespersons.referral_code -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'salespersons' AND COLUMN_NAME = 'referral_code') = 0,
  'ALTER TABLE salespersons ADD COLUMN referral_code VARCHAR(40) DEFAULT NULL AFTER role',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- salespersons.commission_rate -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'salespersons' AND COLUMN_NAME = 'commission_rate') = 0,
  'ALTER TABLE salespersons ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT NULL AFTER referral_code',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- unique index on referral_code per company -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'salespersons' AND INDEX_NAME = 'uniq_sp_refcode') = 0,
  'ALTER TABLE salespersons ADD UNIQUE KEY uniq_sp_refcode (company_id, referral_code)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----- voucher_claims.salesperson_id -----
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'voucher_claims' AND COLUMN_NAME = 'salesperson_id') = 0,
  'ALTER TABLE voucher_claims ADD COLUMN salesperson_id INT UNSIGNED DEFAULT NULL AFTER member_id',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'voucher_claims' AND INDEX_NAME = 'idx_vc_salesperson') = 0,
  'ALTER TABLE voucher_claims ADD INDEX idx_vc_salesperson (salesperson_id)',
  'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
