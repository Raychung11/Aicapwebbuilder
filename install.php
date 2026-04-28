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
        ['branches', 'google_map_link', "VARCHAR(500) DEFAULT NULL AFTER google_map_embed"],
        ['branches', 'waze_link',       "VARCHAR(500) DEFAULT NULL AFTER google_map_link"],
        ['products', 'subcategory',     "VARCHAR(120) DEFAULT NULL AFTER category"],
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

    // Seed sample company
    $sampleSlug = 'ladore';
    if (!db_one('SELECT id FROM companies WHERE slug = ?', [$sampleSlug])) {
        $cid = db_insert(
            'INSERT INTO companies (name, slug, subdomain, theme_color, theme_secondary_color,
                                    description, whatsapp_number, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "active")',
            [
                'Ladore Furniture', $sampleSlug, $sampleSlug,
                '#1e3a8a', '#f59e0b',
                'Modern furniture for modern living.', '60123456789',
            ]
        );
        append($msgs, '✓ Sample company created: ladore.' . APP_BASE_DOMAIN . " (id={$cid})");

        $caEmail = 'owner@ladore.my';
        $caPass  = 'Owner@12345';
        db_insert(
            'INSERT INTO company_admins (company_id, name, email, password_hash, role)
             VALUES (?, ?, ?, ?, "owner")',
            [$cid, 'Ladore Owner', $caEmail, password_hash($caPass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])]
        );
        append($msgs, "✓ Company admin created: {$caEmail} / {$caPass}");
    } else {
        append($msgs, '• Sample company already exists.');
    }

    // Seed furniture catalog for the sample tenant (idempotent)
    require_once __DIR__ . '/inc/seed.php';
    $sampleRow = db_one('SELECT id FROM companies WHERE slug = ?', [$sampleSlug]);
    if ($sampleRow) {
        $r = seed_sample_products((int) $sampleRow['id']);
        if (!empty($r['skipped'])) {
            append($msgs, '• Sample products already present, skipped seeding.');
        } else {
            append($msgs, "✓ Seeded {$r['products']} furniture products + {$r['vouchers']} vouchers.");
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
