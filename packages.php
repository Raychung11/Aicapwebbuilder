<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'packages']);

$packages = tenant_all(
    'SELECT * FROM packages
      WHERE company_id = ? AND status = "active"
      ORDER BY is_featured DESC, sort_order, created_at DESC',
    $cid
);

$page_title = 'Furniture Packages | ' . $company['name'];
$page_meta  = [
    'title'       => $page_title,
    'description' => 'Curated furniture room packages — fully furnished bedrooms, living rooms and dining sets at one bundle price.',
    'image'       => $company['og_image'] ?: ($company['logo'] ?? ''),
];

layout_head($company, 'Packages', 'packages', $page_meta);
?>
<style>
.pkg-card {
  background:#fff; border-radius: 14px; overflow:hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.08);
  display:flex; flex-direction:column; text-decoration:none; color:inherit;
  transition: transform .15s, box-shadow .15s;
}
.pkg-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,.12); }
.pkg-card .img { aspect-ratio: 16/9; background:#eee; position:relative; }
.pkg-card .img img { width:100%; height:100%; object-fit:cover; display:block; }
.pkg-card .img .badge {
  position:absolute; top:12px; left:12px; background: var(--c-secondary); color:#111;
  padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; letter-spacing:.04em;
  text-transform: uppercase;
}
.pkg-card .img .star {
  position:absolute; top:12px; right:12px; background: rgba(0,0,0,.5); color:#f59e0b;
  width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;
  font-size: 16px;
}
.pkg-card .body { padding: 16px; flex:1; display:flex; flex-direction:column; gap:6px; }
.pkg-card h3 { margin:0; font-size: 18px; line-height: 1.2; }
.pkg-card .sub { color:#6b7280; font-size: 13px; }
.pkg-card .price { font-size: 24px; font-weight: 800; color: var(--c-primary); margin-top:auto; }
.pkg-card .was { color:#9ca3af; font-size: 13px; text-decoration: line-through; margin-left:6px; }
.pkg-card .cta { background: var(--c-primary); color:#fff; padding: 10px; text-align:center; font-weight:700; }
</style>

<section>
  <div class="container">
    <h1 style="margin:0 0 4px;">Furniture Packages</h1>
    <p class="muted" style="margin:0 0 22px;">
      Curated room bundles — get your home fully furnished at one bundle price.
    </p>

    <?php if (!$packages): ?>
      <div class="box center" style="padding:32px;">
        <p class="muted">No packages published yet. Check back soon.</p>
      </div>
    <?php else: ?>
      <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
        <?php foreach ($packages as $p): ?>
          <a class="pkg-card" href="/package.php?id=<?= (int) $p['id'] ?>">
            <div class="img">
              <?php if (!empty($p['hero_image'])): ?>
                <img src="<?= e($p['hero_image']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
              <?php endif; ?>
              <?php if (!empty($p['badge'])): ?>
                <span class="badge"><?= e($p['badge']) ?></span>
              <?php endif; ?>
              <?php if ($p['is_featured']): ?><span class="star">⭐</span><?php endif; ?>
            </div>
            <div class="body">
              <h3><?= e($p['title']) ?></h3>
              <?php if (!empty($p['subtitle'])): ?>
                <div class="sub"><?= e($p['subtitle']) ?></div>
              <?php endif; ?>
              <?php if ($p['price'] !== null): ?>
                <div class="price">
                  RM <?= number_format((float) $p['price'], 0) ?>
                  <?php if ($p['was_price'] !== null): ?>
                    <span class="was">RM <?= number_format((float) $p['was_price'], 0) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="cta"><?= e($p['cta_text'] ?: 'View Package →') ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php layout_foot($company); ?>
