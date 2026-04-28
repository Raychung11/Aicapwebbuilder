<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$ca = require_company_admin();

$company = db_one('SELECT * FROM companies WHERE id = ? LIMIT 1', [(int) $ca['company_id']]);
if (!$company) {
    company_admin_logout();
    redirect('/company-admin/login.php');
}
$CID = (int) $company['id'];

$CA_MENU = [
    ['file' => 'index.php',        'label' => 'Dashboard',       'icon' => '🏠'],
    ['file' => 'settings.php',     'label' => 'Branding & SEO',  'icon' => '🎨'],
    ['file' => 'branches.php',     'label' => 'Branches',        'icon' => '📍'],
    ['file' => 'salespersons.php', 'label' => 'Salespersons',    'icon' => '👥'],
    ['file' => 'products.php',     'label' => 'Products',        'icon' => '🛋️'],
    ['file' => 'featured.php',     'label' => 'Featured',        'icon' => '⭐'],
    ['file' => 'vouchers.php',     'label' => 'Vouchers',        'icon' => '🎁'],
    ['file' => 'campaigns.php',    'label' => 'Campaigns',       'icon' => '📷'],
    ['file' => 'leads.php',        'label' => 'Leads',           'icon' => '🎯'],
    ['file' => 'pages.php',        'label' => 'Pages',           'icon' => '📄'],
    ['file' => 'media.php',        'label' => 'Media',           'icon' => '🖼️'],
];

function ca_open(string $title): void {
    global $CA_MENU, $ca, $company;
    admin_head(
        $title,
        '/company-admin',
        $CA_MENU,
        $ca['name'],
        '/company-admin/logout.php',
        $company['name'],                                        // brand
        'Tenant Admin',                                          // brand_sub
        $company['theme_color'] ?: '#2563eb'                     // accent
    );
}
function ca_close(): void { admin_foot(); }

/**
 * Tenant guard: ensure a row belongs to the current company. Returns the row.
 */
function tenant_row_or_404(string $table, int $id): array {
    global $CID;
    $row = db_one("SELECT * FROM {$table} WHERE company_id = ? AND id = ? LIMIT 1", [$CID, $id]);
    if (!$row) {
        http_response_code(404);
        die('Not found.');
    }
    return $row;
}
