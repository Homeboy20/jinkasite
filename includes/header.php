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

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Header -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="<?php echo site_url('index.php'); ?>" class="logo-link">
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

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <nav class="nav" id="mobileNav">
                <div class="nav-container">
                    <div class="mobile-menu-header">
                        <strong style="color: var(--primary-color);">Menu</strong>
                        <button class="mobile-menu-close" id="mobileMenuClose">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form class="header-search" action="<?php echo site_url('products.php'); ?>" method="get">
                        <input type="search" name="q" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button type="submit" class="btn btn-outline">Search</button>
                    </form>

                    <div class="nav-links">
                        <a href="<?php echo site_url('index.php'); ?>" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>Home</a>
                        <a href="<?php echo site_url('products.php'); ?>" <?php echo $current_page === 'products.php' ? 'class="active"' : ''; ?>>Products</a>
                        <a href="<?php echo site_url('index.php'); ?>#features">Features</a>
                        <a href="<?php echo site_url('index.php'); ?>#specifications">Specs</a>
                        <a href="<?php echo site_url('contact.php'); ?>" <?php echo $current_page === 'contact.php' ? 'class="active"' : ''; ?>>Contact</a>
                        <a href="<?php echo site_url('cart.php'); ?>" class="cart-link <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>">
                            Cart
                            <?php if ($header_cart_count > 0): ?>
                                <span class="cart-badge" id="header-cart-badge"><?php echo $header_cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="header-actions">
                        <?php if (!empty($contact_phone_link)): ?>
                            <a href="tel:<?php echo htmlspecialchars($contact_phone_link); ?>" class="btn btn-outline">📞 Call TZ</a>
                        <?php endif; ?>
                        <?php if (!empty($whatsapp_number_link)): ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp_number_link); ?>" class="btn btn-primary" target="_blank">💬 WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>
