<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';
require_once 'includes/CurrencyDetector.php';
$firebase_config = require_once 'includes/firebase-config.php';

$cart = new Cart();
$items = $cart->getItems();
$totals = $cart->getTotals();
$validation = $cart->validateCart();

if (!function_exists('compute_cart_hash')) {
    function compute_cart_hash(array $items, array $totals) {
        $sortedItems = $items;
        if (!empty($sortedItems)) {
            ksort($sortedItems);
        }
        return hash_hmac(
            'sha256',
            json_encode(['items' => $sortedItems, 'totals' => $totals], JSON_UNESCAPED_SLASHES),
            SECRET_KEY
        );
    }
}

$checkout_cart_hash = compute_cart_hash($items, $totals);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$checkout_csrf = bin2hex(random_bytes(32));
$_SESSION['checkout_csrf'] = $checkout_csrf;
$_SESSION['checkout_cart_hash'] = $checkout_cart_hash;

// Get current currency
$currencyDetector = CurrencyDetector::getInstance();
$checkoutCurrency = $currencyDetector->getCurrency();
$isUSDCheckout = ($checkoutCurrency === 'USD');

$site_name = site_setting('site_name', 'ProCut Solutions');
$site_logo = site_setting('site_logo', '');
$site_favicon_setting = trim(site_setting('site_favicon', ''));
$default_favicon_path = 'images/favicon.ico';
$site_favicon = '';

if ($site_favicon_setting !== '') {
    if (preg_match('#^https?://#i', $site_favicon_setting)) {
        $site_favicon = $site_favicon_setting;
    } else {
        $site_favicon = site_url($site_favicon_setting);
    }
} elseif (file_exists(__DIR__ . '/' . $default_favicon_path)) {
    $site_favicon = site_url($default_favicon_path);
}
$site_tagline = site_setting('site_tagline', 'Professional Printing Equipment');
$contact_phone = site_setting('contact_phone', '+255753098911');
$contact_phone_ke = site_setting('contact_phone_ke', $contact_phone);
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');

$contact_phone_link = preg_replace('/[^0-9+]/', '', $contact_phone);
if ($contact_phone_link !== '' && $contact_phone_link[0] !== '+') {
    $contact_phone_link = '+' . ltrim($contact_phone_link, '+');
}

$contact_phone_ke_link = preg_replace('/[^0-9+]/', '', $contact_phone_ke);
if ($contact_phone_ke_link !== '' && $contact_phone_ke_link[0] !== '+') {
    $contact_phone_ke_link = '+' . ltrim($contact_phone_ke_link, '+');
}

$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);

// Get payment gateway settings
$db = Database::getInstance()->getConnection();

