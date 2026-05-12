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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
  });
})();
</script>

<!-- Upload size guard: block oversized POSTs *before* they reach nginx,
     and auto-inject a "Max 5 MB / 8 MB total" hint under every file input -->
<style>
.upload-hint {
  margin: 6px 0 0; padding: 0;
  font-size: 12px; color: #6b7280; line-height: 1.45;
}
.upload-hint a { color: #2563eb; text-decoration: none; }
.upload-hint a:hover { text-decoration: underline; }
</style>
<script>
(function () {
  var MB         = 1024 * 1024;
  var MAX_FILE   = 5 * MB;   // matches UPLOAD_MAX_BYTES
  var MAX_TOTAL  = 8 * MB;   // matches UPLOAD_MAX_TOTAL_BYTES / nginx default

  function fmt(b) { return (b / MB).toFixed(1) + ' MB'; }

  // Auto-inject the size hint under every file input on admin pages.
  document.querySelectorAll('input[type=file]').forEach(function (inp) {
    if (inp.dataset.noHint) return;
    var next = inp.nextElementSibling;
    if (next && next.classList && next.classList.contains('upload-hint')) return;
    var hint = document.createElement('p');
    hint.className = 'upload-hint';
    hint.innerHTML =
      '📷 <strong>Max 5 MB per file, 8 MB total per save.</strong> ' +
      'Compress large photos with ' +
      '<a href="https://tinypng.com" target="_blank" rel="noopener">tinypng.com</a> ' +
      'or <a href="https://squoosh.app" target="_blank" rel="noopener">squoosh.app</a> first.';
    inp.parentNode.insertBefore(hint, inp.nextSibling);
  });

  document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelector('input[type=file]')) return;

    form.addEventListener('submit', function (e) {
      var total = 0;
      var overs = [];
      form.querySelectorAll('input[type=file]').forEach(function (inp) {
        var files = inp.files || [];
        for (var i = 0; i < files.length; i++) {
          var f = files[i];
          if (f.size > MAX_FILE) overs.push(f.name + ' (' + fmt(f.size) + ')');
          total += f.size;
        }
      });

      if (overs.length) {
        e.preventDefault();
        alert(
          'These files are larger than 5 MB and will be rejected:\n\n' +
          overs.join('\n') +
          '\n\nPlease compress them first (try tinypng.com or squoosh.app) ' +
          'and try again.'
        );
        return;
      }

      if (total > MAX_TOTAL) {
        e.preventDefault();
        alert(
          'Your total upload is ' + fmt(total) + ', which exceeds the 8 MB ' +
          'limit per save (the server will refuse it with a 405).\n\n' +
          'Try uploading fewer images at a time, or compress them so the ' +
          'total stays under 8 MB.'
        );
        return;
      }
    });
  });
})();
</script>

</body>
</html>
