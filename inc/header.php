<?php
/**
 * Public-site header.
 *
 * Expects in scope:
 *   $company     array  current company row (required)
 *   $page_title  string optional page title for <title>
 *   $page_id     string optional id used to highlight section nav (e.g. "home")
 */
require_once __DIR__ . '/helpers.php';

$page_title = $page_title ?? '';
$page_id    = $page_id    ?? '';
$page_meta  = $page_meta  ?? [];   // optional: title, description, image, type

// Resolve final SEO values with sensible fallbacks.
$_company_meta_title = $company['meta_title']       ?? '';
$_company_meta_desc  = $company['meta_description'] ?? ($company['description'] ?? '');
$_og_image_default   = $company['og_image'] ?: ($company['logo'] ?? '');

$_seo_title = $page_meta['title']
    ?? ($page_title ? $page_title . ' | ' . $company['name']
                    : ($_company_meta_title ?: $company['name']));
$_seo_desc  = $page_meta['description'] ?? $_company_meta_desc;
$_seo_image = $page_meta['image']       ?? $_og_image_default;
$_seo_type  = $page_meta['type']        ?? 'website';

// Absolute URLs for Open Graph
$_scheme    = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
$_host      = $_SERVER['HTTP_HOST'] ?? '';
$_seo_url   = $_scheme . '://' . $_host . ($_SERVER['REQUEST_URI'] ?? '/');
if ($_seo_image && !preg_match('#^https?://#', $_seo_image)) {
    $_seo_image = $_scheme . '://' . $_host . $_seo_image;
}

$_primary   = e($company['theme_color']           ?: '#111827');
$_secondary = e($company['theme_secondary_color'] ?: '#f59e0b');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?= $_primary ?>">
<title><?= e($_seo_title) ?></title>
<meta name="description" content="<?= e($_seo_desc) ?>">
<link rel="canonical" href="<?= e($_seo_url) ?>">

<!-- Open Graph -->
<meta property="og:site_name" content="<?= e($company['name']) ?>">
<meta property="og:title" content="<?= e($_seo_title) ?>">
<meta property="og:description" content="<?= e($_seo_desc) ?>">
<meta property="og:url" content="<?= e($_seo_url) ?>">
<meta property="og:type" content="<?= e($_seo_type) ?>">
<?php if ($_seo_image): ?>
<meta property="og:image" content="<?= e($_seo_image) ?>">
<?php endif; ?>

