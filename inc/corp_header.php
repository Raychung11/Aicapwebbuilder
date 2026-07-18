<?php
/**
 * Shared corporate chrome — header.
 *
 * Expects in scope:
 *   $page_title    string   <title>
 *   $page_id       string   home | features | consulting | how | pricing | licensing | about | contact | blog
 *   $page_desc     string   meta description (also used for og:/twitter:)
 *   $page_image    string   optional og:image (absolute or root-relative)
 *   $page_keywords string   optional meta keywords (comma-separated)
 *   $page_jsonld   array    optional array of Schema.org JSON-LD objects
 *                           (Organization is emitted automatically site-wide;
 *                           add Service/FAQPage/HowTo/BreadcrumbList/etc here)
 *   $page_faq      array    optional [[q,a], ...] — auto-rendered as FAQPage
 *                           JSON-LD if $page_jsonld doesn't already include one
 */
require_once __DIR__ . '/helpers.php';

$page_title    = $page_title    ?? 'AICAP Furniture BOS';
$page_id       = $page_id       ?? '';
$page_desc     = $page_desc     ?? 'Multi-tenant SaaS + professional consulting for Malaysia\'s furniture industry — branded tenant sites, e-catalog, vouchers, leads, analytics, and end-to-end digital transformation advisory.';
$page_image    = $page_image    ?? '';
$page_keywords = $page_keywords ?? 'furniture BOS, Malaysia furniture, furniture SaaS, digital transformation, ERP, CRM, WMS, BI dashboard, consulting, AICAP';
$page_jsonld   = $page_jsonld   ?? [];
$page_faq      = $page_faq      ?? [];

$_scheme = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
$_host   = $_SERVER['HTTP_HOST'] ?? '';
$_url    = $_scheme . '://' . $_host . ($_SERVER['REQUEST_URI'] ?? '/');
$_origin = $_scheme . '://' . $_host;

if ($page_image && !preg_match('#^https?://#', $page_image)) {
    $page_image = $_origin . $page_image;
}

// ---------- Site-wide Organization schema (renders on every page) ----------
$_org_schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Organization',
    '@id'         => $_origin . '/#organization',
    'name'        => 'AICAP Solution',
    'legalName'   => 'AICAP Solution Sdn. Bhd.',
    'url'         => $_origin,
    'logo'        => $_origin . '/favicon.ico',
    'description' => 'AICAP Solution provides SaaS-based Furniture Business Operating System (BOS) and professional consulting for Malaysia\'s furniture industry — spanning e-catalog, CRM, WMS, BI dashboards, dealer portals and marketplace integration.',
    'foundingDate'=> '2024',
    'identifier'  => '202401048231',
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Zenith Corporate Park 1, Block B-19-02, Jalan SS7/26 Kelana Jaya',
        'addressLocality' => 'Petaling Jaya',
        'addressRegion'   => 'Selangor',
        'addressCountry'  => 'MY',
    ],
    'areaServed'  => ['@type' => 'Country', 'name' => 'Malaysia'],
    'knowsAbout'  => [
        'Furniture industry digitalisation',
        'Business Operating System (BOS)',
        'BI Dashboard', 'CRM', 'WMS',
        'Dealer Portal', 'Supplier Portal',
        'Marketplace integration',
        'AI in furniture business',
    ],
];

// Site-wide WebSite schema with SearchAction (helps AEO recognise the site)
$_site_schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    '@id'      => $_origin . '/#website',
    'url'      => $_origin,
    'name'     => 'AICAP Furniture BOS',
    'publisher'=> ['@id' => $_origin . '/#organization'],
    'inLanguage' => 'en-MY',
];

// If a page passed a $page_faq array and no FAQPage is already in $page_jsonld,
// build one automatically for AEO.
$_has_faq_ld = false;
foreach ($page_jsonld as $b) {
    if (($b['@type'] ?? '') === 'FAQPage') { $_has_faq_ld = true; break; }
}
if ($page_faq && !$_has_faq_ld) {
    $mainEntity = [];
    foreach ($page_faq as $qa) {
        if (!isset($qa[0], $qa[1])) continue;
        $mainEntity[] = [
            '@type' => 'Question',
            'name'  => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ];
    }
    if ($mainEntity) {
        $page_jsonld[] = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }
}

