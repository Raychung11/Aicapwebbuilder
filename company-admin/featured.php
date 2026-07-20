<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) input('id', 0);
    if ($id) {
        tenant_row_or_404('products', $id);
        $to = (int) input('to', 0) === 1 ? 1 : 0;
        db_exec('UPDATE products SET is_featured = ? WHERE company_id = ? AND id = ?',
                [$to, $CID, $id]);
        flash_set('success', $to ? 'Marked as featured.' : 'Removed from featured.');
    }
    redirect('/company-admin/featured.php' . (input('q') ? '?q=' . urlencode((string) input('q')) : ''));
}

$filter = (string) input('filter', 'all');
$q      = trim((string) input('q', ''));

$sql    = 'SELECT p.*,
                  (SELECT image_path FROM product_images
                    WHERE product_id = p.id
                    ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
             FROM products p
            WHERE p.company_id = ?';
$params = [$CID];
if ($filter === 'featured')     { $sql .= ' AND p.is_featured = 1'; }
elseif ($filter === 'not')      { $sql .= ' AND p.is_featured = 0'; }
if ($q !== '')                  { $sql .= ' AND p.name LIKE ?'; $params[] = '%' . $q . '%'; }
$sql   .= ' ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 200';
$rows = db_all($sql, $params);

$counts = [
    'all'      => (int) tenant_one('SELECT COUNT(*) c FROM products WHERE company_id = ?', $CID)['c'],
    'featured' => (int) tenant_one('SELECT COUNT(*) c FROM products WHERE company_id = ? AND is_featured = 1', $CID)['c'],
];
$counts['not'] = $counts['all'] - $counts['featured'];

ca_open('Featured Products');
?>
<div class="card">
  <p class="muted" style="margin:0 0 8px;">
    Featured products appear in the <strong>Featured Products</strong> section on
    your homepage. Tap the star to toggle.
  </p>
  <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
    <div>
      <a class="btn <?= $filter==='all' ? 'primary' : 'outline' ?>" href="?filter=all">All <span class="muted">(<?= $counts['all'] ?>)</span></a>
      <a class="btn <?= $filter==='featured' ? 'primary' : 'outline' ?>" href="?filter=featured">⭐ Featured <span class="muted">(<?= $counts['featured'] ?>)</span></a>
      <a class="btn <?= $filter==='not' ? 'primary' : 'outline' ?>" href="?filter=not">Not featured <span class="muted">(<?= $counts['not'] ?>)</span></a>
    </div>
    <form method="get" style="margin-left:auto;display:flex;gap:6px;">
      <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search product…" style="min-width:200px">
      <button class="btn outline" type="submit">Search</button>
    </form>
  </div>
</div>

<div class="card">
  <table>
    <tr>
      <th style="width:40px"></th>
      <th style="width:60px"></th>
      <th>Name</th>
      <th>Category</th>
      <th>Price</th>
      <th>Status</th>
      <th></th>
    </tr>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="to" value="<?= $p['is_featured'] ? 0 : 1 ?>">
            <input type="hidden" name="q"  value="<?= e($q) ?>">
            <button type="submit" title="<?= $p['is_featured'] ? 'Remove from featured' : 'Mark as featured' ?>"
                    style="background:none;border:0;font-size:22px;cursor:pointer;line-height:1;padding:4px;">
              <?= $p['is_featured'] ? '⭐' : '☆' ?>
            </button>
          </form>
        </td>
        <td>
          <?php if (!empty($p['img'])): ?>
            <img src="<?= e($p['img']) ?>" style="height:46px;width:46px;object-fit:cover;border-radius:4px">
          <?php endif; ?>
        </td>
        <td><?= e($p['name']) ?></td>
        <td>
          <?= e($p['category']) ?>
          <?php if (!empty($p['subcategory'])): ?>
            <div class="muted" style="font-size:12px;">› <?= e($p['subcategory']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= e(format_price($p['price_min'], $p['price_max'])) ?></td>
        <td><span class="badge <?= $p['status']==='active'?'green':'' ?>"><?= e($p['status']) ?></span></td>
        <td>
          <a class="btn outline" href="/company-admin/product-edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="muted center" style="padding:18px">No products match.</td></tr>
    <?php endif; ?>
  </table>
</div>
<?php ca_close(); ?>
