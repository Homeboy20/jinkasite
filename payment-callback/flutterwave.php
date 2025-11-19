<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Cart.php';

$db = Database::getInstance()->getConnection();

// Get parameters from URL
$status = $_GET['status'] ?? '';
$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';

// Validate required parameters
if (empty($status) || empty($tx_ref) || empty($transaction_id)) {
    header('Location: ../checkout.php?error=invalid_callback');
    exit;
}

// Verify transaction with Flutterwave API
$secretKey = FLUTTERWAVE_SECRET_KEY;
$verifyUrl = "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Flutterwave verification failed: HTTP $httpCode - $response");
    header('Location: ../checkout.php?error=verification_failed');
    exit;
}

$result = json_decode($response, true);

// Check if verification was successful
if ($result['status'] !== 'success') {
    error_log("Flutterwave verification unsuccessful: " . json_encode($result));
    header('Location: ../checkout.php?error=verification_unsuccessful');
    exit;
}

$data = $result['data'];

// Verify transaction details
if ($data['status'] !== 'successful') {
    header('Location: ../checkout.php?error=payment_not_successful&status=' . urlencode($data['status']));
    exit;
}

if ($data['tx_ref'] !== $tx_ref) {
    error_log("Transaction reference mismatch: expected $tx_ref, got {$data['tx_ref']}");
    header('Location: ../checkout.php?error=reference_mismatch');
    exit;
}

// Extract order details from tx_ref (format: JINKA-YYYYMMDDHHMMSS-XXX)
$orderIdFromRef = substr($tx_ref, strrpos($tx_ref, '-') + 1);

// Get order from database
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND transaction_ref = ?");
$stmt->bind_param('is', $orderIdFromRef, $tx_ref);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    error_log("Order not found for tx_ref: $tx_ref");
    header('Location: ../checkout.php?error=order_not_found');
    exit;
}

// Verify amount matches
$expectedAmount = floatval($order['total_amount']);
$paidAmount = floatval($data['amount']);
$currency = $data['currency'];

// Allow small floating point differences (0.01)
if (abs($expectedAmount - $paidAmount) > 0.01) {
    error_log("Amount mismatch: expected $expectedAmount, got $paidAmount");
    header('Location: ../checkout.php?error=amount_mismatch');
    exit;
}

// Update order status
$updateStmt = $db->prepare("
    UPDATE orders 
    SET payment_status = 'completed',
        order_status = 'processing',
        transaction_id = ?,
        payment_response = ?,
        updated_at = NOW()
    WHERE id = ?
");

$paymentResponse = json_encode($data);
$updateStmt->bind_param('ssi', $transaction_id, $paymentResponse, $order['id']);

if (!$updateStmt->execute()) {
    error_log("Failed to update order: " . $db->error);
    header('Location: ../checkout.php?error=update_failed');
    exit;
}

// Auto-create delivery for this order
try {
    // Generate unique tracking number
    $trackingNumber = 'JINKA-DEL-' . strtoupper(substr(uniqid(), -8));
    
    // Use shipping address if available, otherwise use billing/contact info
    $deliveryAddress = $order['shipping_address'] ?? $order['customer_address'] ?? 
                       ($order['customer_name'] . "\n" . ($order['customer_phone'] ?? '') . "\n" . ($order['customer_email'] ?? ''));
    
    // Calculate estimated delivery date (3-5 business days)
    $estimatedDate = date('Y-m-d', strtotime('+4 days'));
    
    // Insert delivery record
    $deliveryStmt = $db->prepare("
        INSERT INTO deliveries (
            order_id, tracking_number, delivery_address, 
            estimated_delivery_date, delivery_status, created_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $deliveryStmt->bind_param('isss', 
        $order['id'], 
        $trackingNumber, 
        $deliveryAddress, 
        $estimatedDate
    );
    $deliveryStmt->execute();
    $deliveryId = $db->insert_id;
    
    // Add initial status to history
    $historyStmt = $db->prepare("
        INSERT INTO delivery_status_history (delivery_id, status, notes, updated_by)
        VALUES (?, 'pending', 'Delivery automatically created after payment confirmation', 'System')
    ");
    $historyStmt->bind_param('i', $deliveryId);
    $historyStmt->execute();
    
    error_log("Auto-created delivery #$deliveryId with tracking: $trackingNumber for order #" . $order['id']);
} catch (Exception $e) {
    error_log("Failed to auto-create delivery: " . $e->getMessage());
    // Don't fail the payment if delivery creation fails
}

// Clear cart
if (isset($_SESSION['user_id'])) {
    $cart = new Cart($_SESSION['user_id']);
} else {
    $cart = new Cart();
}
$cart->clear();

// Send confirmation email (optional - implement later)
// sendOrderConfirmationEmail($order, $data);

// Redirect to success page
header('Location: ../order-success.php?order_id=' . $order['id'] . '&tx_ref=' . urlencode($tx_ref));
exit;
