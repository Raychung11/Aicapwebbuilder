<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

$id      = (int) input('id', 0);
$product = $id ? tenant_row_or_404('products', $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) input('action', 'save');

    if ($action === 'delete_image') {
        $iid = (int) input('image_id', 0);
        $img = db_one('SELECT * FROM product_images WHERE company_id = ? AND id = ?', [$CID, $iid]);
        if ($img) {
            db_exec('DELETE FROM product_images WHERE company_id = ? AND id = ?', [$CID, $iid]);
            $abs = __DIR__ . '/..' . $img['image_path'];
            if (is_file($abs)) @unlink($abs);
        }
        redirect('/company-admin/product-edit.php?id=' . $id);
    }

    if ($action === 'delete_variant') {
        $vid = (int) input('variant_id', 0);
        db_exec('DELETE FROM product_variants WHERE company_id = ? AND id = ?', [$CID, $vid]);
        redirect('/company-admin/product-edit.php?id=' . $id);
    }

    if ($action === 'update_variant') {
        $vid = (int) input('variant_id', 0);
        if ($vid && $id) {
            db_exec(
                'UPDATE product_variants
                   SET variant_name = ?, color = ?, material = ?, size = ?, price = ?
                 WHERE id = ? AND company_id = ? AND product_id = ?',
                [
                    trim((string) input('variant_name')),
                    (string) input('color', ''),
                    (string) input('material', ''),
                    (string) input('size', ''),
                    input('price') !== '' ? (float) input('price') : null,
                    $vid, $CID, $id,
                ]
            );
        }
        redirect('/company-admin/product-edit.php?id=' . $id);
    }

    if (($action === 'move_variant_up' || $action === 'move_variant_down') && $id) {
        $vid = (int) input('variant_id', 0);
        $dir = $action === 'move_variant_up' ? 'up' : 'down';
        if ($vid) {
            // Renormalize sort_orders to 10, 20, 30 … so swaps are predictable
            $sort = 10;
            foreach (db_all(
                'SELECT id FROM product_variants
                  WHERE product_id = ? AND company_id = ?
                  ORDER BY sort_order ASC, id ASC',
                [$id, $CID]
            ) as $r) {
                db_exec('UPDATE product_variants SET sort_order = ? WHERE id = ?', [$sort, (int) $r['id']]);
                $sort += 10;
            }
            $self = db_one(
                'SELECT id, sort_order FROM product_variants
                  WHERE id = ? AND company_id = ? AND product_id = ? LIMIT 1',
                [$vid, $CID, $id]
            );
            if ($self) {
                $op    = $dir === 'up' ? '<' : '>';
                $order = $dir === 'up' ? 'DESC' : 'ASC';
                $neighbor = db_one(
                    "SELECT id, sort_order FROM product_variants
                       WHERE company_id = ? AND product_id = ? AND sort_order {$op} ?
                       ORDER BY sort_order {$order} LIMIT 1",
                    [$CID, $id, (int) $self['sort_order']]
                );
                if ($neighbor) {
                    db_exec('UPDATE product_variants SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $neighbor['sort_order'], $vid, $CID]);
                    db_exec('UPDATE product_variants SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $self['sort_order'], (int) $neighbor['id'], $CID]);
                }
            }
        }
        redirect('/company-admin/product-edit.php?id=' . $id);
    }

    if ($action === 'add_variant' && $id) {
        // New variants go to the end
        $maxRow = db_one(
            'SELECT IFNULL(MAX(sort_order),0) AS m FROM product_variants
              WHERE product_id = ? AND company_id = ?',
            [$id, $CID]
        );
        $nextSort = (int) ($maxRow['m'] ?? 0) + 10;
        db_insert(
            'INSERT INTO product_variants (company_id, product_id, variant_name, color, material, size, price, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $CID, $id,
                trim((string) input('variant_name')),
                (string) input('color', ''),
                (string) input('material', ''),
                (string) input('size', ''),
                input('price') !== '' ? (float) input('price') : null,
                $nextSort,
            ]
        );
        redirect('/company-admin/product-edit.php?id=' . $id);
    }

    // Save core
    $name = trim((string) input('name'));
    $slug = slugify((string) (input('slug') ?: $name));
    $f = [
        'name'             => $name,
        'slug'             => $slug,
        'category'         => trim((string) input('category', '')),
        'subcategory'      => trim((string) input('subcategory', '')),
        'description'      => (string) input('description', ''),
        'price_min'        => input('price_min') !== '' ? (float) input('price_min') : null,
        'price_max'        => input('price_max') !== '' ? (float) input('price_max') : null,
        'stock_status'     => in_array(input('stock_status'), ['in_stock','out_of_stock','preorder'], true) ? input('stock_status') : 'in_stock',
        'is_featured'      => !empty($_POST['is_featured']) ? 1 : 0,
        'meta_title'       => trim((string) input('meta_title', '')) ?: null,
        'meta_description' => trim((string) input('meta_description', '')) ?: null,
        'status'           => in_array(input('status'), ['active','draft','archived'], true) ? input('status') : 'active',
    ];

    if ($id) {
        db_exec(
            'UPDATE products SET name=?, slug=?, category=?, subcategory=?, description=?,
                                 price_min=?, price_max=?, stock_status=?, is_featured=?,
                                 meta_title=?, meta_description=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        // ensure slug uniqueness within company
        $base = $slug; $i = 1;
        while (db_one('SELECT id FROM products WHERE company_id = ? AND slug = ?', [$CID, $slug])) {
            $slug = $base . '-' . (++$i);
        }
        $f['slug'] = $slug;
        $id = db_insert(
            'INSERT INTO products (company_id, name, slug, category, subcategory, description,
                                   price_min, price_max, stock_status, is_featured,
                                   meta_title, meta_description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $_n) {
            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            $url = save_upload($file, $CID, 'products');
            if ($url) {
                $isPrimary = db_one(
                    'SELECT COUNT(*) c FROM product_images WHERE company_id = ? AND product_id = ?',
                    [$CID, $id]
                )['c'] == 0 ? 1 : 0;
                db_insert(
                    'INSERT INTO product_images (company_id, product_id, image_path, is_primary, sort_order)
                     VALUES (?, ?, ?, ?, ?)',
                    [$CID, $id, $url, $isPrimary, $i]
                );
            }
        }
    }

    flash_set('success', 'Product saved.');
    redirect('/company-admin/product-edit.php?id=' . $id);
}

$images   = $id ? tenant_all(
    'SELECT * FROM product_images WHERE company_id = ? AND product_id = ? ORDER BY is_primary DESC, sort_order',
    $CID, [$id]
) : [];
$variants = $id ? tenant_all(
    'SELECT * FROM product_variants WHERE company_id = ? AND product_id = ? ORDER BY sort_order ASC, id ASC',
    $CID, [$id]
) : [];

ca_open($product ? 'Edit Product' : 'Add Product');
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($product['name'] ?? '') ?>"></div>
      <div class="col"><label>Slug</label><input class="input" name="slug" value="<?= e($product['slug'] ?? '') ?>"></div>
    </div>
    <div class="row">
      <div class="col"><label>Category <span class="muted">(top-level, e.g. Living Room)</span></label>
        <input class="input" name="category" list="cat-options" value="<?= e($product['category'] ?? '') ?>">
      </div>
      <div class="col"><label>Subcategory <span class="muted">(e.g. Sofa, Coffee Table)</span></label>
        <input class="input" name="subcategory" list="subcat-options" value="<?= e($product['subcategory'] ?? '') ?>">
      </div>
    </div>
    <?php
      $cats    = tenant_all('SELECT DISTINCT category FROM products WHERE company_id = ? AND category IS NOT NULL AND category != "" ORDER BY category', $CID);
      $subcats = tenant_all('SELECT DISTINCT subcategory FROM products WHERE company_id = ? AND subcategory IS NOT NULL AND subcategory != "" ORDER BY subcategory', $CID);
    ?>
    <datalist id="cat-options">
      <?php foreach ($cats as $c):    ?><option value="<?= e($c['category']) ?>"><?php endforeach; ?>
    </datalist>
    <datalist id="subcat-options">
      <?php foreach ($subcats as $s): ?><option value="<?= e($s['subcategory']) ?>"><?php endforeach; ?>
    </datalist>
    <div class="row">
      <div class="col"><label>Price Min</label><input class="input" name="price_min" type="number" step="0.01" value="<?= e($product['price_min'] ?? '') ?>"></div>
      <div class="col"><label>Price Max</label><input class="input" name="price_max" type="number" step="0.01" value="<?= e($product['price_max'] ?? '') ?>"></div>
      <div class="col"><label>Stock</label>
        <select class="input" name="stock_status">
          <?php foreach (['in_stock','out_of_stock','preorder'] as $s): ?>
            <option <?= ($product['stock_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <?php foreach (['active','draft','archived'] as $s): ?>
            <option <?= ($product['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>Featured</label>
        <label style="display:flex;align-items:center;gap:8px;padding:9px 0;font-weight:500;">
          <input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
          ⭐ Show on homepage
        </label>
      </div>
    </div>
    <label>Description</label>
    <textarea class="input" name="description" rows="4"><?= e($product['description'] ?? '') ?></textarea>
    <label>Add images (multiple)</label>
    <input type="file" name="images[]" multiple accept="image/*">

    <div style="margin-top:18px;border-top:1px solid #e5e7eb;padding-top:14px;">
      <h4 style="margin:0 0 4px;">SEO meta tags</h4>
      <p class="muted" style="margin:0 0 8px;">Used by search engines and link previews. Leave blank to auto-fill from name + description.</p>
      <label>Meta Title <span class="muted">(max ~60 chars)</span></label>
      <input class="input" name="meta_title" maxlength="255"
             placeholder="<?= e(($product['name'] ?? 'Product Name')) ?>"
             value="<?= e($product['meta_title'] ?? '') ?>">
      <label>Meta Description <span class="muted">(max ~160 chars)</span></label>
      <textarea class="input" name="meta_description" rows="2" maxlength="500"
                placeholder="Short description that appears in Google results and on social shares."><?= e($product['meta_description'] ?? '') ?></textarea>
    </div>

    <p style="margin-top:14px"><button class="btn primary">Save</button>
       <a class="btn outline" href="/company-admin/products.php">Back</a></p>
  </form>
</div>

<?php if ($id && $images): ?>
<div class="card">
  <h3 style="margin:0 0 10px">Images</h3>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php foreach ($images as $im): ?>
      <div style="position:relative;width:120px">
        <img src="<?= e($im['image_path']) ?>" style="width:120px;height:120px;object-fit:cover;border-radius:6px">
        <form method="post" onsubmit="return confirm('Delete image?')" style="position:absolute;top:4px;right:4px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_image">
          <input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>">
          <button class="btn danger" style="padding:2px 6px;font-size:11px">x</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($id): ?>
<div class="card">
  <h3 style="margin:0 0 4px">Variants</h3>
  <p class="muted" style="margin:0 0 12px;font-size:13px;">
    Edit any field inline then click <strong>Save</strong>. Use ↑ / ↓ to change the
    display order on the public product page.
  </p>

  <?php if ($variants): ?>
    <!-- One standalone form per variant (rendered before the table so
         every input/button can reference it via the HTML5 form= attribute). -->
    <?php foreach ($variants as $v): ?>
      <form id="vform-<?= (int) $v['id'] ?>" method="post" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="variant_id" value="<?= (int) $v['id'] ?>">
      </form>
    <?php endforeach; ?>

    <div style="overflow-x:auto;">
    <table>
      <tr>
        <th>Name</th><th>Color</th><th>Material</th><th>Size</th><th>Price (RM)</th><th>Position</th><th></th>
      </tr>
      <?php foreach ($variants as $i => $v): $fid = 'vform-' . (int) $v['id']; ?>
        <tr>
          <td><input class="input" form="<?= $fid ?>" name="variant_name" required value="<?= e($v['variant_name']) ?>"></td>
          <td><input class="input" form="<?= $fid ?>" name="color"          value="<?= e($v['color']    ?? '') ?>"></td>
          <td><input class="input" form="<?= $fid ?>" name="material"       value="<?= e($v['material'] ?? '') ?>"></td>
          <td><input class="input" form="<?= $fid ?>" name="size"           value="<?= e($v['size']     ?? '') ?>"></td>
          <td><input class="input" form="<?= $fid ?>" type="number" step="0.01" min="0" name="price" value="<?= $v['price'] !== null ? e($v['price']) : '' ?>"></td>
          <td style="white-space:nowrap;text-align:center;">
            <button form="<?= $fid ?>" name="action" value="move_variant_up" class="btn outline"
                    title="Move up" <?= $i === 0 ? 'disabled' : '' ?>
                    style="padding:6px 10px;font-size:14px;">↑</button>
            <button form="<?= $fid ?>" name="action" value="move_variant_down" class="btn outline"
                    title="Move down" <?= $i === count($variants) - 1 ? 'disabled' : '' ?>
                    style="padding:6px 10px;font-size:14px;">↓</button>
          </td>
          <td class="actions" style="white-space:nowrap;">
            <button form="<?= $fid ?>" name="action" value="update_variant" class="btn primary"
                    style="padding:6px 12px;">Save</button>
            <button form="<?= $fid ?>" name="action" value="delete_variant" class="btn danger"
                    onclick="return confirm('Delete this variant?')"
                    style="padding:6px 12px;">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
  <?php endif; ?>

  <h4 style="margin:18px 0 6px">Add Variant</h4>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_variant">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="variant_name" required></div>
      <div class="col"><label>Color</label><input class="input" name="color"></div>
      <div class="col"><label>Material</label><input class="input" name="material"></div>
      <div class="col"><label>Size</label><input class="input" name="size"></div>
      <div class="col"><label>Price (RM)</label><input class="input" type="number" step="0.01" min="0" name="price"></div>
    </div>
    <p><button class="btn primary">Add Variant</button></p>
  </form>
</div>
<?php endif; ?>

<?php ca_close(); ?>
