<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';

// Safety net for partial deploys (older helpers.php on the server)
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

$company = current_company();

if (!$company) {
    require __DIR__ . '/inc/corporate.php';
    exit;
}

// ===== Company landing page =====
$cid = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'home']);

// Featured products first; if fewer than 8, fall back to newest non-featured.
$products = tenant_all(
    'SELECT p.*, (SELECT image_path FROM product_images
                  WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ? AND p.status = "active"
      ORDER BY p.is_featured DESC, p.created_at DESC
      LIMIT 8',
    $cid
);
$branches = tenant_all(
    'SELECT * FROM branches WHERE company_id = ? AND status = "active" ORDER BY id',
    $cid
);
$banner_slides = [];
if (db_table_exists('company_banners')) {
    $banner_slides = tenant_all(
        'SELECT * FROM company_banners
          WHERE company_id = ? AND status = "active"
          ORDER BY sort_order ASC, id ASC',
        $cid
    );
}
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

<?php
  // Build the list of hero slides. If no per-slide rows exist, fall back to
  // the single banner stored on the companies row.
  if (!$banner_slides) {
      $banner_slides = [[
          'image'    => $company['banner_image']    ?? null,
          'title'    => $company['banner_title']    ?? null,
          'subtitle' => $company['banner_subtitle'] ?? null,
          'cta_text' => $company['banner_cta_text'] ?? null,
          'cta_url'  => $company['banner_cta_url']  ?? null,
      ]];
  }
  $is_carousel = count($banner_slides) > 1;
?>
<section id="home" class="<?= $is_carousel ? 'hero-carousel' : '' ?>">
  <?php foreach ($banner_slides as $i => $b):
    $b_title    = !empty($b['title'])    ? $b['title']    : $company['name'];
    $b_subtitle = !empty($b['subtitle']) ? $b['subtitle'] : ($company['description'] ?? '');
    $b_cta_text = !empty($b['cta_text']) ? $b['cta_text'] : 'Browse Catalog';
    $b_cta_url  = !empty($b['cta_url'])  ? $b['cta_url']  : '/catalog.php';
    $b_image    = $b['image'] ?? '';
    $slide_cls  = 'hero' . ($b_image ? ' has-bg' : '') . ($is_carousel ? ' hero-slide' : '') . ($is_carousel && $i === 0 ? ' active' : '');
    $slide_sty  = $b_image ? 'background-image:url(\'' . e($b_image) . '\');' : '';
  ?>
  <div class="<?= $slide_cls ?>" style="<?= $slide_sty ?>">
    <div class="container">
      <h1><?= e($b_title) ?></h1>
      <p><?= e($b_subtitle) ?></p>
      <div class="btn-row" style="margin-top:18px">
        <a class="btn primary" href="<?= e($b_cta_url) ?>"><?= e($b_cta_text) ?></a>
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
  </div>
  <?php endforeach; ?>

  <?php if ($is_carousel): ?>
    <button class="hero-nav prev" type="button" aria-label="Previous slide">‹</button>
    <button class="hero-nav next" type="button" aria-label="Next slide">›</button>
    <div class="hero-dots">
      <?php foreach ($banner_slides as $i => $_b): ?>
        <button type="button" data-i="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Go to slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($is_carousel): ?>
