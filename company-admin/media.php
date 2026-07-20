<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (input('action') === 'delete') {
        $id = (int) input('id', 0);
        $row = db_one('SELECT * FROM media_library WHERE company_id = ? AND id = ?', [$CID, $id]);
        if ($row) {
            db_exec('DELETE FROM media_library WHERE company_id = ? AND id = ?', [$CID, $id]);
            $abs = __DIR__ . '/..' . $row['file_path'];
            if (is_file($abs)) @unlink($abs);
        }
        redirect('/company-admin/media.php');
    }
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $i => $_n) {
            $file = [
                'name'     => $_FILES['files']['name'][$i],
                'type'     => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error'    => $_FILES['files']['error'][$i],
                'size'     => $_FILES['files']['size'][$i],
            ];
            $url = save_upload($file, $CID, 'media');
            if ($url) {
                db_insert(
                    'INSERT INTO media_library (company_id, file_path, file_type, file_size, uploaded_by)
                     VALUES (?, ?, ?, ?, ?)',
                    [$CID, $url, $file['type'], $file['size'], (int) $ca['id']]
                );
            }
        }
    }
    redirect('/company-admin/media.php');
}

$rows = tenant_all('SELECT * FROM media_library WHERE company_id = ? ORDER BY created_at DESC', $CID);

ca_open('Media Library');
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Upload files</label>
    <input type="file" name="files[]" multiple accept="image/*">
    <p><button class="btn primary">Upload</button></p>
  </form>
</div>
<div class="card">
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <?php foreach ($rows as $m): ?>
      <div style="position:relative;width:140px">
        <img src="<?= e($m['file_path']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:6px;background:#eee">
        <form method="post" style="position:absolute;top:4px;right:4px" onsubmit="return confirm('Delete?')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
          <button class="btn danger" style="padding:2px 6px;font-size:11px">x</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php ca_close(); ?>
