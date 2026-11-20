<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'promo']);

// Only list products flagged on-promo whose window is currently active.
// A NULL start means "already started"; a NULL end means "no end yet".
$products = tenant_all(
    'SELECT p.*, (SELECT image_path FROM product_images
                  WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ?
        AND p.status     = "active"
        AND p.is_promo   = 1
        AND (p.promo_starts_at IS NULL OR p.promo_starts_at <= NOW())
        AND (p.promo_ends_at   IS NULL OR p.promo_ends_at   >= NOW())
      ORDER BY (p.promo_ends_at IS NULL), p.promo_ends_at ASC, p.created_at DESC
      LIMIT 120',
    $cid
);

$page_meta = [
    'title'       => 'Promo & Sale · ' . $company['name'],
    'description' => 'Active promotions and limited-time offers at ' . $company['name'] . '.',
];
layout_head($company, 'Promo & Sale', 'promo', $page_meta);
?>

<section class="hero" style="background: linear-gradient(135deg, #dc2626, #7f1d1d); color:#fff; padding: clamp(40px, 7vw, 64px) 0;">
  <div class="container">
    <h1 style="margin:0 0 8px;">🏷️ Promo &amp; Sale</h1>
    <p style="margin:0;opacity:.95;">Limited-time offers — while stocks last.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if (!$products): ?>
      <div class="box center" style="padding: 40px 16px;">
        <h2 style="margin:0 0 6px;font-size:18px;">No promos running right now</h2>
        <p class="muted" style="margin: 0 0 14px;">Check back soon, or browse the full catalog.</p>
        <a class="btn primary" href="/catalog.php">Browse Catalog</a>
      </div>
    <?php else: ?>
      <p class="muted" style="margin:0 0 14px;">
        <?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?> on promo
      </p>

      <style>
        .promo-card { background:#fff; border-radius:10px; overflow:hidden;
                      box-shadow:0 1px 3px rgba(0,0,0,.06); display:flex; flex-direction:column;
                      position: relative; }
        .promo-card .img { aspect-ratio: 4/3; background:#eee; }
        .promo-card .img img { width:100%; height:100%; object-fit:cover; }
        .promo-card .badge-sale {
          position: absolute; top: 10px; left: 10px; z-index: 2;
          background:#dc2626; color:#fff; font-weight:700; font-size:11px;
          padding: 4px 10px; border-radius: 999px; letter-spacing: .04em;
        }
        .promo-card .pad { padding: 12px; display:flex; flex-direction:column; gap:6px; flex:1; }
        .promo-card h3 { margin:0; font-size:15px; line-height:1.25; }
        .promo-card .cat { color:#666; font-size:12px; }
        .promo-card .price-row { display:flex; align-items:baseline; gap:8px; flex-wrap:wrap; }
        .promo-card .price-now  { color:#dc2626; font-weight:800; font-size:18px; }
        .promo-card .price-was  { color:#9ca3af; text-decoration: line-through; font-size:13px; }
        .promo-card .price-off  { background:#fee2e2; color:#991b1b; font-weight:700;
                                  font-size:11px; padding:2px 8px; border-radius: 4px; }
        .promo-card .countdown  { color:#7f1d1d; font-size:12px; font-weight:600; margin-top:auto; }
        .promo-card .enquire {
          background:#25d366; color:#fff; padding:10px;
          font-size:14px; margin-top: 4px;
        }
      </style>

      <div class="grid">
        <?php foreach ($products as $p):
          $promo  = $p['promo_price'] !== null ? (float) $p['promo_price'] : null;
          $orig   = $p['price_min'] !== null ? (float) $p['price_min']  : null;
          $orig_max = $p['price_max'] !== null ? (float) $p['price_max'] : null;
          $off_pct = ($promo !== null && $orig !== null && $orig > 0 && $promo < $orig)
                     ? (int) round((1 - $promo / $orig) * 100) : null;
          $ends = !empty($p['promo_ends_at']) ? strtotime($p['promo_ends_at']) : null;
          $wa   = $company['whatsapp_number']
                ? '/whatsapp-redirect.php?product_id=' . (int) $p['id']
                : null;
        ?>
          <div class="promo-card">
            <?php if ($off_pct !== null): ?>
              <span class="badge-sale">SAVE <?= $off_pct ?>%</span>
            <?php elseif ($promo !== null): ?>
              <span class="badge-sale">PROMO</span>
            <?php endif; ?>

            <a href="/product.php?id=<?= (int)$p['id'] ?>"
               style="text-decoration:none;color:inherit;display:block;">
              <div class="img">
                <?php if (!empty($p['img'])): ?>
                  <img src="<?= e($p['img']) ?>" alt="" loading="lazy">
                <?php endif; ?>
              </div>
              <div class="pad">
                <h3><?= e($p['name']) ?></h3>
                <?php if (!empty($p['category']) || !empty($p['subcategory'])): ?>
                  <div class="cat">
                    <?= e(trim(($p['category'] ?? '') . ' › ' . ($p['subcategory'] ?? ''), ' ›')) ?>
                  </div>
                <?php endif; ?>

                <div class="price-row">
                  <?php if ($promo !== null): ?>
                    <span class="price-now">RM <?= number_format($promo, 2) ?></span>
                    <?php if ($orig !== null && $orig > $promo): ?>
                      <span class="price-was">
                        RM <?= number_format($orig, 2) ?><?php
                          if ($orig_max !== null && $orig_max > $orig) {
                              echo ' - RM ' . number_format($orig_max, 2);
                          }
                        ?>
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="price-now"><?= e(format_price($p['price_min'], $p['price_max'])) ?></span>
                  <?php endif; ?>
                </div>

                <?php if ($ends): ?>
                  <div class="countdown" data-ends="<?= $ends ?>">
                    ⏳ Ends <?= date('j M, g:i a', $ends) ?>
                  </div>
                <?php endif; ?>
              </div>
            </a>

            <?php if ($wa): ?>
              <div style="padding:0 12px 12px;">
                <a class="btn block enquire" target="_blank" rel="noopener" href="<?= e($wa) ?>">
                  💬 Enquire
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <script>
        (function () {
          var nodes = document.querySelectorAll('.promo-card .countdown[data-ends]');
          if (!nodes.length) return;
          function pad(n) { return n < 10 ? '0' + n : '' + n; }
          function tick() {
            var now = Math.floor(Date.now() / 1000);
            nodes.forEach(function (el) {
              var ends = parseInt(el.dataset.ends, 10);
              var diff = ends - now;
              if (diff <= 0) { el.textContent = '⏳ Promo ended'; return; }
              var d = Math.floor(diff / 86400);
              var h = Math.floor((diff % 86400) / 3600);
              var m = Math.floor((diff % 3600) / 60);
              var s = diff % 60;
              var parts = d > 0 ? [d + 'd', pad(h) + 'h', pad(m) + 'm'] : [pad(h) + 'h', pad(m) + 'm', pad(s) + 's'];
              el.textContent = '⏳ Ends in ' + parts.join(' ');
            });
          }
          tick();
          setInterval(tick, 1000);
        })();
      </script>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
