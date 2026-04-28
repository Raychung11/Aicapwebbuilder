<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$page_title = 'Features | AICAP Furniture BOS';
$page_desc  = 'Branded subdomain, e-catalog, vouchers with one-step claim, WhatsApp lead '
            . 'capture, QR campaigns, branch directory with maps, member loyalty, analytics '
            . 'and SEO — everything a furniture brand needs to sell online.';
$page_id    = 'features';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">Platform Features</span>
    <h1>Everything a furniture brand<br>needs to sell online.</h1>
    <p>From branded subdomains to lead-attribution analytics, AICAP covers the entire
       customer journey — discovery, enquiry, voucher claim, showroom visit and follow-up.</p>
  </div>
</section>

<section class="corp">
  <div class="container">
    <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">🌐</div>
        <h3 style="margin:8px 0 6px;">Branded subdomain</h3>
        <p class="muted" style="margin:0;">Each tenant gets <code>{brand}.aicap.my</code> or a custom domain.
           Logo, theme colours and SEO are fully tenant-controlled.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">🛋️</div>
        <h3 style="margin:8px 0 6px;">E-Catalog</h3>
        <p class="muted" style="margin:0;">Categories, subcategories, variants, multiple images,
           stock status and Featured items that surface on the homepage.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">⭐</div>
        <h3 style="margin:8px 0 6px;">Featured Products</h3>
        <p class="muted" style="margin:0;">A dedicated dashboard to mark hero products
           — they appear at the top of the catalog and homepage.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">🎁</div>
        <h3 style="margin:8px 0 6px;">Vouchers</h3>
        <p class="muted" style="margin:0;">Percent / fixed / gift vouchers with per-member
           and total-usage caps. <strong>One-step register &amp; claim</strong> on a single form.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">💬</div>
        <h3 style="margin:8px 0 6px;">WhatsApp lead capture</h3>
        <p class="muted" style="margin:0;">Every WhatsApp click is logged and creates a lead
           with the product, branch and campaign that triggered it.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">📷</div>
        <h3 style="margin:8px 0 6px;">QR campaigns</h3>
        <p class="muted" style="margin:0;">Auto-generated <code>/q/&lt;brand&gt;/&lt;key&gt;</code>
           URLs you can drop on flyers, magazines and POSM. Every scan tracked.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">📍</div>
        <h3 style="margin:8px 0 6px;">Branches &amp; Visit Us</h3>
        <p class="muted" style="margin:0;">Showroom directory with embedded map, Google Maps
           &amp; Waze deep links, photo gallery, opening hours and tap-to-call.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">👥</div>
        <h3 style="margin:8px 0 6px;">Member loyalty</h3>
        <p class="muted" style="margin:0;">Customers register once, claim vouchers and view
           their codes from the My Account dashboard.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">📊</div>
        <h3 style="margin:8px 0 6px;">Analytics</h3>
        <p class="muted" style="margin:0;">Page views, product views, WhatsApp clicks, voucher claims
           and QR scans — all per tenant, with cross-tenant view for HQ.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">🎯</div>
        <h3 style="margin:8px 0 6px;">Lead pipeline</h3>
        <p class="muted" style="margin:0;">Assign leads to salespersons, track status
           (new → contacted → converted → closed), add notes per lead.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">🔎</div>
        <h3 style="margin:8px 0 6px;">SEO ready</h3>
        <p class="muted" style="margin:0;">Per-tenant Meta Title, Meta Description, Social Share
           Image. Open Graph + Twitter Cards + Schema.org LocalBusiness on Visit Us.</p>
      </div>

      <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:28px;">📱</div>
        <h3 style="margin:8px 0 6px;">Mobile-first</h3>
        <p class="muted" style="margin:0;">Sticky header, hamburger nav, fluid hero, 44 px touch
           targets, lazy-loaded images and a floating WhatsApp button on every page.</p>
      </div>

    </div>
  </div>
</section>

<section class="corp dark-cta">
  <div class="container">
    <h2>Want to see it in action?</h2>
    <p class="lead">Browse a live tenant site or talk to us about onboarding your brand.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Subscribe / Talk to us</a>
      <a class="btn outline-light" href="/how-it-works.php">How it works →</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
