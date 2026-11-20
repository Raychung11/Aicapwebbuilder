<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

// Safety net for partial deploys
if (!function_exists('db_table_exists')) {
    function db_table_exists(string $name): bool {
        try {
            $row = db_one(
                'SELECT 1 AS x FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$name]
            );
            return (bool) $row;
        } catch (Throwable $e) { return false; }
    }
}

$id      = (int) input('id', 0);
$company = $id ? db_one('SELECT * FROM companies WHERE id = ?', [$id]) : null;
$action  = (string) input('action', '');
$has_cdomains = db_table_exists('company_domains');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ---- Multi-domain actions ----
    if ($id && $has_cdomains && in_array($action, ['add_domain','delete_domain','set_primary'], true)) {
        if ($action === 'add_domain') {
            $domain = strtolower(trim((string) input('new_domain', '')));
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#/.*$#', '', $domain);
            $is_primary = !empty($_POST['new_is_primary']) ? 1 : 0;
            if ($domain && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
                if ($is_primary) {
                    db_exec('UPDATE company_domains SET is_primary = 0 WHERE company_id = ?', [$id]);
                }
                try {
                    db_insert(
                        'INSERT INTO company_domains (company_id, domain, is_primary) VALUES (?, ?, ?)',
                        [$id, $domain, $is_primary]
                    );
                    flash_set('success', 'Domain added.');
                } catch (Throwable $e) {
                    flash_set('error', 'Could not add — that domain is already in use by another tenant.');
                }
            } else {
                flash_set('error', 'Invalid domain format. Use just the hostname, e.g. example.com.');
            }
        } elseif ($action === 'delete_domain') {
            $did = (int) input('domain_id', 0);
            if ($did) {
                db_exec('DELETE FROM company_domains WHERE id = ? AND company_id = ?', [$did, $id]);
                flash_set('success', 'Domain removed.');
            }
        } elseif ($action === 'set_primary') {
            $did = (int) input('domain_id', 0);
            if ($did) {
                db_exec('UPDATE company_domains SET is_primary = 0 WHERE company_id = ?', [$id]);
                db_exec('UPDATE company_domains SET is_primary = 1 WHERE id = ? AND company_id = ?', [$did, $id]);
                flash_set('success', 'Primary domain updated.');
            }
        }
        redirect('/admin/company-edit.php?id=' . $id);
    }

    // ---- Standard save ----
    $f = $_POST;
    $fields = [
        'name'                  => trim((string) $f['name']),
        'slug'                  => slugify((string) $f['slug']),
        'subdomain'             => slugify((string) $f['subdomain']),
        'custom_domain'         => trim((string) ($f['custom_domain'] ?? '')) ?: null,
        'theme_color'           => trim((string) ($f['theme_color']           ?? '#111827')),
        'theme_secondary_color' => trim((string) ($f['theme_secondary_color'] ?? '#f59e0b')),
        'description'           => (string) ($f['description'] ?? ''),
        'address'               => (string) ($f['address']     ?? ''),
        'phone'                 => (string) ($f['phone']       ?? ''),
        'email'                 => (string) ($f['email']       ?? ''),
        'whatsapp_number'       => (string) ($f['whatsapp_number']       ?? ''),
        'google_map_embed'      => (string) ($f['google_map_embed']      ?? ''),
        'operating_hours'       => (string) ($f['operating_hours']       ?? ''),
        'status'                => in_array($f['status'] ?? '', ['active','suspended','disabled'], true)
                                    ? $f['status'] : 'active',
    ];

    if ($company) {
        $sql = 'UPDATE companies SET name=?, slug=?, subdomain=?, custom_domain=?,
                  theme_color=?, theme_secondary_color=?, description=?, address=?, phone=?,
                  email=?, whatsapp_number=?, google_map_embed=?, operating_hours=?, status=?
                WHERE id=?';
        db_exec($sql, [...array_values($fields), $id]);
    } else {
        $sql = 'INSERT INTO companies
                  (name, slug, subdomain, custom_domain, theme_color, theme_secondary_color,
                   description, address, phone, email, whatsapp_number, google_map_embed,
                   operating_hours, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $id = db_insert($sql, array_values($fields));
    }

    if (!empty($_FILES['logo']['name'])) {
        $url = save_upload($_FILES['logo'], $id, 'branding');
        if ($url) {
            db_exec('UPDATE companies SET logo = ? WHERE id = ?', [$url, $id]);
        }
    }

    flash_set('success', 'Company saved.');
    redirect('/admin/company-edit.php?id=' . $id);
}

// Fetch domain list for the sidebar
$domains = [];
if ($id && $has_cdomains) {
    $domains = db_all(
        'SELECT * FROM company_domains WHERE company_id = ? ORDER BY is_primary DESC, domain ASC',
        [$id]
    );
}

