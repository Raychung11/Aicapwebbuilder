<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) input('id', 0);
    $name        = trim((string) input('name'));
    $key         = slugify((string) input('template_key'));
    $layout_json = (string) input('layout_json');
    $status      = in_array(input('status'), ['active','disabled'], true)
                      ? input('status') : 'active';

    if ($id) {
        db_exec('UPDATE page_templates SET name=?, template_key=?, layout_json=?, status=? WHERE id=?',
            [$name, $key, $layout_json, $status, $id]);
    } else {
        db_insert('INSERT INTO page_templates (name, template_key, layout_json, status) VALUES (?,?,?,?)',
            [$name, $key, $layout_json, $status]);
    }
    flash_set('success', 'Template saved.');
    redirect('/admin/templates.php');
}

$templates = db_all('SELECT * FROM page_templates ORDER BY name');
$edit_id   = (int) input('edit', 0);
$editing   = $edit_id ? db_one('SELECT * FROM page_templates WHERE id=?', [$edit_id]) : null;

admin_layout_open('Page Templates');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Template' : 'Create Template' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Key</label><input class="input" name="template_key" required value="<?= e($editing['template_key'] ?? '') ?>"></div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <option value="active" <?= ($editing['status'] ?? '')==='active'?'selected':'' ?>>active</option>
          <option value="disabled" <?= ($editing['status'] ?? '')==='disabled'?'selected':'' ?>>disabled</option>
        </select>
      </div>
    </div>
    <label>Layout JSON</label>
    <textarea class="input" name="layout_json" rows="8" placeholder='{"blocks":[...]}'><?= e($editing['layout_json'] ?? '') ?></textarea>
    <p><button class="btn primary" type="submit">Save</button>
      <?php if ($editing): ?><a class="btn outline" href="/admin/templates.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Name</th><th>Key</th><th>Status</th><th></th></tr>
    <?php foreach ($templates as $t): ?>
      <tr>
        <td><?= e($t['name']) ?></td>
        <td><code><?= e($t['template_key']) ?></code></td>
        <td><span class="badge <?= $t['status']==='active'?'green':'red' ?>"><?= e($t['status']) ?></span></td>
        <td><a class="btn outline" href="?edit=<?= (int)$t['id'] ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_layout_close(); ?>
