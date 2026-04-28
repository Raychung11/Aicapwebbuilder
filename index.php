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
.wrap { max-width:900px; margin: 0 auto; padding: clamp(40px,10vw,80px) 20px; text-align:center; }
h1 { font-size: clamp(30px,6vw,46px); margin: 0 0 16px; }
p { color:#94a3b8; font-size: clamp(15px,2vw,18px); }
.btn { display:inline-block; margin: 6px; padding: 12px 22px; border-radius:8px; background:#f59e0b; color:#111; font-weight:700; text-decoration:none; min-height:44px; }
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
$cid = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'home']);

$products = tenant_all(
    'SELECT p.*, (SELECT image_path FROM product_images
                  WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ? AND p.status = "active"
      ORDER BY p.created_at DESC
      LIMIT 8',
    $cid
);
$branches = tenant_all(
    'SELECT * FROM branches WHERE company_id = ? AND status = "active" ORDER BY id',
    $cid
);
$active_voucher = tenant_one(
    'SELECT id, title FROM vouchers
      WHERE company_id = ? AND status = "active"
        AND (expiry_date IS NULL OR expiry_date >= CURDATE())
      ORDER BY created_at DESC LIMIT 1',
    $cid
);

layout_head($company);
?>
<section class="hero">
  <div class="container">
    <h1><?= e($company['name']) ?></h1>
    <p><?= e($company['description'] ?? '') ?></p>
    <div class="btn-row" style="margin-top:18px">
      <a class="btn primary" href="/catalog.php">Browse Catalog</a>
      <?php if ($active_voucher): ?>
        <a class="btn outline-light" href="/voucher.php">Get Vouchers</a>
      <?php endif; ?>
      <?php if (!empty($company['whatsapp_number'])): ?>
        <a class="btn outline-light" target="_blank" rel="noopener"
           href="<?= e(whatsapp_link($company['whatsapp_number'], 'Hi, I\'d like to know more.')) ?>">
          Chat on WhatsApp
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($active_voucher): ?>
<section style="background: linear-gradient(90deg, var(--c-secondary), #fff7ed); padding: 22px 0;">
  <div class="container" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;justify-content:space-between;">
    <div>
      <strong>🎁 <?= e($active_voucher['title']) ?></strong><br>
      <span class="muted">Register in seconds and claim your voucher.</span>
    </div>
    <a class="btn dark" href="/voucher-claim.php?id=<?= (int)$active_voucher['id'] ?>">Claim Now</a>
  </div>
</section>
<?php endif; ?>

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
      <p style="margin-top:18px"><a class="btn outline" href="/catalog.php">View full catalog →</a></p>
    <?php endif; ?>
  </div>
</section>

<?php if ($branches): ?>
<section style="background:#fff">
  <div class="container">
    <h2>Visit Our Showroom</h2>
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))">
      <?php foreach ($branches as $b):
        $maps_url = $b['google_map_link'] ?: ($b['address']
          ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($b['address'])
          : null);
        $waze_url = $b['waze_link'] ?: ($b['address']
          ? 'https://waze.com/ul?q=' . rawurlencode($b['address'])
          : null);
      ?>
        <div class="branch">
          <?php if (!empty($b['google_map_embed'])): ?>
            <div class="map"><?= $b['google_map_embed'] /* admin-trusted iframe */ ?></div>
          <?php elseif ($b['address']): ?>
            <div class="map">
              <iframe loading="lazy"
                src="https://www.google.com/maps?q=<?= e(rawurlencode($b['address'])) ?>&output=embed"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          <?php endif; ?>
          <div class="meta">
            <h3><?= e($b['name']) ?></h3>
            <?php if (!empty($b['address'])): ?>
              <div class="muted"><?= e($b['address']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['operating_hours'])): ?>
              <div class="muted">⏰ <?= e($b['operating_hours']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['phone'])): ?>
              <div>📞 <a href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a></div>
            <?php endif; ?>
          </div>
          <div class="actions">
            <?php if ($maps_url): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="<?= e($maps_url) ?>">📍 Google Maps</a>
            <?php endif; ?>
            <?php if ($waze_url): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="<?= e($waze_url) ?>" style="background:#33ccff;color:#fff;border-color:#33ccff">🚗 Waze</a>
            <?php endif; ?>
            <?php
              $branch_wa = $b['whatsapp_number'] ?: ($company['whatsapp_number'] ?? '');
              if ($branch_wa):
            ?>
              <a class="btn primary" target="_blank" rel="noopener"
                 href="<?= e(whatsapp_link($branch_wa, 'Hi, I\'m interested in visiting your showroom.')) ?>"
                 style="background:#25d366;color:#fff">💬 WhatsApp</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php layout_foot($company); ?>
