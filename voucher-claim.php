<?php
/**
 * One-step voucher claim flow.
 *
 *   GET  /voucher-claim.php?id=123
 *     - Logged in   → form auto-claims on POST
 *     - Logged out  → form: name + phone + password, registers + claims
 *     - "I already have an account" toggle reveals login mode
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/lead.php';
require_once __DIR__ . '/inc/layout.php';

$company    = require_company();
$cid        = (int) $company['id'];
$voucher_id = (int) input('id', 0);

$voucher = tenant_one(
    'SELECT * FROM vouchers WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
    $cid, [$voucher_id]
);
if (!$voucher) {
    layout_head($company, 'Voucher not found');
    echo '<section><div class="container"><h1>Voucher not available</h1><p><a class="btn outline" href="/voucher.php">Browse vouchers</a></p></div></section>';
    layout_foot($company);
    exit;
}

$err  = '';
$code = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mode = (string) input('mode', 'register');

    try {
        $member = current_member();

        if (!$member) {
            $phone = normalize_phone(trim((string) input('phone')));
            $pass  = (string) input('password');

            if ($phone === '' || $pass === '') {
                throw new RuntimeException('Please enter your phone and password.');
            }
            if (strlen($phone) < 9 || strlen($phone) > 15) {
                throw new RuntimeException('That phone number looks invalid.');
            }

            $existing = db_one('SELECT * FROM members WHERE phone = ? LIMIT 1', [$phone]);

            if ($mode === 'login') {
                if (!$existing || !password_verify($pass, $existing['password_hash'])) {
                    throw new RuntimeException('Invalid phone or password.');
                }
            } else { // register
                if ($existing) {
                    // If this phone exists, treat password as login attempt
                    if (!password_verify($pass, $existing['password_hash'])) {
                        throw new RuntimeException('That phone is already registered. Use a different password or pick "I already have an account".');
                    }
                } else {
                    $name  = trim((string) input('name'));
                    $email = trim((string) input('email')) ?: null;
                    if ($name === '') {
                        throw new RuntimeException('Please enter your name.');
                    }
                    if (strlen($pass) < 6) {
                        throw new RuntimeException('Password must be at least 6 characters.');
                    }
                    db_insert(
                        'INSERT INTO members (name, phone, email, password_hash) VALUES (?, ?, ?, ?)',
                        [$name, $phone, $email,
                         password_hash($pass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])]
                    );
                }
            }
            // Now log them in
            if (!member_login($phone, $pass)) {
                throw new RuntimeException('Could not log you in. Please try again.');
            }
            $member = current_member();
        }

        $sp_id = current_referral_sp_id($cid);
        $code  = claim_voucher($cid, $voucher_id, (int) $member['id'], $sp_id);

        track_event($cid, 'voucher_claim', [
            'entity_type' => 'voucher',
            'entity_id'   => $voucher_id,
            'member_id'   => (int) $member['id'],
        ]);
        create_lead($cid, [
            'member_id'      => (int) $member['id'],
            'salesperson_id' => $sp_id,
            'customer_name'  => $member['name']  ?? null,
            'customer_phone' => $member['phone'] ?? null,
            'source'         => 'voucher_claim',
            'notes'          => 'Voucher claim: ' . $voucher['title']
                              . ($sp_id ? ' (ref: SP#' . $sp_id . ')' : ''),
        ]);

    } catch (RuntimeException $ex) {
        $err = $ex->getMessage();
    }
}

$member  = current_member();
$success = $code !== null;
layout_head($company, 'Claim Voucher');
?>
<section>
  <div class="container" style="max-width:560px;">
    <div class="box">
      <h1 style="margin:0 0 6px;font-size:clamp(22px,4vw,28px);">🎁 <?= e($voucher['title']) ?></h1>
      <?php if ($voucher['type'] === 'percent'): ?>
        <div style="font-size:30px;font-weight:800;color:var(--c-primary);"><?= e((string)$voucher['value']) ?>% OFF</div>
      <?php elseif ($voucher['type'] === 'fixed'): ?>
        <div style="font-size:30px;font-weight:800;color:var(--c-primary);">RM <?= e(number_format((float)$voucher['value'], 2)) ?> OFF</div>
      <?php else: ?>
        <div style="font-size:22px;font-weight:700;color:var(--c-primary);"><?= e(strtoupper($voucher['type'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($voucher['description'])): ?>
        <p class="muted" style="margin-top:6px;"><?= e($voucher['description']) ?></p>
      <?php endif; ?>
      <?php if (!empty($voucher['expiry_date'])): ?>
        <p class="muted">Valid until <?= e($voucher['expiry_date']) ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert success">
          🎉 Voucher claimed!<br>
          Your code:
          <strong style="font-size:22px;letter-spacing:2px;background:#fff;padding:4px 10px;border-radius:6px;border:1px dashed #166534;">
            <?= e($code) ?>
          </strong>
        </div>
        <p>Show this code at any branch to redeem.</p>
        <div class="btn-row">
          <a class="btn primary block" href="/member-dashboard.php">View My Vouchers</a>
          <a class="btn outline" href="/voucher.php">Browse more</a>
        </div>

      <?php else: ?>
        <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

        <form method="post" id="claim-form">
          <?= csrf_field() ?>
          <input type="hidden" name="mode" id="mode-input" value="<?= $member ? 'logged' : 'register' ?>">

          <?php if ($member): ?>
            <p>You're logged in as <strong><?= e($member['name']) ?></strong>.</p>
            <button class="btn primary block" type="submit" style="font-size:17px;padding:14px;">
              Claim My Voucher Now
            </button>
          <?php else: ?>
            <p style="margin:14px 0 6px;font-weight:600">Quick register to get this voucher:</p>

            <div id="register-fields">
              <label>Your Name</label>
              <input class="input" name="name" autocomplete="name" required>
            </div>

            <label>Phone Number</label>
            <div class="phone-field">
              <span class="prefix"><?= e(DEFAULT_COUNTRY_LABEL) ?></span>
              <input name="phone" type="tel" autocomplete="tel"
                     inputmode="tel" placeholder="123456789" required>
            </div>

            <label>Password <span class="muted">(min 6 chars)</span></label>
            <input class="input" name="password" type="password" autocomplete="new-password"
                   minlength="6" required>

            <label class="muted" id="email-label" style="font-weight:400">Email <span class="muted">(optional)</span></label>
            <input class="input" name="email" type="email" autocomplete="email" id="email-input">

            <button class="btn primary block" type="submit" style="margin-top:14px;font-size:17px;padding:14px;">
              Register & Claim Voucher
            </button>

            <p class="center" style="margin-top:14px;">
              <a href="#" id="toggle-mode" style="font-size:14px;">I already have an account</a>
            </p>
          <?php endif; ?>
        </form>

        <?php if (!$member): ?>
        <script>
          (function () {
            var toggle = document.getElementById('toggle-mode');
            var modeIn = document.getElementById('mode-input');
            var regFields = document.getElementById('register-fields');
            var emailLab = document.getElementById('email-label');
            var emailIn  = document.getElementById('email-input');
            var btn = document.querySelector('#claim-form button[type=submit]');
            toggle.addEventListener('click', function (e) {
              e.preventDefault();
              if (modeIn.value === 'login') {
                modeIn.value = 'register';
                regFields.style.display = '';
                emailLab.style.display = '';
                emailIn.style.display = '';
                btn.textContent = 'Register & Claim Voucher';
                toggle.textContent = 'I already have an account';
              } else {
                modeIn.value = 'login';
                regFields.style.display = 'none';
                emailLab.style.display = 'none';
                emailIn.style.display = 'none';
                btn.textContent = 'Login & Claim Voucher';
                toggle.textContent = 'New here? Create an account';
              }
            });
          })();
        </script>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
