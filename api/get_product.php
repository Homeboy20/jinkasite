<?php
// Public API endpoint to get product information for cart
// Define access constant before including config
define('JINKA_ACCESS', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$product_id = (int)$_GET['id'];

// Database connection
require_once __DIR__ . '/../includes/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Fetch product data with basic info needed for cart
    $stmt = $db->prepare("
        SELECT 
            id, 
            name, 
            sku, 
            price_kes, 
            price_tzs,
            image,
            stock_quantity,
            is_active
        FROM products 
        WHERE id = ? AND is_active = 1
    ");
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Product not found or inactive']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Convert prices to float
    $product['price_kes'] = (float)$product['price_kes'];
    $product['price_tzs'] = (float)$product['price_tzs'];
    $product['stock_quantity'] = (int)$product['stock_quantity'];
    
    // Normalize image path
    if (!empty($product['image'])) {
        // Remove 'images/products/' prefix if exists
        $imagePath = $product['image'];
        if (strpos($imagePath, 'images/products/') === 0) {
            $imagePath = substr($imagePath, strlen('images/products/'));
        }
        $product['image'] = 'images/products/' . $imagePath;
    } else {
        $product['image'] = 'images/placeholder.png';
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
