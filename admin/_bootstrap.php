<?php
// Diagnostic switch: append ?diag=1 to any admin URL to see PHP errors.
if (isset($_GET['diag']) && $_GET['diag'] === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$super = require_super_admin();

$ADMIN_MENU = [
    ['file' => 'index.php',       'label' => 'Dashboard',      'icon' => '🏠'],
    ['file' => 'companies.php',   'label' => 'Companies',      'icon' => '🏢'],
    ['file' => 'templates.php',   'label' => 'Page Templates', 'icon' => '📐'],
    ['file' => 'analytics.php',   'label' => 'Analytics',      'icon' => '📊'],
    ['file' => 'ai-settings.php', 'label' => 'AI Settings',    'icon' => '🤖'],
    ['file' => 'blog.php',        'label' => 'Blog',           'icon' => '📝'],
];

function admin_layout_open(string $title): void {
    global $ADMIN_MENU, $super;
    admin_head(
        $title,
        '/admin',
        $ADMIN_MENU,
        $super['name'],
        '/admin/logout.php',
        'AICAP HQ',         // brand
        'Super Admin',      // brand_sub
        '#f59e0b'           // accent
    );
}
function admin_layout_close(): void {
    admin_foot();
}
