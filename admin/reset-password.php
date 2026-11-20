<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/password_reset.php';

$token = trim((string) input('token'));
$reset = $token ? pw_validate_token($token, 'super_admin') : null;

$err = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    csrf_check();
    $pw  = (string) input('password');
    $pw2 = (string) input('password2');
    if (strlen($pw) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif ($pw !== $pw2) {
        $err = 'Passwords do not match.';
    } else {
        pw_consume_token((int) $reset['id'], 'super_admin', (int) $reset['user_id'], $pw);
        $done = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset password | <?= e(APP_NAME) ?></title>
<style>
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
       min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
       background:
         radial-gradient(900px 500px at -10% 110%, rgba(245,158,11,.18), transparent 60%),
         radial-gradient(700px 500px at 110% -10%, rgba(99,102,241,.18), transparent 60%),
         linear-gradient(135deg, #0f172a, #000); color:#fff; }
.card { background:#fff; color:#111; padding: 30px; border-radius: 14px; max-width: 420px; width: 100%; box-shadow: 0 12px 40px rgba(0,0,0,.4); }
.card .badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px;
  background:#fff7ed; color:#b45309; font-size:12px; font-weight:600; letter-spacing:.04em; }
h2 { margin: 12px 0 4px; font-size: 24px; }
p.lead { color:#6b7280; margin: 0 0 20px; font-size: 14px; }
label { display:block; font-size:13px; color:#374151; margin: 14px 0 6px; font-weight:500; }
input { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:10px; font: inherit; }
input:focus { outline:0; border-color:#0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,.12); }
button { width:100%; padding: 13px; border:0; cursor:pointer; background:#0f172a; color:#fff; font-weight:600; border-radius:10px; margin-top: 18px; font-size: 15px; }
.alert { padding: 11px 14px; border-radius:8px; margin-bottom: 14px; font-size: 14px; }
.alert.error { background:#fee2e2; color:#991b1b; }
.alert.success { background:#dcfce7; color:#166534; }
.foot { margin-top: 18px; text-align:center; font-size:13px; color:#6b7280; }
.foot a { color:#0f172a; font-weight: 600; text-decoration:none; }
</style>
</head>
<body>
<div class="card">
  <span class="badge">🔒 SUPER ADMIN</span>

  <?php if ($done): ?>
    <h2>Password updated</h2>
    <div class="alert success" style="margin-top:14px;">✅ Your new password has been saved.</div>
    <a href="/admin/login.php"><button type="button">Sign in →</button></a>
  <?php elseif (!$reset): ?>
    <h2>Link not valid</h2>
    <p class="lead">This reset link is invalid, expired, or already used.</p>
    <a href="/admin/forgot-password.php"><button type="button">Request a new link</button></a>
  <?php else: ?>
    <h2>Set a new password</h2>
    <p class="lead">For <strong><?= e($reset['email']) ?></strong>. Choose at least 8 characters.</p>
    <?php if ($err): ?><div class="alert error">⚠️ <?= e($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <label>New password</label>
      <input name="password" type="password" autocomplete="new-password" minlength="8" required autofocus>
      <label>Confirm password</label>
      <input name="password2" type="password" autocomplete="new-password" minlength="8" required>
      <button type="submit">Update password</button>
    </form>
  <?php endif; ?>
  <div class="foot"><a href="/admin/login.php">← Back to login</a></div>
</div>
</body>
</html>
