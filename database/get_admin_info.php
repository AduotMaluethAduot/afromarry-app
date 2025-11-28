<?php
/**
 * Get Admin Information
 * Simple script to check admin user and provide login credentials
 */

// Direct database connection (bypass session)
$host = 'localhost';
$db_name = 'afromarry';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get admin user
    $query = "SELECT id, full_name, email, role FROM users WHERE role = 'admin' LIMIT 1";
    $stmt = $db->query($query);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "=== ADMIN USER FOUND ===\n\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Name: " . $admin['full_name'] . "\n";
        echo "Role: " . $admin['role'] . "\n\n";
        echo "NOTE: The default password hash in database.sql is:\n";
        echo "\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\n\n";
        echo "This is typically 'password' but may have been changed.\n";
        echo "If you can't login, run: php database/reset_admin_password.php\n";
    } else {
        echo "ERROR: No admin user found in database.\n";
        echo "Please run database.sql to create the default admin user.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

