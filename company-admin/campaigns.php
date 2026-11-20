<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

$id = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete' && $id) {
        tenant_row_or_404('campaigns', $id);
        db_exec('DELETE FROM campaigns WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Campaign deleted.');
        redirect('/company-admin/campaigns.php');
    }
    $branch_id = (int) input('branch_id', 0) ?: null;
    if ($branch_id) tenant_row_or_404('branches', $branch_id);
    $f = [
        'branch_id'     => $branch_id,
        'name'          => trim((string) input('name')),
        'campaign_type' => in_array(input('campaign_type'), ['qr','flyer','social','event','other'], true)
                              ? input('campaign_type') : 'qr',
        'qr_slug'       => slugify((string) input('qr_slug')),
        'target_url'    => trim((string) input('target_url')),
        'status'        => in_array(input('status'), ['active','paused','ended'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('campaigns', $id);
        db_exec(
            'UPDATE campaigns SET branch_id=?, name=?, campaign_type=?, qr_slug=?, target_url=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO campaigns (company_id, branch_id, name, campaign_type, qr_slug, target_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Campaign saved.');
    redirect('/company-admin/campaigns.php');
}

$editing  = $id ? tenant_row_or_404('campaigns', $id) : null;
$rows     = tenant_all('SELECT * FROM campaigns WHERE company_id = ? ORDER BY created_at DESC', $CID);
$branches = tenant_all('SELECT id, name FROM branches WHERE company_id = ?', $CID);

ca_open('Campaigns');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Campaign' : 'Add Campaign' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Type</label>
        <select class="input" name="campaign_type">
          <?php foreach (['qr','flyer','social','event','other'] as $t): ?>
            <option <?= ($editing['campaign_type'] ?? '')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>QR Slug</label><input class="input" name="qr_slug" required value="<?= e($editing['qr_slug'] ?? '') ?>"></div>
      <div class="col"><label>Branch</label>
        <select class="input" name="branch_id">
          <option value="">All branches</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ($editing['branch_id'] ?? 0) == $b['id'] ? 'selected':'' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col"><label>Status</label>
        <select class="input" name="status">
          <?php foreach (['active','paused','ended'] as $s): ?>
            <option <?= ($editing['status'] ?? '')===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Target URL</label>
    <input class="input" name="target_url" required value="<?= e($editing['target_url'] ?? '') ?>">
    <p><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/campaigns.php">New</a><?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>Name</th><th>Type</th><th>QR Link</th><th>Scans</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $c):
      $scans = db_one('SELECT COUNT(*) c FROM campaign_scans WHERE campaign_id = ? AND company_id = ?',
                      [(int)$c['id'], $CID])['c'];
      $url = APP_URL_SCHEME . '://' . APP_BASE_DOMAIN . '/q/' . $company['slug'] . '/' . $c['qr_slug'];
    ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['campaign_type']) ?></td>
        <td><a href="<?= e($url) ?>" target="_blank"><?= e($url) ?></a></td>
        <td><?= (int)$scans ?></td>
        <td><span class="badge <?= $c['status']==='active'?'green':'red' ?>"><?= e($c['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php ca_close(); ?>
