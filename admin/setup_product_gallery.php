<?php
// Setup script to create product_images table and add video_url column
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$db = Database::getInstance()->getConnection();

// Create product_images table
$sql1 = "CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_featured` (`is_featured`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Add video_url column
$sql2 = "ALTER TABLE `products` ADD COLUMN `video_url` varchar(500) DEFAULT NULL AFTER `image`";

// Create index
$sql3 = "CREATE INDEX idx_product_images_featured ON product_images(product_id, is_featured)";

try {
    // Create table
    if ($db->query($sql1)) {
        echo "✅ Product images table created successfully<br>";
    } else {
        echo "ℹ️ Product images table already exists or error: " . $db->error . "<br>";
    }
    
    // Add video_url column
    if ($db->query($sql2)) {
        echo "✅ Video URL column added to products table<br>";
    } else {
        echo "ℹ️ Video URL column already exists or error: " . $db->error . "<br>";
    }
    
    // Create index
    if ($db->query($sql3)) {
        echo "✅ Index created successfully<br>";
    } else {
        echo "ℹ️ Index already exists or error: " . $db->error . "<br>";
    }
    
    echo "<br><strong>Setup completed! You can now use the product gallery feature.</strong>";
    echo "<br><a href='products.php'>Go to Products</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
