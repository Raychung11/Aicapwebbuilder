<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

// ===== Headline KPIs =====
$kpi = [
    'companies_total'      => (int) (db_one('SELECT COUNT(*) c FROM companies')['c'] ?? 0),
    'companies_active'     => (int) (db_one('SELECT COUNT(*) c FROM companies WHERE status = "active"')['c'] ?? 0),
    'companies_suspended'  => (int) (db_one('SELECT COUNT(*) c FROM companies WHERE status = "suspended"')['c'] ?? 0),
    'products_total'       => (int) (db_one('SELECT COUNT(*) c FROM products')['c'] ?? 0),
    'products_featured'    => (int) (db_one('SELECT COUNT(*) c FROM products WHERE is_featured = 1')['c'] ?? 0),
    'members_total'        => (int) (db_one('SELECT COUNT(*) c FROM members')['c'] ?? 0),
    'members_30d'          => (int) (db_one('SELECT COUNT(*) c FROM members WHERE created_at >= NOW() - INTERVAL 30 DAY')['c'] ?? 0),
    'leads_total'          => (int) (db_one('SELECT COUNT(*) c FROM leads')['c'] ?? 0),
    'leads_30d'            => (int) (db_one('SELECT COUNT(*) c FROM leads WHERE created_at >= NOW() - INTERVAL 30 DAY')['c'] ?? 0),
    'voucher_claims_total' => (int) (db_one('SELECT COUNT(*) c FROM voucher_claims')['c'] ?? 0),
    'qr_scans_total'       => (int) (db_one('SELECT COUNT(*) c FROM campaign_scans')['c'] ?? 0),
    'page_views_30d'       => (int) (db_one('SELECT COUNT(*) c FROM analytics_events WHERE event_type = "page_view" AND created_at >= NOW() - INTERVAL 30 DAY')['c'] ?? 0),
];

// Inquiries (table may be brand new — handle gracefully)
$inquiries_new = 0;
$recent_inquiries = [];
try {
    $inquiries_new = (int) (db_one('SELECT COUNT(*) c FROM partner_inquiries WHERE status = "new"')['c'] ?? 0);
    $recent_inquiries = db_all('SELECT * FROM partner_inquiries ORDER BY created_at DESC LIMIT 5');
} catch (Throwable $e) {
    // table not yet present — ignore
}

// ===== Recent companies (with their per-tenant counts) =====
$recent_companies = db_all(
    'SELECT c.id, c.name, c.slug, c.subdomain, c.logo, c.theme_color, c.status, c.created_at,
            (SELECT COUNT(*) FROM products WHERE company_id = c.id) AS products,
            (SELECT COUNT(*) FROM leads    WHERE company_id = c.id) AS leads,
            (SELECT COUNT(*) FROM members) AS member_pool,
            (SELECT COUNT(*) FROM analytics_events
              WHERE company_id = c.id AND event_type = "page_view") AS views
       FROM companies c
       ORDER BY c.created_at DESC
       LIMIT 8'
);

// ===== Top tenants by leads (last 30d) =====
$top_tenants = db_all(
    'SELECT c.id, c.name, c.slug, c.logo, c.theme_color,
            (SELECT COUNT(*) FROM leads WHERE company_id = c.id
                AND created_at >= NOW() - INTERVAL 30 DAY) AS leads_30d,
            (SELECT COUNT(*) FROM analytics_events WHERE company_id = c.id
                AND event_type = "whatsapp_click"
                AND created_at >= NOW() - INTERVAL 30 DAY) AS wa_30d
       FROM companies c
       WHERE c.status = "active"
       ORDER BY leads_30d DESC, wa_30d DESC
       LIMIT 5'
);

// ===== Recent activity feed =====
$recent_leads = db_all(
    'SELECT l.id, l.source, l.customer_name, l.customer_phone, l.created_at,
            c.name AS company_name, c.slug AS company_slug
       FROM leads l
       JOIN companies c ON c.id = l.company_id
       ORDER BY l.created_at DESC
       LIMIT 6'
);

// helper
function relative_time(string $ts): string {
    $t = strtotime($ts);
    if (!$t) return $ts;
    $d = time() - $t;
    if ($d < 60)        return 'just now';
    if ($d < 3600)      return intdiv($d, 60) . 'm ago';
    if ($d < 86400)     return intdiv($d, 3600) . 'h ago';
    if ($d < 86400*7)   return intdiv($d, 86400) . 'd ago';
    return date('M j', $t);
}

