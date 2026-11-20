<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

// Reused slugifier
if (!function_exists('slugify')) {
    function slugify(string $t): string {
        $t = strtolower(trim($t));
        $t = preg_replace('/[^a-z0-9]+/', '-', $t);
        return trim($t, '-') ?: 'scene';
    }
}

$id      = (int) input('id', 0);
$editing = $id ? tenant_row_or_404('lookbook_scenes', $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Featured/scene image upload
    $image_path = $editing['image_path'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $url = save_upload($_FILES['image'], $CID, 'lookbook');
        if ($url) {
            if ($image_path) {
                $abs = __DIR__ . '/..' . $image_path;
                if (is_file($abs)) @unlink($abs);
            }
            $image_path = $url;
        } else {
            flash_set('error', 'Could not save the image. Use JPG/PNG/WEBP up to 5 MB.');
        }
    } elseif ($id && input('remove_image') === '1') {
        if ($image_path) {
            $abs = __DIR__ . '/..' . $image_path;
            if (is_file($abs)) @unlink($abs);
        }
        $image_path = null;
    }

    $title       = trim((string) input('title', '')) ?: 'Untitled scene';
    $slug_input  = trim((string) input('slug', ''));
    $slug        = slugify($slug_input !== '' ? $slug_input : $title);
    // ensure slug uniqueness per company
    $base = $slug; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM lookbook_scenes WHERE company_id = ? AND slug = ?';
        $params = [$CID, $slug];
        if ($id) { $sql .= ' AND id != ?'; $params[] = $id; }
        if (!db_one($sql, $params)) break;
        $slug = $base . '-' . (++$i);
    }
    $description = trim((string) input('description', '')) ?: null;
    $cover_alt   = trim((string) input('cover_alt', '')) ?: null;
    $status      = in_array(input('status'), ['active','draft','disabled'], true) ? input('status') : 'active';
    $sort        = (int) input('sort_order', 0);

    if ($id) {
        db_exec(
            'UPDATE lookbook_scenes
                SET title=?, slug=?, description=?, image_path=?, cover_alt=?,
                    status=?, sort_order=?
              WHERE company_id=? AND id=?',
            [$title, $slug, $description, $image_path, $cover_alt, $status, $sort, $CID, $id]
        );
    } else {
        $id = db_insert(
            'INSERT INTO lookbook_scenes
              (company_id, title, slug, description, image_path, cover_alt, status, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, $title, $slug, $description, $image_path, $cover_alt, $status, $sort]
        );
    }

    // Hotspots — replace the whole set from the submitted JSON.
    $hotspots_json = (string) input('hotspots_json', '[]');
    $hotspots = json_decode($hotspots_json, true) ?: [];
    db_exec('DELETE FROM lookbook_hotspots WHERE company_id = ? AND scene_id = ?', [$CID, $id]);
    $ord = 0;
    foreach ($hotspots as $h) {
        $pid = (int) ($h['product_id'] ?? 0);
        $x   = (float) ($h['x'] ?? 0);
        $y   = (float) ($h['y'] ?? 0);
        if ($pid <= 0) continue;
        if ($x < 0 || $x > 100 || $y < 0 || $y > 100) continue;
        // confirm product belongs to this company
        $pcheck = db_one('SELECT id FROM products WHERE company_id = ? AND id = ?', [$CID, $pid]);
        if (!$pcheck) continue;
        $label = isset($h['label']) ? trim((string) $h['label']) : null;
        if ($label === '') $label = null;
        db_insert(
            'INSERT INTO lookbook_hotspots
              (company_id, scene_id, product_id, x_pct, y_pct, label, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$CID, $id, $pid, round($x, 2), round($y, 2), $label, $ord++]
        );
    }

    flash_set('success', 'Scene saved.');
    redirect('/company-admin/lookbook-edit.php?id=' . $id);
}

