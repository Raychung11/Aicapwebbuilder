<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/password_reset.php';

$sent = false;
$err  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string) input('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        pw_request_reset('company_admin', $email, '/company-admin/reset-password.php');
        $sent = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot password | <?= e(APP_NAME) ?></title>
<style>
* { box-sizing: border-box; } html, body { margin:0; padding:0; }
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
       background:#0f172a; color:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
       background:
         radial-gradient(900px 500px at -10% 110%, rgba(16,185,129,.18), transparent 60%),
         radial-gradient(700px 500px at 110% -10%, rgba(59,130,246,.18), transparent 60%),
         linear-gradient(135deg, #0f172a, #000); }
.card { background:#fff; color:#111; padding: 30px; border-radius: 14px; max-width: 420px; width: 100%; box-shadow: 0 12px 40px rgba(0,0,0,.4); }
.badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px;
  background:#ecfdf5; color:#047857; font-size:12px; font-weight:600; letter-spacing:.04em; }
h2 { margin: 12px 0 4px; font-size: 24px; }
p.lead { color:#6b7280; margin: 0 0 20px; font-size: 14px; }
label { display:block; font-size:13px; color:#374151; margin: 0 0 6px; font-weight: 500; }
input { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:10px; font: inherit; }
input:focus { outline:0; border-color:#10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.18); }
button { width:100%; padding: 13px; border:0; cursor:pointer; background:#10b981; color:#fff; font-weight:600; border-radius:10px; margin-top: 14px; font-size: 15px; }
button:hover { background:#059669; }
.alert { padding: 11px 14px; border-radius:8px; margin-bottom: 14px; font-size: 14px; }
.alert.error { background:#fee2e2; color:#991b1b; }
.alert.success { background:#dcfce7; color:#166534; }
.foot { margin-top: 18px; text-align:center; font-size:13px; color:#6b7280; }
.foot a { color:#047857; font-weight: 600; text-decoration:none; }
</style>
</head>
<body>
<form class="card" method="post" autocomplete="on">
  <span class="badge">🏪 TENANT ADMIN</span>
  <h2>Forgot your password?</h2>
  <p class="lead">Enter the email tied to your tenant account and we'll send a reset link.</p>

  <?php if ($sent): ?>
    <div class="alert success">
      ✅ If an account exists for that email, a reset link has been sent.
      The link expires in 1 hour.
    </div>
  <?php elseif ($err): ?>
    <div class="alert error">⚠️ <?= e($err) ?></div>
  <?php endif; ?>

  <?= csrf_field() ?>
  <label for="email">Email address</label>
  <input id="email" name="email" type="email" autocomplete="username" required autofocus
         placeholder="owner@yourbrand.my" value="<?= e($_POST['email'] ?? '') ?>">

  <button type="submit">Send reset link</button>

  <div class="foot"><a href="/company-admin/login.php">← Back to tenant login</a></div>
</form>
</body>
</html>
