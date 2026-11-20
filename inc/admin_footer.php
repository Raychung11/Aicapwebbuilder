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
.upload-hint .ok { color: #16a34a; font-weight: 600; }
</style>
<script>
(function () {
  var MB         = 1024 * 1024;
  var MAX_FILE   = 5 * MB;
  var MAX_TOTAL  = 8 * MB;
  var SHRINK_AT  = 0.25 * MB; // compress anything over 250 KB
  var TARGET     = 250 * 1024; // try to land each image under 250 KB

  function fmt(b) {
    if (b < 1024) return b + ' B';
    if (b < MB)   return (b / 1024).toFixed(0) + ' KB';
    return (b / MB).toFixed(2) + ' MB';
  }

  // Compress once at the given maxDim + quality. Returns a Blob.
  function encodeOnce(file, maxDim, quality) {
    return new Promise(function (resolve) {
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        var scale = Math.min(1, maxDim / Math.max(w, h));
        w = Math.max(1, Math.round(w * scale));
        h = Math.max(1, Math.round(h * scale));
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        c.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', quality);
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
      img.src = url;
    });
  }

  // ---------- Iteratively compress until under TARGET ----------
  async function compressImage(file) {
    if (!file || !file.type || file.type.indexOf('image/') !== 0) return file;
    if (file.size < SHRINK_AT) return file;

    var attempts = [
      { dim: 1280, q: 0.82 },
      { dim: 1280, q: 0.72 },
      { dim: 1024, q: 0.70 },
      { dim:  900, q: 0.65 },
      { dim:  720, q: 0.60 },
      { dim:  540, q: 0.55 },
    ];

    var best = null;
    for (var i = 0; i < attempts.length; i++) {
      var blob = await encodeOnce(file, attempts[i].dim, attempts[i].q);
      if (!blob) continue;
      if (!best || blob.size < best.size) best = blob;
      console.log('[compress]', file.name, 'attempt', i + 1,
        attempts[i].dim + 'px q=' + attempts[i].q, '→', fmt(blob.size));
      if (blob.size <= TARGET) break;
    }
    if (!best || best.size >= file.size) {
      console.log('[compress] keeping original (no gain):', file.name, fmt(file.size));
      return file;
    }
    var name = (file.name || 'image').replace(/\.[^.]+$/, '') + '.jpg';
    console.log('[compress] final', file.name, fmt(file.size), '→', fmt(best.size));
    return new File([best], name, { type: 'image/jpeg', lastModified: Date.now() });
  }

  function replaceFiles(input, files) {
    try {
      var dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
      return true;
    } catch (e) {
      console.warn('[compress] DataTransfer unsupported', e);
      return false;
    }
  }

  function setStatus(input, html) {
    var hint = input.nextElementSibling;
    if (hint && hint.classList && hint.classList.contains('upload-hint')) {
      var status = hint.querySelector('.compress-status');
      if (!status) {
        status = document.createElement('span');
        status.className = 'compress-status';
        status.style.display = 'block';
        status.style.marginTop = '4px';
        hint.appendChild(status);
      }
      status.innerHTML = html;
    }
  }

  // Track in-flight compression per form; disable submit until clear.
  var pendingByForm = new WeakMap();
  function setBusy(form, delta) {
    var n = (pendingByForm.get(form) || 0) + delta;
    pendingByForm.set(form, n);
    form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) {
      b.disabled = n > 0;
      if (n > 0 && !b.dataset.origText) {
        b.dataset.origText = b.textContent;
        b.textContent = '⏳ Compressing…';
      } else if (n === 0 && b.dataset.origText) {
        b.textContent = b.dataset.origText;
        delete b.dataset.origText;
      }
    });
  }

  function attachCompress(input) {
    if (input.dataset.compressBound) return;
    input.dataset.compressBound = '1';
    var form = input.closest('form');
    input.addEventListener('change', async function () {
      var files = Array.prototype.slice.call(input.files || []);
      if (!files.length) return;
      if (form) setBusy(form, +1);
      var originalTotal = files.reduce(function (s, f) { return s + f.size; }, 0);
      setStatus(input, '⏳ Compressing ' + files.length + ' image' + (files.length > 1 ? 's' : '') + '… please wait.');
      var processed = [];
      for (var i = 0; i < files.length; i++) {
        try { processed.push(await compressImage(files[i])); }
        catch (e) { processed.push(files[i]); console.warn('[compress] error', e); }
      }
      var newTotal = processed.reduce(function (s, f) { return s + f.size; }, 0);
      var replaced = false;
      if (newTotal < originalTotal) {
        replaced = replaceFiles(input, processed);
      }
      if (replaced) {
        setStatus(input,
          '<span class="ok">✅ Compressed: ' + fmt(originalTotal) + ' → ' + fmt(newTotal) +
          '. Safe to save.</span>'
        );
      } else if (newTotal > MAX_TOTAL) {
        setStatus(input,
          '⚠️ Total still ' + fmt(newTotal) + ' — please pick smaller images.'
        );
      } else {
        setStatus(input, '<span class="ok">✅ Ready (' + fmt(newTotal) + '). Safe to save.</span>');
      }
      if (form) setBusy(form, -1);
    });
  }

  // Auto-inject hints + bind auto-compress to every existing file input.
  document.querySelectorAll('input[type=file]').forEach(function (inp) {
    if (!inp.dataset.noHint) {
      var next = inp.nextElementSibling;
      var hasHint = next && next.classList && next.classList.contains('upload-hint');
      if (!hasHint) {
        var hint = document.createElement('p');
        hint.className = 'upload-hint';
        hint.innerHTML =
          '📷 <strong>Auto-compressed to ~250 KB</strong> in your browser. ' +
          'Wait for the green ✅ before clicking Save.';
        inp.parentNode.insertBefore(hint, inp.nextSibling);
      }
    }
    attachCompress(inp);
  });

  // ---------- Final size guard on form submit ----------
  document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelector('input[type=file]')) return;

    form.addEventListener('submit', function (e) {
      // Block submit if compression is still running
      if ((pendingByForm.get(form) || 0) > 0) {
        e.preventDefault();
        alert('Still compressing — please wait a moment for the ✅ message before clicking Save.');
        return;
      }

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
          'These files are still larger than 5 MB after auto-compression:\n\n' +
          overs.join('\n') +
          '\n\nPick a smaller resolution photo.'
        );
        return;
      }

      if (total > MAX_TOTAL) {
        e.preventDefault();
        alert(
          'Total upload is ' + fmt(total) + ' (limit 8 MB per save).\n\n' +
          'Try uploading fewer images at a time.'
        );
        return;
      }
    });
  });
})();
</script>

</body>
</html>
