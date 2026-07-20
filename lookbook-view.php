<?php
/**
 * Public shoppable lookbook scene viewer.
 * URL: /lookbook/<slug> (via .htaccess rewrite) or /lookbook-view.php?slug=X.
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
$slug    = trim((string) input('slug', ''));

if ($slug === '' || !function_exists('db_table_exists') || !db_table_exists('lookbook_scenes')) {
    http_response_code(404);
    layout_head($company, 'Scene not found', 'lookbook');
    echo '<section><div class="container" style="padding:60px 16px;text-align:center;">'
       . '<h1>Scene not found</h1><p class="muted">This lookbook scene is not available.</p>'
       . '<p><a class="btn primary" href="/lookbook.php">← Back to the lookbook</a></p>'
       . '</div></section>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$scene = tenant_one(
    'SELECT * FROM lookbook_scenes WHERE company_id = ? AND slug = ? AND status = "active" LIMIT 1',
    $cid, [$slug]
);
if (!$scene) {
    http_response_code(404);
    layout_head($company, 'Scene not found', 'lookbook');
    echo '<section><div class="container" style="padding:60px 16px;text-align:center;">'
       . '<h1>Scene not found</h1><p class="muted">This scene may have been removed or renamed.</p>'
       . '<p><a class="btn primary" href="/lookbook.php">← Back to the lookbook</a></p>'
       . '</div></section>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

// Best-effort view count bump
try {
    db_exec('UPDATE lookbook_scenes SET view_count = view_count + 1 WHERE id = ?', [(int) $scene['id']]);
} catch (Throwable $e) { /* ignore */ }
track_event($cid, 'lookbook_view', ['entity_type' => 'lookbook_scene', 'entity_id' => (int) $scene['id']]);

