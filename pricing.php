<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$page_title = 'Pricing | AICAP Furniture BOS';
$page_desc  = 'Three plans for furniture brands — Starter for one showroom, Growth for a chain, '
            . 'and Licensing for multi-brand operations. Talk to us for the right fit.';
$page_id    = 'pricing';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">Pricing</span>
    <h1>Plans that grow<br>with your business.</h1>
    <p>Three options — from a single showroom to a national chain. Final pricing depends on your
       tenant count and onboarding scope; the figures below are launch reference rates.</p>
  </div>
</section>

<section class="corp">
  <div class="container">
    <div style="display:grid;gap:18px;grid-template-columns: 1fr;">
      <div style="display:grid;gap:18px;grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">

        <!-- Starter -->
        <div style="background:#fff;padding:26px;border-radius:14px;border:1px solid #e5e7eb;display:flex;flex-direction:column;">
          <div style="font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Starter</div>
          <div style="margin:10px 0;">
            <span style="font-size:36px;font-weight:800;">RM 99</span>
            <span class="muted">/ month</span>
          </div>
          <p class="muted" style="margin:0 0 16px;">For one furniture brand with a single showroom.</p>
          <ul style="list-style:none;padding:0;margin:0 0 22px;color:#374151;font-size:14px;display:grid;gap:8px;">
            <li>✓ 1 branded subdomain</li>
            <li>✓ 1 showroom branch</li>
            <li>✓ Up to 50 products</li>
            <li>✓ E-catalog + Featured</li>
            <li>✓ Basic vouchers (1 active)</li>
            <li>✓ WhatsApp click tracking</li>
            <li>✓ Mobile-first site</li>
            <li>✗ Custom domain</li>
            <li>✗ QR campaigns</li>
          </ul>
          <a class="btn outline" style="margin-top:auto;" href="/contact.php?type=subscribe&amp;plan=starter">Choose Starter</a>
        </div>

        <!-- Growth (most popular) -->
        <div style="background: linear-gradient(180deg, var(--bg), #1f2937); color:#fff; padding:26px; border-radius:14px; position:relative; box-shadow: 0 8px 24px rgba(15,23,42,.18); display:flex; flex-direction:column;">
          <span style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--accent);color:var(--bg);padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;">MOST POPULAR</span>
          <div style="font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--accent);">Growth</div>
          <div style="margin:10px 0;">
            <span style="font-size:36px;font-weight:800;">RM 299</span>
            <span style="color:#94a3b8;">/ month</span>
          </div>
          <p style="color:#cbd5e1;margin:0 0 16px;">For chains and serious operators ready to scale.</p>
          <ul style="list-style:none;padding:0;margin:0 0 22px;color:#e5e7eb;font-size:14px;display:grid;gap:8px;">
            <li>✓ Everything in Starter</li>
            <li>✓ Unlimited branches</li>
            <li>✓ Unlimited products</li>
            <li>✓ Unlimited vouchers + campaigns</li>
            <li>✓ Lead pipeline + salesperson assignment</li>
            <li>✓ Member loyalty system</li>
            <li>✓ SEO meta tags + Open Graph</li>
            <li>✓ Custom domain</li>
            <li>✓ Priority support</li>
          </ul>
          <a class="btn primary" style="margin-top:auto;" href="/contact.php?type=subscribe&amp;plan=growth">Choose Growth</a>
        </div>

        <!-- Licensing -->
        <div style="background:#fff;padding:26px;border-radius:14px;border:1px solid #e5e7eb;display:flex;flex-direction:column;">
          <div style="font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Licensing</div>
          <div style="margin:10px 0;">
            <span style="font-size:36px;font-weight:800;">Custom</span>
          </div>
          <p class="muted" style="margin:0 0 16px;">For groups running multiple brands or franchises.</p>
          <ul style="list-style:none;padding:0;margin:0 0 22px;color:#374151;font-size:14px;display:grid;gap:8px;">
            <li>✓ Everything in Growth</li>
            <li>✓ Multi-brand under one account</li>
            <li>✓ White-label / co-branding</li>
            <li>✓ Cross-tenant analytics for HQ</li>
            <li>✓ Dedicated onboarding</li>
            <li>✓ Custom development</li>
            <li>✓ SLA &amp; dedicated support</li>
          </ul>
          <a class="btn dark" style="margin-top:auto;" href="/contact.php?type=licensing">Talk to sales</a>
        </div>

      </div>
    </div>

    <p class="muted" style="margin-top:24px;text-align:center;">
      All plans include hosting, SSL, daily backups and platform updates. Pricing in MYR,
      excludes 6% SST. Payment monthly or annually (10% off).
    </p>
  </div>
</section>

<section class="corp alt">
  <div class="container">
    <h2>Frequently asked</h2>
    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-top:18px;">
      <div style="background:#fff;padding:18px;border-radius:10px;border:1px solid #e5e7eb;">
        <strong>Can I switch plans later?</strong>
        <p class="muted" style="margin:6px 0 0;">Yes — upgrade or downgrade any time. We pro-rate the difference.</p>
      </div>
      <div style="background:#fff;padding:18px;border-radius:10px;border:1px solid #e5e7eb;">
        <strong>Do you charge per product?</strong>
        <p class="muted" style="margin:6px 0 0;">Only Starter caps products at 50. Growth and Licensing are unlimited.</p>
      </div>
      <div style="background:#fff;padding:18px;border-radius:10px;border:1px solid #e5e7eb;">
        <strong>Who owns my data?</strong>
        <p class="muted" style="margin:6px 0 0;">You do. We'll export your products, members, vouchers and leads on request.</p>
      </div>
      <div style="background:#fff;padding:18px;border-radius:10px;border:1px solid #e5e7eb;">
        <strong>Is there a setup fee?</strong>
        <p class="muted" style="margin:6px 0 0;">No on Starter and Growth. Licensing tier includes onboarding scope.</p>
      </div>
    </div>
  </div>
</section>

<section class="corp dark-cta">
  <div class="container">
    <h2>Ready to start?</h2>
    <p class="lead">Tell us about your brand and we'll suggest the right plan.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Talk to us</a>
      <a class="btn outline-light" href="/licensing.php">Licensing program →</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
