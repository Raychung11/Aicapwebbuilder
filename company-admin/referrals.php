<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

// Optional date range
$from = (string) input('from', date('Y-m-01'));        // default: this month start
$to   = (string) input('to',   date('Y-m-d'));         // default: today
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

$rows = db_all(
    'SELECT s.id, s.name, s.referral_code, s.commission_rate, s.status,
            (SELECT COUNT(*) FROM leads
              WHERE company_id = s.company_id AND salesperson_id = s.id
                AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)) AS leads,
            (SELECT COUNT(*) FROM voucher_claims
              WHERE company_id = s.company_id AND salesperson_id = s.id
                AND claimed_at >= ? AND claimed_at < DATE_ADD(?, INTERVAL 1 DAY)) AS claims,
            (SELECT COUNT(*) FROM voucher_claims
              WHERE company_id = s.company_id AND salesperson_id = s.id
                AND status = "redeemed"
                AND claimed_at >= ? AND claimed_at < DATE_ADD(?, INTERVAL 1 DAY)) AS redeemed
       FROM salespersons s
      WHERE s.company_id = ?
      ORDER BY claims DESC, leads DESC, s.name',
    [$from, $to, $from, $to, $from, $to, $CID]
);

// Recent attributed leads list (most recent 50)
$recent_leads = tenant_all(
    'SELECT l.id, l.created_at, l.source, l.customer_name, l.customer_phone, l.notes,
            s.name AS sp_name, s.referral_code
       FROM leads l
       JOIN salespersons s ON s.id = l.salesperson_id
      WHERE l.company_id = ?
        AND l.created_at >= ? AND l.created_at < DATE_ADD(?, INTERVAL 1 DAY)
      ORDER BY l.created_at DESC
      LIMIT 50',
    $CID, [$from, $to]
);

// Recent attributed voucher claims
$recent_claims = tenant_all(
    'SELECT vc.id, vc.claimed_at, vc.voucher_code, vc.status,
            v.title AS voucher_title, m.name AS member_name, m.phone AS member_phone,
            s.name AS sp_name, s.referral_code
       FROM voucher_claims vc
       JOIN vouchers v ON v.id = vc.voucher_id
       JOIN members  m ON m.id = vc.member_id
       JOIN salespersons s ON s.id = vc.salesperson_id
      WHERE vc.company_id = ?
        AND vc.claimed_at >= ? AND vc.claimed_at < DATE_ADD(?, INTERVAL 1 DAY)
      ORDER BY vc.claimed_at DESC
      LIMIT 50',
    $CID, [$from, $to]
);

$total_leads     = array_sum(array_column($rows, 'leads'));
$total_claims    = array_sum(array_column($rows, 'claims'));
$total_redeemed  = array_sum(array_column($rows, 'redeemed'));
$active_agents   = count(array_filter($rows, fn($r) => $r['status'] === 'active' && !empty($r['referral_code'])));

ca_open('Referrals & Agent Attribution');
?>

<div class="card">
  <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
    <div>
      <label style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">From</label>
      <input class="input" type="date" name="from" value="<?= e($from) ?>">
    </div>
    <div>
      <label style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">To</label>
      <input class="input" type="date" name="to" value="<?= e($to) ?>">
    </div>
    <div><button class="btn primary" type="submit">Apply</button></div>
    <div><a class="btn outline" href="?from=<?= e(date('Y-m-01')) ?>&to=<?= e(date('Y-m-d')) ?>">This month</a></div>
    <div><a class="btn outline" href="?from=<?= e(date('Y-m-01', strtotime('first day of last month'))) ?>&to=<?= e(date('Y-m-t', strtotime('first day of last month'))) ?>">Last month</a></div>
    <div><a class="btn outline" href="?from=<?= e(date('Y-01-01')) ?>&to=<?= e(date('Y-m-d')) ?>">This year</a></div>
  </form>
</div>

<div class="kpi">
  <div class="box"><div class="l">Active agents w/ code</div><div class="v"><?= $active_agents ?></div></div>
  <div class="box"><div class="l">Attributed leads</div><div class="v"><?= (int) $total_leads ?></div></div>
  <div class="box"><div class="l">Attributed claims</div><div class="v"><?= (int) $total_claims ?></div></div>
  <div class="box"><div class="l">Redeemed claims</div><div class="v"><?= (int) $total_redeemed ?></div></div>
