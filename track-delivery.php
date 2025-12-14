<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'admin/includes/Database.php';

// Header/Footer Configuration
$site_name = site_setting('site_name', 'ProCut Solutions');
$site_logo = site_setting('site_logo', '');
$site_favicon_setting = trim(site_setting('site_favicon', ''));
$default_favicon_path = 'images/favicon.ico';
$site_favicon = '';
if ($site_favicon_setting !== '') {
    if (preg_match('#^https?://#i', $site_favicon_setting)) {
        $site_favicon = $site_favicon_setting;
    } else {
        $site_favicon = site_url($site_favicon_setting);
    }
} elseif (file_exists(__DIR__ . '/' . $default_favicon_path)) {
    $site_favicon = site_url($default_favicon_path);
}
$site_tagline = site_setting('site_tagline', 'Professional Printing Equipment');
$business_name = $site_name;
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');
$phone = site_setting('contact_phone', '+255753098911');
$phone_number = $phone;
$phone_number_ke = site_setting('contact_phone_ke', '+254716522828');
$email = site_setting('contact_email', 'support@procutsolutions.com');
$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);
$footer_logo = site_setting('footer_logo', $site_logo);
$footer_about = site_setting('footer_about', 'Professional printing equipment supplier.');
$footer_address = site_setting('footer_address', 'Kenya & Tanzania');
$footer_phone_label_tz = site_setting('footer_phone_label_tz', 'Tanzania');
$footer_phone_label_ke = site_setting('footer_phone_label_ke', 'Kenya');
$footer_hours_weekday = site_setting('footer_hours_weekday', '8:00 AM - 6:00 PM');
$footer_hours_saturday = site_setting('footer_hours_saturday', '9:00 AM - 4:00 PM');
$footer_hours_sunday = site_setting('footer_hours_sunday', 'Closed');
$footer_whatsapp_label = site_setting('footer_whatsapp_label', '24/7 Available');
$footer_copyright = site_setting('footer_copyright', '');
$facebook_url = trim(site_setting('facebook_url', ''));
$instagram_url = trim(site_setting('instagram_url', ''));
$twitter_url = trim(site_setting('twitter_url', ''));
$linkedin_url = trim(site_setting('linkedin_url', ''));

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
    <title>Track Your Delivery - <?php echo $site_name; ?></title>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/header-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/theme-variables.php?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive-global.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #ff5900;
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
            background: #ff5900;
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
            background: #ff5900;
            border-color: #ff5900;
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
            background: #ff5900;
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
<?php include 'includes/header.php'; ?>

<div class="tracking-page" style="padding: 2rem 0; background: #ff5900; min-height: calc(100vh - 200px);">
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
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

