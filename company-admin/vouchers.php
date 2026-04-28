<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$id = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete' && $id) {
        tenant_row_or_404('vouchers', $id);
        db_exec('DELETE FROM vouchers WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Voucher deleted.');
        redirect('/company-admin/vouchers.php');
    }
    $branch_id = (int) input('branch_id', 0) ?: null;
    if ($branch_id) tenant_row_or_404('branches', $branch_id);
    $f = [
        'branch_id'         => $branch_id,
        'title'             => trim((string) input('title')),
        'description'       => (string) input('description', ''),
        'type'              => in_array(input('type'), ['percent','fixed','gift','freebie'], true) ? input('type') : 'percent',
        'value'             => input('value') !== '' ? (float) input('value') : null,
        'expiry_date'       => input('expiry_date') ?: null,
        'usage_limit'       => input('usage_limit') !== '' ? (int) input('usage_limit') : null,
        'per_member_limit'  => (int) (input('per_member_limit') ?: 1),
        'redemption_method' => in_array(input('redemption_method'), ['qr','code','manual'], true) ? input('redemption_method') : 'code',
        'status'            => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('vouchers', $id);
        db_exec(
            'UPDATE vouchers SET branch_id=?, title=?, description=?, type=?, value=?, expiry_date=?,
                                 usage_limit=?, per_member_limit=?, redemption_method=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO vouchers (company_id, branch_id, title, description, type, value, expiry_date,
                                   usage_limit, per_member_limit, redemption_method, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Voucher saved.');
    redirect('/company-admin/vouchers.php');
}

$editing  = $id ? tenant_row_or_404('vouchers', $id) : null;
$rows     = tenant_all('SELECT * FROM vouchers WHERE company_id = ? ORDER BY created_at DESC', $CID);
$branches = tenant_all('SELECT id, name FROM branches WHERE company_id = ?', $CID);

ca_open('Vouchers');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Voucher' : 'Add Voucher' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Title</label><input class="input" name="title" required value="<?= e($editing['title'] ?? '') ?>"></div>
      <div class="col"><label>Type</label>
        <select class="input" name="type">
          <?php foreach (['percent','fixed','gift','freebie'] as $t): ?>
            <option <?= ($editing['type'] ?? '')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>Value</label><input class="input" type="number" step="0.01" name="value" value="<?= e($editing['value'] ?? '') ?>"></div>
      <div class="col"><label>Branch</label>
        <select class="input" name="branch_id">
          <option value="">All branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ($editing['branch_id'] ?? 0) == $b['id'] ? 'selected':'' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Description</label>
    <textarea class="input" name="description" rows="2"><?= e($editing['description'] ?? '') ?></textarea>
    <div class="row">
      <div class="col"><label>Expiry Date</label><input class="input" type="date" name="expiry_date" value="<?= e($editing['expiry_date'] ?? '') ?>"></div>
      <div class="col"><label>Usage Limit (total)</label><input class="input" type="number" name="usage_limit" value="<?= e($editing['usage_limit'] ?? '') ?>"></div>
      <div class="col"><label>Per Member Limit</label><input class="input" type="number" name="per_member_limit" value="<?= e($editing['per_member_limit'] ?? '1') ?>"></div>
      <div class="col"><label>Redemption</label>
        <select class="input" name="redemption_method">
          <?php foreach (['code','qr','manual'] as $r): ?>
            <option <?= ($editing['redemption_method'] ?? '')===$r?'selected':'' ?>><?= $r ?></option>
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
    <p><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/vouchers.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Title</th><th>Type</th><th>Value</th><th>Expiry</th><th>Status</th><th>Claims</th><th></th></tr>
    <?php foreach ($rows as $v):
      $claims = db_one('SELECT COUNT(*) c FROM voucher_claims WHERE voucher_id = ? AND company_id = ?',
                      [(int)$v['id'], $CID])['c'];
    ?>
      <tr>
        <td><?= e($v['title']) ?></td>
        <td><?= e($v['type']) ?></td>
        <td><?= e($v['value']) ?></td>
        <td><?= e($v['expiry_date']) ?></td>
        <td><span class="badge <?= $v['status']==='active'?'green':'red' ?>"><?= e($v['status']) ?></span></td>
        <td><?= (int)$claims ?></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$v['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete voucher?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
            <button class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
