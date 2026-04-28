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
    $phone = trim((string) input('phone'));
    $pass  = (string) input('password');
    if (member_login($phone, $pass)) {
        redirect('/member-dashboard.php');
    }
    $err = 'Invalid phone or password.';
}

layout_head($company, 'Member Login');
?>
<section>
  <div class="container" style="max-width:420px;">
    <div class="box">
      <h1 style="margin:0 0 12px">Member Login</h1>
      <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <label>Phone</label>
        <input class="input" name="phone" required>
        <label>Password</label>
        <input class="input" type="password" name="password" required>
        <p><button class="btn primary" type="submit">Login</button></p>
      </form>
      <p class="muted">Don't have an account? <a href="/member-register.php">Register</a></p>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
