<?php
/**
 * AICAP corporate landing — included from index.php when no tenant
 * resolves (i.e. visiting aicap.my directly).
 *
 * Two sections for now: a bold hero introducing AICAP, and a "Get in
 * touch" section with login / about / inquiry CTAs. The full features
 * + pricing + licensing build-out lands in the next iteration.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

$companies = db_all(
    'SELECT id, name, slug, subdomain, logo, theme_color
       FROM companies WHERE status = "active" ORDER BY name LIMIT 12'
);

$on_platform = (($_SERVER['HTTP_HOST'] ?? '') === APP_BASE_DOMAIN
              || ($_SERVER['HTTP_HOST'] ?? '') === 'www.' . APP_BASE_DOMAIN);

$_title = APP_NAME . ' — Multi-tenant SaaS for Furniture Brands';
$_desc  = 'AICAP Furniture BOS gives every furniture brand its own branded website, '
        . 'e-catalog, vouchers, leads and analytics. Subscribe or join our licensing '
        . 'program today.';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0f172a">
<title><?= e($_title) ?></title>
<meta name="description" content="<?= e($_desc) ?>">
<meta property="og:title" content="<?= e($_title) ?>">
<meta property="og:description" content="<?= e($_desc) ?>">
<meta property="og:type" content="website">
<style>
:root { --bg:#0f172a; --bg2:#1e293b; --fg:#fff; --muted:#94a3b8; --accent:#f59e0b; --soft:#cbd5e1; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
       background:#fafafa; color:#111; -webkit-font-smoothing: antialiased; line-height:1.6; }
img { max-width:100%; height:auto; display:block; }
.container { max-width: 1080px; margin: 0 auto; padding: 0 20px; }

.topbar { background: var(--bg); color:#fff; padding: 14px 0; position:sticky; top:0; z-index:50; }
.topbar .row { display:flex; align-items:center; justify-content:space-between; gap:14px; }
.topbar a { color:#fff; text-decoration:none; }
.topbar .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:16px; }
.topbar .brand .dot { width:24px; height:24px; border-radius:6px; background: var(--accent); }
.topbar nav { display:flex; gap:18px; align-items:center; font-size:14px; }
.topbar nav a { opacity:.85; }
.topbar nav a:hover { opacity:1; }

.btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding: 10px 18px;
       border-radius: 8px; font-weight:600; text-decoration:none; border:0; cursor:pointer;
       font: inherit; min-height: 42px; line-height: 1.2; }
.btn.primary { background: var(--accent); color:#111; }
.btn.outline { background: transparent; border:1px solid #334155; color:#fff; }
.btn.dark    { background: var(--bg); color:#fff; }
.btn-row { display:flex; flex-wrap:wrap; gap:10px; }

.hero { background: linear-gradient(135deg, var(--bg), #000); color:#fff; padding: clamp(60px,11vw,100px) 0; }
.hero .tag { display:inline-block; padding:4px 10px; border-radius:999px;
             background: rgba(245,158,11,.15); color: var(--accent); font-size:12px;
             font-weight:600; letter-spacing:.06em; text-transform: uppercase; margin-bottom:14px; }
.hero h1 { font-size: clamp(34px,6vw,54px); margin: 0 0 18px; line-height:1.1; max-width: 800px; }
.hero p  { color: var(--soft); font-size: clamp(16px,2.2vw,20px); max-width: 720px; margin: 0; }

section { padding: clamp(48px,8vw,80px) 0; }
section h2 { font-size: clamp(24px,3.5vw,34px); margin: 0 0 14px; }
section.alt { background:#fff; border-top:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb; }

.tenant-grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); margin-top: 18px; }
.tenant { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding: 14px;
          display:flex; flex-direction:column; gap:8px; }
.tenant .h { display:flex; align-items:center; gap:10px; }
.tenant .h img { width:32px; height:32px; object-fit:contain; background:#f3f4f6; border-radius:6px; padding:3px; }
.tenant .h .ph { width:32px; height:32px; background:#1e293b; color:#fff; border-radius:6px;
                 display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
.tenant a.try { font-size: 13px; padding: 6px 10px; border-radius:6px; background:#0f172a; color:#fff;
                text-decoration:none; align-self:flex-start; margin-top:auto; }

footer.site { background:#0b1020; color:#cbd5e1; padding: 30px 0; font-size:14px; }
footer.site a { color:#fff; text-decoration:none; }
footer.site .row { display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px; align-items:center; }
footer.site .copy { opacity:.6; font-size:12px; }

.note { background:#fff7ed; border-left:3px solid var(--accent); padding:12px 14px; border-radius:6px;
        margin-top: 22px; font-size:14px; color:#7c2d12; }
.note code { background:#fff; padding:2px 6px; border-radius:4px; }
</style>
</head>
<body>

<div class="topbar">
  <div class="container row">
    <a class="brand" href="/"><span class="dot"></span> AICAP Furniture BOS</a>
    <nav>
      <a href="/about.php">About</a>
      <a href="/admin/login.php">Super Admin</a>
      <a href="/company-admin/login.php">Tenant Login</a>
    </nav>
  </div>
</div>

<!-- ===== Section 1: What we do ===== -->
<section class="hero">
  <div class="container">
    <span class="tag">Multi-tenant SaaS for Furniture Brands</span>
    <h1>One platform.<br>A branded website for every furniture company.</h1>
    <p>
      AICAP Furniture BOS gives every licensed brand its own subdomain, e-catalog,
      voucher system, lead capture, and analytics — fully isolated, fully on-brand.
    </p>
    <div class="btn-row" style="margin-top:24px;">
      <a class="btn primary" href="/about.php">Read about AICAP →</a>
      <a class="btn outline" href="/company-admin/login.php">Tenant Login</a>
    </div>
  </div>
</section>

<!-- ===== Section 2: Get started / Live tenants ===== -->
<section class="alt">
  <div class="container">
    <h2>Live brands on AICAP</h2>
    <p style="color:#374151;max-width:680px;">
      A few of the furniture companies already running on AICAP. The full corporate
      site — features, pricing, and the licensing &amp; partnership program — is
      coming next.
    </p>

    <?php if ($companies): ?>
      <div class="tenant-grid">
        <?php foreach ($companies as $co): ?>
          <div class="tenant">
            <div class="h">
              <?php if (!empty($co['logo'])): ?>
                <img src="<?= e($co['logo']) ?>" alt="">
              <?php else: ?>
                <div class="ph"><?= e(strtoupper(substr($co['name'], 0, 1))) ?></div>
              <?php endif; ?>
              <div>
                <strong><?= e($co['name']) ?></strong>
                <div style="color:#6b7280;font-size:12px;"><?= e($co['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></div>
              </div>
            </div>
            <a class="try" href="<?= e($on_platform ? company_url($co) : '/?as=' . $co['slug']) ?>">
              <?= $on_platform ? 'Visit site ↗' : 'Preview' ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted" style="color:#6b7280;">No tenants live yet.</p>
    <?php endif; ?>

    <?php if (!$on_platform): ?>
      <div class="note">
        Preview URL detected. DNS for <code><?= e(APP_BASE_DOMAIN) ?></code> isn't pointed
        here yet — click <strong>Preview</strong> on any tenant to browse it on this domain.
      </div>
    <?php endif; ?>

    <div style="margin-top:28px;display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn dark" href="/about.php">About AICAP</a>
      <a class="btn outline" style="color:#0f172a;border-color:#cbd5e1" href="/admin/login.php">Super Admin</a>
    </div>
  </div>
</section>

<footer class="site">
  <div class="container row">
    <div>© <?= date('Y') ?> AICAP Furniture BOS</div>
    <div>
      <a href="/">Home</a> &nbsp;·&nbsp;
      <a href="/about.php">About</a> &nbsp;·&nbsp;
      <a href="/admin/login.php">Super Admin</a> &nbsp;·&nbsp;
      <a href="/company-admin/login.php">Tenant Login</a>
    </div>
  </div>
</footer>

</body>
</html>
