<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Helper function to get settings
function getSetting($key, $default = '') {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['setting_value'] : $default;
}

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = Security::sanitizeInput($_POST['name']);
        $slug = Security::sanitizeInput($_POST['slug']);
        $sku = Security::sanitizeInput($_POST['sku']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $short_description = Security::sanitizeInput($_POST['short_description']);
        $description = $_POST['description']; // Allow HTML
        
        // Get price and input currency
        $input_price = (float)$_POST['price_kes'];
        $input_currency = Security::sanitizeInput($_POST['input_currency'] ?? 'KES');
        
        // Convert price to base currency if needed
        require_once __DIR__ . '/../includes/CurrencyDetector.php';
        $currencyDetector = CurrencyDetector::getInstance();
        $baseCurrency = $currencyDetector->getBaseCurrency();
        
        // Get exchange rates from database
        $exchange_rates = [
            'KES' => (float)getSetting('exchange_rate_kes', '1'),
            'TZS' => (float)getSetting('exchange_rate_tzs', '2860'),
            'UGX' => (float)getSetting('exchange_rate_ugx', '3900'),
            'USD' => (float)getSetting('exchange_rate_usd', '0.0077')
        ];
        
        // If input currency is different from base, convert it
        if ($input_currency !== $baseCurrency) {
            $inputRate = $exchange_rates[$input_currency];
            $baseRate = $exchange_rates[$baseCurrency]; // Should always be 1
            
            // Convert: input_price / input_rate * base_rate
            // Since base_rate is always 1: input_price / input_rate
            if ($inputRate > 0) {
                $price_kes = $input_price / $inputRate;
            } else {
                $price_kes = $input_price; // Fallback
            }
        } else {
            $price_kes = $input_price;
        }
        
        // Calculate all currency prices for storage
        $price_tzs = $price_kes * $exchange_rates['TZS'];
        
        $stock_quantity = (int)$_POST['stock_quantity'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image = Security::sanitizeInput($_POST['image'] ?? '');
        $video_url = Security::sanitizeInput($_POST['video_url'] ?? '');
        
        // Handle specifications JSON
        $specifications = [];
        if (!empty($_POST['specs'])) {
            foreach ($_POST['specs'] as $spec) {
                if (!empty($spec['name']) && !empty($spec['value'])) {
                    $specifications[] = [
                        'name' => Security::sanitizeInput($spec['name']),
                        'value' => Security::sanitizeInput($spec['value'])
                    ];
                }
            }
        }
        $specifications_json = json_encode($specifications);
        
        // Handle features JSON
        $features = [];
        if (!empty($_POST['features'])) {
            foreach ($_POST['features'] as $feature) {
                if (!empty($feature)) {
                    $features[] = Security::sanitizeInput($feature);
                }
            }
        }
        $features_json = json_encode($features);
        
        // Handle gallery images JSON
        $gallery_images = [];
        if (!empty($_POST['gallery_images'])) {
            $gallery_data = json_decode($_POST['gallery_images'], true);
            if (is_array($gallery_data)) {
                $gallery_images = $gallery_data;
            }
        }
        
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO products (name, slug, sku, category_id, short_description, description, specifications, features, price_kes, price_tzs, stock_quantity, is_featured, is_active, image, video_url, input_currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssissssddiisss', $name, $slug, $sku, $category_id, $short_description, $description, $specifications_json, $features_json, $price_kes, $price_tzs, $stock_quantity, $is_featured, $is_active, $image, $video_url, $baseCurrency);
            
            if ($stmt->execute()) {
                $product_id = $db->insert_id;
                
                // Save gallery images
                if (!empty($gallery_images)) {
                    $stmt_img = $db->prepare("INSERT INTO product_images (product_id, image_path, is_featured, sort_order, alt_text) VALUES (?, ?, ?, ?, ?)");
                    foreach ($gallery_images as $img) {
                        $image_path = $img['filename'];
                        $is_featured_img = isset($img['isFeatured']) ? ($img['isFeatured'] ? 1 : 0) : 0;
                        $sort_order = isset($img['sortOrder']) ? (int)$img['sortOrder'] : 0;
                        $alt_text = isset($img['altText']) ? Security::sanitizeInput($img['altText']) : '';
                        
                        $stmt_img->bind_param('isiss', $product_id, $image_path, $is_featured_img, $sort_order, $alt_text);
                        $stmt_img->execute();
                    }
                }
                
                $message = 'Product created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error creating product: ' . $db->error;
                $messageType = 'error';
            }
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE products SET name=?, slug=?, sku=?, category_id=?, short_description=?, description=?, specifications=?, features=?, price_kes=?, price_tzs=?, stock_quantity=?, is_featured=?, is_active=?, image=?, video_url=?, input_currency=? WHERE id=?");
            $stmt->bind_param('sssissssddiiisssi', $name, $slug, $sku, $category_id, $short_description, $description, $specifications_json, $features_json, $price_kes, $price_tzs, $stock_quantity, $is_featured, $is_active, $image, $video_url, $baseCurrency, $id);
            
            if ($stmt->execute()) {
                // Delete existing gallery images
                $db->query("DELETE FROM product_images WHERE product_id = $id");
                
                // Save new gallery images
                if (!empty($gallery_images)) {
                    $stmt_img = $db->prepare("INSERT INTO product_images (product_id, image_path, is_featured, sort_order, alt_text) VALUES (?, ?, ?, ?, ?)");
                    foreach ($gallery_images as $img) {
                        $image_path = $img['filename'];
                        $is_featured_img = isset($img['isFeatured']) ? ($img['isFeatured'] ? 1 : 0) : 0;
                        $sort_order = isset($img['sortOrder']) ? (int)$img['sortOrder'] : 0;
                        $alt_text = isset($img['altText']) ? Security::sanitizeInput($img['altText']) : '';
                        
                        $stmt_img->bind_param('isiss', $id, $image_path, $is_featured_img, $sort_order, $alt_text);
                        $stmt_img->execute();
                    }
                }
                
                $message = 'Product updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating product: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $message = 'Product deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting product: ' . $db->error;
            $messageType = 'error';
        }
    } elseif (strpos($action, 'bulk_') === 0) {
        // Handle bulk operations
        $bulk_action = substr($action, 5); // Remove 'bulk_' prefix
        $ids = explode(',', $_POST['ids']);
        $ids = array_map('intval', $ids); // Sanitize IDs
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $affected_rows = 0;
        
        switch ($bulk_action) {
            case 'activate':
                $stmt = $db->prepare("UPDATE products SET is_active = 1 WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                if ($stmt->execute()) {
                    $affected_rows = $stmt->affected_rows;
                    $message = "$affected_rows products activated successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'deactivate':
                $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                if ($stmt->execute()) {
                    $affected_rows = $stmt->affected_rows;
                    $message = "$affected_rows products deactivated successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'feature':
                $stmt = $db->prepare("UPDATE products SET is_featured = 1 WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                if ($stmt->execute()) {
                    $affected_rows = $stmt->affected_rows;
                    $message = "$affected_rows products marked as featured!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
                if ($stmt->execute()) {
                    $affected_rows = $stmt->affected_rows;
                    $message = "$affected_rows products deleted successfully!";
                    $messageType = 'success';
                }
                break;
                
            default:
                $message = 'Invalid bulk action';
                $messageType = 'error';
        }
        
        if ($affected_rows === 0 && $messageType !== 'error') {
            $message = 'No products were affected by this action';
            $messageType = 'warning';
        }
    } elseif ($action === 'duplicate') {
        // Handle product duplication
        $id = (int)$_POST['id'];
        
        // Get the original product
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $original = $stmt->get_result()->fetch_assoc();
        
        if ($original) {
            // Create duplicate with modified name and SKU
            $new_name = $original['name'] . ' (Copy)';
            $new_sku = $original['sku'] . '_COPY';
            $new_slug = $original['slug'] . '-copy';
            
            $stmt = $db->prepare("INSERT INTO products (name, slug, sku, category_id, short_description, description, specifications, features, price_kes, price_tzs, stock_quantity, is_featured, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())");
            $stmt->bind_param('sssississddi', 
                $new_name, $new_slug, $new_sku, $original['category_id'],
                $original['short_description'], $original['description'],
                $original['specifications'], $original['features'],
                $original['price_kes'], $original['price_tzs'], 
                $original['stock_quantity']
            );
            
            if ($stmt->execute()) {
                $message = 'Product duplicated successfully! (Set as inactive by default)';
                $messageType = 'success';
            } else {
                $message = 'Error duplicating product: ' . $db->error;
                $messageType = 'error';
            }
        } else {
            $message = 'Original product not found';
            $messageType = 'error';
        }
    }
}

// Get products with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Enhanced filtering
$search = Security::sanitizeInput($_GET['search'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$status_filter = Security::sanitizeInput($_GET['status'] ?? '');
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;

$where_conditions = ['1=1'];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(name LIKE ? OR sku LIKE ? OR short_description LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if ($category_filter) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($status_filter) {
    switch ($status_filter) {
        case 'active':
            $where_conditions[] = "is_active = 1";
            break;
        case 'inactive':
            $where_conditions[] = "is_active = 0";
            break;
        case 'featured':
            $where_conditions[] = "is_featured = 1";
            break;
        case 'low_stock':
            $where_conditions[] = "stock_quantity <= 5";
            break;
    }
}

if ($price_min > 0) {
    $where_conditions[] = "price_kes >= ?";
    $params[] = $price_min;
    $types .= 'd';
}

if ($price_max > 0) {
    $where_conditions[] = "price_kes <= ?";
    $params[] = $price_max;
    $types .= 'd';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where_clause ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);

// Add limit and offset to params
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$all_types = $types . 'ii';

if ($all_params) {
    $stmt->bind_param($all_types, ...$all_params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for dropdown
$categories_result = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get product for editing if ID is provided
$editing_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $editing_product = $stmt->get_result()->fetch_assoc();
    
    if ($editing_product) {
        $editing_product['specifications'] = $editing_product['specifications'] ? json_decode($editing_product['specifications'], true) : [];
        $editing_product['features'] = $editing_product['features'] ? json_decode($editing_product['features'], true) : [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <link href="css/alibaba-import.css" rel="stylesheet">
    <link href="css/ai-optimization.css" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Enhanced Header -->
            <header class="admin-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>📦 Product Management</h1>
                        <p class="header-subtitle">Manage your product catalog with advanced features</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="toggleBulkActions()">
                            <span class="icon">☑️</span> Bulk Actions
                        </button>
                        <button class="btn btn-success" onclick="exportProducts()">
                            <span class="icon">📊</span> Export
                        </button>
                        <button class="btn btn-primary" onclick="openProductModal()" title="Keyboard shortcut: Ctrl+N">
                            <span class="icon">➕</span> Add New Product
                        </button>
                        <span class="user-info">Welcome, <?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Currency Info Box -->
                <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-left: 4px solid #ff5900; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: start; gap: 1rem;">
                        <div style="font-size: 2rem;">💱</div>
                        <div style="flex: 1;">
                            <strong style="color: #ff5900; font-size: 1.05rem;">Currency System Active</strong>
                            <p style="color: #64748b; font-size: 0.875rem; margin: 0.5rem 0 0; line-height: 1.5;">
                                All product prices are stored in <strong style="color: #ff5900;"><?= $currencyDetector->getBaseCurrency() ?></strong> (base currency). 
                                When adding/editing products, you can enter prices in any supported currency and they'll be automatically converted. 
                                Customer-facing prices are dynamically converted based on their location or selection.
                                <a href="settings.php" style="color: #ff5900; text-decoration: underline; margin-left: 0.5rem;">Manage Currency Settings →</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Edit Product Form (Only shows when editing) -->
                <?php if ($editing_product): ?>
                <div class="card edit-form">
                    <div class="card-header">
                        <h3>✏️ Edit Product: <?= htmlspecialchars($editing_product['name']) ?></h3>
                        <a href="products.php" class="btn btn-secondary">
                            <span class="icon">❌</span> Cancel Edit
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="product-form">
                            <input type="hidden" name="action" value="<?= $editing_product ? 'update' : 'create' ?>">
                            <?php if ($editing_product): ?>
                                <input type="hidden" name="id" value="<?= $editing_product['id'] ?>">
                            <?php endif; ?>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Product Name *</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?= htmlspecialchars($editing_product['name'] ?? '') ?>"
                                           onkeyup="generateSlug(this.value)">
                                </div>

                                <div class="form-group">
                                    <label for="slug">URL Slug *</label>
                                    <input type="text" id="slug" name="slug" required 
                                           value="<?= htmlspecialchars($editing_product['slug'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="sku">SKU *</label>
                                    <input type="text" id="sku" name="sku" required 
                                           value="<?= htmlspecialchars($editing_product['sku'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= ($editing_product['category_id'] ?? 0) == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="short_description">Short Description</label>
                                <textarea id="short_description" name="short_description" rows="2"><?= htmlspecialchars($editing_product['short_description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="description">Full Description</label>
                                <textarea id="description" name="description" rows="6"><?= htmlspecialchars($editing_product['description'] ?? '') ?></textarea>
                            </div>

                            <!-- Pricing -->
                            <div class="form-section">
                                <h4>Pricing</h4>
                                <?php if (!$editing_product): ?>
                                <div style="background: #fff7ed; border-left: 4px solid #ff5900; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                    <strong style="color: #ff5900;">💱 Currency Conversion:</strong>
                                    <p style="color: #64748b; font-size: 0.875rem; margin: 0.5rem 0 0;">
                                        Select the currency you're entering the price in. It will be automatically converted to the base currency (<?= $currencyDetector->getBaseCurrency() ?>) for storage.
                                    </p>
                                </div>
                                <?php endif; ?>
                                <div class="form-grid">
                                    <?php if (!$editing_product): ?>
                                    <div class="form-group">
                                        <label for="input_currency">Input Currency *</label>
                                        <select id="input_currency" name="input_currency" onchange="updatePriceLabel()">
                                            <?php
                                            $baseCurrency = $currencyDetector->getBaseCurrency();
                                            $availableCurrencies = $currencyDetector->getAvailableCurrencies();
                                            foreach ($availableCurrencies as $code => $details):
                                            ?>
                                                <option value="<?= $code ?>" <?= $code === $baseCurrency ? 'selected' : '' ?>>
                                                    <?= $code ?> - <?= $details['name'] ?>
                                                    <?= $code === $baseCurrency ? ' (Base)' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small style="color: #64748b;">Currency you're entering the price in</small>
                                    </div>
                                    <?php else: ?>
                                    <input type="hidden" id="input_currency" name="input_currency" value="<?= $currencyDetector->getBaseCurrency() ?>">
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label for="price_kes">
                                            <?php if ($editing_product): ?>
                                                Price (<?= $currencyDetector->getBaseCurrency() ?>) *
                                            <?php else: ?>
                                                <span id="price_label">Price (<?= $currencyDetector->getBaseCurrency() ?>)</span> *
                                            <?php endif; ?>
                                        </label>
                                        <input type="number" step="0.01" id="price_kes" name="price_kes" required 
                                               value="<?= $editing_product['price_kes'] ?? '' ?>"
                                               placeholder="0.00">
                                        <small style="color: #64748b;">
                                            <?php if ($editing_product): ?>
                                                Enter price in base currency (<?= $currencyDetector->getBaseCurrency() ?>)
                                            <?php else: ?>
                                                <span id="conversion_note">Will be stored in base currency (<?= $currencyDetector->getBaseCurrency() ?>)</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="stock_quantity">Stock Quantity</label>
                                        <input type="number" id="stock_quantity" name="stock_quantity" 
                                               value="<?= $editing_product['stock_quantity'] ?? 0 ?>">
                                    </div>
                                </div>
                            </div>

                            <?php if (!$editing_product): ?>
                            <script>
                            function updatePriceLabel() {
                                const currencySelect = document.getElementById('input_currency');
                                const selectedCurrency = currencySelect.value;
                                const baseCurrency = '<?= $currencyDetector->getBaseCurrency() ?>';
                                const priceLabel = document.getElementById('price_label');
                                const conversionNote = document.getElementById('conversion_note');
                                
                                priceLabel.textContent = 'Price (' + selectedCurrency + ')';
                                
                                if (selectedCurrency === baseCurrency) {
                                    conversionNote.innerHTML = 'Base currency - no conversion needed';
                                } else {
                                    conversionNote.innerHTML = 'Will be converted from ' + selectedCurrency + ' to ' + baseCurrency + ' (base currency)';
                                }
                            }
                            
                            // Initialize on page load
                            document.addEventListener('DOMContentLoaded', updatePriceLabel);
                            </script>
                            <?php endif; ?>

                            <!-- Specifications -->
                            <div class="form-section">
                                <h4>Specifications</h4>
                                <div id="specifications-container">
                                    <?php if ($editing_product && $editing_product['specifications']): ?>
                                        <?php foreach ($editing_product['specifications'] as $index => $spec): ?>
                                            <div class="spec-row">
                                                <input type="text" name="specs[<?= $index ?>][name]" placeholder="Specification Name" 
                                                       value="<?= htmlspecialchars($spec['name']) ?>">
                                                <input type="text" name="specs[<?= $index ?>][value]" placeholder="Value" 
                                                       value="<?= htmlspecialchars($spec['value']) ?>">
                                                <button type="button" onclick="removeSpecRow(this)" class="btn btn-danger btn-sm">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="spec-row">
                                            <input type="text" name="specs[0][name]" placeholder="Specification Name">
                                            <input type="text" name="specs[0][value]" placeholder="Value">
                                            <button type="button" onclick="removeSpecRow(this)" class="btn btn-danger btn-sm">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick="addSpecRow()" class="btn btn-secondary btn-sm">Add Specification</button>
                            </div>

                            <!-- Features -->
                            <div class="form-section">
                                <h4>Features</h4>
                                <div id="features-container">
                                    <?php if ($editing_product && $editing_product['features']): ?>
                                        <?php foreach ($editing_product['features'] as $index => $feature): ?>
                                            <div class="feature-row">
                                                <input type="text" name="features[<?= $index ?>]" placeholder="Feature" 
                                                       value="<?= htmlspecialchars($feature) ?>">
                                                <button type="button" onclick="removeFeatureRow(this)" class="btn btn-danger btn-sm">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="feature-row">
                                            <input type="text" name="features[0]" placeholder="Feature">
                                            <button type="button" onclick="removeFeatureRow(this)" class="btn btn-danger btn-sm">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick="addFeatureRow()" class="btn btn-secondary btn-sm">Add Feature</button>
                            </div>

                            <!-- Status -->
                            <div class="form-section">
                                <h4>Status</h4>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_featured" value="1" 
                                               <?= ($editing_product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                        Featured Product
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?= ($editing_product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $editing_product ? 'Update Product' : 'Create Product' ?>
                                </button>
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Products List -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Products Catalog (<?= $total_products ?> total)</h3>
                        <div class="header-controls">
                            <div class="view-toggle">
                                <button class="btn btn-sm btn-secondary active" onclick="switchView('table')">📊 Table</button>
                                <button class="btn btn-sm btn-secondary" onclick="switchView('grid')">🔲 Grid</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Enhanced Filters -->
                        <form method="GET" class="filters-form enhanced-filters">
                            <div class="filters-row">
                                <div class="filter-group">
                                    <label>🔍 Search</label>
                                    <input type="text" name="search" placeholder="Search by name, SKU, or description..." 
                                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                                </div>
                                
                                <div class="filter-group">
                                    <label>📂 Category</label>
                                    <select name="category" class="filter-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label>📊 Status</label>
                                    <select name="status" class="filter-select">
                                        <option value="">All Status</option>
                                        <option value="active" <?= ($_GET['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($_GET['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="featured" <?= ($_GET['status'] ?? '') == 'featured' ? 'selected' : '' ?>>Featured</option>
                                        <option value="low_stock" <?= ($_GET['status'] ?? '') == 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label>💰 Price Range</label>
                                    <div class="price-range">
                                        <input type="number" name="price_min" placeholder="Min" value="<?= $_GET['price_min'] ?? '' ?>" class="price-input">
                                        <span>-</span>
                                        <input type="number" name="price_max" placeholder="Max" value="<?= $_GET['price_max'] ?? '' ?>" class="price-input">
                                    </div>
                                </div>
                                
                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="icon">🔍</span> Filter
                                    </button>
                                    <a href="products.php" class="btn btn-secondary">
                                        <span class="icon">🔄</span> Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Bulk Actions Bar -->
                        <div id="bulk-actions-bar" class="bulk-actions-bar" style="display: none;">
                            <div class="bulk-info">
                                <span id="selected-count">0</span> products selected
                            </div>
                            <div class="bulk-buttons">
                                <button class="btn btn-sm btn-success" onclick="bulkAction('activate')">✅ Activate</button>
                                <button class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">❌ Deactivate</button>
                                <button class="btn btn-sm btn-info" onclick="bulkAction('feature')">⭐ Feature</button>
                                <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">🗑️ Delete</button>
                            </div>
                        </div>

                        <!-- Enhanced Products Table -->
                        <div id="table-view" class="table-responsive">
                            <table class="admin-table enhanced-table">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                        </th>
                                        <th width="60">Image</th>
                                        <th>Product Details</th>
                                        <th width="100">SKU</th>
                                        <th width="120">Category</th>
                                        <th width="100">Price (KES)</th>
                                        <th width="80">Stock</th>
                                        <th width="100">Status</th>
                                        <th width="120">Last Updated</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products->num_rows > 0): ?>
                                        <?php while ($product = $products->fetch_assoc()): ?>
                                            <tr class="product-row">
                                                <td>
                                                    <input type="checkbox" class="product-checkbox" value="<?= $product['id'] ?>" onchange="updateBulkActions()">
                                                </td>
                                                <td>
                                                    <div class="product-image">
                                                        <img src="<?= !empty($product['image']) ? '../images/products/' . htmlspecialchars($product['image']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E' ?>" 
                                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="product-details">
                                                        <h4 class="product-name"><?= htmlspecialchars($product['name']) ?></h4>
                                                        <p class="product-description"><?= htmlspecialchars(substr($product['short_description'] ?? '', 0, 100)) ?>...</p>
                                                        <div class="product-meta">
                                                            <?php if ($product['is_featured']): ?>
                                                                <span class="meta-tag featured">⭐ Featured</span>
                                                            <?php endif; ?>
                                                            <span class="meta-tag">ID: <?= $product['id'] ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="sku-code"><?= htmlspecialchars($product['sku']) ?></code>
                                                </td>
                                                <td>
                                                    <span class="category-tag"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                                                </td>
                                                <td>
                                                    <div class="price-display">
                                                        <strong><?= $currencyDetector->getBaseCurrency() ?> <?= number_format($product['price_kes'], 0) ?></strong>
                                                        <small style="color: #64748b; display: block; font-size: 0.75rem;">Base Currency</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="stock-indicator">
                                                        <span class="stock-number <?= $product['stock_quantity'] <= 5 ? 'low-stock' : 'normal-stock' ?>">
                                                            <?= $product['stock_quantity'] ?>
                                                        </span>
                                                        <?php if ($product['stock_quantity'] <= 5): ?>
                                                            <span class="stock-warning">⚠️</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="status-indicators">
                                                        <?php if ($product['is_active']): ?>
                                                            <span class="status-badge active">✅ Active</span>
                                                        <?php else: ?>
                                                            <span class="status-badge inactive">❌ Inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="date-display"><?= date('M d, Y', strtotime($product['updated_at'] ?? $product['created_at'])) ?></span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons enhanced-actions">
                                                        <button class="btn btn-sm btn-primary" onclick="editProduct(<?= $product['id'] ?>)" title="Edit Product">
                                                            ✏️
                                                        </button>
                                                        <button class="btn btn-sm btn-info" onclick="viewProduct(<?= $product['id'] ?>)" title="View Details">
                                                            👁️
                                                        </button>
                                                        <button class="btn btn-sm btn-warning" onclick="duplicateProduct(<?= $product['id'] ?>)" title="Duplicate">
                                                            📋
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?= $product['id'] ?>)" title="Delete">
                                                            🗑️
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No products found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="products.php?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modern Product Modal -->
        <div id="product-modal" class="modal-overlay" style="display: none;">
            <div class="modal-content large-modal">
                <div class="modal-header">
                    <h3><span class="modal-icon">➕</span> Add New Product</h3>
                    <button onclick="closeProductModal()" class="btn btn-secondary modal-close">
                        <span class="icon">✕</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- Modal Navigation Tabs -->
                    <div class="modal-tabs">
                        <button class="tab-button active" onclick="switchModalTab('basic')">
                            <span class="tab-icon">📝</span> Basic Info
                        </button>
                        <button class="tab-button" onclick="switchModalTab('details')">
                            <span class="tab-icon">📋</span> Details
                        </button>
                        <button class="tab-button" onclick="switchModalTab('pricing')">
                            <span class="tab-icon">💰</span> Pricing & Inventory
                        </button>
                        <button class="tab-button" onclick="switchModalTab('features')">
                            <span class="tab-icon">⭐</span> Features & Specs
                        </button>
                        <button class="tab-button" onclick="switchModalTab('images')">
                            <span class="tab-icon">🖼️</span> Images & Media
                        </button>
                    </div>

                    <form id="modal-product-form" method="POST" class="modal-form">
                        <input type="hidden" name="action" value="create" id="modal_form_action">
                        <input type="hidden" name="id" value="" id="modal_product_id">
                        
                        <!-- Basic Information Tab -->
                        <div id="basic-tab" class="tab-content active">
                            <div class="tab-header">
                                <h4>📝 Basic Product Information</h4>
                                <p>Enter the essential details for your product</p>
                            </div>
                            
                            <!-- Alibaba Import Section -->
                            <div class="alibaba-import-section">
                                <div class="import-header">
                                    <h5>🌐 Quick Import from Alibaba</h5>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAlibabaImport()">
                                        <span id="import-toggle-text">Show Import</span>
                                    </button>
                                </div>
                                <div id="alibaba-import-form" style="display: none;">
                                    <div class="form-group">
                                        <label for="alibaba_url">Alibaba Product URL</label>
                                        <div class="url-input-group">
                                            <input type="text" id="alibaba_url" placeholder="https://www.alibaba.com/product-detail/..." class="url-input">
                                            <button type="button" onclick="fetchAlibabaProduct()" class="btn btn-primary">
                                                <span class="icon">🔍</span> Fetch Product
                                            </button>
                                        </div>
                                        <small class="form-hint">Paste an Alibaba.com product URL to auto-fill product details</small>
                                    </div>
                                    <div id="import-status" class="import-status"></div>
                                    <div id="import-preview" class="import-preview" style="display: none;">
                                        <h6>📦 Product Preview</h6>
                                        <div class="preview-content">
                                            <div class="preview-images">
                                                <div id="preview-images-grid" class="preview-grid"></div>
                                            </div>
                                            <div class="preview-details">
                                                <h4 id="preview-title"></h4>
                                                <p id="preview-description"></p>
                                                <div class="preview-meta">
                                                    <span id="preview-price"></span>
                                                    <span id="preview-specs"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-grid modal-grid">
                                <div class="form-group">
                                    <label for="modal_name">Product Name *</label>
                                    <input type="text" id="modal_name" name="name" required 
                                           placeholder="Enter product name..."
                                           onkeyup="generateModalSlug(this.value)">
                                    <small class="form-hint">This will be displayed as the product title</small>
                                </div>

                                <div class="form-group">
                                    <label for="modal_slug">URL Slug *</label>
                                    <input type="text" id="modal_slug" name="slug" required 
                                           placeholder="url-friendly-name">
                                    <small class="form-hint">Used in product URLs (auto-generated)</small>
                                </div>

                                <div class="form-group">
                                    <label for="modal_sku">SKU (Stock Keeping Unit) *</label>
                                    <input type="text" id="modal_sku" name="sku" required 
                                           placeholder="PROD-001">
                                    <small class="form-hint">Unique identifier for inventory tracking</small>
                                </div>

                                <div class="form-group">
                                    <label for="modal_category_id">Product Category</label>
                                    <div class="category-input-group">
                                        <select id="modal_category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="category-actions">
                                            <button type="button" class="btn btn-outline btn-sm" onclick="openCategoryModal()" title="Add New Category">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-outline btn-sm dropdown-toggle" onclick="toggleCategoryDropdown()" title="Category Management">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <div class="dropdown-menu" id="categoryDropdownMenu">
                                                    <a href="#" onclick="editSelectedCategory()">
                                                        <i class="fas fa-edit"></i>
                                                        Edit Selected Category
                                                    </a>
                                                    <a href="#" onclick="showCategoryList()">
                                                        <i class="fas fa-list"></i>
                                                        Manage All Categories
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a href="#" onclick="refreshCategoryDropdownFromMenu()">
                                                        <i class="fas fa-sync-alt"></i>
                                                        Refresh Categories
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="form-hint">Choose the best fitting category or add a new one</small>
                                </div>

                                <div class="form-group full-width">
                                    <label for="modal_product_image">Product Image</label>
                                    <input type="hidden" id="modal_image" name="image" value="">
                                    <div class="image-upload-container">
                                        <div class="current-image-preview" id="modal_current_image_preview" style="display: none;">
                                            <img id="modal_current_image" src="" alt="Current product image">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductImage()">
                                                <span class="icon">🗑️</span> Delete Image
                                            </button>
                                        </div>
                                        <div class="upload-area" id="modal_upload_area">
                                            <input type="file" id="modal_product_image" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.svg,.bmp,.heic,.heif,.jxl,.ico,image/*" onchange="handleImageUpload(this)">
                                            <div class="upload-placeholder">
                                                <span class="upload-icon">📷</span>
                                                <p>Click to upload product image</p>
                                                <small>Supports: JPG, PNG, GIF, WebP, AVIF, SVG, BMP, HEIC/HEIF, JXL, ICO (Max 10MB)</small>
                                            </div>
                                        </div>
                                        <div class="upload-progress" id="modal_upload_progress" style="display: none;">
                                            <div class="progress-bar">
                                                <div class="progress-fill" id="modal_progress_fill"></div>
                                            </div>
                                            <span class="progress-text" id="modal_progress_text">Uploading...</span>
                                        </div>
                                        <div class="media-picker-actions">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openLibraryForFeatured()">
                                                <span class="icon">📁</span> Choose from Media Library
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-hint">Upload a high-quality image (recommended: 1200x1200px)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div id="details-tab" class="tab-content">
                            <div class="tab-header">
                                <h4>📋 Product Details</h4>
                                <p>Provide comprehensive product descriptions</p>
                            </div>
                            
                            <!-- AI Optimization Section -->
                            <div class="ai-optimization-section">
                                <div class="ai-header">
                                    <h5>🤖 AI-Powered Optimization</h5>
                                    <div class="ai-badges">
                                        <span class="ai-badge deepseek">DeepSeek AI</span>
                                        <span class="ai-badge kimi">Kimi AI</span>
                                    </div>
                                </div>
                                <div class="ai-buttons">
                                    <button type="button" class="btn btn-sm btn-ai" onclick="optimizeWithAI('title')">
                                        <span class="icon">✨</span> Optimize Title
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ai" onclick="optimizeWithAI('description')">
                                        <span class="icon">📝</span> Optimize Description
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ai" onclick="optimizeWithAI('short_description')">
                                        <span class="icon">⚡</span> Generate Short Desc
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ai" onclick="optimizeWithAI('keywords')">
                                        <span class="icon">🔑</span> SEO Keywords
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ai-primary" onclick="optimizeWithAI('full')">
                                        <span class="icon">🚀</span> Full AI Optimization
                                    </button>
                                </div>
                                <div id="ai-status" class="ai-status"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="modal_short_description">Short Description</label>
                                <textarea id="modal_short_description" name="short_description" rows="3"
                                          placeholder="Brief product summary (appears in product lists)"></textarea>
                                <small class="form-hint">Keep it concise - this appears in search results</small>
                            </div>

                            <div class="form-group">
                                <label for="modal_description">Full Description</label>
                                <textarea id="modal_description" name="description" rows="6"
                                          placeholder="Detailed product description with features and benefits"></textarea>
                                <small class="form-hint">Provide comprehensive details for customers</small>
                            </div>
                            
                            <!-- SEO Keywords Display -->
                            <div id="seo-keywords-section" class="seo-keywords-section" style="display: none;">
                                <label>SEO Keywords</label>
                                <div id="seo-keywords-display" class="keywords-display"></div>
                                <small class="form-hint">These keywords help improve search visibility</small>
                            </div>
                            
                            <!-- Key Selling Points Display -->
                            <div id="selling-points-section" class="selling-points-section" style="display: none;">
                                <label>Key Selling Points</label>
                                <div id="selling-points-display" class="selling-points-display"></div>
                                <small class="form-hint">Highlight these in your marketing</small>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Tab -->
                        <div id="pricing-tab" class="tab-content">
                            <div class="tab-header">
                                <h4>💰 Pricing & Inventory Management</h4>
                                <p>Set prices and manage stock levels</p>
                            </div>
                            
                            <div style="background: #fff7ed; border-left: 4px solid #ff5900; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                                <strong style="color: #ff5900;">💱 Multi-Currency System:</strong>
                                <p style="color: #64748b; font-size: 0.875rem; margin: 0.5rem 0 0;">
                                    Select the currency you're entering the price in. The system will automatically convert it to the base currency (<?= $currencyDetector->getBaseCurrency() ?>) for storage. Prices are displayed to customers in their local currency based on IP detection or manual selection.
                                </p>
                            </div>
                            
                            <div class="form-grid modal-grid">
                                <div class="form-group">
                                    <label for="modal_input_currency">Input Currency *</label>
                                    <select id="modal_input_currency" name="input_currency" class="form-control" onchange="updateModalPriceLabel()">
                                        <?php
                                        $baseCurrency = $currencyDetector->getBaseCurrency();
                                        $availableCurrencies = $currencyDetector->getAvailableCurrencies();
                                        foreach ($availableCurrencies as $code => $details):
                                        ?>
                                            <option value="<?= $code ?>" <?= $code === $baseCurrency ? 'selected' : '' ?>>
                                                <?= $code ?> - <?= $details['name'] ?>
                                                <?= $code === $baseCurrency ? ' (Base Currency)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-hint">Currency you're entering the price in</small>
                                </div>

                                <div class="form-group">
                                    <label for="modal_price_kes">
                                        <span id="modal_price_label">Price (<?= $baseCurrency ?>)</span> *
                                    </label>
                                    <div class="input-with-currency">
                                        <span class="currency-symbol" id="modal_currency_symbol"><?= $baseCurrency ?></span>
                                        <input type="number" id="modal_price_kes" name="price_kes" step="0.01" 
                                               placeholder="0.00" required>
                                    </div>
                                    <small class="form-hint" id="modal_price_hint">
                                        Will be automatically converted to base currency (<?= $baseCurrency ?>)
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="modal_stock_quantity">Stock Quantity *</label>
                                    <input type="number" id="modal_stock_quantity" name="stock_quantity" 
                                           min="0" placeholder="0" required>
                                    <small class="form-hint">Current available inventory</small>
                                </div>

                                <div class="form-group">
                                    <label>Product Status</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="modal_is_active" name="is_active" checked>
                                            <span class="checkmark">✅</span> Active (visible to customers)
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="modal_is_featured" name="is_featured">
                                            <span class="checkmark">⭐</span> Featured (highlighted product)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <script>
                            function updateModalPriceLabel() {
                                const currencySelect = document.getElementById('modal_input_currency');
                                const selectedCurrency = currencySelect ? currencySelect.value : '<?= $baseCurrency ?>';
                                const baseCurrency = '<?= $baseCurrency ?>';
                                const priceLabel = document.getElementById('modal_price_label');
                                const currencySymbol = document.getElementById('modal_currency_symbol');
                                const priceHint = document.getElementById('modal_price_hint');
                                
                                if (priceLabel) priceLabel.textContent = 'Price (' + selectedCurrency + ')';
                                if (currencySymbol) currencySymbol.textContent = selectedCurrency;
                                
                                if (priceHint) {
                                    if (selectedCurrency === baseCurrency) {
                                        priceHint.innerHTML = 'Base currency - stored directly without conversion';
                                    } else {
                                        priceHint.innerHTML = 'Will be converted from ' + selectedCurrency + ' to ' + baseCurrency + ' (base currency)';
                                    }
                                }
                            }
                            
                            // Initialize on modal open
                            if (typeof window.modalPriceLabelInitialized === 'undefined') {
                                document.addEventListener('DOMContentLoaded', updateModalPriceLabel);
                                window.modalPriceLabelInitialized = true;
                            }
                            </script>
                        </div>

                        <!-- Features & Specifications Tab -->
                        <div id="features-tab" class="tab-content">
                            <div class="tab-header">
                                <h4>⭐ Features & Technical Specifications</h4>
                                <p>Add detailed product features and technical specs</p>
                            </div>
                            
                            <div class="specs-features-grid">
                                <div class="spec-section">
                                    <h5>🔧 Technical Specifications</h5>
                                    <div id="modal-specifications-container">
                                        <div class="spec-row">
                                            <input type="text" name="specs[0][name]" placeholder="Specification Name">
                                            <input type="text" name="specs[0][value]" placeholder="Value">
                                            <button type="button" onclick="removeModalSpecRow(this)" class="btn btn-danger btn-sm">
                                                🗑️
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addModalSpecRow()" class="btn btn-secondary btn-sm">
                                        ➕ Add Specification
                                    </button>
                                </div>

                                <div class="feature-section">
                                    <h5>⭐ Key Features</h5>
                                    <div id="modal-features-container">
                                        <div class="feature-row">
                                            <input type="text" name="features[0]" placeholder="Product feature">
                                            <button type="button" onclick="removeModalFeatureRow(this)" class="btn btn-danger btn-sm">
                                                🗑️
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addModalFeatureRow()" class="btn btn-secondary btn-sm">
                                        ➕ Add Feature
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Images & Media Tab -->
                        <div id="images-tab" class="tab-content">
                            <div class="tab-header">
                                <h4>🖼️ Product Gallery & Media</h4>
                                <p>Manage product images and add YouTube video</p>
                            </div>

                            <!-- YouTube Video -->
                            <div class="form-group">
                                <label for="modal_video_url">
                                    <span class="icon">🎥</span> YouTube Video URL (Optional)
                                </label>
                                <input type="url" id="modal_video_url" name="video_url" 
                                       placeholder="https://www.youtube.com/watch?v=..." 
                                       class="form-control">
                                <small class="form-hint">Paste YouTube video URL to embed product demo or review</small>
                                <div id="video-preview" class="video-preview" style="display: none;"></div>
                            </div>

                            <!-- Image Gallery -->
                            <div class="gallery-section">
                                <div class="gallery-header">
                                    <h5>📸 Product Images</h5>
                                    <div class="gallery-actions">
                                        <button type="button" onclick="triggerImageUpload()" class="btn btn-primary btn-sm">
                                            <span class="icon">➕</span> Upload Images
                                        </button>
                                        <button type="button" onclick="openLibraryForGallery()" class="btn btn-secondary btn-sm">
                                            <span class="icon">📁</span> Choose from Library
                                        </button>
                                    </div>
                                </div>
                                
                                <input type="file" id="gallery_image_input" accept="image/*" 
                                       multiple style="display: none;" onchange="handleGalleryUpload(this)">
                                
                                <div id="gallery-grid" class="gallery-grid">
                                    <!-- Imported images preview will appear here -->
                                    <div class="gallery-placeholder">
                                        <span class="icon">📷</span>
                                        <p>No images yet</p>
                                        <small>Upload images or import from Alibaba</small>
                                    </div>
                                </div>

                                <input type="hidden" id="gallery_images_data" name="gallery_images" value="">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeProductModal()" class="btn btn-secondary">
                        <span class="icon">❌</span> Cancel
                    </button>
                    <button type="button" onclick="submitModalForm()" class="btn btn-primary" id="modal_submit_btn">
                        <span class="icon">💾</span> <span id="modal_submit_text">Create Product</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let specCount = <?= $editing_product ? count($editing_product['specifications'] ?? []) : 1 ?>;
        let featureCount = <?= $editing_product ? count($editing_product['features'] ?? []) : 1 ?>;

        const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'heic', 'heif', 'jxl', 'ico'];
        const SUPPORTED_IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/svg+xml', 'image/bmp', 'image/heic', 'image/heif', 'image/jxl', 'image/x-icon', 'image/vnd.microsoft.icon'];
        const IMAGE_MAX_SIZE_BYTES = 10 * 1024 * 1024;

        function generateSlug(name) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('slug').value = slug;
        }

        function isSupportedImageFile(file) {
            if (!file) {
                return false;
            }
            const extension = (file.name || '').split('.').pop().toLowerCase();
            return SUPPORTED_IMAGE_EXTENSIONS.includes(extension) || SUPPORTED_IMAGE_MIME_TYPES.includes(file.type);
        }

        function normalizeAdminImagePath(path) {
            if (!path) {
                return '';
            }
            if (path.startsWith('http://') || path.startsWith('https://')) {
                return path;
            }
            const sanitized = path.replace(/^\.?\/?/, '');
            return '../' + sanitized;
        }

        function addSpecRow() {
            const container = document.getElementById('specifications-container');
            const div = document.createElement('div');
            div.className = 'spec-row';
            div.innerHTML = `
                <input type="text" name="specs[${specCount}][name]" placeholder="Specification Name">
                <input type="text" name="specs[${specCount}][value]" placeholder="Value">
                <button type="button" onclick="removeSpecRow(this)" class="btn btn-danger btn-sm">Remove</button>
            `;
            container.appendChild(div);
            specCount++;
        }

        function removeSpecRow(button) {
            button.parentElement.remove();
        }

        function addFeatureRow() {
            const container = document.getElementById('features-container');
            const div = document.createElement('div');
            div.className = 'feature-row';
            div.innerHTML = `
                <input type="text" name="features[${featureCount}]" placeholder="Feature">
                <button type="button" onclick="removeFeatureRow(this)" class="btn btn-danger btn-sm">Remove</button>
            `;
            container.appendChild(div);
            featureCount++;
        }

        function removeFeatureRow(button) {
            button.parentElement.remove();
        }

        // Enhanced Product Management Functions
        
        // Bulk Actions
        function toggleBulkActions() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const selectAll = document.getElementById('select-all');
            
            if (selectAll.checked) {
                checkboxes.forEach(cb => cb.checked = false);
                selectAll.checked = false;
            } else {
                checkboxes.forEach(cb => cb.checked = true);
                selectAll.checked = true;
            }
            updateBulkActions();
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const bulkBar = document.getElementById('bulk-actions-bar');
            const countSpan = document.getElementById('selected-count');
            
            if (checkboxes.length > 0) {
                bulkBar.style.display = 'flex';
                countSpan.textContent = checkboxes.length;
            } else {
                bulkBar.style.display = 'none';
            }
        }

        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('Please select products first');
                return;
            }
            
            let message = '';
            switch(action) {
                case 'activate':
                    message = `Activate ${ids.length} products?`;
                    break;
                case 'deactivate':
                    message = `Deactivate ${ids.length} products?`;
                    break;
                case 'feature':
                    message = `Feature ${ids.length} products?`;
                    break;
                case 'delete':
                    message = `Delete ${ids.length} products? This cannot be undone!`;
                    break;
            }
            
            if (confirm(message)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_${action}">
                    <input type="hidden" name="ids" value="${ids.join(',')}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // View Management
        function switchView(viewType) {
            const tableView = document.getElementById('table-view');
            const buttons = document.querySelectorAll('.view-toggle .btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            if (viewType === 'grid') {
                // TODO: Implement grid view
                alert('Grid view coming soon!');
            }
        }

        // Product Actions
        function editProduct(id) {
            // Open modal for editing
            openProductModal(id);
        }

        function viewProduct(id) {
            // Open product details modal
            openProductModal(id);
        }

        function duplicateProduct(id) {
            if (confirm('Duplicate this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="duplicate">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product? This cannot be undone!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Export Functions
        function exportProducts() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }

        function scrollToForm() {
            const form = document.querySelector('.edit-form');
            if (form) {
                form.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            } else {
                openProductModal();
            }
        }

        // Product Details Modal
        function openProductModal(id) {
            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Product Details</h3>
                        <button onclick="closeModal()" class="btn btn-secondary">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="loading">Loading product details...</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Load product details via AJAX (placeholder)
            setTimeout(() => {
                modal.querySelector('.loading').innerHTML = `
                    <p>Product ID: ${id}</p>
                    <p>Detailed view coming soon with full product information, specifications, and images.</p>
                `;
            }, 500);
        }

        function closeModal() {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }

        // Modal Management Functions
        let modalSpecCount = 1;
        let modalFeatureCount = 1;
        
        function openProductModal(productId = null) {
            const modal = document.getElementById('product-modal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Reset form
            document.getElementById('modal-product-form').reset();
            
            // Reset to first tab
            switchModalTab('basic');
            
            if (productId) {
                // Edit mode - load product data
                document.querySelector('.modal-header h3').textContent = '✏️ Edit Product';
                document.getElementById('modal_form_action').value = 'update';
                document.getElementById('modal_product_id').value = productId;
                document.getElementById('modal_submit_text').textContent = 'Save Changes';
                loadProductData(productId);
            } else {
                // Add new mode
                document.querySelector('.modal-header h3').textContent = '✨ Add New Product';
                document.getElementById('modal_form_action').value = 'create';
                document.getElementById('modal_product_id').value = '';
                document.getElementById('modal_submit_text').textContent = 'Create Product';
                
                // Reset image upload area
                document.getElementById('modal_current_image_preview').style.display = 'none';
                document.getElementById('modal_upload_area').style.display = 'block';
                document.getElementById('modal_image').value = '';
                document.getElementById('modal_product_image').value = '';
                
                // Focus on first input
                setTimeout(() => {
                    document.getElementById('modal_name').focus();
                }, 300);
            }
        }

        async function loadProductData(productId) {
            try {
                // Show loading indicator
                const modalBody = document.querySelector('.modal-body');
                const overlay = document.createElement('div');
                overlay.className = 'loading-overlay';
                overlay.innerHTML = '<div class="loading-spinner">Loading product data...</div>';
                overlay.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.9);display:flex;align-items:center;justify-content:center;z-index:1000;';
                modalBody.style.position = 'relative';
                modalBody.appendChild(overlay);

                // Fetch product data
                const response = await fetch(`get_product.php?id=${productId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const product = await response.json();
                
                console.log('Loaded product data:', product);
                
                if (product.error) {
                    console.error('Product error:', product.error);
                    alert('Error loading product: ' + product.error);
                    closeProductModal();
                    return;
                }

                // Helper function to safely set element value
                function safeSetValue(id, value) {
                    const element = document.getElementById(id);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = value == 1 || value === true;
                        } else {
                            element.value = value || '';
                        }
                    } else {
                        console.warn(`Element with id '${id}' not found`);
                    }
                }

                // Populate Basic Info tab
                safeSetValue('modal_name', product.name);
                safeSetValue('modal_slug', product.slug);
                safeSetValue('modal_sku', product.sku);
                safeSetValue('modal_category_id', product.category_id);
                safeSetValue('modal_is_active', product.is_active);
                safeSetValue('modal_is_featured', product.is_featured);

                // Load product image if exists
                if (product.image) {
                    safeSetValue('modal_image', product.image);
                    showImagePreview('images/products/' + product.image);
                } else {
                    // Reset image preview
                    const previewEl = document.getElementById('modal_current_image_preview');
                    const uploadEl = document.getElementById('modal_upload_area');
                    if (previewEl) previewEl.style.display = 'none';
                    if (uploadEl) uploadEl.style.display = 'block';
                    safeSetValue('modal_image', '');
                }

                // Populate Details tab
                safeSetValue('modal_short_description', product.short_description);
                safeSetValue('modal_description', product.description);
                safeSetValue('modal_meta_title', product.meta_title);
                safeSetValue('modal_meta_description', product.meta_description);
                safeSetValue('modal_keywords', product.keywords);

                // Populate Pricing & Inventory tab
                safeSetValue('modal_price_kes', product.price_kes);
                safeSetValue('modal_price_tzs', product.price_tzs);
                safeSetValue('modal_discount_price_kes', product.discount_price_kes);
                safeSetValue('modal_discount_price_tzs', product.discount_price_tzs);
                safeSetValue('modal_stock_quantity', product.stock_quantity);
                safeSetValue('modal_stock_status', product.stock_status);
                safeSetValue('modal_low_stock_threshold', product.low_stock_threshold);

                // Populate Features
                const featuresContainer = document.getElementById('modal-features-container');
                featuresContainer.innerHTML = '';
                if (product.features && product.features.length > 0) {
                    product.features.forEach((feature, index) => {
                        const div = document.createElement('div');
                        div.className = 'feature-row';
                        div.innerHTML = `
                            <input type="text" name="features[${index}]" placeholder="Product feature" value="${feature}">
                            <button type="button" onclick="removeModalFeatureRow(this)" class="btn btn-danger btn-sm">🗑️</button>
                        `;
                        featuresContainer.appendChild(div);
                    });
                    modalFeatureCount = product.features.length;
                } else {
                    modalFeatureCount = 0;
                }

                // Populate Specifications
                const specsContainer = document.getElementById('modal-specifications-container');
                specsContainer.innerHTML = '';
                if (product.specifications && product.specifications.length > 0) {
                    product.specifications.forEach((spec, index) => {
                        const div = document.createElement('div');
                        div.className = 'spec-row';
                        div.innerHTML = `
                            <input type="text" name="specs[${index}][name]" placeholder="Specification Name" value="${spec.name || ''}">
                            <input type="text" name="specs[${index}][value]" placeholder="Value" value="${spec.value || ''}">
                            <button type="button" onclick="removeModalSpecRow(this)" class="btn btn-danger btn-sm">🗑️</button>
                        `;
                        specsContainer.appendChild(div);
                    });
                    modalSpecCount = product.specifications.length;
                } else {
                    modalSpecCount = 0;
                }

                // Remove loading overlay
                overlay.remove();
                
                // Focus on first input
                setTimeout(() => {
                    document.getElementById('modal_name').focus();
                }, 100);

            } catch (error) {
                console.error('Error loading product:', error);
                console.error('Error details:', error.message, error.stack);
                alert('Error loading product data: ' + error.message + '\nCheck browser console for details.');
                closeProductModal();
            }
        }

        function closeProductModal() {
            const modal = document.getElementById('product-modal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function switchModalTab(tabName, element) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active to clicked button if element provided
            if (element) {
                element.classList.add('active');
            } else {
                // Find and activate the button for this tab
                const targetButton = document.querySelector(`[onclick*="${tabName}"]`);
                if (targetButton) {
                    targetButton.classList.add('active');
                }
            }
        }

        function generateModalSlug(name) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('modal_slug').value = slug;
        }

        function addModalSpecRow() {
            const container = document.getElementById('modal-specifications-container');
            const div = document.createElement('div');
            div.className = 'spec-row';
            div.innerHTML = `
                <input type="text" name="specs[${modalSpecCount}][name]" placeholder="Specification Name">
                <input type="text" name="specs[${modalSpecCount}][value]" placeholder="Value">
                <button type="button" onclick="removeModalSpecRow(this)" class="btn btn-danger btn-sm">🗑️</button>
            `;
            container.appendChild(div);
            modalSpecCount++;
        }

        function removeModalSpecRow(button) {
            button.parentElement.remove();
        }

        function addModalFeatureRow() {
            const container = document.getElementById('modal-features-container');
            const div = document.createElement('div');
            div.className = 'feature-row';
            div.innerHTML = `
                <input type="text" name="features[${modalFeatureCount}]" placeholder="Product feature">
                <button type="button" onclick="removeModalFeatureRow(this)" class="btn btn-danger btn-sm">🗑️</button>
            `;
            container.appendChild(div);
            modalFeatureCount++;
        }

        function removeModalFeatureRow(button) {
            button.parentElement.remove();
        }

        function submitModalForm() {
            const form = document.getElementById('modal-product-form');
            
            // Enhanced validation with visual feedback
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;
            
            // Clear previous validation messages
            document.querySelectorAll('.validation-error').forEach(el => el.remove());
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--admin-danger)';
                    field.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
                    
                    // Add error message
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'validation-error';
                    errorMsg.style.color = 'var(--admin-danger)';
                    errorMsg.style.fontSize = '0.8rem';
                    errorMsg.style.marginTop = '0.25rem';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.appendChild(errorMsg);
                    
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--admin-success)';
                    field.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.1)';
                }
            });
            
            // Additional validation
            const sku = form.querySelector('[name="sku"]').value;
            const priceKes = parseFloat(form.querySelector('[name="price_kes"]').value);
            
            if (sku && sku.length < 3) {
                const skuField = form.querySelector('[name="sku"]');
                skuField.style.borderColor = 'var(--admin-danger)';
                const errorMsg = document.createElement('div');
                errorMsg.className = 'validation-error';
                errorMsg.style.color = 'var(--admin-danger)';
                errorMsg.style.fontSize = '0.8rem';
                errorMsg.style.marginTop = '0.25rem';
                errorMsg.textContent = 'SKU must be at least 3 characters';
                skuField.parentNode.appendChild(errorMsg);
                isValid = false;
            }
            
            if (priceKes && priceKes <= 0) {
                const priceField = form.querySelector('[name="price_kes"]');
                priceField.style.borderColor = 'var(--admin-danger)';
                const errorMsg = document.createElement('div');
                errorMsg.className = 'validation-error';
                errorMsg.style.color = 'var(--admin-danger)';
                errorMsg.style.fontSize = '0.8rem';
                errorMsg.style.marginTop = '0.25rem';
                errorMsg.textContent = 'Price must be greater than 0';
                priceField.parentNode.appendChild(errorMsg);
                isValid = false;
            }
            
            if (!isValid) {
                // Show error notification
                showModalNotification('Please correct the highlighted fields', 'error');
                
                // Focus on first invalid field
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                return;
            }
            
            // Determine action
            const action = document.getElementById('modal_form_action').value;
            const isUpdate = action === 'update';
            const actionText = isUpdate ? 'Saving changes...' : 'Creating product...';
            const loadingText = isUpdate ? 'Saving Changes...' : 'Creating Product...';
            
            // Show success feedback
            showModalNotification(actionText, 'loading');
            
            // Show loading state
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner" style="width: 1rem; height: 1rem; margin-right: 0.5rem;"></span>${loadingText}`;
            
            // Submit form
            form.submit();
        }

        function showModalNotification(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.modal-notification').forEach(el => el.remove());
            
            const notification = document.createElement('div');
            notification.className = `modal-notification ${type}`;
            notification.innerHTML = message;
            
            const style = notification.style;
            style.position = 'fixed';
            style.top = '20px';
            style.right = '20px';
            style.padding = '1rem 1.5rem';
            style.borderRadius = 'var(--admin-border-radius)';
            style.zIndex = '10001';
            style.fontWeight = '500';
            style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.2)';
            style.animation = 'slideInRight 0.3s ease';
            
            if (type === 'error') {
                style.background = 'var(--admin-danger)';
                style.color = 'white';
            } else if (type === 'loading') {
                style.background = 'var(--admin-info)';
                style.color = 'white';
            } else {
                style.background = 'var(--admin-success)';
                style.color = 'white';
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds (except for loading)
            if (type !== 'loading') {
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }
        }

        // Alibaba Import Functions
        function toggleAlibabaImport() {
            const importForm = document.getElementById('alibaba-import-form');
            const toggleText = document.getElementById('import-toggle-text');
            
            if (importForm.style.display === 'none') {
                importForm.style.display = 'block';
                toggleText.textContent = 'Hide Import';
            } else {
                importForm.style.display = 'none';
                toggleText.textContent = 'Show Import';
            }
        }

        async function fetchAlibabaProduct() {
            const urlInput = document.getElementById('alibaba_url');
            const url = urlInput.value.trim();
            
            if (!url) {
                showImportStatus('error', '❌ Please enter an Alibaba URL');
                return;
            }
            
            if (!url.includes('alibaba.com')) {
                showImportStatus('error', '❌ Please enter a valid Alibaba.com URL');
                return;
            }
            
            // Show loading state with animation
            showImportStatus('loading', `
                <div class="import-loader">
                    <div class="loader-spinner"></div>
                    <div class="loader-text">
                        <strong>Importing from Alibaba.com</strong>
                        <p>Fetching product data...</p>
                    </div>
                </div>
            `);
            
            try {
                const formData = new FormData();
                formData.append('action', 'import');
                formData.append('url', url);
                
                const response = await fetch('alibaba_import_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                console.log('📦 Alibaba Import Response:', data);
                
                if (data.success && data.product) {
                    const product = data.product;
                    
                    // Update status with progress
                    if (product.downloaded_images && product.downloaded_images.length > 0) {
                        showImportStatus('loading', `
                            <div class="import-loader">
                                <div class="loader-spinner"></div>
                                <div class="loader-text">
                                    <strong>Processing Images</strong>
                                    <p>Downloaded ${product.downloaded_images.length} images. Optimizing...</p>
                                </div>
                            </div>
                        `);
                        
                        // Small delay for user feedback
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                    
                    // AI optimization status
                    if (product.ai_optimized) {
                        showImportStatus('loading', `
                            <div class="import-loader">
                                <div class="loader-spinner"></div>
                                <div class="loader-text">
                                    <strong>✨ AI Optimization</strong>
                                    <p>Enhancing product data for SEO...</p>
                                </div>
                            </div>
                        `);
                        
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                    
                    // Populate form
                    populateFormWithAlibabaData(product);
                    
                    // Show success with details
                    let successHTML = `
                        <div class="import-success">
                            <div class="success-icon">✅</div>
                            <div class="success-content">
                                <strong>Product Imported Successfully!</strong>
                                <div class="success-details">
                    `;
                    
                    if (product.ai_optimized) {
                        successHTML += `<span class="badge badge-ai">✨ AI Optimized</span>`;
                    }
                    if (product.category_confidence) {
                        const confidenceColor = product.category_confidence >= 80 ? '#10b981' : 
                                               product.category_confidence >= 60 ? '#f59e0b' : '#ef4444';
                        successHTML += `<span class="badge" style="background: ${confidenceColor};">
                            📂 ${product.suggested_category} (${product.category_confidence}% confidence)
                        </span>`;
                    }
                    if (product.downloaded_images && product.downloaded_images.length > 0) {
                        const imageCount = product.downloaded_images.length;
                        let qualityInfo = '';
                        
                        // Show image quality summary if available
                        if (product.image_quality_scores) {
                            const scores = Object.values(product.image_quality_scores);
                            const avgScore = scores.reduce((sum, s) => sum + s.overall_score, 0) / scores.length;
                            const grade = avgScore >= 90 ? 'A+' : avgScore >= 80 ? 'A' : avgScore >= 70 ? 'B' : avgScore >= 60 ? 'C' : 'D';
                            qualityInfo = ` (Quality: ${grade})`;
                        }
                        
                        successHTML += `<span class="badge badge-images">🖼️ ${imageCount} Images${qualityInfo}</span>`;
                    }
                    if (product.specifications && product.specifications.length > 0) {
                        successHTML += `<span class="badge badge-specs">📋 ${product.specifications.length} Specs</span>`;
                    }
                    if (product.key_features && product.key_features.length > 0) {
                        successHTML += `<span class="badge badge-features">⭐ ${product.key_features.length} Key Features</span>`;
                    }
                    if (product.price_suggestions) {
                        const recommendedTier = Object.values(product.price_suggestions).find(tier => tier.recommended);
                        if (recommendedTier) {
                            successHTML += `<span class="badge badge-price">💰 Suggested Price: $${recommendedTier.price}</span>`;
                        }
                    }
                    
                    successHTML += `
                                </div>
                                <p class="success-hint">Review and adjust the imported data before saving</p>
                            </div>
                        </div>
                    `;
                    
                    showImportStatus('success', successHTML);
                    
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        const statusDiv = document.getElementById('import-status');
                        if (statusDiv) {
                            statusDiv.style.display = 'none';
                        }
                    }, 5000);
                    
                } else {
                    showImportStatus('error', `
                        <div class="import-error">
                            <div class="error-icon">❌</div>
                            <div class="error-content">
                                <strong>Import Failed</strong>
                                <p>${data.error || 'Could not fetch product data from Alibaba'}</p>
                                <small>Please check the URL and try again</small>
                            </div>
                        </div>
                    `);
                }
            } catch (error) {
                console.error('Import error:', error);
                showImportStatus('error', `
                    <div class="import-error">
                        <div class="error-icon">⚠️</div>
                        <div class="error-content">
                            <strong>Network Error</strong>
                            <p>Failed to connect to import service</p>
                            <small>${error.message}</small>
                        </div>
                    </div>
                `);
            }
        }

        function populateFormWithAlibabaData(product) {
            console.log('📝 Populating form with product data:', product);
            
            // Basic Info Tab - Use AI optimized data if available
            const productName = product.optimized_name || product.name;
            if (productName) {
                document.getElementById('modal_name').value = productName;
                generateModalSlug(productName);
                console.log('✓ Name set:', productName);
            }
            
            // SKU - Generate if not available
            if (!document.getElementById('modal_sku').value) {
                const sku = 'JK-' + Date.now().toString().substr(-8);
                document.getElementById('modal_sku').value = sku;
                console.log('✓ SKU generated:', sku);
            }
            
            // Details Tab - Use AI optimized data
            const shortDesc = product.short_description || product.optimized_short_description || '';
            if (shortDesc) {
                document.getElementById('modal_short_description').value = shortDesc;
                console.log('✓ Short description set');
            }
            
            if (product.optimized_description || product.description) {
                const desc = product.optimized_description || product.description;
                document.getElementById('modal_description').value = desc;
            }
            
            // SEO Keywords - Display using the displaySEOKeywords function
            if (product.seo_keywords) {
                console.log('SEO Keywords found:', product.seo_keywords);
                displaySEOKeywords(product.seo_keywords);
            }
            
            // Selling Points - Display if available
            if (product.selling_points) {
                console.log('Selling Points found:', product.selling_points);
                displaySellingPoints(product.selling_points);
            }
            
            // Specifications
            if (product.specifications && product.specifications.length > 0) {
                const container = document.getElementById('modal-specifications-container');
                container.innerHTML = ''; // Clear existing
                
                product.specifications.forEach((spec, index) => {
                    const div = document.createElement('div');
                    div.className = 'spec-row';
                    div.innerHTML = `
                        <input type="text" name="specs[${index}][name]" placeholder="Specification Name" value="${escapeHtml(spec.name)}">
                        <input type="text" name="specs[${index}][value]" placeholder="Value" value="${escapeHtml(spec.value)}">
                        <button type="button" onclick="removeModalSpecRow(this)" class="btn btn-danger btn-sm">🗑️</button>
                    `;
                    container.appendChild(div);
                });
                
                modalSpecCount = product.specifications.length;
            }
            
            // Features - Use AI optimized features if available
            const features = product.optimized_features || product.features || [];
            if (features.length > 0) {
                const container = document.getElementById('modal-features-container');
                container.innerHTML = ''; // Clear existing
                
                features.forEach((feature, index) => {
                    const div = document.createElement('div');
                    div.className = 'feature-row';
                    div.innerHTML = `
                        <input type="text" name="features[${index}]" placeholder="Product feature" value="${escapeHtml(feature)}">
                        <button type="button" onclick="removeModalFeatureRow(this)" class="btn btn-danger btn-sm">🗑️</button>
                    `;
                    container.appendChild(div);
                });
                
                modalFeatureCount = features.length;
            }
            
            // Handle downloaded images - Get only selected images from checkboxes
            if (product.downloaded_images && product.downloaded_images.length > 0) {
                console.log('Setting up images:', product.downloaded_images);
                
                // Get selected images from checkboxes
                const selectedCheckboxes = document.querySelectorAll('.image-select-checkbox:checked');
                const selectedImages = Array.from(selectedCheckboxes).map(cb => cb.dataset.filename);
                
                // Get main image from radio button
                const mainImageRadio = document.querySelector('.image-main-radio:checked');
                const mainImageFilename = mainImageRadio ? mainImageRadio.dataset.filename : selectedImages[0];
                
                console.log('Selected images:', selectedImages);
                console.log('Main image:', mainImageFilename);
                
                if (selectedImages.length > 0) {
                    // Set primary image (from radio selection)
                    const imageInput = document.getElementById('modal_image');
                    const currentImageDisplay = document.getElementById('modal_current_image');
                    
                    if (imageInput) {
                        imageInput.value = mainImageFilename;
                        console.log('Primary image set to:', mainImageFilename);
                        
                        // Update the image preview
                        if (currentImageDisplay) {
                            currentImageDisplay.src = '../images/products/' + mainImageFilename;
                            currentImageDisplay.style.display = 'block';
                        }
                    }
                    
                    // Load selected images into gallery
                    const galleryImgs = selectedImages.map((filename, index) => ({
                        filename: filename,
                        path: `images/products/${filename}`,
                        isFeatured: filename === mainImageFilename,
                        sortOrder: filename === mainImageFilename ? 0 : index,
                        altText: product.optimized_name || product.name || ''
                    }));
                    
                    // Sort so main image is first
                    galleryImgs.sort((a, b) => a.sortOrder - b.sortOrder);
                    
                    loadGalleryImages(galleryImgs);
                    
                    // Show notification
                    if (product.ai_optimized) {
                        showModalNotification('🎨 Product imported with AI optimization & ' + selectedImages.length + ' selected images!', 'success');
                    } else {
                        showModalNotification('Product imported with ' + selectedImages.length + ' selected images!', 'success');
                    }
                } else {
                    showModalNotification('⚠️ No images selected. Please select images from the preview.', 'warning');
                }
            } else if (product.ai_optimized) {
                showModalNotification('✨ Product data imported and AI-optimized! Please add images and set prices.', 'success');
            } else {
                showModalNotification('Product data imported from Alibaba! Please review and adjust prices.', 'success');
            }
            
            // Switch to Basic Info tab to show populated fields
            setTimeout(() => {
                switchModalTab('basic');
            }, 500);
            
            // Log completion
            console.log('✅ Product populated successfully:', {
                name: document.getElementById('modal_name').value,
                short_description: document.getElementById('modal_short_description').value,
                specs_count: product.specifications?.length || 0,
                features_count: (product.optimized_features || product.features || []).length,
                images_count: product.downloaded_images?.length || 0
            });
        }
        
        function showImagePreview(images) {
            // Create or update image preview section
            let previewDiv = document.getElementById('imported-images-preview');
            if (!previewDiv) {
                previewDiv = document.createElement('div');
                previewDiv.id = 'imported-images-preview';
                previewDiv.className = 'imported-images-preview';
                
                const imagesSection = document.querySelector('.tab-pane#modal-images');
                if (imagesSection) {
                    imagesSection.insertBefore(previewDiv, imagesSection.firstChild);
                }
            }
            
            previewDiv.innerHTML = '<h4>📥 Imported Images</h4><div class="image-grid">';
            
            images.forEach((filename, index) => {
                const imgPath = '../images/products/' + filename;
                previewDiv.innerHTML += `
                    <div class="image-preview-item">
                        <img src="${imgPath}" alt="Product Image ${index + 1}">
                        <span class="image-label">${index === 0 ? 'Primary' : 'Image ' + (index + 1)}</span>
                    </div>
                `;
            });
            
            previewDiv.innerHTML += '</div>';
        }

        function showImportStatus(type, message) {
            const statusDiv = document.getElementById('import-status');
            statusDiv.className = 'import-status ' + type;
            statusDiv.innerHTML = message;
            statusDiv.style.display = 'block';
            
            // Auto-hide success/error messages after 5 seconds
            if (type !== 'loading') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 5000);
            }
        }
        
        function showImportPreview(product) {
            const previewDiv = document.getElementById('import-preview');
            const imagesGrid = document.getElementById('preview-images-grid');
            const titleEl = document.getElementById('preview-title');
            const descEl = document.getElementById('preview-description');
            const priceEl = document.getElementById('preview-price');
            const specsEl = document.getElementById('preview-specs');
            
            // Show preview container
            previewDiv.style.display = 'block';
            
            // Display product title
            const productName = product.optimized_name || product.name || 'Product';
            titleEl.textContent = productName;
            
            // Display description (truncated)
            const description = product.optimized_short_description || product.short_description || product.description || '';
            descEl.textContent = description.substring(0, 200) + (description.length > 200 ? '...' : '');
            
            // Display price
            if (product.price) {
                priceEl.innerHTML = `<strong>Price:</strong> ${product.price}`;
                priceEl.style.display = 'inline-block';
            } else {
                priceEl.style.display = 'none';
            }
            
            // Display specs count
            const specsCount = product.specifications?.length || 0;
            const featuresCount = (product.optimized_features || product.features || []).length;
            specsEl.innerHTML = `<strong>Data:</strong> ${specsCount} specs, ${featuresCount} features`;
            
            // Display images with selection - only first image (featured) selected by default
            imagesGrid.innerHTML = '';
            if (product.downloaded_images && product.downloaded_images.length > 0) {
                product.downloaded_images.forEach((filename, index) => {
                    const imgPath = '../images/products/' + filename;
                    const imgDiv = document.createElement('div');
                    imgDiv.className = 'preview-image-item selectable';
                    // Only select the first image (featured) by default
                    const isFirstImage = index === 0;
                    imgDiv.innerHTML = `
                        <input type="checkbox" class="image-select-checkbox" 
                               data-filename="${filename}" 
                               data-index="${index}" 
                               ${isFirstImage ? 'checked' : ''} 
                               id="img-select-${index}">
                        <input type="radio" class="image-main-radio" 
                               name="main-image" 
                               data-filename="${filename}" 
                               data-index="${index}" 
                               ${isFirstImage ? 'checked' : ''} 
                               id="img-main-${index}"
                               title="Set as main image">
                        <label for="img-select-${index}" class="image-label">
                            <img src="${imgPath}" alt="Product ${index + 1}" loading="lazy">
                            <span class="checkbox-indicator">✓</span>
                        </label>
                        <label for="img-main-${index}" class="main-indicator" title="Set as main image">
                            <span class="star-icon">★</span>
                        </label>
                    `;
                    imagesGrid.appendChild(imgDiv);
                });
                
                // Add change listener for checkboxes
                document.querySelectorAll('.image-select-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const radio = this.parentElement.querySelector('.image-main-radio');
                        if (!this.checked && radio.checked) {
                            // If unchecking the main image, select the first checked image as main
                            const firstChecked = document.querySelector('.image-select-checkbox:checked');
                            if (firstChecked) {
                                const firstRadio = firstChecked.parentElement.querySelector('.image-main-radio');
                                firstRadio.checked = true;
                            }
                        }
                    });
                });
                
                // Add change listener for main image radio
                document.querySelectorAll('.image-main-radio').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const checkbox = this.parentElement.querySelector('.image-select-checkbox');
                        if (this.checked && !checkbox.checked) {
                            // Auto-check the checkbox when setting as main
                            checkbox.checked = true;
                        }
                    });
                });
            } else if (product.images && product.images.length > 0) {
                // Show remote images if local not available
                product.images.slice(0, 5).forEach((imageUrl, index) => {
                    const imgDiv = document.createElement('div');
                    imgDiv.className = 'preview-image-item';
                    imgDiv.innerHTML = `
                        <img src="${imageUrl}" alt="Product ${index + 1}" loading="lazy">
                        ${index === 0 ? '<span class="primary-badge">Primary</span>' : ''}
                    `;
                    imagesGrid.appendChild(imgDiv);
                });
            } else {
                imagesGrid.innerHTML = '<p class="no-images">No images available</p>';
            }
            
            // Store product data for later use
            window.currentImportProduct = product;
        }

        function showImportStatus(type, message) {
            const statusDiv = document.getElementById('import-status');
            statusDiv.className = 'import-status ' + type;
            statusDiv.innerHTML = message;
            
            // Auto-hide success/error messages after 5 seconds
            if (type !== 'loading') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 5000);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // AI Optimization Functions
        async function optimizeWithAI(type) {
            const statusDiv = document.getElementById('ai-status');
            
            // Gather current product data
            const productData = {
                name: document.getElementById('modal_name').value,
                description: document.getElementById('modal_description').value,
                short_description: document.getElementById('modal_short_description').value,
                category_id: document.getElementById('modal_category_id').value,
                specifications: getSpecifications(),
                features: getFeatures()
            };
            
            // Get category name if selected
            let categoryName = '';
            const categorySelect = document.getElementById('modal_category_id');
            if (categorySelect.value) {
                categoryName = categorySelect.options[categorySelect.selectedIndex].text;
            }
            
            // Validate we have enough data
            if (!productData.name) {
                showAIStatus('error', '<span class="status-icon">❌</span> Please enter a product name first');
                return;
            }
            
            let action, statusMessage;
            
            switch(type) {
                case 'title':
                    action = 'optimize_title';
                    statusMessage = 'Optimizing product title with DeepSeek AI...';
                    break;
                case 'description':
                    action = 'optimize_description';
                    statusMessage = 'Optimizing description for SEO with DeepSeek AI...';
                    if (!productData.description) {
                        showAIStatus('error', '<span class="status-icon">❌</span> Please enter a description first');
                        return;
                    }
                    break;
                case 'short_description':
                    action = 'generate_short_description';
                    statusMessage = 'Generating short description with Kimi AI...';
                    if (!productData.description) {
                        showAIStatus('error', '<span class="status-icon">❌</span> Please enter a full description first');
                        return;
                    }
                    break;
                case 'keywords':
                    action = 'generate_keywords';
                    statusMessage = 'Generating SEO keywords with Kimi AI...';
                    if (!productData.description) {
                        showAIStatus('error', '<span class="status-icon">❌</span> Please enter a description first');
                        return;
                    }
                    break;
                case 'full':
                    action = 'full_optimization';
                    statusMessage = 'Running full AI optimization with DeepSeek & Kimi AI...';
                    if (!productData.description) {
                        showAIStatus('error', '<span class="status-icon">❌</span> Please enter product name and description first');
                        return;
                    }
                    break;
                default:
                    return;
            }
            
            // Show loading
            showAIStatus('loading', `<span class="ai-loading"></span> ${statusMessage}`);
            
            try {
                const requestData = {
                    action: action,
                    ...productData,
                    title: productData.name,
                    category: categoryName,
                    product: productData
                };
                
                const response = await fetch('ai_optimize.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    applyAIOptimizations(type, data);
                    showAIStatus('success', '<span class="status-icon">✅</span> AI optimization completed successfully!');
                    
                    // Auto-hide success message after 3 seconds
                    setTimeout(() => {
                        statusDiv.style.display = 'none';
                    }, 3000);
                } else {
                    showAIStatus('error', '<span class="status-icon">❌</span> ' + (data.message || 'AI optimization failed'));
                }
            } catch (error) {
                console.error('AI Error:', error);
                showAIStatus('error', '<span class="status-icon">❌</span> Network error. Please try again.');
            }
        }

        function applyAIOptimizations(type, data) {
            switch(type) {
                case 'title':
                    if (data.optimized_title) {
                        document.getElementById('modal_name').value = data.optimized_title;
                        document.getElementById('modal_name').classList.add('ai-optimized');
                        generateModalSlug(data.optimized_title);
                        showModalNotification('Product title optimized!', 'success');
                    }
                    break;
                    
                case 'description':
                    if (data.optimized_description) {
                        document.getElementById('modal_description').value = data.optimized_description;
                        document.getElementById('modal_description').classList.add('ai-optimized');
                        showModalNotification('Description optimized for SEO!', 'success');
                    }
                    break;
                    
                case 'short_description':
                    if (data.short_description) {
                        document.getElementById('modal_short_description').value = data.short_description;
                        document.getElementById('modal_short_description').classList.add('ai-optimized');
                        showModalNotification('Short description generated!', 'success');
                    }
                    break;
                    
                case 'keywords':
                    if (data.keywords) {
                        displaySEOKeywords(data.keywords);
                        showModalNotification('SEO keywords generated!', 'success');
                    }
                    break;
                    
                case 'full':
                    if (data.optimizations) {
                        const opt = data.optimizations;
                        
                        // Apply title
                        if (opt.title) {
                            document.getElementById('modal_name').value = opt.title;
                            document.getElementById('modal_name').classList.add('ai-optimized');
                            generateModalSlug(opt.title);
                        }
                        
                        // Apply description
                        if (opt.description) {
                            document.getElementById('modal_description').value = opt.description;
                            document.getElementById('modal_description').classList.add('ai-optimized');
                        }
                        
                        // Apply short description
                        if (opt.short_description) {
                            document.getElementById('modal_short_description').value = opt.short_description;
                            document.getElementById('modal_short_description').classList.add('ai-optimized');
                        }
                        
                        // Display keywords
                        if (opt.keywords) {
                            displaySEOKeywords(opt.keywords);
                        }
                        
                        // Display selling points
                        if (opt.selling_points && opt.selling_points.length > 0) {
                            displaySellingPoints(opt.selling_points);
                        }
                        
                        // Apply features
                        if (opt.features && opt.features.length > 0) {
                            applyAIFeatures(opt.features);
                        }
                        
                        showModalNotification('🚀 Full AI optimization complete! Review all fields.', 'success');
                    }
                    break;
            }
        }

        function displaySEOKeywords(keywords) {
            const section = document.getElementById('seo-keywords-section');
            const display = document.getElementById('seo-keywords-display');
            
            section.style.display = 'block';
            
            // Parse keywords (might be comma-separated string or array)
            let keywordArray = [];
            if (typeof keywords === 'string') {
                keywordArray = keywords.split(',').map(k => k.trim()).filter(k => k);
            } else if (Array.isArray(keywords)) {
                keywordArray = keywords;
            }
            
            // Display as tags
            display.innerHTML = keywordArray.map(keyword => 
                `<span class="keyword-tag">🔑 ${escapeHtml(keyword)}</span>`
            ).join('');
        }

        function displaySellingPoints(points) {
            const section = document.getElementById('selling-points-section');
            const display = document.getElementById('selling-points-display');
            
            section.style.display = 'block';
            
            display.innerHTML = points.map(point => 
                `<div class="selling-point">${escapeHtml(point)}</div>`
            ).join('');
        }

        function applyAIFeatures(features) {
            const container = document.getElementById('modal-features-container');
            container.innerHTML = '';
            
            features.forEach((feature, index) => {
                const div = document.createElement('div');
                div.className = 'feature-row';
                div.innerHTML = `
                    <input type="text" name="features[${index}]" placeholder="Product feature" value="${escapeHtml(feature)}">
                    <button type="button" onclick="removeModalFeatureRow(this)" class="btn btn-danger btn-sm">🗑️</button>
                `;
                container.appendChild(div);
            });
            
            modalFeatureCount = features.length;
        }

        function getSpecifications() {
            const specs = [];
            document.querySelectorAll('#modal-specifications-container .spec-row').forEach(row => {
                const inputs = row.querySelectorAll('input');
                if (inputs[0].value && inputs[1].value) {
                    specs.push({
                        name: inputs[0].value,
                        value: inputs[1].value
                    });
                }
            });
            return specs;
        }

        function getFeatures() {
            const features = [];
            document.querySelectorAll('#modal-features-container .feature-row input').forEach(input => {
                if (input.value) {
                    features.push(input.value);
                }
            });
            return features;
        }

        function showAIStatus(type, message) {
            const statusDiv = document.getElementById('ai-status');
            statusDiv.className = 'ai-status ' + type;
            statusDiv.innerHTML = message;
        }

        // Close modal on outside click
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('product-modal');
            if (event.target === modal) {
                closeProductModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Close modal on Escape
            if (event.key === 'Escape') {
                const modal = document.getElementById('product-modal');
                if (modal.style.display === 'flex') {
                    closeProductModal();
                }
            }
            
            // Open modal with Ctrl+N
            if (event.ctrlKey && event.key === 'n') {
                event.preventDefault();
                openProductModal();
            }
            
            // Submit modal form with Ctrl+Enter
            if (event.ctrlKey && event.key === 'Enter') {
                const modal = document.getElementById('product-modal');
                if (modal.style.display === 'flex') {
                    event.preventDefault();
                    submitModalForm();
                }
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for checkboxes
            document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });
            
            // Auto-refresh every 5 minutes to update stock status
            setInterval(() => {
                const currentUrl = window.location.href;
                if (!currentUrl.includes('edit=')) {
                    // Only refresh if not editing
                    console.log('Auto-refreshing product data...');
                }
            }, 300000); // 5 minutes

            const librarySearch = document.getElementById('mediaLibrarySearch');
            librarySearch?.addEventListener('input', () => applyMediaLibraryFilters());

            const libraryFormat = document.getElementById('mediaLibraryFormat');
            libraryFormat?.addEventListener('change', () => applyMediaLibraryFilters());

            const libraryModal = document.getElementById('mediaLibraryModal');
            libraryModal?.addEventListener('click', (event) => {
                if (event.target === libraryModal) {
                    closeMediaLibrary();
                }
            });
        });

        // ========================================
        // Image Upload System
        // ========================================

        async function handleImageUpload(input) {
            const file = input.files[0];
            if (!file) return;

            if (!isSupportedImageFile(file)) {
                alert('Unsupported image format. Accepted formats: ' + SUPPORTED_IMAGE_EXTENSIONS.join(', ').toUpperCase());
                input.value = '';
                return;
            }

            if (file.size > IMAGE_MAX_SIZE_BYTES) {
                alert('File is too large. Maximum size is 10MB.');
                input.value = '';
                return;
            }

            // Show upload progress
            const uploadArea = document.getElementById('modal_upload_area');
            const progressContainer = document.getElementById('modal_upload_progress');
            const progressFill = document.getElementById('modal_progress_fill');
            const progressText = document.getElementById('modal_progress_text');

            uploadArea.style.display = 'none';
            progressContainer.style.display = 'block';
            progressText.textContent = 'Uploading...';

            // Create form data
            const formData = new FormData();
            formData.append('image', file);

            try {
                // Simulate progress
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    if (progress <= 90) {
                        progressFill.style.width = progress + '%';
                    }
                }, 100);

                // Upload image
                const response = await fetch('upload_image.php', {
                    method: 'POST',
                    body: formData
                });

                clearInterval(progressInterval);

                const result = await response.json();

                if (result.success) {
                    // Complete progress
                    progressFill.style.width = '100%';
                    progressText.textContent = 'Upload complete!';

                    // Update hidden field with filename
                    document.getElementById('modal_image').value = result.filename;

                    const existingIndex = findGalleryImageIndex(result.filename);
                    if (existingIndex === -1) {
                        galleryImages.forEach(img => img.isFeatured = false);
                        galleryImages.unshift({
                            filename: result.filename,
                            path: result.path,
                            isFeatured: true,
                            sortOrder: 0,
                            altText: '',
                            deleteOnRemove: true,
                            fromLibrary: false
                        });
                    } else {
                        galleryImages[existingIndex].isFeatured = true;
                        galleryImages[existingIndex].path = result.path;
                        galleryImages[existingIndex].deleteOnRemove = true;
                    }

                    ensureGallerySortOrder();
                    updateGalleryDisplay();
                    updateGalleryHiddenField();

                    // Show preview
                    setTimeout(() => {
                        showImagePreview(result.path);
                        progressContainer.style.display = 'none';
                    }, 500);
                } else {
                    throw new Error(result.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Failed to upload image: ' + error.message);
                
                // Reset upload area
                progressContainer.style.display = 'none';
                uploadArea.style.display = 'block';
                input.value = '';
            }
        }

        function showImagePreview(imagePath) {
            const previewContainer = document.getElementById('modal_current_image_preview');
            const previewImage = document.getElementById('modal_current_image');
            const uploadArea = document.getElementById('modal_upload_area');

            previewImage.src = normalizeAdminImagePath(imagePath);
            previewContainer.style.display = 'flex';
            uploadArea.style.display = 'none';
        }

        async function deleteProductImage() {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            const filename = document.getElementById('modal_image').value;
            if (!filename) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('filename', filename);

                const response = await fetch('upload_image.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Clear preview and reset form
                    document.getElementById('modal_current_image_preview').style.display = 'none';
                    document.getElementById('modal_upload_area').style.display = 'block';
                    document.getElementById('modal_image').value = '';
                    document.getElementById('modal_product_image').value = '';
                    
                    alert('Image deleted successfully!');
                } else {
                    throw new Error(result.message || 'Delete failed');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Failed to delete image: ' + error.message);
            }
        }

        // ===========================
        // GALLERY MANAGEMENT FUNCTIONS
        // ===========================
        
        let galleryImages = []; // Array to store {filename, isFeatured, sortOrder, altText}

        function findGalleryImageIndex(filename) {
            if (!filename) {
                return -1;
            }
            return galleryImages.findIndex(img => (img.filename || img.image_path) === filename);
        }

        function ensureGallerySortOrder() {
            galleryImages.forEach((img, idx) => {
                img.sortOrder = idx;
            });
        }

        function setFeaturedImageByFilename(filename) {
            const index = findGalleryImageIndex(filename);
            if (index !== -1) {
                setFeaturedImage(index);
            }
        }

        function deleteGalleryImageByFilename(filename) {
            const index = findGalleryImageIndex(filename);
            if (index !== -1) {
                deleteGalleryImage(index);
            }
        }
        
        function triggerImageUpload() {
            document.getElementById('gallery_image_input').click();
        }
        
        async function handleGalleryUpload(input) {
            const files = Array.from(input.files);
            if (files.length === 0) return;
            
            for (const file of files) {
                if (!isSupportedImageFile(file)) {
                    alert(`${file.name}: Unsupported file format. Accepted formats: ${SUPPORTED_IMAGE_EXTENSIONS.join(', ').toUpperCase()}`);
                    continue;
                }
                
                if (file.size > IMAGE_MAX_SIZE_BYTES) {
                    alert(`${file.name}: File too large. Maximum size is 10MB.`);
                    continue;
                }
                
                // Upload file
                const formData = new FormData();
                formData.append('image', file);
                
                try {
                    const response = await fetch('upload_image.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (findGalleryImageIndex(result.filename) !== -1) {
                            continue;
                        }

                        // Add to gallery
                        const newImage = {
                            filename: result.filename,
                            path: result.path,
                            isFeatured: galleryImages.length === 0, // First image is featured
                            sortOrder: galleryImages.length,
                            altText: '',
                            deleteOnRemove: true,
                            fromLibrary: false
                        };
                        
                        galleryImages.push(newImage);
                        updateGalleryDisplay();
                        updateGalleryHiddenField();
                        
                        // Update primary image field if this is the first image
                        if (galleryImages.length === 1) {
                            document.getElementById('modal_image').value = result.filename;
                            showImagePreview(result.path);
                        }
                    } else {
                        alert(`Failed to upload ${file.name}: ${result.message}`);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert(`Failed to upload ${file.name}: ${error.message}`);
                }
            }
            
            // Reset input
            input.value = '';
        }
        
        function updateGalleryDisplay() {
            const grid = document.getElementById('gallery-grid');
            
            if (galleryImages.length === 0) {
                grid.innerHTML = `
                    <div class="gallery-placeholder">
                        <span class="icon">📷</span>
                        <p>No images yet</p>
                        <small>Upload images or import from Alibaba</small>
                    </div>
                `;
                return;
            }
            
            // Sort by sortOrder
            galleryImages.sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0));
            const sorted = [...galleryImages];
            
            grid.innerHTML = sorted.map((img, index) => {
                const filename = img.filename || '';
                const imageSrc = normalizeAdminImagePath(img.path || ('images/products/' + filename));
                const altTextAttr = escapeHtml(img.altText || 'Product image');
                const filenameAttr = escapeHtml(filename);

                return `
                    <div class="gallery-item" data-index="${index}" data-filename="${filenameAttr}" draggable="true">
                        <img src="${imageSrc}" alt="${altTextAttr}">
                        <div class="gallery-item-overlay">
                            ${img.isFeatured ? '<span class="featured-badge">⭐ Featured</span>' : ''}
                            <div class="gallery-item-actions">
                                ${!img.isFeatured ? `<button type="button" data-filename="${filenameAttr}" onclick="setFeaturedImageByFilename(this.dataset.filename)" class="btn-gallery-action" title="Set as featured">
                                    <span>⭐</span>
                                </button>` : ''}
                                <button type="button" data-filename="${filenameAttr}" onclick="deleteGalleryImageByFilename(this.dataset.filename)" class="btn-gallery-action" title="Delete">
                                    <span>🗑️</span>
                                </button>
                            </div>
                        </div>
                        <div class="gallery-item-handle">⋮⋮</div>
                    </div>
                `;
            }).join('');
            
            // Add drag and drop functionality
            setupDragAndDrop();
        }
        
        function setFeaturedImage(index) {
            const selected = galleryImages[index];
            if (!selected) {
                return;
            }

            galleryImages.forEach(img => {
                img.isFeatured = false;
            });

            selected.isFeatured = true;

            galleryImages.splice(index, 1);
            galleryImages.unshift(selected);

            ensureGallerySortOrder();

            document.getElementById('modal_image').value = selected.filename;
            showImagePreview(selected.path || 'images/products/' + selected.filename);

            updateGalleryDisplay();
            updateGalleryHiddenField();
        }
        
        async function deleteGalleryImage(index) {
            if (!confirm('Delete this image from the gallery?')) return;
            
            const img = galleryImages[index];
            if (!img) {
                return;
            }

            const shouldDeleteFromServer = !!img.deleteOnRemove;
            const updateAfterRemoval = () => {
                galleryImages.splice(index, 1);

                if (img.isFeatured && galleryImages.length > 0) {
                    galleryImages[0].isFeatured = true;
                    document.getElementById('modal_image').value = galleryImages[0].filename;
                    showImagePreview(galleryImages[0].path || 'images/products/' + galleryImages[0].filename);
                } else if (galleryImages.length === 0) {
                    document.getElementById('modal_image').value = '';
                    const preview = document.getElementById('modal_current_image_preview');
                    const uploadArea = document.getElementById('modal_upload_area');
                    if (preview) preview.style.display = 'none';
                    if (uploadArea) uploadArea.style.display = 'block';
                }

                ensureGallerySortOrder();
                updateGalleryDisplay();
                updateGalleryHiddenField();
            };

            if (!shouldDeleteFromServer) {
                updateAfterRemoval();
                showModalNotification('Image removed from gallery', 'success');
                return;
            }
            
            try {
                // Delete from server
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('filename', img.filename);
                
                console.log('Deleting image:', img.filename);
                
                const response = await fetch('upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status, response.statusText);
                
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first
                const text = await response.text();
                console.log('Response text:', text);
                
                // Check if response is empty
                if (!text || text.trim() === '') {
                    throw new Error('Server returned empty response');
                }
                
                // Try to parse as JSON
                let result;
                try {
                    result = JSON.parse(text);
                    console.log('Parsed result:', result);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid JSON. Check console for details.');
                }
                
                if (result.success || result.message?.includes('not found')) {
                    updateAfterRemoval();
                    showModalNotification('Image deleted successfully', 'success');
                } else {
                    throw new Error(result.error || result.message || 'Failed to delete image');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Failed to delete image: ' + error.message);
            }
        }
        
        function setupDragAndDrop() {
            const items = document.querySelectorAll('.gallery-item');
            
            items.forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
                item.addEventListener('dragend', handleDragEnd);
            });
        }
        
        let draggedIndex = null;
        
        function handleDragStart(e) {
            draggedIndex = parseInt(this.dataset.index);
            this.style.opacity = '0.5';
        }
        
        function handleDragOver(e) {
            e.preventDefault();
            return false;
        }
        
        function handleDrop(e) {
            e.preventDefault();
            const dropIndex = parseInt(this.dataset.index);
            
            if (draggedIndex !== dropIndex) {
                // Reorder array
                const sorted = [...galleryImages].sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0));
                const [draggedItem] = sorted.splice(draggedIndex, 1);
                sorted.splice(dropIndex, 0, draggedItem);

                galleryImages = sorted;
                ensureGallerySortOrder();

                updateGalleryDisplay();
                updateGalleryHiddenField();
            }
            
            return false;
        }
        
        function handleDragEnd(e) {
            this.style.opacity = '1';
            draggedIndex = null;
        }
        
        function updateGalleryHiddenField() {
            const serialized = galleryImages.map(img => ({
                filename: img.filename,
                isFeatured: !!img.isFeatured,
                sortOrder: img.sortOrder ?? 0,
                altText: img.altText || ''
            }));

            document.getElementById('gallery_images_data').value = JSON.stringify(serialized);
        }
        
        function loadGalleryImages(images) {
            if (!images || !Array.isArray(images)) return;
            
            galleryImages = images.map((img, index) => ({
                filename: img.filename || img.image_path || img,
                path: (() => {
                    const filename = img.filename || img.image_path || img;
                    let path = img.path || img.url || img.image_path || filename;
                    if (!path) {
                        return `images/products/${filename}`;
                    }
                    if (path.startsWith('http://') || path.startsWith('https://')) {
                        return path;
                    }
                    path = path.replace(/^\.?\/+/, '');
                    if (!path.startsWith('images/')) {
                        path = `images/products/${path}`;
                    }
                    return path;
                })(),
                isFeatured: img.isFeatured || img.is_featured || index === 0,
                sortOrder: img.sortOrder || img.sort_order || index,
                altText: img.altText || img.alt_text || '',
                deleteOnRemove: false,
                fromLibrary: false
            }));
            
            ensureGallerySortOrder();
            updateGalleryDisplay();
            updateGalleryHiddenField();
        }

        const mediaLibraryState = {
            images: [],
            filtered: [],
            selected: new Set(),
            mode: 'multiple',
            onSelect: () => {},
            loading: false,
            previousOverflow: ''
        };

        async function loadMediaLibraryData(force = false) {
            if (!force && mediaLibraryState.images.length > 0) {
                return;
            }

            mediaLibraryState.loading = true;
            renderMediaLibrary();

            try {
                const response = await fetch('media_library.php');
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Unable to load media library');
                }

                mediaLibraryState.images = Array.isArray(result.images) ? result.images : [];
            } catch (error) {
                console.error('Media library load error:', error);
                alert('Failed to load media library: ' + error.message);
                mediaLibraryState.images = [];
            } finally {
                mediaLibraryState.loading = false;
            }
        }

        function renderMediaLibrary() {
            const grid = document.getElementById('mediaLibraryGrid');
            const loader = document.getElementById('mediaLibraryLoader');
            const empty = document.getElementById('mediaLibraryEmpty');

            if (!grid || !loader || !empty) {
                return;
            }

            if (mediaLibraryState.loading) {
                loader.style.display = 'block';
                empty.style.display = 'none';
                grid.innerHTML = '';
                return;
            }

            loader.style.display = 'none';

            if (!mediaLibraryState.filtered || mediaLibraryState.filtered.length === 0) {
                empty.style.display = 'block';
                grid.innerHTML = '';
                return;
            }

            empty.style.display = 'none';

            grid.innerHTML = mediaLibraryState.filtered.map(image => {
                const isSelected = mediaLibraryState.selected.has(image.filename);
                const owner = image.product_name ? image.product_name : (image.orphaned ? 'Orphaned file' : 'Unassigned');
                const metaParts = [];
                if (image.extension) {
                    metaParts.push(image.extension.toUpperCase());
                }
                if (image.size_label) {
                    metaParts.push(image.size_label);
                }
                const meta = metaParts.join(' • ');
                const filenameAttr = escapeHtml(image.filename || '');
                const ownerAttr = escapeHtml(owner);
                const metaAttr = escapeHtml(meta);
                const imageSrc = getMediaLibraryImageSrc(image);

                return `
                    <div class="media-library-card ${isSelected ? 'selected' : ''}" data-filename="${filenameAttr}" onclick="toggleMediaLibrarySelection(this.dataset.filename)">
                        <img src="${imageSrc}" alt="${filenameAttr}">
                        ${image.is_featured ? '<span class="media-library-featured">Featured</span>' : ''}
                        ${image.orphaned ? '<span class="media-library-orphaned">Orphaned</span>' : ''}
                        <div class="media-library-card-info">
                            <strong title="${filenameAttr}">${filenameAttr}</strong>
                            <span>${ownerAttr}</span>
                            ${meta ? `<span>${metaAttr}</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function getMediaLibraryImageSrc(image) {
            const rawPath = image.url || image.path || `images/products/${image.filename}`;
            return normalizeAdminImagePath(rawPath);
        }

        function applyMediaLibraryFilters() {
            const searchValue = (document.getElementById('mediaLibrarySearch')?.value || '').toLowerCase();
            const formatValue = (document.getElementById('mediaLibraryFormat')?.value || '').toLowerCase();

            const libraryImages = Array.isArray(mediaLibraryState.images) ? mediaLibraryState.images : [];

            mediaLibraryState.filtered = libraryImages.filter(image => {
                const extension = (image.extension || '').toLowerCase();
                const filename = (image.filename || '').toLowerCase();
                const productName = (image.product_name || '').toLowerCase();
                const matchesFormat = !formatValue || extension === formatValue || (formatValue === 'jpg' && (extension === 'jpg' || extension === 'jpeg'));
                const haystack = `${filename} ${productName}`.trim();
                const matchesSearch = !searchValue || haystack.includes(searchValue);
                return matchesFormat && matchesSearch;
            });

            renderMediaLibrary();
            updateMediaLibrarySelectionCount();
        }

        function updateMediaLibrarySelectionCount() {
            const count = mediaLibraryState.selected.size;
            const countLabel = document.getElementById('mediaLibrarySelectionCount');
            if (countLabel) {
                countLabel.textContent = `${count} selected`;
            }

            const confirmBtn = document.getElementById('mediaLibraryConfirmBtn');
            if (confirmBtn) {
                if (mediaLibraryState.mode === 'single') {
                    confirmBtn.disabled = count !== 1;
                } else {
                    confirmBtn.disabled = count === 0;
                }
            }
        }

        function toggleMediaLibrarySelection(filename) {
            if (!filename) {
                return;
            }

            if (mediaLibraryState.mode === 'single') {
                mediaLibraryState.selected.clear();
                mediaLibraryState.selected.add(filename);
            } else {
                if (mediaLibraryState.selected.has(filename)) {
                    mediaLibraryState.selected.delete(filename);
                } else {
                    mediaLibraryState.selected.add(filename);
                }
            }

            renderMediaLibrary();
            updateMediaLibrarySelectionCount();
        }

        async function openMediaLibrary(options = {}) {
            const modal = document.getElementById('mediaLibraryModal');
            if (!modal) {
                return;
            }

            mediaLibraryState.mode = options.mode === 'single' ? 'single' : 'multiple';
            mediaLibraryState.onSelect = typeof options.onSelect === 'function' ? options.onSelect : () => {};

            const normalizedPreselect = (options.preselect || [])
                .map(value => {
                    if (!value) {
                        return '';
                    }
                    const parts = value.toString().split('/');
                    const filename = parts[parts.length - 1];
                    return filename.trim();
                })
                .filter(Boolean);

            mediaLibraryState.selected = new Set(normalizedPreselect);

            const confirmBtn = document.getElementById('mediaLibraryConfirmBtn');
            if (confirmBtn) {
                confirmBtn.textContent = mediaLibraryState.mode === 'single' ? 'Use Image' : 'Use Selected';
            }

            modal.classList.add('open');
            mediaLibraryState.previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

            const shouldForceLoad = options.force === true;
            await loadMediaLibraryData(shouldForceLoad || mediaLibraryState.images.length === 0);
            applyMediaLibraryFilters();

            updateMediaLibrarySelectionCount();
        }

        function closeMediaLibrary() {
            const modal = document.getElementById('mediaLibraryModal');
            if (modal) {
                modal.classList.remove('open');
            }
            document.body.style.overflow = mediaLibraryState.previousOverflow || '';
        }

        function confirmMediaLibrarySelection() {
            if (mediaLibraryState.selected.size === 0) {
                alert('Select at least one image to continue.');
                return;
            }

            const selectedImages = mediaLibraryState.images.filter(image => mediaLibraryState.selected.has(image.filename));
            const payload = mediaLibraryState.mode === 'single' ? selectedImages.slice(0, 1) : selectedImages;

            mediaLibraryState.onSelect(payload);
            closeMediaLibrary();
        }

        function openLibraryForFeatured() {
            const current = document.getElementById('modal_image').value;
            openMediaLibrary({
                mode: 'single',
                preselect: current ? [current] : [],
                force: true,
                onSelect: (images) => {
                    const selected = images[0];
                    if (!selected) {
                        return;
                    }

                    const filename = selected.filename;
                    const existingIndex = findGalleryImageIndex(filename);

                    if (existingIndex === -1) {
                        galleryImages.unshift({
                            filename,
                            path: selected.url,
                            isFeatured: true,
                            sortOrder: 0,
                            altText: selected.alt_text || '',
                            deleteOnRemove: false,
                            fromLibrary: true
                        });
                        ensureGallerySortOrder();
                    } else {
                        const item = galleryImages[existingIndex];
                        item.isFeatured = true;
                        item.path = selected.url;
                        item.fromLibrary = true;
                        item.deleteOnRemove = false;
                    }

                    setFeaturedImageByFilename(filename);
                }
            });
        }

        function openLibraryForGallery() {
            const preselect = galleryImages.map(img => img.filename);
            openMediaLibrary({
                mode: 'multiple',
                preselect,
                force: true,
                onSelect: (images) => {
                    let added = 0;
                    images.forEach(image => {
                        if (findGalleryImageIndex(image.filename) !== -1) {
                            return;
                        }

                        galleryImages.push({
                            filename: image.filename,
                            path: image.url,
                            isFeatured: false,
                            sortOrder: galleryImages.length,
                            altText: image.alt_text || '',
                            deleteOnRemove: false,
                            fromLibrary: true
                        });
                        added += 1;
                    });

                    if (added === 0) {
                        return;
                    }

                    ensureGallerySortOrder();

                    if (!galleryImages.some(img => img.isFeatured) && galleryImages.length > 0) {
                        setFeaturedImage(0);
                    } else {
                        updateGalleryDisplay();
                        updateGalleryHiddenField();
                    }
                }
            });
        }
        
        // YouTube video preview
        document.getElementById('modal_video_url')?.addEventListener('blur', function() {
            const url = this.value.trim();
            if (!url) {
                document.getElementById('video-preview').style.display = 'none';
                return;
            }
            
            // Extract YouTube video ID
            const videoId = extractYouTubeID(url);
            if (videoId) {
                const previewDiv = document.getElementById('video-preview');
                previewDiv.innerHTML = `
                    <iframe width="100%" height="315" 
                            src="https://www.youtube.com/embed/${videoId}" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                `;
                previewDiv.style.display = 'block';
            } else {
                alert('Invalid YouTube URL. Please enter a valid YouTube video link.');
                this.value = '';
            }
        });
        
        function extractYouTubeID(url) {
            const patterns = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/,
                /youtube\.com\/embed\/([^&\s]+)/
            ];
            
            for (const pattern of patterns) {
                const match = url.match(pattern);
                if (match) return match[1];
            }
            
            return null;
        }

        // Category Management Functions
        function openCategoryModal(categoryId = null) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('categoryModalTitle');
            const saveText = document.getElementById('categorySaveText');
            const form = document.getElementById('categoryForm');

            // Reset form
            form.reset();
            document.getElementById('category_id').value = '';
            document.getElementById('category_is_active').checked = true;

            if (categoryId) {
                // Edit mode
                title.textContent = 'Edit Category';
                saveText.textContent = 'Update Category';
                loadCategoryData(categoryId);
            } else {
                // Create mode
                title.textContent = 'Add New Category';
                saveText.textContent = 'Create Category';
            }

            modal.style.display = 'flex';
            document.getElementById('category_name').focus();

            // Auto-generate slug when name changes
            document.getElementById('category_name').addEventListener('input', function() {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                document.getElementById('category_slug').value = slug;
            });
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        async function loadCategoryData(categoryId) {
            try {
                const response = await fetch(`get_categories.php?id=${categoryId}`, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success && result.category) {
                    const category = result.category;
                    document.getElementById('category_id').value = category.id;
                    document.getElementById('category_name').value = category.name;
                    document.getElementById('category_slug').value = category.slug || '';
                    document.getElementById('category_description').value = category.description || '';
                    document.getElementById('category_is_active').checked = category.is_active == 1;
                } else {
                    showNotification('❌ Failed to load category data', 'error');
                }
            } catch (error) {
                console.error('Failed to load category:', error);
                showNotification('❌ Failed to load category data', 'error');
            }
        }

        async function saveCategoryForm(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const modal = document.getElementById('categoryModal');
            const saveButton = form.querySelector('button[type="submit"]');
            
            // Add loading state
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            try {
                const response = await fetch('category_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showNotification('✅ Category saved successfully!', 'success');
                    
                    // Refresh category dropdown
                    await refreshCategoryDropdown();
                    
                    // Select the new/updated category
                    if (result.category_id) {
                        document.getElementById('modal_category_id').value = result.category_id;
                    }
                    
                    closeCategoryModal();
                } else {
                    showNotification('❌ Error: ' + (result.error || 'Failed to save category'), 'error');
                }
            } catch (error) {
                console.error('Category save error:', error);
                showNotification('❌ Network error. Please try again.', 'error');
            } finally {
                // Reset button state
                saveButton.disabled = false;
                saveButton.innerHTML = document.getElementById('categorySaveText').textContent;
            }
        }

        async function refreshCategoryDropdown() {
            try {
                const response = await fetch('get_categories.php', {
                    credentials: 'include'
                });
                const categories = await response.json();
                
                const select = document.getElementById('modal_category_id');
                const currentValue = select.value;
                
                // Clear existing options except the first one
                select.innerHTML = '<option value="">Select Category</option>';
                
                // Add updated categories
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                });
                
                // Restore selection if it still exists
                if (currentValue) {
                    select.value = currentValue;
                }
            } catch (error) {
                console.error('Failed to refresh categories:', error);
            }
        }

        // Category Dropdown Management Functions
        function toggleCategoryDropdown() {
            const dropdown = document.getElementById('categoryDropdownMenu');
            dropdown.classList.toggle('show');
            
            // Close dropdown when clicking outside
            if (dropdown.classList.contains('show')) {
                document.addEventListener('click', closeCategoryDropdownOnClickOutside);
            } else {
                document.removeEventListener('click', closeCategoryDropdownOnClickOutside);
            }
        }

        function closeCategoryDropdownOnClickOutside(event) {
            const dropdown = document.getElementById('categoryDropdownMenu');
            const dropdownButton = event.target.closest('.dropdown-toggle');
            
            if (!dropdownButton && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
                document.removeEventListener('click', closeCategoryDropdownOnClickOutside);
            }
        }

        function editSelectedCategory() {
            const categorySelect = document.getElementById('modal_category_id');
            const selectedCategoryId = categorySelect.value;
            
            if (!selectedCategoryId) {
                showNotification('⚠️ Please select a category to edit first', 'warning');
                return;
            }
            
            // Close the dropdown
            document.getElementById('categoryDropdownMenu').classList.remove('show');
            
            // Open category modal in edit mode
            openCategoryModal(selectedCategoryId);
        }

        function showCategoryList() {
            // Close the dropdown
            document.getElementById('categoryDropdownMenu').classList.remove('show');
            
            // For now, we'll show a simple list in a modal
            // In a full implementation, this could navigate to a dedicated category management page
            showCategoryManagementModal();
        }

        function refreshCategoryDropdownFromMenu() {
            // Close the dropdown
            document.getElementById('categoryDropdownMenu').classList.remove('show');
            
            // Refresh the category dropdown
            refreshCategoryDropdown();
            
            // Show notification
            showNotification('🔄 Categories refreshed!', 'success');
        }

        function showCategoryManagementModal() {
            // Create a simple category list modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1001;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #1f2937;">Category Management</h2>
                        <button onclick="this.closest('[style*=fixed]').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">×</button>
                    </div>
                    <div id="categoryManagementList">Loading categories...</div>
                    <div style="margin-top: 20px; text-align: center;">
                        <button onclick="openCategoryModal(); this.closest('[style*=fixed]').remove();" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Category
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            loadCategoryManagementList();
        }

        async function loadCategoryManagementList() {
            try {
                const response = await fetch('get_categories.php', {
                    credentials: 'include'
                });
                const categories = await response.json();
                
                const listContainer = document.getElementById('categoryManagementList');
                
                if (categories.length === 0) {
                    listContainer.innerHTML = '<p style="text-align: center; color: #6b7280; margin: 20px 0;">No categories found.</p>';
                    return;
                }
                
                const categoriesHtml = categories.map(category => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px;">
                        <div>
                            <strong>${category.name}</strong>
                            ${category.slug ? `<br><small style="color: #6b7280;">Slug: ${category.slug}</small>` : ''}
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="openCategoryModal(${category.id}); this.closest('[style*=fixed]').remove();" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
                
                listContainer.innerHTML = categoriesHtml;
            } catch (error) {
                console.error('Failed to load categories:', error);
                document.getElementById('categoryManagementList').innerHTML = '<p style="color: #dc2626; text-align: center;">Failed to load categories.</p>';
            }
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = message;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Style the notification
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6',
                color: 'white',
                padding: '12px 20px',
                borderRadius: '8px',
                zIndex: '10001',
                fontSize: '14px',
                fontWeight: '500',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                animation: 'slideInRight 0.3s ease',
                maxWidth: '300px'
            });
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('categoryModal');
                if (modal.style.display === 'flex') {
                    closeCategoryModal();
                }
            }
        });
    </script>

    <!-- Category Management Modal -->
    <div id="categoryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <span class="icon">🏷️</span>
                    <span id="categoryModalTitle">Add New Category</span>
                </h2>
                <button type="button" class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>

            <form id="categoryForm" onsubmit="saveCategoryForm(event)">
                <div class="modal-body">
                    <input type="hidden" id="category_id" name="id" value="">
                    
                    <div class="form-group">
                        <label for="category_name">Category Name *</label>
                        <input type="text" id="category_name" name="name" required 
                               placeholder="Enter category name (e.g., Vinyl Cutters, Printers)">
                        <small class="form-hint">Choose a clear, descriptive name for your category</small>
                    </div>

                    <div class="form-group">
                        <label for="category_description">Description</label>
                        <textarea id="category_description" name="description" rows="3" 
                                  placeholder="Optional description of what products belong in this category"></textarea>
                        <small class="form-hint">Brief description to help identify suitable products</small>
                    </div>

                    <div class="form-group">
                        <label for="category_slug">URL Slug</label>
                        <input type="text" id="category_slug" name="slug" 
                               placeholder="Auto-generated from name">
                        <small class="form-hint">URL-friendly version of the name (auto-generated if left empty)</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="category_is_active" name="is_active" checked>
                            Active Category
                        </label>
                        <small class="form-hint">Inactive categories won't appear in product selection</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span id="categorySaveText">Create Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="mediaLibraryModal" class="media-library-modal" role="dialog" aria-modal="true">
        <div class="media-library-dialog">
            <div class="media-library-header">
                <h3>📚 Media Library</h3>
                <button type="button" class="media-library-close" onclick="closeMediaLibrary()" aria-label="Close media library">×</button>
            </div>
            <div class="media-library-toolbar">
                <div class="media-library-search">
                    <input type="search" id="mediaLibrarySearch" placeholder="Search by filename or product..." autocomplete="off">
                </div>
                <select id="mediaLibraryFormat">
                    <option value="">All Formats</option>
                    <option value="jpg">JPG/JPEG</option>
                    <option value="png">PNG</option>
                    <option value="webp">WebP</option>
                    <option value="avif">AVIF</option>
                    <option value="gif">GIF</option>
                    <option value="svg">SVG</option>
                    <option value="bmp">BMP</option>
                    <option value="heic">HEIC</option>
                    <option value="heif">HEIF</option>
                    <option value="jxl">JXL</option>
                    <option value="ico">ICO</option>
                </select>
            </div>
            <div class="media-library-body">
                <div class="media-library-loader" id="mediaLibraryLoader" style="display:none;">Loading media assets…</div>
                <div class="media-library-empty" id="mediaLibraryEmpty" style="display:none;">No media found for the current filters.</div>
                <div class="media-library-grid" id="mediaLibraryGrid"></div>
            </div>
            <div class="media-library-footer">
                <span class="media-library-selection-count" id="mediaLibrarySelectionCount">0 selected</span>
                <div class="media-library-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMediaLibrary()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="mediaLibraryConfirmBtn" onclick="confirmMediaLibrarySelection()">Use Selected</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Category Input Group Styles */
        .category-input-group {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .category-input-group select {
            flex: 1;
        }

        .category-actions {
            display: flex;
            gap: 4px;
            position: relative;
        }

        .category-input-group .btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .category-input-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            z-index: 1000;
            display: none;
            padding: 8px 0;
            margin-top: 4px;
        }

        .dropdown-menu.show {
            display: block;
            animation: dropdownFadeIn 0.2s ease;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: #374151;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .dropdown-menu a:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .media-picker-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .gallery-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .media-library-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            z-index: 1300;
        }

        .media-library-modal.open {
            display: flex;
        }

        .media-library-dialog {
            background: #ffffff;
            border-radius: 16px;
            max-width: 960px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.25);
            overflow: hidden;
        }

        .media-library-header {
            padding: 1.5rem 1.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
        }

        .media-library-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .media-library-close {
            border: none;
            background: transparent;
            font-size: 1.75rem;
            cursor: pointer;
            color: #64748b;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .media-library-close:hover {
            color: #1e293b;
        }

        .media-library-toolbar {
            padding: 1rem 1.75rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .media-library-search {
            flex: 1;
            position: relative;
        }

        .media-library-search input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }

        .media-library-search input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .media-library-toolbar select {
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            min-width: 160px;
            cursor: pointer;
        }

        .media-library-body {
            padding: 1.5rem 1.75rem;
            overflow-y: auto;
        }

        .media-library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .media-library-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            background: #f8fafc;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .media-library-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.16);
            border-color: #6366f1;
        }

        .media-library-card.selected {
            border-color: #6366f1;
            box-shadow: 0 16px 28px rgba(99, 102, 241, 0.3);
        }

        .media-library-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #1e293b;
        }

        .media-library-card-info {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.8rem;
            color: #475569;
        }

        .media-library-card-info strong {
            font-size: 0.85rem;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .media-library-featured {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(250, 204, 21, 0.95);
            color: #78350f;
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .media-library-orphaned {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(248, 113, 113, 0.92);
            color: #fff;
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .media-library-footer {
            padding: 1rem 1.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #e2e8f0;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .media-library-selection-count {
            font-size: 0.95rem;
            color: #475569;
        }

        .media-library-actions {
            display: flex;
            gap: 0.75rem;
        }

        .media-library-empty,
        .media-library-loader {
            text-align: center;
            padding: 2rem 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .media-library-dialog {
                padding: 0;
            }

            .media-library-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .media-library-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
        }

        .dropdown-menu a i {
            width: 16px;
            color: #6b7280;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 4px 0;
        }

        /* Category Modal Styles */
        #categoryModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        #categoryModal .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #categoryModal .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #categoryModal .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #categoryModal .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        #categoryModal .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        #categoryModal .modal-body {
            padding: 24px;
        }

        #categoryModal .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        /* Form styling for category modal */
        #categoryModal .form-group {
            margin-bottom: 20px;
        }

        #categoryModal .form-group:last-child {
            margin-bottom: 0;
        }

        #categoryModal label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
        }

        #categoryModal input[type="text"],
        #categoryModal textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        #categoryModal input[type="text"]:focus,
        #categoryModal textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        #categoryModal input[type="checkbox"] {
            margin-right: 8px;
        }

        #categoryModal .form-hint {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
        }

        /* Loading state */
        .category-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .category-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
    </style>
</body>
</html>