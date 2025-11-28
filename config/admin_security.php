<?php
/**
 * Admin Security Functions
 * 
 * Handles rate limiting, session timeout, and login logging for admin accounts
 */

require_once __DIR__ . '/admin_config.php';
require_once __DIR__ . '/database.php';

/**
 * Check rate limiting for admin login
 * Returns true if allowed, false if rate limited
 */
function checkAdminLoginRateLimit($email, $ip_address) {
    $database = new Database();
    $db = $database->getConnection();
    
    $window_start = date('Y-m-d H:i:s', time() - ADMIN_LOGIN_RATE_WINDOW);
    
    // Count failed attempts in the time window
    $query = "SELECT COUNT(*) as attempts 
              FROM admin_login_logs 
              WHERE (email = :email OR ip_address = :ip_address) 
              AND success = 0 
              AND created_at >= :window_start";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':email' => $email,
        ':ip_address' => $ip_address,
        ':window_start' => $window_start
    ]);
    
    $result = $stmt->fetch();
    $attempts = $result['attempts'] ?? 0;
    
    return $attempts < ADMIN_LOGIN_RATE_LIMIT;
}

/**
 * Log admin login attempt
 */
function logAdminLoginAttempt($email, $ip_address, $user_agent, $success, $user_id = null, $failure_reason = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO admin_login_logs (email, ip_address, user_agent, success, user_id, failure_reason) 
              VALUES (:email, :ip_address, :user_agent, :success, :user_id, :failure_reason)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':email' => $email,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent,
        ':success' => $success ? 1 : 0,
        ':user_id' => $user_id,
        ':failure_reason' => $failure_reason
    ]);
}

/**
 * Check if admin session has timed out
 * Returns true if session is valid, false if timed out
 */
function checkAdminSessionTimeout() {
    if (!isLoggedIn() || !isAdmin()) {
        return false;
    }
    
    // Check if session has last activity time
    if (!isset($_SESSION['admin_last_activity'])) {
        $_SESSION['admin_last_activity'] = time();
        return true;
    }
    
    // Check if session has timed out
    $timeout = ADMIN_SESSION_TIMEOUT;
    $last_activity = $_SESSION['admin_last_activity'];
    
    if ((time() - $last_activity) > $timeout) {
        // Session timed out - destroy session
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
    return true;
}

/**
 * Get remaining session time in seconds
 */
function getAdminSessionTimeRemaining() {
    if (!isLoggedIn() || !isAdmin() || !isset($_SESSION['admin_last_activity'])) {
        return 0;
    }
    
    $timeout = ADMIN_SESSION_TIMEOUT;
    $last_activity = $_SESSION['admin_last_activity'];
    $elapsed = time() - $last_activity;
    $remaining = $timeout - $elapsed;
    
    return max(0, $remaining);
}

/**
 * Get client IP address
 */
function getClientIpAddress() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

?>

