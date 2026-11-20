<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete') {
        $id = (int) input('id', 0);
        tenant_row_or_404('products', $id);
        db_exec('DELETE FROM product_images WHERE company_id = ? AND product_id = ?', [$CID, $id]);
        db_exec('DELETE FROM product_variants WHERE company_id = ? AND product_id = ?', [$CID, $id]);
        db_exec('DELETE FROM products WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Product deleted.');
    }
    redirect('/company-admin/products.php');
}

$products = tenant_all(
    'SELECT p.*,
            (SELECT image_path FROM product_images
              WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ?
      ORDER BY p.created_at DESC',
    $CID
);

ca_open('Products');
?>
<div class="card">
  <a class="btn primary" href="/company-admin/product-edit.php">+ Add Product</a>
</div>
<div class="card">
  <table>
    <tr><th>Image</th><th>Name</th><th>Category</th><th>Subcategory</th><th>Price</th><th>Status</th><th></th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?php if ($p['img']): ?><img src="<?= e($p['img']) ?>" style="height:46px;width:46px;object-fit:cover;border-radius:4px"><?php endif; ?></td>
        <td>
          <?php if (!empty($p['is_featured'])): ?>
            <span title="Featured" style="color:#f59e0b;font-size:14px;">⭐</span>
          <?php endif; ?>
          <?= e($p['name']) ?>
        </td>
        <td><?= e($p['category']) ?></td>
        <td><?= e($p['subcategory'] ?? '') ?></td>
        <td><?= e(format_price($p['price_min'], $p['price_max'])) ?></td>
        <td><span class="badge <?= $p['status']==='active'?'green':'' ?>"><?= e($p['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="/company-admin/product-edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete product?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