admin_layout_open('Dashboard');
?>
<style>
.dash-hero {
  background: linear-gradient(135deg, #0f172a, #1e293b);
  color:#fff; padding: 22px 24px; border-radius: 14px; margin-bottom: 18px;
  display:flex; flex-wrap:wrap; gap:16px; align-items:center; justify-content:space-between;
}
.dash-hero h2 { margin:0 0 4px; color:#fff; font-size: 20px; }
.dash-hero p  { margin:0; color:#94a3b8; font-size:14px; }
.dash-hero .actions a {
  background: rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.15);
  padding: 8px 14px; border-radius: 8px; text-decoration:none; font-size:13px; margin-left:6px;
}
.dash-hero .actions a:hover { background: rgba(255,255,255,.16); }

.kpi-rich { display:grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 18px; }
.kpi-rich .b { background:#fff; padding: 16px; border-radius: 12px; border:1px solid #e5e7eb;
  display:flex; gap: 12px; align-items: flex-start; }
.kpi-rich .ic {
  width: 42px; height: 42px; border-radius: 10px; display:flex; align-items:center; justify-content:center;
  font-size: 20px; flex-shrink: 0;
}
.kpi-rich .v { font-size: 24px; font-weight: 700; color:#111; line-height:1.1; }
.kpi-rich .l { font-size: 12px; color:#6b7280; margin: 4px 0 2px; text-transform: uppercase; letter-spacing: .04em; }
.kpi-rich .sub { font-size: 12px; color:#6b7280; }
.kpi-rich .sub b { color:#16a34a; }
.kpi-rich .sub b.warn { color:#d97706; }

.grid-2 { display:grid; gap:18px; grid-template-columns: 1fr; margin-bottom: 18px; }
@media (min-width: 980px) { .grid-2 { grid-template-columns: 1.4fr 1fr; } }

.tcard { background:#fff; border-radius: 12px; border:1px solid #e5e7eb; }
.tcard h3 {
  margin: 0; padding: 14px 16px; border-bottom:1px solid #f3f4f6;
  font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color:#374151;
  display:flex; justify-content:space-between; align-items:center;
}
.tcard h3 a { color:#2563eb; font-size: 12px; text-transform:none; letter-spacing:0; text-decoration:none; }
.tcard .body { padding: 8px 16px 16px; }

.co-card {
  display:flex; gap: 12px; align-items:center; padding: 12px 6px; border-bottom: 1px solid #f3f4f6;
}
.co-card:last-child { border-bottom: 0; }
.co-card .av {
  width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
  background: #f3f4f6; display:flex; align-items:center; justify-content:center;
  color:#fff; font-weight: 700; overflow: hidden;
}
.co-card .av img { width:100%; height:100%; object-fit: contain; padding: 4px; background:#fff; }
.co-card .meta { flex: 1; min-width: 0; }
.co-card .meta strong { display:block; }
.co-card .meta .sub { color:#6b7280; font-size: 12px; }
.co-card .stats { display:flex; gap:14px; font-size: 12px; color:#374151; }
.co-card .stats .num { font-weight: 700; color:#111; display:block; font-size: 14px; }

.feed { display: grid; gap: 8px; }
.feed .item { display:flex; gap:10px; align-items:flex-start; padding: 10px 6px;
  border-bottom: 1px solid #f3f4f6; }
.feed .item:last-child { border-bottom: 0; }
.feed .ic-src { width: 30px; height:30px; border-radius:8px; flex-shrink:0; display:flex;
  align-items:center; justify-content:center; font-size: 14px; background:#eef2ff; color:#4338ca; }
.feed .body { flex:1; min-width: 0; }
.feed .body strong { font-size: 13px; }
.feed .body .desc { font-size: 12px; color:#6b7280; }
.feed .time { font-size: 11px; color:#9ca3af; flex-shrink:0; }

.lead-row { display:grid; grid-template-columns: 1fr auto auto; gap:12px; align-items:center;
  padding: 10px 6px; border-bottom: 1px solid #f3f4f6; }
.lead-row:last-child { border-bottom: 0; }
.lead-row .src { font-size: 11px; padding: 2px 8px; border-radius: 999px; background:#f3f4f6; color:#374151; }
.lead-row .when { font-size: 11px; color:#9ca3af; }

.qa { display:grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
.qa a {
  background:#fff; border:1px solid #e5e7eb; border-radius: 10px; padding: 14px;
  text-decoration:none; color:#111; display:flex; gap:10px; align-items:center;
  transition: border-color .15s, box-shadow .15s;
}
.qa a:hover { border-color: #2563eb; box-shadow: 0 2px 6px rgba(37,99,235,.1); }
.qa .ic { width: 32px; height: 32px; border-radius: 8px; background:#eef2ff; color:#4338ca;
  display:flex; align-items:center; justify-content:center; flex-shrink: 0; }
.qa strong { display:block; font-size: 14px; }
.qa span { color:#6b7280; font-size: 12px; }

.empty { color:#9ca3af; font-size: 13px; padding: 14px; text-align: center; }
</style>

<!-- Hero -->
<div class="dash-hero">
  <div>
    <h2>Welcome back, <?= e($super['name']) ?> 👋</h2>
    <p><?= e(date('l, F j Y')) ?> · AICAP Furniture BOS HQ</p>
  </div>
  <div class="actions">
    <a href="/admin/companies.php">+ Add Company</a>
    <a href="/admin/analytics.php">Analytics</a>
  </div>
</div>

<!-- KPI cards -->
<div class="kpi-rich">
  <div class="b">
    <div class="ic" style="background:#dbeafe;color:#1e40af;">🏢</div>
    <div>
      <div class="l">Tenants</div>
      <div class="v"><?= $kpi['companies_total'] ?></div>
      <div class="sub"><b><?= $kpi['companies_active'] ?> active</b><?= $kpi['companies_suspended'] ? ' · <b class="warn">' . $kpi['companies_suspended'] . ' suspended</b>' : '' ?></div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#fef3c7;color:#b45309;">🛋️</div>
    <div>
      <div class="l">Products</div>
      <div class="v"><?= $kpi['products_total'] ?></div>
      <div class="sub"><b><?= $kpi['products_featured'] ?>★ featured</b></div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#dcfce7;color:#166534;">👥</div>
    <div>
      <div class="l">Members</div>
      <div class="v"><?= $kpi['members_total'] ?></div>
      <div class="sub">+<?= $kpi['members_30d'] ?> in last 30d</div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#fee2e2;color:#991b1b;">🎯</div>
    <div>
      <div class="l">Leads</div>
      <div class="v"><?= $kpi['leads_total'] ?></div>
      <div class="sub">+<?= $kpi['leads_30d'] ?> in last 30d</div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#ede9fe;color:#6d28d9;">🎁</div>
    <div>
      <div class="l">Voucher Claims</div>
      <div class="v"><?= $kpi['voucher_claims_total'] ?></div>
      <div class="sub">across all tenants</div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#cffafe;color:#0e7490;">📷</div>
    <div>
      <div class="l">QR Scans</div>
      <div class="v"><?= $kpi['qr_scans_total'] ?></div>
      <div class="sub">offline → online</div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#fce7f3;color:#9d174d;">📨</div>
    <div>
      <div class="l">New Inquiries</div>
      <div class="v"><?= $inquiries_new ?></div>
      <div class="sub">subscribe / partner / licensing</div>
    </div>
  </div>
  <div class="b">
    <div class="ic" style="background:#f1f5f9;color:#0f172a;">👁️</div>
    <div>
      <div class="l">Page Views (30d)</div>
      <div class="v"><?= $kpi['page_views_30d'] ?></div>
      <div class="sub">all tenants combined</div>
    </div>
  </div>
</div>

<!-- Two-column: recent companies + activity -->
<div class="grid-2">
  <div class="tcard">
    <h3>Recent Tenants <a href="/admin/companies.php">View all →</a></h3>
    <div class="body">
      <?php if (!$recent_companies): ?>
        <div class="empty">No companies yet.</div>
      <?php else: ?>
        <?php foreach ($recent_companies as $c): ?>
          <div class="co-card">
            <div class="av" style="background: <?= e($c['theme_color'] ?: '#1e293b') ?>;">
              <?php if (!empty($c['logo'])): ?>
                <img src="<?= e($c['logo']) ?>" alt="">
              <?php else: ?>
                <?= e(strtoupper(substr($c['name'], 0, 1))) ?>
              <?php endif; ?>
            </div>
            <div class="meta">
              <strong><?= e($c['name']) ?></strong>
              <div class="sub">
                <?= e($c['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?>
                · <span class="badge <?= $c['status']==='active'?'green':'red' ?>"><?= e($c['status']) ?></span>
              </div>
            </div>
            <div class="stats">
              <div><span class="num"><?= (int) $c['products'] ?></span> products</div>
              <div><span class="num"><?= (int) $c['leads'] ?></span> leads</div>
              <div><span class="num"><?= (int) $c['views'] ?></span> views</div>
              <div><a class="btn outline" href="/admin/company-edit.php?id=<?= (int)$c['id'] ?>" style="padding:5px 10px;font-size:12px;">Edit</a></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="tcard">
    <h3>Live Activity</h3>
    <div class="body feed">
      <?php if (!$recent_leads && !$recent_inquiries): ?>
        <div class="empty">No activity yet — leads and inquiries will show up here.</div>
      <?php endif; ?>
      <?php foreach ($recent_inquiries as $inq): ?>
        <div class="item">
          <div class="ic-src" style="background:#fce7f3;color:#9d174d;">📨</div>
          <div class="body">
            <strong><?= e($inq['contact_name']) ?></strong> from <?= e($inq['company_name']) ?>
            <div class="desc">New <strong><?= e($inq['type']) ?></strong> inquiry — <?= e($inq['email']) ?></div>
          </div>
          <div class="time"><?= e(relative_time($inq['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
      <?php foreach ($recent_leads as $l): ?>
        <div class="item">
          <div class="ic-src">🎯</div>
          <div class="body">
            <strong><?= e($l['customer_name'] ?: '(no name)') ?></strong> · <?= e($l['source']) ?>
            <div class="desc"><?= e($l['company_name']) ?>
              <?= $l['customer_phone'] ? ' · ' . e($l['customer_phone']) : '' ?>
            </div>
          </div>
          <div class="time"><?= e(relative_time($l['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Top tenants leaderboard -->
<?php if ($top_tenants): ?>
<div class="tcard" style="margin-bottom: 18px;">
  <h3>Top Tenants by Leads (last 30d)</h3>
  <div class="body">
    <?php foreach ($top_tenants as $i => $t): ?>
      <div class="lead-row" style="grid-template-columns: 28px 40px 1fr auto auto;">
        <div style="font-weight:700;color:#9ca3af;font-size:13px;">#<?= $i + 1 ?></div>
        <div class="av" style="width:32px;height:32px;border-radius:8px;background: <?= e($t['theme_color'] ?: '#1e293b') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;overflow:hidden;">
          <?php if (!empty($t['logo'])): ?>
            <img src="<?= e($t['logo']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;background:#fff;padding:3px;">
          <?php else: ?>
            <?= e(strtoupper(substr($t['name'], 0, 1))) ?>
          <?php endif; ?>
        </div>
        <div><strong><?= e($t['name']) ?></strong><div class="sub" style="color:#6b7280;font-size:12px;"><?= e($t['slug']) ?></div></div>
        <div class="src" style="background:#dbeafe;color:#1e40af;"><?= (int) $t['leads_30d'] ?> leads</div>
        <div class="src" style="background:#dcfce7;color:#166534;"><?= (int) $t['wa_30d'] ?> WA</div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Quick actions -->
<div class="tcard">
  <h3>Quick Actions</h3>
  <div class="body">
    <div class="qa">
      <a href="/admin/company-edit.php">
        <div class="ic">🏢</div>
        <div><strong>Add a tenant</strong><span>Create a new company subdomain</span></div>
      </a>
      <a href="/admin/companies.php">
        <div class="ic" style="background:#fef3c7;color:#b45309;">🪑</div>
        <div><strong>Seed sample products</strong><span>Drop a starter catalog into any tenant</span></div>
      </a>
      <a href="/admin/templates.php">
        <div class="ic" style="background:#dcfce7;color:#166534;">📐</div>
        <div><strong>Page templates</strong><span>Reusable layouts for tenants</span></div>
      </a>
      <a href="/admin/analytics.php">
        <div class="ic" style="background:#ede9fe;color:#6d28d9;">📊</div>
        <div><strong>Cross-tenant analytics</strong><span>Compare performance across brands</span></div>
      </a>
      <a href="/about.php" target="_blank">
        <div class="ic" style="background:#f1f5f9;color:#0f172a;">🌐</div>
        <div><strong>Public corporate site</strong><span>aicap.my landing &amp; About</span></div>
      </a>
    </div>
  </div>
</div>

<?php admin_layout_close(); ?>
