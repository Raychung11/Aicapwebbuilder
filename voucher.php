<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/lead.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$cid     = (int) $company['id'];
$member  = current_member();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$member) {
        redirect('/member-login.php');
    }
    $voucher_id = (int) input('voucher_id', 0);
    $v = tenant_one(
        'SELECT * FROM vouchers WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
        $cid, [$voucher_id]
    );
    if (!$v) {
        flash_set('error', 'Voucher not available.');
        redirect('/voucher.php');
    }
    if ($v['expiry_date'] && $v['expiry_date'] < date('Y-m-d')) {
        flash_set('error', 'Voucher has expired.');
        redirect('/voucher.php');
    }
    if (!empty($v['per_member_limit'])) {
        $owned = db_one(
            'SELECT COUNT(*) AS c FROM voucher_claims
              WHERE voucher_id = ? AND member_id = ?',
            [$voucher_id, (int)$member['id']]
        );
        if ((int)$owned['c'] >= (int)$v['per_member_limit']) {
            flash_set('error', 'You have already claimed this voucher.');
            redirect('/voucher.php');
        }
    }
    if (!empty($v['usage_limit'])) {
        $total = db_one(
            'SELECT COUNT(*) AS c FROM voucher_claims WHERE voucher_id = ?',
            [$voucher_id]
        );
        if ((int)$total['c'] >= (int)$v['usage_limit']) {
            flash_set('error', 'Voucher fully claimed.');
            redirect('/voucher.php');
        }
    }
    // Generate a unique code
    do {
        $code = 'V' . rand_code(9);
        $exists = db_one('SELECT id FROM voucher_claims WHERE voucher_code = ?', [$code]);
    } while ($exists);

    db_insert(
        'INSERT INTO voucher_claims (company_id, voucher_id, member_id, status, voucher_code)
         VALUES (?, ?, ?, "claimed", ?)',
        [$cid, $voucher_id, (int)$member['id'], $code]
    );

    track_event($cid, 'voucher_claim', [
        'entity_type' => 'voucher', 'entity_id' => $voucher_id,
        'member_id'   => (int) $member['id'],
    ]);
    create_lead($cid, [
        'member_id'      => (int) $member['id'],
        'customer_name'  => $member['name']  ?? null,
        'customer_phone' => $member['phone'] ?? null,
        'source'         => 'voucher_claim',
        'notes'          => 'Voucher claim: ' . $v['title'],
    ]);
    flash_set('success', 'Voucher claimed! Code: ' . $code);
    redirect('/voucher.php');
}

track_event($cid, 'page_view', ['entity_type' => 'voucher_list']);

$vouchers = tenant_all(
    'SELECT * FROM vouchers
      WHERE company_id = ? AND status = "active"
        AND (expiry_date IS NULL OR expiry_date >= CURDATE())
      ORDER BY created_at DESC',
    $cid
);

layout_head($company, 'Vouchers');
?>
<section>
  <div class="container">
    <h1>Vouchers</h1>
    <?php if ($m = flash_pop('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash_pop('error')):   ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>

    <?php if (!$vouchers): ?>
      <p class="muted">No active vouchers right now.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($vouchers as $v): ?>
          <div class="box">
            <h3 style="margin:0"><?= e($v['title']) ?></h3>
            <?php if ($v['type'] === 'percent'): ?>
              <div style="font-size:24px;font-weight:700;color:var(--c-primary)"><?= e((string)$v['value']) ?>% OFF</div>
            <?php elseif ($v['type'] === 'fixed'): ?>
              <div style="font-size:24px;font-weight:700;color:var(--c-primary)">RM <?= e(number_format((float)$v['value'], 2)) ?> OFF</div>
            <?php else: ?>
              <div style="font-size:18px;font-weight:600;color:var(--c-primary)"><?= e(strtoupper($v['type'])) ?></div>
            <?php endif; ?>
            <p class="muted"><?= e($v['description'] ?? '') ?></p>
            <?php if ($v['expiry_date']): ?>
              <div class="muted">Expires: <?= e($v['expiry_date']) ?></div>
            <?php endif; ?>
            <form method="post" style="margin-top:10px">
              <?= csrf_field() ?>
              <input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>">
              <button class="btn primary" type="submit">
                <?= $member ? 'Claim Voucher' : 'Login to Claim' ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_foot($company); ?>