$_all_jsonld = array_merge([$_org_schema, $_site_schema], $page_jsonld);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0f172a">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<?php if ($page_keywords): ?><meta name="keywords" content="<?= e($page_keywords) ?>"><?php endif; ?>
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
<meta name="author" content="AICAP Solution Sdn. Bhd.">
<meta name="geo.region" content="MY-10">
<meta name="geo.placename" content="Petaling Jaya, Selangor, Malaysia">
<link rel="canonical" href="<?= e($_url) ?>">
<meta property="og:site_name" content="AICAP Furniture BOS">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:url" content="<?= e($_url) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_MY">
<?php if ($page_image): ?>
<meta property="og:image" content="<?= e($page_image) ?>">
<meta property="og:image:alt" content="<?= e($page_title) ?>">
<?php endif; ?>
<meta name="twitter:card" content="<?= $page_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($page_title) ?>">
<meta name="twitter:description" content="<?= e($page_desc) ?>">
<?php if ($page_image): ?><meta name="twitter:image" content="<?= e($page_image) ?>"><?php endif; ?>
<?php foreach ($_all_jsonld as $_ld): ?>
<script type="application/ld+json"><?= json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endforeach; ?>
<style>
:root { --bg:#0f172a; --bg2:#1e293b; --fg:#fff; --muted:#94a3b8; --soft:#cbd5e1; --accent:#f59e0b; --accent-2:#fbbf24; --ink:#111; --ink-2:#374151; --line:#e5e7eb; --bg-soft:#f9fafb; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
html { scroll-behavior: smooth; }
body {
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  background:#fff; color: var(--ink); -webkit-font-smoothing: antialiased;
  font-size: 16px; line-height: 1.6;
}
img { max-width:100%; height:auto; display:block; }
a { color: var(--bg); }
.container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

/* ---------- Top bar ---------- */
.corp-topbar {
  background: var(--bg); color:#fff;
  position: sticky; top:0; z-index:60;
  border-bottom: 1px solid rgba(255,255,255,.05);
}
.corp-topbar .row {
  display:flex; align-items:center; justify-content:space-between; gap:14px;
  padding: 12px 0;
}
.corp-topbar a { color:#fff; text-decoration:none; }
.corp-topbar .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:16px; }
.corp-topbar .brand .dot {
  width:26px; height:26px; border-radius:7px; background: var(--accent);
  color: var(--bg); display:flex; align-items:center; justify-content:center;
  font-weight: 800; font-size: 13px;
}
.corp-topbar nav.corp-nav { display:flex; gap:18px; align-items:center; font-size:14px; }
.corp-topbar nav.corp-nav a { opacity:.85; padding: 6px 4px; }
.corp-topbar nav.corp-nav a:hover { opacity:1; }
.corp-topbar nav.corp-nav a.active { opacity:1; box-shadow: inset 0 -2px 0 var(--accent); }
.corp-topbar nav.corp-nav a.cta {
  background: var(--accent); color: var(--bg); padding: 8px 14px; border-radius: 8px;
  font-weight: 600; opacity: 1;
}
.corp-topbar nav.corp-nav a.cta:hover { background: var(--accent-2); }

/* Hamburger */
.corp-burger { display:none; background:transparent; border:0; padding:8px; cursor:pointer; border-radius:8px; }
.corp-burger:hover { background: rgba(255,255,255,.08); }
.corp-burger .bars { width:22px; height:16px; position:relative; display:block; }
.corp-burger .bars::before, .corp-burger .bars::after, .corp-burger .bars span {
  content:''; position:absolute; left:0; right:0; height:2px; background:#fff; border-radius:2px;
  transition: top .25s, transform .25s, opacity .15s;
}
.corp-burger .bars::before { top: 0; }
.corp-burger .bars span    { top: 7px; }
.corp-burger .bars::after  { top: 14px; }
body.corp-menu-open .corp-burger .bars::before { top:7px; transform: rotate(45deg); }
body.corp-menu-open .corp-burger .bars span    { opacity: 0; }
body.corp-menu-open .corp-burger .bars::after  { top:7px; transform: rotate(-45deg); }

@media (max-width: 880px) {
  .corp-burger { display: inline-flex; }
  .corp-topbar nav.corp-nav {
    position:absolute; top:100%; left:0; right:0; background: var(--bg);
    flex-direction: column; align-items: stretch; gap: 0;
    max-height: 0; overflow: hidden; transition: max-height .25s ease;
    box-shadow: 0 8px 18px rgba(0,0,0,.25);
  }
  .corp-topbar nav.corp-nav a { padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.06); }
  .corp-topbar nav.corp-nav a.cta { margin: 10px 16px; border-radius: 8px; text-align:center; }
  body.corp-menu-open .corp-topbar nav.corp-nav { max-height: 520px; }
  body.corp-menu-open { overflow: hidden; }
}

