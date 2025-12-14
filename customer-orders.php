<?php
/**
 * Customer Orders Page
 * Display all orders for the logged-in customer
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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = isset($_GET['status']) ? Security::sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["o.customer_id = ?"];
$params = [$customer_id];
$types = 'i';

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = implode(' AND ', $where_conditions);

// Count total orders
$count_sql = "SELECT COUNT(*) as total FROM orders o WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_orders = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_orders = 0;
}

$total_pages = ceil($total_orders / $per_page);

// Get orders
$sql = "SELECT o.*
        FROM orders o 
        WHERE {$where_clause}
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $types .= 'ii';
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate item count from JSON items field
    foreach ($orders as &$order) {
        $items = json_decode($order['items'] ?? '[]', true);
        $order['item_count'] = is_array($items) ? count($items) : 0;
    }
} else {
    $orders = [];
}

// Status badge colors
$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger',
    'refunded' => 'secondary'
];

$page_title = 'My Orders';
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

.orders-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.orders-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px 20px 0 0;
    margin-bottom: 0;
}

.orders-header h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.orders-header p {
    margin: 0;
    opacity: 0.9;
}

.orders-filters {
    background: white;
    padding: 20px;
    border-left: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #ff5900;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-filter {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: transform 0.2s;
    align-self: flex-end;
}

.btn-filter:hover {
    transform: translateY(-2px);
}

.orders-content {
    background: white;
    padding: 30px;
    border-radius: 0 0 20px 20px;
    border: 1px solid #e0e0e0;
    border-top: none;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.orders-table thead {
    background: #f8f9fa;
}

.orders-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e0e0e0;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.orders-table td {
    padding: 20px 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.orders-table tbody tr {
    transition: background-color 0.2s;
}

.orders-table tbody tr:hover {
    background-color: #f8f9fa;
}

.order-number {
    font-weight: 600;
    color: #ff5900;
    font-size: 0.95rem;
}

.order-date {
    color: #666;
    font-size: 0.875rem;
}

.order-total {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}

.order-items {
    color: #666;
    font-size: 0.875rem;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.status-badge.info {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.primary {
    background: #cfe2ff;
    color: #084298;
}

.status-badge.success {
    background: #d1e7dd;
    color: #0f5132;
}

.status-badge.danger {
    background: #f8d7da;
    color: #842029;
}

.status-badge.secondary {
    background: #e2e3e5;
    color: #41464b;
}

.btn-view {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-block;
    transition: transform 0.2s;
}

.btn-view:hover {
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.pagination a,
.pagination span {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-decoration: none;
    color: #ff5900;
    font-weight: 500;
    transition: all 0.2s;
}

.pagination a:hover {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    border-color: #ff5900;
}

.pagination .active {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    border-color: #ff5900;
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
    margin-bottom: 30px;
}

.btn-shop {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    transition: transform 0.2s;
}

.btn-shop:hover {
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .orders-container {
        margin: 20px auto;
    }
    
    .orders-header {
        padding: 30px 20px;
        border-radius: 15px 15px 0 0;
    }
    
    .orders-header h1 {
        font-size: 1.5rem;
    }
    
    .orders-content {
        padding: 20px;
        border-radius: 0 0 15px 15px;
    }
    
    .orders-filters {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .btn-filter {
        width: 100%;
    }
    
    .orders-table {
        display: block;
        overflow-x: auto;
    }
    
    .orders-table thead {
        display: none;
    }
    
    .orders-table tbody,
    .orders-table tr,
    .orders-table td {
        display: block;
        width: 100%;
    }
    
    .orders-table tr {
        margin-bottom: 20px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
    }
    
    .orders-table td {
        padding: 10px 0;
        border: none;
        text-align: left;
    }
    
    .orders-table td:before {
        content: attr(data-label);
        font-weight: 600;
        display: block;
        margin-bottom: 5px;
        color: #666;
        font-size: 0.875rem;
        text-transform: uppercase;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="orders-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="orders-header">
        <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
        <p>Track and manage all your orders</p>
    </div>
    
    <div class="orders-filters">
        <div class="filter-group">
            <label for="status-filter">Order Status</label>
            <select id="status-filter" name="status" onchange="applyFilters()">
                <option value="all" <?= $status_filter === 'all' || !$status_filter ? 'selected' : '' ?>>All Orders</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="refunded" <?= $status_filter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="search-filter">Search Orders</label>
            <input type="text" id="search-filter" name="search" placeholder="Order number, name, email..." value="<?= esc_html($search) ?>">
        </div>
        
        <button type="button" class="btn-filter" onclick="applyFilters()">
            <i class="fas fa-filter"></i> Apply Filters
        </button>
    </div>
    
    <div class="orders-content">
        <?php if (count($orders) > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order Number">
                                <div class="order-number"><?= esc_html($order['order_number']) ?></div>
                            </td>
                            <td data-label="Date">
                                <div class="order-date"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                            </td>
                            <td data-label="Items">
                                <div class="order-items"><?= $order['item_count'] ?> item(s)</div>
                            </td>
                            <td data-label="Total">
                                <div class="order-total"><?= formatCurrency($order['total_amount'], $order['currency'] ?? 'KES') ?></div>
                            </td>
                            <td data-label="Status">
                                <?php
                                $status = $order['status'];
                                $color = $status_colors[$status] ?? 'secondary';
                                ?>
                                <span class="status-badge <?= $color ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td data-label="Action">
                                <a href="customer-order-details.php?id=<?= $order['id'] ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Found</h3>
                <p>
                    <?php if ($status_filter || $search): ?>
                        No orders match your filters. Try adjusting your search criteria.
                    <?php else: ?>
                        You haven't placed any orders yet. Start shopping now!
                    <?php endif; ?>
                </p>
                <?php if (!$status_filter && !$search): ?>
                    <a href="products.php" class="btn-shop">
                        <i class="fas fa-shopping-cart"></i> Start Shopping
                    </a>
                <?php else: ?>
                    <a href="customer-orders.php" class="btn-shop">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </main>
</div>
</div>
</div>

<script>
function applyFilters() {
    const status = document.getElementById('status-filter').value;
    const search = document.getElementById('search-filter').value;
    
    let url = 'customer-orders.php?';
    
    if (status && status !== 'all') {
        url += 'status=' + encodeURIComponent(status) + '&';
    }
    
    if (search) {
        url += 'search=' + encodeURIComponent(search) + '&';
    }
    
    // Remove trailing & or ?
    url = url.replace(/[&?]$/, '');
    
    window.location.href = url;
}

// Allow Enter key to trigger filter
document.getElementById('search-filter').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

