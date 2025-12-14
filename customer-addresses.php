<?php
/**
 * Customer Addresses Page
 * Manage shipping and billing addresses
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
    
    if ($action === 'add_address' || $action === 'edit_address') {
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $address_type = Security::sanitizeInput($_POST['address_type']);
        $full_name = Security::sanitizeInput($_POST['full_name']);
        $phone = Security::sanitizeInput($_POST['phone']);
        $address_line1 = Security::sanitizeInput($_POST['address_line1']);
        $address_line2 = Security::sanitizeInput($_POST['address_line2']);
        $city = Security::sanitizeInput($_POST['city']);
        $state = Security::sanitizeInput($_POST['state']);
        $postal_code = Security::sanitizeInput($_POST['postal_code']);
        $country = Security::sanitizeInput($_POST['country']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($full_name) || empty($phone) || empty($address_line1) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
            $error_message = 'Please fill in all required fields';
        } else {
            if ($is_default) {
                // Remove default flag from other addresses
                $update_default_sql = "UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?";
                $update_default_stmt = $conn->prepare($update_default_sql);
                $update_default_stmt->bind_param('i', $customer_id);
                $update_default_stmt->execute();
                $update_default_stmt->close();
            }
            
            if ($action === 'add_address') {
                $insert_sql = "INSERT INTO customer_addresses (customer_id, address_type, full_name, phone, address_line1, address_line2, city, state, postal_code, country, is_default) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param('isssssssssi', $customer_id, $address_type, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default);
                
                if ($insert_stmt->execute()) {
                    $success_message = 'Address added successfully';
                } else {
                    $error_message = 'Failed to add address';
                }
                $insert_stmt->close();
            } else {
                // Verify ownership before updating
                $verify_sql = "SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param('ii', $address_id, $customer_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if ($verify_result->num_rows > 0) {
                    $update_sql = "UPDATE customer_addresses SET address_type = ?, full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, is_default = ? 
                                   WHERE id = ? AND customer_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('sssssssssiisi', $address_type, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default, $address_id, $customer_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = 'Address updated successfully';
                    } else {
                        $error_message = 'Failed to update address';
                    }
                    $update_stmt->close();
                } else {
                    $error_message = 'Address not found';
                }
                $verify_stmt->close();
            }
        }
    } elseif ($action === 'delete_address') {
        $address_id = intval($_POST['address_id']);
        
        $delete_sql = "DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $address_id, $customer_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'Address deleted successfully';
        } else {
            $error_message = 'Failed to delete address';
        }
        $delete_stmt->close();
    } elseif ($action === 'set_default') {
        $address_id = intval($_POST['address_id']);
        
        // Remove default from all addresses
        $update_all_sql = "UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?";
        $update_all_stmt = $conn->prepare($update_all_sql);
        $update_all_stmt->bind_param('i', $customer_id);
        $update_all_stmt->execute();
        $update_all_stmt->close();
        
        // Set new default
        $update_sql = "UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND customer_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $address_id, $customer_id);
        
        if ($update_stmt->execute()) {
            $success_message = 'Default address updated';
        } else {
            $error_message = 'Failed to update default address';
        }
        $update_stmt->close();
    }
}

// Get all addresses
$addresses_sql = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$addresses_stmt = $conn->prepare($addresses_sql);
$addresses_stmt->bind_param('i', $customer_id);
$addresses_stmt->execute();
$addresses_result = $addresses_stmt->get_result();
$addresses = $addresses_result->fetch_all(MYSQLI_ASSOC);
$addresses_stmt->close();

$page_title = 'My Addresses';
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

.addresses-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.addresses-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.addresses-header-left h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.addresses-header-left p {
    margin: 0;
    opacity: 0.9;
}

.btn-add {
    background: white;
    color: #ff5900;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s;
    border: none;
    cursor: pointer;
}

.btn-add:hover {
    transform: translateY(-2px);
    color: #ff5900;
    text-decoration: none;
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

.addresses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.address-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    padding: 25px;
    position: relative;
    transition: all 0.3s;
}

.address-card:hover {
    border-color: #ff5900;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
}

.address-card.default {
    border-color: #ff5900;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.default-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.address-type {
    display: inline-block;
    padding: 6px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
    margin-bottom: 15px;
}

.address-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.address-phone {
    color: #ff5900;
    font-weight: 500;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.address-details {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.address-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    cursor: pointer;
}

.btn-edit {
    background: #ff5900;
    color: white;
}

.btn-edit:hover {
    background: #ff5900;
    color: white;
}

.btn-default {
    background: #f8f9fa;
    color: #ff5900;
    border: 1px solid #ff5900;
}

.btn-default:hover {
    background: #ff5900;
    color: white;
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

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: #fefefe;
    margin: 50px auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    animation: slideDown 0.3s;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
}

.close:hover {
    opacity: 0.8;
}

.modal-body {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ff5900;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    cursor: pointer;
}

.checkbox-group label {
    margin: 0;
    cursor: pointer;
}

.btn-submit {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 14px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .addresses-container {
        margin: 20px auto;
    }
    
    .addresses-header {
        padding: 30px 20px;
        border-radius: 15px;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .addresses-header-left h1 {
        font-size: 1.5rem;
    }
    
    .btn-add {
        width: 100%;
        justify-content: center;
    }
    
    .addresses-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .modal-header {
        padding: 20px;
    }
    
    .modal-body {
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
    <div class="addresses-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="addresses-header">
        <div class="addresses-header-left">
            <h1><i class="fas fa-map-marker-alt"></i> My Addresses</h1>
            <p>Manage your shipping and billing addresses</p>
        </div>
        <button class="btn-add" onclick="openModal()">
            <i class="fas fa-plus"></i> Add New Address
        </button>
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
    
    <?php if (count($addresses) > 0): ?>
        <div class="addresses-grid">
            <?php foreach ($addresses as $address): ?>
                <div class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                    <?php if ($address['is_default']): ?>
                        <div class="default-badge">
                            <i class="fas fa-star"></i> Default
                        </div>
                    <?php endif; ?>
                    
                    <div class="address-type">
                        <i class="fas fa-<?= $address['address_type'] === 'shipping' ? 'truck' : ($address['address_type'] === 'billing' ? 'credit-card' : 'map-marked-alt') ?>"></i>
                        <?= ucfirst($address['address_type']) ?>
                    </div>
                    
                    <div class="address-name"><?= esc_html($address['full_name']) ?></div>
                    <div class="address-phone">
                        <i class="fas fa-phone"></i> <?= esc_html($address['phone']) ?>
                    </div>
                    
                    <div class="address-details">
                        <?= esc_html($address['address_line1']) ?><br>
                        <?php if ($address['address_line2']): ?>
                            <?= esc_html($address['address_line2']) ?><br>
                        <?php endif; ?>
                        <?= esc_html($address['city']) ?>, <?= esc_html($address['state']) ?> <?= esc_html($address['postal_code']) ?><br>
                        <?= esc_html($address['country']) ?>
                    </div>
                    
                    <div class="address-actions">
                        <button class="btn-action btn-edit" onclick='editAddress(<?= json_encode($address) ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        
                        <?php if (!$address['is_default']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                <button type="submit" class="btn-action btn-default">
                                    <i class="fas fa-star"></i> Set Default
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <button class="btn-action btn-delete" onclick="deleteAddress(<?= $address['id'] ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-map-marker-alt"></i>
            <h3>No Addresses Yet</h3>
            <p>Add your first shipping or billing address to get started</p>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Add Your First Address
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Address Modal -->
<div id="addressModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Address</h2>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="addressForm">
                <input type="hidden" name="action" id="formAction" value="add_address">
                <input type="hidden" name="address_id" id="addressId">
                
                <div class="form-group">
                    <label for="address_type">Address Type *</label>
                    <select name="address_type" id="address_type" required>
                        <option value="shipping">Shipping Address</option>
                        <option value="billing">Billing Address</option>
                        <option value="both">Both Shipping & Billing</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" name="phone" id="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="address_line1">Address Line 1 *</label>
                    <input type="text" name="address_line1" id="address_line1" placeholder="Street address, P.O. box" required>
                </div>
                
                <div class="form-group">
                    <label for="address_line2">Address Line 2</label>
                    <input type="text" name="address_line2" id="address_line2" placeholder="Apartment, suite, unit, building, floor, etc.">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" name="city" id="city" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="state">State/Province *</label>
                        <input type="text" name="state" id="state" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="postal_code">Postal Code *</label>
                        <input type="text" name="postal_code" id="postal_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country *</label>
                        <select name="country" id="country" required>
                            <option value="Kenya">Kenya</option>
                            <option value="Tanzania">Tanzania</option>
                            <option value="Uganda">Uganda</option>
                            <option value="Rwanda">Rwanda</option>
                            <option value="Burundi">Burundi</option>
                            <option value="South Sudan">South Sudan</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="is_default" id="is_default" value="1">
                    <label for="is_default">Set as default address</label>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Save Address
                </button>
            </form>
        </div>
        </div>
    </main>
</div>
</div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_address">
    <input type="hidden" name="address_id" id="deleteAddressId">
</form>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'Add New Address';
    document.getElementById('formAction').value = 'add_address';
    document.getElementById('addressForm').reset();
    document.getElementById('addressId').value = '';
    document.getElementById('addressModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addressModal').style.display = 'none';
}

function editAddress(address) {
    document.getElementById('modalTitle').textContent = 'Edit Address';
    document.getElementById('formAction').value = 'edit_address';
    document.getElementById('addressId').value = address.id;
    document.getElementById('address_type').value = address.address_type;
    document.getElementById('full_name').value = address.full_name;
    document.getElementById('phone').value = address.phone;
    document.getElementById('address_line1').value = address.address_line1;
    document.getElementById('address_line2').value = address.address_line2 || '';
    document.getElementById('city').value = address.city;
    document.getElementById('state').value = address.state;
    document.getElementById('postal_code').value = address.postal_code;
    document.getElementById('country').value = address.country;
    document.getElementById('is_default').checked = address.is_default == 1;
    document.getElementById('addressModal').style.display = 'block';
}

function deleteAddress(addressId) {
    if (confirm('Are you sure you want to delete this address?')) {
        document.getElementById('deleteAddressId').value = addressId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addressModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

