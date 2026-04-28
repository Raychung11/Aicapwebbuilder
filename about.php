<?php
/**
 * AICAP corporate "About Us" page (aicap.my/about.php).
 *
 * If a tenant subdomain or custom domain is in play, this falls back to
 * the tenant homepage's #about section — tenants have their own About in
 * their landing page.
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

$company = current_company();
if ($company) {
    // Tenant context -> their About lives on the homepage.
    redirect('/#about');
}

$page_title = 'About Us | AICAP Furniture BOS';
$page_desc  = 'AICAP Furniture BOS is a multi-tenant SaaS platform that gives every '
            . 'furniture brand its own branded website, e-catalog, voucher system, '
            . 'lead capture and analytics — all under one digital franchise OS.';

$tenants = db_all(
    'SELECT name, slug, subdomain, logo, theme_color
       FROM companies WHERE status = "active" ORDER BY name LIMIT 12'
);
$on_platform = (($_SERVER['HTTP_HOST'] ?? '') === APP_BASE_DOMAIN
              || ($_SERVER['HTTP_HOST'] ?? '') === 'www.' . APP_BASE_DOMAIN);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0f172a">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:type" content="website">
<style>
:root { --bg:#0f172a; --bg2:#1e293b; --fg:#fff; --muted:#94a3b8; --accent:#f59e0b; --soft:#cbd5e1; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body {
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  background:#fafafa; color:#111; -webkit-font-smoothing: antialiased;
  font-size: 16px; line-height: 1.6;
}
img { max-width:100%; height:auto; display:block; }
a { color: var(--bg); }
.container { max-width: 980px; margin: 0 auto; padding: 0 20px; }

/* ---------- Top bar ---------- */
.topbar { background: var(--bg); color: var(--fg); padding: 14px 0; position: sticky; top:0; z-index:50; }
.topbar .row { display:flex; align-items:center; justify-content:space-between; gap:14px; }
.topbar a { color: var(--fg); text-decoration:none; }
.topbar .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:16px; }
.topbar .brand .dot { width:24px; height:24px; border-radius:6px; background: var(--accent); }
.topbar nav { display:flex; gap:18px; align-items:center; font-size:14px; }
.topbar nav a { opacity:.85; }
.topbar nav a:hover { opacity:1; }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding: 10px 18px;
       border-radius: 8px; font-weight:600; text-decoration:none; border:0; cursor:pointer;
       font: inherit; min-height: 42px; line-height: 1.2; }
