<?php
/**
 * MySQLi Database Setup Script for JINKA Plotter Website
 * Alternative setup when PDO MySQL is not available
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jinka_plotter');
define('DB_CHARSET', 'utf8mb4');

echo "🚀 JINKA Plotter Database Setup (MySQLi)\n";
echo "========================================\n\n";

try {
    // Connect without database name first to create the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset(DB_CHARSET);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "✅ Database '" . DB_NAME . "' created or already exists\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db(DB_NAME);
    
    // Read and execute the schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: " . $schemaFile);
    }
    
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new Exception("Could not read schema file");
    }
    
    echo "📊 Creating database tables...\n";
    
    // Split the schema into individual queries and execute them
    $queries = explode(';', $schema);
    $tableCount = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strpos($query, '--') === 0) {
            continue;
        }
        
        echo "Executing query: " . substr($query, 0, 50) . "...\n";
        
        if ($conn->query($query)) {
            if (stripos($query, 'CREATE TABLE') !== false) {
                $tableCount++;
                // Extract table name for display
                if (preg_match('/CREATE TABLE `?(\w+)`?/', $query, $matches)) {
                    echo "  ✅ Table '{$matches[1]}' created\n";
                }
            }
        } else {
            echo "❌ Error: " . $conn->error . "\n";
            echo "Query: " . $query . "\n\n";
            // Only throw error if it's not a "table already exists" error
            if ($conn->errno !== 1050) {
                throw new Exception("Error executing query: " . $conn->error . "\nQuery: " . substr($query, 0, 200) . "...");
            }
        }
    }
    
    echo "\n📋 Inserting initial data...\n";
    
    // Insert default admin user
    $adminPassword = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $adminSql = "INSERT IGNORE INTO admin_users (username, email, password_hash, full_name, role, is_active) 
                 VALUES ('admin', 'admin@procutsolutions.com', ?, 'System Administrator', 'super_admin', 1)";
    
    $stmt = $conn->prepare($adminSql);
    if ($stmt) {
        $stmt->bind_param('s', $adminPassword);
        if ($stmt->execute()) {
            echo "  ✅ Default admin user created\n";
            echo "     Username: admin\n";
            echo "     Password: Admin@123456\n";
        } else {
            echo "  ⚠️  Admin user may already exist\n";
        }
        $stmt->close();
    }
    
    // Insert sample categories
    $categoriesData = [
        ['Cutting Plotters', 'cutting-plotters', 'Professional vinyl cutting machines for signage and graphics'],
        ['Wide Format Printers', 'wide-format-printers', 'Large format printing solutions for banners and posters'],
        ['Heat Press Machines', 'heat-press-machines', 'Heat transfer equipment for textiles and promotional items'],
        ['Vinyl Materials', 'vinyl-materials', 'High-quality vinyl rolls and sheets for cutting applications'],
        ['Printing Supplies', 'printing-supplies', 'Inks, papers, and accessories for printing equipment']
    ];
    
    $categorySql = "INSERT IGNORE INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($categorySql);
    
    if ($stmt) {
        foreach ($categoriesData as $category) {
            $stmt->bind_param('sss', $category[0], $category[1], $category[2]);
            if ($stmt->execute()) {
                echo "  ✅ Category '{$category[0]}' added\n";
            }
        }
        $stmt->close();
    }
    
    // Insert sample product
    $productSql = "INSERT IGNORE INTO products (name, slug, sku, category_id, short_description, description, price_kes, price_tzs, is_active, is_featured)
                   SELECT 'JINKA PRO 1350 Cutting Plotter', 'jinka-pro-1350', 'JINKA-PRO-1350', c.id, 
                          'Professional 1350mm cutting plotter for vinyl and graphics',
                          'High-precision cutting plotter designed for professional signage and graphics applications. Features advanced servo motor technology and user-friendly software.',
                          285000.00, 720000.00, 1, 1
                   FROM categories c WHERE c.slug = 'cutting-plotters' LIMIT 1";
    
    if ($conn->query($productSql)) {
        echo "  ✅ Sample product added\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "=====================================\n\n";
    echo "📍 Next steps:\n";
    echo "1. Visit: http://localhost/jinkaplotterwebsite/admin/login.php\n";
    echo "2. Login with: admin / Admin@123456\n";
    echo "3. Start managing your products and orders\n\n";
    echo "✨ Your JINKA Plotter website is ready!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "Please check your MySQL configuration and try again.\n";
    exit(1);
}
?>