<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

// Safety net for partial deploys
if (!function_exists('db_table_exists')) {
    function db_table_exists(string $name): bool {
        try {
            $row = db_one(
                'SELECT 1 AS x FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$name]
            );
            return (bool) $row;
        } catch (Throwable $e) { return false; }
    }
}

if (!db_table_exists('categories') || !db_table_exists('subcategories')) {
    ca_open('Categories');
    ?>
    <div class="card">
      <h3 style="margin:0 0 8px;">📂 Categories — one quick setup step</h3>
      <p>The Categories feature uses two new tables that don't exist on your database yet.</p>
      <p><strong>Easy fix:</strong> upload <code>install.php</code> from the repo, visit
         <code>https://aicap.my/install.php</code> once, then delete it again.</p>
      <p><strong>Or in phpMyAdmin:</strong></p>
      <pre style="background:#0b1020;color:#cbd5e1;padding:14px;border-radius:8px;overflow:auto;font-size:12px;line-height:1.45;">CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_cat (company_id, name),
  KEY idx_cat_sort (company_id, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subcategories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_sub (company_id, category_id, name),
  KEY idx_sub_category (category_id, sort_order, id),
  KEY idx_sub_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
    </div>
    <?php
    ca_close();
    exit;
}

// ---------- POST handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) input('action', '');

    // ---- Categories ----
    if ($action === 'add_category') {
        $name = trim((string) input('name'));
        if ($name !== '') {
            $maxRow = db_one('SELECT IFNULL(MAX(sort_order),0) m FROM categories WHERE company_id = ?', [$CID]);
            $sort = (int) ($maxRow['m'] ?? 0) + 10;
            try {
                db_insert(
                    'INSERT INTO categories (company_id, name, sort_order) VALUES (?, ?, ?)',
                    [$CID, $name, $sort]
                );
                flash_set('success', 'Category added: ' . $name);
            } catch (Throwable $e) {
                flash_set('error', 'That category name already exists.');
            }
        }
        redirect('/company-admin/categories.php');
    }

    if ($action === 'rename_category') {
        $cid_t   = (int) input('id', 0);
        $newName = trim((string) input('name'));
        $cascade = !empty($_POST['cascade']);
        if ($cid_t && $newName !== '') {
            $row = db_one('SELECT * FROM categories WHERE id = ? AND company_id = ?', [$cid_t, $CID]);
            if ($row) {
                $oldName = $row['name'];
                try {
                    db_exec('UPDATE categories SET name = ? WHERE id = ? AND company_id = ?',
                            [$newName, $cid_t, $CID]);
                    if ($cascade) {
                        db_exec('UPDATE products SET category = ? WHERE company_id = ? AND category = ?',
                                [$newName, $CID, $oldName]);
                    }
                    flash_set('success', 'Renamed: ' . $oldName . ' → ' . $newName
                        . ($cascade ? ' (and updated all products)' : ''));
                } catch (Throwable $e) {
                    flash_set('error', 'A category with that name already exists.');
                }
            }
        }
        redirect('/company-admin/categories.php?cat=' . $cid_t);
    }

    if ($action === 'delete_category') {
        $cid_t = (int) input('id', 0);
        if ($cid_t) {
            $row = db_one('SELECT * FROM categories WHERE id = ? AND company_id = ?', [$cid_t, $CID]);
            if ($row) {
                $clear_products = !empty($_POST['clear_products']);
                db_exec('DELETE FROM subcategories WHERE company_id = ? AND category_id = ?', [$CID, $cid_t]);
                db_exec('DELETE FROM categories WHERE id = ? AND company_id = ?', [$cid_t, $CID]);
                if ($clear_products) {
                    db_exec(
                        'UPDATE products SET category = NULL, subcategory = NULL
                          WHERE company_id = ? AND category = ?',
                        [$CID, $row['name']]
                    );
                }
                flash_set('success', 'Category deleted: ' . $row['name']
                    . ($clear_products ? ' (and cleared from products)' : ''));
            }
        }
        redirect('/company-admin/categories.php');
    }

    if (($action === 'move_category_up' || $action === 'move_category_down')) {
        $cid_t = (int) input('id', 0);
        if ($cid_t) {
            // Renormalize first
            $sort = 10;
            foreach (db_all('SELECT id FROM categories WHERE company_id = ? ORDER BY sort_order, id', [$CID]) as $r) {
                db_exec('UPDATE categories SET sort_order = ? WHERE id = ?', [$sort, (int) $r['id']]);
                $sort += 10;
            }
            $self = db_one('SELECT id, sort_order FROM categories WHERE id = ? AND company_id = ?', [$cid_t, $CID]);
            if ($self) {
                $op = $action === 'move_category_up' ? '<' : '>';
                $order = $action === 'move_category_up' ? 'DESC' : 'ASC';
                $neighbor = db_one(
                    "SELECT id, sort_order FROM categories
                      WHERE company_id = ? AND sort_order {$op} ?
                      ORDER BY sort_order {$order} LIMIT 1",
                    [$CID, (int) $self['sort_order']]
                );
                if ($neighbor) {
                    db_exec('UPDATE categories SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $neighbor['sort_order'], $cid_t, $CID]);
                    db_exec('UPDATE categories SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $self['sort_order'], (int) $neighbor['id'], $CID]);
                }
            }
        }
        redirect('/company-admin/categories.php?cat=' . $cid_t);
    }

    // ---- Subcategories ----
    if ($action === 'add_subcategory') {
        $catId = (int) input('category_id', 0);
        $name  = trim((string) input('name'));
        if ($catId && $name !== '') {
            $catRow = db_one('SELECT id FROM categories WHERE id = ? AND company_id = ?', [$catId, $CID]);
            if ($catRow) {
                $maxRow = db_one('SELECT IFNULL(MAX(sort_order),0) m FROM subcategories WHERE category_id = ?', [$catId]);
                $sort = (int) ($maxRow['m'] ?? 0) + 10;
                try {
                    db_insert(
                        'INSERT INTO subcategories (company_id, category_id, name, sort_order)
                         VALUES (?, ?, ?, ?)',
                        [$CID, $catId, $name, $sort]
                    );
                    flash_set('success', 'Subcategory added: ' . $name);
                } catch (Throwable $e) {
                    flash_set('error', 'That subcategory already exists in this category.');
                }
            }
        }
        redirect('/company-admin/categories.php?cat=' . $catId);
    }

    if ($action === 'rename_subcategory') {
        $sid     = (int) input('id', 0);
        $newName = trim((string) input('name'));
        $cascade = !empty($_POST['cascade']);
        $back    = 0;
        if ($sid && $newName !== '') {
            $row = db_one(
                'SELECT s.*, c.name AS cat_name FROM subcategories s
                   JOIN categories c ON c.id = s.category_id
                  WHERE s.id = ? AND s.company_id = ?',
                [$sid, $CID]
            );
            if ($row) {
                $back = (int) $row['category_id'];
                try {
                    db_exec('UPDATE subcategories SET name = ? WHERE id = ? AND company_id = ?',
                            [$newName, $sid, $CID]);
                    if ($cascade) {
                        db_exec(
                            'UPDATE products SET subcategory = ?
                              WHERE company_id = ? AND category = ? AND subcategory = ?',
                            [$newName, $CID, $row['cat_name'], $row['name']]
                        );
                    }
                    flash_set('success', 'Renamed: ' . $row['name'] . ' → ' . $newName
                        . ($cascade ? ' (and updated all products)' : ''));
                } catch (Throwable $e) {
                    flash_set('error', 'That subcategory already exists.');
                }
            }
        }
        redirect('/company-admin/categories.php' . ($back ? '?cat=' . $back : ''));
    }

    if ($action === 'delete_subcategory') {
        $sid = (int) input('id', 0);
        $back = 0;
        if ($sid) {
            $row = db_one(
                'SELECT s.*, c.name AS cat_name FROM subcategories s
                   JOIN categories c ON c.id = s.category_id
                  WHERE s.id = ? AND s.company_id = ?',
                [$sid, $CID]
            );
            if ($row) {
                $back  = (int) $row['category_id'];
                $clear = !empty($_POST['clear_products']);
                db_exec('DELETE FROM subcategories WHERE id = ? AND company_id = ?', [$sid, $CID]);
                if ($clear) {
                    db_exec(
                        'UPDATE products SET subcategory = NULL
                          WHERE company_id = ? AND category = ? AND subcategory = ?',
                        [$CID, $row['cat_name'], $row['name']]
                    );
                }
                flash_set('success', 'Subcategory deleted: ' . $row['name']);
            }
        }
        redirect('/company-admin/categories.php' . ($back ? '?cat=' . $back : ''));
    }

    if (($action === 'move_subcategory_up' || $action === 'move_subcategory_down')) {
        $sid = (int) input('id', 0);
        if ($sid) {
            $self = db_one(
                'SELECT id, category_id, sort_order FROM subcategories
                  WHERE id = ? AND company_id = ?',
                [$sid, $CID]
            );
            if ($self) {
                $catId = (int) $self['category_id'];
                // Renormalize within this category
                $sort = 10;
                foreach (db_all(
                    'SELECT id FROM subcategories
                      WHERE company_id = ? AND category_id = ?
                      ORDER BY sort_order, id',
                    [$CID, $catId]
                ) as $r) {
                    db_exec('UPDATE subcategories SET sort_order = ? WHERE id = ?', [$sort, (int) $r['id']]);
                    $sort += 10;
                }
                $self = db_one(
                    'SELECT id, sort_order FROM subcategories WHERE id = ? AND company_id = ?',
                    [$sid, $CID]
                );
                $op = $action === 'move_subcategory_up' ? '<' : '>';
                $order = $action === 'move_subcategory_up' ? 'DESC' : 'ASC';
                $neighbor = db_one(
                    "SELECT id, sort_order FROM subcategories
                      WHERE company_id = ? AND category_id = ? AND sort_order {$op} ?
                      ORDER BY sort_order {$order} LIMIT 1",
                    [$CID, $catId, (int) $self['sort_order']]
                );
                if ($neighbor) {
                    db_exec('UPDATE subcategories SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $neighbor['sort_order'], $sid, $CID]);
                    db_exec('UPDATE subcategories SET sort_order = ? WHERE id = ? AND company_id = ?',
                            [(int) $self['sort_order'], (int) $neighbor['id'], $CID]);
                }
                redirect('/company-admin/categories.php?cat=' . $catId);
            }
        }
        redirect('/company-admin/categories.php');
    }

    if ($action === 'import_existing') {
        $imported_cats = 0;
        $imported_subs = 0;
        $rows = tenant_all(
            'SELECT DISTINCT category, subcategory FROM products
              WHERE company_id = ? AND category IS NOT NULL AND category != ""',
            $CID
        );
        // First pass: categories
        $maxRow = db_one('SELECT IFNULL(MAX(sort_order),0) m FROM categories WHERE company_id = ?', [$CID]);
        $sort = (int) ($maxRow['m'] ?? 0) + 10;
        foreach ($rows as $r) {
            $cat = trim((string) $r['category']);
            if ($cat === '') continue;
            $exists = db_one('SELECT id FROM categories WHERE company_id = ? AND name = ?', [$CID, $cat]);
            if (!$exists) {
                db_insert('INSERT INTO categories (company_id, name, sort_order) VALUES (?, ?, ?)',
                          [$CID, $cat, $sort]);
                $sort += 10;
                $imported_cats++;
            }
        }
        // Second pass: subcategories
        foreach ($rows as $r) {
            $cat = trim((string) $r['category']);
            $sub = trim((string) ($r['subcategory'] ?? ''));
            if ($cat === '' || $sub === '') continue;
            $catRow = db_one('SELECT id FROM categories WHERE company_id = ? AND name = ?', [$CID, $cat]);
            if (!$catRow) continue;
            $exists = db_one(
                'SELECT id FROM subcategories WHERE company_id = ? AND category_id = ? AND name = ?',
                [$CID, (int) $catRow['id'], $sub]
            );
            if (!$exists) {
                $maxSubRow = db_one('SELECT IFNULL(MAX(sort_order),0) m FROM subcategories WHERE category_id = ?',
                                    [(int) $catRow['id']]);
                $subSort = (int) ($maxSubRow['m'] ?? 0) + 10;
                db_insert(
                    'INSERT INTO subcategories (company_id, category_id, name, sort_order)
                     VALUES (?, ?, ?, ?)',
                    [$CID, (int) $catRow['id'], $sub, $subSort]
                );
                $imported_subs++;
            }
        }
        flash_set('success', "Imported {$imported_cats} categories and {$imported_subs} subcategories from existing products.");
        redirect('/company-admin/categories.php');
    }
}

// ---------- Read ----------
$categories = db_all(
    'SELECT c.*,
            (SELECT COUNT(*) FROM products
              WHERE company_id = ? AND category = c.name) AS prod_count,
            (SELECT COUNT(*) FROM subcategories
              WHERE category_id = c.id AND company_id = ?) AS sub_count
       FROM categories c
      WHERE c.company_id = ?
      ORDER BY c.sort_order, c.id',
    [$CID, $CID, $CID]
);

$selected_cat_id = (int) input('cat', 0);
$selected_cat = null;
$subs = [];
if ($selected_cat_id) {
    $selected_cat = db_one('SELECT * FROM categories WHERE id = ? AND company_id = ?',
                           [$selected_cat_id, $CID]);
    if ($selected_cat) {
        // Use db_all directly so the placeholder order matches the params.
        // (tenant_all prepends $CID which would shift everything by one and
        // bind $CID to the wrong column.)
        $subs = db_all(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM products
                      WHERE company_id = ? AND category = ? AND subcategory = s.name) AS prod_count
               FROM subcategories s
              WHERE s.company_id = ? AND s.category_id = ?
              ORDER BY s.sort_order, s.id',
            [$CID, $selected_cat['name'], $CID, $selected_cat_id]
        );
    } else {
        $selected_cat_id = 0;
    }
}

$has_legacy = (int) (db_one(
    'SELECT COUNT(*) c FROM products
      WHERE company_id = ? AND category IS NOT NULL AND category != ""
        AND category NOT IN (SELECT name FROM categories WHERE company_id = ?)',
    [$CID, $CID]
)['c'] ?? 0);

ca_open('Product Categories');
?>
<style>
.cat-pane { display: grid; gap: 16px; grid-template-columns: 1fr; }
@media (min-width: 880px) { .cat-pane { grid-template-columns: 1fr 1.4fr; } }
.cat-list, .sub-list { display: grid; gap: 4px; }
.cat-row, .sub-row {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff;
}
.cat-row:hover, .sub-row:hover { border-color: #c7d2fe; }
.cat-row.active { background: #eef2ff; border-color: #6366f1; }
.cat-row .name, .sub-row .name { flex: 1; font-weight: 600; }
.cat-row .count, .sub-row .count {
  background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 999px; font-size: 11px;
}
.move-btn {
  background: transparent; border: 0; cursor: pointer; padding: 4px 6px;
  border-radius: 4px; color: #6b7280; font-size: 14px;
}
.move-btn:hover { background: #f3f4f6; color: #111; }
.move-btn:disabled { opacity: .3; cursor: default; }
.tiny-btn {
  background: transparent; border: 0; cursor: pointer; font-size: 12px;
  color: #2563eb; padding: 4px 8px; font-family: inherit;
}
.tiny-btn.danger { color: #dc2626; }
.tiny-btn:hover { text-decoration: underline; }
.row-form { display: inline-flex; gap: 4px; align-items: center; margin: 0; }
.row-form input[type=text] {
  padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 6px; font: inherit;
  font-size: 13px; min-width: 140px;
}
.empty { color: #9ca3af; padding: 16px; text-align: center; font-size: 13px; }
</style>

<div class="card">
  <p class="muted" style="margin:0;font-size:13px;">
    Curate the categories and subcategories your tenant uses. Renaming or
    deleting cascades to your products only when you tick the cascade box.
    Click a category on the left to manage its subcategories on the right.
    <?php if ($has_legacy > 0): ?>
      <br><strong>📥 <?= (int) $has_legacy ?> products use a category not yet in this list.</strong>
      <form method="post" style="display:inline;margin-left:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_existing">
        <button class="tiny-btn" type="submit"
                onclick="return confirm('Import all existing categories and subcategories from your products into this list?')">
          Import them now →
        </button>
      </form>
    <?php endif; ?>
  </p>
</div>

<div class="cat-pane">

  <!-- ===== Categories pane ===== -->
  <div class="card">
    <h3 style="margin:0 0 10px;">Categories (<?= count($categories) ?>)</h3>

    <?php if (!$categories): ?>
      <div class="empty">No categories yet. Add one below to get started.</div>
    <?php else: ?>
      <div class="cat-list">
        <?php foreach ($categories as $i => $c): ?>
          <div class="cat-row <?= $selected_cat_id === (int) $c['id'] ? 'active' : '' ?>">
            <a href="?cat=<?= (int) $c['id'] ?>" style="flex:1;text-decoration:none;color:inherit;display:flex;align-items:center;gap:8px;">
              <span class="name"><?= e($c['name']) ?></span>
              <span class="count"><?= (int) $c['sub_count'] ?> subs</span>
              <span class="count"><?= (int) $c['prod_count'] ?> products</span>
            </a>
            <form method="post" class="row-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="move-btn" type="submit" name="action" value="move_category_up" title="Move up"
                      <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
              <button class="move-btn" type="submit" name="action" value="move_category_down" title="Move down"
                      <?= $i === count($categories) - 1 ? 'disabled' : '' ?>>↓</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h4 style="margin:18px 0 6px;font-size:14px;">Add category</h4>
    <form method="post" style="display:flex;gap:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <input class="input" name="name" required placeholder="e.g. Living Room" style="flex:1;">
      <button class="btn primary" type="submit">+ Add</button>
    </form>
  </div>

  <!-- ===== Subcategories pane ===== -->
  <div class="card">
    <?php if (!$selected_cat): ?>
      <div class="empty">← Select a category on the left to manage its subcategories.</div>
    <?php else: ?>
      <h3 style="margin:0 0 4px;">
        <?= e($selected_cat['name']) ?>
        <span class="muted" style="font-weight:normal;font-size:14px;">· <?= count($subs) ?> subcategories</span>
      </h3>

      <details style="margin-bottom:10px;">
        <summary style="cursor:pointer;font-size:13px;color:#6b7280;">⚙️ Rename or delete category</summary>
        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:8px;background:#f9fafb;padding:12px;border-radius:8px;">
          <form method="post" class="row-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="rename_category">
            <input type="hidden" name="id" value="<?= (int) $selected_cat['id'] ?>">
            <input type="text" name="name" value="<?= e($selected_cat['name']) ?>" required>
            <label style="font-size:12px;color:#374151;display:inline-flex;gap:4px;align-items:center;">
              <input type="checkbox" name="cascade" value="1" checked> rename in products too
            </label>
            <button class="tiny-btn" type="submit">Rename</button>
          </form>
          <form method="post" class="row-form" onsubmit="return confirm('Delete category <?= e($selected_cat['name']) ?> and all its subcategories?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="id" value="<?= (int) $selected_cat['id'] ?>">
            <label style="font-size:12px;color:#374151;display:inline-flex;gap:4px;align-items:center;">
              <input type="checkbox" name="clear_products" value="1"> also clear from products
            </label>
            <button class="tiny-btn danger" type="submit">Delete category</button>
          </form>
        </div>
      </details>

      <?php if (!$subs): ?>
        <div class="empty">No subcategories yet under "<?= e($selected_cat['name']) ?>". Add one below.</div>
      <?php else: ?>
        <div class="sub-list">
          <?php foreach ($subs as $i => $s): ?>
            <div class="sub-row">
              <span class="name"><?= e($s['name']) ?></span>
              <span class="count"><?= (int) $s['prod_count'] ?> products</span>

              <form method="post" class="row-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button class="move-btn" type="submit" name="action" value="move_subcategory_up" title="Move up"
                        <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                <button class="move-btn" type="submit" name="action" value="move_subcategory_down" title="Move down"
                        <?= $i === count($subs) - 1 ? 'disabled' : '' ?>>↓</button>
              </form>

              <details>
                <summary style="cursor:pointer;font-size:12px;color:#2563eb;">edit</summary>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;background:#f9fafb;padding:10px;border-radius:6px;">
                  <form method="post" class="row-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="rename_subcategory">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <input type="text" name="name" value="<?= e($s['name']) ?>" required>
                    <label style="font-size:12px;display:inline-flex;gap:4px;align-items:center;">
                      <input type="checkbox" name="cascade" value="1" checked> products too
                    </label>
                    <button class="tiny-btn" type="submit">Rename</button>
                  </form>
                  <form method="post" class="row-form" onsubmit="return confirm('Delete subcategory <?= e($s['name']) ?>?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_subcategory">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <label style="font-size:12px;display:inline-flex;gap:4px;align-items:center;">
                      <input type="checkbox" name="clear_products" value="1"> clear from products
                    </label>
                    <button class="tiny-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </details>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h4 style="margin:18px 0 6px;font-size:14px;">Add subcategory under <?= e($selected_cat['name']) ?></h4>
      <form method="post" style="display:flex;gap:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_subcategory">
        <input type="hidden" name="category_id" value="<?= (int) $selected_cat['id'] ?>">
        <input class="input" name="name" required placeholder="e.g. Sofa" style="flex:1;">
        <button class="btn primary" type="submit">+ Add</button>
      </form>
    <?php endif; ?>
  </div>

</div>

<?php ca_close(); ?>
