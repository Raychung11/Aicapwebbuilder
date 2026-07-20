<?php
require_once __DIR__ . '/auth.php';

// Boot the session at include time — never inside csrf_field(). Pages
// often call csrf_field() deep inside the HTML body, by which point
// the output buffer has flushed and session_start() can no longer set
// cookies. Including this file early (which every CSRF-protected page
// already does) guarantees the session exists before any output.
session_boot();

function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_KEY . '" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $sent = $_POST[CSRF_TOKEN_KEY] ?? '';
    if (!$sent || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        die('CSRF token mismatch.');
    }
}
