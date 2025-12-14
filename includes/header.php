<?php
/**
 * Unified Header Component
 * 
 * This header is used across all pages to maintain consistency.
 * It includes responsive navigation, mobile menu, search, and cart badge.
 */

// Get cart instance for item count
require_once __DIR__ . '/Cart.php';
$header_cart = new Cart();
$header_cart_count = $header_cart->getItemCount();

// Get currency detector
$currencyDetector = CurrencyDetector::getInstance();
$currentCurrency = $currencyDetector->getCurrency();
$availableCurrencies = $currencyDetector->getAvailableCurrencies();

// Check if customer is logged in
$is_customer_logged_in = false;
$customer_data = null;

if (isset($conn) && $conn) {
    require_once __DIR__ . '/CustomerAuth.php';
    $header_auth = new CustomerAuth($conn);
    $is_customer_logged_in = $header_auth->isLoggedIn();
    $customer_data = $is_customer_logged_in ? $header_auth->getCustomerData() : null;
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Modern Redesigned Header -->
<header class="header">
    <div class="header-top">
        <div class="container">
            <div class="header-top-content">
                <div class="header-contact-info">
                    <a href="tel:<?php echo htmlspecialchars($phone ?? ''); ?>" class="header-contact-link">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <span><?php echo htmlspecialchars($phone ?? ''); ?></span>
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($email ?? ''); ?>" class="header-contact-link">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <span><?php echo htmlspecialchars($email ?? ''); ?></span>
                    </a>
                </div>
                <div class="header-top-actions">
                    <!-- Currency Switcher -->
                    <div class="currency-switcher-compact">
                        <button id="currencyToggle" class="currency-toggle-btn">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                            <span id="currentCurrencyDisplay"><?php echo $currentCurrency; ?></span>
                            <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <div id="currencyDropdown" class="currency-dropdown">
                            <?php foreach ($availableCurrencies as $code => $details): ?>
                            <button class="currency-option" data-currency="<?php echo $code; ?>" <?php echo $code === $currentCurrency ? 'data-active="true"' : ''; ?>>
                                <span><?php echo $details['code']; ?> - <?php echo $details['symbol']; ?></span>
                                <?php if ($code === $currentCurrency): ?>
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="header-main">
        <div class="container">
            <div class="header-main-content">
                <div class="logo">
                    <a href="<?php echo site_url('/'); ?>" class="logo-link">
                        <?php if (!empty($site_logo)): ?>
                            <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" class="site-logo">
                        <?php else: ?>
                            <h1><?php echo htmlspecialchars($site_name); ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($site_tagline)): ?>
                            <p class="tagline"><?php echo htmlspecialchars($site_tagline); ?></p>
                        <?php endif; ?>
                    </a>
                </div>

                <form class="header-search-bar" action="<?php echo site_url('products'); ?>" method="get">
                    <div class="search-input-wrapper">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" class="search-icon"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input type="search" name="q" placeholder="Search for cutting plotters, accessories..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                </form>

                <div class="header-main-actions">
                    <?php if ($is_customer_logged_in): ?>
                    <div class="account-menu">
                        <button id="accountToggle" class="account-toggle-btn">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span class="account-name"><?php echo htmlspecialchars($customer_data['first_name']); ?></span>
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24" class="dropdown-arrow"><path d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <div id="accountDropdown" class="account-dropdown">
                            <a href="customer-account.php"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg> Dashboard</a>
                            <a href="customer-orders.php"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg> My Orders</a>
                            <a href="customer-wishlist.php"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> Wishlist</a>
                            <a href="customer-addresses.php"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> Addresses</a>
                            <a href="customer-profile.php"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="customer-logout.php" class="logout-link"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="customer-login.php" class="btn btn-outline-header">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Login
                    </a>
                    <a href="customer-register.php" class="btn btn-primary-header">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Sign Up
                    </a>
                    <?php endif; ?>

                    <a href="<?php echo site_url('cart'); ?>" class="cart-btn">
                        <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        <?php if ($header_cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $header_cart_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (!empty($whatsapp_number_link)): ?>
                    <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>?text=Hi,%20I%27m%20interested%20in%20your%20products" class="whatsapp-btn" target="_blank">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        <span>Chat Now</span>
                    </a>
                    <?php endif; ?>
                </div>

                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>

    <nav class="header-nav">
        <div class="container">
            <div class="nav-links">
                <a href="<?php echo site_url('/'); ?>" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>Home</a>
                <a href="<?php echo site_url('products'); ?>" <?php echo $current_page === 'products.php' ? 'class="active"' : ''; ?>>Shop Products</a>
                <a href="<?php echo site_url('contact'); ?>" <?php echo $current_page === 'contact.php' ? 'class="active"' : ''; ?>>Get Quote</a>
                <a href="#" class="has-submenu">Categories <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></a>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <h3>Menu</h3>
                <button class="mobile-menu-close" id="mobileMenuClose">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form class="mobile-search" action="<?php echo site_url('products'); ?>" method="get">
                <input type="search" name="q" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                </button>
            </form>
            <div class="mobile-nav-links">
                <a href="<?php echo site_url('/'); ?>">Home</a>
                <a href="<?php echo site_url('products'); ?>">Shop Products</a>
                <a href="<?php echo site_url('contact'); ?>">Get Quote</a>
                <a href="<?php echo site_url('cart'); ?>">
                    Cart
                    <?php if ($header_cart_count > 0): ?>
                    <span class="mobile-cart-badge"><?php echo $header_cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($is_customer_logged_in): ?>
                <a href="customer-account.php">My Account</a>
                <a href="customer-orders.php">My Orders</a>
                <a href="customer-wishlist.php">Wishlist</a>
                <a href="customer-profile.php">Settings</a>
                <a href="customer-logout.php" style="color: #dc2626;">Logout</a>
                <?php else: ?>
                <a href="customer-login.php">Login</a>
                <a href="customer-register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>


<script>
// Header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Currency Switcher
    const currencyToggle = document.getElementById('currencyToggle');
    const currencyDropdown = document.getElementById('currencyDropdown');
    const currencyOptions = document.querySelectorAll('.currency-option');
    const currentCurrencyDisplay = document.getElementById('currentCurrencyDisplay');

    if (currencyToggle && currencyDropdown) {
        currencyToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            currencyDropdown.style.display = currencyDropdown.style.display === 'none' ? 'block' : 'none';
            if (accountDropdown) accountDropdown.style.display = 'none';
        });

        currencyOptions.forEach(option => {
            option.addEventListener('click', async function(e) {
                e.stopPropagation();
                const currency = this.dataset.currency;
                
                try {
                    const response = await fetch('<?php echo site_url('api/currency'); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ currency: currency })
                    });

                    const data = await response.json();
                    if (data.success) {
                        currentCurrencyDisplay.textContent = currency;
                        currencyDropdown.style.display = 'none';
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Currency switch error:', error);
                }
            });
        });
    }

    // Account Dropdown
    const accountToggle = document.getElementById('accountToggle');
    const accountDropdown = document.getElementById('accountDropdown');
    
    if (accountToggle && accountDropdown) {
        accountToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            accountDropdown.style.display = accountDropdown.style.display === 'none' ? 'block' : 'none';
            if (currencyDropdown) currencyDropdown.style.display = 'none';
        });
    }

    // Mobile Menu
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileMenuClose = document.getElementById('mobileMenuClose');

    if (mobileMenuToggle && mobileMenuOverlay) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        mobileMenuClose.addEventListener('click', function() {
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        mobileMenuOverlay.addEventListener('click', function(e) {
            if (e.target === mobileMenuOverlay) {
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (currencyDropdown) currencyDropdown.style.display = 'none';
        if (accountDropdown) accountDropdown.style.display = 'none';
    });
});
</script>

