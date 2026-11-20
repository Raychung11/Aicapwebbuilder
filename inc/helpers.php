<?php

function e(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function input(string $key, $default = null) {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $msg): void {
    session_boot();
    $_SESSION['_flash'][$key] = $msg;
}

function flash_pop(string $key): ?string {
    session_boot();
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text === '' ? 'item' : $text;
}

function rand_code(int $len = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

function client_ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}

function format_price($min, $max): string {
    if ($min === null && $max === null) return '';
    if ($min !== null && ($max === null || (float)$max == (float)$min)) {
        return 'RM ' . number_format((float)$min, 2);
    }
    return 'RM ' . number_format((float)$min, 2) . ' - RM ' . number_format((float)$max, 2);
}

function whatsapp_link(string $number, string $message = ''): string {
    $num = preg_replace('/\D+/', '', $number);
    $url = 'https://wa.me/' . $num;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/**
 * Normalize a user-typed phone number to a canonical
 * <country_code><national_number> form (no '+', no spaces).
 *
 * Rules (with cc='60'):
 *   '0123456789'        → '60123456789'
 *   '123456789'         → '60123456789'
 *   '60123456789'       → '60123456789'
 *   '+60 12-345 6789'   → '60123456789'
 *   '601 23456789'      → '60123456789'
 *
 * Empty input returns ''.
 */
function normalize_phone(string $input, ?string $cc = null): string {
    $cc    = $cc ?: DEFAULT_COUNTRY_CODE;
    $clean = preg_replace('/\D+/', '', $input);
    if ($clean === null || $clean === '') return '';
    $clean = ltrim($clean, '0');
    if (strncmp($clean, $cc, strlen($cc)) === 0) {
        $clean = substr($clean, strlen($cc));
    }
    return $cc . $clean;
}

/**
 * Tenant-scoped fetch helpers. Always pass company_id explicitly.
 */
function tenant_one(string $sql, int $company_id, array $params = []): ?array {
    return db_one($sql, array_merge([$company_id], $params));
}
function tenant_all(string $sql, int $company_id, array $params = []): array {
    return db_all($sql, array_merge([$company_id], $params));
}

/**
 * Read a platform-level setting (AI keys, etc.) with env-var override.
 * Returns null if not set.
 */
function platform_setting(string $key, ?string $default = null): ?string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db_all('SELECT setting_key, setting_value FROM platform_settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
            // Table may not exist yet on a half-migrated install.
        }
    }
    // Env-var override always wins
    $env = getenv(strtoupper($key));
    if ($env !== false && $env !== '') return $env;
    return $cache[$key] ?? $default;
}

function set_platform_setting(string $key, ?string $value): void {
    db_exec(
        'INSERT INTO platform_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

/**
 * Cheap "does this table exist?" check, cached per request.
 * Useful when an admin page references a freshly-added table that
 * may not exist yet on a not-yet-migrated install.
 */
function db_table_exists(string $name): bool {
    static $cache = [];
    if (isset($cache[$name])) return $cache[$name];
    try {
        $row = db_one(
            'SELECT 1 AS x FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            [$name]
        );
        return $cache[$name] = (bool) $row;
    } catch (Throwable $e) {
        return $cache[$name] = false;
    }
}
