<?php
/**
 * Customer Order Details Page
 * Display detailed information about a specific order
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

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    redirect('customer-orders.php', 'Invalid order ID', 'error');
}

// Get order details
$sql = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    redirect('customer-orders.php', 'Order not found', 'error');
}

// Get order items
$order_items = [];
if (isset($order['items']) && $order['items']) {
    $items_json = json_decode($order['items'], true);
    if (is_array($items_json)) {
        foreach ($items_json as $item) {
            // Try to get product details if product_id exists
            $product_image = null;
            if (isset($item['product_id'])) {
                $prod_sql = "SELECT image FROM products WHERE id = ?";
                $prod_stmt = $conn->prepare($prod_sql);
                $prod_stmt->bind_param('i', $item['product_id']);
                $prod_stmt->execute();
                $prod_result = $prod_stmt->get_result();
                if ($prod_row = $prod_result->fetch_assoc()) {
                    $product_image = $prod_row['image'];
                }
                $prod_stmt->close();
            }
            
            $order_items[] = [
                'product_name' => $item['name'] ?? $item['product_name'] ?? 'Unknown Product',
                'product_image' => $product_image ?? ($item['image'] ?? null),
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'specifications' => json_encode($item['specifications'] ?? [])
            ];
        }
    }
}

// Status badge colors
$status_colors = [
    'pending' => ['color' => 'warning', 'icon' => 'clock'],
    'processing' => ['color' => 'info', 'icon' => 'cog'],
    'shipped' => ['color' => 'primary', 'icon' => 'truck'],
    'delivered' => ['color' => 'success', 'icon' => 'check-circle'],
    'cancelled' => ['color' => 'danger', 'icon' => 'times-circle'],
    'refunded' => ['color' => 'secondary', 'icon' => 'undo']
];

$page_title = 'Order Details - ' . $order['order_number'];
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

.order-details-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.order-tracking {
    background: white;
    padding: 40px;
    border-radius: 15px;
    margin: 30px 0;
    border: 1px solid #e0e0e0;
}

.tracking-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 30px 0;
}

.tracking-steps::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 0;
    right: 0;
    height: 3px;
    background: #e0e0e0;
    z-index: 0;
}

.tracking-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 1.2rem;
    color: #999;
    transition: all 0.3s;
}

.tracking-step.completed .step-icon {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    border-color: #ff5900;
    color: white;
}

.tracking-step.active .step-icon {
    border-color: #ff5900;
    border-width: 4px;
    color: #ff5900;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.step-label {
    font-size: 0.875rem;
    color: #666;
    font-weight: 500;
}

.tracking-step.completed .step-label,
.tracking-step.active .step-label {
    color: #ff5900;
    font-weight: 600;
}

.step-date {
    font-size: 0.75rem;
    color: #999;
    margin-top: 5px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #ff5900;
    text-decoration: none;
    margin-bottom: 20px;
    font-weight: 500;
    transition: transform 0.2s;
}

.back-link:hover {
    transform: translateX(-5px);
    color: #ff5900;
}

.order-header {
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

.order-header-left h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.order-header-left p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.order-status-big {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: 30px;
    font-size: 1.1rem;
    font-weight: 600;
}

.order-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
}

.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    padding: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.info-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
}

.info-card h3 {
    margin: 0 0 15px 0;
    font-size: 1rem;
    color: #ff5900;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-card p {
    margin: 8px 0;
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
}

.info-card strong {
    color: #333;
    font-weight: 600;
}

.order-items-section {
    padding: 30px;
}

.order-items-section h2 {
    margin: 0 0 20px 0;
    font-size: 1.3rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    align-items: center;
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    background: white;
    border: 1px solid #ddd;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    font-size: 1rem;
}

.item-specs {
    color: #666;
    font-size: 0.875rem;
    line-height: 1.6;
}

.item-quantity {
    text-align: center;
    padding: 0 20px;
}

.item-quantity-label {
    color: #666;
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.item-quantity-value {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
}

.item-price {
    text-align: right;
    min-width: 120px;
}

.item-price-label {
    color: #666;
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.item-price-value {
    font-weight: 600;
    font-size: 1.1rem;
    color: #ff5900;
}

.order-summary {
    padding: 30px;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
}

.summary-rows {
    max-width: 400px;
    margin-left: auto;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #ddd;
    font-size: 0.95rem;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    padding-top: 15px;
    margin-top: 10px;
    border-top: 2px solid #ff5900;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    justify-content: flex-end;
}

.btn-action {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: transform 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
}

.btn-secondary {
    background: white;
    color: #ff5900;
    border: 2px solid #ff5900;
}

.btn-action:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .order-details-container {
        margin: 20px auto;
    }
    
    .order-header {
        padding: 30px 20px;
        border-radius: 15px 15px 0 0;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-header-left h1 {
        font-size: 1.5rem;
    }
    
    .order-info-grid {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .order-items-section {
        padding: 20px;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .item-image {
        width: 100%;
        height: 200px;
    }
    
    .item-quantity,
    .item-price {
        text-align: left;
        padding: 0;
        width: 100%;
    }
    
    .order-summary {
        padding: 20px;
    }
    
    .summary-rows {
        max-width: 100%;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="order-details-container" style="max-width: 100%; margin: 0; padding: 0;">
    <a href="customer-orders.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
    
    <div class="order-header">
        <div class="order-header-left">
            <h1><i class="fas fa-shopping-bag"></i> <?= esc_html($order['order_number']) ?></h1>
            <p>Placed on <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <?php
        $status = $order['status'];
        $status_info = $status_colors[$status] ?? ['color' => 'secondary', 'icon' => 'info-circle'];
        ?>
        <div class="order-status-big">
            <i class="fas fa-<?= $status_info['icon'] ?>"></i>
            <?= ucfirst($status) ?>
        </div>
    </div>
    
    <!-- Order Tracking Steps -->
    <div class="order-tracking">
        <h2 style="margin: 0 0 10px 0; font-size: 1.3rem; color: #333;"><i class="fas fa-route"></i> Order Tracking</h2>
        <p style="margin: 0 0 20px 0; color: #666;">Track your order status and delivery progress</p>
        
        <?php
        $status = $order['status'];
        $steps = [
            'pending' => ['label' => 'Order Placed', 'icon' => 'check'],
            'confirmed' => ['label' => 'Confirmed', 'icon' => 'clipboard-check'],
            'processing' => ['label' => 'Processing', 'icon' => 'cog'],
            'shipped' => ['label' => 'Shipped', 'icon' => 'truck'],
            'delivered' => ['label' => 'Delivered', 'icon' => 'box-open']
        ];
        
        $step_order = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $current_step_index = array_search($status, $step_order);
        if ($current_step_index === false) $current_step_index = 0;
        ?>
        
        <div class="tracking-steps">
            <?php foreach ($step_order as $index => $step_key): ?>
                <?php
                $step_info = $steps[$step_key];
                $is_completed = $index < $current_step_index;
                $is_active = $index === $current_step_index;
                $step_class = $is_completed ? 'completed' : ($is_active ? 'active' : '');
                ?>
                <div class="tracking-step <?= $step_class ?>">
                    <div class="step-icon">
                        <i class="fas fa-<?= $step_info['icon'] ?>"></i>
                    </div>
                    <div class="step-label"><?= $step_info['label'] ?></div>
                    <?php if ($is_completed || $is_active): ?>
                        <div class="step-date"><?= date('M d', strtotime($order['created_at'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="order-content">
        <div class="order-info-grid">
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <p><strong>Name:</strong> <?= esc_html($order['customer_name']) ?></p>
                <p><strong>Email:</strong> <?= esc_html($order['customer_email']) ?></p>
                <p><strong>Phone:</strong> <?= esc_html($order['customer_phone']) ?></p>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <?php if ($order['shipping_address']): ?>
                    <?php
                    // Try to parse JSON first
                    $address = @json_decode($order['shipping_address'], true);
                    if ($address && is_array($address)):
                    ?>
                        <p><?= esc_html($address['street'] ?? '') ?></p>
                        <p><?= esc_html($address['city'] ?? '') ?>, <?= esc_html($address['state'] ?? '') ?></p>
                        <p><?= esc_html($address['postal_code'] ?? '') ?></p>
                        <p><?= esc_html($address['country'] ?? '') ?></p>
                    <?php else: ?>
                        <p><?= nl2br(esc_html($order['shipping_address'])) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No shipping address provided</p>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                <p><strong>Method:</strong> <?= ucfirst(esc_html($order['payment_method'] ?? 'Not specified')) ?></p>
                <p><strong>Status:</strong> <?= ucfirst(esc_html($order['payment_status'] ?? 'pending')) ?></p>
                <?php if ($order['transaction_id']): ?>
                    <p><strong>Transaction ID:</strong> <?= esc_html($order['transaction_id']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-items-section">
            <h2><i class="fas fa-box"></i> Order Items (<?= count($order_items) ?>)</h2>
            
            <div class="order-items-list">
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <?php
                        $image_url = normalize_product_image_url($item['product_image'], ['fallback' => 'images/placeholder.png']);
                        ?>
                        <img src="<?= esc_html($image_url) ?>" alt="<?= esc_html($item['product_name']) ?>" class="item-image">
                        
                        <div class="item-details">
                            <div class="item-name"><?= esc_html($item['product_name']) ?></div>
                            <div class="item-specs">
                                <?php if ($item['specifications']): ?>
                                    <?php
                                    $specs = @json_decode($item['specifications'], true);
                                    if ($specs && is_array($specs)):
                                        foreach ($specs as $key => $value):
                                    ?>
                                        <div><?= esc_html(ucfirst($key)) ?>: <?= esc_html($value) ?></div>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <?= esc_html($item['specifications']) ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="item-quantity">
                            <div class="item-quantity-label">Quantity</div>
                            <div class="item-quantity-value"><?= $item['quantity'] ?></div>
                        </div>
                        
                        <div class="item-price">
                            <div class="item-price-label">Price</div>
                            <div class="item-price-value"><?= formatCurrency($item['price'], $order['currency'] ?? 'KES') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="order-summary">
            <div class="summary-rows">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span><?= formatCurrency($order['subtotal'] ?? $order['total_amount'], $order['currency'] ?? 'KES') ?></span>
                </div>
                
                <?php if (isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= formatCurrency($order['shipping_cost'], $order['currency'] ?? 'KES') ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span><?= formatCurrency($order['tax_amount'], $order['currency'] ?? 'KES') ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span>-<?= formatCurrency($order['discount_amount'], $order['currency'] ?? 'KES') ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span><?= formatCurrency($order['total_amount'], $order['currency'] ?? 'KES') ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if ($order['status'] === 'pending'): ?>
                    <button class="btn-action btn-secondary" onclick="if(confirm('Are you sure you want to cancel this order?')) window.location.href='cancel-order.php?id=<?= $order['id'] ?>'">
                        <i class="fas fa-times"></i> Cancel Order
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                    <a href="track-delivery.php?order=<?= urlencode($order['order_number']) ?>" class="btn-action btn-secondary">
                        <i class="fas fa-map-marker-alt"></i> Track Delivery
                    </a>
                <?php endif; ?>
                
                <a href="customer-support.php?order_id=<?= $order['id'] ?>" class="btn-action btn-primary">
                    <i class="fas fa-life-ring"></i> Get Support
                </a>
            </div>
        </div>
    </div>
    </main>
</div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

