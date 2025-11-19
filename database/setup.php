<?php
/**
 * Database Setup Script
 * 
 * This script creates the database and initializes it with default data.
 * Run this once after setting up the project.
 * 
 * Usage: php database/setup.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('JINKA_ACCESS', true);
require_once '../includes/config.php';

echo "🚀 JINKA Plotter Database Setup\n";
echo "================================\n\n";

try {
    // Connect to MySQL server (without database)
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '" . DB_NAME . "' created/verified\n";
    
    // Use the database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Remove the database creation part since we already did it
    $schema = preg_replace('/CREATE DATABASE.*?;/s', '', $schema);
    $schema = preg_replace('/USE `.*?`;/s', '', $schema);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Database schema created successfully\n";
    
    // Verify tables were created
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ Created " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    
    // Verify default admin user
    $stmt = $pdo->prepare("SELECT username, email, role FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Default admin user created:\n";
        echo "   Username: " . $admin['username'] . "\n";
        echo "   Email: " . $admin['email'] . "\n";
        echo "   Role: " . $admin['role'] . "\n";
        echo "   Password: Admin@123456 (Please change after login)\n";
    }
    
    // Verify default product
    $stmt = $pdo->prepare("SELECT name, sku, price_kes FROM products WHERE sku = 'JINKA-XL-1351E'");
    $stmt->execute();
    $product = $stmt->fetch();
    
    if ($product) {
        echo "✅ Default product created:\n";
        echo "   Name: " . $product['name'] . "\n";
        echo "   SKU: " . $product['sku'] . "\n";
        echo "   Price: KES " . number_format($product['price_kes']) . "\n";
    }
    
    echo "\n🎉 Setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Access admin panel at: " . ADMIN_URL . "\n";
    echo "2. Login with username: admin, password: Admin@123456\n";
    echo "3. Change default admin password immediately\n";
    echo "4. Configure email settings in config.php\n";
    echo "5. Upload product images to images/ folder\n";
    echo "6. Test the website functionality\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
?>