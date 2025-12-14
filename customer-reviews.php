<?php
/**
 * Customer Reviews Page
 * View and manage product reviews
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

// Handle review submission/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_review' || $action === 'edit_review') {
        $product_id = intval($_POST['product_id']);
        $rating = intval($_POST['rating']);
        $review_title = Security::sanitizeInput($_POST['review_title']);
        $review_text = Security::sanitizeInput($_POST['review_text']);
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        
        if ($rating < 1 || $rating > 5) {
            $error_message = 'Invalid rating';
        } elseif (empty($review_title) || empty($review_text)) {
            $error_message = 'Title and review text are required';
        } else {
            // Check if customer purchased this product
            $purchase_check_sql = "SELECT COUNT(*) as count FROM order_items oi
                                   INNER JOIN orders o ON oi.order_id = o.id
                                   WHERE o.customer_id = ? AND oi.product_id = ? AND o.status IN ('delivered', 'completed')";
            $purchase_stmt = $conn->prepare($purchase_check_sql);
            $purchase_stmt->bind_param('ii', $customer_id, $product_id);
            $purchase_stmt->execute();
            $purchase_result = $purchase_stmt->get_result();
            $is_verified = $purchase_result->fetch_assoc()['count'] > 0 ? 1 : 0;
            $purchase_stmt->close();
            
            if ($action === 'add_review') {
                // Check if already reviewed
                $check_sql = "SELECT id FROM customer_reviews WHERE customer_id = ? AND product_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param('ii', $customer_id, $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error_message = 'You have already reviewed this product';
                } else {
                    $insert_sql = "INSERT INTO customer_reviews (customer_id, product_id, rating, review_title, review_text, is_verified_purchase) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param('iiissi', $customer_id, $product_id, $rating, $review_title, $review_text, $is_verified);
                    
                    if ($insert_stmt->execute()) {
                        $success_message = 'Review submitted successfully! It will be published after moderation.';
                    } else {
                        $error_message = 'Failed to submit review';
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            } else {
                // Edit existing review
                $update_sql = "UPDATE customer_reviews SET rating = ?, review_title = ?, review_text = ?, status = 'pending' 
                               WHERE id = ? AND customer_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('issii', $rating, $review_title, $review_text, $review_id, $customer_id);
                
                if ($update_stmt->execute()) {
                    $success_message = 'Review updated successfully! Changes will be published after moderation.';
                } else {
                    $error_message = 'Failed to update review';
                }
                $update_stmt->close();
            }
        }
    } elseif ($action === 'delete_review') {
        $review_id = intval($_POST['review_id']);
        
        $delete_sql = "DELETE FROM customer_reviews WHERE id = ? AND customer_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $review_id, $customer_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'Review deleted successfully';
        } else {
            $error_message = 'Failed to delete review';
        }
        $delete_stmt->close();
    }
}

// Get reviews
$filter = isset($_GET['filter']) ? Security::sanitizeInput($_GET['filter']) : 'all';

$where_clause = "r.customer_id = ?";
$params = [$customer_id];
$types = 'i';

if ($filter !== 'all') {
    $where_clause .= " AND r.status = ?";
    $params[] = $filter;
    $types .= 's';
}

$reviews_sql = "SELECT r.*, p.name as product_name, p.image as product_image
                FROM customer_reviews r
                INNER JOIN products p ON r.product_id = p.id
                WHERE {$where_clause}
                ORDER BY r.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param($types, ...$params);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$reviews_stmt->close();

// Status colors
$status_colors = [
    'pending' => ['color' => '#ffc107', 'icon' => 'clock', 'label' => 'Pending Review'],
    'approved' => ['color' => '#28a745', 'icon' => 'check-circle', 'label' => 'Published'],
    'rejected' => ['color' => '#dc3545', 'icon' => 'times-circle', 'label' => 'Rejected']
];

$page_title = 'My Reviews';
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

.reviews-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.reviews-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.reviews-header h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.reviews-header p {
    margin: 0;
    opacity: 0.9;
}

.reviews-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-btn {
    padding: 10px 20px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #ddd;
    color: #666;
    transition: all 0.2s;
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

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
}

.review-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 15px;
    padding: 25px;
    transition: all 0.3s;
}

.review-card:hover {
    border-color: #ff5900;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
}

.review-product {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    background: #f8f9fa;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    font-size: 0.95rem;
}

.review-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.verified-badge {
    background: #d1e7dd;
    color: #0f5132;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
}

.review-rating {
    display: flex;
    gap: 5px;
    margin-bottom: 15px;
}

.star {
    color: #ffc107;
    font-size: 1.2rem;
}

.star.empty {
    color: #ddd;
}

.review-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.review-text {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.review-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    font-size: 0.875rem;
    color: #999;
}

.review-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-edit {
    background: #ff5900;
    color: white;
}

.btn-edit:hover {
    background: #ff5900;
}

.btn-delete {
    background: #f8d7da;
    color: #842029;
}

.btn-delete:hover {
    background: #dc3545;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    border: 2px dashed #e0e0e0;
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

.btn-browse {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 768px) {
    .reviews-container {
        margin: 20px auto;
    }
    
    .reviews-header {
        padding: 30px 20px;
        border-radius: 15px;
    }
    
    .reviews-header h1 {
        font-size: 1.5rem;
    }
    
    .reviews-grid {
        grid-template-columns: 1fr;
    }
    
    .review-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="reviews-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="reviews-header">
        <h1><i class="fas fa-star"></i> My Reviews</h1>
        <p>Your product reviews and ratings</p>
    </div>
    
    <div class="reviews-filters">
        <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
            <i class="fas fa-list"></i> All Reviews
        </a>
        <a href="?filter=approved" class="filter-btn <?= $filter === 'approved' ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Published
        </a>
        <a href="?filter=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="?filter=rejected" class="filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i> Rejected
        </a>
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
    
    <?php if (count($reviews) > 0): ?>
        <div class="reviews-grid">
            <?php foreach ($reviews as $review): ?>
                <?php
                $status = $review['status'];
                $status_info = $status_colors[$status] ?? $status_colors['pending'];
                $image_url = normalize_product_image_url($review['product_image'], ['fallback' => 'images/placeholder.png']);
                ?>
                <div class="review-card">
                    <div class="review-product">
                        <img src="<?= esc_html($image_url) ?>" alt="<?= esc_html($review['product_name']) ?>" class="product-image">
                        <div class="product-info">
                            <div class="product-name"><?= esc_html($review['product_name']) ?></div>
                            <span class="review-status-badge" style="background: <?= $status_info['color'] ?>20; color: <?= $status_info['color'] ?>;">
                                <i class="fas fa-<?= $status_info['icon'] ?>"></i>
                                <?= $status_info['label'] ?>
                            </span>
                            <?php if ($review['is_verified_purchase']): ?>
                                <div class="verified-badge">
                                    <i class="fas fa-check-circle"></i> Verified Purchase
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star star <?= $i <= $review['rating'] ? '' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="review-title"><?= esc_html($review['review_title']) ?></div>
                    <div class="review-text"><?= nl2br(esc_html($review['review_text'])) ?></div>
                    
                    <div class="review-meta">
                        <span><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                        
                        <div class="review-actions">
                            <?php if ($review['status'] !== 'approved'): ?>
                                <a href="customer-review-edit.php?id=<?= $review['id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this review?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($review['helpful_count'] > 0): ?>
                        <div style="margin-top: 10px; color: #999; font-size: 0.875rem;">
                            <i class="fas fa-thumbs-up"></i> <?= $review['helpful_count'] ?> people found this helpful
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-star-half-alt"></i>
            <h3>No Reviews Yet</h3>
            <p>
                <?php if ($filter !== 'all'): ?>
                    No <?= $filter ?> reviews found. <a href="customer-reviews.php">View all reviews</a>
                <?php else: ?>
                    Start reviewing products you've purchased!
                <?php endif; ?>
            </p>
            <?php if ($filter === 'all'): ?>
                <a href="customer-orders.php" class="btn-browse">
                    <i class="fas fa-shopping-bag"></i> View My Orders
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
    </main>
</div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

