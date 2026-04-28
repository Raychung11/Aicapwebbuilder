<?php
/**
 * Backwards-compat wrappers around inc/admin_header.php and
 * inc/admin_footer.php. Both /admin/_bootstrap.php and
 * /company-admin/_bootstrap.php call these.
 */
require_once __DIR__ . '/helpers.php';

function admin_head(string $title, string $base, array $menu,
                   string $user_label, string $logout_url,
                   ?string $brand = null, ?string $brand_sub = null,
                   ?string $accent = null): void {
    require __DIR__ . '/admin_header.php';
}

function admin_foot(): void {
    require __DIR__ . '/admin_footer.php';
}
