<?php
/**
 * Public-site footer. Expects $company in scope.
 * Closes <body></html>.
 */
require_once __DIR__ . '/helpers.php';

$wa = $company['whatsapp_number'] ?? '';
?>
<?php if ($wa): ?>
<a class="fab-whatsapp" target="_blank" rel="noopener"
   href="<?= e(whatsapp_link($wa, 'Hi, I\'d like to know more.')) ?>"
   aria-label="Chat on WhatsApp">&#128172;</a>
<?php endif; ?>

<footer class="site">
  <div class="container">
    <div class="cols">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
          <?php if (!empty($company['logo'])): ?>
            <img src="<?= e($company['logo']) ?>" alt="<?= e($company['name']) ?>"
                 style="height:36px;background:#fff;padding:3px;border-radius:5px;">
          <?php endif; ?>
          <strong style="color:#fff"><?= e($company['name']) ?></strong>
        </div>
        <?php if (!empty($company['description'])): ?>
          <p style="margin:0;color:#94a3b8;line-height:1.5;"><?= e($company['description']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <h4>Explore</h4>
        <div class="links">
          <a href="/">Home</a>
          <a href="/catalog.php">Catalog</a>
          <a href="/packages.php">Packages</a>
          <a href="/voucher.php">Vouchers</a>
          <a href="/promo.php">Promo</a>
          <a href="/lookbook.php">Lookbook</a>
          <a href="/visit.php">Visit Us</a>
          <a href="/#about">About</a>
        </div>
      </div>

      <div>
        <h4>Account</h4>
        <div class="links">
          <a href="/member-login.php">Member Login</a>
          <a href="/member-register.php">Register</a>
          <a href="/agent.php">Agent Login</a>
          <a href="/company-admin/login.php">Tenant / Staff Login</a>
        </div>
      </div>

      <div>
        <h4>Contact</h4>
        <div class="links">
          <?php if (!empty($company['address'])): ?><div><?= e($company['address']) ?></div><?php endif; ?>
          <?php if (!empty($company['phone'])):   ?><a href="tel:<?= e($company['phone']) ?>">📞 <?= e($company['phone']) ?></a><?php endif; ?>
          <?php if (!empty($company['email'])):   ?><a href="mailto:<?= e($company['email']) ?>">✉️ <?= e($company['email']) ?></a><?php endif; ?>
          <?php if ($wa): ?>
            <a target="_blank" rel="noopener"
               href="<?= e(whatsapp_link($wa)) ?>">💬 WhatsApp</a>
          <?php endif; ?>
          <?php if (!empty($company['operating_hours'])): ?>
            <div>⏰ <?= e($company['operating_hours']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="copy">
      © <?= date('Y') ?> <?= e($company['name']) ?>. Powered by <?= e(APP_NAME) ?>.
    </div>
  </div>
</footer>

<?php require __DIR__ . '/chatbot.php'; ?>

</body></html>
