<?php
require_once __DIR__ . '/db.php';

/**
 * Start the shared session if not already started.
 */
function session_boot(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => APP_URL_SCHEME === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ===== Super Admin =====
function super_admin_login(string $email, string $password): bool {
    session_boot();
    $row = db_one(
        'SELECT * FROM super_admins WHERE email = ? AND status = "active" LIMIT 1',
        [$email]
    );
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['super_admin'] = [
        'id'    => (int) $row['id'],
        'name'  => $row['name'],
        'email' => $row['email'],
    ];
    return true;
}

function super_admin(): ?array {
    session_boot();
    return $_SESSION['super_admin'] ?? null;
}

function require_super_admin(): array {
    $u = super_admin();
    if (!$u) {
        header('Location: /admin/login.php');
        exit;
    }
    return $u;
}

function super_admin_logout(): void {
    session_boot();
    unset($_SESSION['super_admin']);
}

// ===== Company Admin =====
function company_admin_login(string $email, string $password): bool {
    session_boot();
    $row = db_one(
        'SELECT * FROM company_admins WHERE email = ? AND status = "active" LIMIT 1',
        [$email]
    );
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['company_admin'] = [
        'id'         => (int) $row['id'],
        'company_id' => (int) $row['company_id'],
        'name'       => $row['name'],
        'email'      => $row['email'],
        'role'       => $row['role'],
    ];
    return true;
}

function company_admin(): ?array {
    session_boot();
    return $_SESSION['company_admin'] ?? null;
}

function require_company_admin(): array {
    $u = company_admin();
    if (!$u) {
        header('Location: /company-admin/login.php');
        exit;
    }
    return $u;
}

function company_admin_logout(): void {
    session_boot();
    unset($_SESSION['company_admin']);
}

// ===== Member =====
function member_login(string $phone, string $password): bool {
    session_boot();
    $row = db_one(
        'SELECT * FROM members WHERE phone = ? AND status = "active" LIMIT 1',
        [$phone]
    );
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['member'] = [
        'id'    => (int) $row['id'],
        'name'  => $row['name'],
        'phone' => $row['phone'],
        'email' => $row['email'],
    ];
    return true;
}

function current_member(): ?array {
    session_boot();
    return $_SESSION['member'] ?? null;
}

function require_member(string $redirect = '/member-login.php'): array {
    $m = current_member();
    if (!$m) {
        header('Location: ' . $redirect);
        exit;
    }
    return $m;
}

function member_logout(): void {
    session_boot();
    unset($_SESSION['member']);
}
