<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

$action = (string) input('action', '');
$id     = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'delete' && $id) {
        $row = tenant_row_or_404('company_banners', $id);
        if (!empty($row['image'])) {
            $abs = __DIR__ . '/..' . $row['image'];
            if (is_file($abs)) @unlink($abs);
        }
        db_exec('DELETE FROM company_banners WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Slide deleted.');
        redirect('/company-admin/banners.php');
    }

    if ($action === 'move' && $id) {
        tenant_row_or_404('company_banners', $id);
        $dir = (string) input('dir', '');
        $list = db_all(
            'SELECT id, sort_order FROM company_banners
              WHERE company_id = ? ORDER BY sort_order ASC, id ASC',
            [$CID]
        );
        $ids = array_column($list, 'id');
        $idx = array_search($id, $ids, true);
        if ($idx !== false) {
            $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($swap >= 0 && $swap < count($ids)) {
                $a = $ids[$idx]; $b = $ids[$swap];
                $av = $list[$idx]['sort_order']; $bv = $list[$swap]['sort_order'];
                if ($av === $bv) { $av = $idx * 10; $bv = $swap * 10; }
                db_exec('UPDATE company_banners SET sort_order = ? WHERE company_id = ? AND id = ?', [$bv, $CID, $a]);
                db_exec('UPDATE company_banners SET sort_order = ? WHERE company_id = ? AND id = ?', [$av, $CID, $b]);
            }
        }
        redirect('/company-admin/banners.php');
    }

    // Create / update
    $f = [
        'title'    => trim((string) input('title', '')) ?: null,
        'subtitle' => trim((string) input('subtitle', '')) ?: null,
        'cta_text' => trim((string) input('cta_text', '')) ?: null,
        'cta_url'  => trim((string) input('cta_url', '')) ?: null,
        'status'   => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];

    if ($id) {
        $row = tenant_row_or_404('company_banners', $id);
        $image = $row['image'];
        if (!empty($_FILES['image']['name'])) {
            $url = save_upload($_FILES['image'], $CID, 'banner');
            if ($url) {
                if (!empty($row['image'])) {
                    $abs = __DIR__ . '/..' . $row['image'];
                    if (is_file($abs)) @unlink($abs);
                }
                $image = $url;
            } else {
                flash_set('error', 'Could not save the banner image. Use a JPG/PNG/WEBP up to 5 MB.');
            }
        }
        db_exec(
            'UPDATE company_banners
                SET image = ?, title = ?, subtitle = ?, cta_text = ?, cta_url = ?, status = ?
              WHERE company_id = ? AND id = ?',
            [$image, $f['title'], $f['subtitle'], $f['cta_text'], $f['cta_url'], $f['status'], $CID, $id]
        );
        flash_set('success', 'Slide updated.');
    } else {
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = save_upload($_FILES['image'], $CID, 'banner') ?: null;
        }
        $next = (int) db_one(
            'SELECT COALESCE(MAX(sort_order), 0) + 10 AS s FROM company_banners WHERE company_id = ?',
            [$CID]
        )['s'];
        db_insert(
            'INSERT INTO company_banners (company_id, image, title, subtitle, cta_text, cta_url, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, $image, $f['title'], $f['subtitle'], $f['cta_text'], $f['cta_url'], $next, $f['status']]
        );
        flash_set('success', 'Slide added.');
    }
    redirect('/company-admin/banners.php');
}

$editing = $id ? tenant_row_or_404('company_banners', $id) : null;
$slides  = tenant_all(
    'SELECT * FROM company_banners WHERE company_id = ? ORDER BY sort_order ASC, id ASC',
    $CID
);

