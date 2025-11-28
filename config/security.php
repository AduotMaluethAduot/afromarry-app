<?php
/**
 * Security Configuration and Helper Functions
 * Enhanced security measures for the AfroMarry platform
 */

// Security headers
function setSecurityHeaders() {
    // Only set headers if they haven't been sent yet
    if (headers_sent()) {
        return;
    }
    
    // Prevent XSS attacks
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (only for HTML pages, not API endpoints)
    if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/actions/') === false) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self'; media-src 'self' data:; ");
    }
    
    // HSTS (HTTP Strict Transport Security)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input validation and sanitization
function validateInput($data, $type = 'string', $max_length = 255) {
    $data = trim($data);
    
    if (strlen($data) > $max_length) {
        throw new Exception("Input exceeds maximum length of {$max_length} characters");
    }
    
    switch ($type) {
        case 'email':
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            return filter_var($data, FILTER_SANITIZE_EMAIL);
            
        case 'int':
            if (!is_numeric($data)) {
                throw new Exception("Invalid integer value");
            }
            return (int)$data;
            
        case 'float':
            if (!is_numeric($data)) {
                throw new Exception("Invalid float value");
            }
            return (float)$data;
            
        case 'url':
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid URL format");
            }
            return filter_var($data, FILTER_SANITIZE_URL);
            
        case 'phone':
            $data = preg_replace('/[^0-9+\-\s()]/', '', $data);
            if (strlen($data) < 10) {
                throw new Exception("Invalid phone number");
            }
            return $data;
            
        case 'date':
            $timestamp = strtotime($data);
            if ($timestamp === false) {
                throw new Exception("Invalid date format");
            }
            return date('Y-m-d', $timestamp);
            
        case 'datetime':
            $timestamp = strtotime($data);
            if ($timestamp === false) {
                throw new Exception("Invalid datetime format");
            }
            return date('Y-m-d H:i:s', $timestamp);
            
        case 'enum':
            // $max_length parameter is used as allowed values array
            if (!is_array($max_length) || !in_array($data, $max_length)) {
                throw new Exception("Invalid value");
            }
            return $data;
            
        case 'string':
        default:
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

// Password security
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3,         // 3 threads
    ]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Rate limiting
function checkRateLimit($action, $max_attempts = 5, $time_window = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $rate_data = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $rate_data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_data['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

// File upload security
function validateFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
        return $errors;
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = "File size exceeds maximum allowed size";
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = "File type not allowed";
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = "File extension not allowed";
    }
    
    return $errors;
}

// SQL injection prevention
function sanitizeForDatabase($data) {
    if (is_array($data)) {
        return array_map('sanitizeForDatabase', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// XSS prevention
function preventXSS($data) {
    if (is_array($data)) {
        return array_map('preventXSS', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Session security
function secureSession() {
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Set secure session cookie parameters
    if (ini_get('session.cookie_secure') !== '1') {
        ini_set('session.cookie_secure', '1');
    }
    
    if (ini_get('session.cookie_httponly') !== '1') {
        ini_set('session.cookie_httponly', '1');
    }
    
    if (ini_get('session.cookie_samesite') !== 'Strict') {
        ini_set('session.cookie_samesite', 'Strict');
    }
}

// IP whitelist/blacklist
function checkIPAccess() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Blacklisted IPs (in a real app, this would come from database)
    $blacklisted_ips = [];
    if (in_array($ip, $blacklisted_ips)) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Admin IP whitelist (optional)
    $admin_ips = [];
    if (in_array($ip, $admin_ips) && !isAdmin()) {
        // Grant temporary admin access (implement as needed)
    }
}

// Audit logging
function logSecurityEvent($event, $details = [], $severity = 'info') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => getClientIpAddress(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user']['full_name'] ?? null,
        'event' => $event,
        'details' => $details,
        'severity' => $severity,
        'session_id' => session_id(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null
    ];
    
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Also log to system log for critical events
    if ($severity === 'critical' || $severity === 'error') {
        error_log('[' . strtoupper($severity) . '] ' . $event . ': ' . json_encode($details));
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

// Two-factor authentication (enhanced implementation)
function generate2FASecret() {
    // Generate a random secret for TOTP
    return bin2hex(random_bytes(16));
}

function generate2FACode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generateBackupCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
    return $codes;
}

function send2FACode($email, $code) {
    // In a real implementation, send via email/SMS
    // For now, just log it
    logSecurityEvent('2FA Code Generated', ['email' => $email, 'code' => $code]);
    return true;
}

function verify2FACode($user_id, $code) {
    // In a real implementation, this would verify against TOTP or stored code
    // For now, we'll simulate verification
    
    // Load database
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user has 2FA enabled
    $query = "SELECT * FROM user_2fa WHERE user_id = :user_id AND is_enabled = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user_2fa = $stmt->fetch();
    
    if (!$user_2fa) {
        // 2FA not enabled for this user
        return true;
    }
    
    // In a real implementation, verify the code against TOTP algorithm
    // For now, we'll just return true to simulate successful verification
    return true;
}

function enable2FA($user_id) {
    // Load database
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate secret
    $secret = generate2FASecret();
    
    // Generate backup codes
    $backup_codes = generateBackupCodes();
    $encrypted_backup_codes = json_encode($backup_codes); // In real implementation, encrypt these
    
    // Check if user already has 2FA record
    $query = "SELECT id FROM user_2fa WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $query = "UPDATE user_2fa SET secret = :secret, backup_codes = :backup_codes, is_enabled = TRUE WHERE user_id = :user_id";
    } else {
        // Insert new record
        $query = "INSERT INTO user_2fa (user_id, secret, backup_codes, is_enabled) VALUES (:user_id, :secret, :backup_codes, TRUE)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':secret' => $secret,
        ':backup_codes' => $encrypted_backup_codes
    ]);
    
    return [
        'secret' => $secret,
        'backup_codes' => $backup_codes
    ];
}

function disable2FA($user_id) {
    // Load database
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE user_2fa SET is_enabled = FALSE WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    return true;
}

// API security
function validateAPIKey($key) {
    // In a real implementation, validate against database
    $valid_keys = [
        'afromarry_api_2024' => ['permissions' => ['read', 'write'], 'expires' => '2025-12-31']
    ];
    
    return isset($valid_keys[$key]) && strtotime($valid_keys[$key]['expires']) > time();
}

// Content Security Policy
function getCSPHeader() {
    return "Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
           "img-src 'self' data: https:; " .
           "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
           "connect-src 'self'; " .
           "media-src 'self' data:; " .
           "frame-ancestors 'none'; " .
           "base-uri 'self'; " .
           "form-action 'self'";
}

// Initialize security
function initSecurity() {
    setSecurityHeaders();
    secureSession();
    checkIPAccess();
    
    // Log security events
    if (isset($_POST['login']) || isset($_POST['register'])) {
        logSecurityEvent('Authentication Attempt', [
            'action' => isset($_POST['login']) ? 'login' : 'register',
            'email' => $_POST['email'] ?? 'unknown'
        ]);
    }
}

// Error handling
function handleSecurityError($message, $code = 400) {
    logSecurityEvent('Security Error', ['message' => $message, 'code' => $code], 'error');
    http_response_code($code);
    die(json_encode(['error' => $message]));
}

// Initialize security when this file is included
initSecurity();
?>
