<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';

if (current_company()) { redirect('/'); }

$err = '';
$submitted = false;

$type_options = [
    'subscribe'  => 'Subscribe (single brand)',
    'licensing'  => 'Licensing (multi-brand)',
    'partner'    => 'Partnership (agency / consultant)',
    'consulting' => 'Consulting (advisory & implementation)',
    'general'    => 'General enquiry',
];
$default_type = (string) input('type', 'subscribe');
if (!isset($type_options[$default_type])) $default_type = 'subscribe';
$prefill_plan = (string) input('plan', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $type         = (string) input('type', 'subscribe');
    if (!isset($type_options[$type])) $type = 'general';
    $company_name = trim((string) input('company_name'));
    $contact_name = trim((string) input('contact_name'));
    $email        = trim((string) input('email'));
    $phone        = trim((string) input('phone'));
    $brand_name   = trim((string) input('brand_name'));
    $branches     = trim((string) input('branches_count'));
    $message      = trim((string) input('message'));

    if ($company_name === '' || $contact_name === '' || $email === '') {
        $err = 'Please fill in your company, name and email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } elseif (mb_strlen($message) > 4000) {
        $err = 'Message is too long.';
    } else {
        try {
            db_insert(
                'INSERT INTO partner_inquiries
                   (type, company_name, contact_name, email, phone, brand_name,
                    branches_count, message, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $type, $company_name, $contact_name, $email, $phone ?: null,
                    $brand_name ?: null, $branches ?: null, $message ?: null,
                    client_ip(),
                ]
            );
            $submitted = true;
        } catch (Throwable $e) {
            $err = 'Sorry, we could not save your enquiry. Please email hello@aicap.my.';
        }
    }
}

$page_title = 'Contact | AICAP Furniture BOS';
$page_desc  = 'Talk to AICAP about subscribing, licensing or partnering. We typically reply within '
            . '1 business day.';
$page_id    = 'contact';
require __DIR__ . '/inc/corp_header.php';
?>

<section class="corp-hero">
  <div class="container">
    <span class="tag">Contact</span>
    <h1>Tell us about<br>your brand.</h1>
    <p>We typically reply within 1 business day. Use the form below — or message us on
       WhatsApp / email if that's easier.</p>
  </div>
</section>

<section class="corp">
  <div class="container">
    <div style="display:grid;gap:32px;grid-template-columns: 1fr;">
      <div style="display:grid;gap:32px;grid-template-columns: 1fr;">
        <?php if ($submitted): ?>
          <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:22px;border-radius:12px;">
            <h2 style="margin:0 0 6px;color:#166534;">Thanks — we got it.</h2>
            <p class="lead" style="color:#166534;margin:0;">Our team will reach out at the email
               you provided within 1 business day.</p>
            <div class="btn-row" style="margin-top:16px;">
              <a class="btn dark" href="/">Back to home</a>
              <a class="btn outline" href="/features.php">Explore features</a>
            </div>
          </div>
        <?php else: ?>
          <form method="post" style="background:#fff;padding:26px;border-radius:14px;border:1px solid #e5e7eb;display:grid;gap:14px;">
            <?= csrf_field() ?>
            <?php if ($err): ?>
              <div class="alert error">⚠️ <?= e($err) ?></div>
            <?php endif; ?>

            <div>
              <label style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">I'm interested in</label>
              <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
                <?php foreach ($type_options as $val => $lbl): ?>
                  <label style="border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;cursor:pointer;display:flex;gap:8px;align-items:center;font-size:14px;<?= $val === $default_type ? 'border-color:var(--bg);background:#f9fafb;' : '' ?>">
                    <input type="radio" name="type" value="<?= e($val) ?>" <?= $val === $default_type ? 'checked' : '' ?>>
                    <?= e($lbl) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Company / Group name *</span>
                <input class="input" name="company_name" required value="<?= e($_POST['company_name'] ?? '') ?>" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;">
              </label>
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Brand name <span class="muted">(if different)</span></span>
                <input class="input" name="brand_name" value="<?= e($_POST['brand_name'] ?? '') ?>" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;">
              </label>
            </div>

            <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Your name *</span>
                <input class="input" name="contact_name" required value="<?= e($_POST['contact_name'] ?? '') ?>" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;">
              </label>
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Email *</span>
                <input class="input" name="email" type="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;">
              </label>
            </div>

            <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Phone / WhatsApp</span>
                <input class="input" name="phone" type="tel" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;">
              </label>
              <label style="display:block;">
                <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">How many showrooms / brands?</span>
                <select name="branches_count" class="input" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;background:#fff;">
                  <option value="">Select…</option>
                  <option>1 showroom</option>
                  <option>2-5 showrooms</option>
                  <option>6-15 showrooms</option>
                  <option>15+ showrooms</option>
                  <option>Multi-brand group</option>
                </select>
              </label>
            </div>

            <label style="display:block;">
              <span style="display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:500;">Tell us a bit more <span class="muted">(optional)</span></span>
              <textarea name="message" rows="4" maxlength="4000" placeholder="Brief about your brand, timing, anything we should know…" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;"><?= e($_POST['message'] ?? ($prefill_plan ? "I'm interested in the " . ucfirst($prefill_plan) . " plan." : '')) ?></textarea>
            </label>

            <div class="btn-row" style="margin-top:6px;">
              <button class="btn primary" type="submit">Send enquiry →</button>
              <a class="btn outline" href="/pricing.php">See pricing first</a>
            </div>

            <p class="muted" style="margin:8px 0 0;font-size:12px;">
              By submitting you agree we'll contact you about your enquiry. We don't share your details.
            </p>
          </form>
        <?php endif; ?>
      </div>

      <!-- Other ways -->
      <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div style="background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:22px;">✉️</div>
          <strong>Email</strong>
          <p class="muted" style="margin:4px 0 0;">
            <a href="mailto:hello@aicap.my">hello@aicap.my</a>
          </p>
        </div>
        <div style="background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:22px;">💬</div>
          <strong>WhatsApp</strong>
          <p class="muted" style="margin:4px 0 0;">
            <a href="https://wa.me/60123456789?text=Hi%20AICAP" target="_blank" rel="noopener">+60 12-345 6789</a>
          </p>
        </div>
        <div style="background:#f9fafb;padding:18px;border-radius:12px;border:1px solid #e5e7eb;">
          <div style="font-size:22px;">⏱️</div>
          <strong>Response time</strong>
          <p class="muted" style="margin:4px 0 0;">Within 1 business day, Mon–Fri.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/corp_footer.php'; ?>