/* ---------- Buttons ---------- */
.btn {
  display:inline-flex; align-items:center; justify-content:center; gap:6px;
  padding: 11px 22px; border-radius:8px; font-weight:600; text-decoration:none;
  border:0; cursor:pointer; font: inherit; min-height: 44px;
}
.btn.primary { background: var(--accent); color:#111; }
.btn.primary:hover { background: var(--accent-2); }
.btn.dark    { background: var(--bg); color:#fff; }
.btn.dark:hover { background: var(--bg2); }
.btn.outline { background: transparent; border:1px solid #d1d5db; color: var(--ink); }
.btn.outline-light { background: transparent; border:1px solid #334155; color:#fff; }
.btn-row { display:flex; flex-wrap:wrap; gap: 10px; }

/* ---------- Sections ---------- */
section.corp { padding: clamp(48px, 8vw, 84px) 0; }
section.corp.alt { background: var(--bg-soft); border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
section.corp.dark {
  background: linear-gradient(135deg, var(--bg), #000); color:#fff;
}
section.corp.dark h2, section.corp.dark p { color:#fff; }
section.corp h2 { font-size: clamp(26px, 3.6vw, 36px); margin: 0 0 10px; line-height: 1.15; }
section.corp .lead { color: var(--ink-2); max-width: 720px; font-size: clamp(15px,2vw,17px); margin: 0; }
section.corp.dark .lead { color: var(--soft); }

/* Hero */
.corp-hero {
  background:
    radial-gradient(900px 500px at -10% 110%, rgba(245,158,11,.18), transparent 60%),
    radial-gradient(700px 500px at 110% -10%, rgba(99,102,241,.18), transparent 60%),
    linear-gradient(135deg, var(--bg), #000);
  color:#fff; padding: clamp(60px,10vw,100px) 0;
}
.corp-hero .tag {
  display:inline-block; padding:4px 10px; border-radius:999px;
  background: rgba(245,158,11,.15); color: var(--accent);
  font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
  margin-bottom: 14px;
}
.corp-hero h1 { font-size: clamp(34px, 6vw, 54px); margin: 0 0 16px; line-height: 1.1; max-width: 820px; }
.corp-hero p  { color: var(--soft); font-size: clamp(16px,2.2vw,20px); max-width: 720px; margin: 0; }

/* Section CTAs */
.dark-cta { background: linear-gradient(135deg, var(--bg), #1f2937); color:#fff; padding: clamp(40px,6vw,56px) 0; }
.dark-cta h2 { color:#fff; }
.dark-cta .lead { color: var(--soft); }

.alert {
  padding:11px 14px; border-radius:8px; margin: 10px 0;
  font-size: 14px; display: flex; gap: 8px; align-items: flex-start;
}
.alert.success { background:#dcfce7; color:#166534; }
.alert.error   { background:#fee2e2; color:#991b1b; }

.muted { color:#6b7280; font-size: 13px; }

/* ---------- Footer ---------- */
.corp-footer {
  background: #0b1020; color: #cbd5e1;
  padding: 36px 0 18px; font-size:14px; margin-top: 48px;
}
.corp-footer a { color:#fff; text-decoration:none; }
.corp-footer .cols { display:grid; gap: 24px; grid-template-columns: 1fr; }
@media (min-width: 720px) { .corp-footer .cols { grid-template-columns: 1.4fr 1fr 1fr 1fr; } }
.corp-footer h4 { margin: 0 0 10px; color:#fff; font-size: 13px; text-transform: uppercase; letter-spacing: .06em; }
.corp-footer .links a { display:block; padding: 4px 0; color:#cbd5e1; }
.corp-footer .links a:hover { color:#fff; }
.corp-footer .copy {
  margin-top: 26px; padding-top: 16px; border-top: 1px solid #1f2937;
  opacity:.6; font-size: 12px; display:flex; justify-content:space-between; flex-wrap: wrap; gap: 8px;
}
</style>
</head>
<body>

<header class="corp-topbar">
  <div class="container row">
    <a class="brand" href="/">
      <span class="dot">A</span> <span>AICAP <span style="color:#94a3b8;font-weight:500;">Furniture BOS</span></span>
    </a>
    <button type="button" class="corp-burger" id="corp-burger" aria-label="Toggle menu" aria-expanded="false">
      <span class="bars"><span></span></span>
    </button>
    <nav class="corp-nav" id="corp-nav">
      <a href="/"               <?= $page_id === 'home'      ? 'class="active"' : '' ?>>Home</a>
      <a href="/features.php"   <?= $page_id === 'features'  ? 'class="active"' : '' ?>>Features</a>
      <a href="/consulting.php" <?= $page_id === 'consulting'? 'class="active"' : '' ?>>Consulting</a>
      <a href="/how-it-works.php" <?= $page_id === 'how'      ? 'class="active"' : '' ?>>How it works</a>
      <a href="/pricing.php"    <?= $page_id === 'pricing'   ? 'class="active"' : '' ?>>Pricing</a>
      <a href="/licensing.php"  <?= $page_id === 'licensing' ? 'class="active"' : '' ?>>Licensing</a>
      <a href="/blog.php"       <?= $page_id === 'blog'      ? 'class="active"' : '' ?>>Blog</a>
      <a href="/about.php"      <?= $page_id === 'about'     ? 'class="active"' : '' ?>>About</a>
      <a href="/contact.php" class="cta <?= $page_id === 'contact' ? 'active' : '' ?>">Contact</a>
    </nav>
  </div>
</header>

<script>
(function () {
  var btn = document.getElementById('corp-burger');
  if (!btn) return;
  function toggle () {
    var open = document.body.classList.toggle('corp-menu-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  btn.addEventListener('click', toggle);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.body.classList.remove('corp-menu-open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>
