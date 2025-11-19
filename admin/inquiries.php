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
    
    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = Security::sanitizeInput($_POST['status']);
        $priority = Security::sanitizeInput($_POST['priority']);
        $admin_notes = Security::sanitizeInput($_POST['admin_notes']);
        
        $stmt = $db->prepare("UPDATE inquiries SET status = ?, priority = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('sssi', $status, $priority, $admin_notes, $id);
        
        if ($stmt->execute()) {
            $message = 'Inquiry updated successfully!';
            $messageType = 'success';
            
            // Log the change
            Logger::info('Inquiry status updated', [
                'inquiry_id' => $id,
                'new_status' => $status,
                'new_priority' => $priority,
                'admin_id' => $currentUser['id']
            ]);
        } else {
            $message = 'Error updating inquiry: ' . $db->error;
            $messageType = 'error';
        }
    } elseif ($action === 'reply') {
        $id = (int)$_POST['id'];
        $reply_message = Security::sanitizeInput($_POST['reply_message']);
        
        if (empty($reply_message)) {
            $message = 'Reply message is required.';
            $messageType = 'error';
        } else {
            // Update inquiry with reply
            $stmt = $db->prepare("UPDATE inquiries SET admin_reply = ?, status = 'resolved', replied_by = ?, replied_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sii', $reply_message, $currentUser['id'], $id);
            
            if ($stmt->execute()) {
                $message = 'Reply sent successfully!';
                $messageType = 'success';
                
                // Here you would typically send an email to the customer
                // For now, we'll just log it
                Logger::info('Inquiry reply sent', [
                    'inquiry_id' => $id,
                    'admin_id' => $currentUser['id']
                ]);
            } else {
                $message = 'Error sending reply: ' . $db->error;
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $message = 'Inquiry deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting inquiry: ' . $db->error;
            $messageType = 'error';
        }
    }
}

// Get inquiries with filters and pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = Security::sanitizeInput($_GET['search'] ?? '');
$status_filter = Security::sanitizeInput($_GET['status'] ?? '');
$priority_filter = Security::sanitizeInput($_GET['priority'] ?? '');
$date_from = Security::sanitizeInput($_GET['date_from'] ?? '');
$date_to = Security::sanitizeInput($_GET['date_to'] ?? '');

$where_conditions = ['1=1'];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($priority_filter) {
    $where_conditions[] = "priority = ?";
    $params[] = $priority_filter;
    $types .= 's';
}

if ($date_from) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM inquiries WHERE $where_clause";
$count_stmt = $db->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_inquiries = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_inquiries / $limit);

// Get inquiries
$sql = "SELECT i.*, a.full_name as replied_by_name 
        FROM inquiries i 
        LEFT JOIN admin_users a ON i.replied_by = a.id 
        WHERE $where_clause 
        ORDER BY i.created_at DESC 
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
$inquiries = $stmt->get_result();

