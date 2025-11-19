<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

session_start();
require_once 'includes/config.php';

$db = Database::getInstance()->getConnection();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$txRef = $_GET['tx_ref'] ?? '';

if (!$orderId || !$txRef) {
    header('Location: index.php');
    exit;
}

// Get order details
$stmt = $db->prepare("
    SELECT o.*, 
           u.username, u.email,
           GROUP_CONCAT(
               CONCAT(oi.product_name, ' (₦', oi.price, ' x ', oi.quantity, ')')
               SEPARATOR ', '
           ) as items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ? AND o.transaction_ref = ?
    GROUP BY o.id
");
$stmt->bind_param('is', $orderId, $txRef);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$itemsStmt = $db->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$itemsStmt->bind_param('i', $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get delivery information if exists
$deliveryStmt = $db->prepare("
    SELECT * FROM deliveries WHERE order_id = ? ORDER BY created_at DESC LIMIT 1
");
$deliveryStmt->bind_param('i', $orderId);
$deliveryStmt->execute();
$delivery = $deliveryStmt->get_result()->fetch_assoc();

$pageTitle = 'Order Successful';
include 'includes/header.php';
?>

<style>
    .success-container {
        max-width: 800px;
        margin: 4rem auto;
        padding: 0 2rem;
    }

    .success-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        overflow: hidden;
        animation: slideUp 0.5s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        padding: 3rem 2rem;
        text-align: center;
        color: white;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        animation: scaleIn 0.6s ease;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }

    .success-icon i {
        font-size: 2.5rem;
    }

    .success-header h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-weight: 700;
    }

    .success-header p {
        margin: 0;
        font-size: 1.1rem;
        opacity: 0.95;
    }

    .success-body {
        padding: 2.5rem;
    }

    .order-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.2rem;
        color: #10b981;
        padding-top: 1.5rem;
    }

    .summary-label {
        color: #6b7280;
        font-weight: 500;
    }

    .summary-value {
        font-weight: 600;
        color: #1f2937;
    }

    .order-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .detail-item {
        padding: 1.5rem;
        background: #f9fafb;
        border-radius: 10px;
        border-left: 4px solid #10b981;
    }

    .detail-label {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-value {
        font-size: 1.1rem;
        color: #1f2937;
        font-weight: 600;
    }

    .order-items {
        margin-bottom: 2rem;
    }

    .order-items h3 {
        margin: 0 0 1.5rem 0;
        color: #1f2937;
        font-size: 1.25rem;
    }

    .item-card {
        background: #f9fafb;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .item-meta {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .item-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #10b981;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn {
        flex: 1;
        padding: 1rem 2rem;
        border-radius: 10px;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-secondary:hover {
        background: #f3f4f6;
    }

    .info-notice {
        background: #eff6ff;
        border: 1px solid #3b82f6;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
    }

    .info-notice i {
        color: #3b82f6;
        font-size: 1.5rem;
    }

    .info-content {
        flex: 1;
    }

    .info-content h4 {
        margin: 0 0 0.5rem 0;
        color: #1e40af;
        font-size: 1rem;
    }

    .info-content p {
        margin: 0;
        color: #1e3a8a;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    @media (max-width: 768px) {
        .success-container {
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .success-header {
            padding: 2rem 1.5rem;
        }

        .success-header h1 {
            font-size: 1.5rem;
        }

        .success-body {
            padding: 1.5rem;
        }

        .order-details {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="success-container">
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Payment Successful! 🎉</h1>
            <p>Thank you for your order. Your payment has been confirmed.</p>
        </div>

        <div class="success-body">
            <!-- Order Details -->
            <div class="order-details">
                <div class="detail-item">
                    <div class="detail-label">
                        <i class="fas fa-receipt"></i>
                        Order Number
                    </div>
                    <div class="detail-value">#<?= $order['id'] ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <i class="fas fa-credit-card"></i>
                        Transaction ID
                    </div>
                    <div class="detail-value"><?= htmlspecialchars($order['transaction_id']) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <i class="fas fa-calendar-alt"></i>
                        Order Date
                    </div>
                    <div class="detail-value"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <i class="fas fa-info-circle"></i>
                        Status
                    </div>
                    <div class="detail-value" style="color: #10b981;">
                        <i class="fas fa-check-circle"></i> <?= ucfirst($order['order_status']) ?>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h3><i class="fas fa-box"></i> Order Items</h3>
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-card">
                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="item-meta">
                                Quantity: <?= $item['quantity'] ?> × ₦<?= number_format($item['price'], 2) ?>
                            </div>
                        </div>
                        <div class="item-price">
                            ₦<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">₦<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <?php if ($order['shipping_cost'] > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">Shipping</span>
                    <span class="summary-value">₦<?= number_format($order['shipping_cost'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span class="summary-label">Total Paid</span>
                    <span class="summary-value">₦<?= number_format($order['total_amount'] + $order['shipping_cost'], 2) ?></span>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if ($order['shipping_address']): ?>
            <div class="order-items">
                <h3><i class="fas fa-shipping-fast"></i> Shipping Address</h3>
                <div class="item-card">
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($order['shipping_name']) ?></div>
                        <div class="item-meta">
                            <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                            <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_region']) ?><br>
                            <?= htmlspecialchars($order['shipping_postal_code']) ?><br>
                            Phone: <?= htmlspecialchars($order['phone']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Delivery Tracking -->
            <?php if ($delivery): ?>
            <div class="order-items">
                <h3><i class="fas fa-truck"></i> Delivery Tracking</h3>
                <div class="item-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="item-info" style="width: 100%;">
                        <div class="item-name" style="color: white; margin-bottom: 1rem;">
                            Track your delivery in real-time
                        </div>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.25rem;">Tracking Number</div>
                                <div style="font-family: monospace; font-size: 1.125rem; font-weight: 600;">
                                    <?= htmlspecialchars($delivery['tracking_number']) ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.25rem;">Status</div>
                                <div style="font-size: 1.125rem; font-weight: 600;">
                                    <?= ucfirst(str_replace('_', ' ', $delivery['delivery_status'])) ?>
                                </div>
                            </div>
                            <?php if ($delivery['estimated_delivery_date']): ?>
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.25rem;">Estimated Delivery</div>
                                <div style="font-size: 1.125rem; font-weight: 600;">
                                    <?= date('M d, Y', strtotime($delivery['estimated_delivery_date'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="track-delivery.php?tracking=<?= $delivery['tracking_number'] ?>" 
                           style="display: inline-block; padding: 0.75rem 1.5rem; background: white; color: #667eea; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 0.5rem;">
                            <i class="fas fa-map-marker-alt"></i> Track Delivery
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info Notice -->
            <div class="info-notice">
                <i class="fas fa-info-circle"></i>
                <div class="info-content">
                    <h4>What's Next?</h4>
                    <p>
                        We've sent a confirmation email to <strong><?= htmlspecialchars($order['email']) ?></strong>. 
                        Your order is being processed and will be shipped soon. 
                        <?php if ($delivery): ?>
                        You can track your delivery using the tracking number above.
                        <?php else: ?>
                        You will receive a tracking number once your order is shipped.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="account/orders.php" class="btn btn-primary">
                    <i class="fas fa-list-alt"></i> View My Orders
                </a>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
