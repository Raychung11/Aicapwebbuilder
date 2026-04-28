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
    } elseif ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'That email address looks invalid.';
    } else {
        $byPhone = db_one('SELECT id FROM members WHERE phone = ? LIMIT 1', [$phone]);
        $byEmail = $email ? db_one('SELECT id FROM members WHERE email = ? LIMIT 1', [$email]) : null;

        if ($byPhone) {
            $err = 'This phone number is already registered. Try logging in instead.';
        } elseif ($byEmail) {
            $err = 'This email is already in use. Use a different email or log in.';
        } else {
            try {
                db_insert(
                    'INSERT INTO members (name, phone, email, password_hash) VALUES (?, ?, ?, ?)',
                    [$name, $phone, $email,
                     password_hash($pass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])]
                );
                if (member_login($phone, $pass)) {
                    redirect('/member-dashboard.php');
                }
                $err = 'Account created — please log in.';
            } catch (Throwable $e) {
                error_log('member-register: ' . $e->getMessage());
                $err = 'Could not create your account right now. Please try again.';
            }
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
      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <label>Name</label>
        <input class="input" name="name" required autocomplete="name"
               value="<?= e($_POST['name'] ?? '') ?>">

        <label>Phone</label>
        <input class="input" name="phone" type="tel" required autocomplete="tel"
               inputmode="tel" placeholder="e.g. 60123456789"
               value="<?= e($_POST['phone'] ?? '') ?>">

        <label>Email <span class="muted">(optional)</span></label>
        <input class="input" type="email" name="email" autocomplete="email"
               value="<?= e($_POST['email'] ?? '') ?>">

        <label>Password</label>
        <input class="input" type="password" name="password"
               minlength="6" required autocomplete="new-password">

        <p><button class="btn primary" type="submit">Register</button></p>
      </form>
      <p class="muted">Already have an account? <a href="/member-login.php">Login</a></p>
    </div>
  </div>
</section>
<?php layout_foot($company); ?>
