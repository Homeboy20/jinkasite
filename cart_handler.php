<?php
/**
 * Cart AJAX Handler
 * 
 * Handles all cart operations via AJAX
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once 'includes/config.php';
require_once 'includes/Cart.php';

header('Content-Type: application/json');

// Initialize cart
$cart = new Cart();

// Get action (supports POST, GET, and raw fetch payloads)
$rawAction = $_REQUEST['action'] ?? '';
$action = is_string($rawAction) ? trim($rawAction) : '';

if ($action === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        parse_str($rawInput, $postData);
        if (!empty($postData)) {
            $_POST = array_merge($postData, $_POST);
            $action = isset($postData['action']) ? trim($postData['action']) : '';
        }
    }
}

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        $result = $cart->addProduct($productId, $quantity);
        echo json_encode($result);
        break;
        
    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        $result = $cart->updateQuantity($productId, $quantity);
        echo json_encode($result);
        break;
        
    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        
        $result = $cart->removeProduct($productId);
        echo json_encode($result);
        break;
        
    case 'clear':
        $result = $cart->clearCart();
        echo json_encode($result);
        break;
        
    case 'get_count':
        echo json_encode([
            'success' => true,
            'count' => $cart->getItemCount()
        ]);
        break;
        
    case 'get':
    case 'get_items':
        echo json_encode([
            'success' => true,
            'items' => $cart->getItems(),
            'totals' => $cart->getTotals(),
            'item_count' => $cart->getItemCount()
        ]);
        break;
        
    case 'validate':
        $result = $cart->validateCart();
        echo json_encode($result);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
