<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$rows = db_all(
    'SELECT c.id, c.name, c.subdomain,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id AND event_type = "page_view")     AS page_views,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id AND event_type = "product_view") AS product_views,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id AND event_type = "whatsapp_click") AS wa_clicks,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id AND event_type = "voucher_claim")  AS voucher_claims,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id AND event_type = "campaign_scan")  AS scans,
            (SELECT COUNT(*) FROM leads WHERE company_id = c.id) AS leads
       FROM companies c
       ORDER BY c.name'
);

admin_layout_open('Analytics');
?>
<div class="card">
  <table>
    <tr>
      <th>Company</th><th>Page Views</th><th>Product Views</th>
      <th>WhatsApp Clicks</th><th>Voucher Claims</th><th>QR Scans</th><th>Leads</th>
    </tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?> <span class="muted"><?= e($r['subdomain']) ?></span></td>
        <td><?= (int)$r['page_views'] ?></td>
        <td><?= (int)$r['product_views'] ?></td>
        <td><?= (int)$r['wa_clicks'] ?></td>
        <td><?= (int)$r['voucher_claims'] ?></td>
        <td><?= (int)$r['scans'] ?></td>
        <td><?= (int)$r['leads'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_layout_close(); ?>
