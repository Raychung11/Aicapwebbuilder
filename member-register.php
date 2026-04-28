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
    $name  = trim((string) input('name'));
    $phone = trim((string) input('phone'));
    $email = trim((string) input('email')) ?: null;
    $pass  = (string) input('password');

    if ($name === '' || $phone === '' || strlen($pass) < 6) {
        $err = 'Please fill all fields. Password must be at least 6 characters.';
    } else {
        $exists = db_one('SELECT id FROM members WHERE phone = ? LIMIT 1', [$phone]);
        if ($exists) {
            $err = 'Phone already registered.';
        } else {
            db_insert(
                'INSERT INTO members (name, phone, email, password_hash) VALUES (?, ?, ?, ?)',
                [$name, $phone, $email, password_hash($pass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])]
            );
            member_login($phone, $pass);
            redirect('/member-dashboard.php');
        }
    }
}

layout_head($company, 'Register');
?>
<section>
  <div class="container" style="max-width:420px;">
    <div class="box">
      <h1 style="margin:0 0 12px">Create Account</h1>
      <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <label>Name</label><input class="input" name="name" required>
        <label>Phone</label><input class="input" name="phone" required>
        <label>Email (optional)</label><input class="input" type="email" name="email">
        <label>Password</label><input class="input" type="password" name="password" minlength="6" required>
        <p><button class="btn primary" type="submit">Register</button></p>
      </form>
      <p class="muted">Already have an account? <a href="/member-login.php">Login</a></p>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
