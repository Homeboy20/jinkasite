<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('admin');
$db = Database::getInstance()->getConnection();

$delivery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$delivery_id) {
    header('Location: deliveries.php');
    exit;
}

// Get delivery details with order info
$stmt = $db->prepare("
    SELECT d.*, o.order_number, o.customer_name, o.customer_email, o.customer_phone, o.total_amount, o.created_at as order_date
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

// Get status history
$historyStmt = $db->prepare("
    SELECT * FROM delivery_status_history
    WHERE delivery_id = ?
    ORDER BY created_at DESC
");
$historyStmt->bind_param('i', $delivery_id);
$historyStmt->execute();
$history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Delivery Details';
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
        .details-page {
            background: #f8fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
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
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
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
            grid-template-columns: 1fr 400px;
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
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-assigned { background: #dbeafe; color: #1e40af; }
        .status-picked_up { background: #e0e7ff; color: #3730a3; }
        .status-in_transit { background: #dbeafe; color: #1e3a8a; }
        .status-out_for_delivery { background: #ede9fe; color: #5b21b6; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-returned { background: #fef3c7; color: #92400e; }

        .info-grid {
            display: grid;
            gap: 1.5rem;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .info-label {
            color: #6b7280;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 1rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
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
            left: -1.5rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            border: 3px solid #667eea;
            z-index: 1;
        }

        .timeline-marker.active {
            background: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .timeline-content {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .timeline-status {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .timeline-location, .timeline-notes {
            font-size: 0.875rem;
            color: #4b5563;
            margin-top: 0.5rem;
        }

        .timeline-updated-by {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .driver-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
        }

        .driver-card-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .driver-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .driver-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .driver-icon {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .address-box {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            justify-content: center;
        }

        .btn-success {
            background: #10b981;
            color: white;
            justify-content: center;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media print {
            .header-actions, .action-buttons, .admin-sidebar {
                display: none !important;
            }
            .page-header {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .info-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="details-page">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-content">
                        <div>
                            <h1>
                                <i class="fas fa-box"></i>
                                Delivery Details
                            </h1>
                            <div class="tracking-number">
                                Tracking: <?= htmlspecialchars($delivery['tracking_number']) ?>
                                <button class="copy-btn" onclick="copyTracking()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button onclick="window.print()" class="btn btn-white">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="delivery-edit.php?id=<?= $delivery_id ?>" class="btn btn-white">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="deliveries.php" class="btn btn-white">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="content-grid">
                    <!-- Main Content -->
                    <div>
                        <!-- Delivery Status -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-info-circle"></i>
                                Delivery Status
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <span class="status-badge status-<?= $delivery['delivery_status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $delivery['delivery_status'])) ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <div class="info-item">
                                    <div class="info-label">Created Date</div>
                                    <div class="info-value"><?= date('M d, Y H:i', strtotime($delivery['created_at'])) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Estimated Delivery</div>
                                    <div class="info-value">
                                        <?= $delivery['estimated_delivery_date'] ? date('M d, Y', strtotime($delivery['estimated_delivery_date'])) : 'Not set' ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($delivery['current_location']): ?>
                            <div class="info-item" style="margin-top: 1.5rem;">
                                <div class="info-label">
                                    <i class="fas fa-map-marker-alt"></i> Current Location
                                </div>
                                <div class="info-value"><?= htmlspecialchars($delivery['current_location']) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($delivery['latitude'] && $delivery['longitude']): ?>
                            <div class="info-item" style="margin-top: 1rem;">
                                <div class="info-label">
                                    <i class="fas fa-map"></i> GPS Coordinates
                                </div>
                                <div class="info-value">
                                    <?= $delivery['latitude'] ?>, <?= $delivery['longitude'] ?>
                                    <a href="https://www.google.com/maps?q=<?= $delivery['latitude'] ?>,<?= $delivery['longitude'] ?>" 
                                       target="_blank" style="color: #667eea; margin-left: 0.5rem;">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Delivery Address -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-map-marked-alt"></i>
                                Delivery Address
                            </div>

                            <div class="address-box">
                                <?= nl2br(htmlspecialchars($delivery['delivery_address'])) ?>
                            </div>

                            <?php if ($delivery['delivery_instructions']): ?>
                            <div style="margin-top: 1.5rem;">
                                <div class="info-label">Delivery Instructions</div>
                                <div class="address-box" style="margin-top: 0.5rem;">
                                    <?= nl2br(htmlspecialchars($delivery['delivery_instructions'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Information -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-shopping-cart"></i>
                                Order Information
                            </div>

                            <div class="info-row">
                                <div class="info-item">
                                    <div class="info-label">Order Number</div>
                                    <div class="info-value">
                                        <a href="orders.php?view=<?= $delivery['order_id'] ?>" style="color: #667eea;">
                                            #<?= htmlspecialchars($delivery['order_number']) ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Order Date</div>
                                    <div class="info-value"><?= date('M d, Y', strtotime($delivery['order_date'])) ?></div>
                                </div>
                            </div>

                            <div class="info-row" style="margin-top: 1.5rem;">
                                <div class="info-item">
                                    <div class="info-label">Customer Name</div>
                                    <div class="info-value"><?= htmlspecialchars($delivery['customer_name']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Order Amount</div>
                                    <div class="info-value">₦<?= number_format($delivery['total_amount'], 2) ?></div>
                                </div>
                            </div>

                            <div class="info-row" style="margin-top: 1.5rem;">
                                <div class="info-item">
                                    <div class="info-label">Customer Email</div>
                                    <div class="info-value"><?= htmlspecialchars($delivery['customer_email']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Customer Phone</div>
                                    <div class="info-value"><?= htmlspecialchars($delivery['customer_phone'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status History -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-history"></i>
                                Status History
                            </div>

                            <?php if (!empty($history)): ?>
                            <div class="timeline">
                                <?php foreach ($history as $index => $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?= $index === 0 ? 'active' : '' ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-status">
                                            <?= ucfirst(str_replace('_', ' ', $entry['status'])) ?>
                                        </div>
                                        <div class="timeline-time">
                                            <i class="far fa-clock"></i>
                                            <?= date('M d, Y H:i:s', strtotime($entry['created_at'])) ?>
                                        </div>
                                        <?php if ($entry['location']): ?>
                                        <div class="timeline-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($entry['location']) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($entry['notes']): ?>
                                        <div class="timeline-notes">
                                            <i class="fas fa-sticky-note"></i>
                                            <?= htmlspecialchars($entry['notes']) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($entry['updated_by']): ?>
                                        <div class="timeline-updated-by">
                                            Updated by: <?= htmlspecialchars($entry['updated_by']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 2rem;">
                                No status history available
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div>
                        <!-- Driver Information -->
                        <?php if ($delivery['driver_name']): ?>
                        <div class="driver-card">
                            <div class="driver-card-title">
                                <i class="fas fa-user-tie"></i>
                                Driver Information
                            </div>
                            <div class="driver-info">
                                <div class="driver-info-item">
                                    <div class="driver-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.75rem; opacity: 0.8;">Driver Name</div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($delivery['driver_name']) ?></div>
                                    </div>
                                </div>
                                <?php if ($delivery['driver_phone']): ?>
                                <div class="driver-info-item">
                                    <div class="driver-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.75rem; opacity: 0.8;">Phone</div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($delivery['driver_phone']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['vehicle_number']): ?>
                                <div class="driver-info-item">
                                    <div class="driver-icon">
                                        <i class="fas fa-car"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.75rem; opacity: 0.8;">Vehicle</div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($delivery['vehicle_number']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </div>

                            <div class="action-buttons">
                                <a href="delivery-edit.php?id=<?= $delivery_id ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Delivery
                                </a>
                                <a href="../track-delivery.php?tracking=<?= $delivery['tracking_number'] ?>" 
                                   class="btn btn-success" target="_blank">
                                    <i class="fas fa-map-marker-alt"></i> View Customer Tracking
                                </a>
                                <a href="orders.php?view=<?= $delivery['order_id'] ?>" class="btn btn-info">
                                    <i class="fas fa-shopping-cart"></i> View Order Details
                                </a>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="card">
                            <div class="card-title">
                                <i class="fas fa-clock"></i>
                                Timestamps
                            </div>

                            <div class="info-grid">
                                <?php if ($delivery['assigned_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Assigned</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['assigned_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['picked_up_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Picked Up</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['picked_up_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['in_transit_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">In Transit</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['in_transit_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['out_for_delivery_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Out for Delivery</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['out_for_delivery_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['delivered_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Delivered</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['delivered_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($delivery['failed_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Failed</div>
                                    <div class="info-value"><?= date('M d, H:i', strtotime($delivery['failed_at'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function copyTracking() {
            const tracking = "<?= $delivery['tracking_number'] ?>";
            navigator.clipboard.writeText(tracking).then(() => {
                const btn = event.target.closest('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
</body>
</html>