</div>

<div class="card">
  <h3 style="margin:0 0 6px;">Per-agent attribution</h3>
  <p class="muted" style="margin:0 0 12px;font-size:13px;">
    Counts of leads + voucher claims attributed to each salesperson via their referral cookie
    in the selected date range.
  </p>
  <table>
    <tr>
      <th>Agent</th><th>Code</th><th>Leads</th><th>Claims</th>
      <th>Redeemed</th><th>Commission %</th><th>Status</th>
    </tr>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="muted center" style="padding:14px;">No salespersons defined yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td>
          <?php if (!empty($r['referral_code'])): ?>
            <code style="background:#fff7ed;padding:2px 8px;border-radius:6px;color:#b45309;">
              <?= e($r['referral_code']) ?>
            </code>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
        <td><?= (int) $r['leads'] ?></td>
        <td><?= (int) $r['claims'] ?></td>
        <td><?= (int) $r['redeemed'] ?></td>
        <td><?= $r['commission_rate'] !== null ? e($r['commission_rate']) . '%' : '<span class="muted">—</span>' ?></td>
        <td><span class="badge <?= $r['status']==='active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="row" style="display:grid;grid-template-columns:1fr;gap:18px;">
  <div class="card">
    <h3 style="margin:0 0 10px;">Recent attributed voucher claims</h3>
    <?php if (!$recent_claims): ?>
      <p class="muted">No attributed voucher claims in this range.</p>
    <?php else: ?>
      <table>
        <tr><th>When</th><th>Customer</th><th>Voucher</th><th>Code</th><th>Status</th><th>Agent</th></tr>
        <?php foreach ($recent_claims as $c): ?>
          <tr>
            <td><?= e($c['claimed_at']) ?></td>
            <td><?= e($c['member_name']) ?><br><span class="muted" style="font-size:12px;"><?= e($c['member_phone']) ?></span></td>
            <td><?= e($c['voucher_title']) ?></td>
            <td><code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= e($c['voucher_code']) ?></code></td>
            <td><span class="badge <?= $c['status']==='redeemed'?'green':'' ?>"><?= e($c['status']) ?></span></td>
            <td>
              <?= e($c['sp_name']) ?>
              <?php if (!empty($c['referral_code'])): ?>
                <span class="muted" style="font-size:12px;">(<?= e($c['referral_code']) ?>)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 style="margin:0 0 10px;">Recent attributed leads</h3>
    <?php if (!$recent_leads): ?>
      <p class="muted">No attributed leads in this range.</p>
    <?php else: ?>
      <table>
        <tr><th>When</th><th>Source</th><th>Customer</th><th>Phone</th><th>Agent</th></tr>
        <?php foreach ($recent_leads as $l): ?>
          <tr>
            <td><?= e($l['created_at']) ?></td>
            <td><?= e($l['source']) ?></td>
            <td><?= e($l['customer_name'] ?? '') ?></td>
            <td><?= e($l['customer_phone'] ?? '') ?></td>
            <td>
              <?= e($l['sp_name']) ?>
              <?php if (!empty($l['referral_code'])): ?>
                <span class="muted" style="font-size:12px;">(<?= e($l['referral_code']) ?>)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="background:#f9fafb;border:1px dashed #e5e7eb;">
  <h4 style="margin:0 0 8px;font-size:14px;">How attribution works</h4>
  <ul class="muted" style="margin:0;padding-left:20px;font-size:13px;line-height:1.7;">
    <li>Give each agent a unique referral code on the <strong>Salespersons</strong> page.</li>
    <li>Share their personal URL: <code>https://<?= e($public_host = $company['custom_domain'] ?: $company['subdomain'] . '.' . APP_BASE_DOMAIN) ?>/?ref=THEIRCODE</code></li>
    <li>When a customer opens that URL, a 30-day cookie tags them as referred by that agent.</li>
    <li>Subsequent voucher claims, WhatsApp clicks and QR scans land in the agent's column above.</li>
    <li>Commission % is for your reference only — the platform doesn't pay it out, you do.</li>
  </ul>
</div>

<?php ca_close(); ?>
