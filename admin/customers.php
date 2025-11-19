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

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $action === 'edit' ? (int)$_POST['id'] : 0;
        $first_name = Security::sanitizeInput($_POST['first_name']);
        $last_name = Security::sanitizeInput($_POST['last_name']);
        $email = Security::sanitizeInput($_POST['email']);
        $phone = Security::sanitizeInput($_POST['phone']);
        $business_name = Security::sanitizeInput($_POST['business_name']);
        $address = Security::sanitizeInput($_POST['address']);
        $city = Security::sanitizeInput($_POST['city']);
        $country = Security::sanitizeInput($_POST['country']);
        $postal_code = Security::sanitizeInput($_POST['postal_code']);
        $notes = Security::sanitizeInput($_POST['notes']);
        
        // Validate required fields
        if (empty($first_name) || empty($email)) {
            $message = 'First name and email are required.';
            $messageType = 'error';
        } else {
            // Check email uniqueness
            $email_check_sql = $action === 'edit' 
                ? "SELECT id FROM customers WHERE email = ? AND id != ?" 
                : "SELECT id FROM customers WHERE email = ?";
            $email_stmt = $db->prepare($email_check_sql);
            
            if ($action === 'edit') {
                $email_stmt->bind_param('si', $email, $id);
            } else {
                $email_stmt->bind_param('s', $email);
            }
            
            $email_stmt->execute();
            $existing = $email_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                $message = 'A customer with this email already exists.';
                $messageType = 'error';
            } else {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO customers (first_name, last_name, email, phone, business_name, address, city, country, postal_code, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param('ssssssssss', $first_name, $last_name, $email, $phone, $business_name, $address, $city, $country, $postal_code, $notes);
                } else {
                    $stmt = $db->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, business_name = ?, address = ?, city = ?, country = ?, postal_code = ?, notes = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('ssssssssssi', $first_name, $last_name, $email, $phone, $business_name, $address, $city, $country, $postal_code, $notes, $id);
                }
                
                if ($stmt->execute()) {
                    $message = $action === 'add' ? 'Customer added successfully!' : 'Customer updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error saving customer: ' . $db->error;
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if customer has orders
        $order_check = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
        $order_check->bind_param('i', $id);
        $order_check->execute();
        $order_count = $order_check->get_result()->fetch_assoc()['count'];
        
        if ($order_count > 0) {
            $message = 'Cannot delete customer with existing orders. Consider deactivating instead.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $message = 'Customer deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting customer: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE customers SET status = IF(status = 'active', 'inactive', 'active'), updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $message = 'Customer status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating status: ' . $db->error;
            $messageType = 'error';
        }
    }
}

// Get customers with filters and pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = Security::sanitizeInput($_GET['search'] ?? '');
$status_filter = Security::sanitizeInput($_GET['status'] ?? '');
$country_filter = Security::sanitizeInput($_GET['country'] ?? '');

$where_conditions = ['1=1'];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR business_name LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $search_param;
    }
    $types .= 'sssss';
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($country_filter) {
    $where_conditions[] = "country = ?";
    $params[] = $country_filter;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM customers WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_customers = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_customers / $limit);

// Get customers
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as total_orders,
        (SELECT SUM(total_amount) FROM orders WHERE customer_id = c.id AND payment_status = 'paid') as total_spent
        FROM customers c 
        WHERE $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$all_types = $types . 'ii';

if ($all_params) {
    $stmt->bind_param($all_types, ...$all_params);
}
$stmt->execute();
$customers = $stmt->get_result();

