<?php
/**
 * Customer Notifications Page
 * View and manage customer notifications
 */

define('JINKA_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/CustomerAuth.php';

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

// Footer Configuration
$footer_logo = site_setting('footer_logo', $site_logo);
$footer_about = site_setting('footer_about', 'Professional printing equipment supplier serving Kenya and Tanzania.');
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

// Check if customer is logged in
$auth = new CustomerAuth($conn);
if (!$auth->isLoggedIn()) {
    redirect('customer-login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$customer = $auth->getCustomerData();
$customer_id = $customer['id'];

$success_message = '';
$error_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $notification_id = intval($_POST['notification_id']);
        
        $update_sql = "UPDATE customer_notifications SET is_read = 1 WHERE id = ? AND customer_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $notification_id, $customer_id);
        $update_stmt->execute();
        $update_stmt->close();
    } elseif ($action === 'mark_all_read') {
        $update_sql = "UPDATE customer_notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('i', $customer_id);
        
        if ($update_stmt->execute()) {
            $success_message = 'All notifications marked as read';
        }
        $update_stmt->close();
    } elseif ($action === 'delete') {
        $notification_id = intval($_POST['notification_id']);
        
        $delete_sql = "DELETE FROM customer_notifications WHERE id = ? AND customer_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $notification_id, $customer_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'Notification deleted';
        }
        $delete_stmt->close();
    }
}

// Filter
$filter = isset($_GET['filter']) ? Security::sanitizeInput($_GET['filter']) : 'all';

// Build query
$where_clause = "customer_id = ?";
$params = [$customer_id];
$types = 'i';

if ($filter === 'unread') {
    $where_clause .= " AND is_read = 0";
} elseif (in_array($filter, ['order', 'payment', 'shipping', 'review', 'system', 'promotion'])) {
    $where_clause .= " AND type = ?";
    $params[] = $filter;
    $types .= 's';
}

// Get notifications
$sql = "SELECT * FROM customer_notifications WHERE {$where_clause} ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count
$unread_sql = "SELECT COUNT(*) as unread FROM customer_notifications WHERE customer_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param('i', $customer_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread'];
$unread_stmt->close();

// Notification icons and colors
$notification_types = [
    'order' => ['icon' => 'shopping-bag', 'color' => '#ff5900'],
    'payment' => ['icon' => 'credit-card', 'color' => '#28a745'],
    'shipping' => ['icon' => 'truck', 'color' => '#17a2b8'],
    'review' => ['icon' => 'star', 'color' => '#ffc107'],
    'system' => ['icon' => 'bell', 'color' => '#6c757d'],
    'promotion' => ['icon' => 'tag', 'color' => '#e83e8c']
];

$page_title = 'Notifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . $site_name); ?></title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<style>
body {
    background-color: #f8f9fa;
}

.page-wrapper {
    min-height: calc(100vh - 200px);
    padding-bottom: 60px;
}

.notifications-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.notifications-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.notifications-header-left h1 {
    margin: 0 0 5px 0;
    font-size: 2rem;
}

.notifications-header-left p {
    margin: 0;
    opacity: 0.9;
}

.unread-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.notifications-filters {
    background: white;
    padding: 20px;
    border-left: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #ddd;
    color: #666;
    transition: all 0.2s;
    background: white;
    cursor: pointer;
}

.filter-btn:hover {
    border-color: #ff5900;
    color: #ff5900;
}

.filter-btn.active {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    border-color: #ff5900;
}

.btn-mark-all {
    background: #f8f9fa;
    color: #ff5900;
    padding: 8px 16px;
    border-radius: 20px;
    border: 1px solid #ff5900;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-mark-all:hover {
    background: #ff5900;
    color: white;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.notifications-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 20px 20px;
}

.notifications-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notification-item {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 20px;
    transition: background-color 0.2s;
    position: relative;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f8f9ff;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    font-size: 1rem;
}

.notification-message {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 8px;
}

.notification-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    font-size: 0.8rem;
    color: #999;
}

.notification-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
    background: none;
    color: #666;
}

.btn-action:hover {
    background: #f0f0f0;
}

.btn-delete:hover {
    background: #f8d7da;
    color: #842029;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
}

@media (max-width: 768px) {
    .notifications-container {
        margin: 20px auto;
    }
    
    .notifications-header {
        padding: 30px 20px;
        border-radius: 15px 15px 0 0;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notifications-header-left h1 {
        font-size: 1.5rem;
    }
    
    .notifications-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-buttons {
        width: 100%;
        justify-content: center;
    }
    
    .btn-mark-all {
        width: 100%;
        justify-content: center;
    }
    
    .notification-item {
        flex-direction: column;
        gap: 15px;
    }
    
    .notification-actions {
        justify-content: flex-end;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="notifications-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="notifications-header">
        <div class="notifications-header-left">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <p><?= count($notifications) ?> notification(s)</p>
        </div>
        <?php if ($unread_count > 0): ?>
            <div class="unread-badge">
                <?= $unread_count ?> Unread
            </div>
        <?php endif; ?>
    </div>
    
    <div class="notifications-filters">
        <div class="filter-buttons">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All
            </a>
            <a href="?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Unread
            </a>
            <a href="?filter=order" class="filter-btn <?= $filter === 'order' ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i> Orders
            </a>
            <a href="?filter=shipping" class="filter-btn <?= $filter === 'shipping' ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Shipping
            </a>
            <a href="?filter=promotion" class="filter-btn <?= $filter === 'promotion' ? 'active' : '' ?>">
                <i class="fas fa-tag"></i> Promotions
            </a>
        </div>
        
        <?php if ($unread_count > 0): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn-mark-all">
                    <i class="fas fa-check-double"></i> Mark All Read
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="notifications-content">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= esc_html($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($notifications) > 0): ?>
            <ul class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $type_info = $notification_types[$notification['type']] ?? ['icon' => 'bell', 'color' => '#6c757d'];
                    $is_unread = $notification['is_read'] == 0;
                    ?>
                    <li class="notification-item <?= $is_unread ? 'unread' : '' ?>">
                        <div class="notification-icon" style="background-color: <?= $type_info['color'] ?>20; color: <?= $type_info['color'] ?>;">
                            <i class="fas fa-<?= $type_info['icon'] ?>"></i>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-title"><?= esc_html($notification['title']) ?></div>
                            <div class="notification-message"><?= esc_html($notification['message']) ?></div>
                            <div class="notification-meta">
                                <span><i class="far fa-clock"></i> <?= date('M d, Y g:i A', strtotime($notification['created_at'])) ?></span>
                                <span><i class="fas fa-tag"></i> <?= ucfirst($notification['type']) ?></span>
                            </div>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if ($is_unread): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" class="btn-action" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($notification['link']): ?>
                                <a href="<?= esc_html($notification['link']) ?>" class="btn-action" title="View">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Delete" onclick="return confirm('Delete this notification?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No Notifications</h3>
                <p>
                    <?php if ($filter !== 'all'): ?>
                        No <?= $filter ?> notifications found. <a href="customer-notifications.php">View all</a>
                    <?php else: ?>
                        You're all caught up!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    </main>
</div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

