<?php
/**
 * Public shoppable lookbook gallery for a tenant.
 * List of scenes; each links to /lookbook/<slug> (via .htaccess rewrite
 * and the tenant subdomain bootstrap).
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'lookbook']);

$has_lookbook = function_exists('db_table_exists') && db_table_exists('lookbook_scenes');
$scenes = $has_lookbook ? tenant_all(
    'SELECT s.*,
            (SELECT COUNT(*) FROM lookbook_hotspots h
              WHERE h.company_id = s.company_id AND h.scene_id = s.id) AS pins
       FROM lookbook_scenes s
      WHERE s.company_id = ? AND s.status = "active"
      ORDER BY s.sort_order ASC, s.created_at DESC',
    $cid
) : [];

$page_meta = [
    'title'       => 'Lookbook · ' . $company['name'],
    'description' => 'Shop the look — interior design scenes featuring ' . $company['name'] . '\'s furniture, with clickable product tags.',
];
layout_head($company, 'Lookbook', 'lookbook', $page_meta);
?>

<section class="hero" style="background: linear-gradient(135deg, var(--c-primary), #000); padding: clamp(40px, 7vw, 64px) 0;">
  <div class="container">
    <h1 style="margin:0 0 8px;">🖼️ Shop the Look</h1>
    <p style="margin:0;opacity:.95;">Interior scenes designed around our furniture — tap any tag to open the product.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if (!$scenes): ?>
      <div class="box center" style="padding: 40px 16px;">
        <h2 style="margin:0 0 6px;font-size:18px;">No scenes yet</h2>
        <p class="muted" style="margin:0 0 14px;">Come back soon — new interior scenes are being added.</p>
        <a class="btn primary" href="/catalog.php">Browse Catalog</a>
      </div>
    <?php else: ?>
      <p class="muted" style="margin:0 0 14px;">
        <?= count($scenes) ?> scene<?= count($scenes) === 1 ? '' : 's' ?>
      </p>

      <style>
        .lookgrid { display:grid; gap:16px; grid-template-columns: 1fr; }
        @media (min-width: 640px) { .lookgrid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 980px) { .lookgrid { grid-template-columns: repeat(3, 1fr); } }
        .look-card { background:#fff; border-radius:12px; overflow:hidden;
                     box-shadow:0 1px 3px rgba(0,0,0,.06); position:relative;
                     display:block; text-decoration:none; color:inherit;
                     transition: transform .2s, box-shadow .2s; }
        .look-card:hover { transform: translateY(-2px); box-shadow:0 10px 22px rgba(0,0,0,.1); }
        .look-card .cover { aspect-ratio: 16/10; background: #e5e7eb; }
        .look-card .cover img { width:100%; height:100%; object-fit:cover; }
        .look-card .pins-badge {
          position:absolute; top:12px; right:12px;
          background: rgba(0,0,0,.7); color:#fff;
          padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;
        }
        .look-card .pad { padding: 14px 16px; }
        .look-card h3 { margin:0 0 4px; font-size:16px; line-height:1.3; }
        .look-card p { margin:0; font-size:13px; color:#6b7280; line-height:1.5; }
      </style>

      <div class="lookgrid">
        <?php foreach ($scenes as $s): ?>
          <a class="look-card" href="/lookbook/<?= e($s['slug']) ?>">
            <div class="cover">
              <?php if (!empty($s['image_path'])): ?>
                <img src="<?= e($s['image_path']) ?>" alt="<?= e($s['cover_alt'] ?: $s['title']) ?>" loading="lazy">
              <?php endif; ?>
            </div>
            <?php if ((int)$s['pins'] > 0): ?>
              <span class="pins-badge">📍 <?= (int)$s['pins'] ?> item<?= (int)$s['pins'] === 1 ? '' : 's' ?></span>
            <?php endif; ?>
            <div class="pad">
              <h3><?= e($s['title']) ?></h3>
              <?php if (!empty($s['description'])): ?>
                <p><?= e(mb_strimwidth($s['description'], 0, 120, '…')) ?></p>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