ca_open('Homepage Banner Carousel');
?>
<div class="card">
  <h3 style="margin:0 0 4px;"><?= $editing ? 'Edit Slide' : 'Add Slide' ?></h3>
  <p class="muted" style="margin:0 0 12px;">
    Slides rotate automatically on the homepage hero. Add 2 or more for a moving carousel.
    Recommended image size: 1600&times;600 px landscape. A dark overlay is added for text contrast.
  </p>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

    <div class="row">
      <div class="col">
        <label>Background image</label>
        <div style="width:240px;height:120px;border:2px dashed #d1d5db;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
          <?php if (!empty($editing['image'])): ?>
            <img src="<?= e($editing['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span class="muted" style="font-size:12px;text-align:center;padding:8px;">No image yet</span>
          <?php endif; ?>
        </div>
        <input type="file" name="image" accept="image/*" style="margin-top:8px;font-size:13px;">
        <p class="muted" style="margin-top:4px;font-size:12px;">JPG/PNG/WEBP up to 5 MB.</p>
      </div>
      <div class="col">
        <label>Status</label>
        <select class="input" name="status">
          <option value="active"   <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="disabled" <?= ($editing['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Disabled</option>
        </select>
        <p class="muted" style="margin-top:4px;font-size:12px;">Disabled slides are hidden from the carousel.</p>
      </div>
    </div>

    <label>Title</label>
    <input class="input" name="title" maxlength="255"
           placeholder="<?= e($company['name']) ?>"
           value="<?= e($editing['title'] ?? '') ?>">

    <label>Subtitle</label>
    <textarea class="input" name="subtitle" rows="2"
              placeholder="Premium living spaces, crafted for every home."><?= e($editing['subtitle'] ?? '') ?></textarea>

    <div class="row">
      <div class="col">
        <label>CTA button text</label>
        <input class="input" name="cta_text" maxlength="120"
               placeholder="Browse Catalog"
               value="<?= e($editing['cta_text'] ?? '') ?>">
      </div>
      <div class="col">
        <label>CTA button link</label>
        <input class="input" name="cta_url" maxlength="500"
               placeholder="/catalog.php"
               value="<?= e($editing['cta_url'] ?? '') ?>">
        <p class="muted" style="margin-top:4px;font-size:12px;">e.g. <code>/catalog.php</code>, <code>/voucher.php</code>, <code>/visit.php</code> or any URL.</p>
      </div>
    </div>

    <p>
      <button class="btn primary">Save</button>
      <?php if ($editing): ?>
        <a class="btn outline" href="/company-admin/banners.php">+ New slide</a>
      <?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 10px;">Slides (<?= count($slides) ?>)</h3>
  <?php if (!$slides): ?>
    <p class="muted">No slides yet. Add one above — until you do, the homepage shows your saved "Branding &amp; SEO" banner.</p>
  <?php else: ?>
    <table>
      <tr>
        <th style="width:90px">Preview</th>
        <th>Title</th>
        <th>CTA</th>
        <th>Status</th>
        <th>Order</th>
        <th></th>
      </tr>
      <?php foreach ($slides as $i => $s): ?>
        <tr>
          <td>
            <?php if (!empty($s['image'])): ?>
              <img src="<?= e($s['image']) ?>" alt="" style="width:80px;height:50px;object-fit:cover;border-radius:5px;background:#eee;">
            <?php else: ?>
              <div style="width:80px;height:50px;border-radius:5px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:11px;color:#9ca3af;">No img</div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= e($s['title'] ?: '(no title)') ?></strong>
            <?php if (!empty($s['subtitle'])): ?>
              <div class="muted" style="font-size:12px;margin-top:2px;"><?= e(mb_substr($s['subtitle'], 0, 80)) ?><?= mb_strlen($s['subtitle']) > 80 ? '…' : '' ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($s['cta_text'])): ?>
              <span class="badge"><?= e($s['cta_text']) ?></span>
              <?php if (!empty($s['cta_url'])): ?>
                <div class="muted" style="font-size:11px;margin-top:2px;"><?= e($s['cta_url']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $s['status']==='active'?'green':'red' ?>"><?= e($s['status']) ?></span></td>
          <td class="actions">
            <?php if ($i > 0): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="dir" value="up">
                <button class="btn outline" type="submit" title="Move up" style="padding:4px 8px;">↑</button>
              </form>
            <?php endif; ?>
            <?php if ($i < count($slides) - 1): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="dir" value="down">
                <button class="btn outline" type="submit" title="Move down" style="padding:4px 8px;">↓</button>
              </form>
            <?php endif; ?>
          </td>
          <td class="actions">
            <a class="btn outline" href="?id=<?= (int)$s['id'] ?>">Edit</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this slide?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php ca_close(); ?>
