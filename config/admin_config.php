<?php
/**
 * Admin Configuration
 * 
 * SECURITY: All sensitive values are loaded from environment variables
 * See .env.example for configuration template
 */

// Load environment variables first
if (!function_exists('env')) {
    require_once __DIR__ . '/env_loader.php';
}

// Admin signup secret password (from environment)
define('ADMIN_SIGNUP_SECRET', env('ADMIN_SIGNUP_SECRET', 'AfroMarry_Admin_2025_Secure_Change_This'));

// Admin login path (hidden from public)
define('ADMIN_LOGIN_PATH', '/AfroMarry/admin/login.php');

// Rate limiting for admin login
define('ADMIN_LOGIN_RATE_LIMIT', (int)env('ADMIN_LOGIN_RATE_LIMIT', 5)); // Attempts per hour
define('ADMIN_LOGIN_RATE_WINDOW', (int)env('ADMIN_LOGIN_RATE_WINDOW', 3600)); // Time window in seconds

// Admin session timeout (in seconds)
define('ADMIN_SESSION_TIMEOUT', (int)env('ADMIN_SESSION_TIMEOUT', 1800)); // Default: 30 minutes

?>

