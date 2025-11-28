<?php
/**
 * Verify Ad Tables
 * Checks if all ad system tables exist
 */

require_once __DIR__ . '/../config/database.php';

// Skip session for CLI
if (php_sapi_name() === 'cli') {
    define('SKIP_SESSION', true);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tables = ['advertisers', 'ad_campaigns', 'ad_placements', 'ad_interactions'];
    $all_exist = true;
    
    echo "Checking ad system tables...\n\n";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table is missing\n";
            $all_exist = false;
        }
    }
    
    if ($all_exist) {
        echo "\n✅ All ad system tables are present!\n";
    } else {
        echo "\n❌ Some tables are missing. Run: php database/migration_add_ad_tables.php\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