// Fetch existing hotspots for the editor
$existing = $id ? tenant_all(
    'SELECT h.id, h.product_id, h.x_pct, h.y_pct, h.label,
            p.name AS product_name, p.price_min, p.price_max,
            (SELECT image_path FROM product_images
              WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS product_img
       FROM lookbook_hotspots h
       JOIN products p ON p.id = h.product_id
      WHERE h.company_id = ? AND h.scene_id = ?
      ORDER BY h.sort_order',
    $CID, [$id]
) : [];

// All the tenant's active products for the picker
$products = tenant_all(
    'SELECT p.id, p.name, p.category, p.subcategory, p.price_min, p.price_max,
            (SELECT image_path FROM product_images
              WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
       FROM products p
      WHERE p.company_id = ? AND p.status = "active"
      ORDER BY p.name',
    $CID
);

// Pre-serialize hotspots for the editor state
$editor_hotspots = array_map(function ($h) {
    return [
        'product_id'   => (int) $h['product_id'],
        'product_name' => $h['product_name'],
        'product_img'  => $h['product_img'],
        'x'            => (float) $h['x_pct'],
        'y'            => (float) $h['y_pct'],
        'label'        => $h['label'] ?? '',
    ];
}, $existing);

ca_open($editing ? 'Edit Scene · ' . $editing['title'] : 'New Lookbook Scene');
?>

<style>
  .lb-grid { display:grid; gap:16px; grid-template-columns:1fr; }
  @media (min-width: 1100px) { .lb-grid { grid-template-columns: 2fr 1fr; } }
  .lb-canvas {
    position: relative; background: #0b1020;
    border-radius: 12px; overflow: hidden; user-select: none;
    aspect-ratio: 16 / 10;
    display: flex; align-items: center; justify-content: center;
  }
  .lb-canvas .empty { color: #64748b; text-align: center; padding: 30px; font-size: 14px; }
  .lb-canvas img { display:block; width:100%; height:100%; object-fit: contain; pointer-events: none; }
  .lb-pin {
    position: absolute; transform: translate(-50%, -50%);
    width: 30px; height: 30px; border-radius: 999px;
    background: var(--c-primary, #f59e0b); color:#111;
    border: 3px solid #fff; box-shadow: 0 4px 14px rgba(0,0,0,.4);
    display:flex; align-items:center; justify-content:center;
    font-weight: 800; font-size: 13px; cursor: grab;
    z-index: 5;
  }
  .lb-pin:hover { transform: translate(-50%, -50%) scale(1.08); }
  .lb-pin.dragging { cursor: grabbing; z-index: 6; }
  .lb-canvas.placing { cursor: crosshair; }
  .lb-canvas.placing::after {
    content: 'Click on the image to drop a pin for the selected product';
    position: absolute; top: 10px; left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,.7); color:#fff; padding: 6px 12px; border-radius: 6px;
    font-size: 12px; z-index: 10; pointer-events: none;
  }

  .lb-picker { max-height: 460px; overflow: auto; }
  .lb-prod {
    display: flex; align-items: center; gap: 10px; padding: 8px 10px;
    border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 6px;
    cursor: pointer; background: #fff;
  }
  .lb-prod:hover { background: #f9fafb; border-color: #cbd5e1; }
  .lb-prod.selected { background:#fef3c7; border-color:#f59e0b; }
  .lb-prod .thumb {
    width: 44px; height: 44px; border-radius: 6px; background:#eee;
    object-fit: cover; flex-shrink: 0;
  }
  .lb-prod .thumb-ph { width:44px; height:44px; border-radius:6px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; font-size:11px; color:#9ca3af; flex-shrink:0; }
  .lb-prod .meta { min-width: 0; flex: 1; }
  .lb-prod .meta strong { display:block; font-size:13px; line-height:1.3; }
  .lb-prod .meta .cat { color:#6b7280; font-size:11px; margin-top:2px; }

  .lb-pins-list { max-height: 300px; overflow: auto; margin-top: 8px; }
  .lb-pins-list .item {
    display:flex; align-items:center; gap:10px; padding:6px 10px;
    border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 6px;
    background:#fff; font-size: 13px;
  }
  .lb-pins-list .item .n {
    background:#f59e0b; color:#111; width:22px; height:22px; border-radius:50%;
    display:flex;align-items:center;justify-content:center; font-weight:800; font-size:11px; flex-shrink:0;
  }
  .lb-pins-list .item .name { flex:1; min-width:0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .lb-pins-list .item button {
    border:0; background:#fee2e2; color:#991b1b; padding:2px 8px;
    border-radius:6px; cursor:pointer; font-size: 11px; font-weight: 600;
  }
</style>

<form method="post" enctype="multipart/form-data" id="lb-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
  <input type="hidden" name="hotspots_json" id="hotspots_json" value="">

  <div class="card">
    <div class="row">
      <div class="col" style="flex:2;min-width:220px;">
        <label>Scene title</label>
        <input class="input" name="title" required
               placeholder="e.g. Modern Family Living Room"
               value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Slug <span class="muted">(auto from title)</span></label>
        <input class="input" name="slug" placeholder="modern-family-living-room"
               value="<?= e($editing['slug'] ?? '') ?>">
      </div>
      <div class="col" style="max-width:150px;">
        <label>Status</label>
        <select class="input" name="status">
          <?php foreach (['active'=>'Active','draft'=>'Draft','disabled'=>'Disabled'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($editing['status'] ?? 'active')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col" style="max-width:120px;">
        <label>Sort</label>
        <input class="input" type="number" name="sort_order"
               value="<?= (int)($editing['sort_order'] ?? 0) ?>">
      </div>
    </div>

    <label>Description <span class="muted">(shown under the scene)</span></label>
    <textarea class="input" name="description" rows="2"
              placeholder="e.g. A tropical resort look with our Kelana sofa and Ipoh coffee table."><?= e($editing['description'] ?? '') ?></textarea>

    <div class="row">
      <div class="col">
        <label>Scene image</label>
        <input type="file" name="image" accept="image/*" id="lb-image-input">
        <p class="muted" style="font-size:12px;margin-top:4px;">JPG/PNG/WEBP up to 5 MB. Recommended 1600 × 1000 or 16:10.</p>
      </div>
      <div class="col">
        <label>Alt text <span class="muted">(accessibility + SEO)</span></label>
        <input class="input" name="cover_alt"
               placeholder="Modern living room with Kelana sofa"
               value="<?= e($editing['cover_alt'] ?? '') ?>">
      </div>
      <?php if (!empty($editing['image_path'])): ?>
      <div class="col" style="max-width:200px;">
        <label>Current image</label>
        <label style="display:flex;align-items:center;gap:6px;margin:8px 0 0;font-size:13px;">
          <input type="checkbox" name="remove_image" value="1"> Remove
        </label>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h3 style="margin:0 0 6px;">📍 Place your pins</h3>
    <p class="muted" style="margin:0 0 12px;font-size:13px;">
      Pick a product on the right, then click on the image to drop a pin. Drag any pin to reposition. Click a pin to remove it.
    </p>

    <div class="lb-grid">
      <div>
        <div id="lb-canvas" class="lb-canvas">
          <?php if (!empty($editing['image_path'])): ?>
            <img id="lb-image" src="<?= e($editing['image_path']) ?>" alt="">
          <?php else: ?>
            <div class="empty">Upload a scene image and save to start placing pins.</div>
          <?php endif; ?>
        </div>

        <div class="lb-pins-list" id="lb-pins-list"></div>
      </div>

      <div>
        <label style="display:block;margin-bottom:6px;">Products (<?= count($products) ?>)</label>
        <input class="input" id="lb-search" placeholder="Search products…" style="margin-bottom:8px;">
        <div class="lb-picker" id="lb-picker">
          <?php foreach ($products as $p):
            $cat = trim(($p['category'] ?? '') . ' › ' . ($p['subcategory'] ?? ''), ' ›');
            $price = '';
            if ($p['price_min'] !== null) {
                $price = 'RM ' . number_format((float)$p['price_min'], 0);
                if ($p['price_max'] !== null && (float)$p['price_max'] != (float)$p['price_min']) {
                    $price .= '-' . number_format((float)$p['price_max'], 0);
                }
            }
          ?>
            <div class="lb-prod"
                 data-id="<?= (int)$p['id'] ?>"
                 data-name="<?= e($p['name']) ?>"
                 data-img="<?= e($p['img'] ?? '') ?>"
                 data-search="<?= e(strtolower($p['name'] . ' ' . $cat)) ?>">
              <?php if (!empty($p['img'])): ?>
                <img class="thumb" src="<?= e($p['img']) ?>" alt="">
              <?php else: ?>
                <div class="thumb-ph">no img</div>
              <?php endif; ?>
              <div class="meta">
                <strong><?= e($p['name']) ?></strong>
                <?php if ($cat || $price): ?>
                  <div class="cat">
                    <?= e($cat) ?><?php if ($cat && $price): ?> · <?php endif; ?><?= e($price) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$products): ?>
            <p class="muted" style="font-size:13px;">
              No active products yet. Add products first, then come back to place pins.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:center;">
    <span class="muted" style="font-size:13px;">
      Tip: change the scene image and save first, then place pins on the fresh image.
    </span>
    <div style="display:flex;gap:8px;">
      <a class="btn outline" href="/company-admin/lookbook.php">← Back</a>
      <button class="btn primary" type="submit">Save scene</button>
    </div>
  </div>
</form>

<script>
(function () {
  var hotspots = <?= json_encode($editor_hotspots, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var picker   = document.getElementById('lb-picker');
  var canvas   = document.getElementById('lb-canvas');
  var img      = document.getElementById('lb-image');
  var list     = document.getElementById('lb-pins-list');
  var jsonInp  = document.getElementById('hotspots_json');
  var search   = document.getElementById('lb-search');
  var selected = null;

  function render() {
    // Remove existing pins
    canvas.querySelectorAll('.lb-pin').forEach(function (n) { n.remove(); });
    // Draw new ones
    hotspots.forEach(function (h, i) {
      var pin = document.createElement('button');
      pin.type = 'button';
      pin.className = 'lb-pin';
      pin.textContent = (i + 1);
      pin.title = h.product_name + ' — drag to move, click to remove';
      pin.style.left = h.x + '%';
      pin.style.top  = h.y + '%';
      pin.dataset.index = i;
      attachPinHandlers(pin);
      canvas.appendChild(pin);
    });
    renderList();
    syncHidden();
  }

  function renderList() {
    list.innerHTML = '';
    hotspots.forEach(function (h, i) {
      var row = document.createElement('div');
      row.className = 'item';
      row.innerHTML =
        '<div class="n">' + (i + 1) + '</div>' +
        '<div class="name">' + escapeHtml(h.product_name) + '</div>' +
        '<button type="button" data-i="' + i + '">Remove</button>';
      row.querySelector('button').addEventListener('click', function () {
        hotspots.splice(i, 1);
        render();
      });
      list.appendChild(row);
    });
  }

  function syncHidden() {
    jsonInp.value = JSON.stringify(hotspots.map(function (h) {
      return { product_id: h.product_id, x: h.x, y: h.y, label: h.label || '' };
    }));
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }

  function attachPinHandlers(pin) {
    var dragging = false, moved = false;
    var idx = parseInt(pin.dataset.index, 10);

    function onDown(e) {
      e.preventDefault();
      dragging = true; moved = false;
      pin.classList.add('dragging');
    }
    function onMove(e) {
      if (!dragging) return;
      e.preventDefault();
      var rect = canvas.getBoundingClientRect();
      var cx = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
      var cy = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
      var x = Math.max(0, Math.min(100, (cx / rect.width) * 100));
      var y = Math.max(0, Math.min(100, (cy / rect.height) * 100));
      // Only mark as moved if the pointer travelled more than 4px.
      if (Math.abs(x - hotspots[idx].x) > 0.5 || Math.abs(y - hotspots[idx].y) > 0.5) moved = true;
      hotspots[idx].x = x;
      hotspots[idx].y = y;
      pin.style.left = x + '%';
      pin.style.top  = y + '%';
      syncHidden();
    }
    function onUp(e) {
      if (!dragging) return;
      dragging = false;
      pin.classList.remove('dragging');
      // If it was a click (no meaningful drag), delete it.
      if (!moved) {
        if (confirm('Remove pin #' + (idx + 1) + ' (' + hotspots[idx].product_name + ')?')) {
          hotspots.splice(idx, 1);
          render();
        }
      }
    }
    pin.addEventListener('mousedown', onDown);
    pin.addEventListener('touchstart', onDown, { passive: false });
    document.addEventListener('mousemove', onMove);
    document.addEventListener('touchmove', onMove, { passive: false });
    document.addEventListener('mouseup', onUp);
    document.addEventListener('touchend', onUp);
  }

  // Product picker
  picker.addEventListener('click', function (e) {
    var el = e.target.closest('.lb-prod');
    if (!el) return;
    picker.querySelectorAll('.lb-prod.selected').forEach(function (n) { n.classList.remove('selected'); });
    el.classList.add('selected');
    selected = {
      product_id: parseInt(el.dataset.id, 10),
      product_name: el.dataset.name,
      product_img: el.dataset.img,
    };
    canvas.classList.add('placing');
  });

  // Search filter
  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      picker.querySelectorAll('.lb-prod').forEach(function (el) {
        var hit = q === '' || (el.dataset.search || '').indexOf(q) !== -1;
        el.style.display = hit ? '' : 'none';
      });
    });
  }

  // Place new pin on canvas click
  canvas.addEventListener('click', function (e) {
    if (!selected) return;
    // Ignore clicks that hit an existing pin (those handle their own remove).
    if (e.target.classList.contains('lb-pin')) return;
    if (!img) { alert('Save the scene with an image first.'); return; }
    var rect = canvas.getBoundingClientRect();
    var cx = e.clientX - rect.left;
    var cy = e.clientY - rect.top;
    var x = Math.max(0, Math.min(100, (cx / rect.width) * 100));
    var y = Math.max(0, Math.min(100, (cy / rect.height) * 100));
    hotspots.push({
      product_id:   selected.product_id,
      product_name: selected.product_name,
      product_img:  selected.product_img,
      x: x, y: y, label: ''
    });
    // Auto-deselect after placing so the admin sees what they placed.
    picker.querySelectorAll('.lb-prod.selected').forEach(function (n) { n.classList.remove('selected'); });
    canvas.classList.remove('placing');
    selected = null;
    render();
  });

  // Sync JSON right before submit as a belt & braces measure
  document.getElementById('lb-form').addEventListener('submit', syncHidden);

  render();
})();
</script>

<?php ca_close(); ?>
