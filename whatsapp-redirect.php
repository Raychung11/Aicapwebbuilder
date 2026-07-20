<?php
require_once __DIR__ . '/inc/tenant.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/analytics.php';
require_once __DIR__ . '/inc/lead.php';

$company = require_company();
$cid     = (int) $company['id'];

$product_id  = (int) ($_GET['product_id']  ?? 0) ?: null;
$campaign_id = (int) ($_GET['campaign_id'] ?? 0) ?: null;
$branch_id   = (int) ($_GET['branch_id']   ?? 0) ?: null;
$member      = current_member();

$message = 'Hi, I\'d like to know more.';
if ($product_id) {
    $p = tenant_one(
        'SELECT name FROM products WHERE company_id = ? AND id = ? LIMIT 1',
        $cid, [$product_id]
    );
    if ($p) {
        $message = 'Hi, I\'m interested in: ' . $p['name'] . ' (#' . $product_id . ').';
    }
}

track_event($cid, 'whatsapp_click', [
    'entity_type' => $product_id ? 'product' : 'company',
    'entity_id'   => $product_id,
    'campaign_id' => $campaign_id,
    'member_id'   => $member['id'] ?? null,
]);

$sp_id = current_referral_sp_id($cid);
create_lead($cid, [
    'branch_id'      => $branch_id,
    'product_id'     => $product_id,
    'member_id'      => $member['id'] ?? null,
    'campaign_id'    => $campaign_id,
    'salesperson_id' => $sp_id,
    'customer_name'  => $member['name']  ?? null,
    'customer_phone' => $member['phone'] ?? null,
    'source'         => 'whatsapp_click',
    'notes'          => 'Auto-generated from WhatsApp click'
                      . ($sp_id ? ' (ref: SP#' . $sp_id . ')' : ''),
]);

$number = $company['whatsapp_number'] ?: '';
if (!$number) {
    redirect('/');
}
redirect(whatsapp_link($number, $message));
