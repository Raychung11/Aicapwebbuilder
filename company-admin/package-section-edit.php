<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';
require_once __DIR__ . '/../inc/packages.php';

// Safety net for older inc/helpers.php after a partial deploy
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

if (!db_table_exists('package_sections')) {
    flash_set('error', 'Run /install.php once to enable Packages, then refresh.');
    redirect('/company-admin/packages.php');
}

$has_items_table = db_table_exists('package_section_items');
$has_kind_col    = $has_items_table && db_one(
    'SELECT 1 AS x FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "package_sections"
        AND COLUMN_NAME = "kind" LIMIT 1'
);

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

    // Default: save section basics + product items
    $title       = trim((string) input('title')) ?: 'Untitled section';
    $description = (string) input('description', '');
    $sort_order  = (int) (input('sort_order') ?: 0);
    $kind        = in_array(input('kind'), ['included','choice'], true) ? input('kind') : 'included';

    if ($has_kind_col) {
        db_exec(
            'UPDATE package_sections SET title = ?, description = ?, sort_order = ?, kind = ?
              WHERE id = ? AND company_id = ?',
            [$title, $description, $sort_order, $kind, $id, $CID]
        );
    } else {
        db_exec(
            'UPDATE package_sections SET title = ?, description = ?, sort_order = ?
              WHERE id = ? AND company_id = ?',
            [$title, $description, $sort_order, $id, $CID]
        );
    }

    if (!empty($_FILES['image']['name'])) {
        $url = save_upload($_FILES['image'], $CID, 'packages');
        if ($url) {
            db_exec('UPDATE package_sections SET image = ? WHERE id = ? AND company_id = ?',
                    [$url, $id, $CID]);
        }
    }

    // Product items — replace the set with whatever was selected
    if ($has_items_table) {
        $selected = (array) ($_POST['product_ids'] ?? []);
        $quantities = (array) ($_POST['qty'] ?? []);
        db_exec('DELETE FROM package_section_items WHERE company_id = ? AND section_id = ?',
                [$CID, $id]);
        $sort = 0;
        foreach ($selected as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) continue;
            // Confirm product belongs to this tenant
            $valid = db_one('SELECT id FROM products WHERE id = ? AND company_id = ?',
                            [$pid, $CID]);
            if (!$valid) continue;
            $qty = isset($quantities[$pid]) ? max(1, (int) $quantities[$pid]) : 1;
            $sort++;
            db_exec(
                'INSERT INTO package_section_items (section_id, company_id, product_id, quantity, sort_order)
                 VALUES (?, ?, ?, ?, ?)',
                [$id, $CID, $pid, $qty, $sort]
            );
        }
    }

    flash_set('success', 'Section saved.');
    redirect('/company-admin/package-section-edit.php?id=' . $id);
}

$choices = tenant_all(
    'SELECT * FROM package_choices WHERE company_id = ? AND section_id = ? ORDER BY sort_order, id',
    $CID, [$id]
);

