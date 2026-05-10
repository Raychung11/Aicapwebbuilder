<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

if (!db_table_exists('package_sections')) {
    flash_set('error', 'Run /install.php once to enable Packages, then refresh.');
    redirect('/company-admin/packages.php');
}

$id = (int) input('id', 0);
if (!$id) redirect('/company-admin/packages.php');

$section = tenant_row_or_404('package_sections', $id);
$package = tenant_row_or_404('packages', (int) $section['package_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) input('action', 'save');

    if ($action === 'add_choices') {
        if (!empty($_FILES['choice_images']['name'][0])) {
            $next_sort = (int) (db_one(
                'SELECT IFNULL(MAX(sort_order), 0) AS m FROM package_choices WHERE section_id = ?',
                [$id]
            )['m'] ?? 0);
            foreach ($_FILES['choice_images']['name'] as $i => $_n) {
                $file = [
                    'name'     => $_FILES['choice_images']['name'][$i],
                    'type'     => $_FILES['choice_images']['type'][$i],
                    'tmp_name' => $_FILES['choice_images']['tmp_name'][$i],
                    'error'    => $_FILES['choice_images']['error'][$i],
                    'size'     => $_FILES['choice_images']['size'][$i],
                ];
                $url = save_upload($file, $CID, 'packages');
                if ($url) {
                    $next_sort++;
                    db_insert(
                        'INSERT INTO package_choices (section_id, company_id, label, image, sort_order)
                         VALUES (?, ?, NULL, ?, ?)',
                        [$id, $CID, $url, $next_sort]
                    );
                }
            }
            flash_set('success', 'Choice images added.');
        }
        redirect('/company-admin/package-section-edit.php?id=' . $id);
    }

    if ($action === 'update_choice') {
        $cid = (int) input('choice_id', 0);
        $row = db_one('SELECT * FROM package_choices WHERE id = ? AND company_id = ?', [$cid, $CID]);
        if ($row) {
            db_exec(
                'UPDATE package_choices SET label = ?, sort_order = ?
                  WHERE id = ? AND company_id = ?',
                [trim((string) input('label', '')) ?: null,
                 (int) (input('sort_order') ?: 0),
                 $cid, $CID]
            );
            flash_set('success', 'Choice updated.');
        }
        redirect('/company-admin/package-section-edit.php?id=' . $id);
    }

    if ($action === 'delete_choice') {
        $cid = (int) input('choice_id', 0);
        $row = db_one('SELECT * FROM package_choices WHERE id = ? AND company_id = ?', [$cid, $CID]);
        if ($row) {
            db_exec('DELETE FROM package_choices WHERE id = ? AND company_id = ?', [$cid, $CID]);
            $abs = __DIR__ . '/..' . $row['image'];
            if (is_file($abs)) @unlink($abs);
        }
        redirect('/company-admin/package-section-edit.php?id=' . $id);
    }

    if ($action === 'remove_image') {
        db_exec('UPDATE package_sections SET image = NULL WHERE id = ? AND company_id = ?',
                [$id, $CID]);
        flash_set('success', 'Section image removed.');
        redirect('/company-admin/package-section-edit.php?id=' . $id);
    }

    // Default: save section basics
    $title       = trim((string) input('title')) ?: 'Untitled section';
    $description = (string) input('description', '');
    $sort_order  = (int) (input('sort_order') ?: 0);

    db_exec(
        'UPDATE package_sections SET title = ?, description = ?, sort_order = ?
          WHERE id = ? AND company_id = ?',
        [$title, $description, $sort_order, $id, $CID]
    );
    if (!empty($_FILES['image']['name'])) {
        $url = save_upload($_FILES['image'], $CID, 'packages');
        if ($url) {
            db_exec('UPDATE package_sections SET image = ? WHERE id = ? AND company_id = ?',
                    [$url, $id, $CID]);
        }
    }
    flash_set('success', 'Section saved.');
    redirect('/company-admin/package-section-edit.php?id=' . $id);
}

$choices = tenant_all(
    'SELECT * FROM package_choices WHERE company_id = ? AND section_id = ? ORDER BY sort_order, id',
    $CID, [$id]
);

ca_open('Edit Section · ' . $package['title']);
?>
<div class="card">
  <p class="muted" style="margin:0 0 12px;font-size:13px;">
    Editing section in package: <strong><?= e($package['title']) ?></strong> —
    <a href="/company-admin/package-edit.php?id=<?= (int) $package['id'] ?>">← Back to package</a>
  </p>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <div class="row">
      <div class="col">
        <label>Section title *</label>
        <input class="input" name="title" required value="<?= e($section['title']) ?>"
               placeholder="e.g. Master Room / Living Room — Sofa">
      </div>
      <div class="col">
        <label>Sort order</label>
        <input class="input" type="number" name="sort_order" value="<?= e($section['sort_order']) ?>">
      </div>
    </div>

    <label>Description / item list</label>
    <textarea class="input" name="description" rows="3"
              placeholder="e.g. 1627 Queen Bedframe + Carter Queen Mattress"><?= e($section['description'] ?? '') ?></textarea>

    <div class="row" style="align-items:flex-start;">
      <div class="col" style="flex:0 0 220px;">
        <label>Section image</label>
        <div style="width:200px;height:140px;border:2px dashed #d1d5db;border-radius:10px;
                    display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
          <?php if (!empty($section['image'])): ?>
            <img src="<?= e($section['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span class="muted" style="font-size:12px;">No image</span>
          <?php endif; ?>
        </div>
        <input type="file" name="image" accept="image/*" style="margin-top:8px;font-size:13px;">
        <?php if (!empty($section['image'])): ?>
          <button class="btn outline" type="submit"
                  onclick="this.form.elements['action'].value='remove_image';return confirm('Remove section image?');"
                  style="margin-top:6px;font-size:12px;padding:5px 10px;">
            Remove image
          </button>
        <?php endif; ?>
      </div>
      <div class="col" style="align-self:flex-end;">
        <p style="margin-top:14px;">
          <button class="btn primary" type="submit">Save Section</button>
        </p>
      </div>
    </div>
  </form>
</div>

<!-- Choices -->
<div class="card">
  <h3 style="margin:0 0 6px;">Choices (<?= count($choices) ?>)</h3>
  <p class="muted" style="margin:0 0 12px;">
    Optional. Add multiple images here if the customer can pick — e.g. 4
    different sofa designs or 8 TV cabinet designs. Leave empty if this
    section has no customer choice.
  </p>

  <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_choices">
    <input type="file" name="choice_images[]" accept="image/*" multiple required>
    <button class="btn primary" type="submit" style="margin-left:8px;">Upload choices</button>
  </form>

  <?php if ($choices): ?>
    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));">
      <?php foreach ($choices as $i => $ch): ?>
        <form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_choice">
          <input type="hidden" name="choice_id" value="<?= (int) $ch['id'] ?>">
          <div style="aspect-ratio:1/1;background:#f3f4f6;border-radius:8px;overflow:hidden;
                      display:flex;align-items:center;justify-content:center;font-weight:700;color:#6b7280;">
            <img src="<?= e($ch['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
            <span style="position:absolute;display:none;">#<?= $i + 1 ?></span>
          </div>
          <label style="font-size:12px;margin-top:8px;">Label (optional)</label>
          <input class="input" name="label" placeholder="e.g. Design 1 / Light Wood"
                 value="<?= e($ch['label'] ?? '') ?>">
          <div style="display:flex;gap:6px;margin-top:6px;">
            <input class="input" type="number" name="sort_order" value="<?= e($ch['sort_order']) ?>"
                   style="flex:0 0 70px;" title="Sort order">
            <button class="btn outline" type="submit" style="flex:1;">Save</button>
          </div>
          <button class="btn danger" type="submit" style="width:100%;margin-top:6px;font-size:12px;"
                  onclick="this.form.elements['action'].value='delete_choice';return confirm('Delete choice?')">
            Delete
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php ca_close(); ?>
