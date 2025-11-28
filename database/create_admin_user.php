<?php
/**
 * Create Admin User
 * 
 * This script creates an admin user for development purposes.
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Admin user credentials
$email = 'admin@afromarry.com';
$password = 'admin123';
$full_name = 'Admin User';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin user already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email AND role = 'admin'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo "Admin user already exists with email: $email\n";
        exit;
    }
    
    // Insert new admin user
    $query = "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'admin')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':password' => $hashed_password
    ]);
    
    $user_id = $db->lastInsertId();
    
    echo "SUCCESS: Admin user created!\n\n";
    echo "User ID: $user_id\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Role: admin\n\n";
    echo "You can now login with these credentials at: " . base_url('admin/login.php') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>