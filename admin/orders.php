<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Require authentication
$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = Security::sanitizeInput($_POST['status']);
        $payment_status = Security::sanitizeInput($_POST['payment_status']);
        $notes = Security::sanitizeInput($_POST['notes']);
        
        $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('sssi', $status, $payment_status, $notes, $id);
        
        if ($stmt->execute()) {
            $message = 'Order updated successfully!';
            $messageType = 'success';
            
            // Log the change
            Logger::info('Order status updated', [
                'order_id' => $id,
                'new_status' => $status,
                'new_payment_status' => $payment_status,
                'admin_id' => $currentUser['id']
            ]);
        } else {
            $message = 'Error updating order: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        // Only admins can delete
        if (!$auth->hasRole('admin')) {
            $message = 'You do not have permission to delete orders';
            $messageType = 'error';
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $message = 'Order deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting order: ' . $db->error;
                $messageType = 'error';
            }
        }
    }
}

// Get orders with filters and pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = Security::sanitizeInput($_GET['search'] ?? '');
$status_filter = Security::sanitizeInput($_GET['status'] ?? '');
$payment_filter = Security::sanitizeInput($_GET['payment'] ?? '');
$date_from = Security::sanitizeInput($_GET['date_from'] ?? '');
$date_to = Security::sanitizeInput($_GET['date_to'] ?? '');

$where_conditions = ['1=1'];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($payment_filter) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_filter;
    $types .= 's';
}

if ($date_from) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM orders o WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders
$sql = "SELECT o.*, c.first_name, c.last_name, c.business_name,
        d.tracking_number, d.delivery_status
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN deliveries d ON o.id = d.order_id
        WHERE $where_clause 
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$all_types = $types . 'ii';

if ($all_params) {
    $stmt->bind_param($all_types, ...$all_params);
}
$stmt->execute();
$orders = $stmt->get_result();

