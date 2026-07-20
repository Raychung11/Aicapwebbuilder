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

    if ($form === 'seo') {
        if (!empty($_FILES['og_image']['name'])) {
            $url = save_upload($_FILES['og_image'], $CID, 'branding');
            if ($url) {
                db_exec('UPDATE companies SET og_image = ? WHERE id = ?', [$url, $CID]);
            }
        }
        $meta = [
            'meta_title'       => trim((string) input('meta_title', '')) ?: null,
            'meta_description' => trim((string) input('meta_description', '')) ?: null,
        ];
        db_exec('UPDATE companies SET meta_title = ?, meta_description = ? WHERE id = ?',
                [...array_values($meta), $CID]);
        flash_set('success', 'SEO settings saved.');
        redirect('/company-admin/settings.php');
    }

    if ($form === 'remove_og_image') {
        db_exec('UPDATE companies SET og_image = NULL WHERE id = ?', [$CID]);
        flash_set('success', 'Social share image removed.');
        redirect('/company-admin/settings.php');
    }

    if ($form === 'banner') {
        if (!empty($_FILES['banner_image']['name'])) {
            $url = save_upload($_FILES['banner_image'], $CID, 'banner');
            if ($url) {
                db_exec('UPDATE companies SET banner_image = ? WHERE id = ?', [$url, $CID]);
            } else {
                flash_set('error', 'Could not save the banner image. Use a JPG/PNG/WEBP up to 5 MB.');
                redirect('/company-admin/settings.php');
            }
        }
        $b = [
            'banner_title'    => trim((string) input('banner_title', '')) ?: null,
            'banner_subtitle' => trim((string) input('banner_subtitle', '')) ?: null,
            'banner_cta_text' => trim((string) input('banner_cta_text', '')) ?: null,
            'banner_cta_url'  => trim((string) input('banner_cta_url', '')) ?: null,
        ];
        db_exec(
            'UPDATE companies SET banner_title=?, banner_subtitle=?, banner_cta_text=?, banner_cta_url=? WHERE id=?',
            [...array_values($b), $CID]
        );
        flash_set('success', 'Marketing banner saved.');
        redirect('/company-admin/settings.php');
    }

    if ($form === 'remove_banner_image') {
        db_exec('UPDATE companies SET banner_image = NULL WHERE id = ?', [$CID]);
        flash_set('success', 'Banner image removed.');
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

<!-- ===== Marketing banner ===== -->
<div class="card">
  <h3 style="margin:0 0 4px;">Marketing Banner</h3>
  <p class="muted" style="margin:0 0 14px;">Hero banner shown at the top of your homepage. Each field is optional — leave blank to use sensible defaults (company name, description, "Browse Catalog" button).</p>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="banner">

    <div class="row" style="align-items:flex-start;">
      <div class="col" style="flex:0 0 220px;">
        <label>Banner image</label>
        <div style="width:200px;height:120px;border:2px dashed #d1d5db;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
          <?php if (!empty($company['banner_image'])): ?>
            <img src="<?= e($company['banner_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span class="muted" style="font-size:12px;text-align:center;padding:8px;">No banner uploaded</span>
          <?php endif; ?>
        </div>
        <input type="file" name="banner_image" accept="image/*" style="margin-top:8px;font-size:13px;">
        <p class="muted" style="margin-top:6px;font-size:12px;">Recommended 1600 × 600 px landscape. JPG/PNG/WEBP up to 5 MB. A dark overlay is added automatically for text contrast.</p>
        <?php if (!empty($company['banner_image'])): ?>
          <button class="btn outline" type="submit" style="margin-top:6px;font-size:12px;padding:5px 10px;"
                  onclick="this.form.elements['form'].value='remove_banner_image';return confirm('Remove banner image?');">
            Remove banner
          </button>
        <?php endif; ?>
      </div>
      <div class="col">
        <label>Banner title <span class="muted">(falls back to company name)</span></label>
        <input class="input" name="banner_title" maxlength="255"
               placeholder="<?= e($company['name']) ?>"
               value="<?= e($company['banner_title'] ?? '') ?>">

        <label>Banner subtitle <span class="muted">(falls back to description)</span></label>
        <textarea class="input" name="banner_subtitle" rows="2" maxlength="500"
                  placeholder="<?= e(mb_substr($company['description'] ?? '', 0, 140)) ?>"><?= e($company['banner_subtitle'] ?? '') ?></textarea>

        <div class="row">
          <div class="col">
            <label>CTA button text</label>
            <input class="input" name="banner_cta_text" maxlength="120"
                   placeholder="Browse Catalog"
                   value="<?= e($company['banner_cta_text'] ?? '') ?>">
          </div>
          <div class="col">
            <label>CTA button link</label>
            <input class="input" name="banner_cta_url" maxlength="500"
                   placeholder="/catalog.php"
                   value="<?= e($company['banner_cta_url'] ?? '') ?>">
            <p class="muted" style="margin-top:4px;font-size:12px;">e.g. <code>/catalog.php</code>, <code>/voucher.php</code>, <code>/visit.php</code> or any URL.</p>
          </div>
        </div>
      </div>
    </div>

    <p style="margin-top:14px"><button class="btn primary">Save Banner</button></p>
  </form>
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

<!-- ===== SEO meta tags ===== -->
<div class="card">
  <h3 style="margin:0 0 4px;">SEO &amp; Social Sharing</h3>
  <p class="muted" style="margin:0 0 14px;">Controls what shows in Google results and on social previews (WhatsApp, Facebook, Twitter, LinkedIn).</p>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="seo">

    <div class="row">
      <div class="col">
        <label>Meta Title <span class="muted">(falls back to company name; aim for ~60 chars)</span></label>
        <input class="input" name="meta_title" maxlength="255"
               placeholder="<?= e($company['name']) ?>"
               value="<?= e($company['meta_title'] ?? '') ?>">
      </div>
    </div>

    <label>Meta Description <span class="muted">(aim for 120–160 chars)</span></label>
    <textarea class="input" name="meta_description" rows="3" maxlength="500"
              placeholder="<?= e(mb_substr($company['description'] ?? '', 0, 160)) ?>"><?= e($company['meta_description'] ?? '') ?></textarea>

    <div class="row" style="margin-top:14px;align-items:flex-start;">
      <div class="col" style="flex:0 0 180px;">
        <label>Social share image</label>
        <div style="width:160px;height:90px;border:2px dashed #d1d5db;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
          <?php if (!empty($company['og_image'])): ?>
            <img src="<?= e($company['og_image']) ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain;">
          <?php elseif (!empty($company['logo'])): ?>
            <img src="<?= e($company['logo']) ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain;opacity:.6;">
          <?php else: ?>
            <span class="muted" style="font-size:12px">No image</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col">
        <label>Upload (optional)</label>
        <input type="file" name="og_image" accept="image/*">
        <p class="muted" style="margin-top:6px;font-size:12px;">
          Recommended 1200 × 630 px. If empty, your logo is used. Used by
          WhatsApp / Facebook / Twitter / LinkedIn link previews.
        </p>
        <?php if (!empty($company['og_image'])): ?>
          <button class="btn outline" type="submit" formaction="/company-admin/settings.php"
                  onclick="this.form.elements['form'].value='remove_og_image';return confirm('Remove social share image?');">
            Remove image
          </button>
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:14px"><button class="btn primary">Save SEO</button></p>
  </form>
</div>
<?php ca_close(); ?>
