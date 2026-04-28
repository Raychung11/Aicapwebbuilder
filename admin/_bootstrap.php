<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$super = require_super_admin();

$ADMIN_MENU = [
    ['file' => 'index.php',     'label' => 'Dashboard'],
    ['file' => 'companies.php', 'label' => 'Companies'],
    ['file' => 'templates.php', 'label' => 'Page Templates'],
    ['file' => 'analytics.php', 'label' => 'Analytics'],
];

function admin_layout_open(string $title): void {
    global $ADMIN_MENU, $super;
    admin_head($title, '/admin', $ADMIN_MENU, $super['name'], '/admin/logout.php');
}
function admin_layout_close(): void {
    admin_foot();
}
