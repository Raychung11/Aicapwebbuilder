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

ca_open('Dashboard');
?>
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
