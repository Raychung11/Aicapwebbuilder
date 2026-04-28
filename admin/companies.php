<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$action = (string) input('action', '');

// Quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle') {
    csrf_check();
    $id   = (int) input('id', 0);
    $next = input('to', 'active');
    if ($id && in_array($next, ['active','suspended','disabled'], true)) {
        db_exec('UPDATE companies SET status = ? WHERE id = ?', [$next, $id]);
        flash_set('success', 'Company status updated.');
    }
    redirect('/admin/companies.php');
}

$companies = db_all('SELECT * FROM companies ORDER BY created_at DESC');

admin_layout_open('Companies');
?>
<div class="card">
  <a class="btn primary" href="/admin/company-edit.php">+ Add Company</a>
</div>
<div class="card">
  <table>
    <tr>
      <th>Name</th><th>Slug / Subdomain</th><th>Custom Domain</th>
      <th>Status</th><th>Created</th><th></th>
    </tr>
    <?php foreach ($companies as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['slug']) ?> &nbsp;<span class="muted"><?= e($c['subdomain']) ?>.<?= e(APP_BASE_DOMAIN) ?></span></td>
        <td><?= e($c['custom_domain'] ?? '') ?></td>
        <td><span class="badge <?= $c['status']==='active'?'green':'red' ?>"><?= e($c['status']) ?></span></td>
        <td><?= e($c['created_at']) ?></td>
        <td class="actions">
          <a class="btn outline" href="/admin/company-edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="hidden" name="to" value="<?= $c['status']==='active' ? 'suspended' : 'active' ?>">
            <button class="btn outline" type="submit">
              <?= $c['status']==='active' ? 'Suspend' : 'Activate' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_layout_close(); ?>
