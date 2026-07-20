<?php
/**
 * Agent Portal — public per-agent page accessed by referral code.
 *
 *   /agent.php?code=ALICE001
 *
 * Lists every active voucher for the tenant with:
 *   - the referral-tagged URL the agent should share
 *   - a pre-written WhatsApp message (rendered from voucher.marketing_script)
 *   - one-tap "Open in WhatsApp" + Copy Link + Copy Message buttons
 *
 * No login — the referral code itself is the lookup. Disabled / unknown
 * codes get a 404 page so codes can't be enumerated.
 */
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/marketing.php';
require_once __DIR__ . '/inc/analytics.php';

$company = require_company();
$cid     = (int) $company['id'];
$code    = trim((string) input('code', ''));
$err     = '';
$agent   = null;

if ($code !== '') {
    $agent = tenant_one(
        'SELECT * FROM salespersons WHERE company_id = ? AND referral_code = ? AND status = "active" LIMIT 1',
        $cid, [$code]
    );
    if (!$agent) {
        $err = 'That agent code is not active. Please check with ' . $company['name'] . '.';
    }
}

// Render the code-entry form when no valid agent
if (!$agent) {
    $primary   = htmlspecialchars($company['theme_color']           ?: '#111827', ENT_QUOTES);
    $secondary = htmlspecialchars($company['theme_secondary_color'] ?: '#f59e0b', ENT_QUOTES);
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="<?= $primary ?>">
<meta name="robots" content="noindex,nofollow">
<title>Agent Login · <?= e($company['name']) ?></title>
<style>
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body {
  font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 20px;
  color:#fff;
  background:
    radial-gradient(700px 400px at 110% -10%, rgba(245,158,11,.18), transparent 60%),
    radial-gradient(900px 500px at -10% 110%, rgba(99,102,241,.15), transparent 60%),
    linear-gradient(135deg, <?= $primary ?>, #000);
}
.card { background:#fff; color:#111; padding: 30px; border-radius: 14px;
        max-width: 400px; width: 100%; box-shadow: 0 12px 40px rgba(0,0,0,.4); }
.brand { display:flex; align-items:center; gap:10px; margin-bottom: 14px; }
.brand .dot { width: 28px; height: 28px; border-radius: 8px; background: <?= $secondary ?>;
              color: <?= $primary ?>; display:flex; align-items:center; justify-content:center;
              font-weight: 800; font-size: 13px; }
.brand strong { font-size: 14px; }
.brand span { font-size: 11px; color:#6b7280; text-transform: uppercase; letter-spacing:.04em; display:block; }
h2 { margin: 4px 0 4px; font-size: 24px; }
p.lead { color:#6b7280; margin: 0 0 20px; font-size: 14px; }
label { display:block; font-size:13px; color:#374151; margin: 0 0 6px; font-weight:500; }
input { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:10px;
        font: inherit; text-transform: uppercase; letter-spacing:.05em; }
input:focus { outline:0; border-color: <?= $primary ?>; box-shadow: 0 0 0 3px rgba(15,23,42,.12); }
button { width:100%; padding: 13px; border:0; cursor:pointer;
         background: <?= $primary ?>; color:#fff; font-weight:600; border-radius:10px;
         margin-top: 14px; font-size: 15px; }
button:hover { filter: brightness(1.1); }
.alert { background:#fee2e2; color:#991b1b; padding:11px 14px; border-radius:8px;
         margin-bottom: 14px; font-size: 14px; }
.foot { margin-top: 18px; text-align:center; font-size:13px; color:#6b7280; }
.foot a { color: <?= $primary ?>; font-weight: 600; text-decoration:none; }
</style>
</head>
<body>
<form class="card" method="get" autocomplete="off">
  <div class="brand">
    <div class="dot">A</div>
    <div>
      <strong><?= e($company['name']) ?></strong>
      <span>Agent Portal</span>
    </div>
  </div>
  <h2>Sign in</h2>
  <p class="lead">Enter your agent code to open your marketing kit.</p>

  <?php if ($err): ?><div class="alert">⚠️ <?= e($err) ?></div><?php endif; ?>

  <label for="code">Agent code</label>
  <input id="code" name="code" type="text" required autofocus
         placeholder="e.g. ALICE001"
         value="<?= e($code) ?>">

  <button type="submit">Open my kit →</button>

  <div class="foot">
    Don't have a code yet?<br>
    Contact <?= e($company['name']) ?> to register as an agent.
    <div style="margin-top:12px;">
      <a href="/">← Back to <?= e($company['name']) ?></a>
    </div>
  </div>
</form>
</body>
</html>
<?php
    exit;
}

// ===== Agent is valid — render the marketing kit =====
track_event($cid, 'agent_portal_view', [
    'entity_type' => 'salesperson',
    'entity_id'   => (int) $agent['id'],
]);

// Active vouchers for this tenant
$vouchers = tenant_all(
    'SELECT * FROM vouchers
      WHERE company_id = ? AND status = "active"
        AND (expiry_date IS NULL OR expiry_date >= CURDATE())
      ORDER BY created_at DESC',
    $cid
);

// Per-agent attribution counters (this month)
$first_of_month = date('Y-m-01');
$counts = [
    'leads_30d' => (int) (db_one(
        'SELECT COUNT(*) c FROM leads
          WHERE company_id = ? AND salesperson_id = ?
            AND created_at >= ?',
        [$cid, (int) $agent['id'], $first_of_month]
    )['c'] ?? 0),
    'claims_30d' => (int) (db_one(
        'SELECT COUNT(*) c FROM voucher_claims
          WHERE company_id = ? AND salesperson_id = ?
            AND claimed_at >= ?',
        [$cid, (int) $agent['id'], $first_of_month]
    )['c'] ?? 0),
];

// Build the public host for share links
$public_host = !empty($company['custom_domain'])
    ? $company['custom_domain']
    : ($company['subdomain'] . '.' . APP_BASE_DOMAIN);
$site_root = APP_URL_SCHEME . '://' . $public_host;

$home_link = $site_root . '/?ref=' . rawurlencode($agent['referral_code']);
$primary   = e($company['theme_color']           ?: '#111827');
$secondary = e($company['theme_secondary_color'] ?: '#f59e0b');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="<?= $primary ?>">
<meta name="robots" content="noindex,nofollow">
<title><?= e($agent['name']) ?> · Agent Kit · <?= e($company['name']) ?></title>
<style>
:root { --c-primary: <?= $primary ?>; --c-secondary: <?= $secondary ?>; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
       background:#f3f4f6; color:#111; -webkit-font-smoothing: antialiased; line-height: 1.5; }
.container { max-width: 720px; margin: 0 auto; padding: 16px; }

header.kit-head {
  background: linear-gradient(135deg, var(--c-primary), #000); color:#fff;
  padding: 22px 16px; position: sticky; top:0; z-index: 10;
}
header.kit-head .row { max-width: 720px; margin: 0 auto; display:flex; align-items:center; gap:12px; }
header.kit-head .av {
  width:46px; height:46px; border-radius: 12px; background: var(--c-secondary);
  color:#0f172a; display:flex; align-items:center; justify-content:center;
  font-weight: 800; font-size: 18px;
}
header.kit-head h1 { margin: 0; font-size: 18px; line-height: 1.15; }
header.kit-head .sub { font-size: 12px; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .04em; }
header.kit-head code { background: rgba(255,255,255,.12); padding: 2px 8px; border-radius: 6px; font-size: 12px; }

.kpi { display:grid; grid-template-columns: repeat(2,1fr); gap: 10px; margin: 14px 0 6px; }
.kpi .b { background:#fff; padding:14px; border-radius:12px; border:1px solid #e5e7eb; }
.kpi .b .v { font-size: 22px; font-weight: 800; color: var(--c-primary); }
.kpi .b .l { font-size: 11px; color:#6b7280; text-transform: uppercase; letter-spacing:.04em; }

.card { background:#fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.06); margin: 12px 0; overflow: hidden; }
.card .head { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; display:flex; align-items:flex-start; gap: 10px; justify-content:space-between; }
.card .head h3 { margin: 0 0 4px; font-size: 16px; }
.card .head .pill { background:#fff7ed; color:#b45309; padding: 2px 8px; border-radius:999px; font-size: 11px; font-weight: 700; }
.card .head .meta { color:#6b7280; font-size: 13px; }

.card .body { padding: 14px 16px; }
.card .row { display:flex; gap: 8px; align-items:center; margin-bottom: 8px; }
.card .row input, .card .row textarea {
  width:100%; border:1px solid #e5e7eb; border-radius:10px;
  padding:10px 12px; font: inherit; background:#f9fafb; resize: vertical;
}
.card .row textarea { font-size: 13px; min-height: 130px; }
.btn {
  display:inline-flex; align-items:center; justify-content:center; gap:6px;
  padding: 10px 14px; border-radius: 10px; font-weight:600; text-decoration:none;
  border:0; cursor:pointer; font: inherit; min-height: 42px; line-height: 1.2;
  white-space: nowrap;
}
.btn.wa  { background:#25d366; color:#fff; }
.btn.wa:hover { background:#1ebe5b; }
.btn.copy { background:#fff; border:1px solid #d1d5db; color:#111; }
.btn.copy:hover { background:#f9fafb; }
.btn.primary { background: var(--c-primary); color:#fff; }
.btn.block { width: 100%; }
.row .btn { flex: 0 0 auto; }
.actions { display:flex; gap:8px; flex-wrap:wrap; padding: 0 16px 16px; }

.tip { background:#ecfdf5; border-left: 3px solid #10b981; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #065f46; margin: 10px 0; }
.muted { color:#6b7280; font-size: 13px; }
</style>
</head>
<body>

<header class="kit-head">
  <div class="row">
    <div class="av"><?= e(strtoupper(substr($agent['name'], 0, 1))) ?></div>
    <div style="flex:1;min-width:0;">
      <div class="sub"><?= e($company['name']) ?> · Agent Kit</div>
      <h1><?= e($agent['name']) ?></h1>
      <div style="margin-top:4px;font-size:12px;">Code: <code><?= e($agent['referral_code']) ?></code></div>
    </div>
  </div>
</header>

<div class="container">

  <div class="kpi">
    <div class="b"><div class="l">Leads · this month</div><div class="v"><?= (int) $counts['leads_30d'] ?></div></div>
    <div class="b"><div class="l">Voucher claims · this month</div><div class="v"><?= (int) $counts['claims_30d'] ?></div></div>
  </div>

  <div class="tip">
    Bookmark this page — it's your one-tap marketing kit. Share the link
    or message and any voucher claim, WhatsApp click or QR scan from it
    will be tagged to you for 30 days.
  </div>

  <!-- Generic referral link -->
  <div class="card">
    <div class="head">
      <div>
        <h3>Your referral link</h3>
        <div class="meta">Drives anyone to <?= e($company['name']) ?> homepage with your tag.</div>
      </div>
    </div>
    <div class="body">
      <div class="row">
        <input id="home-link" readonly value="<?= e($home_link) ?>" onclick="this.select();">
      </div>
      <div class="actions" style="padding:0;">
        <button class="btn copy" type="button"
          onclick="copyText(this, '<?= e(addslashes($home_link)) ?>', 'Copy link')">📋 Copy link</button>
        <a class="btn wa" target="_blank" rel="noopener"
           href="<?= e(whatsapp_share_url('Check out ' . $company['name'] . ' 👇 ' . $home_link)) ?>">
          💬 Share on WhatsApp
        </a>
      </div>
    </div>
  </div>

  <!-- Per-voucher marketing kits -->
  <?php if (!$vouchers): ?>
    <div class="card"><div class="body muted">No active vouchers right now. Check back soon.</div></div>
  <?php endif; ?>

  <?php foreach ($vouchers as $v):
    $claim_url = $site_root . '/voucher-claim.php?id=' . (int) $v['id']
               . '&ref=' . rawurlencode($agent['referral_code']);
    $script    = render_marketing_script($v, $company, $agent, $claim_url);
    $value_lbl = voucher_value_text($v);
  ?>
    <div class="card">
      <div class="head">
        <div>
          <h3><?= e($v['title']) ?></h3>
          <div class="meta">
            <?= e($value_lbl) ?>
            <?php if (!empty($v['expiry_date'])): ?>
              · valid until <?= e(date('j M Y', strtotime($v['expiry_date']))) ?>
            <?php endif; ?>
          </div>
        </div>
        <span class="pill">VOUCHER</span>
      </div>
      <div class="body">
        <label class="muted" style="display:block;margin-bottom:4px;">Voucher claim link</label>
        <div class="row">
          <input readonly value="<?= e($claim_url) ?>" onclick="this.select();">
        </div>

        <label class="muted" style="display:block;margin:10px 0 4px;">Pre-written message</label>
        <div class="row">
          <textarea id="msg-<?= (int)$v['id'] ?>"><?= e($script) ?></textarea>
        </div>
      </div>
      <div class="actions">
        <button class="btn copy" type="button"
          onclick="copyText(this, '<?= e(addslashes($claim_url)) ?>', 'Copy link')">📋 Link</button>
        <button class="btn copy" type="button"
          onclick="copyTextarea(this, 'msg-<?= (int)$v['id'] ?>', 'Copy message')">📝 Message</button>
        <a class="btn wa" target="_blank" rel="noopener"
           data-share-id="<?= (int)$v['id'] ?>"
           href="<?= e(whatsapp_share_url($script)) ?>">
          💬 Share on WhatsApp
        </a>
      </div>
    </div>
  <?php endforeach; ?>

  <p class="muted" style="margin-top:18px;text-align:center;">
    <a href="/" style="color:inherit;">← Back to <?= e($company['name']) ?> site</a>
  </p>

</div>

<script>
function copyText(btn, txt, original) {
  navigator.clipboard.writeText(txt).then(function () {
    btn.textContent = '✅ Copied';
    setTimeout(function () { btn.textContent = '📋 ' + original; }, 1600);
  });
}
function copyTextarea(btn, id, original) {
  var t = document.getElementById(id);
  if (!t) return;
  navigator.clipboard.writeText(t.value).then(function () {
    btn.textContent = '✅ Copied';
    setTimeout(function () { btn.textContent = '📝 ' + original; }, 1600);
  });
}
// Refresh WhatsApp share links on the fly when the user edits the message textarea.
document.querySelectorAll('textarea[id^="msg-"]').forEach(function (t) {
  t.addEventListener('input', function () {
    var id = t.id.replace('msg-', '');
    var a  = document.querySelector('a.btn.wa[data-share-id="' + id + '"]');
    if (a) a.href = 'https://wa.me/?text=' + encodeURIComponent(t.value);
  });
});
</script>
</body>
</html>
