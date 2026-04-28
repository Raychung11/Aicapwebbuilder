<?php
/**
 * Admin chrome — footer + closing tags + sidebar drawer JS.
 */
?>
  </main>
</div>

<!-- Footer -->
<div class="adm-footer">
  <div>© <?= e(date('Y')) ?> AICAP Furniture BOS · v1.0</div>
  <div>
    <a href="/" target="_blank" rel="noopener">View public site ↗</a>
    <a href="/about.php" target="_blank" rel="noopener">About</a>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('sb-toggle');
  var bd  = document.getElementById('sb-backdrop');
  if (!btn) return;
  function close() { document.body.classList.remove('sb-open'); btn.setAttribute('aria-expanded','false'); }
  function open()  { document.body.classList.add('sb-open');    btn.setAttribute('aria-expanded','true'); }
  btn.addEventListener('click', function () {
    document.body.classList.contains('sb-open') ? close() : open();
  });
  if (bd) bd.addEventListener('click', close);
  // Close on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
  });
})();
</script>

</body>
</html>
