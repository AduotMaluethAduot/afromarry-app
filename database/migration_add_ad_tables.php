<?php
/**
 * Migration: Add Ad System Tables
 * 
 * Creates the ad_placements, ad_campaigns, advertisers, and ad_interactions tables
 * if they don't exist.
 */

require_once __DIR__ . '/../config/database.php';

// Skip session for CLI
if (php_sapi_name() === 'cli') {
    define('SKIP_SESSION', true);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating ad system tables...\n\n";
    
    // Create advertisers table
    $sql = "CREATE TABLE IF NOT EXISTS advertisers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(20),
        website VARCHAR(500),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✓ Created advertisers table\n";
    
    // Create ad_campaigns table
    $sql = "CREATE TABLE IF NOT EXISTS ad_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        advertiser_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        budget DECIMAL(10,2) NOT NULL,
        daily_budget DECIMAL(10,2) NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✓ Created ad_campaigns table\n";
    
    // Create ad_placements table
    $sql = "CREATE TABLE IF NOT EXISTS ad_placements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        type ENUM('banner', 'sidebar', 'inline', 'popup') DEFAULT 'banner',
        image_url VARCHAR(500) NOT NULL,
        link_url VARCHAR(500) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cost per click or impression',
        tribe_targeting JSON NULL COMMENT 'Target specific tribes (JSON array)',
        region_targeting JSON NULL COMMENT 'Target specific regions (JSON array)',
        is_active BOOLEAN DEFAULT TRUE,
        impressions_count INT DEFAULT 0,
        clicks_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
        INDEX idx_campaign_id (campaign_id),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✓ Created ad_placements table\n";
    
    // Create ad_interactions table
    $sql = "CREATE TABLE IF NOT EXISTS ad_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL COMMENT 'Null for anonymous users',
        ad_placement_id INT NOT NULL,
        interaction_type ENUM('impression', 'click') NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (ad_placement_id) REFERENCES ad_placements(id) ON DELETE CASCADE,
        INDEX idx_ad_placement_id (ad_placement_id),
        INDEX idx_interaction_type (interaction_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "✓ Created ad_interactions table\n";
    
    echo "\n✅ All ad system tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

