<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

$action = (string) input('action', '');
$id     = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($action === 'delete' && $id) {
        tenant_row_or_404('branches', $id);
        // Delete branch images from disk + DB
        $imgs = db_all('SELECT * FROM branch_images WHERE company_id = ? AND branch_id = ?', [$CID, $id]);
        foreach ($imgs as $im) {
            $abs = __DIR__ . '/..' . $im['image_path'];
            if (is_file($abs)) @unlink($abs);
        }
        db_exec('DELETE FROM branch_images WHERE company_id = ? AND branch_id = ?', [$CID, $id]);
        db_exec('DELETE FROM branches WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Branch deleted.');
        redirect('/company-admin/branches.php');
    }

    if ($action === 'delete_image') {
        $iid = (int) input('image_id', 0);
        $im  = db_one('SELECT * FROM branch_images WHERE company_id = ? AND id = ?', [$CID, $iid]);
        if ($im) {
            db_exec('DELETE FROM branch_images WHERE company_id = ? AND id = ?', [$CID, $iid]);
            $abs = __DIR__ . '/..' . $im['image_path'];
            if (is_file($abs)) @unlink($abs);
            flash_set('success', 'Image deleted.');
        }
        redirect('/company-admin/branches.php?id=' . (int) $im['branch_id']);
    }

    if ($action === 'upload_images' && $id) {
        tenant_row_or_404('branches', $id);
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $i => $_n) {
                $file = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                ];
                $url = save_upload($file, $CID, 'branches');
                if ($url) {
                    $isPrimary = db_one(
                        'SELECT COUNT(*) c FROM branch_images WHERE company_id = ? AND branch_id = ?',
                        [$CID, $id]
                    )['c'] == 0 ? 1 : 0;
                    db_insert(
                        'INSERT INTO branch_images (company_id, branch_id, image_path, is_primary, sort_order)
                         VALUES (?, ?, ?, ?, ?)',
                        [$CID, $id, $url, $isPrimary, $i]
                    );
                }
            }
            flash_set('success', 'Images uploaded.');
        }
        redirect('/company-admin/branches.php?id=' . $id);
    }
    $f = [
        'name'             => trim((string) input('name')),
        'address'          => (string) input('address', ''),
        'phone'            => (string) input('phone', ''),
        'whatsapp_number'  => (string) input('whatsapp_number', ''),
        'email'            => (string) input('email', ''),
        'google_map_embed' => (string) input('google_map_embed', ''),
        'google_map_link'  => trim((string) input('google_map_link', '')),
        'waze_link'        => trim((string) input('waze_link', '')),
        'operating_hours'  => (string) input('operating_hours', ''),
        'status'           => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('branches', $id);
        db_exec(
            'UPDATE branches SET name=?, address=?, phone=?, whatsapp_number=?, email=?,
                                 google_map_embed=?, google_map_link=?, waze_link=?,
                                 operating_hours=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO branches (company_id, name, address, phone, whatsapp_number, email,
                                   google_map_embed, google_map_link, waze_link,
                                   operating_hours, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Branch saved.');
    redirect('/company-admin/branches.php');
}

$editing  = $id ? tenant_row_or_404('branches', $id) : null;
$branches = tenant_all('SELECT * FROM branches WHERE company_id = ? ORDER BY id DESC', $CID);

ca_open('Branches');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Branch' : 'Add Branch' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Phone</label><input class="input" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
      <div class="col"><label>WhatsApp</label><input class="input" name="whatsapp_number" value="<?= e($editing['whatsapp_number'] ?? '') ?>"></div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <option <?= ($editing['status'] ?? '')==='active'?'selected':'' ?>>active</option>
          <option <?= ($editing['status'] ?? '')==='disabled'?'selected':'' ?>>disabled</option>
        </select>
      </div>
    </div>
    <label>Address</label><input class="input" name="address" value="<?= e($editing['address'] ?? '') ?>">
    <div class="row">
      <div class="col"><label>Email</label><input class="input" name="email" value="<?= e($editing['email'] ?? '') ?>"></div>
      <div class="col"><label>Operating Hours</label><input class="input" name="operating_hours" value="<?= e($editing['operating_hours'] ?? '') ?>"></div>
    </div>
    <label>Google Map Embed (full <code>&lt;iframe&gt;</code> code from Google Maps → Share → Embed a map)</label>
    <textarea class="input" name="google_map_embed" rows="2" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?= e($editing['google_map_embed'] ?? '') ?></textarea>
    <div class="row">
      <div class="col">
        <label>Google Maps Link <span class="muted">(for the "Open in Google Maps" button)</span></label>
        <input class="input" name="google_map_link" placeholder="https://maps.app.goo.gl/..." value="<?= e($editing['google_map_link'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Waze Link <span class="muted">(from waze.com → Share → Live URL)</span></label>
        <input class="input" name="waze_link" placeholder="https://waze.com/ul?ll=..." value="<?= e($editing['waze_link'] ?? '') ?>">
      </div>
    </div>
    <p class="muted" style="margin-top:6px">Tip: leave the deep links empty and we'll auto-build them from the address.</p>
    <p><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/branches.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<?php if ($editing):
  $branch_imgs = tenant_all(
      'SELECT * FROM branch_images WHERE company_id = ? AND branch_id = ?
        ORDER BY is_primary DESC, sort_order ASC',
      $CID, [(int) $editing['id']]
  );
?>
<div class="card">
  <h3 style="margin:0 0 6px;">Showroom photos for <?= e($editing['name']) ?></h3>
  <p class="muted" style="margin:0 0 12px;">Shown on the public Visit Us page. Recommended landscape, min 1200×800 px.</p>

  <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload_images">
    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <input type="file" name="images[]" multiple accept="image/*" required>
    <button class="btn primary" type="submit" style="margin-left:8px;">Upload</button>
  </form>

  <?php if ($branch_imgs): ?>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
      <?php foreach ($branch_imgs as $im): ?>
        <div style="position:relative;width:140px;">
          <img src="<?= e($im['image_path']) ?>"
               style="width:140px;height:140px;object-fit:cover;border-radius:8px;background:#eee;">
          <form method="post" onsubmit="return confirm('Delete this photo?')"
                style="position:absolute;top:4px;right:4px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_image">
            <input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>">
            <button class="btn danger" type="submit" style="padding:2px 8px;font-size:11px;">×</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">No photos uploaded yet.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <table>
    <tr><th>Name</th><th>Photos</th><th>Phone</th><th>Address</th><th>Status</th><th></th></tr>
    <?php foreach ($branches as $b):
      $img_count = (int) db_one(
          'SELECT COUNT(*) c FROM branch_images WHERE company_id = ? AND branch_id = ?',
          [$CID, (int) $b['id']]
      )['c'];
    ?>
      <tr>
        <td><?= e($b['name']) ?></td>
        <td><?= $img_count > 0 ? '🖼️ ' . $img_count : '<span class="muted">—</span>' ?></td>
        <td><?= e($b['phone']) ?></td>
        <td><?= e($b['address']) ?></td>
        <td><span class="badge <?= $b['status']==='active'?'green':'red' ?>"><?= e($b['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$b['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete branch?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