// Get customer statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_customers_30d,
        (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as customers_with_orders_30d
    FROM customers
";
$stats_result = $db->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// Get countries for filter
$countries_result = $db->query("SELECT DISTINCT country FROM customers WHERE country IS NOT NULL AND country != '' ORDER BY country");
$countries = [];
if ($countries_result) {
    while ($row = $countries_result->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}

// Get customer for editing if ID is provided
$editing_customer = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $editing_customer = $stmt->get_result()->fetch_assoc();
}

// Get customer details for viewing
$viewing_customer = null;
$customer_orders = null;
if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param('i', $view_id);
    $stmt->execute();
    $viewing_customer = $stmt->get_result()->fetch_assoc();
    
    if ($viewing_customer) {
        // Get customer's orders
        $orders_stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
        $orders_stmt->bind_param('i', $view_id);
        $orders_stmt->execute();
        $customer_orders = $orders_stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-content">
                    <h1>Customer Management</h1>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="toggleAddForm()">Add Customer</button>
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

                <!-- Customer Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= number_format($stats['total_customers'] ?? 0) ?></h3>
                        <p>Total Customers</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['active_customers'] ?? 0) ?></h3>
                        <p>Active Customers</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['new_customers_30d'] ?? 0) ?></h3>
                        <p>New (30 days)</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['customers_with_orders_30d'] ?? 0) ?></h3>
                        <p>Active Buyers (30d)</p>
                    </div>
                </div>

                <!-- Add/Edit Customer Form -->
                <div id="customerForm" class="card" style="display: <?= $editing_customer ? 'block' : 'none' ?>">
                    <div class="card-header">
                        <h3><?= $editing_customer ? 'Edit Customer' : 'Add New Customer' ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="customer-form">
                            <input type="hidden" name="action" value="<?= $editing_customer ? 'edit' : 'add' ?>">
                            <?php if ($editing_customer): ?>
                                <input type="hidden" name="id" value="<?= $editing_customer['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required
                                           value="<?= htmlspecialchars($editing_customer['first_name'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name"
                                           value="<?= htmlspecialchars($editing_customer['last_name'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required
                                           value="<?= htmlspecialchars($editing_customer['email'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone"
                                           value="<?= htmlspecialchars($editing_customer['phone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="business_name">Business Name</label>
                                    <input type="text" id="business_name" name="business_name"
                                           value="<?= htmlspecialchars($editing_customer['business_name'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country"
                                           value="<?= htmlspecialchars($editing_customer['country'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city"
                                           value="<?= htmlspecialchars($editing_customer['city'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="postal_code">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code"
                                           value="<?= htmlspecialchars($editing_customer['postal_code'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="2"><?= htmlspecialchars($editing_customer['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($editing_customer['notes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $editing_customer ? 'Update Customer' : 'Add Customer' ?>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="cancelForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($viewing_customer): ?>
                    <!-- Customer Details Modal -->
                    <div class="modal-overlay" onclick="closeCustomerModal()">
                        <div class="modal-content customer-modal" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <h3><?= htmlspecialchars($viewing_customer['first_name'] . ' ' . $viewing_customer['last_name']) ?></h3>
                                <button class="modal-close" onclick="closeCustomerModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="customer-details-grid">
                                    <!-- Customer Information -->
                                    <div class="customer-section">
                                        <h4>Contact Information</h4>
                                        <div class="info-grid">
                                            <div><strong>Email:</strong> <?= htmlspecialchars($viewing_customer['email']) ?></div>
                                            <div><strong>Phone:</strong> <?= htmlspecialchars($viewing_customer['phone'] ?? 'N/A') ?></div>
                                            <?php if ($viewing_customer['business_name']): ?>
                                                <div><strong>Business:</strong> <?= htmlspecialchars($viewing_customer['business_name']) ?></div>
                                            <?php endif; ?>
                                            <div><strong>Status:</strong> 
                                                <span class="badge badge-<?= $viewing_customer['status'] ?>">
                                                    <?= ucfirst($viewing_customer['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <div class="customer-section">
                                        <h4>Address</h4>
                                        <div class="address-info">
                                            <?php if ($viewing_customer['address']): ?>
                                                <?= nl2br(htmlspecialchars($viewing_customer['address'])) ?><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($viewing_customer['city'] ?? '') ?>
                                            <?= $viewing_customer['postal_code'] ? ', ' . htmlspecialchars($viewing_customer['postal_code']) : '' ?><br>
                                            <?= htmlspecialchars($viewing_customer['country'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($viewing_customer['notes']): ?>
                                    <div class="customer-section">
                                        <h4>Notes</h4>
                                        <p><?= nl2br(htmlspecialchars($viewing_customer['notes'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Recent Orders -->
                                <div class="customer-section">
                                    <h4>Recent Orders</h4>
                                    <?php if ($customer_orders->num_rows > 0): ?>
                                        <table class="order-history-table">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($order = $customer_orders->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                        <td><?= $order['currency'] ?> <?= number_format($order['total_amount'], 0) ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $order['status'] ?>">
                                                                <?= ucfirst($order['status']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>No orders found for this customer.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-actions">
                                    <a href="customers.php?edit=<?= $viewing_customer['id'] ?>" class="btn btn-primary">Edit Customer</a>
                                    <button class="btn btn-secondary" onclick="closeCustomerModal()">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Customers List -->
                <div class="card">
                    <div class="card-header">
                        <h3>Customers (<?= $total_customers ?> total)</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="filters-form">
                            <div class="filters-grid">
                                <input type="text" name="search" placeholder="Search customers..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                
                                <select name="status">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>

                                <select name="country">
                                    <option value="">All Countries</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= htmlspecialchars($country) ?>" 
                                                <?= $country_filter == $country ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($country) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="customers.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>

                        <!-- Customers Table -->
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Location</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($customers->num_rows > 0): ?>
                                        <?php while ($customer = $customers->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="customer-info">
                                                        <strong><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></strong>
                                                        <?php if ($customer['business_name']): ?>
                                                            <small><?= htmlspecialchars($customer['business_name']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                                <td><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?= htmlspecialchars($customer['city'] ?? '') ?>
                                                    <?php if ($customer['country']): ?>
                                                        <small><?= htmlspecialchars($customer['country']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= (int)$customer['total_orders'] ?></td>
                                                <td>KES <?= number_format($customer['total_spent'] ?? 0, 0) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $customer['status'] ?>">
                                                        <?= ucfirst($customer['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="customers.php?view=<?= $customer['id'] ?>" 
                                                           class="btn btn-sm btn-info">View</a>
                                                        <a href="customers.php?edit=<?= $customer['id'] ?>" 
                                                           class="btn btn-sm btn-primary">Edit</a>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-warning">
                                                                <?= $customer['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No customers found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php
                                $query_params = http_build_query(array_filter([
                                    'search' => $search,
                                    'status' => $status_filter,
                                    'country' => $country_filter
                                ]));
                                $query_string = $query_params ? '&' . $query_params : '';
                                ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="customers.php?page=<?= $i ?><?= $query_string ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('customerForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function cancelForm() {
            window.location.href = 'customers.php';
        }

        function closeCustomerModal() {
            window.location.href = 'customers.php';
        }

        // Show form if editing
        <?php if ($editing_customer): ?>
            document.getElementById('customerForm').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>