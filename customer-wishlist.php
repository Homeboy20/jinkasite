<?php
/**
 * Customer Wishlist Page
 * Display and manage customer wishlist
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

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove') {
        $product_id = intval($_POST['product_id']);
        
        $delete_sql = "DELETE FROM customer_wishlists WHERE customer_id = ? AND product_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $customer_id, $product_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'Product removed from wishlist';
        } else {
            $error_message = 'Failed to remove product';
        }
        $delete_stmt->close();
    }
}

// Get wishlist items
$sql = "SELECT w.*, p.name, p.price_kes, p.price_tzs, p.image, p.stock_quantity, p.is_active
        FROM customer_wishlists w
        INNER JOIN products p ON w.product_id = p.id
        WHERE w.customer_id = ?
        ORDER BY w.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'My Wishlist';
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

.wishlist-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.wishlist-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.wishlist-header h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.wishlist-header p {
    margin: 0;
    opacity: 0.9;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.alert-error {
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.wishlist-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    position: relative;
}

.wishlist-card:hover {
    border-color: #ff5900;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
    transform: translateY(-5px);
}

.product-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    background: #f8f9fa;
}

.wishlist-content {
    padding: 20px;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #ff5900;
    margin-bottom: 15px;
}

.product-stock {
    font-size: 0.875rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.product-stock.in-stock {
    color: #28a745;
}

.product-stock.out-of-stock {
    color: #dc3545;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
    cursor: pointer;
}

.btn-cart {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
}

.btn-cart:hover {
    transform: translateY(-2px);
    color: white;
}

.btn-remove {
    background: #f8d7da;
    color: #842029;
}

.btn-remove:hover {
    background: #dc3545;
    color: white;
}

.added-date {
    font-size: 0.75rem;
    color: #999;
    text-align: center;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
    margin-top: 10px;
}

.out-of-stock-overlay {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #dc3545;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
    border: 2px dashed #e0e0e0;
}

.empty-state i {
    font-size: 5rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.empty-state p {
    color: #999;
    margin-bottom: 30px;
}

.btn-shop {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 14px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s;
}

.btn-shop:hover {
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .wishlist-container {
        margin: 20px auto;
    }
    
    .wishlist-header {
        padding: 30px 20px;
        border-radius: 15px;
    }
    
    .wishlist-header h1 {
        font-size: 1.5rem;
    }
    
    .wishlist-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="wishlist-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="wishlist-header">
        <h1><i class="fas fa-heart"></i> My Wishlist</h1>
        <p><?= count($wishlist_items) ?> item(s) saved for later</p>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= esc_html($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= esc_html($error_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($wishlist_items) > 0): ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-card">
                    <?php
                    $image_url = normalize_product_image_url($item['image'], ['fallback' => 'images/placeholder.png']);
                    $in_stock = $item['stock_quantity'] > 0;
                    $is_active = $item['is_active'] == 1;
                    ?>
                    
                    <?php if (!$in_stock || !$is_active): ?>
                        <div class="out-of-stock-overlay">
                            <?= !$is_active ? 'Unavailable' : 'Out of Stock' ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="product-detail.php?id=<?= $item['product_id'] ?>">
                        <img src="<?= esc_html($image_url) ?>" alt="<?= esc_html($item['name']) ?>" class="product-image">
                    </a>
                    
                    <div class="wishlist-content">
                        <a href="product-detail.php?id=<?= $item['product_id'] ?>" style="text-decoration: none;">
                            <div class="product-name"><?= esc_html($item['name']) ?></div>
                        </a>
                        
                        <div class="product-price"><?= formatCurrency($item['price_kes'] ?? $item['price_tzs'] ?? 0) ?></div>
                        
                        <div class="product-stock <?= $in_stock && $is_active ? 'in-stock' : 'out-of-stock' ?>">
                            <i class="fas fa-<?= $in_stock && $is_active ? 'check-circle' : 'times-circle' ?>"></i>
                            <?php if (!$is_active): ?>
                                Not Available
                            <?php else: ?>
                                <?= $in_stock ? 'In Stock' : 'Out of Stock' ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <?php if ($in_stock && $is_active): ?>
                                <button class="btn-action btn-cart" onclick="addToCart(<?= $item['product_id'] ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <a href="product-detail.php?id=<?= $item['product_id'] ?>" class="btn-action btn-cart" style="opacity: 0.6;">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" class="btn-action btn-remove" style="width: 100%;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="added-date">
                            Added <?= date('M d, Y', strtotime($item['added_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-heart-broken"></i>
            <h3>Your Wishlist is Empty</h3>
            <p>Start adding products you love to your wishlist!</p>
            <a href="products.php" class="btn-shop">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
        </div>
    <?php endif; ?>
    </div>
    </main>
</div>
</div>

<script>
function addToCart(productId) {
    // Add to cart functionality
    fetch('api/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
            // Optionally remove from wishlist
            if (confirm('Remove this item from your wishlist?')) {
                location.reload();
            }
        } else {
            alert('Failed to add product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

