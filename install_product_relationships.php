<?php
/**
 * Install Product Relationships Table
 * Run this file once to create the database table
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once 'includes/config.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install Product Relationships</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 8px; margin: 10px 0; }
        h1 { color: #2563eb; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; }
        .btn { background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🛠️ Install Product Relationships Table</h1>";

// SQL to create the table
$sql = "CREATE TABLE IF NOT EXISTS `product_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'Main product',
  `related_product_id` int(11) NOT NULL COMMENT 'Related/upsell product',
  `relationship_type` enum('related','upsell','cross_sell','accessory','bundle') DEFAULT 'related' COMMENT 'Type of relationship',
  `display_order` int(11) DEFAULT 0 COMMENT 'Order for display',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  KEY `idx_relationship_type` (`relationship_type`),
  UNIQUE KEY `unique_relationship` (`product_id`, `related_product_id`, `relationship_type`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    // Create the main table
    if ($db->query($sql)) {
        echo "<div class='success'>✅ Table 'product_relationships' created successfully!</div>";
    } else {
        throw new Exception($db->error);
    }
    
    // Create additional indexes
    $index1 = "CREATE INDEX IF NOT EXISTS `idx_active_relationships` ON `product_relationships` (`product_id`, `is_active`, `relationship_type`)";
    $index2 = "CREATE INDEX IF NOT EXISTS `idx_display` ON `product_relationships` (`product_id`, `display_order`)";
    
    if ($db->query($index1)) {
        echo "<div class='success'>✅ Index 'idx_active_relationships' created successfully!</div>";
    }
    
    if ($db->query($index2)) {
        echo "<div class='success'>✅ Index 'idx_display' created successfully!</div>";
    }
    
    echo "<div class='info'>
        <h3>🎉 Installation Complete!</h3>
        <p>The product_relationships table has been created with all necessary indexes.</p>
        <p><strong>What's Next?</strong></p>
        <ul>
            <li>Visit any product detail page to see recommendations in action</li>
            <li>Recommendations will appear automatically using smart algorithm</li>
            <li>Optionally add manual relationships using the ProductRelationships class</li>
        </ul>
        <p><strong>Security Note:</strong> You can delete this file (<code>install_product_relationships.php</code>) after installation.</p>
    </div>";
    
    echo "<a href='products.php' class='btn'>View Products</a>";
    echo " <a href='test_upsell.php' class='btn' style='background: #059669;'>Test Feature</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error creating table: " . htmlspecialchars($e->getMessage()) . "</div>";
    
    // Check if table already exists
    $check = $db->query("SHOW TABLES LIKE 'product_relationships'");
    if ($check && $check->num_rows > 0) {
        echo "<div class='info'>
            ℹ️ The table 'product_relationships' already exists. 
            <br><br>
            If you're seeing errors, it might be a permissions issue or the table structure needs updating.
            <br><br>
            Try dropping and recreating the table:
            <br><code>DROP TABLE IF EXISTS product_relationships;</code>
            <br>Then run this installer again.
        </div>";
    }
}

echo "</body></html>";
?>
