<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$page_title = 'How it works | AICAP Furniture BOS';
$page_desc  = 'Four short steps from kick-off to a fully-branded furniture website live '
            . 'on the web — onboarding, customising, publishing your catalog, and capturing leads.';
$page_id    = 'how';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">How it works</span>
    <h1>Live in days,<br>not months.</h1>
    <p>Four short steps from kick-off to a fully-branded furniture website on the web.</p>
  </div>
</section>

<section class="corp">
  <div class="container">
    <div style="display:grid;gap:22px;grid-template-columns: 1fr;">

      <div style="display:grid;gap:18px;grid-template-columns: 80px 1fr;align-items:flex-start;background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="background:var(--bg);color:var(--accent);width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;">1</div>
        <div>
          <h3 style="margin:0 0 6px;">Onboard your brand</h3>
          <p style="margin:0;color:#374151;">We provision your subdomain (or wire up your custom domain),
             create the owner admin account, and configure your name, logo, brand colours, contact
             details and WhatsApp number. Most tenants are live in 24 hours.</p>
          <p class="muted" style="margin-top:6px;">Outputs: working subdomain, admin login, branded header + footer.</p>
        </div>
      </div>

      <div style="display:grid;gap:18px;grid-template-columns: 80px 1fr;align-items:flex-start;background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="background:var(--bg);color:var(--accent);width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;">2</div>
        <div>
          <h3 style="margin:0 0 6px;">Publish your catalog</h3>
          <p style="margin:0;color:#374151;">Add categories, subcategories, products, variants and images
             from the Tenant dashboard — or seed a starter catalog with one click and edit from there.
             Mark items as Featured and they surface on your homepage.</p>
          <p class="muted" style="margin-top:6px;">Outputs: live e-catalog at <code>/catalog.php</code>, search + chip filters.</p>
        </div>
      </div>

      <div style="display:grid;gap:18px;grid-template-columns: 80px 1fr;align-items:flex-start;background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="background:var(--bg);color:var(--accent);width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;">3</div>
        <div>
          <h3 style="margin:0 0 6px;">Promote with vouchers &amp; QR</h3>
          <p style="margin:0;color:#374151;">Create active vouchers with one-step claim, run QR campaigns
             with auto-generated <code>/q/&lt;brand&gt;/&lt;key&gt;</code> URLs, add showroom branches
             with maps and Waze.</p>
          <p class="muted" style="margin-top:6px;">Outputs: voucher claim flow, QR campaigns, Visit Us page.</p>
        </div>
      </div>

      <div style="display:grid;gap:18px;grid-template-columns: 80px 1fr;align-items:flex-start;background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="background:var(--bg);color:var(--accent);width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;">4</div>
        <div>
          <h3 style="margin:0 0 6px;">Capture &amp; convert leads</h3>
          <p style="margin:0;color:#374151;">Every WhatsApp click, voucher claim and QR scan becomes a
             tracked lead — attributed to the right campaign and ready for your salesperson to follow
             up. Watch performance in your dashboard.</p>
          <p class="muted" style="margin-top:6px;">Outputs: lead pipeline, salesperson assignment, KPIs in real time.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="corp dark-cta">
  <div class="container">
    <h2>Most tenants are live within a week.</h2>
    <p class="lead">Tell us about your brand and we'll line up onboarding.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Start onboarding</a>
      <a class="btn outline-light" href="/pricing.php">View pricing</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
