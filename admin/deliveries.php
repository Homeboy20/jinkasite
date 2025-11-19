<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$dateFilter = $_GET['date_range'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter) {
    $whereConditions[] = "d.delivery_status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($dateFilter) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = "DATE(d.created_at) = CURDATE()";
            break;
        case 'week':
            $whereConditions[] = "d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereConditions[] = "d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($searchQuery) {
    $whereConditions[] = "(d.tracking_number LIKE ? OR d.driver_name LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= 'ssss';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get deliveries
$query = "
    SELECT d.*, o.order_number, o.customer_name, o.customer_email, o.total_amount
    FROM deliveries d
    LEFT JOIN orders o ON d.order_id = o.id
    $whereClause
    ORDER BY d.created_at DESC
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
$deliveries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count
$countQuery = "SELECT COUNT(DISTINCT d.id) as total FROM deliveries d LEFT JOIN orders o ON d.order_id = o.id $whereClause";
if ($whereConditions) {
    $countStmt = $db->prepare($countQuery);
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    if ($countParams) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalDeliveries = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $totalDeliveries = $db->query($countQuery)->fetch_assoc()['total'];
}

$totalPages = ceil($totalDeliveries / $perPage);

// Get statistics
$stats = $db->query("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN delivery_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_count,
        SUM(CASE WHEN delivery_status = 'out_for_delivery' THEN 1 ELSE 0 END) as out_for_delivery_count,
        SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN delivery_status = 'failed' THEN 1 ELSE 0 END) as failed_count
    FROM deliveries
")->fetch_assoc();

$pageTitle = 'Delivery Management';
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
        .deliveries-management {
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
        }

        .stat-card.pending { border-left-color: #f59e0b; }
        .stat-card.in-transit { border-left-color: #3b82f6; }
        .stat-card.out-for-delivery { border-left-color: #8b5cf6; }
        .stat-card.delivered { border-left-color: #10b981; }
        .stat-card.failed { border-left-color: #ef4444; }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-create {
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Filters */
        .filters-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        /* Deliveries Table */
        .deliveries-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .deliveries-table {
            width: 100%;
            border-collapse: collapse;
        }

        .deliveries-table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .deliveries-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }

        .deliveries-table tr:hover {
            background: #f9fafb;
        }

        .tracking-number {
            font-family: monospace;
            font-weight: 600;
            color: #667eea;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
        }

        .customer-name {
            font-weight: 600;
            color: #1f2937;
        }

        .customer-email {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .driver-info {
            display: flex;
            flex-direction: column;
        }

        .driver-name {
            font-weight: 500;
        }

        .driver-phone {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-assigned { background: #dbeafe; color: #1e40af; }
        .badge-picked_up { background: #e0e7ff; color: #3730a3; }
        .badge-in_transit { background: #dbeafe; color: #1e3a8a; }
        .badge-out_for_delivery { background: #ede9fe; color: #5b21b6; }
        .badge-delivered { background: #d1fae5; color: #065f46; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .badge-returned { background: #f3f4f6; color: #374151; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            border: none;
            cursor: pointer;
        }

        .action-btn-view {
            background: #3b82f6;
            color: white;
        }

        .action-btn-edit {
            background: #10b981;
            color: white;
        }

        .action-btn-track {
            background: #8b5cf6;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
        }

        .pagination a:hover {
            background: #f3f4f6;
        }

        .pagination .current {
            background: #667eea;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 1024px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .deliveries-table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body class="deliveries-management">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-truck"></i>
                        Delivery Management
                    </h1>
                    <a href="delivery-create.php" class="btn-create">
                        <i class="fas fa-plus"></i> Create Delivery
                    </a>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card pending">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?= $stats['pending_count'] ?></div>
                    </div>
                    <div class="stat-card in-transit">
                        <div class="stat-label">In Transit</div>
                        <div class="stat-value"><?= $stats['in_transit_count'] ?></div>
                    </div>
                    <div class="stat-card out-for-delivery">
                        <div class="stat-label">Out for Delivery</div>
                        <div class="stat-value"><?= $stats['out_for_delivery_count'] ?></div>
                    </div>
                    <div class="stat-card delivered">
                        <div class="stat-label">Delivered</div>
                        <div class="stat-value"><?= $stats['delivered_count'] ?></div>
                    </div>
                    <div class="stat-card failed">
                        <div class="stat-label">Failed</div>
                        <div class="stat-value"><?= $stats['failed_count'] ?></div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-container">
                    <form method="GET" action="">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="filter-input"
                                       placeholder="Tracking number, driver, customer..." 
                                       value="<?= htmlspecialchars($searchQuery) ?>">
                            </div>
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="assigned" <?= $statusFilter === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                    <option value="picked_up" <?= $statusFilter === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                    <option value="in_transit" <?= $statusFilter === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                                    <option value="out_for_delivery" <?= $statusFilter === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    <option value="returned" <?= $statusFilter === 'returned' ? 'selected' : '' ?>>Returned</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="date_range">Date Range</label>
                                <select id="date_range" name="date_range" class="filter-select">
                                    <option value="">All Time</option>
                                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="action-btn action-btn-view">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Deliveries Table -->
                <div class="deliveries-table-container">
                    <?php if (!empty($deliveries)): ?>
                        <table class="deliveries-table">
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Driver</th>
                                    <th>Status</th>
                                    <th>Estimated Delivery</th>
                                    <th>Current Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td>
                                            <div class="tracking-number"><?= htmlspecialchars($delivery['tracking_number']) ?></div>
                                        </td>
                                        <td>
                                            <a href="orders.php?view=<?= $delivery['order_id'] ?>" style="color: #667eea;">
                                                <?= htmlspecialchars($delivery['order_number'] ?? 'N/A') ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <div class="customer-name"><?= htmlspecialchars($delivery['customer_name'] ?? 'N/A') ?></div>
                                                <div class="customer-email"><?= htmlspecialchars($delivery['customer_email'] ?? 'N/A') ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($delivery['driver_name']): ?>
                                                <div class="driver-info">
                                                    <div class="driver-name"><?= htmlspecialchars($delivery['driver_name']) ?></div>
                                                    <div class="driver-phone"><?= htmlspecialchars($delivery['driver_phone'] ?? '') ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $delivery['delivery_status'] ?>">
                                                <?= str_replace('_', ' ', $delivery['delivery_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $delivery['estimated_delivery_date'] ? date('M d, Y', strtotime($delivery['estimated_delivery_date'])) : 'N/A' ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($delivery['current_location'] ?? 'N/A') ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="delivery-details.php?id=<?= $delivery['id'] ?>" class="action-btn action-btn-view" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="delivery-edit.php?id=<?= $delivery['id'] ?>" class="action-btn action-btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../track-delivery.php?tracking=<?= $delivery['tracking_number'] ?>" 
                                                   class="action-btn action-btn-track" title="Track" target="_blank">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>&search=<?= $searchQuery ?>&date_range=<?= $dateFilter ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>&search=<?= $searchQuery ?>&date_range=<?= $dateFilter ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>&search=<?= $searchQuery ?>&date_range=<?= $dateFilter ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <h3>No Deliveries Found</h3>
                            <p>No deliveries match your search criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
