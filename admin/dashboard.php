<?php
define('JINKA_ACCESS', true);
require_once 'includes/auth.php';

// Require authentication (support agents can view limited dashboard)
$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();

// Get dashboard statistics
$db = Database::getInstance()->getConnection();

// Get time period from request (default to 30 days)
$period = $_GET['period'] ?? '30';
$periods = [
    '7' => '7 days',
    '30' => '30 days',
    '90' => '90 days',
    '365' => '1 year'
];

// Helper function to get date range
function getDateRange($period) {
    switch($period) {
        case '7': return 'DATE_SUB(NOW(), INTERVAL 7 DAY)';
        case '30': return 'DATE_SUB(NOW(), INTERVAL 30 DAY)';
        case '90': return 'DATE_SUB(NOW(), INTERVAL 90 DAY)';
        case '365': return 'DATE_SUB(NOW(), INTERVAL 1 YEAR)';
        default: return 'DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }
}

$dateRange = getDateRange($period);

// Get comprehensive analytics
$analytics = [];

// Product Analytics
$stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM products");
$productStats = $stmt ? $stmt->fetch_assoc() : ['total' => 0, 'active' => 0];
$analytics['products'] = $productStats;

// Order Analytics - check if table exists
$orderStats = ['total' => 0, 'recent' => 0, 'revenue' => 0, 'avg_order' => 0];
$tableCheck = $db->query("SHOW TABLES LIKE 'orders'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $orderStats['total'] = $stmt ? $stmt->fetch_assoc()['total'] : 0;
    
    // Recent orders
    $stmt = $db->query("SELECT COUNT(*) as recent FROM orders WHERE created_at >= $dateRange");
    $orderStats['recent'] = $stmt ? $stmt->fetch_assoc()['recent'] : 0;
    
    // Revenue
    $stmt = $db->query("SELECT SUM(total_amount) as revenue, AVG(total_amount) as avg_order FROM orders WHERE created_at >= $dateRange");
    $revenue = $stmt ? $stmt->fetch_assoc() : ['revenue' => 0, 'avg_order' => 0];
    $orderStats['revenue'] = $revenue['revenue'] ?: 0;
    $orderStats['avg_order'] = $revenue['avg_order'] ?: 0;
}
$analytics['orders'] = $orderStats;

// Customer Analytics - check if table exists
$customerStats = ['total' => 0, 'active' => 0, 'new' => 0, 'with_orders' => 0];
$tableCheck = $db->query("SHOW TABLES LIKE 'customers'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    // Total and active customers
    $stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM customers");
    $counts = $stmt ? $stmt->fetch_assoc() : ['total' => 0, 'active' => 0];
    $customerStats['total'] = $counts['total'];
    $customerStats['active'] = $counts['active'];
    
    // New customers
    $stmt = $db->query("SELECT COUNT(*) as new_customers FROM customers WHERE created_at >= $dateRange");
    $customerStats['new'] = $stmt ? $stmt->fetch_assoc()['new_customers'] : 0;
    
    // Customers with orders
    $orderTableCheck = $db->query("SHOW TABLES LIKE 'orders'");
    if ($orderTableCheck && $orderTableCheck->num_rows > 0) {
        $stmt = $db->query("SELECT COUNT(DISTINCT customer_id) as customers_with_orders FROM orders WHERE created_at >= $dateRange");
        $customerStats['with_orders'] = $stmt ? $stmt->fetch_assoc()['customers_with_orders'] : 0;
    }
}
$analytics['customers'] = $customerStats;

// Inquiry Analytics - check if table exists
$inquiryStats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0, 'conversion_rate' => 0];
$tableCheck = $db->query("SHOW TABLES LIKE 'inquiries'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    // Total inquiries
    $stmt = $db->query("SELECT COUNT(*) as total FROM inquiries WHERE created_at >= $dateRange");
    $inquiryStats['total'] = $stmt ? $stmt->fetch_assoc()['total'] : 0;
    
    // Status breakdown
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM inquiries 
        WHERE created_at >= $dateRange
        GROUP BY status
    ");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $inquiryStats[$row['status']] = $row['count'];
        }
    }
    
    // Conversion rate (inquiries to orders)
    if ($inquiryStats['total'] > 0 && $orderStats['recent'] > 0) {
        $inquiryStats['conversion_rate'] = ($orderStats['recent'] / $inquiryStats['total']) * 100;
    }
}
$analytics['inquiries'] = $inquiryStats;

