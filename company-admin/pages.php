<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$id     = (int) input('id', 0);
$action = (string) input('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'delete' && $id) {
        tenant_row_or_404('company_pages', $id);
        db_exec('DELETE FROM company_page_blocks WHERE company_id = ? AND page_id = ?', [$CID, $id]);
        db_exec('DELETE FROM company_pages WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Page deleted.');
        redirect('/company-admin/pages.php');
    }

    if ($action === 'add_block' && $id) {
        tenant_row_or_404('company_pages', $id);
        $type = (string) input('block_type', 'text');
        $json = (string) input('content_json', '{}');
        $sort = (int) (db_one(
            'SELECT MAX(sort_order) m FROM company_page_blocks WHERE company_id = ? AND page_id = ?',
            [$CID, $id]
        )['m'] ?? 0) + 1;
        db_insert(
            'INSERT INTO company_page_blocks (company_id, page_id, block_type, content_json, sort_order)
             VALUES (?, ?, ?, ?, ?)',
            [$CID, $id, $type, $json, $sort]
        );
        flash_set('success', 'Block added.');
        redirect('/company-admin/pages.php?id=' . $id);
    }

    if ($action === 'delete_block') {
        $bid = (int) input('block_id', 0);
        db_exec('DELETE FROM company_page_blocks WHERE company_id = ? AND id = ?', [$CID, $bid]);
        redirect('/company-admin/pages.php?id=' . $id);
    }

    // Save page core
    $slug   = slugify((string) input('slug'));
    $title  = trim((string) input('title'));
    $status = in_array(input('status'), ['draft','published'], true) ? input('status') : 'draft';
    if ($id) {
        tenant_row_or_404('company_pages', $id);
        db_exec(
            'UPDATE company_pages SET slug=?, title=?, status=? WHERE company_id=? AND id=?',
            [$slug, $title, $status, $CID, $id]
        );
    } else {
        $id = db_insert(
            'INSERT INTO company_pages (company_id, slug, title, status) VALUES (?, ?, ?, ?)',
            [$CID, $slug, $title, $status]
        );
    }
    flash_set('success', 'Page saved.');
    redirect('/company-admin/pages.php?id=' . $id);
}

$pages   = tenant_all('SELECT * FROM company_pages WHERE company_id = ? ORDER BY updated_at DESC', $CID);
$editing = $id ? tenant_row_or_404('company_pages', $id) : null;
$blocks  = $id ? tenant_all(
    'SELECT * FROM company_page_blocks WHERE company_id = ? AND page_id = ? ORDER BY sort_order',
    $CID, [$id]
) : [];

ca_open('Pages');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Page' : 'New Page' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Title</label><input class="input" name="title" required value="<?= e($editing['title'] ?? '') ?>"></div>
      <div class="col"><label>Slug</label><input class="input" name="slug" required value="<?= e($editing['slug'] ?? '') ?>"></div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <option <?= ($editing['status'] ?? '')==='draft'?'selected':'' ?>>draft</option>
          <option <?= ($editing['status'] ?? '')==='published'?'selected':'' ?>>published</option>
        </select>
      </div>
    </div>
    <p><button class="btn primary">Save Page</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/pages.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<?php if ($editing): ?>
<div class="card">
  <h3 style="margin:0 0 10px">Blocks</h3>
  <?php if ($blocks): ?>
    <table>
      <tr><th>#</th><th>Type</th><th>Content (JSON)</th><th></th></tr>
      <?php foreach ($blocks as $b): ?>
        <tr>
          <td><?= (int)$b['sort_order'] ?></td>
          <td><?= e($b['block_type']) ?></td>
          <td><code style="font-size:12px;white-space:pre-wrap"><?= e($b['content_json']) ?></code></td>
          <td>
            <form method="post" onsubmit="return confirm('Delete block?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_block">
              <input type="hidden" name="block_id" value="<?= (int)$b['id'] ?>">
              <button class="btn danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h4 style="margin:14px 0 6px">Add Block</h4>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_block">
    <div class="row">
      <div class="col"><label>Block Type</label>
        <select class="input" name="block_type">
          <option>hero</option>
          <option>text</option>
          <option>image</option>
          <option>products</option>
          <option>cta</option>
          <option>map</option>
        </select>
      </div>
      <div class="col" style="flex:2"><label>Content JSON</label>
        <input class="input" name="content_json" placeholder='{"title":"...","body":"..."}' value="{}">
      </div>
    </div>
    <p><button class="btn primary">Add Block</button></p>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <table>
    <tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th></th></tr>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><?= e($p['title']) ?></td>
        <td><?= e($p['slug']) ?></td>
        <td><span class="badge <?= $p['status']==='published'?'green':'' ?>"><?= e($p['status']) ?></span></td>
        <td><?= e($p['updated_at']) ?></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete page?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
