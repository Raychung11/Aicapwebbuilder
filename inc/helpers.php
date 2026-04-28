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
 * Tenant-scoped fetch helpers. Always pass company_id explicitly.
 */
function tenant_one(string $sql, int $company_id, array $params = []): ?array {
    return db_one($sql, array_merge([$company_id], $params));
}
function tenant_all(string $sql, int $company_id, array $params = []): array {
    return db_all($sql, array_merge([$company_id], $params));
}
