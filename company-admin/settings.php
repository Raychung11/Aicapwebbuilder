<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fields = [
        'name'                  => trim((string) input('name')),
        'theme_color'           => trim((string) input('theme_color',           '#111827')),
        'theme_secondary_color' => trim((string) input('theme_secondary_color', '#f59e0b')),
        'description'           => (string) input('description', ''),
        'address'               => (string) input('address',     ''),
        'phone'                 => (string) input('phone',       ''),
        'email'                 => (string) input('email',       ''),
        'whatsapp_number'       => (string) input('whatsapp_number',  ''),
        'google_map_embed'      => (string) input('google_map_embed', ''),
        'operating_hours'       => (string) input('operating_hours',  ''),
    ];
    db_exec(
        'UPDATE companies
            SET name=?, theme_color=?, theme_secondary_color=?, description=?, address=?,
                phone=?, email=?, whatsapp_number=?, google_map_embed=?, operating_hours=?
          WHERE id=?',
        [...array_values($fields), $CID]
    );
    if (!empty($_FILES['logo']['name'])) {
        $url = save_upload($_FILES['logo'], $CID, 'branding');
        if ($url) {
            db_exec('UPDATE companies SET logo = ? WHERE id = ?', [$url, $CID]);
        }
    }
    flash_set('success', 'Saved.');
    redirect('/company-admin/settings.php');
}

$company = db_one('SELECT * FROM companies WHERE id = ?', [$CID]);

ca_open('Branding & Settings');
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
      <div class="col">
        <label>Company Name</label>
        <input class="input" name="name" required value="<?= e($company['name']) ?>">
      </div>
      <div class="col">
        <label>Theme Color</label>
        <input class="input" name="theme_color" value="<?= e($company['theme_color']) ?>">
      </div>
      <div class="col">
        <label>Secondary Color</label>
        <input class="input" name="theme_secondary_color" value="<?= e($company['theme_secondary_color']) ?>">
      </div>
    </div>
    <label>Description</label>
    <textarea class="input" name="description" rows="3"><?= e($company['description']) ?></textarea>
    <div class="row">
      <div class="col"><label>Phone</label>          <input class="input" name="phone" value="<?= e($company['phone']) ?>"></div>
      <div class="col"><label>Email</label>          <input class="input" name="email" value="<?= e($company['email']) ?>"></div>
      <div class="col"><label>WhatsApp Number</label><input class="input" name="whatsapp_number" value="<?= e($company['whatsapp_number']) ?>"></div>
    </div>
    <label>Address</label><input class="input" name="address" value="<?= e($company['address']) ?>">
    <label>Operating Hours</label><input class="input" name="operating_hours" value="<?= e($company['operating_hours']) ?>">
    <label>Google Map Embed</label>
    <textarea class="input" name="google_map_embed" rows="2"><?= e($company['google_map_embed']) ?></textarea>
    <label>Logo</label>
    <input type="file" name="logo" accept="image/*">
    <?php if (!empty($company['logo'])): ?>
      <div style="margin-top:8px"><img src="<?= e($company['logo']) ?>" style="height:48px"></div>
    <?php endif; ?>
    <p style="margin-top:14px"><button class="btn primary">Save</button></p>
  </form>
</div>
<?php ca_close(); ?>
