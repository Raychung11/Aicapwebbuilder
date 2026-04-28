<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$id = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete' && $id) {
        tenant_row_or_404('salespersons', $id);
        db_exec('DELETE FROM salespersons WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Salesperson deleted.');
        redirect('/company-admin/salespersons.php');
    }
    $branch_id = (int) input('branch_id', 0) ?: null;
    if ($branch_id) tenant_row_or_404('branches', $branch_id);
    $f = [
        'branch_id'       => $branch_id,
        'name'            => trim((string) input('name')),
        'phone'           => (string) input('phone', ''),
        'email'           => (string) input('email', ''),
        'whatsapp_number' => (string) input('whatsapp_number', ''),
        'role'            => (string) input('role', ''),
        'status'          => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('salespersons', $id);
        db_exec(
            'UPDATE salespersons SET branch_id=?, name=?, phone=?, email=?, whatsapp_number=?, role=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO salespersons (company_id, branch_id, name, phone, email, whatsapp_number, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Saved.');
    redirect('/company-admin/salespersons.php');
}

$editing  = $id ? tenant_row_or_404('salespersons', $id) : null;
$rows     = tenant_all('SELECT * FROM salespersons WHERE company_id = ? ORDER BY id DESC', $CID);
$branches = tenant_all('SELECT id, name FROM branches WHERE company_id = ? ORDER BY name', $CID);

ca_open('Salespersons');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Salesperson' : 'Add Salesperson' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Branch</label>
        <select class="input" name="branch_id">
          <option value="">— None —</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ($editing['branch_id'] ?? 0) == $b['id'] ? 'selected':'' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <option <?= ($editing['status'] ?? '')==='active'?'selected':'' ?>>active</option>
          <option <?= ($editing['status'] ?? '')==='disabled'?'selected':'' ?>>disabled</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col"><label>Phone</label><input class="input" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
      <div class="col"><label>Email</label><input class="input" name="email" value="<?= e($editing['email'] ?? '') ?>"></div>
      <div class="col"><label>WhatsApp</label><input class="input" name="whatsapp_number" value="<?= e($editing['whatsapp_number'] ?? '') ?>"></div>
      <div class="col"><label>Role</label><input class="input" name="role" value="<?= e($editing['role'] ?? '') ?>"></div>
    </div>
    <p><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/salespersons.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Name</th><th>Phone</th><th>Branch</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $r):
      $br = $r['branch_id']
        ? db_one('SELECT name FROM branches WHERE id = ? AND company_id = ?', [(int)$r['branch_id'], $CID])
        : null;
    ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($br['name'] ?? '') ?></td>
        <td><span class="badge <?= $r['status']==='active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