// All tenant products for the picker + which ones are currently in this section
$products = tenant_all(
    'SELECT id, name, category, subcategory, price_min, price_max, status,
            (SELECT image_path FROM product_images
              WHERE product_id = products.id
              ORDER BY is_primary DESC LIMIT 1) AS img
       FROM products
      WHERE company_id = ?
      ORDER BY status = "active" DESC, category, name',
    $CID
);
$selected_items = $has_items_table ? package_items_for_section($id, $CID) : [];
$selected_map   = [];
foreach ($selected_items as $it) {
    $selected_map[(int) $it['product_id']] = $it;
}

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
        <label>Kind</label>
        <select class="input" name="kind">
          <option value="included" <?= ($section['kind'] ?? 'included') === 'included' ? 'selected' : '' ?>>
            Included — all items ship with the package
          </option>
          <option value="choice" <?= ($section['kind'] ?? '') === 'choice' ? 'selected' : '' ?>>
            Choice — customer picks one (price averaged)
          </option>
        </select>
      </div>
      <div class="col">
        <label>Sort order</label>
        <input class="input" type="number" name="sort_order" value="<?= e($section['sort_order']) ?>">
      </div>
    </div>

    <label>Description / item list <span class="muted">(optional — auto-filled from items if blank)</span></label>
    <textarea class="input" name="description" rows="2"
              placeholder="Leave blank — items below will be listed automatically."><?= e($section['description'] ?? '') ?></textarea>

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
      <div class="col">

        <!-- Product picker -->
        <?php if (!$has_items_table): ?>
          <div class="alert error">
            ⚠️ The <code>package_section_items</code> table is missing.
            Run <code>/install.php</code> once, then refresh.
          </div>
        <?php else: ?>
          <label>Products in this section</label>
          <p class="muted" style="margin:0 0 8px;font-size:13px;">
            Tick the products that belong in this section. Retail price is
            auto-calculated from these. Don't see a product?
            <a href="/company-admin/product-edit.php" target="_blank" rel="noopener">
              + Create new product ↗
            </a> then refresh this page.
          </p>

          <input type="search" id="prod-filter" placeholder="Filter products…"
                 class="input" style="margin-bottom:8px;">

          <div id="prod-picker"
               style="border:1px solid #e5e7eb;border-radius:8px;max-height:360px;overflow:auto;">
            <?php if (!$products): ?>
              <div class="muted" style="padding:12px;">No products yet. Create some first.</div>
            <?php endif; ?>
            <?php foreach ($products as $p):
              $pid     = (int) $p['id'];
              $checked = isset($selected_map[$pid]);
              $qty     = $checked ? (int) $selected_map[$pid]['quantity'] : 1;
              $price   = product_unit_price($p);
            ?>
              <label class="prod-row" data-search="<?= e(strtolower(($p['name']) . ' ' . ($p['category'] ?? '') . ' ' . ($p['subcategory'] ?? ''))) ?>"
                     style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-bottom:1px solid #f3f4f6;cursor:pointer;<?= $checked ? 'background:#ecfdf5;' : '' ?>">
                <input type="checkbox" name="product_ids[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>>
                <?php if (!empty($p['img'])): ?>
                  <img src="<?= e($p['img']) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:4px;background:#eee;">
                <?php else: ?>
                  <span style="width:32px;height:32px;background:#f3f4f6;border-radius:4px;display:inline-block;"></span>
                <?php endif; ?>
                <span style="flex:1;min-width:0;">
                  <strong style="font-size:14px;"><?= e($p['name']) ?></strong>
                  <?php if (!empty($p['category'])): ?>
                    <span class="muted" style="font-size:12px;display:block;">
                      <?= e($p['category']) ?><?= !empty($p['subcategory']) ? ' › ' . e($p['subcategory']) : '' ?>
                    </span>
                  <?php endif; ?>
                </span>
                <span style="font-size:13px;color:#6b7280;white-space:nowrap;">
                  RM <?= number_format($price, 0) ?>
                </span>
                <input type="number" name="qty[<?= $pid ?>]" value="<?= $qty ?>" min="1" max="99"
                       title="Quantity"
                       style="width:60px;padding:6px;border:1px solid #d1d5db;border-radius:6px;font:inherit;text-align:center;">
              </label>
            <?php endforeach; ?>
          </div>

          <p class="muted" style="margin-top:8px;font-size:12px;">
            <?= count($selected_items) ?> selected ·
            Retail will be re-calculated when you save.
          </p>
        <?php endif; ?>

        <p style="margin-top:14px;">
          <button class="btn primary" type="submit">Save Section</button>
          <a class="btn outline" href="/company-admin/package-edit.php?id=<?= (int) $package['id'] ?>">Cancel</a>
        </p>
      </div>
    </div>
  </form>
</div>

<!-- Legacy image-only choices (kept for backward compat) -->
<div class="card">
  <h3 style="margin:0 0 6px;">Image-only choices (legacy, <?= count($choices) ?>)</h3>
  <p class="muted" style="margin:0 0 12px;">
    Optional. Use this only for visual-only options that don't need pricing
    (e.g. 8 generic TV cabinet designs). For anything priced, use the
    Products picker above instead.
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
          <div style="aspect-ratio:1/1;background:#f3f4f6;border-radius:8px;overflow:hidden;">
            <img src="<?= e($ch['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
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

<script>
// Live filter for the product picker
(function () {
  var input  = document.getElementById('prod-filter');
  var picker = document.getElementById('prod-picker');
  if (!input || !picker) return;
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    picker.querySelectorAll('.prod-row').forEach(function (row) {
      var data = (row.dataset.search || '');
      row.style.display = (!q || data.indexOf(q) !== -1) ? '' : 'none';
    });
  });

  // Highlight rows when ticked
  picker.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      cb.closest('.prod-row').style.background = cb.checked ? '#ecfdf5' : '';
    });
  });
})();
</script>

<?php ca_close(); ?>
