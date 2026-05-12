<?php
/**
 * Tenant subdomain bootstrap.
 *
 * Drop this file into the per-subdomain folder when Hostinger doesn't let
 * the subdomain share `public_html` directly. It dispatches every URL to
 * the matching file in the parent app (one directory up), so /catalog.php,
 * /member-login.php, /admin/login.php, /uploads/... and the pretty URLs
 * (/products/<slug>, /packages/<slug>, /p/<slug>, /q/<co>/<key>) all keep
 * working on the subdomain.
 *
 * Pair this file with the .htaccess from the same docs/tenant-bootstrap/
 * folder (Apache rewrites everything that doesn't exist locally to here).
 */
$base = realpath(__DIR__ . '/..');
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rel  = ltrim($uri, '/');

// ----- Pretty URLs (mirror the parent public_html/.htaccess rules) -----
$pretty = [
    // /q/<company_slug>/<qr_slug>
    '#^q/([a-z0-9_-]+)/([a-z0-9_-]+)/?$#i' => function ($m) {
        $_GET['c'] = $m[1]; $_GET['k'] = $m[2];
        return 'q.php';
    },
    // /p/<slug>            → /page.php
    '#^p/([a-z0-9_-]+)/?$#i' => function ($m) {
        $_GET['slug'] = $m[1]; return 'page.php';
    },
    // /products/<slug>     → /product.php
    '#^products/([a-z0-9_-]+)/?$#i' => function ($m) {
        $_GET['slug'] = $m[1]; return 'product.php';
    },
    // /packages/<slug>     → /package.php
    '#^packages/([a-z0-9_-]+)/?$#i' => function ($m) {
        $_GET['slug'] = $m[1]; return 'package.php';
    },
];
foreach ($pretty as $pattern => $resolver) {
    if (preg_match($pattern, $rel, $matches)) {
        $rel = $resolver($matches);
        break;
    }
}

// Map / and /foo/ to /foo/index.php
if ($rel === '' || str_ends_with($rel, '/')) {
    $rel .= 'index.php';
}

$target = $base . '/' . $rel;
$real   = realpath($target);

// Containment + extension whitelist (only allow files inside the parent docroot,
// served as PHP, JPG, PNG, WEBP, GIF, ICO, CSS, JS, SVG, WOFF, TXT, XML, HTML).
$allowed_ext = ['php','html','htm','jpg','jpeg','png','webp','gif','ico','svg','css','js','woff','woff2','ttf','txt','xml'];
$ext = strtolower(pathinfo($real ?: '', PATHINFO_EXTENSION));

if ($real && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real) && in_array($ext, $allowed_ext, true)) {
    if ($ext === 'php' || $ext === 'html' || $ext === 'htm') {
        chdir($base);
        require $real;
        exit;
    }
    // Static asset — serve directly with the right content-type.
    $mimes = [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
        'webp'=>'image/webp','gif'=>'image/gif','ico'=>'image/x-icon',
        'svg'=>'image/svg+xml','css'=>'text/css','js'=>'application/javascript',
        'woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf',
        'txt'=>'text/plain','xml'=>'application/xml',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($real));
    header('Cache-Control: public, max-age=86400');
    readfile($real);
    exit;
}

// Default: render the parent's homepage (which is tenant-aware via host header).
chdir($base);
require $base . '/index.php';
