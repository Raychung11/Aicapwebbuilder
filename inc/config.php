<?php
/**
 * Furniture BOS - Configuration
 *
 * Edit DB_* and APP_* values for your environment.
 * On Hostinger, copy this file as-is and update DB credentials only.
 */

// ---------- Database ----------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'furniture_bos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---------- App ----------
define('APP_NAME', 'AICAP Furniture BOS');
define('APP_BASE_DOMAIN', 'aicap.my');         // root domain
define('APP_URL_SCHEME', 'https');             // 'http' for local
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
define('DEFAULT_COUNTRY_CODE', '60');          // E.164 country code without '+'
define('DEFAULT_COUNTRY_LABEL', '+60');        // human label shown on phone inputs

// ---------- Storage ----------
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);   // 5 MB
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
]);

// ---------- Sessions ----------
define('SESSION_NAME', 'AICAP_BOS');

// ---------- Security ----------
define('CSRF_TOKEN_KEY', 'csrf_token');
define('PASSWORD_COST', 10);

date_default_timezone_set(APP_TIMEZONE);

// Display errors only in dev. Toggle DB_HOST=localhost = dev assumption.
if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
