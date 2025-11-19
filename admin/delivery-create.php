<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $delivery_address = Security::sanitizeInput($_POST['delivery_address']);
    $delivery_instructions = Security::sanitizeInput($_POST['delivery_instructions'] ?? '');
    $driver_name = Security::sanitizeInput($_POST['driver_name'] ?? '');
    $driver_phone = Security::sanitizeInput($_POST['driver_phone'] ?? '');
    $vehicle_number = Security::sanitizeInput($_POST['vehicle_number'] ?? '');
    $estimated_delivery_date = Security::sanitizeInput($_POST['estimated_delivery_date'] ?? '');
    
    // Generate tracking number
    $tracking_number = 'JINKA-DEL-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    // Check if tracking number exists
    $checkStmt = $db->prepare("SELECT id FROM deliveries WHERE tracking_number = ?");
    $checkStmt->bind_param('s', $tracking_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $tracking_number .= '-' . time();
    }
    
    try {
        $db->begin_transaction();
        
        // Determine initial status
        $initial_status = !empty($driver_name) ? 'assigned' : 'pending';
        
        // Create delivery
        $stmt = $db->prepare("
            INSERT INTO deliveries (
                order_id, tracking_number, delivery_address, delivery_instructions,
                driver_name, driver_phone, vehicle_number, estimated_delivery_date,
                delivery_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'issssssss',
            $order_id, $tracking_number, $delivery_address, $delivery_instructions,
            $driver_name, $driver_phone, $vehicle_number, $estimated_delivery_date,
            $initial_status
        );
        $stmt->execute();
        
        $delivery_id = $db->insert_id;
        
        // Add to history
        $historyStmt = $db->prepare("
            INSERT INTO delivery_status_history (delivery_id, status, notes, updated_by)
            VALUES (?, ?, ?, ?)
        ");
        $notes = $initial_status === 'assigned' ? "Delivery created and driver assigned" : "Delivery created";
        $updated_by = $currentUser['username'] ?? 'Admin';
        $historyStmt->bind_param('isss', $delivery_id, $initial_status, $notes, $updated_by);
        $historyStmt->execute();
        
        $db->commit();
        
        $message = "Delivery created successfully! Tracking number: $tracking_number";
        $messageType = 'success';
        
        // Redirect after 2 seconds
        header("refresh:2;url=deliveries.php");
        
    } catch (Exception $e) {
        $db->rollback();
        $message = 'Error creating delivery: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get recent orders for dropdown
$preselected_order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$orders = $db->query("
    SELECT o.id, o.order_number, o.customer_name, o.customer_email, o.total_amount, o.created_at
    FROM orders o
    LEFT JOIN deliveries d ON o.id = d.order_id
    WHERE d.id IS NULL
    ORDER BY o.created_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Create Delivery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .create-delivery-page {
            background: #f8fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-back {
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-label.required::after {
            content: '*';
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-help {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .order-preview {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            display: none;
        }

        .order-preview.active {
            display: block;
        }

        .order-preview-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .order-preview-label {
            color: #6b7280;
        }

        .order-preview-value {
            color: #1f2937;
            font-weight: 600;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="create-delivery-page">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-truck"></i>
                        Create New Delivery
                    </h1>
                    <a href="deliveries.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Deliveries
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Create Form -->
                <form method="POST" class="form-card" id="deliveryForm">
                    <!-- Order Selection -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-shopping-cart"></i>
                            Order Information
                        </div>

                        <div class="form-group">
                            <label for="order_id" class="form-label required">Select Order</label>
                            <select name="order_id" id="order_id" class="form-select" required onchange="showOrderPreview(this.value)">
                                <option value="">-- Select an order --</option>
                                <?php foreach ($orders as $order): ?>
                                    <option value="<?= $order['id'] ?>" 
                                            data-customer="<?= htmlspecialchars($order['customer_name']) ?>"
                                            data-email="<?= htmlspecialchars($order['customer_email']) ?>"
                                            data-amount="<?= $order['total_amount'] ?>"
                                            data-date="<?= date('M d, Y', strtotime($order['created_at'])) ?>"
                                            <?= $preselected_order_id == $order['id'] ? 'selected' : '' ?>>
                                        Order #<?= $order['order_number'] ?> - <?= htmlspecialchars($order['customer_name']) ?> (₦<?= number_format($order['total_amount'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-help">Select an order that doesn't have a delivery yet</div>
                            
                            <div class="order-preview" id="orderPreview">
                                <div class="order-preview-item">
                                    <span class="order-preview-label">Customer:</span>
                                    <span class="order-preview-value" id="preview-customer"></span>
                                </div>
                                <div class="order-preview-item">
                                    <span class="order-preview-label">Email:</span>
                                    <span class="order-preview-value" id="preview-email"></span>
                                </div>
                                <div class="order-preview-item">
                                    <span class="order-preview-label">Amount:</span>
                                    <span class="order-preview-value" id="preview-amount"></span>
                                </div>
                                <div class="order-preview-item">
                                    <span class="order-preview-label">Order Date:</span>
                                    <span class="order-preview-value" id="preview-date"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Details -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Delivery Details
                        </div>

                        <div class="form-group">
                            <label for="delivery_address" class="form-label required">Delivery Address</label>
                            <textarea name="delivery_address" id="delivery_address" class="form-textarea" required
                                      placeholder="Enter complete delivery address including street, city, postal code"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                            <textarea name="delivery_instructions" id="delivery_instructions" class="form-textarea"
                                      placeholder="Special instructions for the driver (e.g., call before delivery, gate code, etc.)"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="estimated_delivery_date" class="form-label">Estimated Delivery Date</label>
                            <input type="date" name="estimated_delivery_date" id="estimated_delivery_date" class="form-input"
                                   min="<?= date('Y-m-d') ?>">
                            <div class="form-help">Optional: Set an estimated delivery date</div>
                        </div>
                    </div>

                    <!-- Driver Assignment (Optional) -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i>
                            Driver Assignment (Optional)
                        </div>
                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem;">
                            You can assign a driver now or do it later from the deliveries list
                        </p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="driver_name" class="form-label">Driver Name</label>
                                <input type="text" name="driver_name" id="driver_name" class="form-input"
                                       placeholder="e.g., John Kamau">
                            </div>

                            <div class="form-group">
                                <label for="driver_phone" class="form-label">Driver Phone</label>
                                <input type="tel" name="driver_phone" id="driver_phone" class="form-input"
                                       placeholder="e.g., +254712345678">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" name="vehicle_number" id="vehicle_number" class="form-input"
                                   placeholder="e.g., KBX 123A">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="deliveries.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Create Delivery
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function showOrderPreview(orderId) {
            const select = document.getElementById('order_id');
            const preview = document.getElementById('orderPreview');
            
            if (!orderId) {
                preview.classList.remove('active');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            document.getElementById('preview-customer').textContent = option.dataset.customer;
            document.getElementById('preview-email').textContent = option.dataset.email;
            document.getElementById('preview-amount').textContent = '₦' + parseFloat(option.dataset.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('preview-date').textContent = option.dataset.date;
            
            preview.classList.add('active');
        }

        // Auto-show preview if order is preselected
        <?php if ($preselected_order_id > 0): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showOrderPreview(<?= $preselected_order_id ?>);
        });
        <?php endif; ?>
    </script>
</body>
</html>
