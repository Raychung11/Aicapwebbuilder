<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/packages.php';

// Safety net for older inc/helpers.php after a partial deploy
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

$company = require_company();
$cid     = (int) $company['id'];
$id      = (int) input('id', 0);
$slug    = trim((string) input('slug', ''));

$package = null;
if (db_table_exists('packages')) {
    if ($slug !== '') {
        $package = tenant_one(
            'SELECT * FROM packages WHERE company_id = ? AND slug = ? AND status = "active" LIMIT 1',
            $cid, [$slug]
        );
    } elseif ($id) {
        $package = tenant_one(
            'SELECT * FROM packages WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
            $cid, [$id]
        );
    }
}

if (!$package) {
    http_response_code(404);
    layout_head($company, 'Package not found');
    echo '<section><div class="container"><h1>Package not found</h1><p><a class="btn outline" href="/packages.php">All packages</a></p></div></section>';
    layout_foot($company);
    exit;
}

track_event($cid, 'package_view', [
    'entity_type' => 'package', 'entity_id' => (int) $package['id'],
]);

$sections = tenant_all(
    'SELECT * FROM package_sections WHERE company_id = ? AND package_id = ? ORDER BY sort_order, id',
    $cid, [(int) $package['id']]
);

// Pre-load image-only legacy choices for all sections in one query
$choices_by_section = [];
// Pre-load product items per section (real catalog products)
$items_by_section = [];
if ($sections) {
    $sids = array_column($sections, 'id');
    $ph   = implode(',', array_fill(0, count($sids), '?'));
    $rows = db_all(
        "SELECT * FROM package_choices WHERE company_id = ? AND section_id IN ({$ph})
          ORDER BY sort_order, id",
        array_merge([$cid], array_map('intval', $sids))
    );
    foreach ($rows as $r) {
        $choices_by_section[(int) $r['section_id']][] = $r;
    }

    if (db_table_exists('package_section_items')) {
        $item_rows = package_items_for_package((int) $package['id'], $cid);
        foreach ($item_rows as $r) {
            $items_by_section[(int) $r['section_id']][] = $r;
        }
    }
}

// Auto-computed retail price (used as strikethrough anchor when was_price is blank)
$retail_price = db_table_exists('package_section_items')
    ? package_retail_price((int) $package['id'], $cid)
    : 0.0;
$strike_price = $package['was_price'] !== null
    ? (float) $package['was_price']
    : $retail_price;

$features = [];
if (!empty($package['features_json'])) {
    $decoded = json_decode($package['features_json'], true);
    if (is_array($decoded)) $features = $decoded;
}

// CTA target — default to WhatsApp the company about this specific package
$cta_url = $package['cta_url'];
if (empty($cta_url)) {
    if (!empty($company['whatsapp_number'])) {
        $msg = "Hi, I'm interested in the package: " . $package['title']
             . ($package['price'] !== null ? ' (RM ' . number_format((float) $package['price'], 0) . ')' : '');
        $cta_url = whatsapp_link($company['whatsapp_number'], $msg);
    } else {
        $cta_url = '/visit.php';
    }
}
$cta_text = $package['cta_text'] ?: 'Book This Package';

// ----- Smart SEO defaults -----
// Title: explicit meta_title → "<title> — From RM N | <company>" → "<title> | <company>"
$auto_title = $package['title'];
if ($package['price'] !== null) {
    $auto_title .= ' — From RM ' . number_format((float) $package['price'], 0);
}
$auto_title .= ' | ' . $company['name'];

// Description: explicit meta_description → subtitle + features + savings
$auto_desc_parts = [];
if (!empty($package['subtitle']))          $auto_desc_parts[] = $package['subtitle'];
elseif (!empty($package['description']))   $auto_desc_parts[] = mb_substr(strip_tags($package['description']), 0, 120);

if ($package['price'] !== null) {
    $price_line = 'From RM ' . number_format((float) $package['price'], 0);
    $strike_calc = $package['was_price'] !== null
        ? (float) $package['was_price']
        : (db_table_exists('package_section_items') ? package_retail_price((int) $package['id'], $cid) : 0.0);
    if ($strike_calc > 0 && $strike_calc > (float) $package['price']) {
        $savings = $strike_calc - (float) $package['price'];
        $pct     = round($savings / $strike_calc * 100);
        $price_line .= ' (save RM ' . number_format($savings, 0) . ', ' . $pct . '% off)';
    }
    $auto_desc_parts[] = $price_line;
}

