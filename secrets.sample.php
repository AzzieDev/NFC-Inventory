<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Secrets Configuration Template (Copy to secrets.php)
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'nfc_inventory');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Emergency Override: Set to true to bypass admin authentication
if (!defined('EMERGENCY_OVERRIDE')) {
    define('EMERGENCY_OVERRIDE', false);
}

// Enable rich diagnostic exception reporting and disk logging (Set to false in high-security production environments)
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

// Set application timezone to Eastern Time (America/New_York)
date_default_timezone_set('America/New_York');

// Keep users logged in for 1 year
ini_set('session.gc_maxlifetime', '31536000');
session_set_cookie_params(31536000);
