<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
track_event((int)$company['id'], 'page_view', ['entity_type' => 'catalog']);

$category = (string) input('category', '');
$q        = trim((string) input('q', ''));

$sql    = 'SELECT p.*, (SELECT image_path FROM product_images
                        WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
             FROM products p
            WHERE p.company_id = ? AND p.status = "active"';
$params = [(int)$company['id']];
if ($category !== '') { $sql .= ' AND p.category = ?'; $params[] = $category; }
if ($q !== '')        { $sql .= ' AND p.name LIKE ?';  $params[] = '%' . $q . '%'; }
$sql   .= ' ORDER BY p.created_at DESC LIMIT 60';
$products = db_all($sql, $params);

$cats = tenant_all(
    'SELECT DISTINCT category FROM products
      WHERE company_id = ? AND category IS NOT NULL AND category != ""
      ORDER BY category',
    (int)$company['id']
);

layout_head($company, 'Catalog');
?>
<section>
  <div class="container">
    <h1 style="margin-top:18px">Catalog</h1>
    <form method="get" class="row" style="display:flex;gap:8px;flex-wrap:wrap;margin:14px 0;">
      <input class="input" name="q" placeholder="Search products" value="<?= e($q) ?>" style="max-width:280px">
      <select class="input" name="category" style="max-width:200px">
        <option value="">All categories</option>
        <?php foreach ($cats as $c): ?>
          <option <?= $c['category'] === $category ? 'selected' : '' ?>><?= e($c['category']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn primary" type="submit">Filter</button>
    </form>

    <?php if (!$products): ?>
      <p class="muted">No products found.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($products as $p): ?>
          <a class="card" style="text-decoration:none;color:inherit" href="/product.php?id=<?= (int)$p['id'] ?>">
            <div class="img">
              <?php if (!empty($p['img'])): ?><img src="<?= e($p['img']) ?>" alt=""><?php endif; ?>
            </div>
            <div class="pad">
              <h3><?= e($p['name']) ?></h3>
              <?php if (!empty($p['category'])): ?><div class="muted"><?= e($p['category']) ?></div><?php endif; ?>
              <div class="price"><?= e(format_price($p['price_min'], $p['price_max'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_foot($company); ?>
