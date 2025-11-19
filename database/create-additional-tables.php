<?php
/**
 * Create Additional Database Tables for Dashboard
 * Creates the missing tables: orders, customers, inquiries
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jinka_plotter');

echo "🔧 Creating Additional Database Tables\n";
echo "=====================================\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // Create customers table
    echo "Creating customers table...\n";
    $customersSQL = "
    CREATE TABLE IF NOT EXISTS `customers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `first_name` varchar(100) NOT NULL,
        `last_name` varchar(100) NOT NULL,
        `email` varchar(255) NOT NULL UNIQUE,
        `phone` varchar(20) NULL,
        `business_name` varchar(255) NULL,
        `address` text NULL,
        `city` varchar(100) NULL,
        `state` varchar(100) NULL,
        `postal_code` varchar(20) NULL,
        `country` varchar(100) DEFAULT 'Kenya',
        `is_active` tinyint(1) DEFAULT 1,
        `email_verified` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_email` (`email`),
        KEY `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($customersSQL)) {
        echo "✅ customers table created\n";
    } else {
        echo "❌ Error creating customers: " . $conn->error . "\n";
    }
    
    // Create orders table
    echo "Creating orders table...\n";
    $ordersSQL = "
    CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_number` varchar(50) NOT NULL UNIQUE,
        `customer_id` int(11) NULL,
        `customer_name` varchar(255) NOT NULL,
        `customer_email` varchar(255) NOT NULL,
        `customer_phone` varchar(20) NULL,
        `billing_address` JSON NULL,
        `shipping_address` JSON NULL,
        `items` JSON NOT NULL,
        `subtotal` decimal(12,2) NOT NULL,
        `tax_amount` decimal(12,2) DEFAULT 0.00,
        `shipping_cost` decimal(12,2) DEFAULT 0.00,
        `total_amount` decimal(12,2) NOT NULL,
        `currency` varchar(3) DEFAULT 'KES',
        `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
        `payment_method` varchar(50) NULL,
        `notes` text NULL,
        `whatsapp_sent` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_customer_id` (`customer_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($ordersSQL)) {
        echo "✅ orders table created\n";
    } else {
        echo "❌ Error creating orders: " . $conn->error . "\n";
    }
    
    // Create inquiries table
    echo "Creating inquiries table...\n";
    $inquiriesSQL = "
    CREATE TABLE IF NOT EXISTS `inquiries` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `phone` varchar(20) NULL,
        `company` varchar(255) NULL,
        `subject` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
        `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
        `assigned_to` int(11) NULL,
        `response` text NULL,
        `responded_at` datetime NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`),
        KEY `idx_assigned_to` (`assigned_to`),
        FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($inquiriesSQL)) {
        echo "✅ inquiries table created\n";
    } else {
        echo "❌ Error creating inquiries: " . $conn->error . "\n";
    }
    
    // Insert sample data
    echo "\nInserting sample data...\n";
    
    // Sample customers
    $customers = [
        ['John', 'Doe', 'john@example.com', '+254712345678', 'Acme Graphics', 'active'],
        ['Jane', 'Smith', 'jane@example.com', '+254787654321', 'Design Studio Ltd', 'active'],
        ['Peter', 'Mwangi', 'peter@example.com', '+254798765432', 'Nairobi Signs', 'active']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO customers (first_name, last_name, email, phone, business_name, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    
    foreach ($customers as $customer) {
        $stmt->bind_param('sssss', $customer[0], $customer[1], $customer[2], $customer[3], $customer[4]);
        if ($stmt->execute()) {
            echo "✅ Customer '{$customer[0]} {$customer[1]}' added\n";
        }
    }
    
    // Sample orders
    $orders = [
        ['ORD-2025-001', 1, 'Complete Order', 285000.00, 'confirmed'],
        ['ORD-2025-002', 2, 'Pending Order', 150000.00, 'pending'],
        ['ORD-2025-003', 3, 'Processing Order', 320000.00, 'processing']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO orders (order_number, customer_id, customer_name, customer_email, items, subtotal, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($orders as $order) {
        $customerData = $conn->query("SELECT first_name, last_name, email FROM customers WHERE id = {$order[1]}")->fetch_assoc();
        $customerName = $customerData['first_name'] . ' ' . $customerData['last_name'];
        $items = json_encode([['name' => 'JINKA PRO Plotter', 'quantity' => 1, 'price' => $order[3]]]);
        
        $stmt->bind_param('sisssdds', $order[0], $order[1], $customerName, $customerData['email'], $items, $order[3], $order[3], $order[4]);
        if ($stmt->execute()) {
            echo "✅ Order '{$order[0]}' added\n";
        }
    }
    
    // Sample inquiries
    $inquiries = [
        ['Support Request', 'john@example.com', 'Product Information', 'Need details about cutting plotters', 'new'],
        ['Technical Issue', 'jane@example.com', 'Installation Help', 'Having trouble with setup', 'in_progress'],
        ['Sales Inquiry', 'peter@example.com', 'Bulk Purchase', 'Interested in wholesale pricing', 'new']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO inquiries (name, email, subject, message, status) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($inquiries as $inquiry) {
        $stmt->bind_param('sssss', $inquiry[0], $inquiry[1], $inquiry[2], $inquiry[3], $inquiry[4]);
        if ($stmt->execute()) {
            echo "✅ Inquiry '{$inquiry[2]}' added\n";
        }
    }
    
    echo "\n🎉 Additional tables setup completed successfully!\n";
    echo "===============================================\n\n";
    echo "✅ Tables created: customers, orders, inquiries\n";
    echo "✅ Sample data added for testing\n";
    echo "✅ Dashboard will now display meaningful statistics\n\n";
    echo "Your admin dashboard is fully ready!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>