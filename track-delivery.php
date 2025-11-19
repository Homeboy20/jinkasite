<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'admin/includes/Database.php';

$db = Database::getInstance()->getConnection();
$tracking_number = $_GET['tracking'] ?? '';
$delivery = null;
$history = [];
$error = '';

if ($tracking_number) {
    // Get delivery details with order info
    $stmt = $db->prepare("
        SELECT d.*, o.order_number, o.customer_name, o.customer_email, o.total_amount
        FROM deliveries d
        LEFT JOIN orders o ON d.order_id = o.id
        WHERE d.tracking_number = ?
    ");
    $stmt->bind_param('s', $tracking_number);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if ($delivery) {
        // Get delivery status history
        $historyStmt = $db->prepare("
            SELECT * FROM delivery_status_history
            WHERE delivery_id = ?
            ORDER BY created_at DESC
        ");
        $historyStmt->bind_param('i', $delivery['id']);
        $historyStmt->execute();
        $history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = 'Tracking number not found. Please check and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Delivery - JINKA Plotters</title>
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .tracking-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .tracking-header {
            background: white;
            padding: 2rem;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .tracking-header h1 {
            margin: 0 0 1rem 0;
            color: #1f2937;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Search Form */
        .tracking-search {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: monospace;
        }

        .search-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
        }

        /* Delivery Info Card */
        .delivery-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .delivery-status {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }

        /* Progress Timeline */
        .timeline-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .timeline-header {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e5e7eb;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-marker {
            position: absolute;
            left: -2.375rem;
            top: 0;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
        }

        .timeline-item.active .timeline-marker {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }

        .timeline-content {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
        }

        .timeline-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .timeline-location {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .timeline-time {
            color: #9ca3af;
            font-size: 0.75rem;
        }

        /* Driver Info */
        .driver-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .driver-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .driver-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .driver-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .driver-icon {
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .driver-details h3 {
            margin: 0;
            font-size: 1.125rem;
        }

        .driver-details p {
            margin: 0.25rem 0 0 0;
            opacity: 0.9;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .tracking-header h1 {
                font-size: 1.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <!-- Search Form -->
        <div class="tracking-search">
            <h2 style="margin: 0 0 1rem 0; color: #1f2937;">Track Your Delivery</h2>
            <form method="GET" action="" class="search-form">
                <input type="text" name="tracking" class="search-input" 
                       placeholder="Enter tracking number (e.g., JINKA-DEL-001)" 
                       value="<?= htmlspecialchars($tracking_number) ?>" required>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Track
                </button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php elseif ($delivery): ?>
            <!-- Delivery Status Card -->
            <div class="delivery-card">
                <h2 style="margin: 0 0 1rem 0; color: #1f2937;">
                    <i class="fas fa-box"></i> Delivery Details
                </h2>
                
                <div class="delivery-status status-<?= $delivery['delivery_status'] ?>">
                    <i class="fas fa-<?= $delivery['delivery_status'] === 'delivered' ? 'check-circle' : 'clock' ?>"></i>
                    <?= ucfirst(str_replace('_', ' ', $delivery['delivery_status'])) ?>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Tracking Number</div>
                        <div class="info-value" style="font-family: monospace;">
                            <?= htmlspecialchars($delivery['tracking_number']) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order Number</div>
                        <div class="info-value"><?= htmlspecialchars($delivery['order_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estimated Delivery</div>
                        <div class="info-value">
                            <?= $delivery['estimated_delivery_date'] ? date('M d, Y', strtotime($delivery['estimated_delivery_date'])) : 'TBD' ?>
                        </div>
                    </div>
                    <?php if ($delivery['delivered_at']): ?>
                        <div class="info-item">
                            <div class="info-label">Delivered On</div>
                            <div class="info-value">
                                <?= date('M d, Y h:i A', strtotime($delivery['delivered_at'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($delivery['current_location']): ?>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                        <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Current Location</div>
                        <div style="font-weight: 600; color: #1f2937;">
                            <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i>
                            <?= htmlspecialchars($delivery['current_location']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Driver Information -->
            <?php if ($delivery['driver_name']): ?>
                <div class="driver-card">
                    <div class="driver-header">
                        <i class="fas fa-user-tie"></i>
                        Your Driver
                    </div>
                    <div class="driver-info">
                        <div class="driver-item">
                            <div class="driver-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="driver-details">
                                <h3><?= htmlspecialchars($delivery['driver_name']) ?></h3>
                                <p><?= htmlspecialchars($delivery['driver_phone'] ?? 'No phone') ?></p>
                            </div>
                        </div>
                        <?php if ($delivery['vehicle_number']): ?>
                            <div class="driver-item">
                                <div class="driver-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="driver-details">
                                    <h3>Vehicle</h3>
                                    <p><?= htmlspecialchars($delivery['vehicle_number']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Delivery Timeline -->
            <div class="timeline-container">
                <div class="timeline-header">
                    <i class="fas fa-route"></i>
                    Delivery Timeline
                </div>
                <div class="timeline">
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $index => $item): ?>
                            <div class="timeline-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title"><?= ucfirst(str_replace('_', ' ', $item['status'])) ?></div>
                                    <?php if ($item['location']): ?>
                                        <div class="timeline-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($item['location']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($item['notes']): ?>
                                        <div class="timeline-location"><?= htmlspecialchars($item['notes']) ?></div>
                                    <?php endif; ?>
                                    <div class="timeline-time">
                                        <?= date('M d, Y h:i A', strtotime($item['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default timeline if no history -->
                        <?php
                        $statuses = [
                            'pending' => 'Order Received',
                            'assigned' => 'Driver Assigned',
                            'picked_up' => 'Package Picked Up',
                            'in_transit' => 'In Transit',
                            'out_for_delivery' => 'Out for Delivery',
                            'delivered' => 'Delivered'
                        ];
                        $current_status = $delivery['delivery_status'];
                        $status_order = array_keys($statuses);
                        $current_index = array_search($current_status, $status_order);
                        
                        foreach ($statuses as $status => $label):
                            $status_index = array_search($status, $status_order);
                            $is_active = $status_index <= $current_index;
                        ?>
                            <div class="timeline-item <?= $is_active ? 'active' : '' ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title"><?= $label ?></div>
                                    <?php if ($is_active && $delivery[$status . '_at']): ?>
                                        <div class="timeline-time">
                                            <?= date('M d, Y h:i A', strtotime($delivery[$status . '_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Information -->
            <?php if ($delivery['delivery_instructions']): ?>
                <div class="delivery-card">
                    <h3 style="margin: 0 0 1rem 0; color: #1f2937;">
                        <i class="fas fa-info-circle"></i> Delivery Instructions
                    </h3>
                    <p style="color: #6b7280; margin: 0;">
                        <?= nl2br(htmlspecialchars($delivery['delivery_instructions'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Back to Home -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" style="color: white; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
