<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = (string) input('form', 'settings');

    if ($form === 'logo') {
        if (!empty($_FILES['logo']['name'])) {
            $url = save_upload($_FILES['logo'], $CID, 'branding');
            if ($url) {
                db_exec('UPDATE companies SET logo = ? WHERE id = ?', [$url, $CID]);
                flash_set('success', 'Logo updated. It now appears on your public site.');
            } else {
                flash_set('error', 'Could not save the logo. Use a JPG/PNG/WEBP image up to 5 MB.');
            }
        } else {
            flash_set('error', 'Please choose an image to upload.');
        }
        redirect('/company-admin/settings.php');
    }

    if ($form === 'remove_logo') {
        db_exec('UPDATE companies SET logo = NULL WHERE id = ?', [$CID]);
        flash_set('success', 'Logo removed.');
        redirect('/company-admin/settings.php');
    }

    // Default: save the rest of the branding/contact settings.
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
    flash_set('success', 'Saved.');
    redirect('/company-admin/settings.php');
}

$company = db_one('SELECT * FROM companies WHERE id = ?', [$CID]);

ca_open('Branding & Settings');
?>

<!-- ===== Logo card (own form so tenants can drop a logo in one step) ===== -->
<div class="card" style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;">
  <div style="flex:0 0 140px;text-align:center;">
    <div style="width:140px;height:140px;border:2px dashed #d1d5db;border-radius:12px;
                display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= e($company['logo']) ?>" alt="Logo"
             style="max-width:100%;max-height:100%;object-fit:contain;">
      <?php else: ?>
        <span style="color:#9ca3af;font-size:13px;text-align:center;padding:8px;">No logo<br>uploaded yet</span>
      <?php endif; ?>
    </div>
  </div>
  <div style="flex:1 1 280px;min-width:240px">
    <h3 style="margin:0 0 6px;">Company Logo</h3>
    <p class="muted" style="margin:0 0 12px;">
      Your logo appears in the site header, footer, and admin. Use a square or
      transparent PNG / JPG / WEBP up to 5 MB. Recommended at least 256×256.
    </p>
    <form method="post" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="logo">
      <input type="file" name="logo" accept="image/*" required
             style="flex:1 1 240px;padding:8px;border:1px solid #d1d5db;border-radius:6px;background:#fff;">
      <button class="btn primary" type="submit">
        <?= !empty($company['logo']) ? 'Replace Logo' : 'Upload Logo' ?>
      </button>
      <?php if (!empty($company['logo'])): ?>
        <button class="btn outline" type="submit"
                formaction="/company-admin/settings.php"
                onclick="this.form.elements['form'].value='remove_logo';this.form.elements['logo'].removeAttribute('required');return confirm('Remove logo?');">
          Remove
        </button>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ===== Other settings ===== -->
<div class="card">
  <h3 style="margin:0 0 12px;">Branding & Contact</h3>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="settings">
    <div class="row">
      <div class="col">
        <label>Company Name</label>
        <input class="input" name="name" required value="<?= e($company['name']) ?>">
      </div>
      <div class="col">
        <label>Primary Color</label>
        <input class="input" type="color" name="theme_color" value="<?= e($company['theme_color']) ?>">
      </div>
      <div class="col">
        <label>Secondary Color</label>
        <input class="input" type="color" name="theme_secondary_color" value="<?= e($company['theme_secondary_color']) ?>">
      </div>
    </div>
    <label>Description (used on the homepage hero and About section)</label>
    <textarea class="input" name="description" rows="3"><?= e($company['description']) ?></textarea>
    <div class="row">
      <div class="col"><label>Phone</label>          <input class="input" name="phone" value="<?= e($company['phone']) ?>"></div>
      <div class="col"><label>Email</label>          <input class="input" name="email" value="<?= e($company['email']) ?>"></div>
      <div class="col"><label>WhatsApp Number</label><input class="input" name="whatsapp_number" placeholder="e.g. 60123456789" value="<?= e($company['whatsapp_number']) ?>"></div>
    </div>
    <label>Address</label><input class="input" name="address" value="<?= e($company['address']) ?>">
    <label>Operating Hours</label><input class="input" name="operating_hours" value="<?= e($company['operating_hours']) ?>">
    <label>Google Map Embed (optional company-wide map)</label>
    <textarea class="input" name="google_map_embed" rows="2"><?= e($company['google_map_embed']) ?></textarea>
    <p style="margin-top:14px"><button class="btn primary">Save Settings</button></p>
  </form>
</div>
<?php ca_close(); ?>
