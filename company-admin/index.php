<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/analytics.php';

$kpi = [
    'products'  => (int) tenant_one('SELECT COUNT(*) c FROM products WHERE company_id = ?',  $CID)['c'],
    'leads'     => (int) tenant_one('SELECT COUNT(*) c FROM leads WHERE company_id = ?',     $CID)['c'],
    'vouchers'  => (int) tenant_one('SELECT COUNT(*) c FROM vouchers WHERE company_id = ?',  $CID)['c'],
    'campaigns' => (int) tenant_one('SELECT COUNT(*) c FROM campaigns WHERE company_id = ?', $CID)['c'],
];
$views = event_count($CID, 'page_view');
$wa    = event_count($CID, 'whatsapp_click');
$claim = event_count($CID, 'voucher_claim');
$scans = event_count($CID, 'campaign_scan');

// Onboarding checks for the helper banner
$has_logo     = !empty($company['logo']);
$has_branch   = (int) tenant_one('SELECT COUNT(*) c FROM branches WHERE company_id = ?', $CID)['c'] > 0;
$has_product  = $kpi['products'] > 0;
$has_voucher  = $kpi['vouchers'] > 0;
$onboard_done = $has_logo && $has_branch && $has_product && $has_voucher;

ca_open('Dashboard');
?>

<?php if (!$onboard_done): ?>
<div class="card" style="border-left:4px solid #2563eb;">
  <h3 style="margin:0 0 10px;">Finish setting up <?= e($company['name']) ?></h3>
  <p class="muted" style="margin:0 0 14px;">A few quick steps to make your tenant site shine.</p>
  <div style="display:grid;gap:10px;grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <a href="/company-admin/settings.php"
       style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:<?= $has_logo?'#f0fdf4':'#fff' ?>;">
      <div style="font-size:24px;"><?= $has_logo ? '✅' : '🖼️' ?></div>
      <strong>Upload your logo</strong>
      <div class="muted">Appears in header, footer, and admin.</div>
    </a>
    <a href="/company-admin/branches.php"
       style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:<?= $has_branch?'#f0fdf4':'#fff' ?>;">
      <div style="font-size:24px;"><?= $has_branch ? '✅' : '📍' ?></div>
      <strong>Add a branch</strong>
      <div class="muted">With map + Waze for visitors.</div>
    </a>
    <a href="/company-admin/products.php"
       style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:<?= $has_product?'#f0fdf4':'#fff' ?>;">
      <div style="font-size:24px;"><?= $has_product ? '✅' : '🛋️' ?></div>
      <strong>Publish products</strong>
      <div class="muted">Your e-catalog for customers.</div>
    </a>
    <a href="/company-admin/vouchers.php"
       style="text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:<?= $has_voucher?'#f0fdf4':'#fff' ?>;">
      <div style="font-size:24px;"><?= $has_voucher ? '✅' : '🎁' ?></div>
      <strong>Create a voucher</strong>
      <div class="muted">Convert visitors to leads.</div>
    </a>
  </div>
</div>
<?php endif; ?>

<div class="kpi">
  <div class="box"><div class="l">Page Views</div><div class="v"><?= $views ?></div></div>
  <div class="box"><div class="l">WhatsApp Clicks</div><div class="v"><?= $wa ?></div></div>
  <div class="box"><div class="l">Voucher Claims</div><div class="v"><?= $claim ?></div></div>
  <div class="box"><div class="l">QR Scans</div><div class="v"><?= $scans ?></div></div>
  <div class="box"><div class="l">Products</div><div class="v"><?= $kpi['products'] ?></div></div>
  <div class="box"><div class="l">Leads</div><div class="v"><?= $kpi['leads'] ?></div></div>
  <div class="box"><div class="l">Vouchers</div><div class="v"><?= $kpi['vouchers'] ?></div></div>
  <div class="box"><div class="l">Campaigns</div><div class="v"><?= $kpi['campaigns'] ?></div></div>
</div>

<div class="card">
  <h3 style="margin:0 0 10px">Recent Leads</h3>
  <table>
    <tr><th>Created</th><th>Source</th><th>Customer</th><th>Phone</th><th>Status</th></tr>
    <?php foreach (tenant_all(
        'SELECT * FROM leads WHERE company_id = ? ORDER BY created_at DESC LIMIT 10', $CID
    ) as $l): ?>
      <tr>
        <td><?= e($l['created_at']) ?></td>
        <td><?= e($l['source']) ?></td>
        <td><?= e($l['customer_name'] ?? '') ?></td>
        <td><?= e($l['customer_phone'] ?? '') ?></td>
        <td><span class="badge"><?= e($l['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
