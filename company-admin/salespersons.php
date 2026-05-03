<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/referral.php';

$id = (int) input('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete' && $id) {
        tenant_row_or_404('salespersons', $id);
        db_exec('DELETE FROM salespersons WHERE company_id = ? AND id = ?', [$CID, $id]);
        flash_set('success', 'Salesperson deleted.');
        redirect('/company-admin/salespersons.php');
    }
    if (input('action') === 'gen_code' && $id) {
        $row = tenant_row_or_404('salespersons', $id);
        $code = generate_referral_code($CID, $row['name']);
        db_exec('UPDATE salespersons SET referral_code = ? WHERE company_id = ? AND id = ?',
                [$code, $CID, $id]);
        flash_set('success', 'New referral code generated: ' . $code);
        redirect('/company-admin/salespersons.php?id=' . $id);
    }

    $branch_id = (int) input('branch_id', 0) ?: null;
    if ($branch_id) tenant_row_or_404('branches', $branch_id);

    $referral_code = strtoupper(trim((string) input('referral_code', '')));
    $referral_code = preg_replace('/[^A-Z0-9-]/', '', $referral_code) ?: null;

    // If a code was provided, ensure it's unique within the tenant
    if ($referral_code) {
        $clash = db_one(
            'SELECT id FROM salespersons WHERE company_id = ? AND referral_code = ? AND id != ?',
            [$CID, $referral_code, $id]
        );
        if ($clash) {
            flash_set('error', 'That referral code is already used by another salesperson.');
            redirect('/company-admin/salespersons.php' . ($id ? '?id=' . $id : ''));
        }
    }

    $commission_in = trim((string) input('commission_rate', ''));
    $commission_rate = $commission_in === '' ? null : (float) $commission_in;

    $f = [
        'branch_id'       => $branch_id,
        'name'            => trim((string) input('name')),
        'phone'           => (string) input('phone', ''),
        'email'           => (string) input('email', ''),
        'whatsapp_number' => (string) input('whatsapp_number', ''),
        'role'            => (string) input('role', ''),
        'referral_code'   => $referral_code,
        'commission_rate' => $commission_rate,
        'status'          => in_array(input('status'), ['active','disabled'], true) ? input('status') : 'active',
    ];
    if ($id) {
        tenant_row_or_404('salespersons', $id);
        db_exec(
            'UPDATE salespersons SET branch_id=?, name=?, phone=?, email=?, whatsapp_number=?,
                                     role=?, referral_code=?, commission_rate=?, status=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        db_insert(
            'INSERT INTO salespersons
               (company_id, branch_id, name, phone, email, whatsapp_number,
                role, referral_code, commission_rate, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }
    flash_set('success', 'Saved.');
    redirect('/company-admin/salespersons.php');
}

$editing  = $id ? tenant_row_or_404('salespersons', $id) : null;
$rows     = tenant_all('SELECT * FROM salespersons WHERE company_id = ? ORDER BY id DESC', $CID);
$branches = tenant_all('SELECT id, name FROM branches WHERE company_id = ? ORDER BY name', $CID);

// Build the public site host for share-link previews
$public_host = !empty($company['custom_domain'])
    ? $company['custom_domain']
    : ($company['subdomain'] . '.' . APP_BASE_DOMAIN);

ca_open('Salespersons & Agents');
?>
<div class="card">
  <h3 style="margin:0 0 10px"><?= $editing ? 'Edit Salesperson / Agent' : 'Add Salesperson / Agent' ?></h3>
  <p class="muted" style="margin:0 0 14px;font-size:13px;">
    Each salesperson can have a unique referral code. When a customer arrives via
    <code>?ref=CODE</code>, every voucher claim, lead and QR scan they make on this site
    is attributed back to that agent for 30 days.
  </p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

    <div class="row">
      <div class="col"><label>Name</label><input class="input" name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="col"><label>Branch</label>
        <select class="input" name="branch_id">
          <option value="">— None —</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ($editing['branch_id'] ?? 0) == $b['id'] ? 'selected':'' ?>>
              <?= e($b['name']) ?>
            </option>
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
      <div class="col"><label>Role</label><input class="input" name="role" placeholder="Agent / Senior Agent / Designer" value="<?= e($editing['role'] ?? '') ?>"></div>
    </div>

    <div class="row" style="background:#fff7ed;padding:12px 14px;border-radius:8px;border-left:3px solid #f59e0b;">
      <div class="col">
        <label>Referral code <span class="muted">(letters / numbers, 4–40 chars)</span></label>
        <input class="input" name="referral_code" maxlength="40" style="text-transform:uppercase;"
               placeholder="e.g. ALICE001"
               value="<?= e($editing['referral_code'] ?? '') ?>">
        <?php if ($editing && empty($editing['referral_code'])): ?>
          <p class="muted" style="font-size:12px;margin:6px 0 0;">
            Leave blank or
            <a href="?action=gen_code&id=<?= (int)$editing['id'] ?>"
               onclick="event.preventDefault();document.getElementById('gen-code-form').submit();">
              auto-generate one
            </a>.
          </p>
        <?php endif; ?>
      </div>
      <div class="col">
        <label>Commission rate <span class="muted">(percent, optional)</span></label>
        <input class="input" name="commission_rate" type="number" step="0.01" min="0" max="100"
               placeholder="e.g. 5.00"
               value="<?= e($editing['commission_rate'] ?? '') ?>">
        <p class="muted" style="font-size:12px;margin:6px 0 0;">Used in the Referrals report only.</p>
      </div>
    </div>

    <p style="margin-top:14px;"><button class="btn primary">Save</button>
       <?php if ($editing): ?><a class="btn outline" href="/company-admin/salespersons.php">New</a><?php endif; ?>
    </p>
  </form>

  <?php if ($editing): ?>
    <form id="gen-code-form" method="post" style="display:none;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="gen_code">
      <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
    </form>

    <?php if (!empty($editing['referral_code'])):
      $share_url = APP_URL_SCHEME . '://' . $public_host . '/?ref=' . rawurlencode($editing['referral_code']);
    ?>
      <div style="margin-top:14px;background:#ecfdf5;padding:14px;border-radius:8px;border-left:3px solid #10b981;">
        <strong style="color:#047857;">Shareable referral link:</strong>
        <div style="display:flex;gap:8px;margin-top:8px;">
          <input class="input" id="share-url" readonly value="<?= e($share_url) ?>" onclick="this.select();">
          <button type="button" class="btn outline" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value).then(()=>this.textContent='Copied ✓')">Copy</button>
        </div>
        <p class="muted" style="margin:6px 0 0;font-size:12px;">
          Share this with the agent. Anyone landing here will be tagged to them
          for 30 days for voucher claims, leads and QR scans.
        </p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin:0 0 10px;">Team (<?= count($rows) ?>)</h3>
  <table>
    <tr>
      <th>Name</th><th>Phone</th><th>Branch</th>
      <th>Referral code</th><th>Commission</th><th>Status</th><th></th>
    </tr>
    <?php foreach ($rows as $r):
      $br = $r['branch_id']
        ? db_one('SELECT name FROM branches WHERE id = ? AND company_id = ?', [(int)$r['branch_id'], $CID])
        : null;
    ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($br['name'] ?? '') ?></td>
        <td>
          <?php if (!empty($r['referral_code'])): ?>
            <code style="background:#fff7ed;padding:2px 8px;border-radius:6px;color:#b45309;">
              <?= e($r['referral_code']) ?>
            </code>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
        <td><?= $r['commission_rate'] !== null ? e($r['commission_rate']) . '%' : '<span class="muted">—</span>' ?></td>
        <td><span class="badge <?= $r['status']==='active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
        <td class="actions">
          <a class="btn outline" href="?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this salesperson?')">
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
