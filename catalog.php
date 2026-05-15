<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'catalog']);

$category    = trim((string) input('category', ''));
$subcategory = trim((string) input('subcategory', ''));
$q           = trim((string) input('q', ''));

// If subcategory is set but its category doesn't match the selected one, reset.
if ($subcategory !== '' && $category !== '') {
    $check = tenant_one(
        'SELECT 1 AS x FROM products WHERE company_id = ? AND status = "active"
            AND category = ? AND subcategory = ? LIMIT 1',
        $cid, [$category, $subcategory]
    );
    if (!$check) $subcategory = '';
}

$sql    = 'SELECT p.*, (SELECT image_path FROM product_images
                        WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
             FROM products p
            WHERE p.company_id = ? AND p.status = "active"';
$params = [$cid];
if ($category !== '')    { $sql .= ' AND p.category = ?';    $params[] = $category;    }
if ($subcategory !== '') { $sql .= ' AND p.subcategory = ?'; $params[] = $subcategory; }
if ($q !== '')           { $sql .= ' AND p.name LIKE ?';     $params[] = '%' . $q . '%'; }
$sql   .= ' ORDER BY p.created_at DESC LIMIT 60';
$products = db_all($sql, $params);

// Categories: prefer the curated taxonomy table for ordering when present.
// Always intersect with categories that actually have ≥1 active product so
// empty categories don't appear on the public site.
$prod_cats = [];
foreach (tenant_all(
    'SELECT category, COUNT(*) AS n FROM products
      WHERE company_id = ? AND status = "active"
        AND category IS NOT NULL AND category != ""
      GROUP BY category',
    $cid
) as $r) {
    $prod_cats[$r['category']] = (int) $r['n'];
}

$cats = [];
$has_curated = function_exists('db_table_exists') && db_table_exists('categories');
if ($has_curated) {
    $curated = tenant_all(
        'SELECT name FROM categories WHERE company_id = ? ORDER BY sort_order, id',
        $cid
    );
    foreach ($curated as $c) {
        if (isset($prod_cats[$c['name']])) {
            $cats[] = ['category' => $c['name'], 'n' => $prod_cats[$c['name']]];
            unset($prod_cats[$c['name']]);
        }
    }
}
// Append any remaining live-only categories alphabetically.
ksort($prod_cats);
foreach ($prod_cats as $name => $n) {
    $cats[] = ['category' => $name, 'n' => $n];
}

// Subcategories under the selected category — same approach.
$subcats = [];
if ($category !== '') {
    $prod_subs = [];
    foreach (tenant_all(
        'SELECT subcategory, COUNT(*) AS n FROM products
          WHERE company_id = ? AND status = "active" AND category = ?
            AND subcategory IS NOT NULL AND subcategory != ""
          GROUP BY subcategory',
        $cid, [$category]
    ) as $r) {
        $prod_subs[$r['subcategory']] = (int) $r['n'];
    }
    if ($has_curated && db_table_exists('subcategories')) {
        $curated_sub = db_all(
            'SELECT s.name FROM subcategories s
               JOIN categories c ON c.id = s.category_id
              WHERE s.company_id = ? AND c.name = ?
              ORDER BY s.sort_order, s.id',
            [$cid, $category]
        );
        foreach ($curated_sub as $s) {
            if (isset($prod_subs[$s['name']])) {
                $subcats[] = ['subcategory' => $s['name'], 'n' => $prod_subs[$s['name']]];
                unset($prod_subs[$s['name']]);
            }
        }
    }
    ksort($prod_subs);
    foreach ($prod_subs as $name => $n) {
        $subcats[] = ['subcategory' => $name, 'n' => $n];
    }
}

// Helper to build catalog URLs while preserving search/category state.
$build_url = function (array $overrides) use ($q, $category, $subcategory) {
    $params = [];
    foreach (['q' => $q, 'category' => $category, 'subcategory' => $subcategory] as $k => $v) {
        if ($v !== '') $params[$k] = $v;
    }
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') unset($params[$k]);
        else                          $params[$k] = $v;
    }
    return '/catalog.php' . ($params ? '?' . http_build_query($params) : '');
};

$page_title = 'Catalog';
$page_id    = 'catalog';
require __DIR__ . '/inc/header.php';
?>
<section>
  <div class="container">
    <h1 style="margin:0 0 4px">Our Catalog</h1>
    <p class="muted" style="margin:0 0 16px">Browse our latest furniture. Tap any item for details, or chat with us on WhatsApp.</p>

    <form method="get" role="search" style="display:flex;gap:8px;margin-bottom:14px;">
      <?php if ($category !== ''):    ?><input type="hidden" name="category"    value="<?= e($category) ?>"><?php endif; ?>
      <?php if ($subcategory !== ''): ?><input type="hidden" name="subcategory" value="<?= e($subcategory) ?>"><?php endif; ?>
      <input class="input" name="q" placeholder="Search products…" value="<?= e($q) ?>" inputmode="search">
      <button class="btn primary" type="submit" style="min-width:90px;">Search</button>
    </form>

    <?php if ($cats): ?>
      <div style="overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch;padding-bottom:6px;">
        <span class="muted" style="margin-right:6px;font-size:12px">Categories:</span>
        <a class="chip <?= $category === '' ? 'active' : '' ?>"
           href="<?= e($build_url(['category' => null, 'subcategory' => null])) ?>">All</a>
        <?php foreach ($cats as $c): ?>
          <a class="chip <?= $c['category'] === $category ? 'active' : '' ?>"
             href="<?= e($build_url(['category' => $c['category'], 'subcategory' => null])) ?>">
             <?= e($c['category']) ?> <span class="muted">(<?= (int)$c['n'] ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($subcats): ?>
      <div style="overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch;padding-bottom:6px;margin-top:6px;">
        <span class="muted" style="margin-right:6px;font-size:12px">In <?= e($category) ?>:</span>
        <a class="chip <?= $subcategory === '' ? 'active' : '' ?>"
           href="<?= e($build_url(['subcategory' => null])) ?>">All</a>
        <?php foreach ($subcats as $sc): ?>
          <a class="chip <?= $sc['subcategory'] === $subcategory ? 'active' : '' ?>"
             href="<?= e($build_url(['subcategory' => $sc['subcategory']])) ?>">
             <?= e($sc['subcategory']) ?> <span class="muted">(<?= (int)$sc['n'] ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="muted" style="margin:14px 0 6px;">
      <?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?>
      <?php if ($category !== '' || $subcategory !== '' || $q !== ''): ?>
        &middot; <a href="/catalog.php">Clear filters</a>
      <?php endif; ?>
    </p>

    <?php if (!$products): ?>
      <div class="box center"><p class="muted">No products match your filters.</p></div>
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
                <?php if (!empty($p['category']) || !empty($p['subcategory'])): ?>
                  <div class="muted" style="font-size:12px">
                    <?= e(trim(($p['category'] ?? '') . ' › ' . ($p['subcategory'] ?? ''), ' ›')) ?>
                  </div>
                <?php endif; ?>
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
<?php require __DIR__ . '/inc/footer.php'; ?>
