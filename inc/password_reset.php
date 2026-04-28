<?php
/**
 * Password reset helpers (super_admin / company_admin / member).
 *
 * Flow:
 *   pw_request_reset($role, $email)  → emails the link if email exists.
 *                                      Returns true if a row was inserted,
 *                                      false otherwise — but callers should
 *                                      always show the same neutral message
 *                                      to avoid email enumeration.
 *   pw_validate_token($token, $role) → returns reset row, or null.
 *   pw_consume_token($id, $role, $user_id, $hash) → updates password,
 *                                                   marks token used.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const PW_TTL_SECONDS = 3600;   // 1 hour

function pw_table_for(string $role): ?string {
    return match ($role) {
        'super_admin'   => 'super_admins',
        'company_admin' => 'company_admins',
        'member'        => 'members',
        default         => null,
    };
}

function pw_request_reset(string $role, string $email, string $reset_path_base): bool {
    $table = pw_table_for($role);
    if (!$table) return false;

    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    $user = db_one("SELECT id, name, email FROM {$table} WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
    if (!$user) return false;

    // Rate-limit: ignore if a fresh unused token was generated in the last 60s.
    $recent = db_one(
        'SELECT id FROM password_resets
          WHERE role = ? AND user_id = ? AND used_at IS NULL
            AND created_at >= NOW() - INTERVAL 60 SECOND
          LIMIT 1',
        [$role, (int) $user['id']]
    );
    if ($recent) return true;  // pretend success

    // Invalidate any older unused tokens for the same user
    db_exec(
        'UPDATE password_resets SET used_at = NOW()
          WHERE role = ? AND user_id = ? AND used_at IS NULL',
        [$role, (int) $user['id']]
    );

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + PW_TTL_SECONDS);

    db_insert(
        'INSERT INTO password_resets (role, user_id, email, token, expires_at, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$role, (int) $user['id'], $email, $token, $expires, client_ip()]
    );

    // Build the reset URL on the same host the request came in on.
    $scheme = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? APP_BASE_DOMAIN;
    $reset_url = $scheme . '://' . $host . $reset_path_base . '?token=' . $token;

    pw_send_reset_email($email, $user['name'], $reset_url);
    // Always log so the admin can fish the link out if mail() fails
    error_log("[pw-reset] {$role} {$email} → {$reset_url}");

    return true;
}

function pw_validate_token(string $token, string $role): ?array {
    if ($token === '' || strlen($token) > 64) return null;
    $row = db_one(
        'SELECT * FROM password_resets
          WHERE token = ? AND role = ? AND used_at IS NULL AND expires_at > NOW()
          LIMIT 1',
        [$token, $role]
    );
    return $row ?: null;
}

function pw_consume_token(int $reset_id, string $role, int $user_id, string $new_password): bool {
    $table = pw_table_for($role);
    if (!$table) return false;
    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    db_exec("UPDATE {$table} SET password_hash = ? WHERE id = ?", [$hash, $user_id]);
    db_exec('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$reset_id]);
    return true;
}

function pw_send_reset_email(string $to, string $name, string $reset_url): bool {
    $subject = '[AICAP] Reset your password';
    $body = "Hi " . ($name ?: 'there') . ",\n\n"
          . "We received a request to reset your AICAP password. Use the link below\n"
          . "to set a new password (valid for 1 hour):\n\n"
          . $reset_url . "\n\n"
          . "If you didn't request this, you can safely ignore this email — your\n"
          . "current password will keep working.\n\n"
          . "— AICAP Furniture BOS\n"
          . APP_URL_SCHEME . "://" . APP_BASE_DOMAIN . "/\n";
    $headers = "From: AICAP <noreply@" . APP_BASE_DOMAIN . ">\r\n"
             . "Reply-To: support@" . APP_BASE_DOMAIN . "\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "X-Mailer: PHP/" . phpversion();
    return @mail($to, $subject, $body, $headers);
}
