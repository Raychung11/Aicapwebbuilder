<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$id      = (int) input('id', 0);

$product = tenant_one(
    'SELECT * FROM products WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
    (int)$company['id'], [$id]
);
if (!$product) {
    http_response_code(404);
    layout_head($company, 'Not Found');
    echo '<section><div class="container"><h1>Product not found</h1></div></section>';
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

$wa_msg  = "Hi, I'm interested in: " . $product['name'] . " (#" . $product['id'] . ").";
$wa_link = !empty($company['whatsapp_number'])
    ? '/whatsapp-redirect.php?product_id=' . (int)$product['id']
    : null;

layout_head($company, $product['name']);
?>
<section>
  <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div>
      <div class="card">
        <div class="img" style="aspect-ratio:1/1;background:#eee;display:flex;align-items:center;justify-content:center;">
          <?php if ($images): ?>
            <img src="<?= e($images[0]['image_path']) ?>" alt="<?= e($product['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php endif; ?>
        </div>
      </div>
      <?php if (count($images) > 1): ?>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
          <?php foreach ($images as $im): ?>
            <img src="<?= e($im['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;" alt="">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div>
      <h1 style="margin:0 0 6px"><?= e($product['name']) ?></h1>
      <?php if (!empty($product['category'])): ?><div class="muted"><?= e($product['category']) ?></div><?php endif; ?>
      <div style="font-size:22px;font-weight:700;color:var(--c-primary);margin:10px 0;">
        <?= e(format_price($product['price_min'], $product['price_max'])) ?>
      </div>
      <p><?= nl2br(e($product['description'] ?? '')) ?></p>

      <?php if ($variants): ?>
        <h3>Variants</h3>
        <table style="width:100%;border-collapse:collapse;">
          <tr><th align="left">Variant</th><th align="left">Color</th><th align="left">Material</th><th align="left">Size</th><th align="right">Price</th></tr>
          <?php foreach ($variants as $v): ?>
            <tr>
              <td><?= e($v['variant_name']) ?></td>
              <td><?= e($v['color']) ?></td>
              <td><?= e($v['material']) ?></td>
              <td><?= e($v['size']) ?></td>
              <td align="right"><?= $v['price'] !== null ? 'RM ' . number_format((float)$v['price'], 2) : '' ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

      <p style="margin-top:18px;">
        <?php if ($wa_link): ?>
          <a class="btn primary" target="_blank" href="<?= e($wa_link) ?>">Enquire on WhatsApp</a>
        <?php endif; ?>
        <a class="btn outline" href="/catalog.php">Back to catalog</a>
      </p>
    </div>
  </div>
</section>

<style>
@media (max-width: 720px) {
  section .container { grid-template-columns: 1fr !important; }
}
</style>

<?php layout_foot($company); ?>