<style>
  .hero-carousel { position: relative; overflow: hidden; }
  .hero-carousel .hero-slide {
    position: absolute; inset: 0; opacity: 0;
    transition: opacity .8s ease;
    pointer-events: none;
  }
  .hero-carousel .hero-slide:first-of-type { position: relative; }
  .hero-carousel .hero-slide.active { opacity: 1; pointer-events: auto; }
  .hero-carousel .hero-nav {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
    background: rgba(0,0,0,.35); color: #fff; border: 0; cursor: pointer;
    width: 40px; height: 40px; border-radius: 999px; font-size: 26px; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
  }
  .hero-carousel .hero-nav.prev { left: 14px; }
  .hero-carousel .hero-nav.next { right: 14px; }
  .hero-carousel .hero-nav:hover { background: rgba(0,0,0,.6); }
  .hero-carousel .hero-dots {
    position: absolute; bottom: 16px; left: 0; right: 0; z-index: 3;
    display: flex; justify-content: center; gap: 8px;
  }
  .hero-carousel .hero-dots button {
    width: 10px; height: 10px; padding: 0; border: 0; cursor: pointer;
    background: rgba(255,255,255,.55); border-radius: 999px;
    transition: background .2s, width .2s;
  }
  .hero-carousel .hero-dots button.active { background: #fff; width: 28px; }
  @media (max-width: 600px) {
    .hero-carousel .hero-nav { width: 32px; height: 32px; font-size: 22px; }
    .hero-carousel .hero-nav.prev { left: 8px; }
    .hero-carousel .hero-nav.next { right: 8px; }
  }
</style>
<script>
(function () {
  var car = document.querySelector('.hero-carousel');
  if (!car) return;
  var slides = car.querySelectorAll('.hero-slide');
  var dots   = car.querySelectorAll('.hero-dots button');
  if (slides.length < 2) return;
  var cur = 0, timer = null, INTERVAL = 5500;

  function show(i) {
    cur = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('active', k === cur); });
    dots.forEach(function (d, k) { d.classList.toggle('active', k === cur); });
  }
  function next() { show(cur + 1); }
  function prev() { show(cur - 1); }
  function start() { stop(); timer = setInterval(next, INTERVAL); }
  function stop()  { if (timer) { clearInterval(timer); timer = null; } }

  car.querySelector('.hero-nav.next').addEventListener('click', function () { next(); start(); });
  car.querySelector('.hero-nav.prev').addEventListener('click', function () { prev(); start(); });
  dots.forEach(function (d, k) {
    d.addEventListener('click', function () { show(k); start(); });
  });
  car.addEventListener('mouseenter', stop);
  car.addEventListener('mouseleave', start);
  document.addEventListener('visibilitychange', function () {
    document.hidden ? stop() : start();
  });

  start();
})();
</script>
<?php endif; ?>

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
    <h2>Visit Our Showroom <a href="/visit.php" style="font-size:14px;font-weight:500;margin-left:8px;">See all →</a></h2>

    <style>
      .home-branches { display:grid; gap:10px; grid-template-columns: repeat(2, 1fr); }
      @media (min-width: 600px) { .home-branches { grid-template-columns: repeat(3, 1fr); } }
      @media (min-width: 980px) { .home-branches { grid-template-columns: repeat(4, 1fr); gap:14px; } }
      .home-branches .branch { font-size: 12px; border-radius: 10px; }
      .home-branches .branch .map { aspect-ratio: 16/10; }
      .home-branches .branch .meta { padding: 10px 12px; gap: 4px; }
      .home-branches .branch .meta h3 { margin:0 0 2px; font-size: 14px; line-height:1.2; }
      .home-branches .branch .meta .muted, .home-branches .branch .meta div { font-size: 11.5px; word-break: break-word; }
      .home-branches .branch .actions {
        display:grid; grid-template-columns: repeat(2, 1fr); gap: 4px;
        padding: 4px 12px 12px; margin-top: auto;
      }
      .home-branches .branch .actions .btn {
        padding: 6px 4px; font-size: 11px; min-height: 0; border-radius: 6px;
        font-weight: 600; line-height: 1.1; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
      }
      @media (min-width: 980px) {
        .home-branches .branch { font-size: 13px; border-radius: 12px; }
        .home-branches .branch .meta { padding: 12px 14px; gap: 5px; }
        .home-branches .branch .meta h3 { font-size: 15px; }
        .home-branches .branch .meta .muted, .home-branches .branch .meta div { font-size: 12px; }
        .home-branches .branch .actions { padding: 4px 14px 14px; gap: 6px; }
        .home-branches .branch .actions .btn { padding: 7px 6px; font-size: 12px; }
      }
    </style>

    <div class="home-branches">
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
              <a class="btn outline" target="_blank" rel="noopener" href="<?= e($maps_url) ?>">📍 Maps</a>
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
            <?php if (!empty($b['phone'])): ?>
              <a class="btn dark" href="tel:<?= e(preg_replace('/[^\d+]/', '', $b['phone'])) ?>">📞 Call</a>
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
