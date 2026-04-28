<?php
require_once __DIR__ . '/db.php';

/**
 * Tenant resolution from HTTP host.
 *
 * Rules:
 *   aicap.my            → null   (root / HQ)
 *   www.aicap.my        → null
 *   ladore.aicap.my     → company by subdomain "ladore"
 *   ladore.example.com  → company by custom_domain
 *
 * The result is cached per-request.
 */
function current_company(): ?array {
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\d+$/', '', $host);  // strip port

    if ($host === '' || $host === APP_BASE_DOMAIN || $host === 'www.' . APP_BASE_DOMAIN) {
        $cache = null;
        return null;
    }

    // Subdomain on the platform domain
    $base = '.' . APP_BASE_DOMAIN;
    if (substr($host, -strlen($base)) === $base) {
        $sub = substr($host, 0, -strlen($base));
        if ($sub === '' || $sub === 'www') {
            $cache = null;
            return null;
        }
        $cache = db_one(
            'SELECT * FROM companies WHERE subdomain = ? AND status = "active" LIMIT 1',
            [$sub]
        );
        return $cache;
    }

    // Custom domain
    $cache = db_one(
        'SELECT * FROM companies WHERE custom_domain = ? AND status = "active" LIMIT 1',
        [$host]
    );
    return $cache;
}

/**
 * Returns the active company id, or null if visiting the HQ root.
 */
function current_company_id(): ?int {
    $c = current_company();
    return $c ? (int) $c['id'] : null;
}

/**
 * Require a company context (for tenant pages).
 * Sends 404 if no tenant resolved.
 */
function require_company(): array {
    $c = current_company();
    if (!$c) {
        http_response_code(404);
        echo '<h1>Site not found</h1>';
        exit;
    }
    return $c;
}

/**
 * Build the public URL of a given company subdomain.
 */
function company_url(array $company, string $path = '/'): string {
    if (!empty($company['custom_domain'])) {
        return APP_URL_SCHEME . '://' . $company['custom_domain'] . $path;
    }
    return APP_URL_SCHEME . '://' . $company['subdomain'] . '.' . APP_BASE_DOMAIN . $path;
}
