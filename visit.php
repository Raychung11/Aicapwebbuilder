<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
track_event($cid, 'page_view', ['entity_type' => 'visit']);

$branches = tenant_all(
    'SELECT * FROM branches WHERE company_id = ? AND status = "active" ORDER BY id',
    $cid
);

// Pre-load images for all branches in one query (tenant-scoped).
$images_by_branch = [];
if ($branches) {
    $ids = array_column($branches, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(
        "SELECT * FROM branch_images
          WHERE company_id = ? AND branch_id IN ($ph)
          ORDER BY is_primary DESC, sort_order ASC",
        array_merge([$cid], array_map('intval', $ids))
    );
    foreach ($rows as $im) {
        $images_by_branch[(int) $im['branch_id']][] = $im;
    }
}

$page_meta = [
    'title'       => 'Visit Us | ' . $company['name'],
    'description' => 'Find ' . $company['name'] . ' showrooms — addresses, opening hours, '
                   . 'directions on Google Maps and Waze, and direct phone or WhatsApp contact.',
    'image'       => $company['og_image'] ?: ($company['logo'] ?? ''),
    'type'        => 'website',
];

layout_head($company, 'Visit Us', 'visit', $page_meta);
?>
<style>
.visit-hero { background: linear-gradient(135deg, var(--c-primary), #000); color:#fff; padding: clamp(28px,6vw,52px) 0; }
.visit-hero h1 { font-size: clamp(26px,5vw,40px); margin: 0 0 8px; }
.visit-hero p  { margin: 0; opacity:.9; max-width: 640px; font-size: clamp(15px,2vw,17px); }

.visit-branch {
  background:#fff; border-radius: 12px; overflow:hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 22px;
  display: grid; grid-template-columns: 1fr; gap: 0;
}
@media (min-width: 800px) { .visit-branch { grid-template-columns: 1.1fr 1fr; } }
.visit-branch .map-wrap { aspect-ratio: 16/10; background:#eee; }
.visit-branch .map-wrap iframe { width:100%; height:100%; border:0; display:block; }
.visit-branch .meta-wrap { padding: 22px; display:flex; flex-direction:column; gap: 10px; }
.visit-branch h2 { margin:0 0 4px; font-size: clamp(20px,3vw,24px); }
.visit-branch .row-info { display:flex; gap:10px; align-items:flex-start; font-size: 15px; }
.visit-branch .row-info .icon { font-size:18px; line-height:1; flex-shrink:0; width:24px; text-align:center; }
.visit-branch .actions { display:flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }

.gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; padding: 0 22px 22px; }
.gallery a { display:block; aspect-ratio: 1/1; overflow:hidden; border-radius: 8px; background:#eee; }
.gallery img { width:100%; height:100%; object-fit:cover; display:block; transition: transform .3s; }
.gallery a:hover img { transform: scale(1.05); }
</style>

<section class="visit-hero">
  <div class="container">
    <h1>Visit Us</h1>
    <p>Drop by any of our showrooms below to see our furniture in person. You can also call,
       message us on WhatsApp, or get directions with one tap.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if (!$branches): ?>
      <div class="box center" style="padding: 32px;">
        <p class="muted">No showrooms have been published yet.</p>
        <?php if (!empty($company['whatsapp_number'])): ?>
          <a class="btn primary" target="_blank" rel="noopener"
             href="<?= e(whatsapp_link($company['whatsapp_number'], 'Hi, I\'d like to know more.')) ?>">
             💬 Chat on WhatsApp
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p class="muted" style="margin: 0 0 16px;"><?= count($branches) ?> location<?= count($branches) === 1 ? '' : 's' ?></p>

      <?php
        // Group branches by region. Anything without a region goes into "Other".
        // Preferred display order for common Malaysian regions:
        $region_order = ['Central', 'North', 'South', 'East Coast', 'East Malaysia'];
        $by_region = [];
        foreach ($branches as $b) {
            $r = trim((string) ($b['region'] ?? '')) ?: 'Other';
            $by_region[$r][] = $b;
        }
        // Sort: known regions in preferred order, then alphabetical for the rest,
        // and Other last.
        uksort($by_region, function ($a, $b) use ($region_order) {
            $ai = array_search($a, $region_order, true);
            $bi = array_search($b, $region_order, true);
            if ($a === 'Other') return 1;
            if ($b === 'Other') return -1;
            if ($ai !== false && $bi !== false) return $ai <=> $bi;
            if ($ai !== false) return -1;
            if ($bi !== false) return 1;
            return strcmp($a, $b);
        });
        function region_slug(string $r): string {
            return 'region-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($r));
        }
      ?>

      <!-- Region filter chips (the "by region" zone) -->
      <?php if (count($by_region) > 1): ?>
        <div class="region-chips" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px;">
          <button type="button" class="rgn-chip active" data-region="all">
            All <span class="cnt"><?= count($branches) ?></span>
          </button>
          <?php foreach ($by_region as $r => $list): ?>
            <button type="button" class="rgn-chip" data-region="<?= e(region_slug($r)) ?>">
              <?= e($r) ?> <span class="cnt"><?= count($list) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
        <style>
          .rgn-chip {
            display:inline-flex; align-items:center; gap:6px;
            padding: 8px 14px; border-radius: 999px;
            border: 1px solid #e5e7eb; background:#fff; color:#374151;
            font-weight: 600; font-size: 14px; cursor: pointer;
            font-family: inherit; transition: background .15s, border-color .15s, color .15s;
          }
          .rgn-chip:hover { border-color: var(--c-primary); color: var(--c-primary); }
          .rgn-chip.active { background: var(--c-primary); color:#fff; border-color: var(--c-primary); }
          .rgn-chip .cnt {
            display:inline-block; background: rgba(0,0,0,.1); color: inherit;
            padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;
          }
          .rgn-chip.active .cnt { background: rgba(255,255,255,.25); }
          .region-section h3 {
            margin: 18px 0 12px; padding-bottom: 6px;
            font-size: 18px; color: var(--c-primary);
            border-bottom: 2px solid #e5e7eb;
          }
        </style>
      <?php endif; ?>

      <?php foreach ($by_region as $region => $list): ?>
        <div class="region-section" data-region="<?= e(region_slug($region)) ?>">
          <?php if (count($by_region) > 1): ?>
            <h3 id="<?= e(region_slug($region)) ?>">📍 <?= e($region) ?>
              <span class="muted" style="font-weight:400;font-size:14px;">
                · <?= count($list) ?> location<?= count($list) === 1 ? '' : 's' ?>
              </span>
            </h3>
          <?php endif; ?>

      <?php foreach ($list as $b):
        $maps_url = $b['google_map_link'] ?: ($b['address']
          ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($b['address'])
          : null);
        $waze_url = $b['waze_link'] ?: ($b['address']
          ? 'https://waze.com/ul?q=' . rawurlencode($b['address'])
          : null);
        $branch_wa = $b['whatsapp_number'] ?: ($company['whatsapp_number'] ?? '');
        $imgs = $images_by_branch[(int) $b['id']] ?? [];
      ?>
        <article class="visit-branch" itemscope itemtype="https://schema.org/LocalBusiness">
          <div class="map-wrap">
            <?php if (!empty($b['google_map_embed'])): ?>
              <?= $b['google_map_embed'] /* admin-trusted iframe */ ?>
            <?php elseif (!empty($b['address'])): ?>
              <iframe loading="lazy"
                src="https://www.google.com/maps?q=<?= e(rawurlencode($b['address'])) ?>&output=embed"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen></iframe>
            <?php else: ?>
              <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;">No map provided</div>
            <?php endif; ?>
          </div>
          <div class="meta-wrap">
            <h2 itemprop="name"><?= e($b['name']) ?></h2>
            <meta itemprop="parentOrganization" content="<?= e($company['name']) ?>">

            <?php if (!empty($b['address'])): ?>
              <div class="row-info" itemprop="address">
                <span class="icon">📍</span>
                <span><?= e($b['address']) ?></span>
              </div>
            <?php endif; ?>

            <?php if (!empty($b['operating_hours'])): ?>
              <div class="row-info">
                <span class="icon">⏰</span>
                <span itemprop="openingHours"><?= e($b['operating_hours']) ?></span>
              </div>
            <?php endif; ?>

            <?php if (!empty($b['phone'])): ?>
              <div class="row-info">
                <span class="icon">📞</span>
                <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $b['phone'])) ?>"
                   itemprop="telephone"><?= e($b['phone']) ?></a>
              </div>
            <?php endif; ?>

            <?php if (!empty($b['email'])): ?>
              <div class="row-info">
                <span class="icon">✉️</span>
                <a href="mailto:<?= e($b['email']) ?>" itemprop="email"><?= e($b['email']) ?></a>
              </div>
            <?php endif; ?>

            <div class="actions">
              <?php if ($maps_url): ?>
                <a class="btn outline" target="_blank" rel="noopener" href="<?= e($maps_url) ?>">
                  📍 Google Maps
                </a>
              <?php endif; ?>
              <?php if ($waze_url): ?>
                <a class="btn" target="_blank" rel="noopener" href="<?= e($waze_url) ?>"
                   style="background:#33ccff;color:#fff;">
                  🚗 Waze
                </a>
              <?php endif; ?>
              <?php if ($branch_wa): ?>
                <a class="btn primary" target="_blank" rel="noopener"
                   href="<?= e(whatsapp_link($branch_wa, 'Hi, I\'d like to visit ' . $b['name'] . '.')) ?>"
                   style="background:#25d366;color:#fff;">
                  💬 WhatsApp
                </a>
              <?php endif; ?>
              <?php if (!empty($b['phone'])): ?>
                <a class="btn dark" href="tel:<?= e(preg_replace('/[^\d+]/', '', $b['phone'])) ?>">
                  📞 Call
                </a>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($imgs): ?>
            <div class="gallery" style="grid-column: 1 / -1;">
              <?php foreach ($imgs as $im): ?>
                <a href="<?= e($im['image_path']) ?>" target="_blank" rel="noopener">
                  <img src="<?= e($im['image_path']) ?>" alt="<?= e($b['name']) ?>" loading="lazy">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
        </div><!-- /.region-section -->
      <?php endforeach; ?>

      <script>
        (function () {
          var chips = document.querySelectorAll('.rgn-chip');
          var sections = document.querySelectorAll('.region-section');
          chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
              var r = chip.dataset.region;
              chips.forEach(function (c) { c.classList.toggle('active', c === chip); });
              sections.forEach(function (s) {
                s.style.display = (r === 'all' || s.dataset.region === r) ? '' : 'none';
              });
            });
          });
        })();
      </script>
    <?php endif; ?>

    <?php if (!empty($company['whatsapp_number'])): ?>
      <div class="box center" style="margin-top: 24px;">
        <h3 style="margin:0 0 6px;">Can't make it in person?</h3>
        <p class="muted" style="margin:0 0 12px;">Browse our catalog and chat with us anytime.</p>
        <div class="btn-row" style="justify-content:center;">
          <a class="btn primary" href="/catalog.php">Browse Catalog</a>
          <a class="btn" target="_blank" rel="noopener"
             href="<?= e(whatsapp_link($company['whatsapp_number'], 'Hi, I\'d like to know more.')) ?>"
             style="background:#25d366;color:#fff;">💬 WhatsApp Us</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php layout_foot($company); ?>
