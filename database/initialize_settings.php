<?php
/**
 * Initialize Settings Table
 * 
 * This script initializes the settings table with default values
 */

require_once '../config/database.php';
require_once '../config/settings.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if settings table exists
    $query = "SHOW TABLES LIKE 'settings'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "Settings table does not exist. Please run the database schema first.\n";
        exit(1);
    }
    
    // Default settings to insert
    $default_settings = [
        // General settings
        ['site_name', 'AfroMarry', 'string', 'Site name'],
        ['site_email', 'admin@afromarry.com', 'string', 'Site email address'],
        ['site_phone', '+1234567890', 'string', 'Site phone number'],
        ['site_address', '', 'string', 'Site address'],
        ['currency', 'USD', 'string', 'Default currency'],
        ['timezone', 'UTC', 'string', 'Default timezone'],
        ['maintenance_mode', '0', 'boolean', 'Maintenance mode status'],
        
        // Email settings
        ['smtp_host', '', 'string', 'SMTP host'],
        ['smtp_port', '587', 'integer', 'SMTP port'],
        ['smtp_username', '', 'string', 'SMTP username'],
        ['smtp_password', '', 'string', 'SMTP password'],
        ['smtp_encryption', 'tls', 'string', 'SMTP encryption'],
        ['from_email', 'noreply@afromarry.com', 'string', 'From email address'],
        ['from_name', 'AfroMarry', 'string', 'From name'],
        
        // Payment settings
        ['paystack_public_key', '', 'string', 'Paystack public key'],
        ['paystack_secret_key', '', 'string', 'Paystack secret key'],
        ['flutterwave_public_key', '', 'string', 'Flutterwave public key'],
        ['flutterwave_secret_key', '', 'string', 'Flutterwave secret key'],
        ['bank_account_name', '', 'string', 'Bank account name for transfers'],
        ['bank_account_number', '', 'string', 'Bank account number for transfers'],
        ['bank_name', '', 'string', 'Bank name for transfers'],
        
        // Security settings
        ['session_timeout', '3600', 'integer', 'Session timeout in seconds'],
        ['max_login_attempts', '5', 'integer', 'Maximum login attempts'],
        ['password_min_length', '6', 'integer', 'Minimum password length'],
        ['require_email_verification', '0', 'boolean', 'Require email verification'],
        ['enable_two_factor', '0', 'boolean', 'Enable two-factor authentication']
    ];
    
    // Insert default settings
    foreach ($default_settings as $setting) {
        list($key, $value, $type, $description) = $setting;
        
        // Check if setting already exists
        $checkQuery = "SELECT id FROM settings WHERE setting_key = :key";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':key' => $key]);
        
        if (!$checkStmt->fetch()) {
            // Insert new setting
            $insertQuery = "INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES (:key, :value, :type, :description)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':type' => $type,
                ':description' => $description
            ]);
            
            echo "Inserted setting: $key\n";
        } else {
            echo "Setting already exists: $key\n";
        }
    }
    
    echo "Settings initialization completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error initializing settings: " . $e->getMessage() . "\n";
    exit(1);
}
?>