admin_layout_open($company ? 'Edit Company' : 'Add Company');
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
      <div class="col">
        <label>Name</label>
        <input class="input" name="name" required value="<?= e($company['name'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Slug (used in /q/ links)</label>
        <input class="input" name="slug" required value="<?= e($company['slug'] ?? '') ?>">
      </div>
    </div>
    <div class="row">
      <div class="col">
        <label>Subdomain</label>
        <input class="input" name="subdomain" required value="<?= e($company['subdomain'] ?? '') ?>">
        <div class="muted">Will be available at <code>{subdomain}.<?= e(APP_BASE_DOMAIN) ?></code></div>
      </div>
      <div class="col">
        <label>Custom Domain (optional)</label>
        <input class="input" name="custom_domain" value="<?= e($company['custom_domain'] ?? '') ?>">
      </div>
    </div>
    <div class="row">
      <div class="col">
        <label>Theme Color</label>
        <input class="input" name="theme_color" value="<?= e($company['theme_color'] ?? '#111827') ?>">
      </div>
      <div class="col">
        <label>Secondary Color</label>
        <input class="input" name="theme_secondary_color" value="<?= e($company['theme_secondary_color'] ?? '#f59e0b') ?>">
      </div>
      <div class="col">
        <label>Status</label>
        <select class="input" name="status">
          <?php foreach (['active','suspended','disabled'] as $s): ?>
            <option <?= ($company['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Description</label>
    <textarea class="input" name="description" rows="3"><?= e($company['description'] ?? '') ?></textarea>
    <div class="row">
      <div class="col"><label>Phone</label>          <input class="input" name="phone" value="<?= e($company['phone'] ?? '') ?>"></div>
      <div class="col"><label>Email</label>          <input class="input" name="email" value="<?= e($company['email'] ?? '') ?>"></div>
      <div class="col"><label>WhatsApp Number</label><input class="input" name="whatsapp_number" value="<?= e($company['whatsapp_number'] ?? '') ?>"></div>
    </div>
    <label>Address</label>
    <input class="input" name="address" value="<?= e($company['address'] ?? '') ?>">
    <label>Operating Hours</label>
    <input class="input" name="operating_hours" value="<?= e($company['operating_hours'] ?? '') ?>">
    <label>Google Map Embed (iframe HTML)</label>
    <textarea class="input" name="google_map_embed" rows="2"><?= e($company['google_map_embed'] ?? '') ?></textarea>
    <label>Logo</label>
    <input type="file" name="logo" accept="image/*">
    <?php if (!empty($company['logo'])): ?>
      <div style="margin-top:8px"><img src="<?= e($company['logo']) ?>" style="height:48px"></div>
    <?php endif; ?>
    <p style="margin-top:14px">
      <button class="btn primary" type="submit">Save</button>
      <a class="btn outline" href="/admin/companies.php">Back</a>
    </p>
  </form>
</div>

<?php if ($id && $has_cdomains): ?>
<div class="card">
  <h3 style="margin:0 0 6px;">🌐 Custom domains</h3>
  <p class="muted" style="margin:0 0 14px;font-size:13px;">
    Add all extra hostnames that should serve this tenant. The primary domain
    is used for canonical URLs. The subdomain <code><?= e($company['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></code>
    always works as a fallback.
  </p>

  <?php if ($domains): ?>
    <table style="margin-bottom:16px;">
      <tr>
        <th>Domain</th>
        <th style="width:120px;">Primary</th>
        <th style="width:220px;"></th>
      </tr>
      <?php foreach ($domains as $d): ?>
        <tr>
          <td>
            <a href="<?= e(APP_URL_SCHEME) ?>://<?= e($d['domain']) ?>" target="_blank" rel="noopener">
              <?= e($d['domain']) ?>
            </a>
          </td>
          <td>
            <?php if ($d['is_primary']): ?>
              <span class="badge green">★ Primary</span>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <?php if (!$d['is_primary']): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="set_primary">
                <input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>">
                <button class="btn outline" type="submit" style="padding:4px 10px;font-size:12px;">Make primary</button>
              </form>
            <?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Remove <?= e($d['domain']) ?> from this tenant?')">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="action" value="delete_domain">
              <input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>">
              <button class="btn danger" type="submit" style="padding:4px 10px;font-size:12px;">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="muted" style="margin:0 0 14px;">No extra domains yet. Add one below.</p>
  <?php endif; ?>

  <form method="post" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="action" value="add_domain">
    <div style="flex:1;min-width:220px;">
      <label>Domain to add</label>
      <input class="input" name="new_domain" required
             placeholder="e.g. ladaza.my (no https://, no path)">
    </div>
    <label style="display:flex;align-items:center;gap:6px;padding:9px 0;font-size:13px;">
      <input type="checkbox" name="new_is_primary" value="1"
             <?= empty($domains) ? 'checked' : '' ?>>
      Set as primary
    </label>
    <button class="btn primary" type="submit">+ Add domain</button>
  </form>

  <p class="muted" style="margin-top:14px;font-size:12px;">
    Before a domain works: point its DNS to Hostinger, add it as an addon/parked
    domain in hPanel pointing to the <code><?= e($company['subdomain']) ?></code>
    folder, and install Let's Encrypt SSL for it.
  </p>
</div>
<?php elseif ($id && !$has_cdomains): ?>
<div class="card">
  <p class="muted" style="margin:0;">
    💡 Multi-domain support requires the <code>company_domains</code> table.
    Visit <a href="/install.php">/install.php</a> once to create it, then reload this page.
  </p>
</div>
<?php endif; ?>

<?php admin_layout_close(); ?>
