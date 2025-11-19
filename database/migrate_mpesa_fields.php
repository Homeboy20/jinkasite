<?php
/**
 * Database Migration: Add M-Pesa fields to orders table
 * Run this file once to update the database schema
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/../includes/config.php';

$db = Database::getInstance()->getConnection();

echo "Adding M-Pesa fields to orders table...\n";

try {
    // Check if columns already exist
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'mpesa_checkout_request_id'");
    if ($result->num_rows > 0) {
        echo "✓ M-Pesa fields already exist.\n";
        exit;
    }
    
    // Add M-Pesa columns
    $sql = "
        ALTER TABLE `orders` 
        ADD COLUMN `mpesa_checkout_request_id` VARCHAR(100) NULL,
        ADD COLUMN `mpesa_merchant_request_id` VARCHAR(100) NULL,
        ADD COLUMN `mpesa_receipt_number` VARCHAR(50) NULL
    ";
    
    if ($db->query($sql)) {
        echo "✓ M-Pesa columns added successfully.\n";
        
        // Add indexes
        $db->query("ALTER TABLE `orders` ADD INDEX `idx_mpesa_checkout` (`mpesa_checkout_request_id`)");
        $db->query("ALTER TABLE `orders` ADD INDEX `idx_mpesa_merchant` (`mpesa_merchant_request_id`)");
        echo "✓ Indexes created successfully.\n";
        
        echo "\n✓ Migration completed successfully!\n";
    } else {
        echo "✗ Error: " . $db->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
