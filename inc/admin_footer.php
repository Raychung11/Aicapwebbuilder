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
  var SHRINK_AT  = 0.5 * MB;  // compress anything over 500 KB
  var MAX_WIDTH  = 1280;      // longest edge after resize
  var JPEG_Q     = 0.78;

  function fmt(b) { return (b / MB).toFixed(2) + ' MB'; }

  // ---------- Auto-compress image files in the browser ----------
  function compressImage(file) {
    return new Promise(function (resolve) {
      if (!file || !file.type || file.type.indexOf('image/') !== 0) return resolve(file);
      if (file.size < SHRINK_AT) return resolve(file);

      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        var scale = Math.min(1, MAX_WIDTH / Math.max(w, h));
        w = Math.max(1, Math.round(w * scale));
        h = Math.max(1, Math.round(h * scale));
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        c.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) {
            console.log('[compress] skipped (no gain):', file.name, file.size);
            return resolve(file);
          }
          console.log('[compress]', file.name,
            (file.size / MB).toFixed(2), 'MB →',
            (blob.size / MB).toFixed(2), 'MB');
          var name = (file.name || 'image').replace(/\.[^.]+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', JPEG_Q);
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        console.warn('[compress] decode failed for', file.name);
        resolve(file);
      };
      img.src = url;
    });
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
      setStatus(input, '⏳ Compressing image' + (files.length > 1 ? 's' : '') + '… please wait.');
      var processed = [];
      for (var i = 0; i < files.length; i++) {
        try { processed.push(await compressImage(files[i])); }
        catch (e) { processed.push(files[i]); console.warn('[compress] error', e); }
      }
      var newTotal = processed.reduce(function (s, f) { return s + f.size; }, 0);
      if (newTotal < originalTotal && replaceFiles(input, processed)) {
        var savedMB = ((originalTotal - newTotal) / MB).toFixed(2);
        setStatus(input,
          '<span class="ok">✅ Compressed — saved ' + savedMB + ' MB. ' +
          'New total: ' + fmt(newTotal) + '. You can save now.</span>'
        );
      } else if (newTotal > MAX_TOTAL) {
        setStatus(input,
          '⚠️ Total still ' + fmt(newTotal) + ' — please pick smaller images.'
        );
      } else {
        setStatus(input, '<span class="ok">✅ Ready (' + fmt(newTotal) + '). You can save now.</span>');
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
          '📷 <strong>Max 5 MB per file, 8 MB total per save.</strong> ' +
          'Photos are <strong>auto-compressed in your browser</strong> ' +
          '— wait for the green ✅ before clicking Save.';
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
          '\n\nThe images are unusually large. Pick a smaller resolution photo.'
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
