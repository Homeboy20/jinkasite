<?php
/**
 * AzamPay Payment Processor
 * Initiates payment with AzamPay gateway
 * 
 * @author JINKA Plotter
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/AzamPay.php';
require_once __DIR__ . '/../includes/Cart.php';
require_once __DIR__ . '/../includes/Security.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

// Extract payment details
$orderData = $data['orderData'] ?? [];
$paymentMethod = $data['payment_method'] ?? 'azampay';
$provider = $data['provider'] ?? 'Tigo'; // Tigo, Airtel, Halopesa, Mpesa
$customerPhone = $data['customer_phone'] ?? '';

// Validate required fields
if (empty($orderData)) {
    echo json_encode(['success' => false, 'error' => 'Order data is required']);
    exit;
}

if (empty($customerPhone)) {
    echo json_encode(['success' => false, 'error' => 'Phone number is required for mobile money payment']);
    exit;
}

// Format phone number
$formattedPhone = AzamPay::formatPhoneNumber($customerPhone);

// Get cart totals
$cart = new Cart();
$totals = $cart->getTotals();
$items = $cart->getItems();

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// Calculate total in TZS
$currency = 'TZS';
$totalAmount = $totals['total'];

// If cart is in KES, convert to TZS (approximate rate: 1 KES = 4.5 TZS)
if ($totals['currency'] === 'KES') {
    $totalAmount = $totalAmount * 4.5; // You should use a real exchange rate API
    Logger::info('Converting KES to TZS', [
        'original_amount' => $totals['total'],
        'converted_amount' => $totalAmount
    ]);
}

// Generate transaction reference
$txRef = 'JINKA-' . date('YmdHis') . '-' . mt_rand(100, 999);

try {
    // Create order in database
    $orderNumber = generateOrderNumber();
    $customerId = $_SESSION['user_id'] ?? null;
    
    // Prepare order data
    $customerName = Security::sanitizeInput($orderData['billingInfo']['fullName']);
    $customerEmail = Security::sanitizeInput($orderData['billingInfo']['email']);
    $customerPhone = Security::sanitizeInput($orderData['billingInfo']['phone']);
    $billingAddress = json_encode($orderData['billingInfo']);
    $shippingAddress = json_encode($orderData['shippingInfo'] ?? $orderData['billingInfo']);
    $itemsJson = json_encode($items);
    
    $subtotal = $totals['subtotal'];
    $taxAmount = $totals['tax'];
    $shippingCost = $totals['shipping'];
    $total = $totals['total'];
    
    // Insert order
    $stmt = $db->prepare("
        INSERT INTO orders (
            customer_id, order_number, customer_name, customer_email, customer_phone,
            billing_address, shipping_address, items, subtotal, tax_amount, shipping_cost,
            total_amount, currency, payment_method, payment_status, order_status,
            transaction_ref, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())
    ");
    
    $paymentStatusPending = 'pending';
    $stmt->bind_param(
        'isssssssddddsss',
        $customerId,
        $orderNumber,
        $customerName,
        $customerEmail,
        $customerPhone,
        $billingAddress,
        $shippingAddress,
        $itemsJson,
        $subtotal,
        $taxAmount,
        $shippingCost,
        $total,
        $currency,
        $paymentMethod,
        $txRef
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order: ' . $db->error);
    }
    
    $orderId = $db->insert_id;
    
    // Initialize AzamPay
    $azampay = new AzamPay();
    
    if (!$azampay->isConfigured()) {
        throw new Exception('AzamPay is not properly configured');
    }
    
    // Prepare payment parameters
    $paymentParams = [
        'amount' => $totalAmount,
        'reference' => $txRef,
        'provider' => $provider,
        'description' => 'Order #' . $orderNumber . ' - JINKA Plotter',
        'order_id' => $orderId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $formattedPhone
    ];
    
    // Initiate payment
    $result = $azampay->initiateMobilePayment($paymentParams);
    
    if (!$result['success']) {
        // Update order status to failed
        $db->query("UPDATE orders SET payment_status = 'failed', notes = '{$result['error']}' WHERE id = $orderId");
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Payment initiation failed'
        ]);
        exit;
    }
    
    // Log transaction
    Logger::info('AzamPay payment initiated', [
        'order_id' => $orderId,
        'reference' => $txRef,
        'amount' => $totalAmount,
        'provider' => $provider,
        'transaction_id' => $result['transaction_id'] ?? null
    ]);
    
    // Return success with instructions
    echo json_encode([
        'success' => true,
        'transaction_id' => $result['transaction_id'] ?? null,
        'reference' => $txRef,
        'order_id' => $orderId,
        'message' => 'Payment request sent successfully',
        'instructions' => [
            'title' => 'Complete Payment on Your Phone',
            'steps' => [
                "1. Check your phone for a payment prompt from {$provider}",
                "2. Enter your PIN to authorize the payment",
                "3. You will receive a confirmation message",
                "4. Return to this page to see your order confirmation"
            ],
            'provider' => $provider,
            'amount' => number_format($totalAmount, 2) . ' TZS',
            'reference' => $txRef
        ],
        'redirect_url' => SITE_URL . '/order-success.php?order_id=' . $orderId . '&tx_ref=' . urlencode($txRef)
    ]);
    
} catch (Exception $e) {
    Logger::error('AzamPay payment error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Payment processing failed: ' . $e->getMessage()
    ]);
}
