<?php
// Database configuration
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/security.php';

// Load environment variables first
if (!function_exists('env')) {
    require_once __DIR__ . '/env_loader.php';
}

/**
 * Detect environment (local or production)
 * Uses SERVER_NAME or HTTP_HOST to determine environment
 */
function detectEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // Local development environments
    $local_hosts = ['localhost', '127.0.0.1', '::1'];
    $is_local = in_array($host, $local_hosts) || strpos($host, 'localhost') !== false || 
                strpos($host, '192.168.') === 0 || strpos($host, '10.') === 0;
    
    return $is_local ? 'local' : 'production';
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $environment = getenv('APP_ENV') ?: detectEnvironment();
        
        // Load database configuration based on environment
        if ($environment === 'local') {
            // Local development settings
            $this->host = getenv('DB_HOST') ?: 'localhost';
            $this->db_name = getenv('DB_NAME') ?: 'ecommerce_2025A_aduot_jok';
            $this->username = getenv('DB_USER') ?: 'root';
            $this->password = getenv('DB_PASS') ?: '';
        } else {
            // Production server settings
            $this->host = getenv('DB_HOST') ?: 'localhost';
            $this->db_name = getenv('DB_NAME') ?: 'ecommerce_2025A_aduot_jok';
            $this->username = getenv('DB_USER') ?: 'aduot.jok';
            $this->password = getenv('DB_PASS') ?: 'Aduot12';
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            // Do not echo here to avoid breaking JSON responses
            throw new Exception("Connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}

// Session management
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return $_SESSION['user'];
    }
    return null;
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

function isPremium() {
    $user = getCurrentUser();
    return $user && isset($user['is_premium']) && $user['is_premium'] && 
           (($user['premium_expires_at'] ?? null) === null || strtotime($user['premium_expires_at']) > time());
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect(auth_url('login.php'));
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        redirect(base_url('index.php'));
    }
    
    // Check admin session timeout
    require_once __DIR__ . '/admin_security.php';
    if (!checkAdminSessionTimeout()) {
        // Session timed out
        session_destroy();
        redirect(admin_url('login.php?timeout=1'));
    }
}

function requirePremium() {
    requireAuth();
    if (!isPremium()) {
        redirect(page_url('upgrade.php'));
    }
}

function redirect($url) {
    // Normalize to absolute app path if a relative path is provided
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        // If not absolute URL, ensure it starts with base path
        if (strpos($url, '/') !== 0) {
            $url = BASE_PATH . '/' . ltrim($url, '/');
        } elseif (strpos($url, BASE_PATH) !== 0) {
            // If absolute path but doesn't include base path, add it
            $url = BASE_PATH . $url;
        }
    }
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderReference() {
    return 'AFM' . date('Ymd') . rand(1000, 9999);
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function logAdminAction($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    if (!isAdmin()) return;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
             VALUES (:admin_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':admin_id' => $_SESSION['user_id'],
        ':action' => $action,
        ':table_name' => $table_name,
        ':record_id' => $record_id,
        ':old_values' => $old_values ? json_encode($old_values) : null,
        ':new_values' => $new_values ? json_encode($new_values) : null,
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}
?>
