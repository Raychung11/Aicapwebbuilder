<?php
/**
 * QR campaign redirector.
 *   /q.php?c=<company_slug>&k=<qr_slug>
 *   /q/<company_slug>/<qr_slug>     (rewritten via .htaccess)
 *
 * Logs the scan, then 302-redirects to campaign.target_url.
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/lead.php';

$company_slug = trim((string) ($_GET['c'] ?? ''));
$qr_slug      = trim((string) ($_GET['k'] ?? ''));
if ($company_slug === '' || $qr_slug === '') {
    http_response_code(404);
    die('Campaign not found.');
}

$company = db_one(
    'SELECT * FROM companies WHERE slug = ? AND status = "active" LIMIT 1',
    [$company_slug]
);
if (!$company) {
    http_response_code(404);
    die('Company not found.');
}

$campaign = db_one(
    'SELECT * FROM campaigns WHERE company_id = ? AND qr_slug = ? AND status = "active" LIMIT 1',
    [(int)$company['id'], $qr_slug]
);
if (!$campaign) {
    http_response_code(404);
    die('Campaign not found.');
}

$member = current_member();

db_insert(
    'INSERT INTO campaign_scans (company_id, campaign_id, member_id, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?)',
    [
        (int)$company['id'],
        (int)$campaign['id'],
        $member['id'] ?? null,
        client_ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]
);

track_event((int)$company['id'], 'campaign_scan', [
    'entity_type' => 'campaign',
    'entity_id'   => (int)$campaign['id'],
    'campaign_id' => (int)$campaign['id'],
    'member_id'   => $member['id'] ?? null,
]);

create_lead((int)$company['id'], [
    'campaign_id' => (int)$campaign['id'],
    'member_id'   => $member['id'] ?? null,
    'source'      => 'campaign_scan',
    'notes'       => 'Scanned QR: ' . $campaign['name'],
]);

redirect($campaign['target_url']);
