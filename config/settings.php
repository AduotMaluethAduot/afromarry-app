<?php
/**
 * Settings Management
 * 
 * Functions to manage application settings stored in the database
 */

require_once 'database.php';

/**
 * Get a setting value by key
 * 
 * @param string $key The setting key
 * @param mixed $default The default value if setting not found
 * @return mixed The setting value or default
 */
function getSetting($key, $default = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT setting_value, setting_type FROM settings WHERE setting_key = :key";
        $stmt = $db->prepare($query);
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();
        
        if ($result) {
            $value = $result['setting_value'];
            $type = $result['setting_type'];
            
            // Convert value based on type
            switch ($type) {
                case 'integer':
                    return (int)$value;
                case 'float':
                    return (float)$value;
                case 'boolean':
                    return (bool)$value;
                case 'json':
                    return json_decode($value, true);
                default:
                    return $value;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting setting '$key': " . $e->getMessage());
    }
    
    return $default;
}

/**
 * Set a setting value
 * 
 * @param string $key The setting key
 * @param mixed $value The setting value
 * @param string $type The setting type (string, integer, float, boolean, json)
 * @return bool Success status
 */
function setSetting($key, $value, $type = 'string') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Convert value based on type
        switch ($type) {
            case 'integer':
                $value = (string)(int)$value;
                break;
            case 'float':
                $value = (string)(float)$value;
                break;
            case 'boolean':
                $value = (string)(bool)$value;
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        // Check if setting exists
        $query = "SELECT id FROM settings WHERE setting_key = :key";
        $stmt = $db->prepare($query);
        $stmt->execute([':key' => $key]);
        
        if ($stmt->fetch()) {
            // Update existing setting
            $query = "UPDATE settings SET setting_value = :value, setting_type = :type, updated_at = NOW() WHERE setting_key = :key";
        } else {
            // Insert new setting
            $query = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (:key, :value, :type)";
        }
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':type' => $type
        ]);
        
        return $result;
    } catch (Exception $e) {
        error_log("Error setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings as an associative array
 * 
 * @return array Associative array of settings
 */
function getAllSettings() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT setting_key, setting_value, setting_type FROM settings";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $row) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            $type = $row['setting_type'];
            
            // Convert value based on type
            switch ($type) {
                case 'integer':
                    $settings[$key] = (int)$value;
                    break;
                case 'float':
                    $settings[$key] = (float)$value;
                    break;
                case 'boolean':
                    $settings[$key] = (bool)$value;
                    break;
                case 'json':
                    $settings[$key] = json_decode($value, true);
                    break;
                default:
                    $settings[$key] = $value;
            }
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting all settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Load settings into global constants
 */
function loadSettings() {
    $settings = getAllSettings();
    
    // Define constants for commonly used settings
    define('SITE_NAME', $settings['site_name'] ?? 'AfroMarry');
    define('SITE_EMAIL', $settings['site_email'] ?? 'admin@afromarry.com');
    define('CURRENCY', $settings['currency'] ?? 'USD');
    define('TIMEZONE', $settings['timezone'] ?? 'UTC');
    
    // Payment settings
    define('PAYSTACK_PUBLIC_KEY', $settings['paystack_public_key'] ?? '');
    define('PAYSTACK_SECRET_KEY', $settings['paystack_secret_key'] ?? '');
    define('FLUTTERWAVE_PUBLIC_KEY', $settings['flutterwave_public_key'] ?? '');
    define('FLUTTERWAVE_SECRET_KEY', $settings['flutterwave_secret_key'] ?? '');
    
    // Bank transfer settings
    define('BANK_ACCOUNT_NAME', $settings['bank_account_name'] ?? '');
    define('BANK_ACCOUNT_NUMBER', $settings['bank_account_number'] ?? '');
    define('BANK_NAME', $settings['bank_name'] ?? '');
}

?>