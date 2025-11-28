<?php
/**
 * Reset Admin Password
 * 
 * This script resets the admin password to a default value.
 * Run this from browser or command line.
 */

// Skip session for CLI
if (php_sapi_name() === 'cli') {
    define('SKIP_SESSION', true);
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// New password - CHANGE THIS TO YOUR DESIRED PASSWORD
$new_password = 'admin123'; // Default password - CHANGE THIS!

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update admin password
    $query = "UPDATE users SET password = :password WHERE email = 'admin@afromarry.com' AND role = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute([':password' => $hashed_password]);
    
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        echo "SUCCESS: Admin password has been reset!\n\n";
        echo "Email: admin@afromarry.com\n";
        echo "Password: $new_password\n\n";
        echo "IMPORTANT: Please change this password after logging in!\n";
        echo "Login at: " . base_url('admin/login.php') . "\n";
    } else {
        echo "ERROR: Admin user not found. Make sure the admin user exists in the database.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

