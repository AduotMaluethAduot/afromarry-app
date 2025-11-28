<?php
/**
 * Migration script for existing databases
 * 
 * NOTE: For new installations, use database.sql instead - it includes all tables and columns.
 * This script is only needed if you already have a database and need to add the expert payment features.
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Add payment_percentage column
    try {
        $db->exec("ALTER TABLE experts ADD COLUMN payment_percentage DECIMAL(5,2) DEFAULT 70.00 COMMENT 'Percentage expert receives (admin keeps the rest)'");
        echo "Column payment_percentage added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column payment_percentage already exists.\n";
        } else {
            throw $e;
        }
    }
    
    // Create expert_payments table
    $createTable = "CREATE TABLE IF NOT EXISTS expert_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id INT NOT NULL,
        booking_ids TEXT,
        total_earnings DECIMAL(10,2) NOT NULL,
        payment_percentage DECIMAL(5,2) NOT NULL,
        expert_amount DECIMAL(10,2) NOT NULL,
        admin_commission DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(100),
        payment_reference VARCHAR(255),
        payment_date DATE NOT NULL,
        paid_by INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
        FOREIGN KEY (paid_by) REFERENCES users(id)
    )";
    
    $db->exec($createTable);
    echo "Table expert_payments created successfully.\n";
    
    // Update existing experts
    $db->exec("UPDATE experts SET payment_percentage = 70.00 WHERE payment_percentage IS NULL");
    echo "Existing experts updated with default percentage.\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