// Sales trend data for charts
$salesTrend = [];
$orderTableCheck = $db->query("SHOW TABLES LIKE 'orders'");
if ($orderTableCheck && $orderTableCheck->num_rows > 0) {
    $days = $period == '7' ? 7 : ($period == '30' ? 30 : ($period == '90' ? 90 : 365));
    $interval = $period == '365' ? 'MONTH' : 'DAY';
    $format = $period == '365' ? '%Y-%m' : '%Y-%m-%d';
    
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '$format') as date,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at >= $dateRange
        GROUP BY DATE_FORMAT(created_at, '$format')
        ORDER BY date ASC
    ");
    
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $salesTrend[] = $row;
        }
    }
}

// Top products
$topProducts = [];
$orderTableCheck = $db->query("SHOW TABLES LIKE 'order_items'");
$productTableCheck = $db->query("SHOW TABLES LIKE 'products'");
if ($orderTableCheck && $orderTableCheck->num_rows > 0 && $productTableCheck && $productTableCheck->num_rows > 0) {
    $stmt = $db->query("
        SELECT 
            p.name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= $dateRange
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $topProducts[] = $row;
        }
    }
}

// Get recent activity (improved)
$recentActivity = [];

// Recent orders
$orderTableCheck = $db->query("SHOW TABLES LIKE 'orders'");
if ($orderTableCheck && $orderTableCheck->num_rows > 0) {
    $stmt = $db->query("
        SELECT 
            'order' as type,
            o.id,
            o.order_number,
            o.total_amount,
            o.currency,
            o.created_at,
            COALESCE(
                COALESCE(c.business_name, CONCAT(c.first_name, ' ', c.last_name)), 
                o.customer_name,
                'Guest Customer'
            ) as customer_name
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $recentActivity[] = $row;
        }
    }
}

// Recent inquiries
$inquiryTableCheck = $db->query("SHOW TABLES LIKE 'inquiries'");
if ($inquiryTableCheck && $inquiryTableCheck->num_rows > 0) {
    $stmt = $db->query("
        SELECT 
            'inquiry' as type,
            id,
            name as customer_name,
            subject,
            status,
            priority,
            created_at
        FROM inquiries 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $recentActivity[] = $row;
        }
    }
}

