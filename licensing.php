<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$page_title = 'Licensing & Partnership | AICAP Furniture BOS';
$page_desc  = 'White-label AICAP for groups running multiple brands, or refer customers and earn '
            . 'commission through our agency partnership program.';
$page_id    = 'licensing';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">Licensing &amp; Partnership</span>
    <h1>Run a chain.<br>Or refer one.</h1>
    <p>Two programs for partners who want to operate or grow with AICAP — licensing for multi-brand
       operators, and partnership for agencies and consultants.</p>
  </div>
</section>

<!-- LICENSING -->
<section class="corp">
  <div class="container">
    <div style="display:grid;gap:36px;grid-template-columns: 1fr;">
      <div>
        <span class="tag" style="display:inline-block;padding:4px 10px;border-radius:999px;background:#fff7ed;color:#b45309;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">For multi-brand operators</span>
        <h2 style="margin-top:10px;">Licensing program</h2>
        <p class="lead">Operate multiple furniture brands under one umbrella — your own holding company,
          franchise group or family of stores — on a single AICAP licence with cross-brand HQ analytics.</p>
      </div>

      <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🏢</div>
          <h3 style="margin:8px 0 6px;">Multi-brand under one account</h3>
          <p class="muted" style="margin:0;">Each brand gets its own subdomain, branding and isolated data.
             You see everything from a single HQ dashboard.</p>
        </div>

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🎨</div>
          <h3 style="margin:8px 0 6px;">White-label / co-branding</h3>
          <p class="muted" style="margin:0;">Optional white-label so the platform is your group's
             technology, not a vendor logo on your brands.</p>
        </div>

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">📊</div>
          <h3 style="margin:8px 0 6px;">Cross-brand analytics</h3>
          <p class="muted" style="margin:0;">Compare performance across brands at HQ level — leads,
             conversions, voucher claims and QR scans side by side.</p>
        </div>

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🛠️</div>
          <h3 style="margin:8px 0 6px;">Custom development</h3>
          <p class="muted" style="margin:0;">Bespoke integrations (POS, CRM, ERP, accounting) and
             group-specific reporting included in your licence.</p>
        </div>

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🤝</div>
          <h3 style="margin:8px 0 6px;">Dedicated onboarding</h3>
          <p class="muted" style="margin:0;">A team works with you to migrate existing catalogs,
             set up tenants, train staff and launch each brand.</p>
        </div>

        <div style="background:#f9fafb;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🛡️</div>
          <h3 style="margin:8px 0 6px;">SLA &amp; dedicated support</h3>
          <p class="muted" style="margin:0;">Priority support with response targets, named account
             manager, and quarterly reviews.</p>
        </div>

      </div>

      <div class="btn-row" style="margin-top:6px;">
        <a class="btn primary" href="/contact.php?type=licensing">Apply for a licence</a>
        <a class="btn outline" href="/pricing.php">See plans</a>
      </div>
    </div>
  </div>
</section>

<!-- PARTNERSHIP -->
<section class="corp alt">
  <div class="container">
    <div style="display:grid;gap:36px;grid-template-columns: 1fr;">
      <div>
        <span class="tag" style="display:inline-block;padding:4px 10px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">For agencies &amp; consultants</span>
        <h2 style="margin-top:10px;">Partnership program</h2>
        <p class="lead">If you work with furniture brands as a digital agency, marketing consultant or
           interior-design studio, refer them to AICAP and earn commission while we handle the platform.</p>
      </div>

      <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">

        <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">💸</div>
          <h3 style="margin:8px 0 6px;">Referral commission</h3>
          <p class="muted" style="margin:0;">Earn a share of recurring revenue for every brand you
             refer that signs up. Paid quarterly.</p>
        </div>

        <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">📣</div>
          <h3 style="margin:8px 0 6px;">Co-marketing</h3>
          <p class="muted" style="margin:0;">Co-branded launch announcements, shared case studies
             and a partner badge for your website.</p>
        </div>

        <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">🚀</div>
          <h3 style="margin:8px 0 6px;">Onboarding support</h3>
          <p class="muted" style="margin:0;">We help you set up your client's tenant and brief
             their team — no implementation lift on your side.</p>
        </div>

        <div style="background:#fff;padding:22px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:24px;">📚</div>
          <h3 style="margin:8px 0 6px;">Partner portal &amp; assets</h3>
          <p class="muted" style="margin:0;">Pitch decks, demo tenant access, FAQ and pricing sheets
             you can use with your clients.</p>
        </div>

      </div>

      <div class="btn-row" style="margin-top:6px;">
        <a class="btn dark" href="/contact.php?type=partner">Become a partner</a>
        <a class="btn outline" href="/about.php">About AICAP</a>
      </div>
    </div>
  </div>
</section>

<section class="corp dark-cta">
  <div class="container">
    <h2>Ready to grow with AICAP?</h2>
    <p class="lead">Whether you're a chain looking to license, or an agency looking to refer — we'd love to talk.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Get in touch</a>
      <a class="btn outline-light" href="/features.php">Explore features</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
