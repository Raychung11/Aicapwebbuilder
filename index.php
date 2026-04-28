<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = current_company();

if (!$company) {
    // ===== HQ landing =====
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(APP_NAME) ?></title>
<style>
body { margin:0; font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; background:#0f172a; color:#fff; }
.wrap { max-width:900px; margin: 0 auto; padding: 80px 20px; text-align:center; }
h1 { font-size: 44px; margin: 0 0 16px; }
p { color:#94a3b8; font-size: 18px; }
.btn { display:inline-block; margin: 6px; padding: 12px 22px; border-radius:8px; background:#f59e0b; color:#111; font-weight:700; text-decoration:none; }
.btn.outline { background:transparent; border:1px solid #334155; color:#fff; }
</style>
</head>
<body>
<div class="wrap">
  <h1>AICAP Furniture BOS</h1>
  <p>Multi-tenant SaaS platform for furniture brands.<br>
     Each licensed company gets its own subdomain, catalog, vouchers and analytics.</p>
  <p>
    <a class="btn" href="/admin/login.php">Super Admin Login</a>
    <a class="btn outline" href="/company-admin/login.php">Company Admin Login</a>
  </p>
</div>
</body></html>
<?php
    exit;
}

// ===== Company homepage =====
track_event((int)$company['id'], 'page_view', ['entity_type' => 'home']);

$products = tenant_all(
    'SELECT p.*, (SELECT image_path FROM product_images
                  WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ? AND p.status = "active"
      ORDER BY p.created_at DESC
      LIMIT 8',
    (int)$company['id']
);
$branches = tenant_all(
    'SELECT * FROM branches WHERE company_id = ? AND status = "active" ORDER BY id',
    (int)$company['id']
);

layout_head($company);
?>
<section class="hero">
  <div class="container">
    <h1><?= e($company['name']) ?></h1>
    <p style="font-size:18px;opacity:.9;max-width:640px;"><?= e($company['description']) ?></p>
    <p>
      <a class="btn primary" href="/catalog.php">Browse Catalog</a>
      <?php if (!empty($company['whatsapp_number'])): ?>
        <a class="btn outline" style="background:#fff" href="<?= e(whatsapp_link($company['whatsapp_number'], 'Hi, I\'d like to know more.')) ?>" target="_blank">Chat on WhatsApp</a>
      <?php endif; ?>
    </p>
  </div>
</section>

<section>
  <div class="container">
    <h2>Featured Products</h2>
    <?php if (!$products): ?>
      <p class="muted">No products published yet.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($products as $p): ?>
          <a class="card" style="text-decoration:none;color:inherit" href="/product.php?id=<?= (int)$p['id'] ?>">
            <div class="img">
              <?php if (!empty($p['img'])): ?><img src="<?= e($p['img']) ?>" alt=""><?php endif; ?>
            </div>
            <div class="pad">
              <h3><?= e($p['name']) ?></h3>
              <div class="price"><?= e(format_price($p['price_min'], $p['price_max'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($branches): ?>
<section>
  <div class="container">
    <h2>Visit Us</h2>
    <div class="grid">
      <?php foreach ($branches as $b): ?>
        <div class="card pad" style="padding:14px">
          <h3 style="margin:0 0 6px"><?= e($b['name']) ?></h3>
          <div class="muted"><?= e($b['address']) ?></div>
          <?php if (!empty($b['phone'])): ?><div>Tel: <?= e($b['phone']) ?></div><?php endif; ?>
          <?php if (!empty($b['operating_hours'])): ?><div class="muted"><?= e($b['operating_hours']) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php layout_foot($company); ?>
