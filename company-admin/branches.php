<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$action = (string) input('action', '');
$id     = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($action === 'delete' && $id) {
        tenant_row_or_404('branches', $id);
        db_exec('DELETE FROM branches WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Branch deleted.');
        redirect('/company-admin/branches.php');
    }
    $f = [
        'name'             => trim((string) input('name')),
        'address'          => (string) input('address', ''),
        'phone'            => (string) input('phone', ''),
        'whatsapp_number'  => (string) input('whatsapp_number', ''),
        'email'            => (string) input('email', ''),
        'google_map_embed' => (string) input('google_map_embed', ''),
        'operating_hours'  => (string) input('operating_hours', ''),
        'status'           => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('branches', $id);
        db_exec(
            'UPDATE branches SET name=?, address=?, phone=?, whatsapp_number=?, email=?,
                                 google_map_embed=?, operating_hours=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO branches (company_id, name, address, phone, whatsapp_number, email,
                                   google_map_embed, operating_hours, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Branch saved.');
    redirect('/company-admin/branches.php');
}

$editing  = $id ? tenant_row_or_404('branches', $id) : null;
$branches = tenant_all('SELECT * FROM branches WHERE company_id = ? ORDER BY id DESC', $CID);

ca_open('Branches');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Branch' : 'Add Branch' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Phone</label><input class="input" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
      <div class="col"><label>WhatsApp</label><input class="input" name="whatsapp_number" value="<?= e($editing['whatsapp_number'] ?? '') ?>"></div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <option <?= ($editing['status'] ?? '')==='active'?'selected':'' ?>>active</option>
          <option <?= ($editing['status'] ?? '')==='disabled'?'selected':'' ?>>disabled</option>
        </select>
      </div>
    </div>
    <label>Address</label><input class="input" name="address" value="<?= e($editing['address'] ?? '') ?>">
    <div class="row">
      <div class="col"><label>Email</label><input class="input" name="email" value="<?= e($editing['email'] ?? '') ?>"></div>
      <div class="col"><label>Operating Hours</label><input class="input" name="operating_hours" value="<?= e($editing['operating_hours'] ?? '') ?>"></div>
    </div>
    <label>Google Map Embed</label>
    <textarea class="input" name="google_map_embed" rows="2"><?= e($editing['google_map_embed'] ?? '') ?></textarea>
    <p><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/branches.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Name</th><th>Phone</th><th>Address</th><th>Status</th><th></th></tr>
    <?php foreach ($branches as $b): ?>
      <tr>
        <td><?= e($b['name']) ?></td>
        <td><?= e($b['phone']) ?></td>
        <td><?= e($b['address']) ?></td>
        <td><span class="badge <?= $b['status']==='active'?'green':'red' ?>"><?= e($b['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$b['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete branch?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
