<?php
/**
 * Create Test User
 * 
 * This script creates a test user for development purposes.
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test user credentials
$email = 'test@example.com';
$password = 'password123';
$full_name = 'Test User';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo "User already exists with email: $email\n";
        exit;
    }
    
    // Insert new user
    $query = "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'customer')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':password' => $hashed_password
    ]);
    
    $user_id = $db->lastInsertId();
    
    echo "SUCCESS: Test user created!\n\n";
    echo "User ID: $user_id\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Role: customer\n\n";
    echo "You can now login with these credentials.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>