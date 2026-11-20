<?php
require_once __DIR__ . '/db.php';

/**
 * Record an analytics event for a company.
 *
 * @param int    $company_id  Required tenant id.
 * @param string $event_type  e.g. page_view, product_view, whatsapp_click,
 *                            voucher_claim, campaign_scan
 * @param array  $opts        entity_type, entity_id, campaign_id, member_id
 */
function track_event(int $company_id, string $event_type, array $opts = []): void {
    db_exec(
        'INSERT INTO analytics_events
           (company_id, event_type, entity_type, entity_id, campaign_id,
            member_id, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $company_id,
            $event_type,
            $opts['entity_type']  ?? null,
            $opts['entity_id']    ?? null,
            $opts['campaign_id']  ?? null,
            $opts['member_id']    ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]
    );
}

function event_count(int $company_id, string $event_type, ?string $since = null): int {
    if ($since) {
        $row = db_one(
            'SELECT COUNT(*) AS c FROM analytics_events
              WHERE company_id = ? AND event_type = ? AND created_at >= ?',
            [$company_id, $event_type, $since]
        );
    } else {
        $row = db_one(
            'SELECT COUNT(*) AS c FROM analytics_events
              WHERE company_id = ? AND event_type = ?',
            [$company_id, $event_type]
        );
    }
    return (int) ($row['c'] ?? 0);
}