function getPaymentSetting($key, $default = '') {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Check which payment gateways are enabled
$flutterwave_enabled = getPaymentSetting('flutterwave_enabled', '1') === '1';
$azampay_enabled = getPaymentSetting('azampay_enabled', '0') === '1';
$mpesa_enabled = getPaymentSetting('mpesa_enabled', '0') === '1';
$pesapal_enabled = getPaymentSetting('pesapal_enabled', '0') === '1';
$paypal_enabled = getPaymentSetting('paypal_enabled', '0') === '1';
$stripe_enabled = getPaymentSetting('stripe_enabled', '0') === '1';

$page_title = 'Checkout | ' . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-modern.css">
    <link rel="stylesheet" href="css/theme-variables.php">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f1f5f9;
        }
        .checkout-page {
            padding: 3rem 0;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 2rem;
            align-items: start;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
        }
        .card h2 {
            margin: 0 0 1.5rem;
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
        }
        .card h3 {
            margin: 2rem 0 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }
        .form-grid.full {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        label {
            font-weight: 600;
            color: #1f2937;
            display: block;
            margin-bottom: 0.5rem;
        }
        .input-with-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .input-with-action input {
            flex: 1;
        }
        .btn-compact {
            padding: 0.65rem 0.9rem;
            font-size: 0.95rem;
            border-radius: 10px;
            background: #e2e8f0;
            border: 2px solid #e2e8f0;
            color: #0f172a;
            cursor: pointer;
            font-weight: 700;
        }
        .btn-compact:hover {
            background: #d9e2ec;
        }
        .verify-status {
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }
        .verify-status.success { color: #15803d; }
        .verify-status.error { color: #b91c1c; }
        .verify-status.pending { color: #b45309; }
        input, textarea, select {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 1rem;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #ff5900;
            background: #fff;
            outline: none;
        }
        textarea {
            min-height: 110px;
            resize: vertical;
        }
        
        /* Step Navigation Buttons */
        .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        .btn {
            padding: 0.9rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 89, 0, 0.3);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }
        .checkout-step {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .region-tabs {
            display: flex;
            gap: 0.75rem;
            margin: 2rem 0 1.5rem;
            flex-wrap: wrap;
        }
        .region-tab {
            padding: 0.75rem 1.25rem;
            border-radius: 9999px;
            border: 2px solid #fed7aa;
            background: #fff;
            color: #e64f00;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .region-tab.active {
            background: #e64f00;
            border-color: #e64f00;
            color: #fff;
            box-shadow: 0 10px 25px rgba(230,79,0,0.25);
        }
        .payment-panels {
            display: grid;
            gap: 1rem;
        }
        .payment-panel {
            display: none;
            gap: 1rem;
        }
        .payment-panel.active {
            display: grid;
        }
        .payment-option {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.5rem;
            display: grid;
            gap: 1rem;
            background: #f8fafc;
        }
        .payment-option h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
        }
        .payment-option p {
            margin: 0;
            color: #475569;
            line-height: 1.5;
        }
        .supporting-text {
            font-size: 0.9rem;
            color: #64748b;
        }
        .pay-button {
            justify-self: start;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: none;
            background: #ff5900;
            color: #fff;
            font-weight: 700;
            padding: 0.9rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .pay-button.secondary {
            background: #0f172a;
        }
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255,89,0,0.35);
        }
        .pay-button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .order-card h2 {
            font-size: 1.6rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-item span {
            font-weight: 600;
        }
        .currency-switch {
            display: inline-flex;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 1rem;
        }
        .currency-switch button {
            border: none;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            padding: 0.55rem 1.1rem;
            cursor: pointer;
        }
        .currency-switch button.active {
            background: #e64f00;
            color: #fff;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
        }
        .item-list {
            margin-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5rem;
            display: grid;
            gap: 1rem;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }
        .item-row h5 {
            margin: 0;
            font-size: 1rem;
            color: #1f2937;
        }
        .empty-cart-alert {
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: 10px;
            color: #991b1b;
            font-weight: 600;
        }
        .empty-cart-alert a {
            color: #e64f00;
        }
        .item-primary {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .item-amounts {
            text-align: right;
            min-width: 120px;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            align-items: flex-end;
        }
        .checkout-item-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        .item-quantity {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .qty-btn {
            background: #fff;
            border: none;
            width: 34px;
            height: 32px;
            font-weight: 700;
            cursor: pointer;
            color: #1f2937;
        }
        .qty-btn:hover {
            background: #ffedd5;
            color: #e64f00;
        }
        .qty-input {
            width: 50px;
            height: 32px;
            border: none;
            text-align: center;
            font-weight: 600;
            background: #f8fafc;
        }
        .qty-input:focus {
            outline: none;
        }
        .item-remove {
            border: none;
            background: none;
            color: #ef4444;
            font-weight: 600;
            cursor: pointer;
        }
        .item-remove:hover {
            text-decoration: underline;
        }
        .checkout-message {
            display: none;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        .checkout-message.success {
            display: block;
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            color: #166534;
        }
        .checkout-message.error {
            display: block;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }
        .item-row.updating {
            opacity: 0.6;
            pointer-events: none;
        }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }
        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }
        .message-area {
            margin-top: 1rem;
            display: none;
            border-radius: 10px;
            padding: 0.9rem 1.25rem;
            font-weight: 600;
        }
        .message-area.success {
            display: block;
            background: #dcfce7;
            color: #166534;
        }
        .message-area.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }

        /* Checkout-specific styles */
        @media (max-width: 1024px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            .region-tabs {
                gap: 0.5rem;
            }
            .region-tab {
                flex: 1 1 45%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Minimal Checkout Header -->
    <header class="header" style="padding: 1rem 0; border-bottom: 1px solid #e2e8f0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo">
                    <a href="<?php echo site_url('/'); ?>" style="text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                        <?php if (!empty($site_logo)): ?>
                            <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 36px; width: auto;">
                        <?php else: ?>
                            <span style="font-size: 1.25rem; font-weight: 700; color: #ff5900;"><?php echo htmlspecialchars($site_name); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="<?php echo site_url('cart'); ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        Back to Cart
                    </a>
                    <span style="color: #cbd5e1;">|</span>
                    <span style="color: #64748b; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem;">
                        <svg width="18" height="18" fill="#10b981" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        Secure Checkout
                    </span>
                </div>
            </div>
        </div>
    </header>

    <section class="checkout-page">
        <div class="container">
            <!-- 5-Step Checkout Progress -->
            <ul class="checkout-progress">
                <li class="step completed" data-step="1">
                    <div class="step-marker">
                        <span class="step-number">1</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Shopping Cart</span>
                        <span class="step-caption">Items selected</span>
                    </div>
                </li>
                <li class="step active" data-step="2">
                    <div class="step-marker">
                        <span class="step-number">2</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Information</span>
                        <span class="step-caption">Fill your details</span>
                    </div>
                </li>
                <li class="step" data-step="3">
                    <div class="step-marker">
                        <span class="step-number">3</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Shipping</span>
                        <span class="step-caption">Delivery address</span>
                    </div>
                </li>
                <li class="step" data-step="4">
                    <div class="step-marker">
                        <span class="step-number">4</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Payment</span>
                        <span class="step-caption">Secure checkout</span>
                    </div>
                </li>
                <li class="step" data-step="5">
                    <div class="step-marker">
                        <span class="step-number">5</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Complete</span>
                        <span class="step-caption">Order confirmed</span>
                    </div>
                </li>
            </ul>

            <?php if (!empty($validation['errors'])): ?>
                <div class="alert alert-warning">
                    <strong>Cart updated:</strong>
                    <ul>
                        <?php foreach ($validation['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($items)): ?>
                <div class="alert alert-error">
                    Your cart is empty. <a href="products.php">Add products</a> to proceed to checkout.
                </div>
            <?php else: ?>
                <div class="checkout-grid">
                    <div class="card">
                        <!-- Step 2: Customer Information -->
                        <div class="checkout-step" id="step-2" style="display: block;">
                            <!-- Trust Badges -->
                            <div class="checkout-trust-header" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem;">
                                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: space-around; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                                        <span style="font-size: 0.9rem; font-weight: 600; color: #0f172a;">Secure Checkout</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                        <span style="font-size: 0.9rem; font-weight: 600; color: #0f172a;">Free Shipping</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <svg width="24" height="24" fill="#10b981" viewBox="0 0 24 24"><path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 00-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2z"/></svg>
                                        <span style="font-size: 0.9rem; font-weight: 600; color: #0f172a;">12 Month Warranty</span>
                                    </div>
                                </div>
                            </div>
                            
                            <h2>Customer Information</h2>
                            <div class="form-grid">
                                <div>
                                    <label for="customerName">Full Name *</label>
                                    <input type="text" id="customerName" placeholder="Jane Doe" required>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                                        <label for="customerEmail" style="margin-bottom: 0;">Email Address *</label>
                                        <span id="emailVerifyStatus" class="verify-status">Not verified</span>
                                    </div>
                                    <div class="input-with-action">
                                        <input type="email" id="customerEmail" placeholder="name@example.com" required>
                                        <button type="button" class="btn-compact" id="emailVerifyBtn">Verify Email</button>
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                                        <label for="customerPhone" style="margin-bottom: 0;">Phone Number *</label>
                                        <span id="phoneVerifyStatus" class="verify-status">Not verified</span>
                                    </div>
                                    <div class="input-with-action">
                                        <input type="tel" id="customerPhone" placeholder="+2547..." required>
                                        <button type="button" class="btn-compact" id="phoneOtpBtn">Send OTP</button>
                                    </div>
                                    <div id="phoneOtpSection" style="display: none; margin-top: 0.5rem;">
                                        <div class="input-with-action">
                                            <input type="text" id="phoneOtpInput" placeholder="Enter OTP">
                                            <button type="button" class="btn-compact" id="phoneOtpVerifyBtn">Verify OTP</button>
                                        </div>
                                    </div>
                                    <div id="recaptcha-container" style="display: none;"></div>
                                </div>
                                <div>
                                    <label for="customerCountry">Country *</label>
                                    <select id="customerCountry" required>
                                        <option value="">Select your country</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="South Sudan">South Sudan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-grid full" style="margin-top: 1.25rem;">
                                <div>
                                    <label for="orderNotes">Order Notes (Optional)</label>
                                    <textarea id="orderNotes" placeholder="Share delivery instructions or company details..."></textarea>
                                </div>
                            </div>
                            
                            <div class="step-navigation" style="margin-top: 2rem; display: flex; justify-content: space-between;">
                                <a href="cart.php" class="btn btn-secondary" style="text-decoration: none;">
                                    ← Back to Cart
                                </a>
                                <button type="button" class="btn btn-primary" onclick="goToStep(3)">
                                    Continue to Shipping →
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Shipping & Delivery Details -->
                        <div class="checkout-step" id="step-3" style="display: none;">
                            <h2>Shipping & Delivery Details</h2>
                            <div class="form-grid">
                                <div class="form-grid full">
                                    <div>
                                        <label for="shippingAddress">Street Address / Building *</label>
                                        <input type="text" id="shippingAddress" placeholder="123 Main Street, Apt 4B" required autocomplete="address-line1">
                                    </div>
                                </div>
                                <div>
                                    <label for="shippingCity">City / Town *</label>
                                    <input type="text" id="shippingCity" placeholder="Nairobi" required list="shippingCityList" autocomplete="address-level2">
                                </div>
                                <div>
                                    <label for="shippingState">State / Region</label>
                                    <input type="text" id="shippingState" placeholder="Nairobi County">
                                </div>
                                <div>
                                    <label for="shippingPostalCode">Postal / ZIP Code</label>
                                    <input type="text" id="shippingPostalCode" placeholder="00100">
                                </div>
                                <div>
                                    <label for="shippingCountry">Country *</label>
                                    <select id="shippingCountry" required>
                                        <option value="">Select destination country</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="South Sudan">South Sudan</option>
                                    </select>
                                </div>
                            </div>

                            <datalist id="shippingCityList">
                                <option value="Nairobi">
                                <option value="Mombasa">
                                <option value="Kisumu">
                                <option value="Nakuru">
                                <option value="Dar es Salaam">
                                <option value="Arusha">
                            </datalist>
                            
                            <h3 style="margin-top: 2rem;">Delivery Preferences</h3>
                            <div class="form-grid">
                                <div class="form-grid full">
                                    <div>
                                        <label for="deliveryMethod">Delivery Method</label>
                                        <select id="deliveryMethod" class="form-control" style="width: 100%; padding: 0.9rem 1rem; border-radius: 10px; border: 2px solid #e2e8f0; background: #f8fafc; font-size: 1rem;">
                                            <option value="standard">Standard Delivery (3-5 business days)</option>
                                            <option value="express">Express Delivery (1-2 business days)</option>
                                            <option value="pickup">Store Pickup</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="deliveryContactName">Contact Person for Delivery</label>
                                    <input type="text" id="deliveryContactName" placeholder="Person to receive delivery">
                                </div>
                                <div>
                                    <label for="deliveryContactPhone">Delivery Contact Phone</label>
                                    <input type="tel" id="deliveryContactPhone" placeholder="+254...">
                                </div>
                            </div>
                            <div class="form-grid full" style="margin-top: 1.25rem;">
                                <div>
                                    <label for="deliveryInstructions">Special Delivery Instructions</label>
                                    <textarea id="deliveryInstructions" placeholder="Gate code, landmark, preferred delivery time, etc..." style="min-height: 90px;"></textarea>
                                </div>
                            </div>
                            
                            <div class="step-navigation" style="margin-top: 2rem; display: flex; justify-content: space-between;">
                                <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                                    ← Back to Information
                                </button>
                                <button type="button" class="btn btn-primary" onclick="goToStep(4)">
                                    Continue to Payment →
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Payment -->
                        <div class="checkout-step" id="step-4" style="display: none;">
                            <!-- Payment Trust Section -->
                            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; border-left: 4px solid #10b981;">
                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <svg width="32" height="32" fill="#10b981" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                                    <div>
                                        <h3 style="margin: 0 0 0.5rem; font-size: 1.1rem; color: #166534;">Your Payment is 100% Secure</h3>
                                        <p style="margin: 0; font-size: 0.9rem; color: #166534;">All transactions are encrypted and processed through trusted payment gateways. Your financial information is never stored on our servers.</p>
                                        <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; font-weight: 600; color: #166534;">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                                SSL Encrypted
                                            </span>
                                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; font-weight: 600; color: #166534;">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                                PCI Compliant
                                            </span>
                                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; font-weight: 600; color: #166534;">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                                Money-Back Guarantee
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h2>Choose Payment Method</h2>
                            <div style="padding: 1rem; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; border-radius: 8px; margin-bottom: 1.5rem;">
                                <p style="margin: 0; color: #1e40af; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                                    All Payments in <?php echo $checkoutCurrency; ?>
                                </p>
                                <p style="margin: 0.5rem 0 0 0; color: #1e3a8a; font-size: 0.875rem;">Your order total is <?php echo $currencyDetector->formatPrice($totals['total'], $checkoutCurrency); ?>. All payment methods below accept <?php echo $checkoutCurrency; ?>.</p>
                            </div>
                            <?php if ($isUSDCheckout): ?>
                            <div style="padding: 1rem; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; margin-bottom: 1.5rem;">
                                <p style="margin: 0; color: #92400e; font-weight: 600;">💳 International Checkout (USD)</p>
                                <p style="margin: 0.5rem 0 0 0; color: #78350f; font-size: 0.875rem;">Card payment options available for USD transactions.</p>
                            </div>
                            <?php else: ?>
                            <h3>Choose Payment Region</h3>
                            <div class="region-tabs" id="regionTabs">
                                <button type="button" class="region-tab active" data-region="kenya">Kenya</button>
                                <button type="button" class="region-tab" data-region="tanzania">Tanzania</button>
                                <button type="button" class="region-tab" data-region="global">Global / Other</button>
                            </div>
                            <?php endif; ?>

                            <div class="payment-panels">
                            <?php if (!$isUSDCheckout): ?>
                            <div class="payment-panel active" data-region="kenya">
                                <?php if ($mpesa_enabled): ?>
                                <div class="payment-option">
                                    <h4>Lipa na M-Pesa</h4>
                                    <p>Instant STK push to your Kenyan M-Pesa wallet. Confirm the prompt to complete payment.</p>
                                    <span class="supporting-text">Accepts: KES, TZS, UGX, USD (auto-converted)</span>
                                    <button class="pay-button" data-gateway="mpesa" data-accept-currencies="KES,TZS,UGX,USD">
                                        Pay with Lipa na M-Pesa
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>Mobile Money (M-Pesa Kenya)</h4>
                                    <p>Quick M-Pesa payment - enter your phone number and confirm the payment prompt.</p>
                                    <span class="supporting-text">Supports KES, TZS, UGX, USD</span>
                                    <button class="pay-button" data-gateway="flutterwave-mno" data-payment-method="mpesa" data-accept-currencies="KES,TZS,UGX,USD">
                                        <i class="fas fa-mobile-alt"></i> Pay with M-Pesa
                                    </button>
                                </div>
                                <div class="payment-option">
                                    <h4>Cards & International Payments</h4>
                                    <p>Secure Flutterwave checkout for cards, bank transfers, USSD, and more payment methods.</p>
                                    <span class="supporting-text">Multi-currency support</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer,ussd" data-accept-currencies="KES,TZS,UGX,USD">
                                        <i class="fas fa-credit-card"></i> Pay with Cards/Bank
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!$isUSDCheckout): ?>
                            <div class="payment-panel" data-region="tanzania">
                                <?php if ($azampay_enabled): ?>
                                <div class="payment-option">
                                    <h4>AzamPay</h4>
                                    <p>Recommended for Tanzania payments. Supports Tigo Pesa, M-Pesa, Airtel Money, and local cards.</p>
                                    <span class="supporting-text">Accepts: TZS, KES, UGX, USD (auto-converted)</span>
                                    <button class="pay-button" data-gateway="azampay" data-accept-currencies="TZS,KES,UGX,USD">
                                        Pay with AzamPay
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>Mobile Money (Tanzania)</h4>
                                    <p>Quick mobile money payment - supports M-Pesa, Tigo Pesa, Airtel Money, and Halopesa.</p>
                                    <span class="supporting-text">Supports TZS, KES, UGX, USD</span>
                                    <button class="pay-button" data-gateway="flutterwave-mno" data-payment-method="mobilemoneytz" data-accept-currencies="TZS,KES,UGX,USD">
                                        <i class="fas fa-mobile-alt"></i> Pay with Mobile Money
                                    </button>
                                </div>
                                <div class="payment-option">
                                    <h4>Cards & Bank Transfer</h4>
                                    <p>Secure Flutterwave checkout for local and international cards plus bank transfers.</p>
                                    <span class="supporting-text">Multi-currency support</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer" data-accept-currencies="TZS,KES,UGX,USD">
                                        <i class="fas fa-credit-card"></i> Pay with Cards/Bank
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="payment-panel <?php echo $isUSDCheckout ? 'active' : ''; ?>" data-region="global" <?php echo $isUSDCheckout ? 'style="display: block;"' : ''; ?>>
                                <?php if ($pesapal_enabled): ?>
                                <div class="payment-option">
                                    <h4>Pesapal</h4>
                                    <p>Trusted East African gateway for cards, mobile wallets, and bank payments across multiple countries.</p>
                                    <span class="supporting-text">Accepts: KES, TZS, UGX, USD, GBP, EUR</span>
                                    <button class="pay-button" data-gateway="pesapal" data-accept-currencies="KES,TZS,UGX,USD,GBP,EUR">
                                        Pay with Pesapal
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>International Card Payments</h4>
                                    <p>Accept global credit/debit cards, Apple Pay, Google Pay, and bank transfers in multiple currencies.</p>
                                    <span class="supporting-text">Multi-currency with fraud protection</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer,applepay,googlepay" data-accept-currencies="KES,TZS,UGX,USD,GBP,EUR">
                                        <i class="fas fa-globe"></i> Pay with International Cards
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($paypal_enabled): ?>
                                <div class="payment-option">
                                    <h4>PayPal</h4>
                                    <p>Fast checkout for international shoppers using PayPal balance or linked cards.</p>
                                    <span class="supporting-text">Supports USD, EUR, GBP and 20+ currencies</span>
                                    <button class="pay-button" data-gateway="paypal" data-accept-currencies="USD,EUR,GBP,KES,TZS,UGX">
                                        Pay with PayPal
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($stripe_enabled): ?>
                                <div class="payment-option">
                                    <h4>Stripe Checkout</h4>
                                    <p>Global card processing with 3D Secure support and localized payment pages.</p>
                                    <span class="supporting-text">135+ currencies supported</span>
                                    <button class="pay-button secondary" data-gateway="stripe" data-accept-currencies="KES,TZS,UGX,USD,GBP,EUR">
                                        Pay with Stripe
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="paymentMessage" class="message-area"></div>
                        
                        <div class="step-navigation" style="margin-top: 2rem; display: flex; justify-content: space-between;">
                            <button type="button" class="btn btn-secondary" onclick="goToStep(3)">
                                ← Back to Shipping
                            </button>
                            <span style="color: #64748b; font-style: italic;">Select a payment method above to proceed</span>
                        </div>
                    </div>
                    <!-- End of all steps -->
                    </div>

                    <div class="card order-card">
                        <h2>Order Summary</h2>

                        <div id="checkoutCartMessage" class="checkout-message" style="display:none;"></div>

                        <!-- Currency Badge -->
                        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; font-weight: 600; margin-bottom: 1.5rem;">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                            <span>Payment Currency: <?php echo $checkoutCurrency; ?></span>
                            <?php if ($isUSDCheckout): ?>
                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">International</span>
                            <?php endif; ?>
                        </div>

                        <!-- Items List with Details -->
                        <div id="checkoutItemList" class="item-list" style="margin-bottom: 1.5rem;">
                            <?php 
                            $totalItems = 0;
                            foreach ($items as $productId => $item): 
                                $totalItems += $item['quantity'];
                                $itemPrice = $currencyDetector->getPrice($item['price_kes'], $checkoutCurrency);
                            ?>
                                <div class="item-row" data-product-id="<?php echo $productId; ?>" style="padding: 1rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.75rem;">
                                    <div class="item-primary">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                            <h5 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <button class="item-remove" data-product-id="<?php echo $productId; ?>" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; font-size: 1.25rem;" title="Remove item">×</button>
                                        </div>
                                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.75rem;">
                                            <span class="supporting-text" style="font-size: 0.8rem; color: #64748b;">SKU: <?php echo htmlspecialchars($item['sku']); ?></span>
                                            <span style="font-size: 0.8rem; color: #64748b;">•</span>
                                            <span style="font-size: 0.8rem; color: #10b981; font-weight: 600;">
                                                <?php if (!empty($item['track_stock'])): ?>
                                                    <?php echo (int)$item['stock_quantity'] > 0 ? 'In Stock' : 'Backorder'; ?>
                                                <?php else: ?>
                                                    Available
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div class="item-quantity" style="display: flex; align-items: center; gap: 0.5rem;">
                                                <button class="qty-btn qty-decrease" data-product-id="<?php echo $productId; ?>" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: white; border-radius: 4px; cursor: pointer; font-weight: 600; color: #64748b;">-</button>
                                                <input type="number" class="qty-input" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo $item['track_stock'] ? (int)$item['stock_quantity'] : 999; ?>" data-product-id="<?php echo $productId; ?>" style="width: 50px; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.25rem; font-weight: 600;">
                                                <button class="qty-btn qty-increase" data-product-id="<?php echo $productId; ?>" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: white; border-radius: 4px; cursor: pointer; font-weight: 600; color: #64748b;">+</button>
                                                <span style="font-size: 0.8rem; color: #64748b; margin-left: 0.5rem;">× <?php echo $currencyDetector->formatPrice($itemPrice, $checkoutCurrency); ?></span>
                                            </div>
                                            <div class="item-price" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                                                <?php echo $currencyDetector->formatPrice($itemPrice * $item['quantity'], $checkoutCurrency); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Summary Breakdown -->
                        <div class="summary-group" style="border-top: 2px solid #e2e8f0; padding-top: 1.5rem;">
                            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.85rem; color: #78350f;">
                                <strong>📦 <?php echo $totalItems; ?> item<?php echo $totalItems > 1 ? 's' : ''; ?></strong> in your order
                            </div>
                            
                            <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; color: #475569;">
                                <span>Subtotal</span>
                                <span class="summary-value" style="font-weight: 600;"><?php echo $currencyDetector->formatPrice($totals['subtotal'], $checkoutCurrency); ?></span>
                            </div>
                            
                            <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; color: #475569;">
                                <span style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Tax (<?php echo ($totals['tax_rate'] * 100); ?>%)</span>
                                    <?php if ($totals['tax_rate'] == 0): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600;">Tax Free</span>
                                    <?php endif; ?>
                                </span>
                                <span class="summary-value" style="font-weight: 600;"><?php echo $currencyDetector->formatPrice($totals['tax'], $checkoutCurrency); ?></span>
                            </div>
                            
                            <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; color: #64748b; font-size: 0.9rem;">
                                <span style="display: flex; align-items: center; gap: 0.5rem;">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                                    <span>Shipping</span>
                                </span>
                                <span style="font-style: italic;">Calculated after order</span>
                            </div>
                            
                            <div class="total-line" style="display: flex; justify-content: space-between; padding: 1.25rem 0; border-top: 2px solid #e2e8f0; margin-top: 0.5rem;">
                                <span style="font-size: 1.1rem; font-weight: 700; color: #0f172a;">Total Amount</span>
                                <span class="summary-value" style="font-size: 1.5rem; font-weight: 800; color: #ff5900;"><?php echo $currencyDetector->formatPrice($totals['total'], $checkoutCurrency); ?></span>
                            </div>
                            
                            <?php if (!$isUSDCheckout): ?>
                            <div style="background: #f0fdf4; border: 1px solid #86efac; padding: 0.75rem; border-radius: 6px; margin-top: 1rem; font-size: 0.85rem; color: #166534;">
                                <strong>✓ Secure Local Payment</strong> - Pay with M-Pesa, Mobile Money, or local cards
                            </div>
                            <?php else: ?>
                            <div style="background: #eff6ff; border: 1px solid #93c5fd; padding: 0.75rem; border-radius: 6px; margin-top: 1rem; font-size: 0.85rem; color: #1e40af;">
                                <strong>🌍 International Checkout</strong> - Secure card payment with fraud protection
                            </div>
                            <?php endif; ?>
                        </div>

                        <div id="checkoutEmptyNotice" class="empty-cart-alert" style="display:none;">
                            <p>Your cart is empty. <a href="products.php">Add products</a> to continue checkout.</p>
                        </div>

                        <a href="cart.php" style="display:inline-flex; align-items:center; gap:0.5rem; margin-top:1.5rem; font-weight:600; color:#ff5900; text-decoration: none;">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                            Modify cart
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Mobile Money Payment Modal -->
    <div id="mnoModal" class="mno-modal" style="display: none;">
        <div class="mno-modal-content">
            <div class="mno-modal-header">
                <h3 id="mnoModalTitle">Mobile Money Payment</h3>
                <button class="mno-modal-close" onclick="closeMnoModal()">&times;</button>
            </div>
            <div class="mno-modal-body">
                <div id="mnoPaymentForm">
                    <div class="form-group">
                        <label for="mnoPhoneNumber">Phone Number</label>
                        <input type="tel" id="mnoPhoneNumber" class="form-control" placeholder="e.g., 0712345678" required>
                        <small class="form-text">Enter your mobile money number</small>
                    </div>
                    
                    <div id="mnoNetworkSelect" class="form-group" style="display: none;">
                        <label for="mnoNetwork">Select Network</label>
                        <select id="mnoNetwork" class="form-control">
                            <option value="">Choose your network...</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="tigo">Tigo Pesa</option>
                            <option value="airtel">Airtel Money</option>
                            <option value="halopesa">Halopesa</option>
                        </select>
                    </div>

                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Amount to Pay:</span>
                            <strong id="mnoAmount"></strong>
                        </div>
                    </div>

                    <div class="mno-modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeMnoModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processMnoPayment()">
                            <i class="fas fa-mobile-alt"></i> Pay Now
                        </button>
                    </div>
                </div>

                <div id="mnoPaymentStatus" style="display: none;">
                    <div class="payment-status-icon">
                        <i class="fas fa-spinner fa-spin" id="mnoStatusIcon"></i>
                    </div>
                    <h4 id="mnoStatusTitle">Processing Payment...</h4>
                    <p id="mnoStatusMessage">Please check your phone for a payment prompt</p>
                    <div class="mno-modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeMnoModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mno-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        .mno-modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .mno-modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mno-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }
        .mno-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        .mno-modal-close:hover {
            color: #0f172a;
        }
        .mno-modal-body {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #ff5900;
        }
        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .payment-summary {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 1.25rem;
            margin: 1.5rem 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.125rem;
        }
        .summary-row strong {
            color: #ff5900;
            font-size: 1.5rem;
        }
        .mno-modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .mno-modal-actions .btn {
            flex: 1;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #ff5900;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #e64f00;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 89, 0, 0.4);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        .payment-status-icon {
            text-align: center;
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ff5900;
        }
        #mnoPaymentStatus {
            text-align: center;
        }
        #mnoPaymentStatus h4 {
            margin: 1rem 0 0.5rem;
            color: #0f172a;
        }
        #mnoPaymentStatus p {
            color: #64748b;
            margin-bottom: 2rem;
        }
    </style>

    <!-- Minimal Footer -->
    <footer style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 2rem 0; margin-top: 4rem;">
        <div class="container">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.5rem; text-align: center;">
                <div style="flex: 1; min-width: 200px;">
                    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">&copy; <?php echo date('Y'); ?> ProCut Solutions. All rights reserved.</p>
                </div>
                <div style="flex: 1; min-width: 200px; display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap;">
                    <span style="color: #64748b; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem;">
                        <svg width="16" height="16" fill="#10b981" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        SSL Encrypted
                    </span>
                    <span style="color: #64748b; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem;">
                        <svg width="16" height="16" fill="#10b981" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        PCI Compliant
                    </span>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div style="display: flex; justify-content: center; gap: 1rem; font-size: 0.875rem;">
                        <span style="color: #64748b;">Need help?</span>
                        <a href="tel:+255753098911" style="color: #ff5900; text-decoration: none;">📞 Call</a>
                        <a href="https://wa.me/255753098911" style="color: #ff5900; text-decoration: none;" target="_blank">💬 WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="js/firebase-auth.js"></script>

    <script>
        // ==========================================
        // STEP NAVIGATION & PROGRESS BAR
        // ==========================================
        let currentStep = 2; // Start at step 2 (Customer Information)
        const firebaseConfigData = <?php echo json_encode($firebase_config ?? []); ?>;
        const firebaseEnabled = !!(firebaseConfigData && firebaseConfigData.enabled);
        let emailVerified = false;
        let phoneVerified = false;
        let phoneOtpSent = false;
        
        function goToStep(stepNumber) {
            // Validate current step before proceeding
            if (stepNumber > currentStep && !validateCurrentStep()) {
                return;
            }
            
            // Hide all steps
            document.querySelectorAll('.checkout-step').forEach(step => {
                step.style.display = 'none';
            });
            
            // Show the selected step
            const targetStep = document.getElementById(`step-${stepNumber}`);
            if (targetStep) {
                targetStep.style.display = 'block';
                currentStep = stepNumber;
                updateProgressBar(stepNumber);
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        
        function validateCurrentStep() {
            if (currentStep === 2) {
                // Validate Customer Information
                const name = document.getElementById('customerName').value.trim();
                const email = document.getElementById('customerEmail').value.trim();
                const phone = document.getElementById('customerPhone').value.trim();
                const country = document.getElementById('customerCountry').value.trim();
                
                if (!name || !email || !phone || !country) {
                    alert('Please fill in all required fields (Full Name, Email, Phone, Country)');
                    return false;
                }
                
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Please enter a valid email address');
                    return false;
                }

                if (firebaseEnabled) {
                    if (!emailVerified) {
                        alert('Please verify your email via Firebase before continuing.');
                        return false;
                    }
                    if (!phoneVerified) {
                        alert('Please verify your phone number via Firebase before continuing.');
                        return false;
                    }
                }
                
                return true;
            } else if (currentStep === 3) {
                // Validate Shipping Details
                const address = document.getElementById('shippingAddress').value.trim();
                const city = document.getElementById('shippingCity').value.trim();
                const shippingCountry = document.getElementById('shippingCountry').value.trim();
                
                if (!address || !city || !shippingCountry) {
                    alert('Please fill in all required shipping fields (Address, City, Country)');
                    return false;
                }
                
                return true;
            }
            
            return true;
        }
        
        function updateProgressBar(activeStep) {
            document.querySelectorAll('.checkout-progress .step').forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                
                step.classList.remove('active', 'completed');
                
                if (stepNum < activeStep) {
                    step.classList.add('completed');
                } else if (stepNum === activeStep) {
                    step.classList.add('active');
                }
            });
        }
        
        // Allow clicking on progress steps to navigate (only to completed steps)
        document.querySelectorAll('.checkout-progress .step').forEach(step => {
            step.addEventListener('click', function() {
                const targetStep = parseInt(this.dataset.step);
                
                // Only allow navigation to cart (step 1) or previously completed steps
                if (targetStep === 1) {
                    window.location.href = 'cart.php';
                } else if (targetStep <= currentStep) {
                    goToStep(targetStep);
                }
            });
        });
        
        // ==========================================
        // CHECKOUT DATA & INITIALIZATION
        // ==========================================
        const checkoutInitialData = <?php echo json_encode([
            'totals' => $totals,
            'items' => $items,
            'count' => $cart->getItemCount(),
        ]); ?> || { totals: {}, items: {}, count: 0 };

        const CHECKOUT_CSRF = '<?php echo $checkout_csrf; ?>';
        const CART_HASH = '<?php echo $checkout_cart_hash; ?>';

        const currencyMeta = {
            kes: { code: 'KES', locale: 'en-KE' },
            tzs: { code: 'TZS', locale: 'en-TZ' }
        };

        const summaryGroups = document.querySelectorAll('.summary-group');
        const currencySwitchButtons = document.querySelectorAll('#currencySwitch button');
        const regionTabs = document.querySelectorAll('.region-tab');
        const paymentPanels = document.querySelectorAll('.payment-panel');
        const payButtons = document.querySelectorAll('.pay-button');
        const checkoutMessage = document.getElementById('checkoutCartMessage');
        const checkoutItemList = document.getElementById('checkoutItemList');
        const emptyNotice = document.getElementById('checkoutEmptyNotice');
        const messageArea = document.getElementById('paymentMessage');
        const validationAlert = document.querySelector('.alert.alert-warning');
        const emailVerifyBtn = document.getElementById('emailVerifyBtn');
        const emailVerifyStatus = document.getElementById('emailVerifyStatus');
        const phoneOtpBtn = document.getElementById('phoneOtpBtn');
        const phoneOtpVerifyBtn = document.getElementById('phoneOtpVerifyBtn');
        const phoneOtpSection = document.getElementById('phoneOtpSection');
        const phoneOtpInput = document.getElementById('phoneOtpInput');
        const phoneVerifyStatus = document.getElementById('phoneVerifyStatus');

        const customerInputs = {
            name: document.getElementById('customerName'),
            email: document.getElementById('customerEmail'),
            phone: document.getElementById('customerPhone'),
            country: document.getElementById('customerCountry'),
            notes: document.getElementById('orderNotes')
        };

        let selectedCurrency = 'KES';
        let selectedCountryCode = '';
        
        // Cart totals from PHP
        const kesTotal = <?php echo $totals['kes']['total']; ?>;
        const tzsTotal = <?php echo $totals['tzs']['total']; ?>;

        if (validationAlert) {
            setTimeout(() => {
                validationAlert.style.display = 'none';
            }, 6000);
        }

        function formatCurrency(amount, currencyCode) {
            const normalizedCode = (currencyCode || '').toString().toLowerCase();
            const code = (currencyMeta[normalizedCode]?.code || currencyCode || '').toUpperCase();
            const numericAmount = Number(amount) || 0;
            const formattedAmount = numericAmount.toLocaleString('en-US', { maximumFractionDigits: 0 });
            return `${code} ${formattedAmount}`.trim();
        }

        function showCheckoutMessage(type, message) {
            if (!checkoutMessage) {
                return;
            }

            if (!message) {
                checkoutMessage.style.display = 'none';
                checkoutMessage.textContent = '';
                checkoutMessage.className = 'checkout-message';
                return;
            }

            checkoutMessage.textContent = message;
            checkoutMessage.className = `checkout-message ${type}`;
            checkoutMessage.style.display = 'block';
        }

        function toggleCheckoutEmptyState(isEmpty) {
            if (checkoutItemList) {
                checkoutItemList.style.display = isEmpty ? 'none' : '';
            }
            if (emptyNotice) {
                emptyNotice.style.display = isEmpty ? 'block' : 'none';
            }
            payButtons.forEach(button => {
                button.disabled = isEmpty;
            });
            if (isEmpty) {
                showCheckoutMessage('error', 'Your cart is empty. Add products to continue checkout.');
            } else {
                showCheckoutMessage('', '');
            }
        }

        function updateCheckoutSummary(totals) {
            if (!totals) {
                return;
            }

            Object.entries(totals).forEach(([currencyKey, currencyTotals]) => {
                if (!currencyTotals || typeof currencyTotals !== 'object') {
                    return;
                }

                const currencyCode = currencyMeta[currencyKey]?.code || currencyKey.toUpperCase();
                const subtotalEl = document.querySelector(`[data-summary="${currencyKey}-subtotal"]`);
                const taxEl = document.querySelector(`[data-summary="${currencyKey}-tax"]`);
                const totalEl = document.querySelector(`[data-summary="${currencyKey}-total"]`);

                if (subtotalEl) {
                    subtotalEl.textContent = formatCurrency(currencyTotals.subtotal, currencyCode);
                }
                if (taxEl) {
                    taxEl.textContent = formatCurrency(currencyTotals.tax, currencyCode);
                }
                if (totalEl) {
                    totalEl.textContent = formatCurrency(currencyTotals.total, currencyCode);
                }
            });
        }

        function updateCheckoutItem(productId, item) {
            const row = document.querySelector(`.item-row[data-product-id="${productId}"]`);
            if (!row || !item) {
                return;
            }

            const qtyInput = row.querySelector('.qty-input');
            if (qtyInput) {
                qtyInput.value = item.quantity;
                qtyInput.setAttribute('max', item.track_stock ? item.stock_quantity : 999);
            }

            const title = row.querySelector('h5');
            if (title) {
                title.textContent = item.name;
            }

            const sku = row.querySelector('.supporting-text');
            if (sku) {
                sku.textContent = `SKU: ${item.sku}`;
            }

            Object.keys(currencyMeta).forEach(currencyKey => {
                const totalEl = row.querySelector(`[data-currency-total="${currencyKey}"]`);
                const unitPrice = item[`price_${currencyKey}`];
                if (totalEl && typeof unitPrice !== 'undefined') {
                    totalEl.textContent = formatCurrency(unitPrice * item.quantity, currencyMeta[currencyKey].code);
                }
            });
        }

        function removeCheckoutItem(productId) {
            const row = document.querySelector(`.item-row[data-product-id="${productId}"]`);
            if (row) {
                row.remove();
            }

            const remainingItems = checkoutItemList ? checkoutItemList.querySelectorAll('.item-row').length : 0;
            toggleCheckoutEmptyState(remainingItems === 0);
        }

        function toggleItemLoading(productId, isLoading) {
            const row = document.querySelector(`.item-row[data-product-id="${productId}"]`);
            if (!row) {
                return;
            }

            row.classList.toggle('updating', isLoading);
            row.querySelectorAll('button, input').forEach(control => {
                control.disabled = isLoading;
            });
        }

        function handleCheckoutResponse(data, context = {}) {
            if (!data) {
                showCheckoutMessage('error', 'Unexpected response from server.');
                return;
            }

            if (data.success) {
                if (data.item) {
                    updateCheckoutItem(data.item.product_id || context.productId, data.item);
                }

                if (data.removed_product_id) {
                    removeCheckoutItem(data.removed_product_id);
                }

                if (data.totals) {
                    updateCheckoutSummary(data.totals);
                }

                const notice = data.message || data.notice || '';
                if (notice) {
                    showCheckoutMessage('success', notice);
                } else {
                    showCheckoutMessage('', '');
                }

                if (data.cart_count === 0) {
                    toggleCheckoutEmptyState(true);
                }

                setCurrency(selectedCurrency);
            } else {
                let errorMessage = data.message || '';
                if (!errorMessage && Array.isArray(data.errors) && data.errors.length) {
                    errorMessage = data.errors.join(' ');
                }

                if (context.previousQty && context.productId) {
                    const qtyInput = document.querySelector(`.qty-input[data-product-id="${context.productId}"]`);
                    if (qtyInput) {
                        qtyInput.value = context.previousQty;
                    }
                }

                showCheckoutMessage('error', errorMessage || 'Unable to update the cart.');
            }
        }

        function setCurrency(currencyCode) {
            const normalized = (currencyCode || '').toString().toLowerCase();
            const canonical = normalized === 'tzs' ? 'TZS' : 'KES';
            selectedCurrency = canonical;

            summaryGroups.forEach(group => {
                group.style.display = group.classList.contains('currency-' + canonical) ? 'block' : 'none';
            });

            document.querySelectorAll('[data-currency-total]').forEach(el => {
                el.style.display = el.dataset.currencyTotal === canonical.toLowerCase() ? 'block' : 'none';
            });

            currencySwitchButtons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.switch === canonical);
            });
        }

        currencySwitchButtons.forEach(btn => {
            btn.addEventListener('click', () => setCurrency(btn.dataset.switch));
        });

        function updateQuantity(productId, quantity) {
            const row = document.querySelector(`.item-row[data-product-id="${productId}"]`);
            if (!row) {
                return;
            }

            const qtyInput = row.querySelector('.qty-input');
            const previousQty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
            const minQty = 1;
            const maxQty = qtyInput && qtyInput.max ? parseInt(qtyInput.max, 10) : null;

            let normalizedQty = Math.max(minQty, Math.floor(quantity));
            if (maxQty) {
                normalizedQty = Math.min(normalizedQty, maxQty);
            }

            if (normalizedQty === previousQty) {
                if (qtyInput) {
                    qtyInput.value = previousQty;
                }
                return;
            }

            if (qtyInput) {
                qtyInput.value = normalizedQty;
            }

            toggleItemLoading(productId, true);
            showCheckoutMessage('', '');

            fetch('cart_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update',
                    product_id: productId,
                    quantity: normalizedQty
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                toggleItemLoading(productId, false);
                handleCheckoutResponse(data, { productId, previousQty });
            })
            .catch(() => {
                toggleItemLoading(productId, false);
                if (qtyInput) {
                    qtyInput.value = previousQty;
                }
                showCheckoutMessage('error', 'Could not update the cart. Please try again.');
            });
        }

        function removeItem(productId) {
            toggleItemLoading(productId, true);
            showCheckoutMessage('', '');

            fetch('cart_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'remove',
                    product_id: productId
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                toggleItemLoading(productId, false);
                handleCheckoutResponse(data, { productId, action: 'remove' });
            })
            .catch(() => {
                toggleItemLoading(productId, false);
                showCheckoutMessage('error', 'Could not remove the item. Please try again.');
            });
        }

        if (checkoutItemList) {
            checkoutItemList.addEventListener('click', event => {
                const target = event.target;
                const productId = target.dataset.productId;
                if (!productId) {
                    return;
                }

                if (target.classList.contains('qty-decrease')) {
                    const input = checkoutItemList.querySelector(`.qty-input[data-product-id="${productId}"]`);
                    const current = input ? parseInt(input.value, 10) || 1 : 1;
                    updateQuantity(productId, current - 1);
                } else if (target.classList.contains('qty-increase')) {
                    const input = checkoutItemList.querySelector(`.qty-input[data-product-id="${productId}"]`);
                    const current = input ? parseInt(input.value, 10) || 1 : 1;
                    updateQuantity(productId, current + 1);
                } else if (target.classList.contains('item-remove')) {
                    if (confirm('Remove this item from the order?')) {
                        removeItem(productId);
                    }
                }
            });

            checkoutItemList.addEventListener('change', event => {
                const target = event.target;
                if (target.classList.contains('qty-input')) {
                    const productId = target.dataset.productId;
                    const requestedQty = parseInt(target.value, 10);
                    updateQuantity(productId, isNaN(requestedQty) ? 1 : requestedQty);
                }
            });
        }

        toggleCheckoutEmptyState(Object.keys(checkoutInitialData.items || {}).length === 0);
        updateCheckoutSummary(checkoutInitialData.totals);
        setCurrency(selectedCurrency);

        function setRegion(region) {
            regionTabs.forEach(tab => tab.classList.toggle('active', tab.dataset.region === region));
            paymentPanels.forEach(panel => {
                const isActive = panel.dataset.region === region;
                panel.classList.toggle('active', isActive);
                if (isActive) {
                    const defaultCurrency = panel.querySelector('[data-default-currency]')?.dataset.defaultCurrency;
                    if (defaultCurrency) {
                        setCurrency(defaultCurrency);
                    }
                }
            });
        }

        regionTabs.forEach(tab => {
            tab.addEventListener('click', () => setRegion(tab.dataset.region));
        });

        function showPaymentMessage(type, text) {
            if (!messageArea) {
                return;
            }
            messageArea.textContent = text;
            messageArea.classList.remove('success', 'error');
            if (type) {
                messageArea.classList.add(type);
            }
        }

        function getInputValue(field) {
            return customerInputs[field] ? customerInputs[field].value.trim() : '';
        }

        function setVerifyStatus(el, state, text) {
            if (!el) return;
            el.classList.remove('success', 'error', 'pending');
            if (state) {
                el.classList.add(state);
            }
            el.textContent = text;
        }

        function ensureFirebaseReady() {
            if (!firebaseEnabled) {
                setVerifyStatus(emailVerifyStatus, 'error', 'Firebase disabled');
                setVerifyStatus(phoneVerifyStatus, 'error', 'Firebase disabled');
                return false;
            }
            return true;
        }

        if (firebaseEnabled) {
            initializeFirebase(firebaseConfigData);
            setVerifyStatus(emailVerifyStatus, 'pending', 'Awaiting verification');
            setVerifyStatus(phoneVerifyStatus, 'pending', 'Awaiting verification');
        } else {
            setVerifyStatus(emailVerifyStatus, 'error', 'Firebase disabled');
            setVerifyStatus(phoneVerifyStatus, 'error', 'Firebase disabled');
        }

        async function handleEmailVerification() {
            if (!ensureFirebaseReady()) return;
            const email = getInputValue('email');
            if (!email) {
                setVerifyStatus(emailVerifyStatus, 'error', 'Enter email first');
                return;
            }
            setVerifyStatus(emailVerifyStatus, 'pending', 'Sending verification link...');
            const result = await sendEmailVerification(email);
            if (result?.success) {
                emailVerified = true;
                setVerifyStatus(emailVerifyStatus, 'success', 'Verification link sent');
                alert('Verification link sent to your email. Complete it to finalize verification.');
            } else {
                setVerifyStatus(emailVerifyStatus, 'error', result?.message || 'Email verification failed');
            }
        }

        async function handleSendPhoneOtp() {
            if (!ensureFirebaseReady()) return;
            const phone = getInputValue('phone');
            if (!phone) {
                setVerifyStatus(phoneVerifyStatus, 'error', 'Enter phone first');
                return;
            }
            setVerifyStatus(phoneVerifyStatus, 'pending', 'Sending OTP...');
            const recaptchaContainer = document.getElementById('recaptcha-container');
            if (recaptchaContainer) {
                recaptchaContainer.style.display = 'block';
            }
            const result = await sendPhoneOTP(phone);
            if (result?.success) {
                phoneOtpSent = true;
                if (phoneOtpSection) {
                    phoneOtpSection.style.display = 'block';
                }
                setVerifyStatus(phoneVerifyStatus, 'pending', 'OTP sent. Check your phone.');
            } else {
                setVerifyStatus(phoneVerifyStatus, 'error', result?.message || 'OTP send failed');
            }
        }

        async function handleVerifyPhoneOtp() {
            if (!ensureFirebaseReady()) return;
            if (!phoneOtpSent) {
                setVerifyStatus(phoneVerifyStatus, 'error', 'Send OTP first');
                return;
            }
            const otp = phoneOtpInput ? phoneOtpInput.value.trim() : '';
            if (!otp) {
                setVerifyStatus(phoneVerifyStatus, 'error', 'Enter the OTP');
                return;
            }
            setVerifyStatus(phoneVerifyStatus, 'pending', 'Verifying OTP...');
            const result = await verifyPhoneOTP(otp);
            if (result?.success) {
                phoneVerified = true;
                if (phoneOtpSection) {
                    phoneOtpSection.style.display = 'none';
                }
                setVerifyStatus(phoneVerifyStatus, 'success', 'Phone verified');
            } else {
                setVerifyStatus(phoneVerifyStatus, 'error', result?.message || 'OTP verification failed');
            }
        }

        if (emailVerifyBtn) {
            emailVerifyBtn.addEventListener('click', handleEmailVerification);
        }

        if (phoneOtpBtn) {
            phoneOtpBtn.addEventListener('click', handleSendPhoneOtp);
        }

        if (phoneOtpVerifyBtn) {
            phoneOtpVerifyBtn.addEventListener('click', handleVerifyPhoneOtp);
        }

        // Removed external IP geolocation call for privacy/latency; country selection is user-driven
        if (customerInputs.country) {
            customerInputs.country.addEventListener('change', () => {
                const value = customerInputs.country.value.toLowerCase();
                if (value.includes('kenya') || value.includes('ke')) {
                    selectedCountryCode = 'KE';
                    setRegion('kenya');
                } else if (value.includes('tanzania') || value.includes('tz')) {
                    selectedCountryCode = 'TZ';
                    setRegion('tanzania');
                } else {
                    selectedCountryCode = '';
                    setRegion('global');
                }
            });
        }

        async function initiatePayment(button) {
            if (button.hasAttribute('disabled')) {
                return;
            }

            const gateway = button.dataset.gateway;
            
            // Handle inline MNO payment - show modal instead of API call
            if (gateway === 'flutterwave-mno') {
                openMnoModal(button);
                return;
            }
            
            // Use selected currency from cart, validate against accepted currencies
            const acceptedCurrencies = button.dataset.acceptCurrencies ? button.dataset.acceptCurrencies.split(',') : [];
            const currentCurrency = selectedCurrency || '<?php echo $checkoutCurrency; ?>';
            
            if (acceptedCurrencies.length > 0 && !acceptedCurrencies.includes(currentCurrency)) {
                showPaymentMessage('error', `This payment method does not support ${currentCurrency}. Accepted currencies: ${acceptedCurrencies.join(', ')}`);
                return;
            }

            const payload = {
                gateway,
                currency: currentCurrency,
                country_code: selectedCountryCode,
                customer_name: getInputValue('name'),
                customer_email: getInputValue('email'),
                customer_phone: getInputValue('phone'),
                notes: getInputValue('notes'),
                csrf_token: CHECKOUT_CSRF,
                cart_hash: CART_HASH
            };

            if (button.dataset.paymentOptions) {
                payload.payment_options = button.dataset.paymentOptions;
            }

            if (!payload.customer_name || !payload.customer_email || !payload.customer_phone) {
                showPaymentMessage('error', 'Enter your name, email, and phone number before paying.');
                return;
            }

            button.setAttribute('disabled', 'disabled');
            const originalText = button.textContent;
            button.textContent = 'Processing...';
            showPaymentMessage('', '');

            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                // Check if response is actually JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned an error. Please check the console for details.');
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Payment failed to start.');
                }
                const result = data.data || {};
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else if (result.customer_message) {
                    showPaymentMessage('success', result.customer_message);
                } else {
                    showPaymentMessage('success', 'Payment initiated. Follow the prompts to complete.');
                }
            } catch (error) {
                console.error('Payment error', error);
                showPaymentMessage('error', error.message);
            } finally {
                button.removeAttribute('disabled');
                button.textContent = originalText;
            }
        }

        // ==========================================
        // MOBILE MONEY INLINE PAYMENT MODAL
        // ==========================================
        let currentMnoButton = null;
        
        function openMnoModal(button) {
            const paymentMethod = button.dataset.paymentMethod;
            const currency = button.dataset.currency;
            const total = selectedCurrency === 'KES' ? kesTotal : tzsTotal;
            
            // Validate customer info first
            const customerName = getInputValue('name');
            const customerEmail = getInputValue('email');
            const customerPhone = getInputValue('phone');
            
            if (!customerName || !customerEmail || !customerPhone) {
                showPaymentMessage('error', 'Enter your name, email, and phone number before paying.');
                return;
            }
            
            currentMnoButton = button;
            
            // Update modal title and amount
            if (paymentMethod === 'mpesa') {
                document.getElementById('mnoModalTitle').textContent = 'M-Pesa Payment (Kenya)';
                document.getElementById('mnoNetworkSelect').style.display = 'none';
            } else if (paymentMethod === 'mobilemoneytz') {
                document.getElementById('mnoModalTitle').textContent = 'Mobile Money Payment (Tanzania)';
                document.getElementById('mnoNetworkSelect').style.display = 'block';
            }
            
            document.getElementById('mnoAmount').textContent = currency + ' ' + total.toLocaleString();
            
            // Show modal
            document.getElementById('mnoModal').style.display = 'block';
            document.getElementById('mnoPaymentForm').style.display = 'block';
            document.getElementById('mnoPaymentStatus').style.display = 'none';
            
            // Pre-fill phone if available
            const phoneValue = getInputValue('phone');
            if (phoneValue) {
                document.getElementById('mnoPhoneNumber').value = phoneValue;
            }
        }
        
        function closeMnoModal() {
            document.getElementById('mnoModal').style.display = 'none';
            document.getElementById('mnoPhoneNumber').value = '';
            document.getElementById('mnoNetwork').value = '';
            currentMnoButton = null;
        }
        
        async function processMnoPayment() {
            const phoneNumber = document.getElementById('mnoPhoneNumber').value.trim();
            const network = document.getElementById('mnoNetwork').value;
            const paymentMethod = currentMnoButton.dataset.paymentMethod;
            const currency = currentMnoButton.dataset.currency;
            
            // Validation
            if (!phoneNumber) {
                alert('Please enter your phone number');
                return;
            }
            
            if (paymentMethod === 'mobilemoneytz' && !network) {
                alert('Please select your mobile money network');
                return;
            }
            
            // Show processing status
            document.getElementById('mnoPaymentForm').style.display = 'none';
            document.getElementById('mnoPaymentStatus').style.display = 'block';
            document.getElementById('mnoStatusIcon').className = 'fas fa-spinner fa-spin';
            document.getElementById('mnoStatusTitle').textContent = 'Processing Payment...';
            document.getElementById('mnoStatusMessage').textContent = 'Please check your phone for a payment prompt';
            
            // Prepare payload
            const payload = {
                gateway: 'flutterwave-inline',
                payment_method: paymentMethod,
                network: network || 'mpesa',
                phone_number: phoneNumber,
                currency: currency,
                country_code: selectedCountryCode,
                customer_name: getInputValue('name'),
                customer_email: getInputValue('email'),
                customer_phone: getInputValue('phone'),
                notes: getInputValue('notes'),
                csrf_token: CHECKOUT_CSRF,
                cart_hash: CART_HASH
            };
            
            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned an error. Please check the console for details.');
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Payment failed to start.');
                }
                
                // Success - show success status
                document.getElementById('mnoStatusIcon').className = 'fas fa-check-circle';
                document.getElementById('mnoStatusIcon').style.color = '#10b981';
                document.getElementById('mnoStatusTitle').textContent = 'Payment Initiated!';
                document.getElementById('mnoStatusMessage').textContent = data.data.customer_message || 'Check your phone and complete the payment';
                
                // Auto-close modal after 5 seconds
                setTimeout(() => {
                    closeMnoModal();
                    showPaymentMessage('success', 'Payment initiated. Complete it on your phone.');
                }, 5000);
                
            } catch (error) {
                console.error('MNO Payment error', error);
                
                // Show error status
                document.getElementById('mnoStatusIcon').className = 'fas fa-times-circle';
                document.getElementById('mnoStatusIcon').style.color = '#ef4444';
                document.getElementById('mnoStatusTitle').textContent = 'Payment Failed';
                document.getElementById('mnoStatusMessage').textContent = error.message;
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('mnoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMnoModal();
            }
        });

        payButtons.forEach(button => {
            button.addEventListener('click', () => initiatePayment(button));
        });

        detectLocation();
    </script>
</body>
</html>