$hotspots = tenant_all(
    'SELECT h.id, h.product_id, h.x_pct, h.y_pct, h.label, h.sort_order,
            p.name AS product_name, p.slug AS product_slug,
            p.price_min, p.price_max, p.is_promo, p.promo_price,
            (SELECT image_path FROM product_images
              WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS product_img
       FROM lookbook_hotspots h
       JOIN products p ON p.id = h.product_id
      WHERE h.company_id = ? AND h.scene_id = ? AND p.status = "active"
      ORDER BY h.sort_order',
    $cid, [(int) $scene['id']]
);

$page_meta = [
    'title'       => $scene['title'] . ' · ' . $company['name'] . ' Lookbook',
    'description' => $scene['description'] ?: ('Shop the look from ' . $company['name'] . ' — every furniture piece in this scene is clickable.'),
    'image'       => $scene['image_path'],
    'type'        => 'article',
];
layout_head($company, $scene['title'], 'lookbook', $page_meta);
?>

<section style="padding: 20px 0;">
  <div class="container">
    <p style="margin:0 0 12px;">
      <a href="/lookbook.php" style="text-decoration:none;color:var(--c-primary);font-weight:600;">← All scenes</a>
    </p>
    <h1 style="margin:0 0 6px;font-size: clamp(22px,3vw,30px);"><?= e($scene['title']) ?></h1>
    <?php if (!empty($scene['description'])): ?>
      <p style="margin:0 0 16px;color:#4b5563;max-width:720px;"><?= e($scene['description']) ?></p>
    <?php endif; ?>

    <style>
      .lv-canvas {
        position: relative; background:#0b1020; border-radius: 14px;
        overflow: hidden; user-select: none; margin-top: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,.12);
      }
      .lv-canvas img { display:block; width:100%; height:auto; }
      .lv-pin {
        position: absolute; transform: translate(-50%, -50%);
        width: 34px; height: 34px; border-radius: 999px;
        background: var(--c-primary, #f59e0b); color:#111;
        border: 3px solid #fff; box-shadow: 0 4px 14px rgba(0,0,0,.35);
        display:flex; align-items:center; justify-content:center;
        font-weight: 800; font-size: 14px; cursor: pointer;
        z-index: 3; animation: lvPulse 2.2s ease-in-out infinite;
      }
      @keyframes lvPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .55), 0 4px 14px rgba(0,0,0,.35); }
        50%      { box-shadow: 0 0 0 12px rgba(245, 158, 11, 0),  0 4px 14px rgba(0,0,0,.35); }
      }
      .lv-pin:hover, .lv-pin.active { animation: none; transform: translate(-50%, -50%) scale(1.15); }
      .lv-card {
        position: absolute; z-index: 5; width: 240px; background:#fff;
        border-radius: 12px; overflow: hidden; box-shadow: 0 14px 32px rgba(0,0,0,.25);
        transform: translate(-50%, 12px); text-decoration:none; color:inherit;
        display:none;
      }
      .lv-card.open { display:block; }
      .lv-card .thumb { width: 100%; aspect-ratio: 4/3; object-fit: cover; background:#eee; }
      .lv-card .pad { padding: 12px 14px 14px; }
      .lv-card h4 { margin: 0 0 4px; font-size: 14px; line-height:1.3; }
      .lv-card .price { color: var(--c-primary, #f59e0b); font-weight: 800; font-size: 14px; }
      .lv-card .promo { color:#9ca3af; text-decoration: line-through; font-size:12px; margin-left:4px; }
      .lv-card .cta {
        display: block; text-align: center; margin-top: 8px; padding: 8px;
        background: #111; color:#fff; border-radius: 6px; font-size: 13px; font-weight: 600;
      }
      .lv-card .close {
        position: absolute; top: 6px; right: 6px; background: rgba(255,255,255,.9);
        border: 0; width: 22px; height: 22px; border-radius: 999px; cursor: pointer;
        font-size: 14px; line-height: 1;
      }

      .lv-list { display:grid; gap:12px; grid-template-columns: 1fr; margin-top: 26px; }
      @media (min-width: 640px) { .lv-list { grid-template-columns: repeat(2, 1fr); } }
      @media (min-width: 980px) { .lv-list { grid-template-columns: repeat(4, 1fr); } }
      .lv-item {
        background:#fff; border-radius:10px; overflow:hidden;
        box-shadow:0 1px 3px rgba(0,0,0,.06); text-decoration:none; color:inherit;
        display:flex; flex-direction:column;
      }
      .lv-item .im { aspect-ratio: 4/3; background:#e5e7eb; }
      .lv-item .im img { width:100%; height:100%; object-fit:cover; }
      .lv-item .lp { padding: 10px 12px; }
      .lv-item .n {
        display:inline-flex; align-items:center; justify-content:center;
        background: var(--c-primary, #f59e0b); color:#111;
        width:22px; height:22px; border-radius:50%; font-weight:800; font-size:11px;
        margin-right: 6px;
      }
      .lv-item h4 { margin:0; font-size:13px; line-height:1.3; }
      .lv-item .lp .price { color: var(--c-primary, #f59e0b); font-weight:700; font-size:13px; margin-top:4px; }
    </style>

    <div class="lv-canvas" id="lv-canvas">
      <?php if (!empty($scene['image_path'])): ?>
        <img src="<?= e($scene['image_path']) ?>" alt="<?= e($scene['cover_alt'] ?: $scene['title']) ?>">
      <?php endif; ?>
      <?php foreach ($hotspots as $i => $h):
        $priceHtml = '';
        if ($h['is_promo'] && $h['promo_price'] !== null) {
            $priceHtml = 'RM ' . number_format((float) $h['promo_price'], 0);
            if ($h['price_min'] !== null && (float) $h['price_min'] > (float) $h['promo_price']) {
                $priceHtml .= ' <span class="promo">RM ' . number_format((float) $h['price_min'], 0) . '</span>';
            }
        } elseif ($h['price_min'] !== null) {
            $priceHtml = 'RM ' . number_format((float) $h['price_min'], 0);
            if ($h['price_max'] !== null && (float) $h['price_max'] != (float) $h['price_min']) {
                $priceHtml .= ' – RM ' . number_format((float) $h['price_max'], 0);
            }
        }
      ?>
        <button class="lv-pin" type="button"
                data-i="<?= $i ?>"
                style="left:<?= e((string) $h['x_pct']) ?>%;top:<?= e((string) $h['y_pct']) ?>%;"
                aria-label="View <?= e($h['product_name']) ?>"><?= ($i + 1) ?></button>
        <a class="lv-card" href="/product.php?id=<?= (int) $h['product_id'] ?>"
           data-card="<?= $i ?>"
           style="left:<?= e((string) $h['x_pct']) ?>%;top:<?= e((string) $h['y_pct']) ?>%;">
          <button type="button" class="close" aria-label="Close" data-close="<?= $i ?>">×</button>
          <?php if (!empty($h['product_img'])): ?>
            <img class="thumb" src="<?= e($h['product_img']) ?>" alt="">
          <?php endif; ?>
          <div class="pad">
            <h4><?= e($h['product_name']) ?></h4>
            <?php if ($priceHtml): ?>
              <div class="price"><?= $priceHtml /* html-safe: only e()/number_format() outputs */ ?></div>
            <?php endif; ?>
            <span class="cta">View product →</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($hotspots): ?>
      <h2 style="margin:32px 0 6px;font-size:18px;">Products in this scene</h2>
      <div class="lv-list">
        <?php foreach ($hotspots as $i => $h):
          $priceHtml = '';
          if ($h['is_promo'] && $h['promo_price'] !== null) {
              $priceHtml = 'RM ' . number_format((float) $h['promo_price'], 0);
          } elseif ($h['price_min'] !== null) {
              $priceHtml = 'RM ' . number_format((float) $h['price_min'], 0);
              if ($h['price_max'] !== null && (float) $h['price_max'] != (float) $h['price_min']) {
                  $priceHtml .= ' – RM ' . number_format((float) $h['price_max'], 0);
              }
          }
        ?>
          <a class="lv-item" href="/product.php?id=<?= (int) $h['product_id'] ?>">
            <div class="im">
              <?php if (!empty($h['product_img'])): ?>
                <img src="<?= e($h['product_img']) ?>" alt="" loading="lazy">
              <?php endif; ?>
            </div>
            <div class="lp">
              <h4><span class="n"><?= ($i + 1) ?></span><?= e($h['product_name']) ?></h4>
              <?php if ($priceHtml): ?>
                <div class="price"><?= $priceHtml ?></div>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
(function () {
  var pins  = document.querySelectorAll('.lv-pin');
  var cards = document.querySelectorAll('.lv-card');

  function closeAll() {
    pins.forEach(function (p) { p.classList.remove('active'); });
    cards.forEach(function (c) { c.classList.remove('open'); });
  }

  pins.forEach(function (pin) {
    pin.addEventListener('click', function (e) {
      e.stopPropagation();
      var i = pin.dataset.i;
      var target = document.querySelector('.lv-card[data-card="' + i + '"]');
      var wasOpen = target && target.classList.contains('open');
      closeAll();
      if (target && !wasOpen) {
        target.classList.add('open');
        pin.classList.add('active');
      }
    });
  });

  cards.forEach(function (card) {
    var closeBtn = card.querySelector('.close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        closeAll();
      });
    }
    // Prevent card clicks from bubbling up and closing themselves.
    card.addEventListener('click', function (e) { e.stopPropagation(); });
  });

  document.addEventListener('click', closeAll);
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
