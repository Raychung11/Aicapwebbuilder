<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$counts = [
    'companies' => (int) (db_one('SELECT COUNT(*) c FROM companies')['c'] ?? 0),
    'products'  => (int) (db_one('SELECT COUNT(*) c FROM products')['c'] ?? 0),
    'leads'     => (int) (db_one('SELECT COUNT(*) c FROM leads')['c'] ?? 0),
    'members'   => (int) (db_one('SELECT COUNT(*) c FROM members')['c'] ?? 0),
    'scans'     => (int) (db_one('SELECT COUNT(*) c FROM campaign_scans')['c'] ?? 0),
];

admin_layout_open('Dashboard');
?>
<div class="kpi">
  <div class="box"><div class="l">Companies</div><div class="v"><?= $counts['companies'] ?></div></div>
  <div class="box"><div class="l">Products</div><div class="v"><?= $counts['products'] ?></div></div>
  <div class="box"><div class="l">Members</div><div class="v"><?= $counts['members'] ?></div></div>
  <div class="box"><div class="l">Leads</div><div class="v"><?= $counts['leads'] ?></div></div>
  <div class="box"><div class="l">QR Scans</div><div class="v"><?= $counts['scans'] ?></div></div>
</div>

<div class="card">
  <h3 style="margin:0 0 10px">Recent Companies</h3>
  <table>
    <tr><th>Name</th><th>Subdomain</th><th>Status</th><th>Created</th></tr>
    <?php foreach (db_all('SELECT * FROM companies ORDER BY created_at DESC LIMIT 10') as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></td>
        <td><span class="badge <?= $c['status']==='active'?'green':'red' ?>"><?= e($c['status']) ?></span></td>
        <td><?= e($c['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_layout_close(); ?>
