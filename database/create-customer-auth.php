<?php
/**
 * Create Customer Authentication System Tables
 * Adds password and authentication fields to customers table
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jinka_plotter');

echo "🔧 Setting Up Customer Authentication System\n";
echo "============================================\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // Add authentication columns to customers table
    echo "Adding authentication columns to customers table...\n";
    
    $alterSQL = "
    ALTER TABLE `customers` 
    ADD COLUMN IF NOT EXISTS `password` varchar(255) NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `remember_token` varchar(255) NULL,
    ADD COLUMN IF NOT EXISTS `email_verification_token` varchar(255) NULL,
    ADD COLUMN IF NOT EXISTS `password_reset_token` varchar(255) NULL,
    ADD COLUMN IF NOT EXISTS `password_reset_expires` datetime NULL,
    ADD COLUMN IF NOT EXISTS `last_login` datetime NULL,
    ADD COLUMN IF NOT EXISTS `login_attempts` int(11) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `locked_until` datetime NULL;
    ";
    
    // Run alter statements individually to handle IF NOT EXISTS
    $columns = [
        "ADD COLUMN `password` varchar(255) NULL AFTER `email`",
        "ADD COLUMN `remember_token` varchar(255) NULL",
        "ADD COLUMN `email_verification_token` varchar(255) NULL",
        "ADD COLUMN `password_reset_token` varchar(255) NULL",
        "ADD COLUMN `password_reset_expires` datetime NULL",
        "ADD COLUMN `last_login` datetime NULL",
        "ADD COLUMN `login_attempts` int(11) DEFAULT 0",
        "ADD COLUMN `locked_until` datetime NULL"
    ];
    
    foreach ($columns as $column) {
        $sql = "ALTER TABLE `customers` " . $column;
        if ($conn->query($sql)) {
            echo "✅ Column added\n";
        } else {
            // Column might already exist, check error
            if (strpos($conn->error, 'Duplicate column name') === false) {
                echo "⚠️ " . $conn->error . "\n";
            } else {
                echo "✓ Column already exists\n";
            }
        }
    }
    
    // Create customer addresses table
    echo "\nCreating customer_addresses table...\n";
    $addressesSQL = "
    CREATE TABLE IF NOT EXISTS `customer_addresses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `address_type` enum('billing','shipping') NOT NULL,
        `is_default` tinyint(1) DEFAULT 0,
        `full_name` varchar(255) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `address_line1` varchar(255) NOT NULL,
        `address_line2` varchar(255) NULL,
        `city` varchar(100) NOT NULL,
        `state` varchar(100) NULL,
        `postal_code` varchar(20) NULL,
        `country` varchar(100) NOT NULL DEFAULT 'Kenya',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_customer_id` (`customer_id`),
        KEY `idx_is_default` (`is_default`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($addressesSQL)) {
        echo "✅ customer_addresses table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    // Create customer wishlists table
    echo "Creating customer_wishlists table...\n";
    $wishlistSQL = "
    CREATE TABLE IF NOT EXISTS `customer_wishlists` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wishlist` (`customer_id`, `product_id`),
        KEY `idx_customer_id` (`customer_id`),
        KEY `idx_product_id` (`product_id`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($wishlistSQL)) {
        echo "✅ customer_wishlists table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    // Create customer reviews table
    echo "Creating customer_reviews table...\n";
    $reviewsSQL = "
    CREATE TABLE IF NOT EXISTS `customer_reviews` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `order_id` int(11) NULL,
        `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
        `review_title` varchar(255) NOT NULL,
        `review_text` text NOT NULL,
        `is_verified_purchase` tinyint(1) DEFAULT 0,
        `is_approved` tinyint(1) DEFAULT 0,
        `helpful_count` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_customer_id` (`customer_id`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_is_approved` (`is_approved`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($reviewsSQL)) {
        echo "✅ customer_reviews table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    // Create customer activity log table
    echo "Creating customer_activity_log table...\n";
    $activitySQL = "
    CREATE TABLE IF NOT EXISTS `customer_activity_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `activity_type` varchar(50) NOT NULL,
        `activity_description` text NOT NULL,
        `ip_address` varchar(45) NULL,
        `user_agent` varchar(255) NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_customer_id` (`customer_id`),
        KEY `idx_activity_type` (`activity_type`),
        KEY `idx_created_at` (`created_at`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($activitySQL)) {
        echo "✅ customer_activity_log table created\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    echo "\n🎉 Customer Authentication System Setup Complete!\n";
    echo "================================================\n\n";
    echo "✅ Enhanced customers table with authentication\n";
    echo "✅ Created customer_addresses table\n";
    echo "✅ Created customer_wishlists table\n";
    echo "✅ Created customer_reviews table\n";
    echo "✅ Created customer_activity_log table\n\n";
    echo "Ready to implement:\n";
    echo "  • Customer registration & login\n";
    echo "  • Profile management\n";
    echo "  • Order history\n";
    echo "  • Address book\n";
    echo "  • Wishlist functionality\n";
    echo "  • Product reviews\n";
    echo "  • Activity tracking\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
