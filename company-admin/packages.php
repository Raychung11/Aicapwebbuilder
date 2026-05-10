<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$action = (string) input('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) input('id', 0);
    if ($action === 'delete' && $id) {
        tenant_row_or_404('packages', $id);
        db_exec('DELETE FROM package_choices WHERE company_id = ? AND section_id IN (SELECT id FROM package_sections WHERE package_id = ?)', [$CID, $id]);
        db_exec('DELETE FROM package_sections WHERE company_id = ? AND package_id = ?', [$CID, $id]);
        db_exec('DELETE FROM packages WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Package deleted.');
    } elseif ($action === 'toggle_status' && $id) {
        $row = tenant_row_or_404('packages', $id);
        $next = $row['status'] === 'active' ? 'draft' : 'active';
        db_exec('UPDATE packages SET status = ? WHERE company_id = ? AND id = ?', [$next, $CID, $id]);
        flash_set('success', 'Package set to ' . $next . '.');
    } elseif ($action === 'toggle_featured' && $id) {
        $row = tenant_row_or_404('packages', $id);
        $next = $row['is_featured'] ? 0 : 1;
        db_exec('UPDATE packages SET is_featured = ? WHERE company_id = ? AND id = ?', [$next, $CID, $id]);
    }
    redirect('/company-admin/packages.php');
}

$rows = tenant_all(
    'SELECT p.*,
            (SELECT COUNT(*) FROM package_sections WHERE package_id = p.id) AS section_count
       FROM packages p
      WHERE p.company_id = ?
      ORDER BY p.is_featured DESC, p.sort_order, p.created_at DESC',
    $CID
);

ca_open('Furniture Packages');
?>
<div class="card">
  <p class="muted" style="margin:0 0 12px;">
    Curated room-bundle deals that customers can browse and book — like the
    "Get 2-3 rooms fully furnished from RM 6,988" promo. Each package can
    contain multiple sections (Master Room, Living, Dining…) and each section
    can offer customer choices (different sofa or TV cabinet designs).
  </p>
  <a class="btn primary" href="/company-admin/package-edit.php">+ New Package</a>
</div>

<div class="card">
  <table>
    <tr>
      <th></th><th>Title</th><th>Price</th><th>Sections</th>
      <th>Featured</th><th>Status</th><th>Actions</th>
    </tr>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="muted center" style="padding:18px;">
        No packages yet — click "+ New Package" to create one.
      </td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td style="width:60px;">
          <?php if (!empty($p['hero_image'])): ?>
            <img src="<?= e($p['hero_image']) ?>" alt=""
                 style="width:50px;height:50px;object-fit:cover;border-radius:6px;background:#eee;">
          <?php endif; ?>
        </td>
        <td>
          <strong><?= e($p['title']) ?></strong>
          <?php if (!empty($p['subtitle'])): ?>
            <div class="muted" style="font-size:12px;"><?= e($p['subtitle']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($p['price'] !== null): ?>
            <strong>RM <?= number_format((float) $p['price'], 0) ?></strong>
            <?php if ($p['was_price'] !== null): ?>
              <div class="muted" style="font-size:12px;text-decoration:line-through;">
                RM <?= number_format((float) $p['was_price'], 0) ?>
              </div>
            <?php endif; ?>
          <?php else: ?><span class="muted">—</span><?php endif; ?>
        </td>
        <td><?= (int) $p['section_count'] ?></td>
        <td>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle_featured">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" style="background:none;border:0;font-size:18px;cursor:pointer;line-height:1;padding:4px;"
                    title="<?= $p['is_featured'] ? 'Remove from featured' : 'Mark as featured' ?>">
              <?= $p['is_featured'] ? '⭐' : '☆' ?>
            </button>
          </form>
        </td>
        <td>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="badge <?= $p['status'] === 'active' ? 'green' : '' ?>"
                    style="border:0;cursor:pointer;font-family:inherit;">
              <?= e($p['status']) ?>
            </button>
          </form>
        </td>
        <td class="actions">
          <a class="btn outline" href="/company-admin/package-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
          <a class="btn outline" href="/package.php?id=<?= (int) $p['id'] ?>" target="_blank" rel="noopener">Preview</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this package, all sections and choices? This cannot be undone.')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
