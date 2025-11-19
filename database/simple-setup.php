<?php
/**
 * Simple Database Setup - Create Essential Tables Only
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jinka_plotter');

echo "🚀 Simple Database Setup\n";
echo "========================\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // Create database
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);
    
    echo "✅ Database connected\n\n";
    
    // Create admin_users table
    echo "Creating admin_users table...\n";
    $adminUsersSQL = "
    CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `email` varchar(255) NOT NULL UNIQUE,
        `password_hash` varchar(255) NOT NULL,
        `full_name` varchar(255) NULL,
        `role` enum('super_admin','admin','editor') NOT NULL DEFAULT 'admin',
        `is_active` tinyint(1) DEFAULT 1,
        `last_login` datetime NULL,
        `login_attempts` int(11) DEFAULT 0,
        `lockout_until` datetime NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_username` (`username`),
        KEY `idx_email` (`email`),
        KEY `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($adminUsersSQL)) {
        echo "✅ admin_users table created\n";
    } else {
        echo "❌ Error creating admin_users: " . $conn->error . "\n";
    }
    
    // Create categories table
    echo "Creating categories table...\n";
    $categoriesSQL = "
    CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(100) NOT NULL UNIQUE,
        `description` text,
        `image` varchar(255) NULL,
        `parent_id` int(11) NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `seo_title` varchar(255) NULL,
        `seo_description` varchar(255) NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_parent_id` (`parent_id`),
        KEY `idx_is_active` (`is_active`),
        FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($categoriesSQL)) {
        echo "✅ categories table created\n";
    } else {
        echo "❌ Error creating categories: " . $conn->error . "\n";
    }
    
    // Create products table
    echo "Creating products table...\n";
    $productsSQL = "
    CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL UNIQUE,
        `sku` varchar(50) NOT NULL UNIQUE,
        `category_id` int(11) NULL,
        `short_description` text,
        `description` longtext,
        `specifications` JSON,
        `features` JSON,
        `price_kes` decimal(12,2) NOT NULL DEFAULT 0.00,
        `price_tzs` decimal(15,2) NOT NULL DEFAULT 0.00,
        `compare_price_kes` decimal(12,2) NULL,
        `compare_price_tzs` decimal(15,2) NULL,
        `cost_price` decimal(12,2) NULL,
        `stock_quantity` int(11) DEFAULT 0,
        `min_stock_level` int(11) DEFAULT 0,
        `track_stock` tinyint(1) DEFAULT 1,
        `allow_backorder` tinyint(1) DEFAULT 0,
        `weight` decimal(8,2) NULL COMMENT 'Weight in kg',
        `dimensions` JSON COMMENT 'Length, Width, Height in cm',
        `warranty_period` int(11) DEFAULT 12 COMMENT 'Warranty in months',
        `images` JSON COMMENT 'Array of image URLs',
        `is_featured` tinyint(1) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `seo_title` varchar(255) NULL,
        `seo_description` varchar(255) NULL,
        `seo_keywords` varchar(255) NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_category_id` (`category_id`),
        KEY `idx_is_active` (`is_active`),
        KEY `idx_is_featured` (`is_featured`),
        KEY `idx_price_kes` (`price_kes`),
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($productsSQL)) {
        echo "✅ products table created\n";
    } else {
        echo "❌ Error creating products: " . $conn->error . "\n";
    }
    
    // Insert admin user
    echo "\nInserting default admin user...\n";
    $passwordHash = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $username = 'admin';
    $email = 'admin@procutsolutions.com';
    $fullName = 'System Administrator';
    $role = 'super_admin';
    
    $stmt->bind_param('sssss', $username, $email, $passwordHash, $fullName, $role);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully\n";
        echo "   Username: admin\n";
        echo "   Password: Admin@123456\n";
    } else {
        echo "⚠️ Admin user may already exist\n";
    }
    
    // Insert sample categories
    echo "\nInserting sample categories...\n";
    $categories = [
        ['Cutting Plotters', 'cutting-plotters', 'Professional vinyl cutting machines'],
        ['Wide Format Printers', 'wide-format-printers', 'Large format printing solutions'],
        ['Heat Press Machines', 'heat-press-machines', 'Heat transfer equipment'],
        ['Vinyl Materials', 'vinyl-materials', 'High-quality vinyl rolls and sheets'],
        ['Printing Supplies', 'printing-supplies', 'Inks, papers, and accessories']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, 1)");
    
    foreach ($categories as $category) {
        $stmt->bind_param('sss', $category[0], $category[1], $category[2]);
        if ($stmt->execute()) {
            echo "✅ Category '{$category[0]}' added\n";
        }
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "=====================================\n\n";
    echo "📍 You can now:\n";
    echo "1. Visit: http://localhost/jinkaplotterwebsite/admin/login.php\n";
    echo "2. Login with: admin / Admin@123456\n";
    echo "3. Start managing your products!\n";
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>