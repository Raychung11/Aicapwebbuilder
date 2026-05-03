<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/layout.php';

$company = require_company();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone_raw = trim((string) input('phone'));
    $phone     = normalize_phone($phone_raw);
    $pass      = (string) input('password');
    if ($phone === '' || $pass === '') {
        $err = 'Please enter your phone and password.';
    } elseif (member_login($phone, $pass)) {
        redirect('/member-dashboard.php');
    } else {
        $err = 'Invalid phone or password.';
    }
}

layout_head($company, 'Member Login');
?>
<section>
  <div class="container" style="max-width:420px;">
    <div class="box">
      <h1 style="margin:0 0 12px">Member Login</h1>
      <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <label>Phone</label>
        <div class="phone-field">
          <span class="prefix"><?= e(DEFAULT_COUNTRY_LABEL) ?></span>
          <input name="phone" type="tel" autocomplete="tel"
                 inputmode="tel" required autofocus
                 placeholder="123456789"
                 value="<?= e($_POST['phone'] ?? '') ?>">
        </div>
        <label>Password</label>
        <input class="input" type="password" name="password"
               autocomplete="current-password" required>
        <p><button class="btn primary" type="submit">Login</button></p>
      </form>
      <p class="muted">
        Don't have an account? <a href="/member-register.php">Register</a>
      </p>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
