<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();

// Support both ?id=  and  ?slug= (and rewritten /products/{slug}/)
$id   = (int) input('id', 0);
$slug = (string) input('slug', '');
if ($id) {
    $product = tenant_one(
        'SELECT * FROM products WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
        (int)$company['id'], [$id]
    );
} elseif ($slug !== '') {
    $product = tenant_one(
        'SELECT * FROM products WHERE company_id = ? AND slug = ? AND status = "active" LIMIT 1',
        (int)$company['id'], [$slug]
    );
} else {
    $product = null;
}

if (!$product) {
    http_response_code(404);
    layout_head($company, 'Not Found');
    echo '<section><div class="container"><h1>Product not found</h1><p><a class="btn outline" href="/catalog.php">Back to catalog</a></p></div></section>';
    layout_foot($company);
    exit;
}

track_event((int)$company['id'], 'product_view', [
    'entity_type' => 'product', 'entity_id' => (int)$product['id'],
]);

$images = tenant_all(
    'SELECT * FROM product_images WHERE company_id = ? AND product_id = ?
      ORDER BY is_primary DESC, sort_order ASC',
    (int)$company['id'], [(int)$product['id']]
);
$variants = tenant_all(
    'SELECT * FROM product_variants WHERE company_id = ? AND product_id = ? ORDER BY id',
    (int)$company['id'], [(int)$product['id']]
);

$wa_link = !empty($company['whatsapp_number'])
    ? '/whatsapp-redirect.php?product_id=' . (int)$product['id']
    : null;

$page_meta = [
    'title'       => $product['meta_title'] ?: ($product['name'] . ' | ' . $company['name']),
    'description' => $product['meta_description']
        ?: mb_substr(strip_tags((string) ($product['description'] ?? '')), 0, 160),
    'image'       => $images[0]['image_path'] ?? ($company['og_image'] ?? ($company['logo'] ?? '')),
    'type'        => 'product',
];

layout_head($company, $product['name'], 'product', $page_meta);
?>
<section>
  <div class="container split">
    <div>
      <div class="card">
        <div class="img" style="aspect-ratio:1/1;background:#eee;display:flex;align-items:center;justify-content:center;">
          <?php if ($images): ?>
            <img id="hero-img" src="<?= e($images[0]['image_path']) ?>"
                 alt="<?= e($product['name']) ?>"
                 style="width:100%;height:100%;object-fit:cover;">
          <?php endif; ?>
        </div>
      </div>
      <?php if (count($images) > 1): ?>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
          <?php foreach ($images as $im): ?>
            <img src="<?= e($im['image_path']) ?>"
                 onclick="document.getElementById('hero-img').src=this.src"
                 style="width:72px;height:72px;object-fit:cover;border-radius:6px;cursor:pointer;" alt="">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div>
      <h1 style="margin:0 0 6px;font-size:clamp(22px,4.5vw,30px)"><?= e($product['name']) ?></h1>
      <?php if (!empty($product['category'])): ?><div class="muted"><?= e($product['category']) ?></div><?php endif; ?>
      <div style="font-size:22px;font-weight:700;color:var(--c-primary);margin:10px 0;">
        <?= e(format_price($product['price_min'], $product['price_max'])) ?>
      </div>
      <p style="white-space:pre-wrap"><?= e($product['description'] ?? '') ?></p>

      <?php if ($variants): ?>
        <h3 style="margin-top:18px">Variants</h3>
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:14px;min-width:420px;">
            <tr style="background:#f3f4f6;text-align:left">
              <th style="padding:8px">Variant</th><th style="padding:8px">Color</th>
              <th style="padding:8px">Material</th><th style="padding:8px">Size</th>
              <th style="padding:8px;text-align:right">Price</th>
            </tr>
            <?php foreach ($variants as $v): ?>
              <tr style="border-top:1px solid #eee">
                <td style="padding:8px"><?= e($v['variant_name']) ?></td>
                <td style="padding:8px"><?= e($v['color']) ?></td>
                <td style="padding:8px"><?= e($v['material']) ?></td>
                <td style="padding:8px"><?= e($v['size']) ?></td>
                <td style="padding:8px;text-align:right"><?= $v['price'] !== null ? 'RM ' . number_format((float)$v['price'], 2) : '' ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>

      <div class="btn-row" style="margin-top:20px">
        <?php if ($wa_link): ?>
          <a class="btn primary block" target="_blank" rel="noopener" href="<?= e($wa_link) ?>" style="background:#25d366;color:#fff">
            Enquire on WhatsApp
          </a>
        <?php endif; ?>
        <a class="btn outline" href="/catalog.php">Back to catalog</a>
      </div>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
