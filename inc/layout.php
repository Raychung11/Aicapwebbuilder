<?php
/**
 * Shared public-site layout. Expects $company (array) in scope.
 * Usage:
 *   $page_title = 'Catalog';
 *   require __DIR__ . '/inc/layout.php';   // calls layout_head($company)
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?></title>
<meta name="description" content="<?= e($company['description'] ?? '') ?>">
<style>
:root { --c-primary: <?= $primary ?>; --c-secondary: <?= $secondary ?>; }
* { box-sizing: border-box; }
body { margin:0; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#111; background:#fafafa; }
a { color: var(--c-primary); }
.container { max-width: 1100px; margin: 0 auto; padding: 0 16px; }
header.site { background: var(--c-primary); color:#fff; padding: 14px 0; }
header.site .row { display:flex; align-items:center; justify-content:space-between; gap:16px; }
header.site a { color:#fff; text-decoration:none; }
header.site .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:18px; }
header.site .brand img { height: 32px; width:auto; background:#fff; padding:2px; border-radius:4px; }
nav.site a { margin-left: 14px; font-size: 14px; opacity: 0.9; }
nav.site a:hover { opacity: 1; }
.btn { display:inline-block; padding: 10px 16px; border-radius: 6px; font-weight:600; text-decoration:none; border:0; cursor:pointer; }
.btn.primary { background: var(--c-secondary); color:#111; }
.btn.outline { background: transparent; border:1px solid #ddd; color:#111; }
.hero { padding: 60px 0; background: linear-gradient(135deg, var(--c-primary), #000); color:#fff; }
.hero h1 { font-size: 36px; margin: 0 0 12px; }
.grid { display:grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
.card { background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.card .img { aspect-ratio: 4/3; background:#eee; display:flex; align-items:center; justify-content:center; color:#aaa; }
.card .img img { width:100%; height:100%; object-fit:cover; }
.card .pad { padding: 12px; }
.card h3 { margin:0 0 6px; font-size:15px; }
.card .price { color: var(--c-primary); font-weight:700; }
section { padding: 32px 0; }
footer.site { background:#111; color:#bbb; padding: 28px 0; margin-top: 40px; font-size:14px; }
footer.site a { color:#fff; }
.input, select.input, textarea.input { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font: inherit; }
label { font-size: 13px; color:#444; display:block; margin: 10px 0 4px; }
.alert { padding:10px 12px; border-radius:6px; margin: 10px 0; }
.alert.error { background:#fee; color:#a00; }
.alert.success { background:#efe; color:#070; }
.box { background:#fff; padding:18px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.muted { color:#666; font-size: 13px; }
.center { text-align: center; }
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
    ?>
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
