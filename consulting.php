<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$page_title = 'Professional Consulting Services | AICAP Solution';
$page_desc  = 'AICAP Solution provides professional consulting, advisory, planning and '
            . 'implementation support for business system development, process optimisation, '
            . 'digital transformation initiatives and operational improvement projects.';
$page_id    = 'consulting';
require __DIR__ . '/inc/corp_header.php';
?>

<style>
  .cs-two { display:grid; gap:22px; grid-template-columns:1fr; }
  @media (min-width: 900px) { .cs-two { grid-template-columns: 1.1fr 1fr; } }
  .cs-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:14px;
    padding:24px 26px;
  }
  .cs-card h3 { margin:0 0 14px; font-size:19px; color: var(--bg); display:flex; align-items:center; gap:10px; }
  .cs-list { list-style: none; padding:0; margin:0; }
  .cs-list li {
    padding: 10px 0 10px 28px; position: relative; font-size: 15px;
    border-bottom: 1px solid #f3f4f6; color:#1f2937; line-height:1.5;
  }
  .cs-list li:last-child { border-bottom: 0; }
  .cs-list li::before {
    content:""; position: absolute; left: 0; top: 14px;
    width: 18px; height: 18px; border-radius: 5px;
    background: rgba(245, 158, 11, .15);
    box-shadow: inset 0 0 0 1px var(--accent);
  }
  .cs-list li::after {
    content:"✓"; position: absolute; left: 4px; top: 10px;
    font-size: 12px; font-weight: 800; color: var(--accent);
  }
  .cs-num { display:grid; gap:20px; grid-template-columns: 1fr; }
  @media (min-width: 640px) { .cs-num { grid-template-columns: repeat(2, 1fr); } }
  @media (min-width: 980px) { .cs-num { grid-template-columns: repeat(4, 1fr); } }
  .cs-step {
    background: #f9fafb; border:1px solid #e5e7eb; border-radius: 12px;
    padding: 20px; position: relative;
  }
  .cs-step .n {
    display:inline-flex; align-items:center; justify-content:center;
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--bg); color: var(--accent);
    font-weight: 800; margin-bottom: 10px;
  }
  .cs-step h4 { margin: 0 0 6px; font-size: 15px; color: var(--bg); }
  .cs-step p  { margin: 0; color: #4b5563; font-size: 13.5px; line-height: 1.55; }
  .cs-price {
    background: linear-gradient(135deg, var(--bg), #1e293b); color:#fff;
    border-radius: 16px; padding: 28px 30px; margin-top: 28px;
    display: grid; gap: 20px; grid-template-columns: 1fr;
  }
  @media (min-width: 720px) { .cs-price { grid-template-columns: 1.4fr 1fr; align-items: center; } }
  .cs-price h3 { color:#fff; margin: 0 0 6px; font-size: 22px; }
  .cs-price p  { color:#cbd5e1; margin: 0; }
  .cs-price .band {
    font-size: 12px; font-weight: 700; letter-spacing:.06em; text-transform: uppercase;
    color: var(--accent);
  }
  .cs-facts {
    display:grid; gap: 8px; padding: 16px 18px;
    background: rgba(255,255,255,.06); border-radius: 12px; font-size: 14px;
  }
  .cs-facts b { color:#fff; }
  .cs-facts .row { display:flex; justify-content:space-between; gap:12px; color: #cbd5e1; }
</style>

<section class="corp-hero">
  <div class="container">
    <span class="tag">Professional Services</span>
    <h1>Consulting &amp; advisory for<br>digital transformation.</h1>
    <p>End-to-end consulting, advisory, planning and implementation support for business
       system development, process optimisation, and operational improvement projects —
       delivered by the AICAP Solution team.</p>
    <div class="btn-row" style="margin-top:24px;">
      <a class="btn primary" href="/contact.php?type=consulting">Request a scoping call</a>
      <a class="btn outline-light" href="#package">See the package →</a>
    </div>
  </div>
</section>

<!-- CORE SCOPE + DELIVERABLES -->
<section class="corp" id="package">
  <div class="container">
    <span class="tag" style="display:inline-block;padding:4px 10px;border-radius:999px;background:#fff7ed;color:#b45309;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">Professional Consulting Package</span>
    <h2 style="margin-top:10px;">What's in the engagement</h2>
    <p class="lead">
      A structured 12-month engagement combining hands-on advisory, documentation
      artefacts, and a fortnightly review cadence to keep the roadmap moving.
    </p>

    <div class="cs-two" style="margin-top:26px;">
      <div class="cs-card">
        <h3>🎯 Scope of services</h3>
        <ul class="cs-list">
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
      <div class="cs-card">
        <h3>📦 Deliverables</h3>
        <ul class="cs-list">
          <li>Business Requirements Document (BRD)</li>
          <li>Process Flow Documentation</li>
          <li>System Architecture Recommendations</li>
          <li>Integration Strategy Report</li>
          <li>Implementation Roadmap</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- HOW WE WORK -->
<section class="corp alt">
  <div class="container">
    <h2>How we work</h2>
    <p class="lead">A phased approach that lets us learn your operations first, then design the roadmap, then help you execute.</p>

    <div class="cs-num" style="margin-top:28px;">
      <div class="cs-step">
        <div class="n">1</div>
        <h4>Discover</h4>
        <p>Assess current processes, systems, data and pain points across the business.</p>
      </div>
      <div class="cs-step">
        <div class="n">2</div>
        <h4>Design</h4>
        <p>Translate business goals into a BRD, workflow, data structure, and architecture.</p>
      </div>
      <div class="cs-step">
        <div class="n">3</div>
        <h4>Plan</h4>
        <p>Sequence the initiatives into a realistic implementation roadmap with milestones.</p>
      </div>
      <div class="cs-step">
        <div class="n">4</div>
        <h4>Guide</h4>
        <p>Fortnightly reviews with your team to unblock, adjust scope, and drive delivery.</p>
      </div>
    </div>
  </div>
</section>

<!-- WHO IT'S FOR -->
<section class="corp">
  <div class="container">
    <h2>Who it's for</h2>
    <p class="lead">Companies stepping past ad-hoc tools into structured business systems — usually running on Excel, WhatsApp and disconnected apps today.</p>
    <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));margin-top:26px;">
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
        <div style="font-size:22px;">🏭</div>
        <h3 style="margin:8px 0 4px;font-size:15px;">Furniture manufacturers</h3>
        <p class="muted" style="margin:0;">Digitalising sales orders, WMS, dealer &amp; supplier portals.</p>
      </div>
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
        <div style="font-size:22px;">🛒</div>
        <h3 style="margin:8px 0 4px;font-size:15px;">Retailers &amp; wholesalers</h3>
        <p class="muted" style="margin:0;">CRM, showroom lead tracking, marketplace integration.</p>
      </div>
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
        <div style="font-size:22px;">📦</div>
        <h3 style="margin:8px 0 4px;font-size:15px;">SME operators</h3>
        <p class="muted" style="margin:0;">Owner-led businesses that want a structured transformation plan.</p>
      </div>
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
        <div style="font-size:22px;">🌐</div>
        <h3 style="margin:8px 0 4px;font-size:15px;">Group holdings</h3>
        <p class="muted" style="margin:0;">Multi-brand groups aligning systems, data and reporting.</p>
      </div>
    </div>
  </div>
</section>

<!-- ENGAGEMENT & COMMERCIALS -->
<section class="corp alt">
  <div class="container">
    <h2>Engagement terms</h2>
    <p class="lead">Milestone-based monthly invoicing so budgets align with real delivery.</p>

    <div class="cs-price">
      <div>
        <div class="band">Professional Consulting Package</div>
        <h3>12-month advisory engagement</h3>
        <p>Renewable by mutual written agreement. Actual monthly fees vary with the scope
           and milestones performed for the respective billing period.</p>
        <div class="btn-row" style="margin-top:16px;">
          <a class="btn primary" href="/contact.php?type=consulting">Request a scoping call</a>
          <a class="btn outline-light" href="/features.php">Explore the platform</a>
        </div>
      </div>
      <div class="cs-facts">
        <div class="row"><span>Term</span><b>12 months</b></div>
        <div class="row"><span>Review cadence</span><b>Fortnightly</b></div>
        <div class="row"><span>Billing</span><b>Monthly milestones</b></div>
        <div class="row"><span>Monthly range</span><b>RM 10k – 40k</b></div>
        <div class="row"><span>Annual cap</span><b>RM 300k</b></div>
      </div>
    </div>

    <p class="muted" style="margin-top:14px;font-size:12px;">
      Indicative commercials from AICAP Solution's standard Professional Consulting Package.
      Final scope, deliverables and fees are agreed per engagement in Appendix A —
      Services Milestones &amp; Deliverable Schedule.
    </p>
  </div>
</section>

<!-- CTA -->
<section class="corp dark-cta">
  <div class="container">
    <h2>Ready to scope your engagement?</h2>
    <p class="lead">Tell us where your business is today. We'll come back with a fit assessment and a proposed milestone plan.</p>
    <div class="btn-row" style="margin-top:18px;">
      <a class="btn primary" href="/contact.php?type=consulting">Book a discovery call</a>
      <a class="btn outline-light" href="/about.php">About AICAP Solution</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
