<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$member  = require_member();

$claims = db_all(
    'SELECT vc.*, v.title, v.type, v.value, v.expiry_date, v.redemption_method
       FROM voucher_claims vc
       JOIN vouchers v ON v.id = vc.voucher_id
      WHERE vc.member_id = ? AND vc.company_id = ?
      ORDER BY vc.claimed_at DESC',
    [(int)$member['id'], (int)$company['id']]
);

layout_head($company, 'My Account');
?>
<section>
  <div class="container">
    <h1>Welcome, <?= e($member['name']) ?></h1>
    <p class="muted">Phone: <?= e($member['phone']) ?> &middot; <a href="/member-logout.php">Logout</a></p>

    <h2>My Vouchers</h2>
    <?php if (!$claims): ?>
      <p class="muted">No vouchers yet. <a href="/voucher.php">Browse vouchers</a></p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($claims as $c): ?>
          <div class="box">
            <div style="display:flex;justify-content:space-between;align-items:start">
              <strong><?= e($c['title']) ?></strong>
              <span class="badge <?= $c['status'] === 'redeemed' ? 'green' : '' ?>"><?= e($c['status']) ?></span>
            </div>
            <div class="muted" style="margin-top:6px">
              Code: <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= e($c['voucher_code']) ?></code>
            </div>
            <?php if ($c['expiry_date']): ?>
              <div class="muted">Expires: <?= e($c['expiry_date']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_foot($company); ?>
