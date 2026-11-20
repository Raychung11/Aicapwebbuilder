<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/blog.php';

$action = (string) input('action', '');
$id     = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'delete' && $id) {
        // Detach posts from the category rather than deleting them.
        db_exec('UPDATE blog_posts SET category_id = NULL WHERE category_id = ?', [$id]);
        db_exec('DELETE FROM blog_categories WHERE id = ?', [$id]);
        flash_set('success', 'Category deleted.');
        redirect('/admin/blog-categories.php');
    }

    $name = trim((string) input('name', ''));
    if ($name === '') {
        flash_set('error', 'Name is required.');
        redirect('/admin/blog-categories.php');
    }
    $slug = blog_slugify(trim((string) input('slug', '')) ?: $name);
    $slug = blog_unique_slug('blog_categories', $slug, $id ?: null);
    $desc = trim((string) input('description', '')) ?: null;
    $status = input('status') === 'disabled' ? 'disabled' : 'active';
    $sort   = (int) input('sort_order', 0);

    if ($id) {
        db_exec(
            'UPDATE blog_categories SET name=?, slug=?, description=?, status=?, sort_order=?
              WHERE id=?',
            [$name, $slug, $desc, $status, $sort, $id]
        );
        flash_set('success', 'Category updated.');
    } else {
        db_insert(
            'INSERT INTO blog_categories (name, slug, description, status, sort_order)
             VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $desc, $status, $sort]
        );
        flash_set('success', 'Category added.');
    }
    redirect('/admin/blog-categories.php');
}

$editing = $id ? db_one('SELECT * FROM blog_categories WHERE id = ?', [$id]) : null;
$cats    = db_all(
    'SELECT c.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = c.id) AS n
       FROM blog_categories c
      ORDER BY c.sort_order, c.name'
);

admin_layout_open('Blog Categories');
?>
<div class="card">
  <h3 style="margin:0 0 10px;"><?= $editing ? 'Edit category' : 'Add category' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col">
        <label>Name</label>
        <input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Slug <span class="muted">(auto from name if blank)</span></label>
        <input class="input" name="slug" value="<?= e($editing['slug'] ?? '') ?>">
      </div>
      <div class="col" style="max-width:150px;">
        <label>Status</label>
        <select class="input" name="status">
          <option value="active"   <?= ($editing['status'] ?? 'active')==='active'?'selected':'' ?>>Active</option>
          <option value="disabled" <?= ($editing['status'] ?? '')==='disabled'?'selected':'' ?>>Disabled</option>
        </select>
      </div>
      <div class="col" style="max-width:120px;">
        <label>Sort</label>
        <input class="input" type="number" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>">
      </div>
    </div>
    <label>Description <span class="muted">(optional)</span></label>
    <textarea class="input" name="description" rows="2"><?= e($editing['description'] ?? '') ?></textarea>
    <p>
      <button class="btn primary">Save</button>
      <?php if ($editing): ?>
        <a class="btn outline" href="/admin/blog-categories.php">+ New</a>
      <?php endif; ?>
      <a class="btn outline" href="/admin/blog.php">← Back to posts</a>
    </p>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 10px;">Categories</h3>
  <?php if (!$cats): ?>
    <p class="muted">No categories yet. Suggested: Industry Trends, Digital Transformation, AI &amp; Automation, CRM &amp; Sales, Warehouse &amp; Stock, Export &amp; Marketplace, SME Policy.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Slug</th><th>Status</th><th>Sort</th><th>Posts</th><th></th></tr>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td>
            <strong><?= e($c['name']) ?></strong>
            <?php if (!empty($c['description'])): ?>
              <div class="muted" style="font-size:12px;margin-top:2px;"><?= e($c['description']) ?></div>
            <?php endif; ?>
          </td>
          <td><code style="font-size:12px;"><?= e($c['slug']) ?></code></td>
          <td><span class="badge <?= $c['status']==='active'?'green':'red' ?>"><?= e($c['status']) ?></span></td>
          <td><?= (int)$c['sort_order'] ?></td>
          <td><?= (int)$c['n'] ?></td>
          <td class="actions">
            <a class="btn outline" href="?id=<?= (int)$c['id'] ?>">Edit</a>
            <form method="post" style="display:inline"
                  onsubmit="return confirm('Delete this category? Its posts will lose the category assignment.')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php admin_layout_close(); ?>
