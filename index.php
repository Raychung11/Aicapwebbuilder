<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';

$company = current_company();

if (!$company) {
    // ===== HQ landing =====
    $companies = db_all('SELECT id, name, slug, subdomain, logo, theme_color
                           FROM companies WHERE status = "active" ORDER BY name');
    $on_platform = (($_SERVER['HTTP_HOST'] ?? '') === APP_BASE_DOMAIN
                  || ($_SERVER['HTTP_HOST'] ?? '') === 'www.' . APP_BASE_DOMAIN);
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(APP_NAME) ?></title>
<style>
body { margin:0; font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; background:#0f172a; color:#fff; }
.wrap { max-width: 980px; margin: 0 auto; padding: clamp(40px,10vw,80px) 20px; }
.hero { text-align:center; }
h1 { font-size: clamp(30px,6vw,46px); margin: 0 0 16px; }
.lead { color:#94a3b8; font-size: clamp(15px,2vw,18px); }
.btn { display:inline-flex; align-items:center; gap:6px; margin: 6px; padding: 12px 22px; border-radius:8px; background:#f59e0b; color:#111; font-weight:700; text-decoration:none; min-height:44px; }
.btn.outline { background:transparent; border:1px solid #334155; color:#fff; }
h2 { font-size: 20px; margin: 40px 0 16px; }
.tenants { display:grid; gap:14px; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
.tenant { background:#1e293b; border-radius:10px; padding: 16px; display:flex; flex-direction:column; gap:8px; }
.tenant .head { display:flex; align-items:center; gap:10px; }
.tenant .head img { width:36px; height:36px; object-fit:contain; background:#fff; border-radius:6px; padding:3px; }
.tenant .head strong { color:#fff; }
.tenant .actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:auto; }
.tenant .actions a { font-size: 13px; padding: 7px 12px; border-radius:6px; text-decoration:none; }
.tenant .a-prev { background:#2563eb; color:#fff; }
.tenant .a-open { background:transparent; border:1px solid #334155; color:#cbd5e1; }
.note { background:#1e293b; border-left:3px solid #f59e0b; padding:14px 16px; border-radius:6px; margin: 24px 0; font-size:14px; color:#cbd5e1; }
.note code { background:#0f172a; padding:2px 6px; border-radius:4px; color:#f59e0b; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <h1>AICAP Furniture BOS</h1>
    <p class="lead">Multi-tenant SaaS for furniture brands.<br>
       Each licensed company gets its own subdomain, catalog, vouchers and analytics.</p>
    <p>
      <a class="btn" href="/admin/login.php">Super Admin Login</a>
      <a class="btn outline" href="/company-admin/login.php">Company Admin Login</a>
    </p>
  </div>

  <?php if (!$on_platform): ?>
    <div class="note">
      You're on a preview URL. Subdomain routing only takes over once DNS for
      <code><?= e(APP_BASE_DOMAIN) ?></code> points to this server.
      Until then, click <strong>Preview</strong> below to browse a tenant.
    </div>
  <?php endif; ?>

  <?php if ($companies): ?>
    <h2>Tenants <span style="color:#94a3b8;font-weight:normal">(<?= count($companies) ?>)</span></h2>
    <div class="tenants">
      <?php foreach ($companies as $co): ?>
        <div class="tenant" style="border-top: 3px solid <?= e($co['theme_color'] ?: '#334155') ?>;">
          <div class="head">
            <?php if (!empty($co['logo'])): ?>
              <img src="<?= e($co['logo']) ?>" alt="">
            <?php else: ?>
              <div style="width:36px;height:36px;background:#334155;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;">
                <?= e(strtoupper(substr($co['name'], 0, 1))) ?>
              </div>
            <?php endif; ?>
            <strong><?= e($co['name']) ?></strong>
          </div>
          <div style="color:#94a3b8;font-size:13px;">
            <?= e($co['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?>
          </div>
          <div class="actions">
            <a class="a-prev" href="/?as=<?= e($co['slug']) ?>">Preview here</a>
            <?php if ($on_platform): ?>
              <a class="a-open" href="<?= e(company_url($co)) ?>">Open subdomain ↗</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body></html>
<?php
    exit;
}

// ===== Company landing page =====
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
$vouchers = tenant_all(
    'SELECT * FROM vouchers
      WHERE company_id = ? AND status = "active"
        AND (expiry_date IS NULL OR expiry_date >= CURDATE())
      ORDER BY created_at DESC LIMIT 6',
    $cid
);

// ----- Build the section index dynamically -----
$sections = [];
$sections[] = ['id' => 'home',     'label' => 'Home'];
if ($products) $sections[] = ['id' => 'products', 'label' => 'Featured'];
if ($vouchers) $sections[] = ['id' => 'vouchers', 'label' => 'Vouchers'];
if ($branches) $sections[] = ['id' => 'visit',    'label' => 'Visit Us'];
$sections[] = ['id' => 'about',    'label' => 'About'];

$page_title = '';
$page_id    = 'home';
require __DIR__ . '/inc/header.php';
?>

<nav class="section-index" aria-label="Page sections">
  <div class="container">
    <?php foreach ($sections as $s): ?>
      <a href="#<?= e($s['id']) ?>" data-anchor="<?= e($s['id']) ?>"><?= e($s['label']) ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<section id="home" class="hero">
  <div class="container">
    <h1><?= e($company['name']) ?></h1>
    <p><?= e($company['description'] ?? '') ?></p>
    <div class="btn-row" style="margin-top:18px">
      <a class="btn primary" href="/catalog.php">Browse Catalog</a>
      <?php if ($vouchers): ?>
        <a class="btn outline-light" href="#vouchers">Get Vouchers</a>
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

<?php if ($vouchers): ?>
<section id="vouchers" style="background: linear-gradient(180deg, #fff7ed, #fff);">
  <div class="container">
    <h2>🎁 Active Vouchers</h2>
    <p class="muted" style="margin-top:-8px">Quick register, claim instantly, redeem in store.</p>
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
      <?php foreach ($vouchers as $v): ?>
        <div class="box" style="display:flex;flex-direction:column;gap:8px;">
          <h3 style="margin:0;font-size:17px"><?= e($v['title']) ?></h3>
          <?php if ($v['type'] === 'percent'): ?>
            <div style="font-size:26px;font-weight:800;color:var(--c-primary);"><?= e((string)$v['value']) ?>% OFF</div>
          <?php elseif ($v['type'] === 'fixed'): ?>
            <div style="font-size:26px;font-weight:800;color:var(--c-primary);">RM <?= e(number_format((float)$v['value'], 2)) ?> OFF</div>
          <?php else: ?>
            <div style="font-size:18px;font-weight:600;color:var(--c-primary)"><?= e(strtoupper($v['type'])) ?></div>
          <?php endif; ?>
          <?php if (!empty($v['description'])): ?><p class="muted" style="margin:0;"><?= e($v['description']) ?></p><?php endif; ?>
          <?php if (!empty($v['expiry_date'])): ?><div class="muted">⏳ <?= e($v['expiry_date']) ?></div><?php endif; ?>
          <a class="btn primary block" style="margin-top:auto"
             href="/voucher-claim.php?id=<?= (int)$v['id'] ?>">Claim Now</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($products): ?>
<section id="products">
  <div class="container">
    <h2>Featured Products</h2>
    <div class="grid">
      <?php foreach ($products as $p): ?>
        <a class="card" style="text-decoration:none;color:inherit" href="/product.php?id=<?= (int)$p['id'] ?>">
          <div class="img">
            <?php if (!empty($p['img'])): ?><img src="<?= e($p['img']) ?>" alt="" loading="lazy"><?php endif; ?>
          </div>
          <div class="pad">
            <h3><?= e($p['name']) ?></h3>
            <div class="price"><?= e(format_price($p['price_min'], $p['price_max'])) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:18px"><a class="btn outline" href="/catalog.php">View full catalog →</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($branches): ?>
<section id="visit" style="background:#fff">
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
            <?php if (!empty($b['address'])):         ?><div class="muted"><?= e($b['address']) ?></div><?php endif; ?>
            <?php if (!empty($b['operating_hours'])): ?><div class="muted">⏰ <?= e($b['operating_hours']) ?></div><?php endif; ?>
            <?php if (!empty($b['phone'])):           ?><div>📞 <a href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a></div><?php endif; ?>
          </div>
          <div class="actions">
            <?php if ($maps_url): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="<?= e($maps_url) ?>">📍 Google Maps</a>
            <?php endif; ?>
            <?php if ($waze_url): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="<?= e($waze_url) ?>"
                 style="background:#33ccff;color:#fff;border-color:#33ccff">🚗 Waze</a>
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

<section id="about">
  <div class="container">
    <h2>About <?= e($company['name']) ?></h2>
    <div class="split">
      <div>
        <p style="font-size:16px;line-height:1.6;">
          <?= e($company['description'] ?: 'Welcome to ' . $company['name'] . '. Discover quality furniture for your home or office.') ?>
        </p>
        <div class="btn-row" style="margin-top:14px">
          <a class="btn primary" href="/catalog.php">Browse Catalog</a>
          <?php if ($vouchers): ?>
            <a class="btn outline" href="#vouchers">View Vouchers</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="box">
        <h3 style="margin:0 0 10px">Get in touch</h3>
        <?php if (!empty($company['address'])): ?><div>📍 <?= e($company['address']) ?></div><?php endif; ?>
        <?php if (!empty($company['phone'])):   ?><div>📞 <a href="tel:<?= e($company['phone']) ?>"><?= e($company['phone']) ?></a></div><?php endif; ?>
        <?php if (!empty($company['email'])):   ?><div>✉️ <a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a></div><?php endif; ?>
        <?php if (!empty($company['whatsapp_number'])): ?>
          <div>💬 <a target="_blank" rel="noopener"
                    href="<?= e(whatsapp_link($company['whatsapp_number'])) ?>">WhatsApp Us</a></div>
        <?php endif; ?>
        <?php if (!empty($company['operating_hours'])): ?><div style="margin-top:8px">⏰ <?= e($company['operating_hours']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
// Highlight the section index entry for whichever section is in view.
(function () {
  var nav  = document.querySelector('.section-index');
  if (!nav) return;
  var links = nav.querySelectorAll('a[data-anchor]');
  var map = {};
  links.forEach(function (a) { map[a.dataset.anchor] = a; });
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        links.forEach(function (a) { a.classList.remove('active'); });
        var hit = map[e.target.id];
        if (hit) hit.classList.add('active');
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
  Object.keys(map).forEach(function (id) {
    var el = document.getElementById(id);
    if (el) io.observe(el);
  });
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
