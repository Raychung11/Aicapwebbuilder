-- =====================================================================
-- Furniture BOS - Multi-Tenant SaaS Schema
-- Target: MySQL 5.7+ / 8.0
-- All tenant tables include company_id for strict isolation.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- companies
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  subdomain VARCHAR(120) NOT NULL,
  custom_domain VARCHAR(190) DEFAULT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  theme_color VARCHAR(20) DEFAULT '#111827',
  theme_secondary_color VARCHAR(20) DEFAULT '#f59e0b',
  description TEXT,
  address VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  whatsapp_number VARCHAR(40) DEFAULT NULL,
  google_map_embed TEXT,
  operating_hours VARCHAR(255) DEFAULT NULL,
  meta_title VARCHAR(255) DEFAULT NULL,
  meta_description TEXT,
  og_image VARCHAR(255) DEFAULT NULL,
  banner_image VARCHAR(255) DEFAULT NULL,
  banner_title VARCHAR(255) DEFAULT NULL,
  banner_subtitle TEXT,
  banner_cta_text VARCHAR(120) DEFAULT NULL,
  banner_cta_url VARCHAR(500) DEFAULT NULL,
  status ENUM('active','suspended','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_slug (slug),
  UNIQUE KEY uniq_subdomain (subdomain),
  UNIQUE KEY uniq_custom_domain (custom_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- company_admins (Company Admin role only; super admins live elsewhere)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS company_admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','manager','staff') NOT NULL DEFAULT 'manager',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_admin_email (email),
  KEY idx_admin_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- super_admins (HQ / AICAP)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS super_admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_super_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- branches
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  whatsapp_number VARCHAR(40) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  google_map_embed TEXT,
  google_map_link VARCHAR(500) DEFAULT NULL,
  waze_link VARCHAR(500) DEFAULT NULL,
  operating_hours VARCHAR(255) DEFAULT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_branch_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- branch_images
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branch_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_branch_img_company (company_id),
  KEY idx_branch_img_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- salespersons
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS salespersons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  whatsapp_number VARCHAR(40) DEFAULT NULL,
  role VARCHAR(80) DEFAULT NULL,
  referral_code VARCHAR(40) DEFAULT NULL,
  commission_rate DECIMAL(5,2) DEFAULT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sp_company (company_id),
  KEY idx_sp_branch (branch_id),
  UNIQUE KEY uniq_sp_refcode (company_id, referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  category VARCHAR(120) DEFAULT NULL,
  subcategory VARCHAR(120) DEFAULT NULL,
  description TEXT,
  price_min DECIMAL(12,2) DEFAULT NULL,
  price_max DECIMAL(12,2) DEFAULT NULL,
  stock_status ENUM('in_stock','out_of_stock','preorder') NOT NULL DEFAULT 'in_stock',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(255) DEFAULT NULL,
  meta_description TEXT,
  status ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_company_slug (company_id, slug),
  KEY idx_prod_company (company_id),
  KEY idx_prod_category (company_id, category),
  KEY idx_prod_subcategory (company_id, category, subcategory),
  KEY idx_prod_featured (company_id, is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- product_images
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pi_company (company_id),
  KEY idx_pi_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- product_variants
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_variants (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  variant_name VARCHAR(150) NOT NULL,
  color VARCHAR(80) DEFAULT NULL,
  material VARCHAR(120) DEFAULT NULL,
  size VARCHAR(80) DEFAULT NULL,
  price DECIMAL(12,2) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pv_company (company_id),
  KEY idx_pv_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- members  (cross-company; phone+email globally unique)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_member_phone (phone),
  UNIQUE KEY uniq_member_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- vouchers
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vouchers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT,
  marketing_script TEXT,
  type ENUM('percent','fixed','gift','freebie') NOT NULL DEFAULT 'percent',
  value DECIMAL(12,2) DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  per_member_limit INT DEFAULT 1,
  redemption_method ENUM('qr','code','manual') NOT NULL DEFAULT 'code',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_vou_company (company_id),
  KEY idx_vou_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- voucher_claims
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS voucher_claims (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  voucher_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  salesperson_id INT UNSIGNED DEFAULT NULL,
  status ENUM('claimed','redeemed','expired','void') NOT NULL DEFAULT 'claimed',
  voucher_code VARCHAR(40) NOT NULL,
  claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  redeemed_at DATETIME DEFAULT NULL,
  redeemed_branch_id INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_voucher_code (voucher_code),
  KEY idx_vc_company (company_id),
  KEY idx_vc_voucher (voucher_id),
  KEY idx_vc_member (member_id),
  KEY idx_vc_salesperson (salesperson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- campaigns
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaigns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  campaign_type ENUM('qr','flyer','social','event','other') NOT NULL DEFAULT 'qr',
  qr_slug VARCHAR(120) NOT NULL,
  target_url VARCHAR(500) NOT NULL,
  status ENUM('active','paused','ended') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_campaign_slug (company_id, qr_slug),
  KEY idx_camp_company (company_id),
  KEY idx_camp_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- campaign_scans
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaign_scans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  campaign_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cs_company (company_id),
  KEY idx_cs_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- leads
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED DEFAULT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  member_id INT UNSIGNED DEFAULT NULL,
  campaign_id INT UNSIGNED DEFAULT NULL,
  salesperson_id INT UNSIGNED DEFAULT NULL,
  customer_name VARCHAR(150) DEFAULT NULL,
  customer_phone VARCHAR(40) DEFAULT NULL,
  source ENUM('whatsapp_click','voucher_claim','campaign_scan','contact_form','other') NOT NULL DEFAULT 'other',
  status ENUM('new','contacted','converted','closed','lost') NOT NULL DEFAULT 'new',
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_company (company_id),
  KEY idx_lead_branch (branch_id),
  KEY idx_lead_product (product_id),
  KEY idx_lead_campaign (campaign_id),
  KEY idx_lead_salesperson (salesperson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- analytics_events
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS analytics_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  event_type VARCHAR(60) NOT NULL,
  entity_type VARCHAR(60) DEFAULT NULL,
  entity_id INT UNSIGNED DEFAULT NULL,
  campaign_id INT UNSIGNED DEFAULT NULL,
  member_id INT UNSIGNED DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ae_company (company_id),
  KEY idx_ae_event (company_id, event_type),
  KEY idx_ae_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- media_library
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS media_library (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(60) DEFAULT NULL,
  file_size INT UNSIGNED DEFAULT NULL,
  uploaded_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ml_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- company_pages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS company_pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_page_slug (company_id, slug),
  KEY idx_page_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- company_page_blocks
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS company_page_blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  page_id INT UNSIGNED NOT NULL,
  block_type VARCHAR(60) NOT NULL,
  content_json LONGTEXT,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pb_company (company_id),
  KEY idx_pb_page (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- page_templates  (HQ-managed; reusable)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  template_key VARCHAR(80) NOT NULL,
  layout_json LONGTEXT,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_tpl_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- partner_inquiries  (subscribe / partner / licensing leads from aicap.my)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partner_inquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  type ENUM('subscribe','partner','licensing','general') NOT NULL DEFAULT 'subscribe',
  company_name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  brand_name VARCHAR(150) DEFAULT NULL,
  branches_count VARCHAR(40) DEFAULT NULL,
  message TEXT,
  status ENUM('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inq_status (status),
  KEY idx_inq_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- password_resets  (forgot-password tokens for super_admin / company_admin / member)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('super_admin','company_admin','member') NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_pr_token (token),
  KEY idx_pr_email (email),
  KEY idx_pr_user (role, user_id),
  KEY idx_pr_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- platform_settings  (HQ-level key/value store: AI provider, API keys, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS platform_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ps_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Furniture packages (curated room bundles like Krafmen's "2 rooms
-- fully furnished" deals). Each package has sections (Master Room,
-- Living, Dining, TV Cabinet…) and each section can offer choices
-- (multiple sofa designs, multiple TV cabinet designs…).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) DEFAULT NULL,
  subtitle VARCHAR(190) DEFAULT NULL,
  description TEXT,
  hero_image VARCHAR(255) DEFAULT NULL,
  badge VARCHAR(80) DEFAULT NULL,
  price DECIMAL(12,2) DEFAULT NULL,
  was_price DECIMAL(12,2) DEFAULT NULL,
  features_json TEXT,
  pwp_blurb TEXT,
  cta_text VARCHAR(120) DEFAULT NULL,
  cta_url VARCHAR(500) DEFAULT NULL,
  meta_title VARCHAR(255) DEFAULT NULL,
  meta_description TEXT,
  status ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_pkg_slug (company_id, slug),
  KEY idx_pkg_company (company_id),
  KEY idx_pkg_status (company_id, status),
  KEY idx_pkg_featured (company_id, is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS package_sections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  package_id INT UNSIGNED NOT NULL,
  company_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  kind ENUM('included','choice') NOT NULL DEFAULT 'included',
  description TEXT,
  image VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pkgsec_package (package_id),
  KEY idx_pkgsec_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS package_choices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_id INT UNSIGNED NOT NULL,
  company_id INT UNSIGNED NOT NULL,
  label VARCHAR(150) DEFAULT NULL,
  image VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pkgch_section (section_id),
  KEY idx_pkgch_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Run install.php once after importing this schema to seed the
-- super admin account and a sample company with proper bcrypt hashes.