if (!empty($features)) {
    $auto_desc_parts[] = implode(' · ', array_map(
        fn($f) => $f['label'] ?? '',
        array_slice($features, 0, 4)
    ));
}
$auto_desc = trim(implode('. ', array_filter($auto_desc_parts)));
$auto_desc = mb_substr($auto_desc, 0, 250);

$page_meta = [
    'title'       => !empty($package['meta_title'])       ? $package['meta_title']       : $auto_title,
    'description' => !empty($package['meta_description']) ? $package['meta_description'] : $auto_desc,
    'image'       => $package['hero_image'] ?: ($company['og_image'] ?: ($company['logo'] ?? '')),
    'type'        => 'product',
];

layout_head($company, $package['title'], 'packages', $page_meta);
?>
<style>
.pkg-hero {
  position: relative; color:#fff; padding: 0;
  background: linear-gradient(135deg, var(--c-primary), #000);
  overflow: hidden;
}
.pkg-hero .bg-img { position: absolute; inset: 0; background-size: cover; background-position: center; }
.pkg-hero::after { content: ""; position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,.55), rgba(0,0,0,.7)); }
.pkg-hero .container { position: relative; z-index: 2; padding: clamp(40px, 8vw, 80px) 16px; }
.pkg-hero .badge { display:inline-block; background: var(--c-secondary); color:#111;
  padding:6px 14px; border-radius:999px; font-size:12px; font-weight:800; letter-spacing:.04em;
  text-transform: uppercase; margin-bottom: 14px; }
.pkg-hero h1 { font-size: clamp(28px, 6vw, 52px); margin: 0 0 12px; line-height: 1.05; max-width: 820px;
  text-shadow: 0 2px 16px rgba(0,0,0,.3); }
.pkg-hero .sub { font-size: clamp(15px, 2.2vw, 19px); opacity:.95; max-width: 720px; margin: 0; }
.pkg-hero .price-row { display:flex; align-items:baseline; gap:14px; flex-wrap: wrap;
  margin-top: 22px; }
