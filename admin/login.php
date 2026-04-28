<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (super_admin_login(trim((string) input('email')), (string) input('password'))) {
        redirect('/admin/index.php');
    }
    $err = 'Invalid credentials.';
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Super Admin Login</title>
<style>body{font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#0f172a;color:#fff;margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:#fff;color:#111;padding:28px;border-radius:10px;width:340px;box-shadow:0 8px 30px rgba(0,0,0,.3)}
input{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;font:inherit;margin-bottom:10px}
button{width:100%;padding:11px;border:0;background:#2563eb;color:#fff;font-weight:600;border-radius:6px;cursor:pointer}
.err{background:#fee2e2;color:#991b1b;padding:8px 10px;border-radius:6px;margin-bottom:10px;font-size:14px}
h2{margin:0 0 14px;font-size:20px}
</style></head>
<body>
<form class="box" method="post">
  <h2>Super Admin</h2>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <input name="email" type="email" placeholder="Email" required>
  <input name="password" type="password" placeholder="Password" required>
  <button type="submit">Login</button>
</form>
</body></html>
