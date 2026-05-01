<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/password_reset.php';

// Only the owner role can manage staff
if (($ca['role'] ?? '') !== 'owner') {
    flash_set('error', 'Only the owner can manage staff and access.');
    redirect('/company-admin/index.php');
}

$action = (string) input('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'create') {
        $name  = trim((string) input('name'));
        $email = trim((string) input('email'));
        $role  = in_array(input('role'), ['owner','manager','staff'], true) ? input('role') : 'staff';
        $pass  = (string) input('password');

        if ($name === '' || $email === '' || strlen($pass) < 8) {
            flash_set('error', 'Name, email and a password of at least 8 characters are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'That email address is not valid.');
        } elseif (db_one('SELECT id FROM company_admins WHERE email = ? LIMIT 1', [$email])) {
            flash_set('error', 'That email is already registered to another admin account.');
        } else {
            db_insert(
                'INSERT INTO company_admins (company_id, name, email, password_hash, role, status)
                 VALUES (?, ?, ?, ?, ?, "active")',
                [$CID, $name, $email,
                 password_hash($pass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]), $role]
            );
            flash_set('success', 'Staff added. Share the password securely or click "Send reset link" to email a self-set link.');
        }
        redirect('/company-admin/staff.php');
    }

    $id  = (int) input('id', 0);
    $row = $id ? db_one('SELECT * FROM company_admins WHERE id = ? AND company_id = ? LIMIT 1', [$id, $CID]) : null;
    if (!$row) {
        flash_set('error', 'Staff member not found.');
        redirect('/company-admin/staff.php');
    }

    if ($action === 'update') {
        $name   = trim((string) input('name'));
        $role   = in_array(input('role'),   ['owner','manager','staff'], true) ? input('role')   : $row['role'];
        $status = in_array(input('status'), ['active','disabled'],         true) ? input('status') : 'active';

        // Self-protection: can't demote yourself if last owner
        if ($id === (int) $ca['id'] && $role !== 'owner') {
            $other = (int) (db_one(
                'SELECT COUNT(*) c FROM company_admins
                  WHERE company_id = ? AND role = "owner" AND id != ? AND status = "active"',
                [$CID, $id]
            )['c'] ?? 0);
            if ($other === 0) {
                flash_set('error', 'You cannot demote yourself — you are the only active owner.');
                redirect('/company-admin/staff.php?edit=' . $id);
            }
        }
        // Self-protection: can't disable yourself
        if ($id === (int) $ca['id'] && $status === 'disabled') {
            flash_set('error', 'You cannot disable your own account.');
            redirect('/company-admin/staff.php?edit=' . $id);
        }

        db_exec(
            'UPDATE company_admins SET name = ?, role = ?, status = ?
              WHERE id = ? AND company_id = ?',
            [$name, $role, $status, $id, $CID]
        );
        flash_set('success', 'Staff member updated.');
        redirect('/company-admin/staff.php');
    }

    if ($action === 'reset_password') {
        $new_pass = (string) input('new_password');
        if (strlen($new_pass) < 8) {
            flash_set('error', 'Password must be at least 8 characters.');
        } else {
            db_exec(
                'UPDATE company_admins SET password_hash = ?
                  WHERE id = ? AND company_id = ?',
                [password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]), $id, $CID]
            );
            flash_set('success', 'Password reset. Share the new password securely.');
        }
        redirect('/company-admin/staff.php?edit=' . $id);
    }

    if ($action === 'send_reset_link') {
        pw_request_reset('company_admin', $row['email'], '/company-admin/reset-password.php');
        flash_set('success', 'Password reset link sent to ' . $row['email'] . '.');
        redirect('/company-admin/staff.php');
    }

    if ($action === 'delete') {
        if ($id === (int) $ca['id']) {
            flash_set('error', 'You cannot delete your own account.');
            redirect('/company-admin/staff.php');
        }
        if ($row['role'] === 'owner') {
            $other = (int) (db_one(
                'SELECT COUNT(*) c FROM company_admins
                  WHERE company_id = ? AND role = "owner" AND id != ?',
                [$CID, $id]
            )['c'] ?? 0);
            if ($other === 0) {
                flash_set('error', 'Cannot delete the last owner.');
                redirect('/company-admin/staff.php');
            }
        }
        db_exec('DELETE FROM company_admins WHERE id = ? AND company_id = ?', [$id, $CID]);
        flash_set('success', 'Staff member removed.');
        redirect('/company-admin/staff.php');
    }
}

$staff   = tenant_all(
    'SELECT * FROM company_admins WHERE company_id = ?
      ORDER BY (role = "owner") DESC, (role = "manager") DESC, name',
    $CID
);
$edit_id = (int) input('edit', 0);
$editing = $edit_id ? db_one(
    'SELECT * FROM company_admins WHERE id = ? AND company_id = ? LIMIT 1',
    [$edit_id, $CID]
) : null;