// Get order statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
        AVG(total_amount) as average_order_value
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stats_result = $db->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// Get order for viewing/editing if ID is provided
$viewing_order = null;
$viewing_delivery = null;
if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    $stmt = $db->prepare("
        SELECT o.*, c.first_name, c.last_name, c.business_name, c.email as customer_db_email, c.phone as customer_db_phone
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param('i', $view_id);
    $stmt->execute();
    $viewing_order = $stmt->get_result()->fetch_assoc();
    
    if ($viewing_order) {
        $viewing_order['items'] = $viewing_order['items'] ? json_decode($viewing_order['items'], true) : [];
        $viewing_order['billing_address'] = $viewing_order['billing_address'] ? json_decode($viewing_order['billing_address'], true) : [];
        $viewing_order['shipping_address'] = $viewing_order['shipping_address'] ? json_decode($viewing_order['shipping_address'], true) : [];
        
        // Get delivery information for this order
        $deliveryStmt = $db->prepare("SELECT * FROM deliveries WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
        $deliveryStmt->bind_param('i', $view_id);
        $deliveryStmt->execute();
        $viewing_delivery = $deliveryStmt->get_result()->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-content">
                    <h1>Order Management</h1>
                    <div class="header-actions">
                        <span class="user-info">Welcome, <?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Order Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= number_format($stats['total_orders'] ?? 0) ?></h3>
                        <p>Total Orders (30 days)</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['pending_orders'] ?? 0) ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['processing_orders'] ?? 0) ?></h3>
                        <p>Processing</p>
                    </div>
                    <div class="stat-card">
                        <h3>KES <?= number_format($stats['total_revenue'] ?? 0) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-card">
                        <h3>KES <?= number_format($stats['average_order_value'] ?? 0) ?></h3>
                        <p>Average Order Value</p>
                    </div>
                </div>

                <?php if ($viewing_order): ?>
                    <!-- Order Details Modal -->
                    <div class="modal-overlay" onclick="closeOrderModal()">
                        <div class="modal-content order-modal" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <h3>Order #<?= htmlspecialchars($viewing_order['order_number']) ?></h3>
                                <button class="modal-close" onclick="closeOrderModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="order-details-grid">
                                    <!-- Customer Information -->
                                    <div class="order-section">
                                        <h4>Customer Information</h4>
                                        <div class="info-grid">
                                            <div>
                                                <strong>Name:</strong> <?= htmlspecialchars($viewing_order['customer_name']) ?>
                                            </div>
                                            <div>
                                                <strong>Email:</strong> <?= htmlspecialchars($viewing_order['customer_email']) ?>
                                            </div>
                                            <div>
                                                <strong>Phone:</strong> <?= htmlspecialchars($viewing_order['customer_phone'] ?? 'N/A') ?>
                                            </div>
                                            <?php if ($viewing_order['business_name']): ?>
                                                <div>
                                                    <strong>Business:</strong> <?= htmlspecialchars($viewing_order['business_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Order Information -->
                                    <div class="order-section">
                                        <h4>Order Information</h4>
                                        <div class="info-grid">
                                            <div>
                                                <strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($viewing_order['created_at'])) ?>
                                            </div>
                                            <div>
                                                <strong>Status:</strong> 
                                                <span class="badge badge-<?= $viewing_order['status'] ?>"><?= ucfirst($viewing_order['status']) ?></span>
                                            </div>
                                            <div>
                                                <strong>Payment:</strong> 
                                                <span class="badge badge-<?= $viewing_order['payment_status'] ?>"><?= ucfirst($viewing_order['payment_status']) ?></span>
                                            </div>
                                            <div>
                                                <strong>Currency:</strong> <?= $viewing_order['currency'] ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delivery Information -->
                                    <?php if ($viewing_delivery): ?>
                                    <div class="order-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                                        <h4 style="color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                            </svg>
                                            Delivery Information
                                        </h4>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                            <div>
                                                <div style="opacity: 0.8; font-size: 0.875rem; margin-bottom: 0.25rem;">Tracking Number</div>
                                                <div style="font-weight: 600; font-family: monospace;"><?= htmlspecialchars($viewing_delivery['tracking_number']) ?></div>
                                            </div>
                                            <div>
                                                <div style="opacity: 0.8; font-size: 0.875rem; margin-bottom: 0.25rem;">Status</div>
                                                <div style="font-weight: 600;"><?= ucfirst(str_replace('_', ' ', $viewing_delivery['delivery_status'])) ?></div>
                                            </div>
                                            <div>
                                                <div style="opacity: 0.8; font-size: 0.875rem; margin-bottom: 0.25rem;">Estimated Delivery</div>
                                                <div style="font-weight: 600;">
                                                    <?= $viewing_delivery['estimated_delivery_date'] ? date('M d, Y', strtotime($viewing_delivery['estimated_delivery_date'])) : 'Not set' ?>
                                                </div>
                                            </div>
                                            <?php if ($viewing_delivery['driver_name']): ?>
                                            <div>
                                                <div style="opacity: 0.8; font-size: 0.875rem; margin-bottom: 0.25rem;">Driver</div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($viewing_delivery['driver_name']) ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                                            <a href="delivery-details.php?id=<?= $viewing_delivery['id'] ?>" 
                                               style="flex: 1; background: rgba(255,255,255,0.2); color: white; padding: 0.75rem; border-radius: 6px; text-align: center; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                                                View Details
                                            </a>
                                            <a href="delivery-edit.php?id=<?= $viewing_delivery['id'] ?>" 
                                               style="flex: 1; background: rgba(255,255,255,0.2); color: white; padding: 0.75rem; border-radius: 6px; text-align: center; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                                                Edit Delivery
                                            </a>
                                            <a href="../track-delivery.php?tracking=<?= $viewing_delivery['tracking_number'] ?>" 
                                               target="_blank"
                                               style="flex: 1; background: rgba(255,255,255,0.2); color: white; padding: 0.75rem; border-radius: 6px; text-align: center; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                                                Track
                                            </a>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="order-section" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 2px dashed #d1d5db;">
                                        <h4 style="margin-bottom: 1rem; color: #6b7280;">No Delivery Created</h4>
                                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                                            This order doesn't have a delivery record yet. Create one to start tracking.
                                        </p>
                                        <a href="delivery-create.php?order_id=<?= $viewing_order['id'] ?>" 
                                           style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 600;">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="vertical-align: middle; margin-right: 0.5rem;">
                                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                            </svg>
                                            Create Delivery
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Order Items -->
                                <div class="order-section">
                                    <h4>Order Items</h4>
                                    <table class="order-items-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewing_order['items'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['name'] ?? 'Unknown Product') ?></td>
                                                    <td><?= (int)($item['quantity'] ?? 1) ?></td>
                                                    <td><?= $viewing_order['currency'] ?> <?= number_format($item['price'] ?? 0, 2) ?></td>
                                                    <td><?= $viewing_order['currency'] ?> <?= number_format(($item['quantity'] ?? 1) * ($item['price'] ?? 0), 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3"><strong>Subtotal:</strong></td>
                                                <td><strong><?= $viewing_order['currency'] ?> <?= number_format($viewing_order['subtotal'], 2) ?></strong></td>
                                            </tr>
                                            <?php if ($viewing_order['tax_amount'] > 0): ?>
                                                <tr>
                                                    <td colspan="3">Tax:</td>
                                                    <td><?= $viewing_order['currency'] ?> <?= number_format($viewing_order['tax_amount'], 2) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php if ($viewing_order['shipping_cost'] > 0): ?>
                                                <tr>
                                                    <td colspan="3">Shipping:</td>
                                                    <td><?= $viewing_order['currency'] ?> <?= number_format($viewing_order['shipping_cost'], 2) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr class="total-row">
                                                <td colspan="3"><strong>Total:</strong></td>
                                                <td><strong><?= $viewing_order['currency'] ?> <?= number_format($viewing_order['total_amount'], 2) ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Update Order Form -->
                                <div class="order-section">
                                    <h4>Update Order</h4>
                                    <form method="POST" class="order-update-form">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= $viewing_order['id'] ?>">
                                        
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label for="status">Order Status</label>
                                                <select id="status" name="status">
                                                    <option value="pending" <?= $viewing_order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $viewing_order['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="processing" <?= $viewing_order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="shipped" <?= $viewing_order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="delivered" <?= $viewing_order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $viewing_order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="payment_status">Payment Status</label>
                                                <select id="payment_status" name="payment_status">
                                                    <option value="pending" <?= $viewing_order['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="paid" <?= $viewing_order['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                                    <option value="failed" <?= $viewing_order['payment_status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
                                                    <option value="refunded" <?= $viewing_order['payment_status'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($viewing_order['notes'] ?? '') ?></textarea>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">Update Order</button>
                                            <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Close</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Orders List -->
                <div class="card">
                    <div class="card-header">
                        <h3>Orders (<?= $total_orders ?> total)</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="filters-form">
                            <div class="filters-grid">
                                <input type="text" name="search" placeholder="Search orders, customers..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                
                                <select name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>

                                <select name="payment">
                                    <option value="">All Payments</option>
                                    <option value="pending" <?= $payment_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="paid" <?= $payment_filter == 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="failed" <?= $payment_filter == 'failed' ? 'selected' : '' ?>>Failed</option>
                                    <option value="refunded" <?= $payment_filter == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                </select>

                                <input type="date" name="date_from" value="<?= $date_from ?>" placeholder="From Date">
                                <input type="date" name="date_to" value="<?= $date_to ?>" placeholder="To Date">

                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="orders.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>

                        <!-- Orders Table -->
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Delivery</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($orders->num_rows > 0): ?>
                                        <?php while ($order = $orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="customer-info">
                                                        <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                                        <small><?= htmlspecialchars($order['customer_email']) ?></small>
                                                        <?php if ($order['business_name']): ?>
                                                            <small><?= htmlspecialchars($order['business_name']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <strong><?= $order['currency'] ?> <?= number_format($order['total_amount'], 0) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $order['status'] ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $order['payment_status'] ?>">
                                                        <?= ucfirst($order['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($order['tracking_number']): ?>
                                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                            <small style="font-family: monospace; font-weight: 600; color: #667eea;">
                                                                <?= htmlspecialchars($order['tracking_number']) ?>
                                                            </small>
                                                            <span class="badge badge-<?= $order['delivery_status'] ?>" style="font-size: 0.75rem;">
                                                                <?= ucfirst(str_replace('_', ' ', $order['delivery_status'])) ?>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color: #9ca3af; font-size: 0.875rem;">No delivery</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="orders.php?view=<?= $order['id'] ?>" 
                                                           class="btn btn-sm btn-info">View</a>
                                                        <?php if ($auth->hasRole('admin')): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this order?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No orders found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php
                                $query_params = http_build_query(array_filter([
                                    'search' => $search,
                                    'status' => $status_filter,
                                    'payment' => $payment_filter,
                                    'date_from' => $date_from,
                                    'date_to' => $date_to
                                ]));
                                $query_string = $query_params ? '&' . $query_params : '';
                                ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="orders.php?page=<?= $i ?><?= $query_string ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function closeOrderModal() {
            window.location.href = 'orders.php';
        }

        // Auto-refresh order status
        setInterval(function() {
            // Only refresh if not in modal view
            if (!document.querySelector('.modal-overlay')) {
                window.location.reload();
            }
        }, 300000); // Refresh every 5 minutes
    </script>
</body>
</html>