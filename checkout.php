<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';

$cart = new Cart();
$items = $cart->getItems();
$totals = $cart->getTotals();
$validation = $cart->validateCart();

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
        input, textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 1rem;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        input:focus, textarea:focus {
            border-color: #2563eb;
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
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
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
            border: 2px solid #cbd5f5;
            background: #fff;
            color: #1d4ed8;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .region-tab.active {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
            box-shadow: 0 10px 25px rgba(29,78,216,0.25);
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
            background: #2563eb;
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
            box-shadow: 0 12px 30px rgba(37,99,235,0.35);
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
            background: #1d4ed8;
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
            color: #1d4ed8;
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
            background: #eff6ff;
            color: #1d4ed8;
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
    <?php include 'includes/header.php'; ?>

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
                            <h2>Customer Information</h2>
                            <div class="form-grid">
                                <div>
                                    <label for="customerName">Full Name *</label>
                                    <input type="text" id="customerName" placeholder="Jane Doe" required>
                                </div>
                                <div>
                                    <label for="customerEmail">Email Address *</label>
                                    <input type="email" id="customerEmail" placeholder="name@example.com" required>
                                </div>
                                <div>
                                    <label for="customerPhone">Phone Number *</label>
                                    <input type="tel" id="customerPhone" placeholder="+2547..." required>
                                </div>
                                <div>
                                    <label for="customerCountry">Country *</label>
                                    <input type="text" id="customerCountry" placeholder="Kenya" autocomplete="country-name" required>
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
                                        <input type="text" id="shippingAddress" placeholder="123 Main Street, Apt 4B" required>
                                    </div>
                                </div>
                                <div>
                                    <label for="shippingCity">City / Town *</label>
                                    <input type="text" id="shippingCity" placeholder="Nairobi" required>
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
                                    <input type="text" id="shippingCountry" placeholder="Kenya" required>
                                </div>
                            </div>
                            
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
                            <h2>Choose Payment Method</h2>
                            
                            <h3>Choose Payment Region</h3>
                            <div class="region-tabs" id="regionTabs">
                                <button type="button" class="region-tab active" data-region="kenya">Kenya</button>
                                <button type="button" class="region-tab" data-region="tanzania">Tanzania</button>
                                <button type="button" class="region-tab" data-region="global">Global / Other</button>
                            </div>

                            <div class="payment-panels">
                            <div class="payment-panel active" data-region="kenya">
                                <?php if ($mpesa_enabled): ?>
                                <div class="payment-option" data-default-currency="KES">
                                    <h4>Lipa na M-Pesa</h4>
                                    <p>Instant STK push to your Kenyan M-Pesa wallet. Confirm the prompt to complete payment.</p>
                                    <span class="supporting-text">Payment currency: Kenyan Shilling (KES)</span>
                                    <button class="pay-button" data-gateway="mpesa" data-currency="KES">
                                        Pay with Lipa na M-Pesa
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>Mobile Money (M-Pesa Kenya)</h4>
                                    <p>Quick M-Pesa payment - enter your phone number and confirm the payment prompt.</p>
                                    <span class="supporting-text">Inline payment - stays on this page</span>
                                    <button class="pay-button" data-gateway="flutterwave-mno" data-payment-method="mpesa" data-currency="KES">
                                        <i class="fas fa-mobile-alt"></i> Pay with M-Pesa
                                    </button>
                                </div>
                                <div class="payment-option">
                                    <h4>Cards & International Payments</h4>
                                    <p>Secure Flutterwave checkout for cards, bank transfers, USSD, and more payment methods.</p>
                                    <span class="supporting-text">Redirects to secure payment page</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer,ussd" data-currency="KES">
                                        <i class="fas fa-credit-card"></i> Pay with Cards/Bank
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="payment-panel" data-region="tanzania">
                                <?php if ($azampay_enabled): ?>
                                <div class="payment-option" data-default-currency="TZS">
                                    <h4>AzamPay</h4>
                                    <p>Recommended for Tanzania payments. Supports Tigo Pesa, M-Pesa, Airtel Money, and local cards.</p>
                                    <span class="supporting-text">Payment currency: Tanzanian Shilling (TZS)</span>
                                    <button class="pay-button" data-gateway="azampay" data-currency="TZS">
                                        Pay with AzamPay
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>Mobile Money (Tanzania)</h4>
                                    <p>Quick mobile money payment - supports M-Pesa, Tigo Pesa, Airtel Money, and Halopesa.</p>
                                    <span class="supporting-text">Inline payment - stays on this page</span>
                                    <button class="pay-button" data-gateway="flutterwave-mno" data-payment-method="mobilemoneytz" data-currency="TZS">
                                        <i class="fas fa-mobile-alt"></i> Pay with Mobile Money
                                    </button>
                                </div>
                                <div class="payment-option">
                                    <h4>Cards & Bank Transfer</h4>
                                    <p>Secure Flutterwave checkout for local and international cards plus bank transfers.</p>
                                    <span class="supporting-text">Redirects to secure payment page</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer" data-currency="TZS">
                                        <i class="fas fa-credit-card"></i> Pay with Cards/Bank
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="payment-panel" data-region="global">
                                <?php if ($pesapal_enabled): ?>
                                <div class="payment-option" data-default-currency="KES">
                                    <h4>Pesapal</h4>
                                    <p>Trusted East African gateway for cards, mobile wallets, and bank payments across multiple countries.</p>
                                    <span class="supporting-text">Supports KES, USD, GBP, and more</span>
                                    <button class="pay-button" data-gateway="pesapal">
                                        Pay with Pesapal
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($flutterwave_enabled): ?>
                                <div class="payment-option">
                                    <h4>International Card Payments</h4>
                                    <p>Accept global credit/debit cards, Apple Pay, Google Pay, and bank transfers in multiple currencies.</p>
                                    <span class="supporting-text">Secure hosted payment page with fraud protection</span>
                                    <button class="pay-button secondary" data-gateway="flutterwave" data-payment-options="card,banktransfer,applepay,googlepay" data-currency="USD">
                                        <i class="fas fa-globe"></i> Pay with International Cards
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($paypal_enabled): ?>
                                <div class="payment-option">
                                    <h4>PayPal</h4>
                                    <p>Fast checkout for international shoppers using PayPal balance or linked cards.</p>
                                    <span class="supporting-text">Redirects to PayPal to authorize payment securely</span>
                                    <button class="pay-button" data-gateway="paypal">
                                        Pay with PayPal
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($stripe_enabled): ?>
                                <div class="payment-option">
                                    <h4>Stripe Checkout</h4>
                                    <p>Global card processing with 3D Secure support and localized payment pages.</p>
                                    <span class="supporting-text">Supports major debit and credit cards</span>
                                    <button class="pay-button secondary" data-gateway="stripe">
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
                        <div class="currency-switch" id="currencySwitch">
                            <button type="button" class="active" data-switch="KES">KES</button>
                            <button type="button" data-switch="TZS">TZS</button>
                        </div>

                        <div id="checkoutCartMessage" class="checkout-message" style="display:none;"></div>

                        <div id="summaryKES" class="summary-group currency-block currency-KES">
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span class="summary-value" data-summary="kes-subtotal">KES <?php echo number_format($totals['kes']['subtotal'], 0); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>VAT (16%)</span>
                                <span class="summary-value" data-summary="kes-tax">KES <?php echo number_format($totals['kes']['tax'], 0); ?></span>
                            </div>
                            <div class="total-line">
                                <span>Total</span>
                                <span class="summary-value" data-summary="kes-total">KES <?php echo number_format($totals['kes']['total'], 0); ?></span>
                            </div>
                        </div>

                        <div id="summaryTZS" class="summary-group currency-block currency-TZS" style="display:none;">
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span class="summary-value" data-summary="tzs-subtotal">TZS <?php echo number_format($totals['tzs']['subtotal'], 0); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>VAT (18%)</span>
                                <span class="summary-value" data-summary="tzs-tax">TZS <?php echo number_format($totals['tzs']['tax'], 0); ?></span>
                            </div>
                            <div class="total-line">
                                <span>Total</span>
                                <span class="summary-value" data-summary="tzs-total">TZS <?php echo number_format($totals['tzs']['total'], 0); ?></span>
                            </div>
                        </div>

                        <div id="checkoutItemList" class="item-list">
                            <?php foreach ($items as $productId => $item): ?>
                                <div class="item-row" data-product-id="<?php echo $productId; ?>">
                                    <div class="item-primary">
                                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <span class="supporting-text">SKU: <?php echo htmlspecialchars($item['sku']); ?></span>
                                        <div class="checkout-item-controls">
                                            <div class="item-quantity">
                                                <button class="qty-btn qty-decrease" data-product-id="<?php echo $productId; ?>">-</button>
                                                <input type="number" class="qty-input" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo $item['track_stock'] ? (int)$item['stock_quantity'] : 999; ?>" data-product-id="<?php echo $productId; ?>">
                                                <button class="qty-btn qty-increase" data-product-id="<?php echo $productId; ?>">+</button>
                                            </div>
                                            <button class="item-remove" data-product-id="<?php echo $productId; ?>">Remove</button>
                                        </div>
                                    </div>
                                    <div class="item-amounts">
                                        <div class="item-price" data-currency-total="kes">KES <?php echo number_format($item['price_kes'] * $item['quantity'], 0); ?></div>
                                        <div class="item-price" data-currency-total="tzs" style="display:none;">TZS <?php echo number_format($item['price_tzs'] * $item['quantity'], 0); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="checkoutEmptyNotice" class="empty-cart-alert" style="display:none;">
                            <p>Your cart is empty. <a href="products.php">Add products</a> to continue checkout.</p>
                        </div>

                        <a href="cart.php" style="display:inline-block;margin-top:2rem;font-weight:600;color:#2563eb;">← Modify cart</a>
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
            border-color: #2563eb;
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
            color: #2563eb;
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
            background: #2563eb;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
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
            color: #2563eb;
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

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>ProCut Solutions</h3>
                    <p>Professional printing equipment supplier serving Kenya and Tanzania.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="cart.php">Cart</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li>Tanzania: <a href="tel:+255753098911">+255 753 098 911</a></li>
                        <li>Kenya: <a href="tel:+254716522828">+254 716 522 828</a></li>
                        <li><a href="mailto:support@procutsolutions.com">support@procutsolutions.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ProCut Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // ==========================================
        // STEP NAVIGATION & PROGRESS BAR
        // ==========================================
        let currentStep = 2; // Start at step 2 (Customer Information)
        
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

        async function detectLocation() {
            if (!customerInputs.country) {
                return;
            }
            try {
                const response = await fetch('https://ipapi.co/json/');
                if (!response.ok) {
                    throw new Error('Failed to detect location');
                }
                const data = await response.json();
                if (data.country) {
                    customerInputs.country.value = data.country_name || data.country;
                    selectedCountryCode = data.country_code || '';
                    if (selectedCountryCode === 'KE') {
                        setRegion('kenya');
                        if (customerInputs.phone) {
                            customerInputs.phone.placeholder = '+2547...';
                        }
                    } else if (selectedCountryCode === 'TZ') {
                        setRegion('tanzania');
                        if (customerInputs.phone) {
                            customerInputs.phone.placeholder = '+2557...';
                        }
                    } else {
                        setRegion('global');
                    }
                }
            } catch (error) {
                console.warn('Geolocation lookup failed', error);
            }
        }

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
            
            const forcedCurrency = button.dataset.currency;
            if (forcedCurrency && forcedCurrency !== selectedCurrency) {
                setCurrency(forcedCurrency);
            }

            const payload = {
                gateway,
                currency: forcedCurrency || selectedCurrency,
                country_code: selectedCountryCode,
                customer_name: getInputValue('name'),
                customer_email: getInputValue('email'),
                customer_phone: getInputValue('phone'),
                notes: getInputValue('notes')
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
                notes: getInputValue('notes')
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
