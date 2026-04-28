<?php
require_once __DIR__ . '/db.php';

/**
 * Create a lead row for a tenant. All fields except company_id are optional.
 */
function create_lead(int $company_id, array $data): int {
    return db_insert(
        'INSERT INTO leads
           (company_id, branch_id, product_id, member_id, campaign_id,
            salesperson_id, customer_name, customer_phone, source, status, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $company_id,
            $data['branch_id']      ?? null,
            $data['product_id']     ?? null,
            $data['member_id']      ?? null,
            $data['campaign_id']    ?? null,
            $data['salesperson_id'] ?? null,
            $data['customer_name']  ?? null,
            $data['customer_phone'] ?? null,
            $data['source']         ?? 'other',
            $data['status']         ?? 'new',
            $data['notes']          ?? null,
        ]
    );
}
