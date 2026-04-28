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

// Logged-in users can claim directly from the list with one click.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$member) {
        // Not logged in → send to the quick-register flow with the voucher id.
        $vid = (int) input('voucher_id', 0);
        redirect('/voucher-claim.php?id=' . $vid);
    }
    $voucher_id = (int) input('voucher_id', 0);
    try {
        $code = claim_voucher($cid, $voucher_id, (int) $member['id']);
        track_event($cid, 'voucher_claim', [
            'entity_type' => 'voucher',
            'entity_id'   => $voucher_id,
            'member_id'   => (int) $member['id'],
        ]);
        $v = tenant_one('SELECT title FROM vouchers WHERE company_id = ? AND id = ?',
                        $cid, [$voucher_id]);
        create_lead($cid, [
            'member_id'      => (int) $member['id'],
            'customer_name'  => $member['name']  ?? null,
            'customer_phone' => $member['phone'] ?? null,
            'source'         => 'voucher_claim',
            'notes'          => 'Voucher claim: ' . ($v['title'] ?? ''),
        ]);
        flash_set('success', 'Voucher claimed! Your code: ' . $code);
    } catch (RuntimeException $ex) {
        flash_set('error', $ex->getMessage());
    }
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
    <h1 style="margin:0 0 4px;">Vouchers</h1>
    <p class="muted" style="margin:0 0 16px">
      <?= $member ? 'Tap a voucher to claim it instantly.' : 'Claim in seconds — quick register required.' ?>
    </p>

    <?php if ($m = flash_pop('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash_pop('error')):   ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>

    <?php if (!$vouchers): ?>
      <p class="muted">No active vouchers right now.</p>
    <?php else: ?>
      <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
        <?php foreach ($vouchers as $v): ?>
          <div class="box" style="display:flex;flex-direction:column;gap:8px;">
            <h3 style="margin:0;font-size:17px"><?= e($v['title']) ?></h3>
            <?php if ($v['type'] === 'percent'): ?>
              <div style="font-size:28px;font-weight:800;color:var(--c-primary);"><?= e((string)$v['value']) ?>% OFF</div>
            <?php elseif ($v['type'] === 'fixed'): ?>
              <div style="font-size:28px;font-weight:800;color:var(--c-primary);">RM <?= e(number_format((float)$v['value'], 2)) ?> OFF</div>
            <?php else: ?>
              <div style="font-size:18px;font-weight:600;color:var(--c-primary)"><?= e(strtoupper($v['type'])) ?></div>
            <?php endif; ?>
            <p class="muted" style="margin:0"><?= e($v['description'] ?? '') ?></p>
            <?php if ($v['expiry_date']): ?>
              <div class="muted">⏳ Expires: <?= e($v['expiry_date']) ?></div>
            <?php endif; ?>
            <div style="margin-top:auto">
              <?php if ($member): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>">
                  <button class="btn primary block" type="submit">Claim Now</button>
                </form>
              <?php else: ?>
                <a class="btn primary block" href="/voucher-claim.php?id=<?= (int)$v['id'] ?>">Get Voucher</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_foot($company); ?>
