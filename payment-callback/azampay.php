<?php
/**
 * AzamPay Payment Callback Handler
 * Processes IPN/Webhook notifications from AzamPay
 * 
 * @author JINKA Plotter
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/AzamPay.php';
require_once __DIR__ . '/../includes/Cart.php';

$db = Database::getInstance()->getConnection();

// Get callback data
$input = file_get_contents('php://input');
$callbackData = json_decode($input, true);

// Log raw callback
Logger::info('AzamPay callback received', [
    'raw_input' => $input,
    'parsed_data' => $callbackData,
    'headers' => getallheaders()
]);

if (!$callbackData) {
    Logger::error('AzamPay callback: Invalid JSON data');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

// Initialize AzamPay
$azampay = new AzamPay();
$processedData = $azampay->processCallback($callbackData);

$transactionId = $processedData['transaction_id'];
$reference = $processedData['reference'];
$status = strtolower($processedData['status']);
$amount = $processedData['amount'];
$provider = $processedData['provider'];

// Find order by transaction reference
$stmt = $db->prepare("SELECT * FROM orders WHERE transaction_ref = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    Logger::error('AzamPay callback: Order not found', ['reference' => $reference]);
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

// Verify amount matches (allow small difference for currency conversion)
$expectedAmount = $order['total_amount'];
if ($order['currency'] === 'KES') {
    $expectedAmount = $expectedAmount * 4.5; // Convert KES to TZS
}

$amountDifference = abs($expectedAmount - $amount);
if ($amountDifference > 1) { // Allow 1 TZS difference
    Logger::warning('AzamPay callback: Amount mismatch', [
        'expected' => $expectedAmount,
        'received' => $amount,
        'difference' => $amountDifference
    ]);
}

try {
    $db->begin_transaction();
    
    // Update order based on payment status
    if ($status === 'success' || $status === 'successful' || $status === 'completed') {
        // Payment successful
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET payment_status = 'completed',
                order_status = 'processing',
                transaction_id = ?,
                payment_response = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $paymentResponse = json_encode($processedData['raw_data']);
        $updateStmt->bind_param('ssi', $transactionId, $paymentResponse, $order['id']);
        $updateStmt->execute();
        
        // Auto-create delivery
        try {
            $trackingNumber = 'JINKA-DEL-' . strtoupper(substr(uniqid(), -8));
            $deliveryAddress = $order['shipping_address'] ?? $order['customer_address'] ?? 
                               ($order['customer_name'] . "\n" . $order['customer_phone'] . "\n" . $order['customer_email']);
            $estimatedDate = date('Y-m-d', strtotime('+4 days'));
            
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
            $deliveryId = $db->insert_id();
            
            // Add initial status to history
            $historyStmt = $db->prepare("
                INSERT INTO delivery_status_history (delivery_id, status, notes, updated_by)
                VALUES (?, 'pending', 'Delivery automatically created after AzamPay payment confirmation', 'System')
            ");
            $historyStmt->bind_param('i', $deliveryId);
            $historyStmt->execute();
            
            Logger::info("Auto-created delivery for AzamPay order", [
                'delivery_id' => $deliveryId,
                'tracking_number' => $trackingNumber,
                'order_id' => $order['id']
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to auto-create delivery for AzamPay order', [
                'error' => $e->getMessage(),
                'order_id' => $order['id']
            ]);
        }
        
        // Clear cart if session exists
        session_start();
        if (isset($_SESSION['user_id'])) {
            $cart = new Cart($_SESSION['user_id']);
        } else {
            $cart = new Cart();
        }
        $cart->clear();
        
        Logger::info('AzamPay payment completed', [
            'order_id' => $order['id'],
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'provider' => $provider
        ]);
        
        $db->commit();
        
        // Send success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment processed successfully'
        ]);
        
    } elseif ($status === 'failed' || $status === 'declined' || $status === 'cancelled') {
        // Payment failed
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET payment_status = 'failed',
                order_status = 'cancelled',
                payment_response = ?,
                notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $paymentResponse = json_encode($processedData['raw_data']);
        $failureReason = "Payment {$status} via {$provider}";
        $updateStmt->bind_param('ssi', $paymentResponse, $failureReason, $order['id']);
        $updateStmt->execute();
        
        Logger::warning('AzamPay payment failed', [
            'order_id' => $order['id'],
            'status' => $status,
            'provider' => $provider
        ]);
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode([
            'status' => 'failed',
            'message' => 'Payment failed'
        ]);
        
    } else {
        // Payment pending or unknown status
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET payment_response = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $paymentResponse = json_encode($processedData['raw_data']);
        $updateStmt->bind_param('si', $paymentResponse, $order['id']);
        $updateStmt->execute();
        
        Logger::info('AzamPay payment status update', [
            'order_id' => $order['id'],
            'status' => $status
        ]);
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode([
            'status' => 'pending',
            'message' => 'Payment status updated'
        ]);
    }
    
} catch (Exception $e) {
    $db->rollback();
    Logger::error('AzamPay callback processing error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Callback processing failed'
    ]);
}
