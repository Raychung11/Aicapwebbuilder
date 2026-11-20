<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/#about'); }

$tenants = db_all(
    'SELECT name, slug, subdomain, logo, theme_color
       FROM companies WHERE status = "active" ORDER BY name LIMIT 12'
);
$on_platform = (($_SERVER['HTTP_HOST'] ?? '') === APP_BASE_DOMAIN
              || ($_SERVER['HTTP_HOST'] ?? '') === 'www.' . APP_BASE_DOMAIN);

$page_title = 'About | AICAP Furniture BOS';
$page_desc  = 'AICAP Furniture BOS is a multi-tenant SaaS that gives every furniture '
            . 'brand its own branded website, e-catalog, voucher system, lead capture '
            . 'and analytics.';
$page_id    = 'about';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">About AICAP</span>
    <h1>The digital operating system<br>for furniture brands.</h1>
    <p>We help furniture companies in Malaysia run a modern, mobile-first online presence —
       a branded website, e-catalog, vouchers, lead capture and analytics — all from one place.</p>
  </div>
</section>

<section class="corp alt">
  <div class="container" style="display:grid;gap:36px;grid-template-columns: 1fr;">
    <div>
      <h2>Our mission</h2>
      <p class="lead">Furniture is a face-to-face business — but customers research online first.
         Every showroom visit, every WhatsApp enquiry, every voucher claim should be trackable
         and attributed to the brand that earned it.</p>
      <p class="lead" style="margin-top:14px;">AICAP Furniture BOS is built so each licensed
         brand keeps its own identity, catalog and customer relationships, while sharing a single
         robust platform underneath.</p>
    </div>
  </div>
</section>

<section class="corp">
  <div class="container">
    <h2>What we believe</h2>
    <p class="lead">Three principles guide every decision we make on the platform.</p>
    <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));margin-top:24px;">
      <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f59e0b;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;">🏷️</div>
        <h3 style="margin:0 0 6px;">Each brand stays its own brand</h3>
        <p class="muted" style="margin:0;">Every tenant looks, feels and ranks for itself — never as a marketplace tab.</p>
      </div>
      <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f59e0b;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;">📱</div>
        <h3 style="margin:0 0 6px;">Mobile-first, always</h3>
        <p class="muted" style="margin:0;">Customers shop from a phone. Every page loads fast and works on every screen.</p>
      </div>
      <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f59e0b;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;">🔒</div>
        <h3 style="margin:0 0 6px;">Your data is yours</h3>
        <p class="muted" style="margin:0;">Strict tenant isolation — leads, members and analytics never leak between brands.</p>
      </div>
    </div>
  </div>
</section>

<section class="corp alt">
  <div class="container">
    <h2>Built to scale</h2>
    <p class="lead">From a single showroom to a national chain — same platform, same uptime.</p>
    <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-top:24px;">
      <div style="background:var(--bg);color:#fff;padding:22px;border-radius:12px;"><div style="font-size:30px;font-weight:800;color:#f59e0b;">100+</div><div style="font-size:13px;color:#94a3b8;">tenants supported per platform</div></div>
      <div style="background:var(--bg);color:#fff;padding:22px;border-radius:12px;"><div style="font-size:30px;font-weight:800;color:#f59e0b;">17</div><div style="font-size:13px;color:#94a3b8;">tables, every one tenant-isolated</div></div>
      <div style="background:var(--bg);color:#fff;padding:22px;border-radius:12px;"><div style="font-size:30px;font-weight:800;color:#f59e0b;">5</div><div style="font-size:13px;color:#94a3b8;">core analytics events tracked</div></div>
      <div style="background:var(--bg);color:#fff;padding:22px;border-radius:12px;"><div style="font-size:30px;font-weight:800;color:#f59e0b;">0</div><div style="font-size:13px;color:#94a3b8;">cross-tenant data leaks by design</div></div>
    </div>
  </div>
</section>

<?php if ($tenants): ?>
<section class="corp">
  <div class="container">
    <h2>Trusted by furniture brands</h2>
    <p class="lead">A few of the companies already running on AICAP.</p>
    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-top:22px;">
      <?php foreach ($tenants as $t):
        $href = $on_platform
          ? 'https://' . $t['subdomain'] . '.' . APP_BASE_DOMAIN
          : '/?as=' . rawurlencode($t['slug']);
      ?>
        <a href="<?= e($href) ?>" <?= $on_platform ? 'target="_blank" rel="noopener"' : '' ?>
           style="display:flex;gap:12px;align-items:center;background:#fff;padding:14px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:inherit;">
          <div style="width:42px;height:42px;border-radius:8px;background:<?= e($t['theme_color'] ?: '#1e293b') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;overflow:hidden;flex-shrink:0;">
            <?php if (!empty($t['logo'])): ?>
              <img src="<?= e($t['logo']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;background:#fff;padding:5px;">
            <?php else: ?>
              <?= e(strtoupper(substr($t['name'], 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div style="min-width:0;">
            <strong style="display:block;line-height:1.2;"><?= e($t['name']) ?></strong>
            <span class="muted" style="font-size:12px;"><?= e($t['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="corp dark-cta">
  <div class="container">
    <h2>Ready to put your brand on AICAP?</h2>
    <p class="lead">We're onboarding furniture companies for our subscription and licensing programs.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Subscribe / partner with us</a>
      <a class="btn outline-light" href="/pricing.php">See pricing</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
