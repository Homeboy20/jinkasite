<?php
/**
 * Customer Account Sidebar Navigation
 * Reusable sidebar for all customer account pages
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="account-sidebar">
    <nav>
        <ul class="account-nav">
            <li>
                <a href="customer-account.php" <?php echo $current_page === 'customer-account.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="customer-orders.php" <?php echo $current_page === 'customer-orders.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    My Orders
                </a>
            </li>
            <li>
                <a href="customer-order-details.php" <?php echo $current_page === 'customer-order-details.php' ? 'class="active"' : ''; ?> style="display: none;" id="orderDetailsNav">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    Order Details
                </a>
            </li>
            <li>
                <a href="customer-wishlist.php" <?php echo $current_page === 'customer-wishlist.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    Wishlist
                </a>
            </li>
            <li>
                <a href="customer-addresses.php" <?php echo $current_page === 'customer-addresses.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    Addresses
                </a>
            </li>
            <li>
                <a href="customer-reviews.php" <?php echo $current_page === 'customer-reviews.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/>
                    </svg>
                    Reviews
                </a>
            </li>
            <li>
                <a href="customer-notifications.php" <?php echo $current_page === 'customer-notifications.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    Notifications
                </a>
            </li>
            <li>
                <a href="customer-support.php" <?php echo $current_page === 'customer-support.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 12.22C21 6.73 16.74 3 12 3c-4.69 0-9 3.65-9 9.28-.6.34-1 .98-1 1.72v2c0 1.1.9 2 2 2h1v-6.1c0-3.87 3.13-7 7-7s7 3.13 7 7V19h-8v2h8c1.1 0 2-.9 2-2v-1.22c.59-.31 1-.92 1-1.64v-2.3c0-.7-.41-1.31-1-1.62z"/>
                    </svg>
                    Support
                </a>
            </li>
            <li>
                <a href="customer-profile.php" <?php echo $current_page === 'customer-profile.php' ? 'class="active"' : ''; ?>>
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                    </svg>
                    Profile Settings
                </a>
            </li>
            <li style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <a href="customer-logout.php" style="color: #dc2626;">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

<style>
.account-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.account-sidebar {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.account-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-nav li {
    margin-bottom: 0.5rem;
}

.account-nav a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    color: #64748b;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.account-nav a:hover,
.account-nav a.active {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
}

.account-nav svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.account-main {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    min-height: 500px;
}

@media (max-width: 1024px) {
    .account-grid {
        grid-template-columns: 1fr;
        padding: 1rem;
    }
    
    .account-sidebar {
        position: relative;
        top: 0;
    }
    
    .account-nav {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.5rem;
    }
    
    .account-nav li {
        margin-bottom: 0;
    }
}

@media (max-width: 640px) {
    .account-nav {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Show order details nav item if on order details page
if (window.location.pathname.includes('customer-order-details')) {
    const orderDetailsNav = document.getElementById('orderDetailsNav');
    if (orderDetailsNav) {
        orderDetailsNav.style.display = 'flex';
    }
}
</script>

