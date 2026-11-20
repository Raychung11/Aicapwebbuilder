<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';      // for session_boot()
require_once __DIR__ . '/referral.php';  // for capture_referral_from_query()

/**
 * Tenant resolution.
 *
 * Order of precedence:
 *   1. ?as=<slug>         → set session preview + redirect to clean URL
 *   2. ?exit_preview      → clear session preview
 *   3. session preview    → render that company (used while testing on a
 *                           preview / Hostinger temp domain)
 *   4. Host header        → subdomain or custom_domain match
 *   5. If exactly one active company exists AND host doesn't match the
 *      configured platform domain, fall back to that company. This makes
 *      a Hostinger preview URL "just work" for a single tenant before DNS
 *      is wired up.
 *
 * Result is cached per request, and once a tenant is resolved the
 * referral cookie is captured/refreshed from any ?ref= query.
 */
function current_company(): ?array {
    static $cache = false;
    if ($cache !== false) {
        if ($cache) capture_referral_from_query((int) $cache['id']);
        return $cache;
    }
    $cache = _resolve_company();
    if ($cache) capture_referral_from_query((int) $cache['id']);
    return $cache;
}

function _resolve_company(): ?array {
    // 1. Explicit preview switch via query string
    if (isset($_GET['as']) && $_GET['as'] !== '') {
        $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) $_GET['as']);
        if ($slug) {
            $row = db_one(
                'SELECT * FROM companies WHERE slug = ? AND status = "active" LIMIT 1',
                [$slug]
            );
            if ($row) {
                session_boot();
                $_SESSION['preview_company_id'] = (int) $row['id'];
                $clean = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
                $qs = $_GET;
                unset($qs['as']);
                if ($qs) $clean .= '?' . http_build_query($qs);
                header('Location: ' . $clean);
                exit;
            }
        }
    }

    // 2. Clear preview
    if (isset($_GET['exit_preview'])) {
        session_boot();
        unset($_SESSION['preview_company_id']);
        $clean = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        header('Location: ' . $clean);
        exit;
    }

    // 3. Sticky session preview
    session_boot();
    if (!empty($_SESSION['preview_company_id'])) {
        $row = db_one(
            'SELECT * FROM companies WHERE id = ? AND status = "active" LIMIT 1',
            [(int) $_SESSION['preview_company_id']]
        );
        if ($row) return $row;
    }

    // 4. Host-based resolution
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host !== '' && $host !== APP_BASE_DOMAIN && $host !== 'www.' . APP_BASE_DOMAIN) {
        $base = '.' . APP_BASE_DOMAIN;
        if (substr($host, -strlen($base)) === $base) {
            $sub = substr($host, 0, -strlen($base));
            if ($sub !== '' && $sub !== 'www') {
                $row = db_one(
                    'SELECT * FROM companies WHERE subdomain = ? AND status = "active" LIMIT 1',
                    [$sub]
                );
                if ($row) return $row;
            }
        } else {
            // Prefer the multi-domain table (company_domains) when present;
            // fall back to the legacy single `custom_domain` column so older
            // tenants keep working.
            $row = null;
            try {
                $row = db_one(
                    'SELECT c.* FROM company_domains d
                       JOIN companies c ON c.id = d.company_id
                      WHERE d.domain = ? AND c.status = "active" LIMIT 1',
                    [$host]
                );
            } catch (Throwable $e) {
                // company_domains not yet migrated — ignore and fall through.
            }
            if (!$row) {
                $row = db_one(
                    'SELECT * FROM companies WHERE custom_domain = ? AND status = "active" LIMIT 1',
                    [$host]
                );
            }
            if ($row) return $row;
        }
    }

    // 5. Single-tenant fallback when not on the platform domain
    $on_platform = ($host === APP_BASE_DOMAIN || $host === 'www.' . APP_BASE_DOMAIN);
    if (!$on_platform) {
        $count = (int) (db_one('SELECT COUNT(*) c FROM companies WHERE status = "active"')['c'] ?? 0);
        if ($count === 1) {
            return db_one('SELECT * FROM companies WHERE status = "active" LIMIT 1');
        }
    }

    return null;
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

function company_url(array $company, string $path = '/'): string {
    $primary = company_primary_domain($company);
    if ($primary) {
        return APP_URL_SCHEME . '://' . $primary . $path;
    }
    return APP_URL_SCHEME . '://' . $company['subdomain'] . '.' . APP_BASE_DOMAIN . $path;
}

/**
 * Return the tenant's canonical hostname for public URLs:
 *  1. company_domains row flagged is_primary = 1 (new multi-domain table)
 *  2. legacy companies.custom_domain column
 *  3. null (caller falls back to <subdomain>.aicap.my)
 */
function company_primary_domain(array $company): ?string {
    try {
        $row = db_one(
            'SELECT domain FROM company_domains
              WHERE company_id = ?
              ORDER BY is_primary DESC, id ASC
              LIMIT 1',
            [(int) $company['id']]
        );
        if ($row && !empty($row['domain'])) return $row['domain'];
    } catch (Throwable $e) {
        // table missing — ignore and fall through
    }
    if (!empty($company['custom_domain'])) {
        return $company['custom_domain'];
    }
    return null;
}

function is_preview_mode(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) return false;
    return !empty($_SESSION['preview_company_id']);
}
