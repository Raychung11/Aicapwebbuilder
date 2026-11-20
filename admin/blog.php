<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/blog.php';

$action = (string) input('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) input('id', 0);

    if ($action === 'delete' && $id) {
        $row = db_one('SELECT featured_image FROM blog_posts WHERE id = ?', [$id]);
        if ($row) {
            if (!empty($row['featured_image'])) {
                $abs = __DIR__ . '/..' . $row['featured_image'];
                if (is_file($abs)) @unlink($abs);
            }
            db_exec('DELETE FROM blog_post_tags WHERE blog_post_id = ?', [$id]);
            db_exec('DELETE FROM blog_posts WHERE id = ?', [$id]);
            flash_set('success', 'Post deleted.');
        }
        redirect('/admin/blog.php');
    }

    if ($action === 'set_status' && $id) {
        $status = (string) input('status', 'draft');
        if (in_array($status, ['draft','pending_review','published','archived'], true)) {
            if ($status === 'published') {
                db_exec(
                    'UPDATE blog_posts
                        SET status = ?,
                            published_at = COALESCE(published_at, NOW())
                      WHERE id = ?',
                    [$status, $id]
                );
            } else {
                db_exec('UPDATE blog_posts SET status = ? WHERE id = ?', [$status, $id]);
            }
            flash_set('success', 'Status updated.');
        }
        redirect('/admin/blog.php');
    }
}

// Filters
$f_status   = (string) input('status', '');
$f_category = (int)    input('category', 0);
$f_language = (string) input('language', '');
$f_q        = trim((string) input('q', ''));

$sql = 'SELECT p.id, p.title, p.slug, p.status, p.language, p.published_at,
               p.created_at, p.view_count, c.name AS category_name,
               s.name AS author_name
          FROM blog_posts p
     LEFT JOIN blog_categories c ON c.id = p.category_id
     LEFT JOIN super_admins    s ON s.id = p.author_id
         WHERE 1 = 1';
$params = [];
if ($f_status !== '' && in_array($f_status, ['draft','pending_review','published','archived'], true)) {
    $sql .= ' AND p.status = ?';
    $params[] = $f_status;
}
if ($f_category) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $f_category;
}
if ($f_language !== '' && in_array($f_language, ['en','zh','ms'], true)) {
    $sql .= ' AND p.language = ?';
    $params[] = $f_language;
}
if ($f_q !== '') {
    $sql .= ' AND (p.title LIKE ? OR p.excerpt LIKE ?)';
    $params[] = '%' . $f_q . '%';
    $params[] = '%' . $f_q . '%';
}
$sql .= ' ORDER BY p.created_at DESC LIMIT 200';
$posts = db_all($sql, $params);
$cats  = blog_all_categories();

admin_layout_open('Blog');
?>
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
    <div>
      <h3 style="margin:0;">📝 Blog posts</h3>
      <p class="muted" style="margin:2px 0 0;font-size:13px;">
        AiCap furniture industry digital intelligence blog.
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn outline" href="/admin/blog-categories.php">📂 Categories</a>
      <a class="btn primary" href="/admin/blog-edit.php">+ New post</a>
      <a class="btn dark" href="/admin/blog-edit.php?mode=ai">🤖 AI generate</a>
    </div>
  </div>

  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
    <input class="input" name="q" placeholder="Search title / excerpt" value="<?= e($f_q) ?>" style="flex:1;min-width:180px;">
    <select class="input" name="status" style="max-width:150px;">
      <option value="">All statuses</option>
      <?php foreach (['draft'=>'Draft','pending_review'=>'Pending','published'=>'Published','archived'=>'Archived'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $f_status===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="category" style="max-width:180px;">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $f_category===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="language" style="max-width:150px;">
      <option value="">Any language</option>
      <option value="en" <?= $f_language==='en'?'selected':'' ?>>English</option>
      <option value="zh" <?= $f_language==='zh'?'selected':'' ?>>中文</option>
      <option value="ms" <?= $f_language==='ms'?'selected':'' ?>>Bahasa Malaysia</option>
    </select>
    <button class="btn primary" type="submit">Filter</button>
    <?php if ($f_status||$f_category||$f_language||$f_q!==''): ?>
      <a class="btn outline" href="/admin/blog.php">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$posts): ?>
    <div class="box center" style="padding:36px 12px;">
      <p class="muted" style="margin:0 0 12px;">No posts yet. Draft your first article with AI in a few clicks.</p>
      <a class="btn dark" href="/admin/blog-edit.php?mode=ai">🤖 AI generate</a>
    </div>
  <?php else: ?>
    <table>
      <tr>
        <th>Title</th>
        <th>Category</th>
        <th>Lang</th>
        <th>Status</th>
        <th>Author</th>
        <th>Published</th>
        <th>Views</th>
        <th></th>
      </tr>
      <?php foreach ($posts as $p): ?>
        <tr>
          <td>
            <strong><?= e($p['title']) ?></strong>
            <div class="muted" style="font-size:11px;margin-top:2px;">/blog/<?= e($p['slug']) ?></div>
          </td>
          <td><?= e($p['category_name'] ?: '—') ?></td>
          <td><span class="badge"><?= e(strtoupper($p['language'])) ?></span></td>
          <td>
            <?= blog_status_badge($p['status']) ?>
            <?php
              if ($p['status'] === 'published'
                  && !empty($p['published_at'])
                  && strtotime($p['published_at']) > time() + 60) {
                  echo ' <span class="badge yellow" title="Scheduled — will appear once the publish time is reached">⏳ scheduled</span>';
              }
            ?>
          </td>
          <td><?= e($p['author_name'] ?: '—') ?></td>
          <td>
            <?php if ($p['published_at']): ?>
              <?= e(date('j M Y H:i', strtotime($p['published_at']))) ?>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$p['view_count'] ?></td>
          <td class="actions">
            <a class="btn outline" href="/admin/blog-edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
            <?php if ($p['status'] === 'published'): ?>
              <a class="btn outline" target="_blank" rel="noopener" href="/blog/<?= e($p['slug']) ?>">View</a>
            <?php else: ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="status" value="published">
                <button class="btn primary" type="submit">Publish</button>
              </form>
            <?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this post?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php admin_layout_close(); ?>
