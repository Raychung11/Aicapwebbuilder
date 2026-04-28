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
    $err = 'Invalid email or password. Please try again.';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0f172a">
<title>Super Admin Login | <?= e(APP_NAME) ?></title>
<style>
:root { --bg:#0f172a; --bg2:#1e293b; --accent:#f59e0b; --accent-2:#fbbf24; --soft:#cbd5e1; --muted:#94a3b8; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body {
  min-height: 100vh;
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  background: #f3f4f6; color:#111; -webkit-font-smoothing: antialiased;
  display:flex;
}

/* ---------- Layout ---------- */
.shell {
  display:grid; min-height:100vh; width:100%;
  grid-template-columns: 1fr;
}
@media (min-width: 880px) { .shell { grid-template-columns: 1.05fr 1fr; } }

/* ---------- Left brand panel ---------- */
.brand-panel {
  background:
    radial-gradient(900px 500px at -10% 110%, rgba(245,158,11,.18), transparent 60%),
    radial-gradient(700px 500px at 110% -10%, rgba(99,102,241,.18), transparent 60%),
    linear-gradient(135deg, var(--bg), #000);
  color:#fff; padding: 40px 36px;
  display:flex; flex-direction:column; justify-content:space-between;
  position: relative; overflow:hidden;
}
@media (max-width: 879px) { .brand-panel { padding: 26px 22px; } }

.brand-panel .top { display:flex; align-items:center; gap:10px; font-weight:700; }
.brand-panel .top .dot { width:28px; height:28px; border-radius:8px; background: var(--accent);
  box-shadow: 0 0 0 4px rgba(245,158,11,.15); }
.brand-panel .top a { color:#fff; text-decoration:none; }

.brand-panel .pitch h1 {
  font-size: clamp(28px, 4.5vw, 44px); margin: 24px 0 12px; line-height: 1.15;
}
.brand-panel .pitch p { color: var(--soft); font-size: 16px; max-width: 460px; line-height: 1.6; margin: 0; }

.brand-panel .features { margin-top: 28px; display:grid; gap: 10px; }
.brand-panel .feature { display:flex; gap:10px; align-items:flex-start; color: var(--soft); font-size: 14px; }
.brand-panel .feature .ic {
  width: 24px; height: 24px; border-radius: 6px; background: rgba(245,158,11,.15);
  color: var(--accent); display:flex; align-items:center; justify-content:center;
  flex-shrink: 0; font-size: 13px;
}

.brand-panel .footer { color: var(--muted); font-size: 12px; }
.brand-panel .footer a { color: var(--soft); text-decoration: none; }
@media (max-width: 879px) {
  .brand-panel .features, .brand-panel .footer { display:none; }
}

/* ---------- Right form panel ---------- */
.form-panel {
  display:flex; align-items:center; justify-content:center;
  padding: 32px 22px; background: #fff;
}
.form-card {
  width: 100%; max-width: 400px;
}
.form-card .badge {
  display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
  border-radius: 999px; background: #fff7ed; color: #b45309;
  font-size: 12px; font-weight: 600; letter-spacing: .04em;
}
.form-card h2 { margin: 12px 0 4px; font-size: 28px; }
.form-card .lead { color: #6b7280; margin: 0 0 22px; font-size: 14px; }

label.field { display:block; margin: 0 0 14px; }
label.field .lbl { display:block; font-size: 13px; color:#374151; margin-bottom: 6px; font-weight: 500; }
label.field .wrap { position: relative; }
label.field input {
  width:100%; padding: 12px 12px 12px 40px; border:1px solid #d1d5db;
  border-radius:10px; font: inherit; background:#fff;
  transition: border-color .15s, box-shadow .15s;
}
label.field input:focus { outline:0; border-color: var(--bg); box-shadow: 0 0 0 3px rgba(15,23,42,.12); }
label.field .ic {
  position:absolute; left:12px; top:50%; transform: translateY(-50%);
  color:#9ca3af; font-size: 16px;
}
label.field .toggle {
  position:absolute; right:8px; top:50%; transform: translateY(-50%);
  background:none; border:0; color:#6b7280; cursor:pointer; font-size: 13px;
  padding: 6px 10px; border-radius: 6px;
}
label.field .toggle:hover { background:#f3f4f6; color:#111; }

.options { display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px; font-size: 13px; }
.options label { display:flex; align-items:center; gap:6px; color:#374151; }
.options a { color: var(--bg); text-decoration:none; font-weight: 500; }
.options a:hover { text-decoration: underline; }

button.submit {
  width:100%; padding: 13px; border:0; cursor:pointer;
  background: var(--bg); color:#fff; font-weight: 600; font-size: 15px;
  border-radius: 10px; transition: transform .05s, background .15s;
}
button.submit:hover { background: #1f2937; }
button.submit:active { transform: translateY(1px); }

.alert {
  background: #fee2e2; color:#991b1b; padding: 10px 12px; border-radius: 8px;
  margin-bottom: 14px; font-size: 14px; display:flex; gap:8px; align-items:flex-start;
}
.alert .ic { font-size: 16px; line-height: 1; flex-shrink:0; }

.alt-link { text-align:center; margin-top: 20px; font-size: 13px; color: #6b7280; }
.alt-link a { color: var(--bg); font-weight: 600; text-decoration: none; }
.alt-link a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="shell">

  <!-- LEFT: brand panel -->
  <aside class="brand-panel">
    <div class="top">
      <a href="/" style="display:flex;align-items:center;gap:10px;">
        <span class="dot"></span> AICAP Furniture BOS
      </a>
    </div>

    <div class="pitch">
      <h1>Super Admin Console</h1>
      <p>
        Manage every tenant on the AICAP platform — companies, page templates,
        cross-tenant analytics and partner inquiries — from one secure dashboard.
      </p>

      <div class="features">
        <div class="feature"><span class="ic">🏢</span> Manage all furniture company tenants</div>
        <div class="feature"><span class="ic">📊</span> Cross-tenant analytics &amp; KPIs</div>
        <div class="feature"><span class="ic">🪑</span> Seed sample products on demand</div>
        <div class="feature"><span class="ic">🛡️</span> Strict tenant data isolation</div>
      </div>
    </div>

    <div class="footer">
      © <?= date('Y') ?> <?= e(APP_NAME) ?> &nbsp;·&nbsp;
      <a href="/about.php">About</a> &nbsp;·&nbsp;
      <a href="/">Home</a>
    </div>
  </aside>

  <!-- RIGHT: form -->
  <main class="form-panel">
    <form class="form-card" method="post" autocomplete="on">
      <span class="badge">🔒 SUPER ADMIN</span>
      <h2>Welcome back</h2>
      <p class="lead">Sign in to your AICAP HQ dashboard.</p>

      <?php if ($err): ?>
        <div class="alert"><span class="ic">⚠️</span><span><?= e($err) ?></span></div>
      <?php endif; ?>

      <?= csrf_field() ?>

      <label class="field">
        <span class="lbl">Email address</span>
        <span class="wrap">
          <span class="ic">✉️</span>
          <input name="email" type="email" autocomplete="username"
                 placeholder="you@aicap.my" required autofocus
                 value="<?= e($_POST['email'] ?? '') ?>">
        </span>
      </label>

      <label class="field">
        <span class="lbl">Password</span>
        <span class="wrap">
          <span class="ic">🔑</span>
          <input id="pw" name="password" type="password" autocomplete="current-password"
                 placeholder="••••••••••" required>
          <button type="button" class="toggle" id="pw-toggle"
                  onclick="var i=document.getElementById('pw');var t=this;if(i.type==='password'){i.type='text';t.textContent='Hide';}else{i.type='password';t.textContent='Show';}">
            Show
          </button>
        </span>
      </label>

      <div class="options">
        <label><input type="checkbox" name="remember"> Keep me signed in</label>
        <a href="/admin/forgot-password.php">Forgot password?</a>
      </div>

      <button class="submit" type="submit">Sign in →</button>

      <div class="alt-link">
        Looking for the tenant admin? <a href="/company-admin/login.php">Tenant Login</a>
      </div>
    </form>
  </main>

</div>

</body>
</html>
