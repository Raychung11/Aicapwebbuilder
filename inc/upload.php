<?php
require_once __DIR__ . '/config.php';

/**
 * Save an uploaded file to /uploads/company/{company_id}/{folder}/.
 *
 * @return string|null  Web-relative path (e.g. /uploads/company/1/products/abc.jpg) or null on failure.
 */
function save_upload(array $file, int $company_id, string $folder): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED_MIME, true)) {
        return null;
    }
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => null,
    };
    if (!$ext) return null;

    $folder    = preg_replace('/[^a-z0-9_-]/i', '', $folder);
    $targetDir = UPLOAD_DIR . "/company/{$company_id}/{$folder}";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $targetDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return UPLOAD_URL . "/company/{$company_id}/{$folder}/{$name}";
}
