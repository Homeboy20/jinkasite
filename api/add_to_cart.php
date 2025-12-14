<?php
// API endpoint to add item to PHP session cart
define('JINKA_ACCESS', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

try {
    $cart = new Cart();
    $result = $cart->addProduct($productId, $quantity);
    
    if ($result['success']) {
        $itemCount = $cart->getItemCount();
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? 'Product added to cart',
            'cart_count' => $itemCount
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
