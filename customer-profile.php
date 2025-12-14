<?php
/**
 * Customer Profile Page
 * Allow customers to view and edit their profile information
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update basic profile information
        $first_name = Security::sanitizeInput($_POST['first_name']);
        $last_name = Security::sanitizeInput($_POST['last_name']);
        $email = Security::sanitizeInput($_POST['email'], 'email');
        $phone = Security::sanitizeInput($_POST['phone']);
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = 'First name, last name and email are required';
        } else {
            // Check if email is already used by another customer
            $check_sql = "SELECT id FROM customers WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param('si', $email, $customer_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = 'Email address is already in use by another account';
            } else {
                $update_sql = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ssssi', $first_name, $last_name, $email, $phone, $customer_id);
                
                if ($update_stmt->execute()) {
                    $success_message = 'Profile updated successfully';
                    // Refresh customer data in session
                    $_SESSION['customer'] = array_merge($_SESSION['customer'], [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone
                    ]);
                    $customer = $_SESSION['customer'];
                } else {
                    $error_message = 'Failed to update profile';
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } else {
            // Validate new password strength
            $password_errors = Security::validatePassword($new_password);
            if (!empty($password_errors)) {
                $error_message = implode('. ', $password_errors);
            } else {
                // Verify current password
                $verify_sql = "SELECT password FROM customers WHERE id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param('i', $customer_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $customer_data = $verify_result->fetch_assoc();
                $verify_stmt->close();
                
                if (!Security::verifyPassword($current_password, $customer_data['password'])) {
                    $error_message = 'Current password is incorrect';
                } else {
                    // Update password
                    $new_password_hash = Security::hashPassword($new_password);
                    $update_sql = "UPDATE customers SET password = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('si', $new_password_hash, $customer_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = 'Password changed successfully';
                    } else {
                        $error_message = 'Failed to change password';
                    }
                    $update_stmt->close();
                }
            }
        }
    }
}

$page_title = 'My Profile';
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

.profile-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px 20px 0 0;
    text-align: center;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
    color: #ff5900;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    margin: 0 auto 20px;
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.profile-header h1 {
    margin: 0 0 5px 0;
    font-size: 2rem;
}

.profile-header p {
    margin: 0;
    opacity: 0.9;
}

.profile-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 20px 20px;
}

.profile-tabs {
    display: flex;
    border-bottom: 2px solid #e0e0e0;
}

.tab-button {
    flex: 1;
    padding: 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
    position: relative;
}

.tab-button:hover {
    color: #ff5900;
    background: #f8f9fa;
}

.tab-button.active {
    color: #ff5900;
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
}

.tab-content {
    display: none;
    padding: 40px;
}

.tab-content.active {
    display: block;
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

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #ff5900;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.btn-update {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 14px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-update:hover {
    transform: translateY(-2px);
}

.password-requirements {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
    font-size: 0.875rem;
    color: #666;
}

.password-requirements ul {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.password-requirements li {
    margin: 5px 0;
}

.info-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    margin-bottom: 20px;
}

.info-card h3 {
    margin: 0 0 15px 0;
    font-size: 1rem;
    color: #ff5900;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #666;
    font-weight: 500;
}

.info-value {
    color: #333;
    font-weight: 600;
}

@media (max-width: 768px) {
    .profile-container {
        margin: 20px auto;
    }
    
    .profile-header {
        padding: 30px 20px;
        border-radius: 15px 15px 0 0;
    }
    
    .profile-header h1 {
        font-size: 1.5rem;
    }
    
    .profile-tabs {
        flex-direction: column;
    }
    
    .tab-button {
        text-align: left;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="profile-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h1><?= esc_html(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?></h1>
        <p><?= esc_html($customer['email']) ?></p>
    </div>
    
    <div class="profile-content">
        <div class="profile-tabs">
            <button class="tab-button active" onclick="switchTab('personal')">
                <i class="fas fa-user"></i> Personal Information
            </button>
            <button class="tab-button" onclick="switchTab('password')">
                <i class="fas fa-lock"></i> Change Password
            </button>
            <button class="tab-button" onclick="switchTab('activity')">
                <i class="fas fa-history"></i> Account Activity
            </button>
        </div>
        
        <!-- Personal Information Tab -->
        <div id="personal-tab" class="tab-content active">
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
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?= esc_html($customer['first_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?= esc_html($customer['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= esc_html($customer['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= esc_html($customer['phone'] ?? '') ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-update">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password Tab -->
        <div id="password-tab" class="tab-content">
            <?php if ($success_message && $_POST['action'] === 'change_password'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= esc_html($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message && $_POST['action'] === 'change_password'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= esc_html($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required>
                    
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least <?= MIN_PASSWORD_LENGTH ?> characters long</li>
                            <?php if (REQUIRE_PASSWORD_UPPERCASE): ?>
                                <li>At least one uppercase letter</li>
                            <?php endif; ?>
                            <?php if (REQUIRE_PASSWORD_LOWERCASE): ?>
                                <li>At least one lowercase letter</li>
                            <?php endif; ?>
                            <?php if (REQUIRE_PASSWORD_NUMBERS): ?>
                                <li>At least one number</li>
                            <?php endif; ?>
                            <?php if (REQUIRE_PASSWORD_SPECIAL): ?>
                                <li>At least one special character</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn-update">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Account Activity Tab -->
        <div id="activity-tab" class="tab-content">
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                
                <div class="info-row">
                    <span class="info-label">Account Created:</span>
                    <span class="info-value"><?= date('F d, Y', strtotime($customer['created_at'])) ?></span>
                </div>
                
                <?php if (isset($customer['last_login']) && $customer['last_login']): ?>
                    <div class="info-row">
                        <span class="info-label">Last Login:</span>
                        <span class="info-value"><?= date('F d, Y \a\t g:i A', strtotime($customer['last_login'])) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Email Verified:</span>
                    <span class="info-value">
                        <?php if (isset($customer['email_verified']) && $customer['email_verified']): ?>
                            <i class="fas fa-check-circle" style="color: #28a745;"></i> Verified
                        <?php else: ?>
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i> Not Verified
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (isset($customer['phone']) && $customer['phone']): ?>
                    <div class="info-row">
                        <span class="info-label">Phone Verified:</span>
                        <span class="info-value">
                            <?php if (isset($customer['phone_verified']) && $customer['phone_verified']): ?>
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> Verified
                            <?php else: ?>
                                <i class="fas fa-times-circle" style="color: #dc3545;"></i> Not Verified
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php
            // Get recent orders count
            $orders_sql = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?";
            $orders_stmt = $conn->prepare($orders_sql);
            $orders_stmt->bind_param('i', $customer_id);
            $orders_stmt->execute();
            $orders_result = $orders_stmt->get_result();
            $orders_count = $orders_result->fetch_assoc()['total'];
            $orders_stmt->close();
            
            // Get wishlist count
            $wishlist_sql = "SELECT COUNT(*) as total FROM customer_wishlists WHERE customer_id = ?";
            $wishlist_stmt = $conn->prepare($wishlist_sql);
            $wishlist_stmt->bind_param('i', $customer_id);
            $wishlist_stmt->execute();
            $wishlist_result = $wishlist_stmt->get_result();
            $wishlist_count = $wishlist_result->fetch_assoc()['total'];
            $wishlist_stmt->close();
            ?>
            
            <div class="info-card">
                <h3><i class="fas fa-chart-bar"></i> Account Statistics</h3>
                
                <div class="info-row">
                    <span class="info-label">Total Orders:</span>
                    <span class="info-value"><?= $orders_count ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Wishlist Items:</span>
                    <span class="info-value"><?= $wishlist_count ?></span>
                </div>
            </div>
        </div>
    </div>
    </main>
</div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to clicked button
    event.target.closest('.tab-button').classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

