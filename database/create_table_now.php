<?php
/**
 * Migration script for existing databases ONLY
 * 
 * NOTE: For new installations, use database.sql instead - it includes ALL tables.
 * This script is only for existing databases that need to add expert payment features.
 * 
 * Run this once: http://localhost/AfroMarry/database/create_table_now.php
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Creating Expert Payments Table</h2>";
echo "<pre>";

try {
    // Create expert_payments table
    $createTableSQL = "CREATE TABLE IF NOT EXISTS expert_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id INT NOT NULL,
        booking_ids TEXT COMMENT 'Comma-separated booking IDs included in this payment',
        total_earnings DECIMAL(10,2) NOT NULL COMMENT 'Total amount from bookings',
        payment_percentage DECIMAL(5,2) NOT NULL COMMENT 'Percentage paid to expert',
        expert_amount DECIMAL(10,2) NOT NULL COMMENT 'Amount paid to expert',
        admin_commission DECIMAL(10,2) NOT NULL COMMENT 'Amount kept by admin',
        payment_method VARCHAR(100) COMMENT 'Payment method used',
        payment_reference VARCHAR(255) COMMENT 'Transaction reference',
        payment_date DATE NOT NULL,
        paid_by INT NOT NULL COMMENT 'Admin who made the payment',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
        FOREIGN KEY (paid_by) REFERENCES users(id)
    )";
    
    $db->exec($createTableSQL);
    echo "✓ expert_payments table created successfully!\n\n";
    
    // Add payment_percentage column to experts table
    try {
        $alterSQL = "ALTER TABLE experts ADD COLUMN payment_percentage DECIMAL(5,2) DEFAULT 70.00 COMMENT 'Percentage expert receives (admin keeps the rest)'";
        $db->exec($alterSQL);
        echo "✓ payment_percentage column added to experts table!\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ payment_percentage column already exists (that's fine)\n\n";
        } else {
            throw $e;
        }
    }
    
    // Update existing experts
    $updateSQL = "UPDATE experts SET payment_percentage = 70.00 WHERE payment_percentage IS NULL";
    $db->exec($updateSQL);
    echo "✓ Updated existing experts with default payment percentage!\n\n";
    
    echo "<strong style='color: green;'>SUCCESS! All tables and columns created.</strong>\n";
    echo "\nYou can now close this page and use the Experts Management page.\n";
    
} catch (PDOException $e) {
    echo "<strong style='color: red;'>ERROR:</strong>\n";
    echo $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='../admin/experts.php'>Go to Experts Management</a></p>";
?>

