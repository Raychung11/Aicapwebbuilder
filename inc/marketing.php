<?php
/**
 * Marketing script renderer for the Agent Portal.
 *
 * Each voucher can carry a custom `marketing_script` template (set in
 * Tenant Admin → Vouchers). When empty we fall back to a sensible default.
 *
 * Available placeholders inside the template:
 *   {voucher_title}   {voucher_value}   {expiry_date}   {expiry_text}
 *   {voucher_url}     {company_name}    {agent_name}
 */
require_once __DIR__ . '/helpers.php';

const MARKETING_PLACEHOLDERS = [
    '{voucher_title}', '{voucher_value}', '{expiry_date}', '{expiry_text}',
    '{voucher_url}',   '{company_name}',  '{agent_name}',
];

function voucher_value_text(array $v): string {
    $val = $v['value'] ?? null;
    return match ($v['type']) {
        'percent' => $val !== null ? rtrim(rtrim((string)(float)$val, '0'), '.') . '% off' : 'Discount',
        'fixed'   => $val !== null ? 'RM ' . number_format((float) $val, 2) . ' off'    : 'Discount',
        'gift'    => 'Free gift with purchase',
        'freebie' => 'Free item',
        default   => 'Special offer',
    };
}

function default_marketing_script(): string {
    return "Hi! 🎁 Special offer from {company_name}:\n\n"
         . "🌟 {voucher_title}\n"
         . "💸 {voucher_value}\n"
         . "{expiry_text}\n\n"
         . "Claim it here 👇\n"
         . "{voucher_url}\n\n"
         . "— {agent_name}";
}

/**
 * Render the marketing script for a voucher.
 *
 * @param array       $voucher  voucher row (must include title/type/value/expiry_date/marketing_script)
 * @param array       $company  company row (must include name)
 * @param array|null  $agent    salesperson row (optional, for agent_name)
 * @param string      $voucher_url  the referral-tagged URL the agent should share
 */
function render_marketing_script(array $voucher, array $company, ?array $agent, string $voucher_url): string {
    $template = trim((string) ($voucher['marketing_script'] ?? '')) !== ''
        ? $voucher['marketing_script']
        : default_marketing_script();

    $expiry_date = $voucher['expiry_date'] ?? '';
    $expiry_text = $expiry_date
        ? '⏳ Valid until ' . date('j M Y', strtotime($expiry_date))
        : '⏳ Limited time offer';

    return strtr((string) $template, [
        '{voucher_title}' => $voucher['title'] ?? '',
        '{voucher_value}' => voucher_value_text($voucher),
        '{expiry_date}'   => $expiry_date,
        '{expiry_text}'   => $expiry_text,
        '{voucher_url}'   => $voucher_url,
        '{company_name}'  => $company['name'] ?? '',
        '{agent_name}'    => $agent['name']   ?? '',
    ]);
}

function whatsapp_share_url(string $message): string {
    return 'https://wa.me/?text=' . rawurlencode($message);
}
