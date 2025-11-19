<?php
// Get current page to highlight active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>JINKA Admin</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="products.php" class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>">
            <span class="nav-icon">📦</span> Products
        </a>
        <a href="media.php" class="nav-link <?= $current_page == 'media.php' ? 'active' : '' ?>">
            <span class="nav-icon">🖼️</span> Media Manager
        </a>
        <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏷️</span> Categories
        </a>
        <a href="orders.php" class="nav-link <?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <span class="nav-icon">🛒</span> Orders
        </a>
        <a href="transactions.php" class="nav-link <?= $current_page == 'transactions.php' ? 'active' : '' ?>">
            <span class="nav-icon">💳</span> Transactions
        </a>
        <a href="deliveries.php" class="nav-link <?= $current_page == 'deliveries.php' ? 'active' : '' ?>">
            <span class="nav-icon">🚚</span> Deliveries
        </a>
        <a href="customers.php" class="nav-link <?= $current_page == 'customers.php' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Customers
        </a>
        <a href="inquiries.php" class="nav-link <?= $current_page == 'inquiries.php' ? 'active' : '' ?>">
            <span class="nav-icon">❓</span> Inquiries
        </a>
        <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>">
            <span class="nav-icon">👨‍💼</span> Users
        </a>
        <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <span class="nav-icon">⚙️</span> Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </div>
</aside>