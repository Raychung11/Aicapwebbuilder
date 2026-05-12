<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

if (!db_table_exists('packages')) {
    flash_set('error', 'Run /install.php once to enable Packages, then refresh.');
    redirect('/company-admin/packages.php');
}

$id      = (int) input('id', 0);
$package = $id ? tenant_row_or_404('packages', $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) input('action', 'save');

    // ----- Section actions (require existing package) -----
    if ($id) {
        if ($action === 'add_section') {
            $sid = db_insert(
                'INSERT INTO package_sections (package_id, company_id, title, description, sort_order)
                 VALUES (?, ?, ?, ?, ?)',
                [$id, $CID, trim((string) input('section_title')) ?: 'New section',
                 (string) input('section_description', ''),
                 (int) (input('section_sort_order') ?: 0)]
            );
            // Optional image on creation
            if (!empty($_FILES['section_image']['name'])) {
                $url = save_upload($_FILES['section_image'], $CID, 'packages');
                if ($url) {
                    db_exec('UPDATE package_sections SET image = ? WHERE id = ? AND company_id = ?',
                            [$url, $sid, $CID]);
                }
            }
            flash_set('success', 'Section added. Click Edit to add choices.');
            redirect('/company-admin/package-edit.php?id=' . $id);
        }

        if ($action === 'delete_section') {
            $sid = (int) input('section_id', 0);
            db_exec('DELETE FROM package_choices WHERE company_id = ? AND section_id = ?', [$CID, $sid]);
            db_exec('DELETE FROM package_sections WHERE company_id = ? AND id = ? AND package_id = ?', [$CID, $sid, $id]);
            flash_set('success', 'Section deleted.');
            redirect('/company-admin/package-edit.php?id=' . $id);
        }

        if ($action === 'remove_hero') {
            db_exec('UPDATE packages SET hero_image = NULL WHERE company_id = ? AND id = ?', [$CID, $id]);
            flash_set('success', 'Hero image removed.');
            redirect('/company-admin/package-edit.php?id=' . $id);
        }
    }

    // ----- Package save / create -----
    // Parse features pills (one per line, "emoji label")
    $features_raw = trim((string) input('features_raw', ''));
    $features = [];
    if ($features_raw !== '') {
        foreach (preg_split('/\r?\n/', $features_raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            // Try to split first emoji/symbol from rest
            if (preg_match('/^(\S+)\s+(.*)$/u', $line, $m)) {
                $features[] = ['icon' => $m[1], 'label' => $m[2]];
            } else {
                $features[] = ['icon' => '✓', 'label' => $line];
            }
        }
    }

    $f = [
        'title'         => trim((string) input('title')),
        'subtitle'      => trim((string) input('subtitle', '')) ?: null,
        'description'   => (string) input('description', '') ?: null,
        'badge'         => trim((string) input('badge', '')) ?: null,
        'price'         => input('price') !== '' ? (float) input('price') : null,
        'was_price'     => input('was_price') !== '' ? (float) input('was_price') : null,
        'features_json' => $features ? json_encode($features, JSON_UNESCAPED_UNICODE) : null,
        'pwp_blurb'     => trim((string) input('pwp_blurb', '')) ?: null,
        'cta_text'      => trim((string) input('cta_text', '')) ?: null,
        'cta_url'       => trim((string) input('cta_url', '')) ?: null,
        'status'        => in_array(input('status'), ['active','draft','archived'], true) ? input('status') : 'draft',
        'is_featured'   => !empty($_POST['is_featured']) ? 1 : 0,
        'sort_order'    => (int) (input('sort_order') ?: 0),
    ];

    if ($id) {
        db_exec(
            'UPDATE packages SET title=?, subtitle=?, description=?, badge=?, price=?, was_price=?,
                                 features_json=?, pwp_blurb=?, cta_text=?, cta_url=?,
                                 status=?, is_featured=?, sort_order=?
              WHERE company_id=? AND id=?',
            [...array_values($f), $CID, $id]
        );
    } else {
        $id = db_insert(
            'INSERT INTO packages (company_id, title, subtitle, description, badge, price, was_price,
                                   features_json, pwp_blurb, cta_text, cta_url, status, is_featured, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$CID, ...array_values($f)]
        );
    }

    if (!empty($_FILES['hero_image']['name'])) {
        $url = save_upload($_FILES['hero_image'], $CID, 'packages');
        if ($url) {
            db_exec('UPDATE packages SET hero_image = ? WHERE company_id = ? AND id = ?',
                    [$url, $CID, $id]);
        }
    }

    flash_set('success', 'Package saved.');
    redirect('/company-admin/package-edit.php?id=' . $id);
}

