<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

$delivery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

if (!$delivery_id) {
    header('Location: deliveries.php');
    exit;
}

// Get delivery details
$stmt = $db->prepare("
    SELECT d.*, o.order_number, o.customer_name, o.customer_email, o.total_amount
    FROM deliveries d
    LEFT JOIN orders o ON d.order_id = o.id
    WHERE d.id = ?
");
$stmt->bind_param('i', $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();

if (!$delivery) {
    header('Location: deliveries.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_details') {
        $delivery_address = Security::sanitizeInput($_POST['delivery_address']);
        $delivery_instructions = Security::sanitizeInput($_POST['delivery_instructions'] ?? '');
        $estimated_delivery_date = Security::sanitizeInput($_POST['estimated_delivery_date'] ?? '');
        $driver_name = Security::sanitizeInput($_POST['driver_name'] ?? '');
        $driver_phone = Security::sanitizeInput($_POST['driver_phone'] ?? '');
        $vehicle_number = Security::sanitizeInput($_POST['vehicle_number'] ?? '');
        
        try {
            $stmt = $db->prepare("
                UPDATE deliveries 
                SET delivery_address = ?,
                    delivery_instructions = ?,
                    estimated_delivery_date = ?,
                    driver_name = ?,
                    driver_phone = ?,
                    vehicle_number = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param(
                'ssssssi',
                $delivery_address,
                $delivery_instructions,
                $estimated_delivery_date,
                $driver_name,
                $driver_phone,
                $vehicle_number,
                $delivery_id
            );
            $stmt->execute();
            
            $message = 'Delivery details updated successfully';
            $messageType = 'success';
            
            // Refresh delivery data
            $stmt = $db->prepare("SELECT * FROM deliveries WHERE id = ?");
            $stmt->bind_param('i', $delivery_id);
            $stmt->execute();
            $delivery = array_merge($delivery, $stmt->get_result()->fetch_assoc());
            
        } catch (Exception $e) {
            $message = 'Error updating delivery: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'update_status') {
        $new_status = Security::sanitizeInput($_POST['status']);
        $location = Security::sanitizeInput($_POST['location'] ?? '');
        $notes = Security::sanitizeInput($_POST['notes'] ?? '');
        
        $valid_statuses = ['pending', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'];
        
        if (in_array($new_status, $valid_statuses)) {
            try {
                $db->begin_transaction();
                
                // Update delivery status
                $timestamp_field = $new_status . '_at';
                $stmt = $db->prepare("
                    UPDATE deliveries 
                    SET delivery_status = ?,
                        $timestamp_field = NOW(),
                        current_location = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('ssi', $new_status, $location, $delivery_id);
                $stmt->execute();
                
                // Add to history
                $historyStmt = $db->prepare("
                    INSERT INTO delivery_status_history (delivery_id, status, location, notes, updated_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $updated_by = $currentUser['username'] ?? 'Admin';
                $historyStmt->bind_param('issss', $delivery_id, $new_status, $location, $notes, $updated_by);
                $historyStmt->execute();
                
                $db->commit();
                
                $message = 'Delivery status updated successfully';
                $messageType = 'success';
                
                // Refresh delivery data
                $stmt = $db->prepare("SELECT * FROM deliveries WHERE id = ?");
                $stmt->bind_param('i', $delivery_id);
                $stmt->execute();
                $delivery = array_merge($delivery, $stmt->get_result()->fetch_assoc());
                
            } catch (Exception $e) {
                $db->rollback();
                $message = 'Error updating status: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$pageTitle = 'Edit Delivery';
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
        .edit-delivery-page {
            background: #f8fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tracking-number {
            font-family: monospace;
            opacity: 0.9;
            font-size: 1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-white {
            background: white;
            color: #667eea;
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .card-title {
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

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-assigned { background: #dbeafe; color: #1e40af; }
        .status-picked_up { background: #e0e7ff; color: #3730a3; }
        .status-in_transit { background: #dbeafe; color: #1e3a8a; }
        .status-out_for_delivery { background: #ede9fe; color: #5b21b6; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }

        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-label {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .info-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.875rem;
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

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
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
<body class="edit-delivery-page">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-content">
                        <div>
                            <h1>
                                <i class="fas fa-edit"></i>
                                Edit Delivery
                            </h1>
                            <div class="tracking-number">
                                Tracking: <?= htmlspecialchars($delivery['tracking_number']) ?>
                            </div>
                        </div>
                        <div class="header-actions">
                            <a href="delivery-details.php?id=<?= $delivery_id ?>" class="btn btn-white">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="deliveries.php" class="btn btn-white">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="content-grid">
                    <!-- Main Edit Forms -->
                    <div>
                        <!-- Update Status -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-sync-alt"></i>
                                Update Delivery Status
                            </div>

                            <div class="status-badge status-<?= $delivery['delivery_status'] ?>">
                                Current: <?= ucfirst(str_replace('_', ' ', $delivery['delivery_status'])) ?>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                
                                <div class="form-group">
                                    <label for="status" class="form-label">New Status</label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="pending">Pending</option>
                                        <option value="assigned">Assigned</option>
                                        <option value="picked_up">Picked Up</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="out_for_delivery">Out for Delivery</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="failed">Failed</option>
                                        <option value="returned">Returned</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="location" class="form-label">Current Location</label>
                                    <input type="text" name="location" id="location" class="form-input"
                                           placeholder="e.g., Mombasa Road, Nairobi"
                                           value="<?= htmlspecialchars($delivery['current_location'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="notes" class="form-label">Update Notes</label>
                                    <textarea name="notes" id="notes" class="form-textarea"
                                              placeholder="Add any notes about this status update"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                            </form>
                        </div>

                        <!-- Edit Delivery Details -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-info-circle"></i>
                                Delivery Details
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_details">

                                <div class="form-group">
                                    <label for="delivery_address" class="form-label">Delivery Address</label>
                                    <textarea name="delivery_address" id="delivery_address" class="form-textarea" required><?= htmlspecialchars($delivery['delivery_address']) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                                    <textarea name="delivery_instructions" id="delivery_instructions" class="form-textarea"><?= htmlspecialchars($delivery['delivery_instructions'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="estimated_delivery_date" class="form-label">Estimated Delivery Date</label>
                                    <input type="date" name="estimated_delivery_date" id="estimated_delivery_date" class="form-input"
                                           value="<?= $delivery['estimated_delivery_date'] ?>" min="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="card-title" style="margin-top: 2rem;">
                                    <i class="fas fa-user-tie"></i>
                                    Driver Information
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="driver_name" class="form-label">Driver Name</label>
                                        <input type="text" name="driver_name" id="driver_name" class="form-input"
                                               value="<?= htmlspecialchars($delivery['driver_name'] ?? '') ?>"
                                               placeholder="e.g., John Kamau">
                                    </div>

                                    <div class="form-group">
                                        <label for="driver_phone" class="form-label">Driver Phone</label>
                                        <input type="tel" name="driver_phone" id="driver_phone" class="form-input"
                                               value="<?= htmlspecialchars($delivery['driver_phone'] ?? '') ?>"
                                               placeholder="e.g., +254712345678">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                    <input type="text" name="vehicle_number" id="vehicle_number" class="form-input"
                                           value="<?= htmlspecialchars($delivery['vehicle_number'] ?? '') ?>"
                                           placeholder="e.g., KBX 123A">
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Info -->
                    <div>
                        <!-- Order Information -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-shopping-cart"></i>
                                Order Information
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Order Number</span>
                                    <span class="info-value">
                                        <a href="orders.php?view=<?= $delivery['order_id'] ?>" style="color: #667eea;">
                                            #<?= htmlspecialchars($delivery['order_number']) ?>
                                        </a>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Customer</span>
                                    <span class="info-value"><?= htmlspecialchars($delivery['customer_name']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($delivery['customer_email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Amount</span>
                                    <span class="info-value">₦<?= number_format($delivery['total_amount'], 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Timeline -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-clock"></i>
                                Timeline
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Created</span>
                                    <span class="info-value"><?= date('M d, Y H:i', strtotime($delivery['created_at'])) ?></span>
                                </div>
                                <?php if ($delivery['assigned_at']): ?>
                                <div class="info-item">
                                    <span class="info-label">Assigned</span>
                                    <span class="info-value"><?= date('M d, Y H:i', strtotime($delivery['assigned_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['picked_up_at']): ?>
                                <div class="info-item">
                                    <span class="info-label">Picked Up</span>
                                    <span class="info-value"><?= date('M d, Y H:i', strtotime($delivery['picked_up_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['delivered_at']): ?>
                                <div class="info-item">
                                    <span class="info-label">Delivered</span>
                                    <span class="info-value"><?= date('M d, Y H:i', strtotime($delivery['delivered_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="../track-delivery.php?tracking=<?= $delivery['tracking_number'] ?>" 
                                   class="btn btn-primary" target="_blank" style="justify-content: center;">
                                    <i class="fas fa-map-marker-alt"></i> View Customer Tracking
                                </a>
                                <a href="delivery-details.php?id=<?= $delivery_id ?>" 
                                   class="btn btn-primary" style="background: #10b981; justify-content: center;">
                                    <i class="fas fa-eye"></i> View Full Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