.btn.primary { background: var(--accent); color:#111; }
.btn.outline { background: transparent; border:1px solid #334155; color: var(--fg); }
.btn.dark    { background: var(--bg); color:#fff; }
.btn-row { display:flex; flex-wrap:wrap; gap:10px; }

/* ---------- Hero ---------- */
.hero { background: linear-gradient(135deg, var(--bg), #000); color:#fff; padding: clamp(50px,10vw,90px) 0; }
.hero h1 { font-size: clamp(32px,6vw,52px); margin: 0 0 16px; line-height:1.1; }
.hero .lead { color: var(--soft); font-size: clamp(16px,2.2vw,20px); max-width: 720px; }
.hero .tag  { display:inline-block; padding:4px 10px; border-radius:999px; background: rgba(245,158,11,.15);
              color: var(--accent); font-size:12px; font-weight:600; letter-spacing:.06em;
              text-transform: uppercase; margin-bottom: 14px; }

/* ---------- Sections ---------- */
section { padding: clamp(48px,8vw,80px) 0; }
section h2 { font-size: clamp(24px,3.5vw,34px); margin: 0 0 14px; }
section p  { color:#374151; max-width: 720px; }
.alt { background:#fff; border-top:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb; }

.split { display:grid; gap:36px; grid-template-columns: 1fr; }
@media (min-width: 760px) { .split { grid-template-columns: 1.1fr 1fr; align-items: start; } }

.values { display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top: 24px; }
.value { background:#fff; padding:22px; border-radius:12px; border:1px solid #e5e7eb; }
.value .ico { width:40px; height:40px; border-radius:10px; background: var(--accent); display:flex;
              align-items:center; justify-content:center; font-size:20px; margin-bottom: 10px; }
.value h3 { margin: 0 0 6px; font-size: 17px; }
.value p  { margin: 0; color:#4b5563; font-size: 14px; }

.stats { display:grid; gap:20px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-top: 24px; }
.stat { background: var(--bg); color:#fff; padding: 22px; border-radius:12px; }
.stat .v { font-size: 30px; font-weight: 800; color: var(--accent); }
.stat .l { font-size: 13px; color: var(--muted); margin-top: 4px; }

.steps { display:grid; gap:18px; grid-template-columns: 1fr; margin-top:24px; }
@media (min-width: 760px) { .steps { grid-template-columns: repeat(3, 1fr); } }
.step { background:#fff; padding: 22px; border-radius: 12px; border:1px solid #e5e7eb; position:relative; }
.step .num { position:absolute; top:-14px; left: 22px; width:32px; height:32px; border-radius: 8px;
  background: var(--bg); color: var(--accent); display:flex; align-items:center; justify-content:center;
  font-weight: 800; }
.step h3 { margin: 8px 0 6px; font-size: 17px; }
.step p  { margin: 0; color: #4b5563; font-size: 14px; }

.brands { display:grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); margin-top: 22px; }
.brand-card {
  background:#fff; border:1px solid #e5e7eb; border-radius: 10px;
  padding: 14px; display:flex; gap:12px; align-items:center; text-decoration:none; color:#111;
  transition: transform .15s, border-color .15s, box-shadow .15s;
}
.brand-card:hover { transform: translateY(-2px); border-color: var(--accent); box-shadow: 0 6px 14px rgba(0,0,0,.06); }
.brand-card .logo {
  width:42px; height:42px; border-radius: 8px; background:#f3f4f6;
  display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff;
  overflow: hidden; flex-shrink: 0;
}
.brand-card .logo img { width:100%; height:100%; object-fit:contain; padding:5px; background:#fff; }
.brand-card .meta strong { display:block; font-size: 14px; line-height: 1.2; }
.brand-card .meta span  { font-size: 12px; color:#6b7280; }
.brands-empty { color:#9ca3af; font-size: 14px; }

.cta-band { background: linear-gradient(135deg, var(--bg), #1f2937); color:#fff; }
.cta-band h2 { color:#fff; }
.cta-band p  { color: var(--soft); }

footer.site { background:#0b1020; color:#cbd5e1; padding: 30px 0; font-size:14px; }
footer.site a { color:#fff; text-decoration:none; }
footer.site .row { display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px; align-items:center; }
footer.site .copy { opacity:.6; font-size:12px; }
</style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
  <div class="container row">
    <a class="brand" href="/">
      <span class="dot"></span> AICAP Furniture BOS
    </a>
    <nav>
      <a href="/">Home</a>
      <a href="/about.php" style="opacity:1">About</a>
      <a href="/admin/login.php">Login</a>
    </nav>
  </div>
</div>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <span class="tag">About AICAP</span>
    <h1>The digital operating system<br>for furniture brands.</h1>
    <p class="lead">
      We help furniture companies in Malaysia run a modern, mobile-first online presence —
      a branded website on their own subdomain, an e-catalog, vouchers, lead capture and
      analytics — all from one place.
    </p>
    <div class="btn-row" style="margin-top:22px;">
      <a class="btn primary" href="/#contact">Talk to us</a>
      <a class="btn outline" href="/">Back to home</a>
    </div>
  </div>
</section>

<!-- Mission -->
<section class="alt">
  <div class="container split">
    <div>
      <h2>Our mission</h2>
      <p>
        Furniture is a face-to-face business — but customers research online first. Every
        showroom visit, every WhatsApp enquiry, every voucher claim should be trackable
        and tied to the brand that earned it.
      </p>
      <p>
        AICAP Furniture BOS is built so each licensed brand keeps its own identity, its own
        catalog, and its own customer relationships, while sharing a single robust platform
        underneath. Less software headache for the brand, more time selling sofas and beds.
      </p>
    </div>
    <div>
      <h2>What we do</h2>
      <ul style="padding-left:18px;color:#374151;">
        <li>Branded subdomain or custom domain per tenant</li>
        <li>E-catalog with categories, subcategories &amp; variants</li>
        <li>One-step voucher claim with member loyalty</li>
        <li>WhatsApp + QR campaign attribution</li>
        <li>Branch directory with maps, Waze and tap-to-call</li>
        <li>Analytics for the metrics that matter</li>
      </ul>
    </div>
  </div>
</section>

<!-- Values -->
<section>
  <div class="container">
    <h2>What we believe</h2>
    <p>Three principles guide every decision we make on the platform.</p>
    <div class="values">
      <div class="value">
        <div class="ico">🏷️</div>
        <h3>Each brand stays its own brand</h3>
        <p>Every tenant looks, feels and ranks for itself — never as a marketplace tab.</p>
      </div>
      <div class="value">
        <div class="ico">📱</div>
        <h3>Mobile-first, always</h3>
        <p>Customers shop from a phone. Every page loads fast and works on any screen.</p>
      </div>
      <div class="value">
        <div class="ico">🔒</div>
        <h3>Your data is yours</h3>
        <p>Strict tenant isolation — leads, members and analytics never leak between brands.</p>
      </div>
    </div>
  </div>
</section>

<!-- Stats / proof -->
<section class="alt">
  <div class="container">
    <h2>Built to scale</h2>
    <p>From a single showroom to a national chain — the same platform, the same uptime.</p>
    <div class="stats">
      <div class="stat"><div class="v">100+</div><div class="l">tenants supported per platform</div></div>
      <div class="stat"><div class="v">17</div><div class="l">tables, every one tenant-isolated</div></div>
      <div class="stat"><div class="v">5</div><div class="l">core analytics events tracked</div></div>
      <div class="stat"><div class="v">0</div><div class="l">cross-tenant data leaks by design</div></div>
    </div>
  </div>
</section>

<!-- How AICAP works -->
<section>
  <div class="container">
    <h2>How AICAP works for your brand</h2>
    <p>Three short steps from kick-off to a fully-branded furniture website live on the web.</p>
    <div class="steps">
      <div class="step">
        <div class="num">1</div>
        <h3>Onboard your brand</h3>
        <p>We provision your subdomain (or wire up your own domain), create your admin account
           and set the brand colors, logo and contact details so the site looks like yours from day one.</p>
      </div>
      <div class="step">
        <div class="num">2</div>
        <h3>Publish your catalog</h3>
        <p>Add categories, subcategories, products and variants — or seed a starter catalog
           with one click and edit from there. Mark items as Featured and they appear on your homepage.</p>
      </div>
      <div class="step">
        <div class="num">3</div>
        <h3>Capture &amp; convert leads</h3>
        <p>Every WhatsApp click, voucher claim and QR scan becomes a tracked lead, attributed to
           the right campaign and salesperson. Watch performance in your dashboard.</p>
      </div>
    </div>
  </div>
</section>

<!-- Trusted brands strip -->
<?php if ($tenants): ?>
<section class="alt">
  <div class="container">
    <h2>Trusted by furniture brands</h2>
    <p>A few of the companies already running on AICAP. Each one keeps its own brand,
       customers and analytics — fully isolated, fully on-brand.</p>

    <div class="brands">
      <?php foreach ($tenants as $t):
        $href = $on_platform
          ? 'https://' . $t['subdomain'] . '.' . APP_BASE_DOMAIN
          : '/?as=' . rawurlencode($t['slug']);
      ?>
        <a class="brand-card" href="<?= e($href) ?>" <?= $on_platform ? 'target="_blank" rel="noopener"' : '' ?>>
          <div class="logo" style="background: <?= e($t['theme_color'] ?: '#1e293b') ?>;">
            <?php if (!empty($t['logo'])): ?>
              <img src="<?= e($t['logo']) ?>" alt="<?= e($t['name']) ?>">
            <?php else: ?>
              <?= e(strtoupper(substr($t['name'], 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div class="meta">
            <strong><?= e($t['name']) ?></strong>
            <span><?= e($t['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="cta-band">
  <div class="container">
    <h2>Ready to put your brand on AICAP?</h2>
    <p>We're onboarding furniture companies for our subscription and licensing programs.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/#contact">Subscribe / partner with us</a>
      <a class="btn outline" href="/company-admin/login.php">Tenant Login</a>
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
