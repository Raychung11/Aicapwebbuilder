<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) input('id', 0);
    if ($id) {
        tenant_row_or_404('leads', $id);
        $status         = in_array(input('status'), ['new','contacted','converted','closed','lost'], true)
                              ? input('status') : 'new';
        $salesperson_id = (int) input('salesperson_id', 0) ?: null;
        if ($salesperson_id) tenant_row_or_404('salespersons', $salesperson_id);
        $notes = (string) input('notes', '');
        db_exec(
            'UPDATE leads SET status=?, salesperson_id=?, notes=? WHERE company_id=? AND id=?',
            [$status, $salesperson_id, $notes, $CID, $id]
        );
        flash_set('success', 'Lead updated.');
    }
    redirect('/company-admin/leads.php');
}

$leads = tenant_all(
    'SELECT l.*,
            (SELECT name FROM salespersons WHERE id = l.salesperson_id) AS sp_name,
            (SELECT name FROM products WHERE id = l.product_id) AS product_name
       FROM leads l
      WHERE l.company_id = ?
      ORDER BY l.created_at DESC
      LIMIT 200',
    $CID
);
$sps = tenant_all('SELECT id, name FROM salespersons WHERE company_id = ? AND status = "active"', $CID);

ca_open('Leads');
?>
<div class="card">
  <table>
    <tr>
      <th>Date</th><th>Source</th><th>Customer</th><th>Phone</th>
      <th>Product</th><th>Status</th><th>Assigned</th><th></th>
    </tr>
    <?php foreach ($leads as $l): ?>
      <tr>
        <td><?= e($l['created_at']) ?></td>
        <td><?= e($l['source']) ?></td>
        <td><?= e($l['customer_name']) ?></td>
        <td><?= e($l['customer_phone']) ?></td>
        <td><?= e($l['product_name'] ?? '') ?></td>
        <td><span class="badge"><?= e($l['status']) ?></span></td>
        <td><?= e($l['sp_name'] ?? '—') ?></td>
        <td>
          <details>
            <summary class="btn outline">Update</summary>
            <form method="post" style="margin-top:6px;min-width:240px">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <label>Status</label>
              <select class="input" name="status">
                <?php foreach (['new','contacted','converted','closed','lost'] as $s): ?>
                  <option <?= $l['status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <label>Assigned</label>
              <select class="input" name="salesperson_id">
                <option value="">— none —</option>
                <?php foreach ($sps as $sp): ?>
                  <option value="<?= (int)$sp['id'] ?>" <?= $l['salesperson_id']==$sp['id']?'selected':'' ?>><?= e($sp['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <label>Notes</label>
              <textarea class="input" name="notes" rows="2"><?= e($l['notes'] ?? '') ?></textarea>
              <p><button class="btn primary">Save</button></p>
            </form>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