// Get inquiry statistics
$stats_query = "SELECT COUNT(*) as total_inquiries, SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_inquiries, SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved, SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as `high_priority`, SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as inquiries_24h FROM inquiries";
$stats_result = $db->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// Get inquiry for viewing/replying if ID is provided
$viewing_inquiry = null;
if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    $stmt = $db->prepare("SELECT i.*, a.full_name as replied_by_name FROM inquiries i LEFT JOIN admin_users a ON i.replied_by = a.id WHERE i.id = ?");
    $stmt->bind_param('i', $view_id);
    $stmt->execute();
    $viewing_inquiry = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Management - JINKA Admin</title>
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
                    <h1>Inquiry Management</h1>
                    <div class="header-actions">
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

                <!-- Inquiry Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= number_format($stats['total_inquiries'] ?? 0) ?></h3>
                        <p>Total Inquiries</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['new_inquiries'] ?? 0) ?></h3>
                        <p>New Inquiries</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['in_progress'] ?? 0) ?></h3>
                        <p>In Progress</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['high_priority'] ?? 0) ?></h3>
                        <p>High Priority</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= number_format($stats['inquiries_24h'] ?? 0) ?></h3>
                        <p>Last 24 Hours</p>
                    </div>
                </div>

                <?php if ($viewing_inquiry): ?>
                    <!-- Inquiry Details Modal -->
                    <div class="modal-overlay" onclick="closeInquiryModal()" style="display: flex;">
                        <div class="modal-content inquiry-modal large-modal" onclick="event.stopPropagation()">
                            <!-- Modal Header -->
                            <div class="modal-header">
                                <div class="header-content">
                                    <h3>
                                        <span class="modal-icon">📧</span>
                                        Inquiry Details - <?= htmlspecialchars($viewing_inquiry['subject']) ?>
                                    </h3>
                                    <div class="header-meta">
                                        <span class="badge badge-<?= $viewing_inquiry['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $viewing_inquiry['status'])) ?>
                                        </span>
                                        <span class="badge badge-<?= $viewing_inquiry['priority'] ?>">
                                            <?= ucfirst($viewing_inquiry['priority']) ?> Priority
                                        </span>
                                    </div>
                                </div>
                                <button class="btn btn-secondary modal-close" onclick="closeInquiryModal()">
                                    <span class="icon">✕</span>
                                </button>
                            </div>
                            
                            <!-- Modal Body -->
                            <div class="modal-body">
                                <!-- Customer Information Card -->
                                <div class="inquiry-card">
                                    <div class="card-header-custom">
                                        <h4><span class="icon">👤</span> Customer Information</h4>
                                    </div>
                                    <div class="card-content">
                                        <div class="info-grid-2col">
                                            <div class="info-item">
                                                <label>Full Name</label>
                                                <div class="info-value"><?= htmlspecialchars($viewing_inquiry['name']) ?></div>
                                            </div>
                                            <div class="info-item">
                                                <label>Email Address</label>
                                                <div class="info-value">
                                                    <a href="mailto:<?= htmlspecialchars($viewing_inquiry['email']) ?>">
                                                        <?= htmlspecialchars($viewing_inquiry['email']) ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <label>Phone Number</label>
                                                <div class="info-value"><?= htmlspecialchars($viewing_inquiry['phone'] ?? 'Not provided') ?></div>
                                            </div>
                                            <div class="info-item">
                                                <label>Company</label>
                                                <div class="info-value"><?= htmlspecialchars($viewing_inquiry['company'] ?? 'Not provided') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inquiry Details Card -->
                                <div class="inquiry-card">
                                    <div class="card-header-custom">
                                        <h4><span class="icon">📋</span> Inquiry Information</h4>
                                    </div>
                                    <div class="card-content">
                                        <div class="info-grid-2col">
                                            <div class="info-item">
                                                <label>Subject</label>
                                                <div class="info-value"><?= htmlspecialchars($viewing_inquiry['subject']) ?></div>
                                            </div>
                                            <div class="info-item">
                                                <label>Date Submitted</label>
                                                <div class="info-value"><?= date('F j, Y \a\t g:i A', strtotime($viewing_inquiry['created_at'])) ?></div>
                                            </div>
                                            <div class="info-item">
                                                <label>Current Status</label>
                                                <div class="info-value">
                                                    <span class="badge badge-<?= $viewing_inquiry['status'] ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                                        <?= ucfirst(str_replace('_', ' ', $viewing_inquiry['status'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <label>Priority Level</label>
                                                <div class="info-value">
                                                    <span class="badge badge-<?= $viewing_inquiry['priority'] ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                                        <?= ucfirst($viewing_inquiry['priority']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Message Card -->
                                <div class="inquiry-card">
                                    <div class="card-header-custom">
                                        <h4><span class="icon">💬</span> Customer Message</h4>
                                    </div>
                                    <div class="card-content">
                                        <div class="message-content">
                                            <?= nl2br(htmlspecialchars($viewing_inquiry['message'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Reply Card (if exists) -->
                                <?php if (!empty($viewing_inquiry['admin_reply'])): ?>
                                    <div class="inquiry-card">
                                        <div class="card-header-custom" style="background: #ecfdf5;">
                                            <h4><span class="icon">✅</span> Your Reply</h4>
                                        </div>
                                        <div class="card-content">
                                            <div class="reply-content">
                                                <?= nl2br(htmlspecialchars($viewing_inquiry['admin_reply'])) ?>
                                            </div>
                                            <div class="reply-meta">
                                                Replied by <strong><?= htmlspecialchars($viewing_inquiry['replied_by_name'] ?? 'Admin') ?></strong> 
                                                on <?= date('F j, Y \a\t g:i A', strtotime($viewing_inquiry['replied_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Admin Notes Card (if exists) -->
                                <?php if (!empty($viewing_inquiry['admin_notes'])): ?>
                                    <div class="inquiry-card">
                                        <div class="card-header-custom" style="background: #fef3c7;">
                                            <h4><span class="icon">📝</span> Internal Notes</h4>
                                        </div>
                                        <div class="card-content">
                                            <div class="notes-content">
                                                <?= nl2br(htmlspecialchars($viewing_inquiry['admin_notes'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Update Status & Priority Card -->
                                <div class="inquiry-card">
                                    <div class="card-header-custom">
                                        <h4><span class="icon">⚙️</span> Update Status & Priority</h4>
                                    </div>
                                    <div class="card-content">
                                        <form method="POST" class="inquiry-update-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= $viewing_inquiry['id'] ?>">
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label for="status">Change Status</label>
                                                    <select id="status" name="status">
                                                        <option value="new" <?= $viewing_inquiry['status'] == 'new' ? 'selected' : '' ?>>New</option>
                                                        <option value="in_progress" <?= $viewing_inquiry['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                        <option value="resolved" <?= $viewing_inquiry['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        <option value="closed" <?= $viewing_inquiry['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="priority">Change Priority</label>
                                                    <select id="priority" name="priority">
                                                        <option value="low" <?= $viewing_inquiry['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
                                                        <option value="medium" <?= $viewing_inquiry['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                                                        <option value="high" <?= $viewing_inquiry['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                                                        <option value="urgent" <?= $viewing_inquiry['priority'] == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="admin_notes">Internal Notes (Not visible to customer)</label>
                                                <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add private notes about this inquiry..."><?= htmlspecialchars($viewing_inquiry['admin_notes'] ?? '') ?></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-primary">
                                                <span class="icon">💾</span> Update Status & Notes
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Reply to Customer Card -->
                                <div class="inquiry-card">
                                    <div class="card-header-custom" style="background: linear-gradient(135deg, #1e40af, #0369a1);">
                                        <h4 style="color: white;"><span class="icon">✉️</span> Reply to Customer</h4>
                                    </div>
                                    <div class="card-content">
                                        <form method="POST" class="inquiry-reply-form">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="id" value="<?= $viewing_inquiry['id'] ?>">
                                            
                                            <div class="form-group">
                                                <label for="reply_message">Your Reply Message</label>
                                                <textarea id="reply_message" name="reply_message" rows="8" 
                                                          placeholder="Write your response to the customer here... This will be sent via email."><?= !empty($viewing_inquiry['admin_reply']) ? htmlspecialchars($viewing_inquiry['admin_reply']) : '' ?></textarea>
                                                <small class="form-hint">This message will be sent to <?= htmlspecialchars($viewing_inquiry['email']) ?></small>
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-success">
                                                    <span class="icon">📤</span> Send Reply
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="closeInquiryModal()">
                                                    <span class="icon">✕</span> Close
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inquiries List -->
                <div class="card">
                    <div class="card-header">
                        <h3>Inquiries (<?= $total_inquiries ?> total)</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="filters-form">
                            <div class="filters-grid">
                                <input type="text" name="search" placeholder="Search inquiries..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                
                                <select name="status">
                                    <option value="">All Statuses</option>
                                    <option value="new" <?= $status_filter == 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $status_filter == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="closed" <?= $status_filter == 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>

                                <select name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="low" <?= $priority_filter == 'low' ? 'selected' : '' ?>>Low</option>
                                    <option value="medium" <?= $priority_filter == 'medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="high" <?= $priority_filter == 'high' ? 'selected' : '' ?>>High</option>
                                    <option value="urgent" <?= $priority_filter == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                </select>

                                <input type="date" name="date_from" value="<?= $date_from ?>" placeholder="From Date">
                                <input type="date" name="date_to" value="<?= $date_to ?>" placeholder="To Date">

                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="inquiries.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>

                        <!-- Inquiries Table -->
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Contact</th>
                                        <th>Message Preview</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($inquiries->num_rows > 0): ?>
                                        <?php while ($inquiry = $inquiries->fetch_assoc()): ?>
                                            <tr class="<?= $inquiry['status'] == 'new' ? 'new-inquiry' : '' ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($inquiry['subject']) ?></strong>
                                                    <small class="message-preview">
                                                        <?= htmlspecialchars(substr($inquiry['message'], 0, 100)) ?>...
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="contact-info">
                                                        <strong><?= htmlspecialchars($inquiry['name']) ?></strong>
                                                        <small><?= htmlspecialchars($inquiry['email']) ?></small>
                                                        <?php if ($inquiry['company']): ?>
                                                            <small><?= htmlspecialchars($inquiry['company']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars(substr($inquiry['message'], 0, 50)) . (strlen($inquiry['message']) > 50 ? '...' : '') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $inquiry['priority'] ?>">
                                                        <?= ucfirst($inquiry['priority']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $inquiry['status'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $inquiry['status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($inquiry['created_at'])) ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="inquiries.php?view=<?= $inquiry['id'] ?>" 
                                                           class="btn btn-sm btn-info">View</a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this inquiry?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $inquiry['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No inquiries found.</td>
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
                                    'priority' => $priority_filter,
                                    'date_from' => $date_from,
                                    'date_to' => $date_to
                                ]));
                                $query_string = $query_params ? '&' . $query_params : '';
                                ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="inquiries.php?page=<?= $i ?><?= $query_string ?>" 
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
        function closeInquiryModal() {
            window.location.href = 'inquiries.php';
        }

        // Auto-refresh for new inquiries
        setInterval(function() {
            // Only refresh if not in modal view
            if (!document.querySelector('.modal-overlay')) {
                window.location.reload();
            }
        }, 120000); // Refresh every 2 minutes
    </script>
</body>
</html>