ca_open('Staff & Access');
?>

<div class="card">
  <h3 style="margin:0 0 6px;">Add a new staff member</h3>
  <p class="muted" style="margin:0 0 14px;">
    Each entry creates a login at <code>/company-admin/login.php</code> for
    <strong><?= e($company['name']) ?></strong>. Roles control what they can do.
  </p>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="row">
      <div class="col">
        <label>Full name</label>
        <input class="input" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Email</label>
        <input class="input" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="col">
        <label>Role</label>
        <select class="input" name="role">
          <option value="staff">Staff</option>
          <option value="manager" selected>Manager</option>
          <option value="owner">Owner</option>
        </select>
      </div>
      <div class="col">
        <label>Initial password</label>
        <input class="input" type="text" name="password" minlength="8" required
               value="<?= e(rand_code(12)) ?>">
        <p class="muted" style="font-size:12px;margin-top:4px;">
          Random password pre-filled. After creating, click "Send reset link"
          on their row so they can set their own.
        </p>
      </div>
    </div>
    <p style="margin-top:14px;"><button class="btn primary">Add Staff</button></p>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 10px;">Current team (<?= count($staff) ?>)</h3>
  <table>
    <tr>
      <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Added</th><th></th>
    </tr>
    <?php foreach ($staff as $s):
      $is_self = ((int) $s['id']) === ((int) $ca['id']);
    ?>
      <tr>
        <td>
          <?= e($s['name']) ?>
          <?php if ($is_self): ?><span class="badge green" style="margin-left:6px;">you</span><?php endif; ?>
        </td>
        <td><?= e($s['email']) ?></td>
        <td><span class="badge <?= $s['role']==='owner'?'green':'' ?>"><?= e(ucfirst($s['role'])) ?></span></td>
        <td><span class="badge <?= $s['status']==='active'?'green':'red' ?>"><?= e($s['status']) ?></span></td>
        <td class="muted" style="font-size:12px;"><?= e(date('Y-m-d', strtotime($s['created_at']))) ?></td>
        <td class="actions">
          <a class="btn outline" href="?edit=<?= (int) $s['id'] ?>">Edit</a>
          <form method="post" style="display:inline"
                onsubmit="return confirm('Send a password-reset link to <?= e($s['email']) ?>?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_reset_link">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn outline">Send reset link</button>
          </form>
          <?php if (!$is_self): ?>
            <form method="post" style="display:inline"
                  onsubmit="return confirm('Permanently remove <?= e($s['name']) ?>?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn danger">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php if ($editing): ?>
<div class="card">
  <h3 style="margin:0 0 10px;">Edit <?= e($editing['name']) ?></h3>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <div class="row">
      <div class="col">
        <label>Name</label>
        <input class="input" name="name" required value="<?= e($editing['name']) ?>">
      </div>
      <div class="col">
        <label>Role</label>
        <select class="input" name="role">
          <?php foreach (['owner','manager','staff'] as $r): ?>
            <option value="<?= $r ?>" <?= $editing['role'] === $r ? 'selected' : '' ?>>
              <?= ucfirst($r) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Status</label>
        <select class="input" name="status">
          <option value="active"   <?= $editing['status']==='active'?'selected':'' ?>>Active</option>
          <option value="disabled" <?= $editing['status']==='disabled'?'selected':'' ?>>Disabled</option>
        </select>
      </div>
    </div>
    <p style="margin-top:14px;">
      <button class="btn primary">Save</button>
      <a class="btn outline" href="/company-admin/staff.php">Cancel</a>
    </p>
  </form>

  <h4 style="margin:18px 0 8px;">Reset password directly</h4>
  <p class="muted" style="margin:0 0 10px;font-size:13px;">
    Set a new password for this staff member. For self-service, use
    "Send reset link" instead — it emails them a one-time link.
  </p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <div class="row">
      <div class="col" style="flex:2;">
        <input class="input" type="text" name="new_password" minlength="8"
               placeholder="New password (min 8 chars)" value="<?= e(rand_code(12)) ?>">
      </div>
      <div class="col">
        <button class="btn primary" type="submit">Set new password</button>
      </div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card" style="background:#f9fafb;border:1px dashed #e5e7eb;">
  <h4 style="margin:0 0 6px;font-size:14px;">About roles</h4>
  <ul class="muted" style="margin:0;padding-left:20px;font-size:13px;line-height:1.7;">
    <li><strong>Owner</strong> — full admin access including this Staff page.</li>
    <li><strong>Manager</strong> — full admin access for everyday operations (products, vouchers, leads, branches, settings) but cannot manage staff.</li>
    <li><strong>Staff</strong> — full admin access for everyday operations; reserved for future fine-grained restrictions.</li>
  </ul>
</div>

<?php ca_close(); ?>