$sections = $id ? tenant_all(
    'SELECT s.*,
            (SELECT COUNT(*) FROM package_choices WHERE section_id = s.id) AS choice_count
       FROM package_sections s
      WHERE s.company_id = ? AND s.package_id = ?
      ORDER BY s.sort_order, s.id',
    $CID, [$id]
) : [];

// Absolute URL base for the Preview link — the admin may be on aicap.my
// where there's no tenant context, so we point at the tenant's subdomain.
$tenant_base = !empty($company['custom_domain'])
    ? APP_URL_SCHEME . '://' . $company['custom_domain']
    : APP_URL_SCHEME . '://' . $company['subdomain'] . '.' . APP_BASE_DOMAIN;

// Decode existing features for the textarea
$features_raw = '';
if ($package && !empty($package['features_json'])) {
    $items = json_decode($package['features_json'], true);
    if (is_array($items)) {
        foreach ($items as $it) {
            $features_raw .= ($it['icon'] ?? '✓') . ' ' . ($it['label'] ?? '') . "\n";
        }
        $features_raw = rtrim($features_raw);
    }
}

ca_open($package ? 'Edit Package' : 'New Package');
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <div class="row">
      <div class="col">
        <label>Title *</label>
        <input class="input" name="title" required value="<?= e($package['title'] ?? '') ?>"
               placeholder="e.g. Package 2 Rooms">
      </div>
      <div class="col">
        <label>Subtitle</label>
        <input class="input" name="subtitle" value="<?= e($package['subtitle'] ?? '') ?>"
               placeholder="e.g. Get 2-3 rooms fully furnished">
      </div>
      <div class="col">
        <label>Badge <span class="muted">(optional pill)</span></label>
        <input class="input" name="badge" value="<?= e($package['badge'] ?? '') ?>"
               placeholder="e.g. MOST POPULAR / LIMITED OFFER">
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Price (RM) *</label>
        <input class="input" name="price" type="number" step="0.01" min="0"
               value="<?= e($package['price'] ?? '') ?>" placeholder="6988">
      </div>
      <div class="col">
        <label>"Was" price (RM) <span class="muted">(strikethrough anchor)</span></label>
        <input class="input" name="was_price" type="number" step="0.01" min="0"
               value="<?= e($package['was_price'] ?? '') ?>" placeholder="30000">
      </div>
      <div class="col">
        <label>Status</label>
        <select class="input" name="status">
          <?php foreach (['active','draft','archived'] as $s): ?>
            <option <?= ($package['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Featured</label>
        <label style="display:flex;align-items:center;gap:8px;padding:9px 0;font-weight:500;">
          <input type="checkbox" name="is_featured" value="1"
                 <?= !empty($package['is_featured']) ? 'checked' : '' ?>>
          ⭐ Highlight on homepage
        </label>
      </div>
      <div class="col">
        <label>Sort order</label>
        <input class="input" name="sort_order" type="number" value="<?= e($package['sort_order'] ?? 0) ?>">
      </div>
    </div>

    <label>Description / "Don't spend RM 30K" message</label>
    <textarea class="input" name="description" rows="3"
              placeholder="e.g. Don't spend RM 30K on furniture. Get 2-3 rooms fully furnished from only RM 6,988!"><?= e($package['description'] ?? '') ?></textarea>

    <label>Features pills <span class="muted">(one per line — emoji + label)</span></label>
    <textarea class="input" name="features_raw" rows="5"
              placeholder="🛏️ Premium Quality&#10;🛋️ Stylish Design&#10;📦 Complete Set&#10;🚚 Delivery & Installation"><?= e($features_raw) ?></textarea>

    <label>PWP / bundled offer blurb <span class="muted">(optional)</span></label>
    <textarea class="input" name="pwp_blurb" rows="2"
              placeholder="e.g. Get 50% OFF 360° Coffee Table with any furniture package purchase!"><?= e($package['pwp_blurb'] ?? '') ?></textarea>

    <div class="row">
      <div class="col">
        <label>CTA button text</label>
        <input class="input" name="cta_text" maxlength="120"
               placeholder="Book Now" value="<?= e($package['cta_text'] ?? '') ?>">
      </div>
      <div class="col">
        <label>CTA link <span class="muted">(blank = WhatsApp the company)</span></label>
        <input class="input" name="cta_url" maxlength="500"
               placeholder="/whatsapp-redirect.php" value="<?= e($package['cta_url'] ?? '') ?>">
      </div>
    </div>

    <div class="row" style="align-items:flex-start;">
      <div class="col" style="flex:0 0 200px;">
        <label>Hero image</label>
        <div style="width:200px;height:120px;border:2px dashed #d1d5db;border-radius:10px;
                    display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;">
          <?php if (!empty($package['hero_image'])): ?>
            <img src="<?= e($package['hero_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span class="muted" style="font-size:12px;">No hero image</span>
          <?php endif; ?>
        </div>
        <input type="file" name="hero_image" accept="image/*" style="margin-top:8px;font-size:13px;">
        <p class="muted" style="margin-top:6px;font-size:12px;">Recommended 1600 × 900 landscape.</p>
        <?php if ($package && !empty($package['hero_image'])): ?>
          <button class="btn outline" type="submit"
                  onclick="this.form.elements['action'].value='remove_hero';return confirm('Remove hero image?');"
                  style="margin-top:6px;font-size:12px;padding:5px 10px;">
            Remove hero
          </button>
        <?php endif; ?>
      </div>
      <div class="col" style="align-self:flex-end;">
        <p style="margin-top:14px;">
          <button class="btn primary" type="submit">Save Package</button>
          <a class="btn outline" href="/company-admin/packages.php">Back</a>
          <?php if ($package): ?>
            <a class="btn outline" href="<?= e($tenant_base) ?>/package.php?id=<?= (int) $package['id'] ?>" target="_blank" rel="noopener">Preview ↗</a>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </form>
</div>

<?php if ($id): ?>
<div class="card">
  <h3 style="margin:0 0 10px;">Sections (<?= count($sections) ?>)</h3>
  <p class="muted" style="margin:0 0 12px;">
    Each section is a part of the package — Master Room, Living Room (Sofa),
    Dining, Hall TV Cabinet, etc. Inside a section you can add multiple
    <strong>choices</strong> (e.g. 4 different sofa designs, 8 TV cabinet designs)
    that the customer picks from.
  </p>

  <table>
    <tr><th></th><th>Title</th><th>Description</th><th>Choices</th><th>Sort</th><th></th></tr>
    <?php if (!$sections): ?>
      <tr><td colspan="6" class="muted center" style="padding:14px;">No sections yet — add one below.</td></tr>
    <?php endif; ?>
    <?php foreach ($sections as $s): ?>
      <tr>
        <td style="width:60px;">
          <?php if (!empty($s['image'])): ?>
            <img src="<?= e($s['image']) ?>" alt=""
                 style="width:50px;height:50px;object-fit:cover;border-radius:6px;background:#eee;">
          <?php endif; ?>
        </td>
        <td><strong><?= e($s['title']) ?></strong></td>
        <td class="muted" style="font-size:13px;"><?= nl2br(e($s['description'] ?? '')) ?></td>
        <td><?= (int) $s['choice_count'] ?></td>
        <td><?= (int) $s['sort_order'] ?></td>
        <td class="actions">
          <a class="btn outline" href="/company-admin/package-section-edit.php?id=<?= (int) $s['id'] ?>">Edit</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete section + all its choices?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_section">
            <input type="hidden" name="section_id" value="<?= (int) $s['id'] ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h4 style="margin:18px 0 8px;">Add a section</h4>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_section">
    <div class="row">
      <div class="col">
        <label>Section title *</label>
        <input class="input" name="section_title" required
               placeholder="e.g. Master Room / Living Room — Sofa / Hall TV Cabinet">
      </div>
      <div class="col">
        <label>Sort order</label>
        <input class="input" name="section_sort_order" type="number" value="0">
      </div>
    </div>
    <label>Description / item list</label>
    <textarea class="input" name="section_description" rows="2"
              placeholder="e.g. 1627 Queen Bedframe + Carter Queen Mattress"></textarea>
    <label>Section image (optional — can also be added on the section edit page)</label>
    <input type="file" name="section_image" accept="image/*">
    <p style="margin-top:14px;"><button class="btn primary" type="submit">Add Section</button></p>
  </form>
</div>
<?php endif; ?>

<?php ca_close(); ?>
