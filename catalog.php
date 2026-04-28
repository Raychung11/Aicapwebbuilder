<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'catalog']);

$category = trim((string) input('category', ''));
$q        = trim((string) input('q', ''));

$sql    = 'SELECT p.*, (SELECT image_path FROM product_images
                        WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
             FROM products p
            WHERE p.company_id = ? AND p.status = "active"';
$params = [$cid];
if ($category !== '') { $sql .= ' AND p.category = ?'; $params[] = $category; }
if ($q !== '')        { $sql .= ' AND p.name LIKE ?';  $params[] = '%' . $q . '%'; }
$sql   .= ' ORDER BY p.created_at DESC LIMIT 60';
$products = db_all($sql, $params);

$cats = tenant_all(
    'SELECT category, COUNT(*) AS n FROM products
      WHERE company_id = ? AND status = "active"
        AND category IS NOT NULL AND category != ""
      GROUP BY category
      ORDER BY category',
    $cid
);

layout_head($company, 'Catalog');
?>
<section>
  <div class="container">
    <h1 style="margin:0 0 4px">Our Catalog</h1>
    <p class="muted" style="margin:0 0 16px">Browse our latest furniture. Tap any item for details, or chat with us on WhatsApp.</p>

    <form method="get" role="search" style="display:flex;gap:8px;margin-bottom:14px;">
      <input class="input" name="q" placeholder="Search products…" value="<?= e($q) ?>" inputmode="search">
      <button class="btn primary" type="submit" style="min-width:90px;">Search</button>
    </form>

    <?php if ($cats): ?>
      <div style="overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch;padding-bottom:6px;margin-bottom:14px">
        <a class="chip <?= $category === '' ? 'active' : '' ?>" href="/catalog.php<?= $q ? '?q=' . urlencode($q) : '' ?>">All</a>
        <?php foreach ($cats as $c):
          $params2 = ['category' => $c['category']];
          if ($q !== '') $params2['q'] = $q;
          $href = '/catalog.php?' . http_build_query($params2);
        ?>
          <a class="chip <?= $c['category'] === $category ? 'active' : '' ?>"
             href="<?= e($href) ?>"><?= e($c['category']) ?> <span class="muted">(<?= (int)$c['n'] ?>)</span></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$products): ?>
      <p class="muted">No products found. <a href="/catalog.php">Clear filters</a></p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($products as $p):
          $wa = $company['whatsapp_number']
              ? '/whatsapp-redirect.php?product_id=' . (int)$p['id']
              : null;
        ?>
          <div class="card" style="display:flex;flex-direction:column;">
            <a href="/product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none;color:inherit;display:block;">
              <div class="img">
                <?php if (!empty($p['img'])): ?><img src="<?= e($p['img']) ?>" alt="" loading="lazy"><?php endif; ?>
              </div>
              <div class="pad" style="padding-bottom:6px">
                <h3><?= e($p['name']) ?></h3>
                <?php if (!empty($p['category'])): ?><div class="muted" style="font-size:12px"><?= e($p['category']) ?></div><?php endif; ?>
                <div class="price"><?= e(format_price($p['price_min'], $p['price_max'])) ?></div>
              </div>
            </a>
            <?php if ($wa): ?>
              <div style="padding:0 12px 12px;">
                <a class="btn block" target="_blank" rel="noopener" href="<?= e($wa) ?>"
                   style="background:#25d366;color:#fff;font-size:14px;padding:10px;">
                  💬 Enquire
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_foot($company); ?>
