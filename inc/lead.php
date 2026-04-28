<?php
require_once __DIR__ . '/db.php';

/**
 * Create a lead row for a tenant. All fields except company_id are optional.
 */
/**
 * Atomically claim a voucher for a member.
 * Returns the generated code on success; throws RuntimeException on failure.
 */
function claim_voucher(int $company_id, int $voucher_id, int $member_id): string {
    require_once __DIR__ . '/helpers.php';

    $v = db_one(
        'SELECT * FROM vouchers WHERE company_id = ? AND id = ? AND status = "active" LIMIT 1',
        [$company_id, $voucher_id]
    );
    if (!$v) {
        throw new RuntimeException('Voucher not available.');
    }
    if ($v['expiry_date'] && $v['expiry_date'] < date('Y-m-d')) {
        throw new RuntimeException('Voucher has expired.');
    }
    if (!empty($v['per_member_limit'])) {
        $owned = db_one(
            'SELECT COUNT(*) AS c FROM voucher_claims WHERE voucher_id = ? AND member_id = ?',
            [$voucher_id, $member_id]
        );
        if ((int) $owned['c'] >= (int) $v['per_member_limit']) {
            throw new RuntimeException('You have already claimed this voucher.');
        }
    }
    if (!empty($v['usage_limit'])) {
        $total = db_one(
            'SELECT COUNT(*) AS c FROM voucher_claims WHERE voucher_id = ?',
            [$voucher_id]
        );
        if ((int) $total['c'] >= (int) $v['usage_limit']) {
            throw new RuntimeException('Voucher fully claimed.');
        }
    }
    do {
        $code   = 'V' . rand_code(9);
        $exists = db_one('SELECT id FROM voucher_claims WHERE voucher_code = ?', [$code]);
    } while ($exists);

    db_insert(
        'INSERT INTO voucher_claims (company_id, voucher_id, member_id, status, voucher_code)
         VALUES (?, ?, ?, "claimed", ?)',
        [$company_id, $voucher_id, $member_id, $code]
    );
    return $code;
}

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
