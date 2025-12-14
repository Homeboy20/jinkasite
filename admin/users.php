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
        $full_name = Security::sanitizeInput($_POST['full_name']);
        $email = Security::sanitizeInput($_POST['email']);
        $username = Security::sanitizeInput($_POST['username']);
        $role = Security::sanitizeInput($_POST['role']);
        $is_active = (int)($_POST['is_active'] ?? 1);
        $password = $_POST['password'] ?? '';
        
        // Validate required fields
        if (empty($full_name) || empty($email) || empty($username)) {
            $message = 'Full name, email, and username are required.';
            $messageType = 'error';
        } else {
            // Check username uniqueness
            $username_check_sql = $action === 'edit' 
                ? "SELECT id FROM admin_users WHERE username = ? AND id != ?" 
                : "SELECT id FROM admin_users WHERE username = ?";
            $username_stmt = $db->prepare($username_check_sql);
            
            if ($action === 'edit') {
                $username_stmt->bind_param('si', $username, $id);
            } else {
                $username_stmt->bind_param('s', $username);
            }
            
            $username_stmt->execute();
            $existing_username = $username_stmt->get_result()->fetch_assoc();
            
            // Check email uniqueness
            $email_check_sql = $action === 'edit' 
                ? "SELECT id FROM admin_users WHERE email = ? AND id != ?" 
                : "SELECT id FROM admin_users WHERE email = ?";
            $email_stmt = $db->prepare($email_check_sql);
            
            if ($action === 'edit') {
                $email_stmt->bind_param('si', $email, $id);
            } else {
                $email_stmt->bind_param('s', $email);
            }
            
            $email_stmt->execute();
            $existing_email = $email_stmt->get_result()->fetch_assoc();
            
            if ($existing_username) {
                $message = 'A user with this username already exists.';
                $messageType = 'error';
            } elseif ($existing_email) {
                $message = 'A user with this email already exists.';
                $messageType = 'error';
            } else {
                if ($action === 'add') {
                    if (empty($password)) {
                        $message = 'Password is required for new users.';
                        $messageType = 'error';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("INSERT INTO admin_users (full_name, email, username, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param('sssssi', $full_name, $email, $username, $password_hash, $role, $is_active);
                        
                        if ($stmt->execute()) {
                            $message = 'User added successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Error adding user: ' . $db->error;
                            $messageType = 'error';
                        }
                    }
                } else {
                    // Update user
                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ?, username = ?, password_hash = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param('sssssii', $full_name, $email, $username, $password_hash, $role, $is_active, $id);
                    } else {
                        $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ?, username = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param('ssssii', $full_name, $email, $username, $role, $is_active, $id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = 'User updated successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Error updating user: ' . $db->error;
                        $messageType = 'error';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Prevent self-deletion
        if ($id == $currentUser['id']) {
            $message = 'You cannot delete your own account.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $message = 'User deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting user: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        
        // Prevent self-deactivation
        if ($id == $currentUser['id']) {
            $message = 'You cannot change your own status.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("UPDATE admin_users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $message = 'User status updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating status: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'All password fields are required.';
            $messageType = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
            $messageType = 'error';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long.';
            $messageType = 'error';
        } else {
            // Verify current password
            if (password_verify($current_password, $currentUser['password_hash'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('si', $new_password_hash, $currentUser['id']);
                
                if ($stmt->execute()) {
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error changing password: ' . $db->error;
                    $messageType = 'error';
                }
            } else {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            }
        }
    }
}

// Get users with filters and pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = Security::sanitizeInput($_GET['search'] ?? '');
$role_filter = Security::sanitizeInput($_GET['role'] ?? '');
$status_filter = Security::sanitizeInput($_GET['status'] ?? '');

$where_conditions = ['1=1'];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($role_filter) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = (int)$status_filter;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM admin_users WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$sql = "SELECT * FROM admin_users WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$all_types = $types . 'ii';

if ($all_params) {
    $stmt->bind_param($all_types, ...$all_params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get user statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_last_30d
    FROM admin_users
";
$stats_result = $db->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// Get user for editing if ID is provided
$editing_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $editing_user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - JINKA Admin</title>
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
                    <h1>User Management</h1>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="toggleAddForm()">Add User</button>
                        <button class="btn btn-secondary" onclick="togglePasswordForm()">Change Password</button>
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

                <!-- User Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= number_format($stats['total_users'] ?? 0) ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['active_users'] ?? 0) ?></h3>
                        <p>Active Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['admin_users'] ?? 0) ?></h3>
                        <p>Admin Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['active_last_30d'] ?? 0) ?></h3>
                        <p>Active (30 days)</p>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div id="passwordForm" class="card" style="display: none">
                    <div class="card-header">
                        <h3>Change Your Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="password-form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <small>Minimum 8 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                                <button type="button" class="btn btn-secondary" onclick="togglePasswordForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add/Edit User Form -->
                <div id="userForm" class="card" style="display: <?= $editing_user ? 'block' : 'none' ?>">
                    <div class="card-header">
                        <h3><?= $editing_user ? 'Edit User' : 'Add New User' ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="user-form">
                            <input type="hidden" name="action" value="<?= $editing_user ? 'edit' : 'add' ?>">
                            <?php if ($editing_user): ?>
                                <input type="hidden" name="id" value="<?= $editing_user['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="full_name">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" required
                                           value="<?= htmlspecialchars($editing_user['full_name'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required
                                           value="<?= htmlspecialchars($editing_user['email'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" id="username" name="username" required
                                           value="<?= htmlspecialchars($editing_user['username'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select id="role" name="role">
                                        <option value="super_admin" <?= ($editing_user['role'] ?? '') == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                        <option value="admin" <?= ($editing_user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="manager" <?= ($editing_user['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="support_agent" <?= ($editing_user['role'] ?? '') == 'support_agent' ? 'selected' : '' ?>>Support Agent</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select id="is_active" name="is_active">
                                        <option value="1" <?= ($editing_user['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                                        <option value="0" <?= ($editing_user['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password <?= $editing_user ? '(leave blank to keep current)' : '*' ?></label>
                                    <input type="password" id="password" name="password" 
                                           <?= $editing_user ? '' : 'required' ?> minlength="8">
                                    <small>Minimum 8 characters</small>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $editing_user ? 'Update User' : 'Add User' ?>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="cancelForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users List -->
                <div class="card">
                    <div class="card-header">
                        <h3>Users (<?= $total_users ?> total)</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="filters-form">
                            <div class="filters-grid">
                                <input type="text" name="search" placeholder="Search users..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                
                                <select name="role">
                                    <option value="">All Roles</option>
                                    <option value="super_admin" <?= $role_filter == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                    <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="manager" <?= $role_filter == 'manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="support_agent" <?= $role_filter == 'support_agent' ? 'selected' : '' ?>>Support Agent</option>
                                </select>

                                <select name="status">
                                    <option value="">All Statuses</option>
                                    <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>Inactive</option>
                                </select>

                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="users.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users->num_rows > 0): ?>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                                    <?php if ($user['id'] == $currentUser['id']): ?>
                                                        <span class="badge badge-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $user['role'] ?>">
                                                        <?= ucfirst($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $user['is_active'] == 1 ? 'active' : 'inactive' ?>">
                                                        <?= $user['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never' ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="users.php?edit=<?= $user['id'] ?>" 
                                                           class="btn btn-sm btn-primary">Edit</a>
                                                        
                                                        <?php if ($user['id'] != $currentUser['id']): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="toggle_status">
                                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning">
                                                                    <?= $user['is_active'] == 1 ? 'Deactivate' : 'Activate' ?>
                                                                </button>
                                                            </form>
                                                            
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No users found.</td>
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
                                    'role' => $role_filter,
                                    'status' => $status_filter
                                ]));
                                $query_string = $query_params ? '&' . $query_params : '';
                                ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="users.php?page=<?= $i ?><?= $query_string ?>" 
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
            const form = document.getElementById('userForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function togglePasswordForm() {
            const form = document.getElementById('passwordForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function cancelForm() {
            window.location.href = 'users.php';
        }

        // Show form if editing
        <?php if ($editing_user): ?>
            document.getElementById('userForm').style.display = 'block';
        <?php endif; ?>

        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>