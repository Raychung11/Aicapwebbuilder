<?php
/**
 * Admin chrome — header.
 *
 * Expects in scope:
 *   $title       string  page title
 *   $base        string  base URL for sidebar links (/admin or /company-admin)
 *   $menu        array   list of ['file' => '…', 'label' => '…']
 *   $user_label  string  display name for the top-bar user pill
 *   $logout_url  string  logout link
 *   $brand       string  optional brand label (defaults to APP_NAME or company)
 *   $brand_sub   string  optional subtitle under brand
 */
require_once __DIR__ . '/helpers.php';

$brand     = $brand     ?? APP_NAME;
$brand_sub = $brand_sub ?? ($base === '/admin' ? 'Super Admin' : 'Tenant Admin');
$accent    = $accent    ?? ($base === '/admin' ? '#f59e0b' : '#2563eb');
$self      = basename($_SERVER['SCRIPT_NAME'] ?? '');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0f172a">
<title><?= e($title) ?> &middot; AICAP Admin</title>
<style>
:root { --side-w: 240px; --top-h: 56px; --accent: <?= e($accent) ?>; --bg-dark:#0f172a; --bg-deep:#0b1020; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body {
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  background:#f3f4f6; color:#111; -webkit-font-smoothing: antialiased;
  font-size: 14px; line-height: 1.5; min-height: 100vh;
}
a { color: #2563eb; text-decoration: none; }

/* ---------- Top bar ---------- */
.topbar {
  position: fixed; top: 0; left: 0; right: 0; height: var(--top-h);
  background: var(--bg-dark); color:#fff; z-index: 60;
  display:flex; align-items:center; gap: 12px; padding: 0 16px;
  box-shadow: 0 1px 0 rgba(0,0,0,.2);
}
.topbar .brand { display:flex; align-items:center; gap: 10px; min-width: 0; }
.topbar .brand .dot { width: 26px; height: 26px; border-radius: 7px; background: var(--accent);
  display:flex; align-items:center; justify-content:center; color:#0f172a; font-weight: 800; font-size: 13px; }
.topbar .brand strong { font-size: 15px; line-height: 1.1; color:#fff; }
.topbar .brand .sub { font-size: 11px; color:#94a3b8; text-transform: uppercase; letter-spacing: .06em; line-height:1.2; }
.topbar .spacer { flex: 1; }

/* Hamburger */
.hamburger {
  display: none; background: transparent; border: 0; padding: 8px; cursor: pointer;
  border-radius: 8px;
}
.hamburger:hover { background: rgba(255,255,255,.08); }
.hamburger .bars { width: 22px; height: 16px; position: relative; display: block; }
.hamburger .bars::before, .hamburger .bars::after, .hamburger .bars span {
  content: ''; position: absolute; left: 0; right: 0; height: 2px; background: #fff; border-radius: 2px;
  transition: transform .25s ease, top .25s ease, opacity .15s ease;
}
.hamburger .bars::before { top: 0; }
.hamburger .bars span    { top: 7px; }
.hamburger .bars::after  { top: 14px; }
body.sb-open .hamburger .bars::before { top: 7px; transform: rotate(45deg); }
body.sb-open .hamburger .bars span    { opacity: 0; }
body.sb-open .hamburger .bars::after  { top: 7px; transform: rotate(-45deg); }

/* User pill */
.user {
  display:flex; align-items:center; gap: 10px;
  padding: 6px 10px 6px 6px; border-radius: 999px;
  background: rgba(255,255,255,.08); color:#fff; font-size: 13px;
  border: 1px solid rgba(255,255,255,.12);
}
.user .av {
  width: 28px; height: 28px; border-radius: 50%; background: var(--accent); color:#0f172a;
  display:flex; align-items:center; justify-content:center; font-weight: 700; font-size: 12px;
}
.user .nm { color:#e5e7eb; font-weight: 500; max-width: 140px; white-space: nowrap; overflow:hidden; text-overflow: ellipsis; }
.user a.logout { color:#94a3b8; padding-left: 6px; border-left: 1px solid rgba(255,255,255,.1); }
.user a.logout:hover { color:#fff; }

/* ---------- Layout ---------- */
.layout {
  padding-top: var(--top-h);
  min-height: 100vh;
}
main.main {
  margin-left: var(--side-w);
  padding: 22px 26px 80px;
  min-height: calc(100vh - var(--top-h));
  min-width: 0;
}
@media (max-width: 900px) {
  main.main { margin-left: 0; padding: 18px 16px 80px; }
}

/* ---------- Sidebar ---------- */
aside.sidebar {
  background: var(--bg-deep); color:#cbd5e1;
  position: fixed; top: var(--top-h); bottom: 0; left: 0; width: var(--side-w);
  padding: 14px 10px; overflow-y: auto;
  border-right: 1px solid rgba(255,255,255,.05);
}
aside.sidebar .group { color:#64748b; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; margin: 6px 12px 4px; }
aside.sidebar a {
  display:flex; align-items:center; gap: 10px;
  padding: 9px 12px; border-radius: 8px; font-size: 13.5px;
  color:#cbd5e1; text-decoration:none; transition: background .15s, color .15s;
}
aside.sidebar a:hover { background: rgba(255,255,255,.05); color:#fff; }
aside.sidebar a.active {
  background: rgba(245,158,11,.12); color:#fff;
  box-shadow: inset 3px 0 0 var(--accent);
}
aside.sidebar a .ic { width: 18px; flex-shrink: 0; text-align:center; }

aside.sidebar .footer {
  margin-top: 20px; padding: 14px 12px; border-top: 1px solid rgba(255,255,255,.06);
  font-size: 12px; color:#64748b;
}
aside.sidebar .footer a { color:#94a3b8; padding: 0; display: inline; }
aside.sidebar .footer a:hover { color:#fff; background:none; }

/* Mobile drawer */
.backdrop { display: none; }
@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }
  .hamburger { display: inline-flex; }
  aside.sidebar {
    transform: translateX(-100%);
    transition: transform .25s ease;
    box-shadow: 4px 0 18px rgba(0,0,0,.25);
    z-index: 80;
  }
  body.sb-open aside.sidebar { transform: translateX(0); }
  .backdrop {
    display: block; position: fixed; top: var(--top-h); left:0; right:0; bottom:0;
    background: rgba(0,0,0,.45);
    opacity: 0; pointer-events: none; transition: opacity .25s; z-index: 70;
  }
  body.sb-open .backdrop { opacity: 1; pointer-events: auto; }
  body.sb-open { overflow: hidden; }
  /* When sidebar is closed, hide its links from screen readers / tab order */
  aside.sidebar:not(.is-visible) { /* CSS-only fallback */ }
}

/* ---------- Main ---------- */
main.main .pageHead { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap: 12px; margin-bottom: 16px; }
main.main h1.page-title { margin: 0; font-size: 22px; }
main.main .crumb { color:#6b7280; font-size: 12px; }

/* Existing content classes stay compatible */
.card { background:#fff; padding:18px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom: 16px; }
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

/* ---------- Footer ---------- */
.adm-footer {
  position: fixed; left: var(--side-w); right: 0; bottom: 0;
  background:#fff; border-top: 1px solid #e5e7eb;
  padding: 8px 22px; font-size: 12px; color:#6b7280;
  display:flex; justify-content:space-between; gap: 12px; align-items:center;
  z-index: 40;
}
.adm-footer a { color:#374151; text-decoration:none; margin-left: 12px; }
.adm-footer a:hover { color:#111; }
@media (max-width: 900px) { .adm-footer { left: 0; } }
</style>
</head>
<body>

<!-- Top bar -->
<header class="topbar">
  <button type="button" class="hamburger" id="sb-toggle" aria-label="Open menu" aria-expanded="false">
    <span class="bars"><span></span></span>
  </button>
  <a class="brand" href="<?= e($base) ?>/index.php" style="text-decoration:none;">
    <span class="dot">A</span>
    <span>
      <strong><?= e($brand) ?></strong>
      <div class="sub"><?= e($brand_sub) ?></div>
    </span>
  </a>
  <span class="spacer"></span>
  <span class="user">
    <span class="av"><?= e(strtoupper(substr($user_label, 0, 1))) ?></span>
    <span class="nm"><?= e($user_label) ?></span>
    <a class="logout" href="<?= e($logout_url) ?>" title="Sign out">↪</a>
  </span>
</header>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="group">Navigate</div>
    <?php foreach ($menu as $item): ?>
      <a class="<?= $item['file'] === $self ? 'active' : '' ?>"
         href="<?= e($base . '/' . $item['file']) ?>">
        <span class="ic"><?= e($item['icon'] ?? '•') ?></span>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>

    <div class="footer">
      Signed in as<br>
      <strong style="color:#e5e7eb;"><?= e($user_label) ?></strong><br>
      <a href="<?= e($logout_url) ?>">Sign out →</a>
    </div>
  </aside>

  <!-- Backdrop for mobile drawer -->
  <div class="backdrop" id="sb-backdrop"></div>

  <!-- Main content area -->
  <main class="main">
    <div class="pageHead">
      <div>
        <div class="crumb"><?= e($brand_sub) ?></div>
        <h1 class="page-title"><?= e($title) ?></h1>
      </div>
    </div>

    <?php if ($m = flash_pop('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash_pop('error')):   ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
