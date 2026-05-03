<?php
/**
 * Agent referral tracking.
 *
 *   ?ref=<CODE> on any tenant page → look up the salesperson, store their
 *   id in a per-tenant cookie for 30 days. Subsequent voucher claims, lead
 *   captures and WhatsApp clicks are then attributed to that agent.
 */
require_once __DIR__ . '/db.php';

const REFERRAL_COOKIE_PREFIX = 'aicap_ref_';
const REFERRAL_COOKIE_DAYS   = 30;

function referral_cookie_name(int $company_id): string {
    return REFERRAL_COOKIE_PREFIX . $company_id;
}

/**
 * Inspect $_GET['ref']; if it matches an active salesperson for the tenant,
 * set a 30-day cookie and stash the id on $_COOKIE for the current request.
 */
function capture_referral_from_query(int $company_id): void {
    $code = trim((string) ($_GET['ref'] ?? ''));
    if ($code === '' || strlen($code) > 40) return;

    $row = db_one(
        'SELECT id FROM salespersons
          WHERE company_id = ? AND referral_code = ? AND status = "active"
          LIMIT 1',
        [$company_id, $code]
    );
    if (!$row) return;

    $name = referral_cookie_name($company_id);
    $val  = (string) (int) $row['id'];
    setcookie($name, $val, [
        'expires'  => time() + 86400 * REFERRAL_COOKIE_DAYS,
        'path'     => '/',
        'secure'   => ($_SERVER['HTTPS'] ?? 'off') !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $val;
}

function current_referral_sp_id(int $company_id): ?int {
    $name = referral_cookie_name($company_id);
    if (empty($_COOKIE[$name])) return null;
    $sp_id = (int) $_COOKIE[$name];
    if ($sp_id <= 0) return null;
    // Re-validate that the salesperson is still active for this tenant
    $row = db_one(
        'SELECT id FROM salespersons WHERE id = ? AND company_id = ? AND status = "active" LIMIT 1',
        [$sp_id, $company_id]
    );
    return $row ? (int) $row['id'] : null;
}

function clear_referral(int $company_id): void {
    $name = referral_cookie_name($company_id);
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path'    => '/',
    ]);
    unset($_COOKIE[$name]);
}

/**
 * Generate a short, unique-per-tenant referral code from a person's name.
 */
function generate_referral_code(int $company_id, string $name): string {
    $slug = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($name))) ?: 'AGT';
    $slug = substr($slug, 0, 6);
    for ($i = 0; $i < 12; $i++) {
        $suffix = str_pad((string) random_int(100, 9999), 3, '0', STR_PAD_LEFT);
        $code = $slug . $suffix;
        $clash = db_one(
            'SELECT id FROM salespersons WHERE company_id = ? AND referral_code = ?',
            [$company_id, $code]
        );
        if (!$clash) return $code;
    }
    // Last resort: completely random
    return 'AGT' . strtoupper(bin2hex(random_bytes(3)));
}