<!-- Twitter -->
<meta name="twitter:card" content="<?= $_seo_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($_seo_title) ?>">
<meta name="twitter:description" content="<?= e($_seo_desc) ?>">
<?php if ($_seo_image): ?><meta name="twitter:image" content="<?= e($_seo_image) ?>"><?php endif; ?>
<style>
:root { --c-primary: <?= $_primary ?>; --c-secondary: <?= $_secondary ?>; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
html { scroll-behavior: smooth; scroll-padding-top: 120px; }
body {
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  color:#111; background:#fafafa; -webkit-font-smoothing: antialiased;
  font-size: 15px; line-height: 1.5;
}
img { max-width: 100%; height: auto; display: block; }
a { color: var(--c-primary); }
.container { max-width: 1100px; margin: 0 auto; padding: 0 16px; }

/* ---------- Header / nav ---------- */
header.site { background: var(--c-primary); color:#fff; padding: 12px 0; position: sticky; top:0; z-index: 50; }
header.site .row { display:flex; align-items:center; justify-content:space-between; gap: 12px; }
header.site a { color:#fff; text-decoration:none; }
header.site .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:17px; min-width:0; }
header.site .brand img { height: 32px; width:auto; background:#fff; padding:3px; border-radius:5px; flex: 0 0 auto; }
header.site .brand span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
nav.site { display:flex; align-items:center; gap:18px; }
nav.site a { font-size: 14px; opacity: 0.92; }
nav.site a:hover { opacity: 1; }
nav.site a.active { opacity: 1; box-shadow: inset 0 -2px 0 var(--c-secondary); }

/* Hamburger button — animated 3-bars → X */
.nav-toggle {
  display: none; background: transparent; border: 0; padding: 8px; cursor: pointer;
  border-radius: 8px; transition: background .15s;
}
.nav-toggle:hover { background: rgba(255,255,255,.1); }
.nav-toggle .bars { width: 22px; height: 16px; position: relative; display:block; }
.nav-toggle .bars::before, .nav-toggle .bars::after, .nav-toggle .bars span {
  content: ''; position: absolute; left: 0; right: 0; height: 2px; background: #fff;
  border-radius: 2px;
  transition: top .25s ease, transform .25s ease, opacity .15s ease;
}
.nav-toggle .bars::before { top: 0; }
.nav-toggle .bars span    { top: 7px; }
.nav-toggle .bars::after  { top: 14px; }
body.menu-open .nav-toggle .bars::before { top: 7px; transform: rotate(45deg); }
body.menu-open .nav-toggle .bars span    { opacity: 0; }
body.menu-open .nav-toggle .bars::after  { top: 7px; transform: rotate(-45deg); }

/* Backdrop for mobile menu */
.nav-backdrop {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4);
  opacity: 0; pointer-events: none; transition: opacity .25s; z-index: 40;
}
@media (max-width: 720px) {
  .nav-toggle { display: inline-flex; align-items:center; }
  nav.site {
    position: absolute; top: 100%; right: 0; left: 0;
    background: var(--c-primary);
    flex-direction: column; align-items: stretch; gap: 0;
    max-height: 0; overflow: hidden; transition: max-height .25s ease;
    box-shadow: 0 8px 18px rgba(0,0,0,.15);
  }
  nav.site a { padding: 14px 18px; border-top: 1px solid rgba(255,255,255,.08); }
  body.menu-open nav.site { max-height: 420px; }
  body.menu-open { overflow: hidden; }
  body.menu-open .nav-backdrop { display: block; opacity: 1; pointer-events: auto; }
}

/* ---------- Section index (landing) ---------- */
.section-index {
  position: sticky; top: 56px; z-index: 40;
  background: #fff; border-bottom: 1px solid #e5e7eb;
  overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch;
}
.section-index .container { padding: 8px 16px; }
.section-index a {
  display: inline-block; padding: 8px 14px; margin-right: 4px;
  border-radius: 999px; font-size: 14px; font-weight: 500;
  color: #374151; text-decoration: none; border: 1px solid transparent;
}
.section-index a:hover { background: #f3f4f6; }
.section-index a.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }

/* ---------- Buttons ---------- */
.btn {
  display:inline-flex; align-items:center; justify-content:center; gap: 6px;
  padding: 11px 18px; border-radius: 8px; font-weight:600;
  text-decoration:none; border:0; cursor:pointer; font: inherit;
  min-height: 44px; line-height: 1.2;
}
.btn.primary { background: var(--c-secondary); color:#111; }
.btn.dark    { background: #111; color:#fff; }
.btn.outline { background: transparent; border:1px solid #d1d5db; color:#111; }
.btn.outline-light { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.5); color:#fff; }
.btn.block   { width: 100%; }
.btn-row { display:flex; flex-wrap:wrap; gap:8px; }

/* ---------- Hero ---------- */
.hero {
  padding: clamp(40px, 8vw, 80px) 0;
  background: linear-gradient(135deg, var(--c-primary), #000);
  color:#fff;
  position: relative;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
.hero.has-bg { padding: clamp(80px, 14vw, 140px) 0; }
.hero.has-bg::before {
  content: ""; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(15,23,42,.65), rgba(0,0,0,.45));
  pointer-events: none;
}
.hero .container { position: relative; z-index: 1; }
.hero h1 { font-size: clamp(28px, 5.5vw, 44px); margin: 0 0 12px; line-height: 1.15;
  text-shadow: 0 2px 12px rgba(0,0,0,.25); }
.hero p  { font-size: clamp(15px, 2.2vw, 18px); opacity: .95; max-width: 640px;
  text-shadow: 0 1px 8px rgba(0,0,0,.25); }

/* ---------- Sections / cards ---------- */
section { padding: clamp(28px, 5vw, 48px) 0; scroll-margin-top: 120px; }
section h2 { font-size: clamp(20px, 3vw, 26px); margin: 0 0 16px; }

.grid { display:grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
@media (min-width: 560px) { .grid { gap: 16px; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); } }
@media (min-width: 900px) { .grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); } }

.card { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.card .img { aspect-ratio: 4/3; background:#eee; display:flex; align-items:center; justify-content:center; color:#aaa; }
.card .img img { width:100%; height:100%; object-fit:cover; }
.card .pad { padding: 12px; }
.card h3 { margin:0 0 6px; font-size:15px; line-height: 1.25; }
.card .price { color: var(--c-primary); font-weight:700; }

/* ---------- Forms ---------- */
.input, select.input, textarea.input {
  width:100%; padding:12px; border:1px solid #d1d5db; border-radius:8px;
  font: inherit; background:#fff;
}
.input:focus { outline: 2px solid var(--c-primary); outline-offset: 1px; border-color: var(--c-primary); }

.phone-field {
  display:flex; align-items:stretch; border:1px solid #d1d5db; border-radius:8px;
  background:#fff; overflow:hidden;
}
.phone-field:focus-within { outline: 2px solid var(--c-primary); outline-offset: 1px; border-color: var(--c-primary); }
.phone-field .prefix {
  padding: 12px 14px; background:#f3f4f6; color:#374151; font-weight:600;
  border-right:1px solid #e5e7eb; display:flex; align-items:center;
  font-variant-numeric: tabular-nums;
}
.phone-field input {
  flex:1; min-width:0; border:0; padding:12px; background:transparent; font: inherit;
}
.phone-field input:focus { outline: 0; }
label { font-size: 13px; color:#444; display:block; margin: 12px 0 4px; font-weight: 500; }
.alert { padding:11px 14px; border-radius:8px; margin: 12px 0; font-size: 14px; }
.alert.error   { background:#fee; color:#a00; }
.alert.success { background:#efe; color:#070; }

.box { background:#fff; padding:18px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.muted { color:#666; font-size: 13px; }
.center { text-align: center; }
.chip { display:inline-block; padding:6px 12px; border-radius:999px; background:#fff; border:1px solid #d1d5db; font-size:13px; text-decoration:none; color:#111; margin: 0 4px 6px 0; }
.chip.active { background: var(--c-primary); color:#fff; border-color: var(--c-primary); }

/* ---------- 2-col layout (used on product detail) ---------- */
.split { display: grid; gap: 24px; grid-template-columns: 1fr; }
@media (min-width: 760px) { .split { grid-template-columns: 1fr 1fr; } }

/* ---------- Map / branch card ---------- */
.branch { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); display:flex; flex-direction:column; }
.branch .map { aspect-ratio: 16/10; background:#eee; }
.branch .map iframe { width:100%; height:100%; border:0; display:block; }
.branch .meta { padding: 14px; flex: 1; display:flex; flex-direction:column; gap:6px; }
.branch .meta h3 { margin: 0 0 4px; font-size:17px; }
.branch .actions { display:flex; flex-wrap:wrap; gap:8px; padding: 0 14px 14px; }

/* ---------- Footer ---------- */
footer.site { background:#111; color:#bbb; padding: 28px 0; margin-top: 40px; font-size:14px; }
footer.site a { color:#fff; text-decoration:none; }
footer.site a:hover { text-decoration:underline; }
footer.site .cols { display:grid; gap:18px; grid-template-columns: 1fr; }
@media (min-width: 600px) { footer.site .cols { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 900px) { footer.site .cols { grid-template-columns: 1.6fr 1fr 1fr 1fr; } }
footer.site h4 { margin:0 0 10px; color:#fff; font-size:14px; text-transform: uppercase; letter-spacing:.04em; }
footer.site .links a { display:block; padding: 4px 0; color:#cbd5e1; }
footer.site .copy { margin-top:24px; padding-top:14px; opacity:.6; font-size:12px; }

/* ---------- Floating WhatsApp ---------- */
.fab-whatsapp {
  position: fixed; bottom: 16px; right: 16px; z-index: 60;
  background: #25d366; color: #fff; border-radius: 999px;
  width: 56px; height: 56px; display:flex; align-items:center; justify-content:center;
  box-shadow: 0 6px 18px rgba(0,0,0,.25); text-decoration: none; font-size: 28px;
}
.fab-whatsapp:hover { background: #1ebe5b; }
</style>
</head>
<body data-page="<?= e($page_id) ?>">
<?php if (function_exists('is_preview_mode') && is_preview_mode()): ?>
<div style="background:#fef3c7;color:#78350f;padding:8px 14px;font-size:13px;text-align:center;">
  Preview mode — viewing <strong><?= e($company['name']) ?></strong>.
  <a href="?exit_preview=1" style="color:#7c2d12;text-decoration:underline;font-weight:600;margin-left:8px;">Exit preview</a>
</div>
<?php endif; ?>
<header class="site">
  <div class="container row">
    <a class="brand" href="/">
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= e($company['logo']) ?>" alt="<?= e($company['name']) ?>">
      <?php endif; ?>
      <span><?= e($company['name']) ?></span>
    </a>
    <button type="button" class="nav-toggle" id="nav-toggle"
            aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">
      <span class="bars"><span></span></span>
    </button>
    <nav class="site" id="site-nav">
      <a href="/" <?= $page_id === 'home' ? 'class="active"' : '' ?>>Home</a>
      <a href="/catalog.php" <?= $page_id === 'catalog' ? 'class="active"' : '' ?>>Catalog</a>
      <a href="/packages.php" <?= $page_id === 'packages' ? 'class="active"' : '' ?>>Packages</a>
      <a href="/voucher.php" <?= $page_id === 'voucher' ? 'class="active"' : '' ?>>Vouchers</a>
      <a href="/visit.php" <?= $page_id === 'visit' ? 'class="active"' : '' ?>>Visit Us</a>
      <?php if (current_member()): ?>
        <a href="/member-dashboard.php">My Account</a>
      <?php else: ?>
        <a href="/member-login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<div class="nav-backdrop" id="nav-backdrop"></div>
<script>
(function () {
  var btn = document.getElementById('nav-toggle');
  var bd  = document.getElementById('nav-backdrop');
  if (!btn) return;
  function close () { document.body.classList.remove('menu-open'); btn.setAttribute('aria-expanded','false'); }
  function open  () { document.body.classList.add('menu-open');    btn.setAttribute('aria-expanded','true'); }
  btn.addEventListener('click', function () {
    document.body.classList.contains('menu-open') ? close() : open();
  });
  if (bd) bd.addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
