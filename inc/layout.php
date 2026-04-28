<?php
/**
 * Shared public-site layout. Expects $company (array) in scope.
 * Usage:
 *   $page_title = 'Catalog';
 *   require __DIR__ . '/inc/layout.php';
 *   layout_head($company, 'Catalog');
 *   ... HTML body ...
 *   layout_foot($company);
 */
require_once __DIR__ . '/helpers.php';

function layout_head(array $company, string $page_title = ''): void {
    $title = $page_title
        ? e($page_title) . ' | ' . e($company['name'])
        : e($company['name']);
    $primary   = e($company['theme_color'] ?: '#111827');
    $secondary = e($company['theme_secondary_color'] ?: '#f59e0b');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?= $primary ?>">
<title><?= $title ?></title>
<meta name="description" content="<?= e($company['description'] ?? '') ?>">
<style>
:root { --c-primary: <?= $primary ?>; --c-secondary: <?= $secondary ?>; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
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
header.site .brand img { height: 30px; width:auto; background:#fff; padding:2px; border-radius:4px; flex: 0 0 auto; }
header.site .brand span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
nav.site { display:flex; align-items:center; gap:18px; }
nav.site a { font-size: 14px; opacity: 0.92; }
nav.site a:hover { opacity: 1; }
.nav-toggle { display: none; background: none; border: 0; color: #fff; padding: 6px 10px; font-size: 22px; cursor: pointer; line-height: 1; }
@media (max-width: 720px) {
  .nav-toggle { display: block; }
  nav.site {
    position: absolute; top: 100%; right: 0; left: 0;
    background: var(--c-primary);
    flex-direction: column; align-items: stretch; gap: 0;
    max-height: 0; overflow: hidden; transition: max-height .25s ease;
    box-shadow: 0 8px 18px rgba(0,0,0,.15);
  }
  nav.site a { padding: 14px 18px; border-top: 1px solid rgba(255,255,255,.08); }
  nav.site.open { max-height: 360px; }
}

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
}
.hero h1 { font-size: clamp(28px, 5.5vw, 44px); margin: 0 0 12px; line-height: 1.15; }
.hero p  { font-size: clamp(15px, 2.2vw, 18px); opacity: .92; max-width: 640px; }

/* ---------- Sections / cards ---------- */
section { padding: clamp(28px, 5vw, 48px) 0; }
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
footer.site a { color:#fff; }

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
<body>
<header class="site">
  <div class="container row">
    <a class="brand" href="/">
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= e($company['logo']) ?>" alt="<?= e($company['name']) ?>">
      <?php endif; ?>
      <span><?= e($company['name']) ?></span>
    </a>
    <button class="nav-toggle" aria-label="Open menu" onclick="this.nextElementSibling.classList.toggle('open')">&#9776;</button>
    <nav class="site">
      <a href="/">Home</a>
      <a href="/catalog.php">Catalog</a>
      <a href="/voucher.php">Vouchers</a>
      <?php if (current_member()): ?>
        <a href="/member-dashboard.php">My Account</a>
      <?php else: ?>
        <a href="/member-login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php
}

function layout_foot(array $company): void {
    $wa = $company['whatsapp_number'] ?? '';
    ?>
<?php if ($wa): ?>
<a class="fab-whatsapp" target="_blank" rel="noopener"
   href="<?= e(whatsapp_link($wa, 'Hi, I\'d like to know more.')) ?>"
   aria-label="Chat on WhatsApp">&#128172;</a>
<?php endif; ?>
<footer class="site">
  <div class="container">
    <div><strong><?= e($company['name']) ?></strong></div>
    <?php if (!empty($company['address'])): ?><div><?= e($company['address']) ?></div><?php endif; ?>
    <?php if (!empty($company['phone'])): ?><div>Tel: <?= e($company['phone']) ?></div><?php endif; ?>
    <?php if (!empty($company['email'])): ?><div>Email: <?= e($company['email']) ?></div><?php endif; ?>
    <div style="margin-top:14px;opacity:.6;">Powered by <?= e(APP_NAME) ?></div>
  </div>
</footer>
</body></html>
    <?php
}