.pkg-hero .from { font-size: 13px; color: #fef3c7; text-transform: uppercase; letter-spacing:.06em; }
.pkg-hero .price { font-size: clamp(38px, 8vw, 72px); font-weight: 900; color: #fff;
  text-shadow: 0 4px 18px rgba(0,0,0,.4); letter-spacing:-0.02em; }
.pkg-hero .was { font-size: clamp(20px, 3vw, 28px); color:#fca5a5; text-decoration: line-through; font-weight:700; }
.pkg-hero .cta-row { margin-top: 20px; display:flex; flex-wrap: wrap; gap: 10px; }

.feature-strip { background:#fff; border-bottom:1px solid #e5e7eb; padding: 18px 0; }
.feature-strip .grid { display:flex; flex-wrap:wrap; gap: 12px; justify-content:center; }
.feature-strip .pill {
  display:flex; align-items:center; gap:8px; padding: 10px 16px; border-radius:999px;
  background:#f9fafb; border:1px solid #e5e7eb; font-size:14px; font-weight:600;
}
.feature-strip .pill .ic { font-size:18px; }

.pwp-band {
  background: linear-gradient(90deg, var(--c-secondary), #fef3c7);
  padding: 18px 0; border-top:1px solid rgba(0,0,0,.05); border-bottom:1px solid rgba(0,0,0,.05);
}
.pwp-band p { margin:0; font-weight:700; color:#111; max-width: 820px; }
.pwp-band .pill { display:inline-block; padding:4px 10px; border-radius:999px;
  background:#111; color: var(--c-secondary); font-size:11px; font-weight:800; letter-spacing:.06em;
  text-transform: uppercase; margin-right:10px; }

section.pkg-section {
  padding: clamp(32px, 5vw, 56px) 0;
  border-bottom: 1px solid #f3f4f6;
}
section.pkg-section:nth-child(even) { background:#f9fafb; }
section.pkg-section .head { display:grid; gap:18px; grid-template-columns: 1fr; align-items:center; }
@media (min-width: 760px) {
  section.pkg-section .head { grid-template-columns: 1.1fr 1fr; }
  section.pkg-section.flip .head .text { order: 2; }
  section.pkg-section.flip .head .img  { order: 1; }
}
section.pkg-section .img {
  aspect-ratio: 4/3; border-radius: 14px; background:#eee; overflow:hidden;
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
}
section.pkg-section .img img { width:100%; height:100%; object-fit:cover; display:block; }
section.pkg-section h2 { font-size: clamp(22px, 3.4vw, 30px); margin: 0 0 10px;
  display:inline-block; padding: 4px 14px; border-radius:6px;
  background: var(--c-primary); color:#fff; }
section.pkg-section .desc { font-size: 16px; line-height: 1.7; color: #1f2937; white-space: pre-line; }
section.pkg-section .desc strong { color: var(--c-primary); }

.choices {
  display:grid; gap:12px; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  margin-top: 22px;
}
.choice {
  background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; position:relative;
  display:flex; flex-direction:column;
}
.choice .num {
  position:absolute; top:8px; left:8px; width:28px; height:28px; border-radius:50%;
  background: var(--c-primary); color:#fff; display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:800; box-shadow: 0 2px 6px rgba(0,0,0,.18); z-index: 1;
}
.choice .ph { aspect-ratio: 1/1; background:#f3f4f6; }
.choice .ph img { width:100%; height:100%; object-fit:cover; display:block; }
.choice .lbl { padding: 8px 10px; font-size: 12px; font-weight:600; color:#374151; text-align:center; }

.sticky-cta {
  position: sticky; bottom: 12px; z-index: 30; padding: 10px 16px; pointer-events:none;
}
.sticky-cta .bar {
  background:#fff; border-radius: 14px; box-shadow: 0 8px 28px rgba(0,0,0,.18);
  padding: 12px 14px; display:flex; align-items:center; gap:14px; pointer-events:auto;
  border:1px solid #e5e7eb;
}
.sticky-cta .bar .price { font-size: 20px; font-weight: 800; color: var(--c-primary); }
.sticky-cta .bar .label { color:#6b7280; font-size: 12px; }
.sticky-cta .bar .grow { flex:1; min-width: 0; }
.sticky-cta .bar .grow strong { display:block; font-size: 14px; line-height:1.1;
  white-space: nowrap; overflow:hidden; text-overflow: ellipsis; }
.sticky-cta .bar a.btn { white-space: nowrap; }
</style>

<!-- HERO -->
<div class="pkg-hero">
  <?php if (!empty($package['hero_image'])): ?>
    <div class="bg-img" style="background-image:url('<?= e($package['hero_image']) ?>');"></div>
  <?php endif; ?>
  <div class="container">
    <?php if (!empty($package['badge'])): ?>
      <span class="badge"><?= e($package['badge']) ?></span>
    <?php endif; ?>
    <h1><?= e($package['title']) ?></h1>
    <?php if (!empty($package['subtitle'])): ?>
      <p class="sub"><?= e($package['subtitle']) ?></p>
    <?php endif; ?>
    <?php if (!empty($package['description'])): ?>
      <p class="sub" style="margin-top:10px;"><?= e($package['description']) ?></p>
    <?php endif; ?>

    <?php if ($package['price'] !== null): ?>
      <div class="price-row">
        <div>
          <div class="from">From only</div>
          <div class="price">RM <?= number_format((float) $package['price'], 0) ?></div>
        </div>
        <?php if ($strike_price > 0 && $strike_price > (float) $package['price']):
          $savings = $strike_price - (float) $package['price'];
        ?>
          <div>
            <span class="was">RM <?= number_format($strike_price, 0) ?></span>
            <div style="color:#fef3c7;font-size:14px;margin-top:4px;font-weight:700;">
              Save RM <?= number_format($savings, 0) ?>
              (<?= round($savings / $strike_price * 100) ?>% off)
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="cta-row">
      <a class="btn primary" href="<?= e($cta_url) ?>" <?= str_starts_with($cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
        💬 <?= e($cta_text) ?>
      </a>
      <a class="btn outline" href="/packages.php" style="background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.4);">
        ← All packages
      </a>
    </div>
  </div>
</div>

<!-- FEATURES -->
<?php if ($features): ?>
<div class="feature-strip">
  <div class="container grid">
    <?php foreach ($features as $f): ?>
      <div class="pill"><span class="ic"><?= e($f['icon'] ?? '✓') ?></span><?= e($f['label'] ?? '') ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- PWP BLURB -->
<?php if (!empty($package['pwp_blurb'])): ?>
<div class="pwp-band">
  <div class="container">
    <p><span class="pill">PWP Special</span><?= e($package['pwp_blurb']) ?></p>
  </div>
</div>
<?php endif; ?>

<!-- SECTIONS -->
<style>
.pkg-items { list-style:none; padding:0; margin: 12px 0 0; display:grid; gap: 8px; }
.pkg-items li { display:flex; align-items:center; gap:10px; padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
.pkg-items li:last-child { border-bottom: 0; }
.pkg-items .thumb { width: 44px; height: 44px; border-radius:8px; background:#eee; overflow:hidden; flex-shrink:0; }
.pkg-items .thumb img { width:100%; height:100%; object-fit:cover; }
.pkg-items .name { flex:1; font-weight: 600; color:#111; }
.pkg-items .qty  { color:#6b7280; font-size: 13px; }
.pkg-items .price{ color: var(--c-primary); font-weight:700; white-space:nowrap; }
.pkg-section .kind-pill { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px;
  font-weight:700; letter-spacing:.04em; text-transform: uppercase; margin-left: 8px; vertical-align: middle; }
.pkg-section .kind-pill.choice  { background:#fef3c7; color:#b45309; }
.pkg-section .kind-pill.included{ background:#dcfce7; color:#166534; }
</style>
<?php foreach ($sections as $i => $s):
  $sec_items     = $items_by_section[(int) $s['id']] ?? [];
  $section_choices = $choices_by_section[(int) $s['id']] ?? [];
  $flip = ($i % 2 === 1);
  $kind = $s['kind'] ?? 'included';
?>
  <section class="pkg-section <?= $flip ? 'flip' : '' ?>">
    <div class="container">
      <div class="head">
        <div class="text">
          <h2>
            <?= e($s['title']) ?>
            <?php if ($kind === 'choice'): ?>
              <span class="kind-pill choice">Pick one</span>
            <?php endif; ?>
          </h2>

          <?php if ($sec_items): ?>
            <ul class="pkg-items">
              <?php foreach ($sec_items as $it):
                $unit = product_unit_price($it);
                $qty  = (int) $it['quantity'];
                $line = $unit * $qty;
              ?>
                <li>
                  <div class="thumb">
                    <?php if (!empty($it['img'])): ?>
                      <img src="<?= e($it['img']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                  </div>
                  <span class="name"><?= e($it['product_name']) ?></span>
                  <?php if ($qty > 1): ?>
                    <span class="qty">× <?= $qty ?></span>
                  <?php endif; ?>
                  <?php if ($unit > 0): ?>
                    <span class="price">RM <?= number_format($line, 0) ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php elseif (!empty($s['description'])): ?>
            <div class="desc"><?= e($s['description']) ?></div>
          <?php endif; ?>
        </div>
        <div class="img">
          <?php if (!empty($s['image'])): ?>
            <img src="<?= e($s['image']) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
          <?php endif; ?>
        </div>
      </div>

      <?php if ($section_choices): ?>
        <div class="choices">
          <?php foreach ($section_choices as $idx => $ch): ?>
            <div class="choice">
              <span class="num"><?= $idx + 1 ?></span>
              <div class="ph"><img src="<?= e($ch['image']) ?>" alt="" loading="lazy"></div>
              <?php if (!empty($ch['label'])): ?>
                <div class="lbl"><?= e($ch['label']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<!-- STICKY BOTTOM CTA -->
<div class="sticky-cta">
  <div class="container">
    <div class="bar">
      <div class="grow">
        <strong><?= e($package['title']) ?></strong>
        <?php if ($package['price'] !== null): ?>
          <span class="label">From</span>
          <span class="price">RM <?= number_format((float) $package['price'], 0) ?></span>
        <?php endif; ?>
      </div>
      <a class="btn primary" href="<?= e($cta_url) ?>" <?= str_starts_with($cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
        💬 <?= e($cta_text) ?>
      </a>
    </div>
  </div>
</div>

<?php layout_foot($company); ?>
