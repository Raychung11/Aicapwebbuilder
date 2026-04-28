<?php
/**
 * Shared admin layout for both Super Admin (/admin) and
 * Company Admin (/company-admin). Pass the menu items in.
 */
require_once __DIR__ . '/helpers.php';

function admin_head(string $title, string $base, array $menu, string $user_label, string $logout_url): void {
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | Admin</title>
<style>
* { box-sizing: border-box; }
body { margin:0; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background:#f3f4f6; color:#111; }
.layout { display:grid; grid-template-columns: 230px 1fr; min-height:100vh; }
aside { background:#111827; color:#e5e7eb; padding: 18px 0; }
aside .brand { padding: 0 18px 14px; font-weight:700; font-size:16px; border-bottom:1px solid #1f2937; }
aside nav a { display:block; padding: 10px 18px; color:#cbd5e1; text-decoration:none; font-size:14px; }
aside nav a:hover { background:#1f2937; color:#fff; }
aside nav a.active { background:#2563eb; color:#fff; }
main { padding: 22px 28px; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px; }
.topbar h1 { margin:0; font-size:20px; }
.topbar .user { font-size:13px; color:#555; }
.topbar .user a { margin-left:10px; color:#2563eb; text-decoration:none; }
.card { background:#fff; padding:18px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom: 16px; }
table { width:100%; border-collapse: collapse; }
th, td { text-align:left; padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 14px; }
th { background:#f9fafb; font-weight:600; }
.input, select.input, textarea.input { width:100%; padding:9px; border:1px solid #d1d5db; border-radius:6px; font: inherit; }
label { font-size:13px; color:#444; display:block; margin: 10px 0 4px; }
.btn { display:inline-block; padding: 9px 14px; border-radius:6px; border:0; cursor:pointer; font-weight:600; text-decoration:none; font: inherit; }
.btn.primary { background:#2563eb; color:#fff; }
.btn.danger  { background:#dc2626; color:#fff; }
.btn.outline { background:#fff; border:1px solid #d1d5db; color:#111; }
.row { display:flex; gap:16px; flex-wrap: wrap; }
.row > .col { flex: 1 1 240px; }
.alert { padding:10px 12px; border-radius:6px; margin: 10px 0; }
.alert.error { background:#fee2e2; color:#991b1b; }
.alert.success { background:#dcfce7; color:#166534; }
.kpi { display:grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap:12px; margin-bottom: 18px; }
.kpi .box { background:#fff; padding:14px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.kpi .box .v { font-size: 24px; font-weight: 700; }
.kpi .box .l { font-size: 12px; color:#6b7280; text-transform: uppercase; letter-spacing: .04em; }
.muted { color:#6b7280; font-size:13px; }
.actions a, .actions button { margin-right:6px; }
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; background:#e5e7eb; color:#111; }
.badge.green { background:#dcfce7; color:#166534; }
.badge.red   { background:#fee2e2; color:#991b1b; }
@media (max-width: 700px) {
  .layout { grid-template-columns: 1fr; }
  aside { display:flex; flex-wrap: wrap; padding: 8px; }
  aside .brand { width:100%; border:0; padding: 6px 12px; }
  aside nav { display:flex; flex-wrap:wrap; }
  aside nav a { padding: 6px 10px; }
}
</style>
</head>
<body>
<div class="layout">
  <aside>
    <div class="brand"><?= e($title) ?></div>
    <nav>
      <?php
      $self = basename($_SERVER['SCRIPT_NAME']);
      foreach ($menu as $item) {
          $active = $item['file'] === $self ? ' active' : '';
          echo '<a class="' . trim($active) . '" href="' . e($base . '/' . $item['file']) . '">'
              . e($item['label']) . '</a>';
      }
      ?>
    </nav>
  </aside>
  <main>
    <div class="topbar">
      <h1><?= e($title) ?></h1>
      <div class="user">
        Hi, <strong><?= e($user_label) ?></strong>
        <a href="<?= e($logout_url) ?>">Logout</a>
      </div>
    </div>
    <?php if ($m = flash_pop('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash_pop('error')):   ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
<?php
}

function admin_foot(): void {
    ?>
  </main>
</div>
</body></html>
    <?php
}
