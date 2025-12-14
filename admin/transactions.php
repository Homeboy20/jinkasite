<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Require authentication
$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$statusFilter = $_GET['status'] ?? '';
$paymentMethodFilter = $_GET['payment_method'] ?? '';
$dateFilter = $_GET['date_range'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter) {
    $whereConditions[] = "o.payment_status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($paymentMethodFilter) {
    $whereConditions[] = "o.payment_method = ?";
    $params[] = $paymentMethodFilter;
    $types .= 's';
}

if ($dateFilter) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $whereConditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

if ($searchQuery) {
    $whereConditions[] = "(o.transaction_ref LIKE ? OR o.transaction_id LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= 'ssss';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get transactions
$query = "
    SELECT o.*
    FROM orders o
    $whereClause
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($query);
if ($params) {
    $types .= 'ii';
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT o.id) as total FROM orders o $whereClause";
if ($whereConditions) {
    $countStmt = $db->prepare($countQuery);
    // Remove the last two parameters (LIMIT and OFFSET)
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    if ($countParams) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalTransactions = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $totalTransactions = $db->query($countQuery)->fetch_assoc()['total'];
}

$totalPages = ceil($totalTransactions / $perPage);

// Get statistics
$stats = $db->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN payment_status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'completed' AND DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
    FROM orders
")->fetch_assoc();

$pageTitle = 'Transactions Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .transactions-management {
            background: #f8fafc;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            padding: 0 1rem;
        }

        .admin-main {
            width: 100% !important;
            max-width: none !important;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.revenue {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .stat-icon.completed {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .stat-icon.failed {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .stat-content h3 {
            margin: 0;
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .stat-content .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0.25rem 0 0 0;
        }

        .stat-content .subtext {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        /* Header */
        .transactions-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-info p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Filters */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .filter-control {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Transactions Table */
        .transactions-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table thead {
            background: #f9fafb;
        }

        .transactions-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e5e7eb;
        }

        .transactions-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }

        .transactions-table tbody tr {
            transition: background 0.2s ease;
        }

        .transactions-table tbody tr:hover {
            background: #f9fafb;
        }

        .transaction-ref {
            font-family: monospace;
            font-weight: 600;
            color: #1f2937;
        }

        .transaction-id {
            font-family: monospace;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 600;
            color: #1f2937;
        }

        .user-email {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .amount {
            font-weight: 700;
            color: #10b981;
            font-size: 1rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
        }

        .badge-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-refunded {
            background: #e0e7ff;
            color: #3730a3;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
        }

        .date-time {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .date {
            font-weight: 600;
            color: #1f2937;
        }

        .time {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn-view {
            background: #3b82f6;
            color: white;
        }

        .action-btn-view:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1.5rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .pagination a {
            background: white;
            color: #667eea;
            border: 2px solid #e5e7eb;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border: 2px solid #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .transactions-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .transactions-table-container {
                overflow-x: auto;
            }

            .transactions-table {
                min-width: 1000px;
            }
        }
    </style>
</head>
<body class="transactions-management">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="container">
                <!-- Header -->
                <div class="transactions-header">
                    <div class="header-info">
                        <h1><i class="fas fa-receipt"></i> Transactions Management</h1>
                        <p>Monitor and manage all payment transactions</p>
                    </div>
                    <div class="header-actions">
                        <a href="?export=csv" class="btn btn-primary">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon revenue">
                            <i class="fas fa-naira-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Revenue</h3>
                            <div class="value">₦<?= number_format($stats['total_revenue'], 2) ?></div>
                            <div class="subtext">Today: ₦<?= number_format($stats['today_revenue'], 2) ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Completed</h3>
                            <div class="value"><?= number_format($stats['completed_count']) ?></div>
                            <div class="subtext">Successful payments</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending</h3>
                            <div class="value"><?= number_format($stats['pending_count']) ?></div>
                            <div class="subtext">Awaiting payment</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon failed">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Failed</h3>
                            <div class="value"><?= number_format($stats['failed_count']) ?></div>
                            <div class="subtext">Failed transactions</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="filter-control" 
                                       placeholder="Transaction ID, Ref, Email..." 
                                       value="<?= htmlspecialchars($searchQuery) ?>">
                            </div>

                            <div class="filter-group">
                                <label for="status">Payment Status</label>
                                <select id="status" name="status" class="filter-control">
                                    <option value="">All Status</option>
                                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="filter-control">
                                    <option value="">All Methods</option>
                                    <option value="flutterwave" <?= $paymentMethodFilter === 'flutterwave' ? 'selected' : '' ?>>Flutterwave</option>
                                    <option value="paystack" <?= $paymentMethodFilter === 'paystack' ? 'selected' : '' ?>>Paystack</option>
                                    <option value="bank_transfer" <?= $paymentMethodFilter === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="date_range">Date Range</label>
                                <select id="date_range" name="date_range" class="filter-control">
                                    <option value="">All Time</option>
                                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                                    <option value="year" <?= $dateFilter === 'year' ? 'selected' : '' ?>>Last Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="filter-actions">
                            <a href="transactions.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Transactions Table -->
                <div class="transactions-table-container">
                    <?php if (empty($transactions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3>No Transactions Found</h3>
                            <p>No transactions match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Transaction</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th>Method</th>
                                    <th>Items</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td>
                                            <div class="transaction-ref"><?= htmlspecialchars($txn['transaction_ref'] ?? $txn['order_number'] ?? 'N/A') ?></div>
                                            <?php if (!empty($txn['transaction_id'])): ?>
                                                <div class="transaction-id"><?= htmlspecialchars($txn['transaction_id']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-name"><?= htmlspecialchars($txn['customer_name'] ?? 'Guest') ?></div>
                                                <div class="user-email"><?= htmlspecialchars($txn['customer_email'] ?? 'N/A') ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="amount">₦<?= number_format($txn['total_amount'], 2) ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge-' . $txn['payment_status'];
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <i class="fas fa-<?= $txn['payment_status'] === 'completed' ? 'check' : ($txn['payment_status'] === 'pending' ? 'clock' : 'times') ?>"></i>
                                                <?= ucfirst($txn['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= ($txn['order_status'] ?? 'pending') === 'delivered' ? 'completed' : 'pending' ?>">
                                                <?= ucfirst($txn['order_status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="payment-method">
                                                <i class="fas fa-<?= ($txn['payment_method'] ?? 'cash') === 'flutterwave' ? 'credit-card' : 'university' ?>"></i>
                                                <?= ucfirst(str_replace('_', ' ', $txn['payment_method'] ?? 'N/A')) ?>
                                            </div>
                                        </td>
                                        <td><?= $txn['item_count'] ?? '-' ?></td>
                                        <td>
                                            <div class="date-time">
                                                <div class="date"><?= date('M d, Y', strtotime($txn['created_at'])) ?></div>
                                                <div class="time"><?= date('h:i A', strtotime($txn['created_at'])) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="transaction-details.php?id=<?= $txn['id'] ?>" class="action-btn action-btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= $statusFilter ? "&status=$statusFilter" : '' ?><?= $paymentMethodFilter ? "&payment_method=$paymentMethodFilter" : '' ?><?= $dateFilter ? "&date_range=$dateFilter" : '' ?><?= $searchQuery ? "&search=" . urlencode($searchQuery) : '' ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?><?= $statusFilter ? "&status=$statusFilter" : '' ?><?= $paymentMethodFilter ? "&payment_method=$paymentMethodFilter" : '' ?><?= $dateFilter ? "&date_range=$dateFilter" : '' ?><?= $searchQuery ? "&search=" . urlencode($searchQuery) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= $statusFilter ? "&status=$statusFilter" : '' ?><?= $paymentMethodFilter ? "&payment_method=$paymentMethodFilter" : '' ?><?= $dateFilter ? "&date_range=$dateFilter" : '' ?><?= $searchQuery ? "&search=" . urlencode($searchQuery) : '' ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
