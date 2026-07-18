<?php
/**
 * AICAP corporate landing — included from index.php when no tenant
 * resolves (visiting aicap.my directly). Slim hero + value teaser
 * + featured tenants. Detailed content lives on the segment pages.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

$companies = db_all(
    'SELECT id, name, slug, subdomain, logo, theme_color
       FROM companies WHERE status = "active" ORDER BY name LIMIT 8'
);
$on_platform = (($_SERVER['HTTP_HOST'] ?? '') === APP_BASE_DOMAIN
              || ($_SERVER['HTTP_HOST'] ?? '') === 'www.' . APP_BASE_DOMAIN);

$page_title    = 'AICAP Furniture BOS — SaaS + Consulting for Malaysia\'s Furniture Industry';
$page_desc     = 'Multi-tenant SaaS and professional consulting for Malaysia\'s furniture industry. '
               . 'Every brand gets a branded site, e-catalog, vouchers, leads and analytics, '
               . 'backed by advisory services for business systems, process optimisation, '
               . 'and digital transformation.';
$page_id       = 'home';
$page_keywords = 'AICAP, AICAP Furniture BOS, furniture SaaS Malaysia, furniture consulting Malaysia, digital transformation furniture, multi-tenant furniture platform, furniture ERP CRM WMS, Malaysia furniture industry, furniture business operating system';

$_origin = (($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'aicap.my');

$page_faq = [
    [
        'What is AICAP Furniture BOS?',
        'AICAP Furniture BOS is a multi-tenant Business Operating System for Malaysia\'s furniture industry. Each licensed furniture brand gets its own branded subdomain running a full e-catalog, voucher system, lead capture, agent referral, package builder, blog and analytics — all managed from one platform.',
    ],
    [
        'Does AICAP Solution provide software only, or also consulting?',
        'Both. AICAP Solution runs the AICAP Furniture BOS SaaS platform and separately offers a Professional Consulting Package covering business requirements, system architecture, integration strategy, and an implementation roadmap. The two can be engaged together or independently.',
    ],
    [
        'Who is AICAP Furniture BOS built for?',
        'Furniture manufacturers, retailers, wholesalers, exporters, SME operators and multi-brand groups — especially companies moving from Excel and WhatsApp to structured digital operations.',
    ],
    [
        'Can I run multiple furniture brands on one account?',
        'Yes. The licensing program is designed for multi-brand operators — one licensee runs several tenant subdomains, each with its own branding, catalog, vouchers and analytics.',
    ],
    [
        'Does each brand need a technical team to run the platform?',
        'No. The tenant admin console handles catalog uploads, voucher setup, banners, showroom info and lead management without code. AICAP handles hosting, updates, backups and security.',
    ],
];

$page_jsonld = [
    // Product / SoftwareApplication schema for the SaaS
    [
        '@context'       => 'https://schema.org',
        '@type'          => 'SoftwareApplication',
        '@id'            => $_origin . '/#saas',
        'name'           => 'AICAP Furniture BOS',
        'description'    => 'Multi-tenant Business Operating System for furniture brands — branded tenant sites, e-catalog, vouchers, leads, analytics, agent referrals and AI chatbot.',
        'applicationCategory' => 'BusinessApplication',
        'applicationSubCategory' => 'ERP / CMS / CRM',
        'operatingSystem'=> 'Web',
        'provider'       => ['@id' => $_origin . '/#organization'],
        'audience'       => [
            '@type' => 'BusinessAudience',
            'audienceType' => 'Furniture manufacturers, retailers, wholesalers, exporters, SMEs',
        ],
        'featureList'    => [
            'Multi-tenant branded subdomains',
            'E-catalog with categories, subcategories and variants',
            'Voucher and loyalty system',
            'Agent referral tracking',
            'Package builder with retail-price calculation',
            'Showroom map with regional grouping',
            'Analytics dashboard',
            'AI product recommendation chatbot',
            'Blog module with AI drafting',
        ],
    ],
    // Service schema for consulting (mirrored so both are indexable from the landing page)
    [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        '@id'         => $_origin . '/#consulting',
        'serviceType' => 'Business system and digital transformation consulting',
        'name'        => 'Professional Consulting Package',
        'provider'    => ['@id' => $_origin . '/#organization'],
        'areaServed'  => ['@type' => 'Country', 'name' => 'Malaysia'],
        'description' => 'Advisory and implementation guidance covering business process assessment, requirements analysis, system architecture, integration strategy and roadmap delivery.',
        'url'         => $_origin . '/consulting.php',
    ],
];

require __DIR__ . '/corp_header.php';
?>

<!-- HERO -->
<section class="corp-hero">
  <div class="container">
    <span class="tag">Multi-tenant SaaS for Furniture Brands</span>
    <h1>One platform.<br>A branded website for every furniture company.</h1>
    <p>Each licensed brand gets its own subdomain, e-catalog, voucher system,
       lead capture and analytics — fully isolated, fully on-brand.</p>
    <div class="btn-row" style="margin-top:24px;">
      <a class="btn primary" href="/contact.php">Subscribe / Talk to us</a>
      <a class="btn outline-light" href="/features.php">Explore features →</a>
    </div>
  </div>
</section>

<!-- VALUE TEASER -->
<section class="corp">
  <div class="container">
    <h2>Built for furniture brands, end to end</h2>
    <p class="lead">Five product pillars cover the entire customer journey from discovery to showroom visit.</p>

    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));margin-top:26px;">
      <a href="/features.php" style="text-decoration:none;color:inherit;background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:24px;">🛋️</div>
        <h3 style="margin:8px 0 4px;">E-Catalog</h3>
        <p class="muted" style="margin:0;">Categories, subcategories, variants, featured items.</p>
      </a>
      <a href="/features.php" style="text-decoration:none;color:inherit;background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:24px;">🎁</div>
        <h3 style="margin:8px 0 4px;">Vouchers</h3>
        <p class="muted" style="margin:0;">One-step register &amp; claim with member loyalty.</p>
      </a>
      <a href="/features.php" style="text-decoration:none;color:inherit;background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:24px;">🎯</div>
        <h3 style="margin:8px 0 4px;">Lead Capture</h3>
        <p class="muted" style="margin:0;">WhatsApp clicks, voucher claims and QR scans, all tracked.</p>
      </a>
      <a href="/features.php" style="text-decoration:none;color:inherit;background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:24px;">📍</div>
        <h3 style="margin:8px 0 4px;">Showrooms</h3>
        <p class="muted" style="margin:0;">Branches with maps, Waze, photos, tap-to-call.</p>
      </a>
      <a href="/features.php" style="text-decoration:none;color:inherit;background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
        <div style="font-size:24px;">📊</div>
        <h3 style="margin:8px 0 4px;">Analytics</h3>
        <p class="muted" style="margin:0;">Page views, clicks, claims and scans per tenant.</p>
      </a>
    </div>
  </div>
</section>

<!-- PROFESSIONAL CONSULTING SERVICES -->
<section class="corp alt">
  <div class="container">
    <span class="tag" style="display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(15,23,42,.08);color:var(--bg);font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">
      Professional Services
    </span>
    <h2>Consulting &amp; advisory for digital transformation</h2>
    <p class="lead">
      Beyond software — AICAP Solution provides end-to-end consulting, advisory,
      planning and implementation support for business system development, process
      optimisation, and operational improvement.
    </p>

    <div style="display:grid;gap:22px;grid-template-columns: 1fr;margin-top:30px;">
      <style>
        @media (min-width: 900px) {
          .cons-grid { grid-template-columns: 1.1fr 1fr !important; }
        }
        .cons-col {
          background:#fff; border:1px solid #e5e7eb; border-radius:14px;
          padding:22px 24px;
        }
        .cons-col h3 { margin: 0 0 12px; font-size:17px; color: var(--bg); display:flex; align-items:center; gap:8px; }
        .cons-col ul { list-style: none; padding: 0; margin: 0; }
        .cons-col li {
          padding: 8px 0 8px 26px; position: relative; font-size: 15px;
          border-bottom: 1px solid #f3f4f6; color:#1f2937;
        }
        .cons-col li:last-child { border-bottom: 0; }
        .cons-col li::before {
          content: ""; position: absolute; left: 0; top: 14px;
          width: 16px; height: 16px; border-radius: 4px;
          background: rgba(245, 158, 11, .15);
          box-shadow: inset 0 0 0 1px var(--accent);
        }
        .cons-col li::after {
          content: "✓"; position: absolute; left: 3px; top: 10px;
          font-size: 11px; font-weight: 800; color: var(--accent);
        }
      </style>
      <div class="cons-grid" style="display:grid;gap:18px;grid-template-columns:1fr;">
        <div class="cons-col">
          <h3>🎯 Scope of services</h3>
          <ul>
            <li>Business process assessment</li>
            <li>Functional &amp; technical requirements analysis</li>
            <li>Workflow optimisation consultation</li>
            <li>System architecture advisory</li>
            <li>User experience review</li>
            <li>Data structure planning</li>
            <li>Integration framework consultation</li>
            <li>Project planning &amp; implementation guidance</li>
            <li>Fortnightly project review sessions</li>
          </ul>
        </div>
        <div class="cons-col">
          <h3>📦 Deliverables</h3>
          <ul>
            <li>Business Requirements Document (BRD)</li>
            <li>Process Flow Documentation</li>
            <li>System Architecture Recommendations</li>
            <li>Integration Strategy Report</li>
            <li>Implementation Roadmap</li>
          </ul>
          <div style="margin-top:18px;padding:14px 16px;background:#fef3c7;border-radius:10px;border:1px solid #fde68a;">
            <div style="font-size:12px;font-weight:700;color:#92400e;letter-spacing:.06em;text-transform:uppercase;margin-bottom:4px;">
              Engagement
            </div>
            <div style="color:#78350f;font-size:14px;line-height:1.5;">
              12-month renewable term ·
              milestone-based monthly invoicing ·
              fortnightly review cadence.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="btn-row" style="margin-top:24px;">
      <a class="btn dark" href="/consulting.php">Learn about consulting →</a>
      <a class="btn outline" href="/contact.php?type=consulting">Request a scoping call</a>
    </div>
  </div>
</section>

<!-- LIVE TENANTS -->
<?php if ($companies): ?>
<section class="corp alt">
  <div class="container">
    <h2>Brands already on AICAP</h2>
    <p class="lead">A few of the furniture companies running their site on AICAP today.</p>
    <div style="display:grid;gap:14px;grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));margin-top:22px;">
      <?php foreach ($companies as $co):
        $href = $on_platform
          ? 'https://' . $co['subdomain'] . '.' . APP_BASE_DOMAIN
          : '/?as=' . rawurlencode($co['slug']);
      ?>
        <a href="<?= e($href) ?>" <?= $on_platform ? 'target="_blank" rel="noopener"' : '' ?>
           style="display:flex;gap:12px;align-items:center;background:#fff;padding:14px;border-radius:10px;border:1px solid #e5e7eb;text-decoration:none;color:inherit;">
          <div style="width:42px;height:42px;border-radius:8px;background: <?= e($co['theme_color'] ?: '#1e293b') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;overflow:hidden;flex-shrink:0;">
            <?php if (!empty($co['logo'])): ?>
              <img src="<?= e($co['logo']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;background:#fff;padding:5px;">
            <?php else: ?>
              <?= e(strtoupper(substr($co['name'], 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div style="min-width:0;">
            <strong style="display:block;line-height:1.2;"><?= e($co['name']) ?></strong>
            <span class="muted" style="font-size:12px;"><?= e($co['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$on_platform): ?>
      <p class="muted" style="margin-top:18px;background:#fff7ed;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:6px;color:#7c2d12;">
        Preview URL detected. Click any tenant card to render it on this domain.
      </p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- FAQ (visible + FAQPage JSON-LD auto-emitted by corp_header) -->
<section class="corp" id="faq" itemscope itemtype="https://schema.org/FAQPage">
  <div class="container">
    <h2>Frequently asked questions</h2>
    <p class="lead">Short, direct answers so buyers (and AI answer engines) can quote them cleanly.</p>

    <div style="max-width:820px;margin-top:24px;">
      <?php foreach ($page_faq as $i => [$q, $a]):
        $open = $i < 2 ? ' open' : '';
      ?>
        <details<?= $open ?>
                 style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 20px;margin-bottom:10px;"
                 itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
          <summary style="font-weight:700;font-size:16px;cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;color:var(--bg);">
            <span itemprop="name"><?= e($q) ?></span>
            <span style="color:var(--accent);font-size:20px;line-height:1;">＋</span>
          </summary>
          <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"
               style="margin-top:10px;color:#374151;font-size:15px;line-height:1.6;">
            <div itemprop="text"><?= e($a) ?></div>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
    <style>
      details[open] > summary > span:last-child { transform: rotate(45deg); display:inline-block; }
      summary::-webkit-details-marker { display: none; }
    </style>
  </div>
</section>

<!-- DARK CTA -->
<section class="corp dark-cta">
  <div class="container">
    <h2>Ready to put your brand on AICAP?</h2>
    <p class="lead">Subscribe as a single tenant, or talk to us about our licensing &amp; partnership program.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php">Subscribe / partner with us</a>
      <a class="btn outline-light" href="/pricing.php">See pricing</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/corp_footer.php'; ?>