// Sort recent activity by date
usort($recentActivity, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentActivity = array_slice($recentActivity, 0, 10);

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1 class="page-title">Dashboard</h1>
                <nav class="breadcrumb">
                    <span>Home</span>
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6-6-6z"/>
                    </svg>
                    <span>Dashboard</span>
                </nav>
            </div>
            
            <div class="header-right">
                <div class="user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($currentUser['role']); ?></div>
                        </div>
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    
                    <div class="user-menu-dropdown" id="userMenu">
                        <a href="profile.php" class="user-menu-item">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Profile
                        </a>
                        <a href="settings.php" class="user-menu-item">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                            </svg>
                            Settings
                        </a>
                        <a href="logout.php" class="user-menu-item danger">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="admin-content">
            <?php if ($flash['message']): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-header">
                <div class="header-title-section">
                    <h2 class="content-title">Analytics Dashboard</h2>
                    <p class="content-description">Comprehensive business insights and performance metrics</p>
                </div>
                <div class="header-actions">
                    <div class="period-selector">
                        <label for="period">Period:</label>
                        <select id="period" onchange="changePeriod(this.value)">
                            <?php foreach ($periods as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $period == $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Stats Grid -->
            <div class="stats-grid enhanced">
                <!-- Total Revenue -->
                <div class="stat-card revenue">
                    <div class="stat-header">
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= formatCurrency($analytics['orders']['revenue'], 'KES') ?></div>
                    <div class="stat-change positive">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                        Last <?= $periods[$period] ?>
                    </div>
                </div>

                <!-- Orders -->
                <div class="stat-card orders">
                    <div class="stat-header">
                        <div class="stat-title">Orders</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 7h-1V2H6v5H5c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zM8 4h8v3H8V4zm11 15H5V9h14v10z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($analytics['orders']['recent']) ?></div>
                    <div class="stat-meta">
                        <span>Avg: <?= formatCurrency($analytics['orders']['avg_order'], 'KES') ?></span>
                        <span>Total: <?= number_format($analytics['orders']['total']) ?></span>
                    </div>
                </div>
                
                <!-- Products -->
                <div class="stat-card products">
                    <div class="stat-header">
                        <div class="stat-title">Products</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($analytics['products']['active']) ?></div>
                    <div class="stat-meta">
                        <span>Active: <?= number_format($analytics['products']['active']) ?></span>
                        <span>Total: <?= number_format($analytics['products']['total']) ?></span>
                    </div>
                </div>
                
                <!-- Customers -->
                <div class="stat-card customers">
                    <div class="stat-header">
                        <div class="stat-title">Customers</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.77 1.77 0 0 0 18.27 7H16v1.5l-1.5 1.5H18l1.5 5H21v6h-1z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($analytics['customers']['new']) ?></div>
                    <div class="stat-meta">
                        <span>New: <?= number_format($analytics['customers']['new']) ?></span>
                        <span>Active: <?= number_format($analytics['customers']['active']) ?></span>
                    </div>
                </div>
                
                <!-- Inquiries -->
                <div class="stat-card inquiries">
                    <div class="stat-header">
                        <div class="stat-title">Inquiries</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($analytics['inquiries']['total']) ?></div>
                    <div class="stat-meta">
                        <span>New: <?= number_format($analytics['inquiries']['new'] ?? 0) ?></span>
                        <span>Rate: <?= number_format($analytics['inquiries']['conversion_rate'], 1) ?>%</span>
                    </div>
                </div>
                
                <!-- Conversion Rate -->
                <div class="stat-card conversion">
                    <div class="stat-header">
                        <div class="stat-title">Conversion Rate</div>
                        <div class="stat-icon">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($analytics['inquiries']['conversion_rate'], 1) ?>%</div>
                    <div class="stat-change <?= $analytics['inquiries']['conversion_rate'] > 10 ? 'positive' : 'neutral' ?>">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="<?= $analytics['inquiries']['conversion_rate'] > 10 ? 'M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z' : 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z' ?>"/>
                        </svg>
                        Inquiries to Orders
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Sales Trend Chart -->
                <div class="admin-card chart-card">
                    <div class="card-header">
                        <h3 class="card-title">Sales Trend</h3>
                        <div class="chart-controls">
                            <button class="btn-chart active" onclick="toggleChart('sales', 'orders')">Orders</button>
                            <button class="btn-chart" onclick="toggleChart('sales', 'revenue')">Revenue</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="admin-card chart-card">
                    <div class="card-header">
                        <h3 class="card-title">Inquiry Status</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Products & Recent Activity -->
            <div class="bottom-grid">
                <!-- Top Products -->
                <?php if (!empty($topProducts)): ?>
                <div class="admin-card">
                    <div class="card-header">
                        <h3 class="card-title">Top Products</h3>
                        <span class="card-subtitle">Last <?= $periods[$period] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="products-list">
                            <?php foreach ($topProducts as $index => $product): ?>
                                <div class="product-item">
                                    <div class="product-rank">#<?= $index + 1 ?></div>
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="product-stats">
                                            <span><?= number_format($product['total_sold']) ?> sold</span>
                                            <span><?= formatCurrency($product['revenue'], 'KES') ?></span>
                                        </div>
                                    </div>
                                    <div class="product-progress">
                                        <div class="progress-bar" style="width: <?= ($product['total_sold'] / $topProducts[0]['total_sold']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            
                <!-- Recent Activity -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                        <span class="card-subtitle">Latest updates</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (empty($recentActivity)): ?>
                                <p class="text-center text-muted">No recent activity</p>
                            <?php else: ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?= $activity['type'] == 'order' ? 'success' : 'info' ?>">
                                            <?php if ($activity['type'] == 'order'): ?>
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 7h-1V2H6v5H5c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2z"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <?php if ($activity['type'] == 'order'): ?>
                                                <div class="activity-title">New Order #<?= esc_html($activity['order_number']) ?></div>
                                                <div class="activity-description">
                                                    From <?= esc_html($activity['customer_name'] ?? 'Guest Customer') ?> - 
                                                    <?= formatCurrency($activity['total_amount'], $activity['currency']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="activity-title">
                                                    New Inquiry from <?= esc_html($activity['customer_name'] ?? 'Anonymous') ?>
                                                    <span class="badge <?= $activity['priority'] ?>"><?= ucfirst($activity['priority']) ?></span>
                                                </div>
                                                <div class="activity-description"><?= esc_html($activity['subject'] ?? '') ?></div>
                                            <?php endif; ?>
                                            <div class="activity-time"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Dashboard data from PHP
        const salesData = <?= json_encode($salesTrend) ?>;
        const inquiryStats = <?= json_encode($analytics['inquiries']) ?>;
        
        // Sales Chart
        let salesChart;
        let currentSalesType = 'orders';
        
        function initSalesChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            const labels = salesData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            
            const ordersData = salesData.map(item => parseInt(item.orders));
            const revenueData = salesData.map(item => parseFloat(item.revenue));
            
            salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Orders',
                        data: ordersData,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        function toggleChart(chartType, dataType) {
            if (chartType === 'sales') {
                const buttons = document.querySelectorAll('.btn-chart');
                buttons.forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
                
                currentSalesType = dataType;
                
                const ordersData = salesData.map(item => parseInt(item.orders));
                const revenueData = salesData.map(item => parseFloat(item.revenue));
                
                salesChart.data.datasets[0].data = dataType === 'orders' ? ordersData : revenueData;
                salesChart.data.datasets[0].label = dataType === 'orders' ? 'Orders' : 'Revenue';
                salesChart.data.datasets[0].borderColor = dataType === 'orders' ? '#3B82F6' : '#10B981';
                salesChart.data.datasets[0].backgroundColor = dataType === 'orders' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                
                salesChart.update();
            }
        }
        
        // Status Chart
        function initStatusChart() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            const data = {
                labels: ['New', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [
                        inquiryStats.new || 0,
                        inquiryStats.in_progress || 0,
                        inquiryStats.resolved || 0
                    ],
                    backgroundColor: [
                        '#EF4444',
                        '#F59E0B',
                        '#10B981'
                    ]
                }]
            };
            
            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Period change function
        function changePeriod(period) {
            window.location.href = `dashboard.php?period=${period}`;
        }
        
        // User menu toggle
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('show');
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initSalesChart();
            initStatusChart();
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('userMenu');
            const toggle = document.querySelector('.user-menu-toggle');
            
            if (!toggle.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
    
    <style>
        /* Enhanced Dashboard Styles */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
        }
        
        .header-title-section h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .header-title-section p {
            margin: 0;
            color: var(--text-light);
        }
        
        .period-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .period-selector label {
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .period-selector select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.875rem;
            min-width: 120px;
        }
        
        /* Enhanced Stats Grid */
        .stats-grid.enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card.revenue {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        
        .stat-card.orders {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
        }
        
        .stat-card.products {
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            color: white;
        }
        
        .stat-card.customers {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }
        
        .stat-card.inquiries {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
        }
        
        .stat-card.conversion {
            background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%);
            color: white;
        }
        
        .stat-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            min-height: 350px;
        }
        
        .chart-card .card-body {
            height: 300px;
            position: relative;
        }
        
        .chart-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-chart {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-chart.active {
            background: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        
        .btn-chart:hover {
            background: var(--bg-light);
        }
        
        .btn-chart.active:hover {
            background: var(--admin-primary-hover);
        }
        
        /* Bottom Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        /* Top Products */
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .product-item:hover {
            border-color: var(--admin-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .product-rank {
            width: 2rem;
            height: 2rem;
            background: var(--admin-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .product-stats {
            font-size: 0.75rem;
            color: var(--text-light);
            display: flex;
            gap: 1rem;
        }
        
        .product-progress {
            width: 60px;
            height: 4px;
            background: var(--bg-light);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--admin-primary);
            transition: width 0.3s ease;
        }
        
        /* Enhanced Activity List */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        
        .activity-item:hover {
            background: var(--bg-light);
            border-color: var(--admin-primary);
        }
        
        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-icon.success { 
            background: rgba(16, 185, 129, 0.1); 
            color: #10B981; 
        }
        
        .activity-icon.info { 
            background: rgba(59, 130, 246, 0.1); 
            color: #3B82F6; 
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-description {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .activity-time {
            color: var(--text-light);
            font-size: 0.75rem;
        }
        
        .badge {
            background: var(--admin-warning);
            color: white;
            font-size: 0.625rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .badge.high, .badge.urgent {
            background: var(--admin-danger);
        }
        
        .badge.medium {
            background: var(--admin-warning);
        }
        
        .badge.low {
            background: var(--admin-info);
        }
        
        .text-center { text-align: center; }
        .text-muted { color: var(--text-light); }
        
        .card-subtitle {
            font-size: 0.75rem;
            color: var(--text-light);
            font-weight: normal;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stats-grid.enhanced {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>