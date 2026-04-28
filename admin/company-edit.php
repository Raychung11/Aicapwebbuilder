<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

$id      = (int) input('id', 0);
$company = $id ? db_one('SELECT * FROM companies WHERE id = ?', [$id]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
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
<?php admin_layout_close(); ?>
