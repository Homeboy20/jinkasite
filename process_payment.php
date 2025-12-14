<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Cart.php';
require_once __DIR__ . '/includes/payments/PaymentGatewayManager.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$gateway = strtolower(trim($input['gateway'] ?? ''));
$currency = strtoupper(trim($input['currency'] ?? DEFAULT_CURRENCY));
$countryCode = strtoupper(trim($input['country_code'] ?? ''));
$customer = [
    'name' => trim($input['customer_name'] ?? ''),
    'email' => trim($input['customer_email'] ?? ''),
    'phone' => trim($input['customer_phone'] ?? ''),
];
$csrfToken = $input['csrf_token'] ?? '';

if (empty($_SESSION['checkout_csrf']) || !hash_equals($_SESSION['checkout_csrf'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security check failed. Please refresh and try again.']);
    exit;
}

if (!$gateway) {
    echo json_encode(['success' => false, 'error' => 'Select a payment method.']);
    exit;
}

if (empty($customer['name']) || empty($customer['email']) || empty($customer['phone'])) {
    echo json_encode(['success' => false, 'error' => 'Provide name, email, and phone number.']);
    exit;
}

$cart = new Cart();
$totals = $cart->getTotals();
$currencyKey = strtolower($currency);
$cartItems = $cart->getItems();

if (!function_exists('compute_cart_hash')) {
    function compute_cart_hash(array $items, array $totals) {
        $sortedItems = $items;
        if (!empty($sortedItems)) {
            ksort($sortedItems);
        }
        return hash_hmac(
            'sha256',
            json_encode(['items' => $sortedItems, 'totals' => $totals], JSON_UNESCAPED_SLASHES),
            SECRET_KEY
        );
    }
}

$inputCartHash = $input['cart_hash'] ?? '';
$expectedCartHash = compute_cart_hash($cartItems, $totals);
$sessionCartHash = $_SESSION['checkout_cart_hash'] ?? '';

if (empty($inputCartHash) || !hash_equals($expectedCartHash, $inputCartHash) || ($sessionCartHash && !hash_equals($sessionCartHash, $inputCartHash))) {
    echo json_encode(['success' => false, 'error' => 'Cart changed during checkout. Please refresh and try again.']);
    exit;
}

if (empty($totals['item_count'])) {
    echo json_encode(['success' => false, 'error' => 'Your cart is empty.']);
    exit;
}

if (!isset($totals[$currencyKey])) {
    echo json_encode(['success' => false, 'error' => 'Unsupported currency selected.']);
    exit;
}

$orderReference = 'JINKA-' . date('YmdHis') . '-' . random_int(100, 999);
$order = [
    'reference' => $orderReference,
    'amount' => $totals[$currencyKey]['total'],
    'currency' => $currency,
    'description' => 'Order payment for ' . $customer['name'],
    'items' => $cart->getItems(),
];

$metadata = [
    'country_code' => $countryCode,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
];

if (!empty($input['payment_options'])) {
    $metadata['payment_options'] = $input['payment_options'];
}

// Handle inline MNO payments separately
if (!empty($input['payment_method'])) {
    $metadata['payment_method'] = $input['payment_method'];
}

if (!empty($input['network'])) {
    $metadata['network'] = $input['network'];
}

if (!empty($input['phone_number'])) {
    $metadata['phone_number'] = $input['phone_number'];
}

if (!empty($input['notes'])) {
    $metadata['meta']['notes'] = trim((string) $input['notes']);
}

try {
    // Create order in database BEFORE payment initiation (for M-Pesa callbacks)
    $db = Database::getInstance()->getConnection();
    
    // Prepare cart items as JSON
    $cartItems = json_encode($cart->getItems());
    
    // Prepare billing/shipping addresses as JSON (empty for now)
    $billingAddress = json_encode([]);
    $shippingAddress = json_encode([]);
    
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number,
            customer_name,
            customer_email,
            customer_phone,
            billing_address,
            shipping_address,
            items,
            subtotal,
            tax_amount,
            total_amount,
            currency, 
            status, 
            payment_status,
            payment_method,
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $paymentMethod = $gateway;
    $subtotal = $totals[$currencyKey]['subtotal'];
    $taxAmount = $totals[$currencyKey]['tax'];
    $totalAmount = $totals[$currencyKey]['total'];
    $status = 'pending';
    $paymentStatus = 'pending';
    
    $stmt->bind_param(
        'sssssssdddssss',
        $orderReference,
        $customer['name'],
        $customer['email'],
        $customer['phone'],
        $billingAddress,
        $shippingAddress,
        $cartItems,
        $subtotal,
        $taxAmount,
        $totalAmount,
        $currency,
        $status,
        $paymentStatus,
        $paymentMethod
    );
    $stmt->execute();
    $orderId = $db->insert_id;
    
    // Add order ID to metadata
    $metadata['order_id'] = $orderId;
    
    $response = PaymentGatewayManager::initiate($gateway, $order, $customer, $metadata);
    
    // For M-Pesa, store the checkout request IDs
    if ($gateway === 'mpesa' || $gateway === 'lipa_na_mpesa') {
        if (!empty($response['checkout_request_id']) || !empty($response['merchant_request_id'])) {
            $stmt = $db->prepare("
                UPDATE orders 
                SET mpesa_checkout_request_id = ?,
                    mpesa_merchant_request_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $checkoutId = $response['checkout_request_id'] ?? '';
            $merchantId = $response['merchant_request_id'] ?? '';
            $stmt->bind_param('ssi', $checkoutId, $merchantId, $orderId);
            $stmt->execute();
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['pending_payment'] = [
        'reference' => $orderReference,
        'order_id' => $orderId,
        'gateway' => $gateway,
        'currency' => $currency,
        'total' => $order['amount'],
        'timestamp' => time(),
    ];

    echo json_encode(['success' => true, 'data' => $response]);
} catch (PaymentGatewayException $e) {
    error_log('Payment gateway error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Payment processing failure: ' . $e->getMessage());
    error_log('Error type: ' . get_class($e));
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Payment could not be initiated. Error: ' . $e->getMessage()]);
}
