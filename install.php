<?php
/**
 * One-time installer.
 *
 *  1. Confirms DB connection.
 *  2. Loads sql/schema.sql.
 *  3. Seeds a super admin and a sample company + company admin.
 *
 * Visit /install.php once, then DELETE this file.
 */
require_once __DIR__ . '/inc/db.php';

$msgs = [];
$err  = null;

function append(&$msgs, $line) { $msgs[] = $line; }

try {
    db();
    append($msgs, '✓ Database connection OK.');

    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    if (!$sql) {
        throw new RuntimeException('Cannot read sql/schema.sql');
    }
    // PDO can't run multi-statement strings as one prepare, but exec() can.
    db()->exec($sql);
    append($msgs, '✓ Schema applied.');

    // ----- Idempotent column adds (run safely on existing DBs) -----
    $columns_to_add = [
        ['branches',  'google_map_link',  "VARCHAR(500) DEFAULT NULL AFTER google_map_embed"],
        ['branches',  'waze_link',        "VARCHAR(500) DEFAULT NULL AFTER google_map_link"],
        ['products',  'subcategory',      "VARCHAR(120) DEFAULT NULL AFTER category"],
        ['products',  'is_featured',      "TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_status"],
        ['products',  'meta_title',       "VARCHAR(255) DEFAULT NULL AFTER is_featured"],
        ['products',  'meta_description', "TEXT AFTER meta_title"],
        ['companies', 'meta_title',       "VARCHAR(255) DEFAULT NULL AFTER operating_hours"],
        ['companies', 'meta_description', "TEXT AFTER meta_title"],
        ['companies', 'og_image',         "VARCHAR(255) DEFAULT NULL AFTER meta_description"],
        ['companies', 'banner_image',     "VARCHAR(255) DEFAULT NULL AFTER og_image"],
        ['companies', 'banner_title',     "VARCHAR(255) DEFAULT NULL AFTER banner_image"],
        ['companies', 'banner_subtitle',  "TEXT AFTER banner_title"],
        ['companies', 'banner_cta_text',  "VARCHAR(120) DEFAULT NULL AFTER banner_subtitle"],
        ['companies', 'banner_cta_url',   "VARCHAR(500) DEFAULT NULL AFTER banner_cta_text"],
        ['salespersons',   'referral_code',   "VARCHAR(40) DEFAULT NULL AFTER role"],
        ['salespersons',   'commission_rate', "DECIMAL(5,2) DEFAULT NULL AFTER referral_code"],
        ['voucher_claims', 'salesperson_id',  "INT UNSIGNED DEFAULT NULL AFTER member_id"],
        ['vouchers',       'marketing_script',"TEXT AFTER description"],
        ['package_sections','kind',           "ENUM('included','choice') NOT NULL DEFAULT 'included' AFTER title"],
    ];
    foreach ($columns_to_add as [$tbl, $col, $type]) {
        $exists = db_one(
            'SELECT 1 AS x FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB_NAME, $tbl, $col]
        );
        if (!$exists) {
            db()->exec("ALTER TABLE {$tbl} ADD COLUMN {$col} {$type}");
            append($msgs, "✓ Added column {$tbl}.{$col}.");
        }
    }

    // Seed super admin
    $superEmail = 'admin@aicap.my';
    $superPass  = 'Admin@12345';
    if (!db_one('SELECT id FROM super_admins WHERE email = ?', [$superEmail])) {
        db_insert(
            'INSERT INTO super_admins (name, email, password_hash) VALUES (?, ?, ?)',
            ['AICAP HQ', $superEmail, password_hash($superPass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])]
        );
        append($msgs, "✓ Super admin created: {$superEmail} / {$superPass}");
    } else {
        append($msgs, "• Super admin already exists ({$superEmail}).");
    }

    // ----- Seed two sample tenants (idempotent) -----
    require_once __DIR__ . '/inc/seed.php';
    foreach (sample_company_profiles() as $profile) {
        $r = seed_sample_company($profile, PASSWORD_COST);
        $base = APP_BASE_DOMAIN;
        if ($r['created']) {
            append($msgs, "✓ Sample company created: {$profile['name']} ({$profile['slug']}.{$base})");
            append($msgs, "  ↳ admin: {$profile['admin']['email']} / {$profile['admin']['pass']}");
        } else {
            append($msgs, "• Sample company already exists: {$profile['name']}");
        }
        if ($r['products'] > 0) {
            append($msgs, "  ↳ seeded {$r['products']} products + {$r['vouchers']} vouchers");
        }
    }

    // Make sure /uploads is writable
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    append($msgs, is_writable(UPLOAD_DIR)
        ? '✓ /uploads is writable.'
        : '! /uploads is NOT writable. Fix permissions to allow image uploads.');

} catch (Throwable $e) {
    $err = $e->getMessage();
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Install</title>
<style>body{font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f3f4f6;padding:30px;max-width:780px;margin:auto}
.card{background:#fff;padding:22px;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
pre{background:#0b1020;color:#cbd5e1;padding:14px;border-radius:8px;overflow:auto}
.err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px}
h1{margin:0 0 14px}
</style></head>
<body>
<div class="card">
<h1>Furniture BOS Installer</h1>
<?php if ($err): ?>
  <div class="err"><strong>Error:</strong> <?= htmlspecialchars($err) ?></div>
<?php else: ?>
  <pre><?php foreach ($msgs as $m) echo htmlspecialchars($m), "\n"; ?></pre>
  <p><strong>Important:</strong> delete <code>install.php</code> from the server now.</p>
  <p><a href="/admin/login.php">Super Admin Login</a> &middot;
     <a href="/company-admin/login.php">Company Admin Login</a></p>
<?php endif; ?>
</div>
</body></html>
