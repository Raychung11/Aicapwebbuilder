<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$action = (string) input('action', '');
$id     = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'delete' && $id) {
        $row = tenant_row_or_404('lookbook_scenes', $id);
        if (!empty($row['image_path'])) {
            $abs = __DIR__ . '/..' . $row['image_path'];
            if (is_file($abs)) @unlink($abs);
        }
        db_exec('DELETE FROM lookbook_hotspots WHERE company_id = ? AND scene_id = ?', [$CID, $id]);
        db_exec('DELETE FROM lookbook_scenes WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Scene deleted.');
        redirect('/company-admin/lookbook.php');
    }

    if ($action === 'set_status' && $id) {
        $status = (string) input('status', 'active');
        if (in_array($status, ['active','draft','disabled'], true)) {
            tenant_row_or_404('lookbook_scenes', $id);
            db_exec('UPDATE lookbook_scenes SET status = ? WHERE company_id = ? AND id = ?', [$status, $CID, $id]);
            flash_set('success', 'Status updated.');
        }
        redirect('/company-admin/lookbook.php');
    }
}

$scenes = tenant_all(
    'SELECT s.*,
            (SELECT COUNT(*) FROM lookbook_hotspots h
              WHERE h.company_id = s.company_id AND h.scene_id = s.id) AS pins
       FROM lookbook_scenes s
      WHERE s.company_id = ?
      ORDER BY s.sort_order ASC, s.created_at DESC',
    $CID
);

ca_open('Shoppable Lookbook');
?>
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
    <div>
      <h3 style="margin:0;">🖼️ Shoppable Lookbook</h3>
      <p class="muted" style="margin:2px 0 0;font-size:13px;">
        Upload an interior design image, drop pins on the furniture, and every pin becomes a clickable link to the matching product.
      </p>
    </div>
    <a class="btn primary" href="/company-admin/lookbook-edit.php">+ New scene</a>
  </div>

  <?php if (!$scenes): ?>
    <div class="box center" style="padding:36px 12px;">
      <p class="muted" style="margin:0 0 10px;">No scenes yet. Generate an interior design image (Nano Banana / Midjourney / Firefly) featuring your products, then drop pins on it.</p>
      <a class="btn dark" href="/company-admin/lookbook-edit.php">Add your first scene</a>
    </div>
  <?php else: ?>
    <table>
      <tr>
        <th style="width:100px">Cover</th>
        <th>Title</th>
        <th>Pins</th>
        <th>Views</th>
        <th>Status</th>
        <th></th>
      </tr>
      <?php foreach ($scenes as $s): ?>
        <tr>
          <td>
            <?php if (!empty($s['image_path'])): ?>
              <img src="<?= e($s['image_path']) ?>" alt=""
                   style="width:90px;height:60px;object-fit:cover;border-radius:6px;background:#eee;">
            <?php else: ?>
              <div style="width:90px;height:60px;border-radius:6px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:11px;color:#9ca3af;">
                No image
              </div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= e($s['title']) ?></strong>
            <div class="muted" style="font-size:11px;margin-top:2px;">/lookbook/<?= e($s['slug']) ?></div>
            <?php if (!empty($s['description'])): ?>
              <div class="muted" style="font-size:12px;margin-top:2px;">
                <?= e(mb_strimwidth($s['description'], 0, 80, '…')) ?>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge"><?= (int)$s['pins'] ?> 📍</span>
          </td>
          <td><?= (int)$s['view_count'] ?></td>
          <td><span class="badge <?= $s['status']==='active' ? 'green' : ($s['status']==='draft' ? 'yellow' : 'red') ?>"><?= e($s['status']) ?></span></td>
          <td class="actions">
            <a class="btn outline" href="/company-admin/lookbook-edit.php?id=<?= (int)$s['id'] ?>">Edit</a>
            <?php if ($s['status'] === 'active'): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="/lookbook/<?= e($s['slug']) ?>">View</a>
            <?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this scene? Its pins are removed too.')">
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
