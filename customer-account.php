<?php
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

$auth = new CustomerAuth($conn);
$auth->requireLogin();

$customer = $auth->getCustomerData();
$customer_id = $auth->getCustomerId();

// Get customer statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM customer_wishlists WHERE customer_id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$wishlist_count = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM customer_addresses WHERE customer_id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$addresses_count = $stmt->get_result()->fetch_assoc()['total'];

// Get recent orders
$stmt = $conn->prepare("
    SELECT id, order_number, total_amount, currency, status, created_at 
    FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

$page_title = 'My Account | ' . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php if ($site_favicon): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/header-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/theme-variables.php">
    <link rel="stylesheet" href="css/responsive-global.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .account-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        
        .account-header {
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }
        
        .account-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .account-header p {
            opacity: 0.9;
            font-size: 1.125rem;
        }
        
        .account-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .account-sidebar {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .account-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .account-nav li {
            margin-bottom: 0.5rem;
        }
        
        .account-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .account-nav a:hover,
        .account-nav a.active {
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
        }
        
        .account-nav svg {
            width: 20px;
            height: 20px;
        }
        
        .account-main {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            text-align: left;
            padding: 0.875rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .orders-table td {
            padding: 1rem 0.875rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .orders-table tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-processing {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-shipped {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-view {
            padding: 0.5rem 1rem;
            background: #ff5900;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .account-grid {
                grid-template-columns: 1fr;
            }
            
            .account-sidebar {
                position: relative;
                top: 0;
            }
        }
        
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                font-size: 0.875rem;
            }
            
            .orders-table th,
            .orders-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="account-container">
        <div class="account-header">
            <h1>Welcome back, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>
            <p>Manage your account, orders, and preferences</p>
        </div>
        
        <div class="account-grid">
            <aside class="account-sidebar">
                <nav>
                    <ul class="account-nav">
                        <li>
                            <a href="customer-account.php" class="active">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="customer-orders.php">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                                My Orders
                            </a>
                        </li>
                        <li>
                            <a href="customer-wishlist.php">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Wishlist
                            </a>
                        </li>
                        <li>
                            <a href="customer-addresses.php">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                                Addresses
                            </a>
                        </li>
                        <li>
                            <a href="customer-profile.php">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                Profile Settings
                            </a>
                        </li>
                        <li>
                            <a href="customer-logout.php">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                </svg>
                                Logout
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
            
            <main class="account-main">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="stat-value"><?php echo $total_orders; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Wishlist Items</h3>
                        <div class="stat-value"><?php echo $wishlist_count; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Saved Addresses</h3>
                        <div class="stat-value"><?php echo $addresses_count; ?></div>
                    </div>
                </div>
                
                <h2 class="section-title">Recent Orders</h2>
                
                <?php if ($recent_orders->num_rows > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['currency']); ?> <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="customer-order-details.php?id=<?php echo $order['id']; ?>" class="btn-view">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here</p>
                        <a href="products.php" class="btn-view" style="margin-top: 1rem;">Browse Products</a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

