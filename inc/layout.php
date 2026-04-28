<?php
/**
 * Backward-compat wrappers around inc/header.php and inc/footer.php.
 *
 * New code can simply do:
 *   $company    = require_company();
 *   $page_title = 'Catalog';
 *   $page_id    = 'catalog';
 *   require __DIR__ . '/inc/header.php';
 *   ...
 *   require __DIR__ . '/inc/footer.php';
 */
require_once __DIR__ . '/helpers.php';

function layout_head(array $company, string $page_title = '', string $page_id = ''): void {
    require __DIR__ . '/header.php';
}

function layout_foot(array $company): void {
    require __DIR__ . '/footer.php';
}
