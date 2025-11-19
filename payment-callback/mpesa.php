<?php
/**
 * M-Pesa STK Push Callback Handler
 * Processes callback notifications from Safaricom M-Pesa API
 * 
 * @author JINKA Plotter
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Cart.php';

$db = Database::getInstance()->getConnection();

// Get callback data from M-Pesa
$callbackData = file_get_contents('php://input');
$callback = json_decode($callbackData, true);

// Log the callback for debugging
error_log('M-Pesa Callback: ' . $callbackData);

// Respond immediately to M-Pesa
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

// Process the callback
if (!empty($callback['Body']['stkCallback'])) {
    $stkCallback = $callback['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $merchantRequestId = $stkCallback['MerchantRequestID'] ?? '';
    $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? '';
    
    // Extract callback metadata
    $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
    $mpesaData = [];
    foreach ($callbackMetadata as $item) {
        $mpesaData[$item['Name']] = $item['Value'] ?? null;
    }
    
    $mpesaReceiptNumber = $mpesaData['MpesaReceiptNumber'] ?? '';
    $transactionDate = $mpesaData['TransactionDate'] ?? '';
    $phoneNumber = $mpesaData['PhoneNumber'] ?? '';
    $amount = $mpesaData['Amount'] ?? 0;
    
    // Find order by merchant/checkout request ID
    // Note: We need to store these IDs when initiating payment
    $stmt = $db->prepare("
        SELECT id, order_number, total_amount, status, customer_email, customer_name, customer_phone 
        FROM orders 
        WHERE mpesa_checkout_request_id = ? OR mpesa_merchant_request_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $checkoutRequestId, $merchantRequestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        error_log("M-Pesa callback: Order not found for checkout request: $checkoutRequestId");
        exit;
    }
    
    // Check if order is already processed
    if ($order['status'] === 'paid' || $order['payment_status'] === 'paid') {
        error_log("M-Pesa callback: Order {$order['order_number']} already paid");
        exit;
    }
    
    // Check result code (0 = success)
    if ($resultCode == 0) {
        // Payment successful
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'confirmed',
                payment_status = 'paid',
                payment_method = 'mpesa',
                mpesa_receipt_number = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('si', $mpesaReceiptNumber, $order['id']);
        $stmt->execute();
        
        // Clear the cart session if exists
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        
        // Create delivery record automatically
        $trackingNumber = 'TRK-' . strtoupper(substr(md5($order['reference'] . time()), 0, 10));
        $estimatedDeliveryDate = date('Y-m-d', strtotime('+3 days'));
        
        $stmt = $db->prepare("
            INSERT INTO deliveries (
                order_id,
                tracking_number,
                status,
                delivery_address,
                delivery_city,
                delivery_country,
                customer_name,
                customer_phone,
                estimated_delivery_date,
                created_at,
                updated_at
            ) VALUES (?, ?, 'pending', '', '', 'Kenya', ?, ?, ?, NOW(), NOW())
        ");
        
        $deliveryStatus = 'pending';
        $stmt->bind_param(
            'issss',
            $order['id'],
            $trackingNumber,
            $order['customer_name'],
            $order['customer_phone'],
            $estimatedDeliveryDate
        );
        $stmt->execute();
        $deliveryId = $db->insert_id;
        
        // Add delivery history entry
        $stmt = $db->prepare("
            INSERT INTO delivery_history (
                delivery_id,
                status,
                notes,
                created_at
            ) VALUES (?, 'pending', 'Delivery created automatically after payment', NOW())
        ");
        $stmt->bind_param('i', $deliveryId);
        $stmt->execute();
        
        error_log("M-Pesa: Order {$order['order_number']} marked as paid. Receipt: $mpesaReceiptNumber. Delivery created: $trackingNumber");
        
        // TODO: Send email notification to customer
        
    } else {
        // Payment failed or cancelled
        $resultDesc = $stkCallback['ResultDesc'] ?? 'Payment failed';
        
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'cancelled',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $order['id']);
        $stmt->execute();
        
        error_log("M-Pesa: Order {$order['order_number']} payment failed. Code: $resultCode. Desc: $resultDesc");
    }
}

exit;
