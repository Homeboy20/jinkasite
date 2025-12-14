<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/config.php';
require_once 'includes/Cart.php';
require_once 'includes/CurrencyDetector.php';

$cart = new Cart();
$items = $cart->getItems();
$totals = $cart->getTotals();
$validation = $cart->validateCart();
$itemCount = $cart->getItemCount();

// Get current currency for display
$currencyDetector = CurrencyDetector::getInstance();
$currentCurrency = $totals['currency'] ?? 'KES';
$currencySymbol = $totals['symbol'] ?? 'KSh';

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
$business_name = $site_name;
$contact_phone = site_setting('contact_phone', '+255753098911');
$contact_phone_ke = site_setting('contact_phone_ke', $contact_phone);
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');
$phone_number = $contact_phone;
$phone_number_ke = $contact_phone_ke;
$email = site_setting('contact_email', 'support@procutsolutions.com');

$facebook_url = trim(site_setting('facebook_url', ''));
$instagram_url = trim(site_setting('instagram_url', ''));
$twitter_url = trim(site_setting('twitter_url', ''));
$linkedin_url = trim(site_setting('linkedin_url', ''));

// Footer Configuration
$footer_logo = site_setting('footer_logo', $site_logo);
$footer_about = site_setting('footer_about', 'Professional printing equipment supplier serving Kenya and Tanzania. Quality products, expert support, and competitive pricing for all your printing needs.');
$footer_address = site_setting('footer_address', 'Kenya & Tanzania');
$footer_phone_label_tz = site_setting('footer_phone_label_tz', 'Tanzania');
$footer_phone_label_ke = site_setting('footer_phone_label_ke', 'Kenya');
$footer_hours_weekday = site_setting('footer_hours_weekday', '8:00 AM - 6:00 PM');
$footer_hours_saturday = site_setting('footer_hours_saturday', '9:00 AM - 4:00 PM');
$footer_hours_sunday = site_setting('footer_hours_sunday', 'Closed');
$footer_whatsapp_label = site_setting('footer_whatsapp_label', '24/7 Available');
$footer_copyright = site_setting('footer_copyright', '');

$contact_phone_link = preg_replace('/[^0-9+]/', '', $contact_phone);
if ($contact_phone_link !== '' && $contact_phone_link[0] !== '+') {
    $contact_phone_link = '+' . ltrim($contact_phone_link, '+');
}

$contact_phone_ke_link = preg_replace('/[^0-9+]/', '', $contact_phone_ke);
if ($contact_phone_ke_link !== '' && $contact_phone_ke_link[0] !== '+') {
    $contact_phone_ke_link = '+' . ltrim($contact_phone_ke_link, '+');
}

$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);

$page_title = "Shopping Cart | " . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?php echo $page_title; ?></title>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header-modern.css">
    <link rel="stylesheet" href="css/theme-variables.php">
    <link rel="stylesheet" href="css/cart-responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #eef2ff;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .cart-page {
            padding: 4rem 0 6rem;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            transition: padding 0.3s ease;
        }

        .cart-shell {
            width: min(1100px, 100%);
            margin: 0 auto;
            padding: 0 1.5rem;
            transition: padding 0.3s ease;
        }

        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        }

        .cart-header::after {
            display: none;
        }

        .cart-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.75rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .cart-header h1 {
            margin: 0 0 0.5rem;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .cart-header p {
            margin: 0;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .cart-header-subtitle {
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .cart-header-count {
            font-weight: 600;
            opacity: 1;
        }

        /* Cart-specific styles */
        .cart-alerts {
            position: relative;
            z-index: 1;
            margin-bottom: 1.5rem;
        }

        .validation-alert {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            background: linear-gradient(135deg, #fef9c3, #fef08a);
            border: 1px solid #facc15;
            color: #854d0e;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 18px 32px -26px rgba(203, 161, 53, 0.7);
        }

        .validation-alert strong {
            font-weight: 700;
        }

        .validation-alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .cart-message {
            display: none;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            box-shadow: 0 24px 45px -32px rgba(15, 23, 42, 0.45);
            border: 1px solid transparent;
        }

        .cart-message.success {
            background: linear-gradient(135deg, #ecfdf5, #bbf7d0);
            color: #047857;
            border-color: rgba(16, 185, 129, 0.45);
        }

        .cart-message.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #b91c1c;
            border-color: rgba(248, 113, 113, 0.45);
        }

        .cart-message.info {
            background: linear-gradient(135deg, #ffedd5, #fed7aa);
            color: #e64f00;
            border-color: rgba(255, 89, 0, 0.45);
        }

        #cartLayout.cart-content {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 2.5rem;
            align-items: start;
            width: 100%;
            max-width: none;
            height: auto;
            min-height: 0;
        }

        .cart-items {
            background: white;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 40px 70px -45px rgba(15, 23, 42, 0.2);
            overflow: hidden;
            min-width: 0;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px minmax(0, 1fr) auto;
            gap: 1.5rem;
            padding: 1.75rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 26px 50px -40px rgba(15, 23, 42, 0.35);
        }

        .cart-item.updating {
            opacity: 0.45;
            pointer-events: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(59, 130, 246, 0.12));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            display: grid;
            gap: 0.75rem;
            align-content: start;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .item-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .item-name a {
            color: inherit;
            text-decoration: none;
        }

        .item-name a:hover {
            color: #ff5900;
        }

        .item-sku {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
        }

        .item-stock {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.12);
            color: #15803d;
            white-space: nowrap;
            align-self: flex-start;
        }

        .item-stock.backorder {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .item-price {
            display: flex;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .item-price span {
            display: inline-flex;
            align-items: center;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .quantity-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
        }

        .qty-controls {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #cbd5f5;
            background: #f8fafc;
            overflow: hidden;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            color: #475569;
            font-size: 1.35rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            background: rgba(255, 89, 0, 0.1);
            color: #ff5900;
        }

        .qty-input {
            width: 64px;
            height: 40px;
            border: none;
            background: transparent;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1.25rem;
        }

        .item-total {
            text-align: right;
        }

        .item-total-label {
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .item-total-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
        }

        .remove-btn {
            padding: 0.6rem 1.1rem;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid transparent;
            color: #b91c1c;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remove-btn:hover {
            background: #ef4444;
            color: white;
            box-shadow: 0 18px 30px -22px rgba(239, 68, 68, 0.65);
        }

        .cart-summary {
            background: white;
            border-radius: 22px;
            padding: 2rem;
            box-shadow: 0 40px 70px -45px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(99, 102, 241, 0.12);
            position: sticky;
            top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-self: start;
        }

        .summary-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .currency-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .currency-tab {
            padding: 0.85rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            color: #475569;
            transition: all 0.2s ease;
        }

        .currency-tab:hover {
            border-color: #cbd5f5;
        }

        .currency-tab.active {
            background: linear-gradient(135deg, #ff5900, #e64f00);
            border-color: transparent;
            color: white;
            box-shadow: 0 15px 30px -20px rgba(255, 89, 0, 0.45);
        }

        .summary-totals {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            display: grid;
            gap: 0.85rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            color: #475569;
        }

        .summary-row.subtotal {
            font-weight: 600;
        }

        .summary-row.total {
            padding-top: 0.85rem;
            margin-top: 0.75rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.35);
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
        }

        .summary-note {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
        }

        .support-card {
            border-radius: 16px;
            padding: 1.15rem 1.2rem;
            background: linear-gradient(135deg, rgba(255, 89, 0, 0.1), rgba(230, 79, 0, 0.1));
            border: 1px solid rgba(255, 89, 0, 0.2);
            font-size: 0.9rem;
            color: #e64f00;
        }

        .support-card a {
            color: inherit;
            text-decoration: underline;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #ff5900, #e64f00);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 45px -25px rgba(255, 89, 0, 0.55);
        }

        .checkout-btn:disabled {
            background: #cbd5f5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .continue-shopping {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .continue-shopping:hover {
            color: #ff5900;
        }

        .empty-cart {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 40px 70px -50px rgba(15, 23, 42, 0.45);
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .empty-cart svg {
            width: 120px;
            height: 120px;
            color: rgba(99, 102, 241, 0.35);
            margin-bottom: 1.5rem;
        }

        .empty-cart h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .empty-cart p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .empty-cart-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .empty-cart .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .empty-cart .btn-primary {
            background: linear-gradient(135deg, #ff5900, #e64f00);
            color: white;
            box-shadow: 0 18px 40px -30px rgba(255, 89, 0, 0.55);
        }

        .empty-cart .btn-primary:hover {
            transform: translateY(-1px);
        }

        .empty-cart .btn-outline {
            background: white;
            border: 1px solid #fed7aa;
            color: #e64f00;
        }

        /* Footer - Modern Design */
        .footer {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 5rem 0 0;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff5900, #8b5cf6, #ec4899);
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1.2fr 1.5fr;
            gap: 3rem;
            margin-bottom: 3rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 2rem;
            align-items: start;
        }

        .footer-col {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .footer-col-brand {
            padding-right: 1rem;
        }

        .footer-logo {
            margin-bottom: 1.25rem;
        }

        .footer-logo-img {
            max-width: 200px;
            height: auto;
            max-height: 70px;
            object-fit: contain;
            display: block;
        }

        .footer-col h3 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 1.25rem 0;
            color: white;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: capitalize;
            line-height: 1.2;
        }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 1.75rem 0;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #ff5900, #8b5cf6);
            border-radius: 2px;
        }

        .footer-col p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.8;
            margin: 0 0 1.75rem 0;
        }

        .footer-col ul {
            list-style: none;
            font-size: 0.95rem;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }

        .footer-col ul li {
            margin: 0;
            color: rgba(255, 255, 255, 0.75);
            position: relative;
            padding-left: 0;
            transition: all 0.3s ease;
        }

        .footer-col ul li:hover {
            transform: translateX(5px);
        }

        .footer-col a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-col a:hover {
            color: #60a5fa;
            text-decoration: none;
        }

        .footer-col ul li a::before {
            content: '→';
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
            font-weight: 700;
        }

        .footer-col ul li a:hover::before {
            opacity: 1;
            transform: translateX(0);
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-social a {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-social a::before {
            display: none !important;
        }

        .footer-social a:hover {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
            border-color: transparent;
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            padding: 2rem 0;
            margin-top: 3rem;
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .footer-bottom p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
            margin: 0;
        }

        .footer-bottom-links {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-bottom-links a {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a::before {
            display: none !important;
        }

        .footer-bottom-links a:hover {
            color: #60a5fa;
        }

        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .footer-contact-item:first-child {
            margin-top: 0;
        }

        .footer-contact-item:last-child {
            margin-bottom: 0;
        }

        .footer-contact-icon {
            width: 40px;
            height: 40px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            flex-shrink: 0;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .footer-contact-item a {
            color: rgba(255, 255, 255, 0.85);
        }

        .footer-contact-item a:hover {
            color: #60a5fa;
        }

        .footer-contact-item a::before {
            display: none !important;
        }

        .footer-contact-item div {
            flex: 1;
            line-height: 1.6;
        }

        .footer-hours-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            gap: 1.5rem;
        }

        .footer-hours-item:first-child {
            padding-top: 0;
        }

        .footer-hours-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .footer-hours-item span {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .footer-hours-item strong {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9rem;
            font-weight: 600;
            text-align: right;
        }

        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
                gap: 3rem 2.5rem;
                padding: 0 1.5rem;
            }

            .footer-col-brand {
                padding-right: 0;
            }

            #cartLayout.cart-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .cart-shell {
                padding: 0 1.25rem;
            }

            .cart-summary {
                position: static;
                align-self: auto;
                padding: 2rem;
            }

            .cart-header {
                padding: 2rem;
            }

            .checkout-progress {
                padding: 1.5rem;
                gap: 1rem;
            }

            .cart-progress {
                grid-template-columns: repeat(3, 1fr);
            }

            .cart-item {
                padding: 1.75rem;
                gap: 1.25rem;
            }

            .checkout-btn {
                font-size: 1rem;
                padding: 1rem 2rem;
            }
        }

        @media (max-width: 768px) {
            .footer {
                padding: 3rem 0 0;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2.5rem;
                padding: 0 1.25rem;
            }

            .footer-col h3 {
                font-size: 1.5rem;
            }

            .footer-col h4 {
                font-size: 0.95rem;
                margin-bottom: 1.5rem;
            }

            .footer-logo-img {
                max-width: 160px;
                max-height: 55px;
            }

            .footer-bottom {
                margin-top: 2.5rem;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 0 1.25rem;
            }

            .footer-bottom-links {
                justify-content: center;
                gap: 1.5rem;
            }

            .cart-shell {
                padding: 0 1rem;
            }

            .cart-header {
                padding: 1.75rem 1.5rem;
                border-radius: 12px;
            }

            .cart-header h1 {
                font-size: 1.75rem;
            }

            .checkout-progress {
                padding: 1.25rem;
                margin-bottom: 2rem;
                gap: 0.75rem;
                border-radius: 12px;
            }

            .cart-progress {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .cart-progress .step {
                padding: 0.875rem 0.625rem;
            }

            .step-marker {
                width: 40px;
                height: 40px;
                font-size: 0.95rem;
            }

            .step-title {
                font-size: 0.8125rem;
            }

            .step-caption {
                font-size: 0.68rem;
                letter-spacing: 0.08em;
            }

            .cart-item {
                grid-template-columns: 90px minmax(0, 1fr);
                padding: 1.5rem;
                gap: 1rem;
                border-radius: 12px;
            }

            .item-image {
                width: 90px;
                height: 90px;
                border-radius: 10px;
            }

            .item-info {
                min-width: 0;
            }

            .item-name {
                font-size: 1rem;
            }

            .item-price {
                font-size: 1.125rem;
            }

            .item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 0.5rem;
            }

            .quantity-control {
                margin: 0;
            }

            .item-total {
                text-align: left;
                margin-top: 0;
            }

            .item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .cart-summary {
                padding: 1.75rem;
                border-radius: 16px;
            }

            .summary-title {
                font-size: 1.25rem;
            }

            .checkout-btn {
                padding: 1rem 1.5rem;
                font-size: 0.95rem;
            }

            .continue-shopping {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 540px) {
            .cart-shell {
                padding: 0 0.875rem;
            }

            .cart-header {
                padding: 1.5rem 1.25rem;
                border-radius: 10px;
            }

            .cart-header h1 {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .cart-header-subtitle {
                font-size: 0.875rem;
            }

            .checkout-progress {
                padding: 1rem;
                margin-bottom: 1.5rem;
                gap: 0.5rem;
                border-radius: 10px;
            }

            .checkout-progress .step {
                min-width: 120px;
            }

            .cart-progress {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                padding: 0.875rem;
            }

            .step-marker {
                width: 44px;
                height: 44px;
                font-size: 1rem;
            }

            .step-title {
                font-size: 0.9375rem;
            }

            .step-caption {
                font-size: 0.7rem;
            }

            .cart-item {
                grid-template-columns: 1fr;
                padding: 1.25rem;
                gap: 1rem;
                border-radius: 10px;
            }

            .item-image {
                width: 100%;
                height: 200px;
                border-radius: 8px;
                object-fit: cover;
            }

            .item-info {
                width: 100%;
            }

            .item-name {
                font-size: 1.0625rem;
            }

            .item-price {
                font-size: 1.25rem;
                margin-top: 0.5rem;
            }

            .item-meta {
                font-size: 0.8125rem;
            }

            .item-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .quantity-control {
                width: 100%;
                justify-content: space-between;
            }

            .quantity-btn {
                width: 44px;
                height: 44px;
                font-size: 1.125rem;
            }

            .quantity-value {
                font-size: 1rem;
                min-width: 50px;
            }

            .remove-btn {
                width: 100%;
                padding: 0.875rem;
                font-size: 0.9rem;
            }

            .item-total {
                text-align: center;
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid #e2e8f0;
            }

            .item-total .label {
                font-size: 0.875rem;
            }

            .item-total .value {
                font-size: 1.375rem;
            }

            .cart-summary {
                padding: 1.5rem;
                border-radius: 12px;
            }

            .summary-title {
                font-size: 1.125rem;
            }

            .summary-totals .summary-row {
                font-size: 0.9375rem;
            }

            .summary-totals .total-row {
                font-size: 1.125rem;
            }

            .checkout-btn {
                padding: 1.125rem 1.25rem;
                font-size: 1rem;
                border-radius: 10px;
            }

            .continue-shopping {
                font-size: 0.875rem;
                padding: 0.75rem;
            }

            .support-card {
                padding: 1rem;
                font-size: 0.875rem;
            }

            .empty-cart-message h2 {
                font-size: 1.5rem;
            }

            .empty-cart-message p {
                font-size: 0.9375rem;
            }

            .currency-tabs {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .item-header {
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Extra small devices */
        @media (max-width: 375px) {
            .cart-shell {
                padding: 0 0.75rem;
            }

            .cart-header {
                padding: 1.25rem 1rem;
            }

            .cart-header h1 {
                font-size: 1.375rem;
            }

            .checkout-progress {
                padding: 0.875rem;
            }

            .cart-item {
                padding: 1rem;
            }

            .item-image {
                height: 180px;
            }

            .cart-summary {
                padding: 1.25rem;
            }

            .checkout-btn {
                padding: 1rem;
                font-size: 0.9375rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Cart Page -->
    <section class="cart-page">
        <div class="cart-shell">
            <!-- 5-Step Checkout Progress -->
            <ul class="checkout-progress">
                <li class="step active" data-step="1">
                    <div class="step-marker">
                        <span class="step-number">1</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Shopping Cart</span>
                        <span class="step-caption">Review items</span>
                    </div>
                </li>
                <li class="step" data-step="2">
                    <div class="step-marker">
                        <span class="step-number">2</span>
                        <span class="step-icon">✓</span>
                    </div>
                    <div class="step-labels">
                        <span class="step-title">Information</span>
                        <span class="step-caption">Customer details</span>
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
            
            <div class="cart-header">
                <span class="cart-kicker">Shopping Cart</span>
                <h1>Review your order</h1>
                <p class="cart-header-subtitle">Real-time totals for Kenya and Tanzania markets.</p>
                <p class="cart-header-count">You currently have <?php echo $itemCount; ?> item<?php echo $itemCount != 1 ? 's' : ''; ?> in your bag.</p>
            </div>

            <div class="cart-alerts">
                <?php if (!empty($validation['errors'])): ?>
                    <div class="validation-alert" id="cartValidationAlert">
                        <strong>Heads up:</strong>
                        <ul>
                            <?php foreach ($validation['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div id="cartMessage" class="cart-message" style="display:none;"></div>
            </div>

            <div id="emptyCartState" class="empty-cart" style="<?php echo empty($items) ? '' : 'display:none;'; ?>">
                <svg width="120" height="120" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2.25 3a.75.75 0 0 0 0 1.5h1.3l1.386 7.17a2.25 2.25 0 0 0 2.217 1.83h7.694a2.25 2.25 0 0 0 2.217-1.83l1.278-6.639H20.25a.75.75 0 0 0 0-1.5H5.492l-.3-1.553A1.5 1.5 0 0 0 3.72 3H2.25Z"/>
                    <path d="M9 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm9 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                </svg>
                <h2>Your cart is feeling light</h2>
                <p>Discover flagship cutters, laminators, and accessories tailored for East Africa.</p>
                <div class="empty-cart-actions">
                    <a href="products.php" class="btn btn-primary">Browse products</a>
                    <a href="index.php#contact" class="btn btn-outline">Talk to sales</a>
                </div>
            </div>

            <div id="cartLayout" class="cart-content" style="<?php echo empty($items) ? 'display:none;' : ''; ?>">
                <div class="cart-items">
                    <?php foreach ($items as $productId => $item): ?>
                        <div class="cart-item" data-product-id="<?php echo $productId; ?>">
                            <div class="item-image">
                                <?php $cartItemImage = normalize_product_image_url($item['image'] ?? ''); ?>
                                <?php if (!empty($cartItemImage)): ?>
                                    <img src="<?php echo htmlspecialchars($cartItemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Crect fill='%23f1f5f9' width='120' height='120'/%3E%3C/svg%3E" alt="No image">
                                <?php endif; ?>
                            </div>

                            <div class="item-details">
                                <div class="item-header">
                                    <div>
                                        <div class="item-name">
                                            <a href="product-detail.php?slug=<?php echo urlencode($item['slug']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </div>
                                        <div class="item-sku">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
                                    </div>
                                    <span class="item-stock<?php echo !empty($item['track_stock']) && (int)$item['stock_quantity'] <= 0 ? ' backorder' : ''; ?>">
                                        <?php if (!empty($item['track_stock'])): ?>
                                            <?php echo (int)$item['stock_quantity'] > 0 ? (int)$item['stock_quantity'] . ' in stock' : 'Backorder'; ?>
                                        <?php else: ?>
                                            Ready to ship
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="item-price">
                                    <?php 
                                    $itemPrice = $currencyDetector->getPrice($item['price_kes'], $currentCurrency);
                                    ?>
                                    <span class="price"><?php echo $currencyDetector->formatPrice($itemPrice, $currentCurrency); ?></span>
                                </div>
                                <div class="item-quantity">
                                    <span class="quantity-label">Quantity</span>
                                    <div class="qty-controls">
                                        <button class="qty-btn qty-decrease" data-product-id="<?php echo $productId; ?>">−</button>
                                        <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['track_stock'] ? (int)$item['stock_quantity'] : 999; ?>" data-product-id="<?php echo $productId; ?>">
                                        <button class="qty-btn qty-increase" data-product-id="<?php echo $productId; ?>">+</button>
                                    </div>
                                </div>
                            </div>

                            <div class="item-actions">
                                <div class="item-total">
                                    <div class="item-total-label">Item total</div>
                                    <div class="item-total-price">
                                        <?php echo $currencyDetector->formatPrice($itemPrice * $item['quantity'], $currentCurrency); ?>
                                    </div>
                                </div>
                                <button class="remove-btn" data-product-id="<?php echo $productId; ?>">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary">
                    <h2 class="summary-title">Order summary (<?php echo $currentCurrency; ?>)</h2>

                    <div class="summary-totals">
                        <div class="summary-row subtotal">
                            <span>Subtotal</span>
                            <strong class="summary-value"><?php echo $currencyDetector->formatPrice($totals['subtotal'], $currentCurrency); ?></strong>
                        </div>
                        <div class="summary-row tax">
                            <span>Tax (<?php echo ($totals['tax_rate'] * 100); ?>%)</span>
                            <span class="summary-value"><?php echo $currencyDetector->formatPrice($totals['tax'], $currentCurrency); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <strong class="summary-value"><?php echo $currencyDetector->formatPrice($totals['total'], $currentCurrency); ?></strong>
                        </div>
                    </div>

                    <p class="summary-note">Shipping and installation are quoted after checkout. We'll confirm lead times once we receive your order.</p>

                    <div class="support-card">
                        Need a formal quotation? Call Kenya <a href="tel:+254716522828">+254 716 522 828</a> or Tanzania <a href="tel:+255753098911">+255 753 098 911</a>.
                    </div>

                    <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                        Proceed to checkout
                    </button>

                    <a href="products.php" class="continue-shopping">← Continue shopping</a>
                </aside>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        const cartInitialData = <?php echo json_encode([
            'totals' => $totals,
            'items' => $items,
            'count' => $itemCount,
        ]); ?> || { totals: {}, items: {}, count: 0 };

        const currencyMeta = {
            kes: { code: 'KES', locale: 'en-KE' },
            tzs: { code: 'TZS', locale: 'en-TZ' }
        };

        const cartLayout = document.getElementById('cartLayout');
        const emptyCartState = document.getElementById('emptyCartState');
    const cartMessage = document.getElementById('cartMessage');
    const cartHeaderCount = document.querySelector('.cart-header-count');
        const cartCountNodes = document.querySelectorAll('#cart-count');
        const currencyTabs = document.querySelectorAll('.currency-tab');
        const summaryGroups = document.querySelectorAll('.summary-totals');
        const validationAlert = document.getElementById('cartValidationAlert');

        let activeCurrency = 'kes';

        if (validationAlert) {
            setTimeout(() => {
                validationAlert.style.display = 'none';
            }, 6000);
        }

        function formatCurrency(amount, currencyCode) {
            const code = (currencyMeta[currencyCode.toLowerCase()]?.code || currencyCode || '').toUpperCase();
            const numericAmount = Number(amount) || 0;
            const formattedAmount = numericAmount.toLocaleString('en-US', { maximumFractionDigits: 0 });
            return `${code} ${formattedAmount}`.trim();
        }

        function showMessage(message, type = 'info') {
            if (!cartMessage) {
                return;
            }

            if (!message) {
                cartMessage.style.display = 'none';
                cartMessage.textContent = '';
                cartMessage.className = 'cart-message';
                return;
            }

            cartMessage.textContent = message;
            cartMessage.className = `cart-message ${type}`;
            cartMessage.style.display = 'block';
        }

        function toggleEmptyState(isEmpty) {
            if (!cartLayout || !emptyCartState) {
                return;
            }

            cartLayout.style.display = isEmpty ? 'none' : '';
            emptyCartState.style.display = isEmpty ? '' : 'none';
        }

        function updateCartCount(count) {
            cartCountNodes.forEach(node => {
                node.textContent = count;
            });

            if (cartHeaderCount) {
                cartHeaderCount.textContent = `You currently have ${count} item${count === 1 ? '' : 's'} in your bag.`;
            }
        }

        function updateCartSummary(totals) {
            if (!totals) {
                return;
            }

            Object.entries(totals).forEach(([currencyKey, currencyTotals]) => {
                if (!currencyTotals || typeof currencyTotals !== 'object') {
                    return;
                }

                const subtotalEl = document.querySelector(`[data-summary="${currencyKey}-subtotal"]`);
                const taxEl = document.querySelector(`[data-summary="${currencyKey}-tax"]`);
                const totalEl = document.querySelector(`[data-summary="${currencyKey}-total"]`);
                const currencyCode = currencyMeta[currencyKey]?.code || currencyKey.toUpperCase();

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

        function toggleItemLoading(productId, isLoading) {
            const row = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (!row) {
                return;
            }

            row.classList.toggle('updating', isLoading);
            row.querySelectorAll('button, input').forEach(control => {
                control.disabled = isLoading;
            });
        }

        function updateItemRow(productId, item) {
            const row = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (!row || !item) {
                return;
            }

            const qtyInput = row.querySelector('.qty-input');
            if (qtyInput) {
                qtyInput.value = item.quantity;
                qtyInput.setAttribute('max', item.track_stock ? item.stock_quantity : 999);
            }

            Object.keys(currencyMeta).forEach(currencyKey => {
                const priceValue = item[`price_${currencyKey}`];
                const priceEl = row.querySelector(`[data-currency-price="${currencyKey}"]`);
                const totalEl = row.querySelector(`[data-currency-total="${currencyKey}"]`);
                const currencyCode = currencyMeta[currencyKey].code;

                if (priceEl && typeof priceValue !== 'undefined') {
                    priceEl.textContent = formatCurrency(priceValue, currencyCode);
                }

                if (totalEl && typeof priceValue !== 'undefined') {
                    totalEl.textContent = formatCurrency(priceValue * item.quantity, currencyCode);
                }
            });
        }

        function removeItemRow(productId) {
            const row = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (row) {
                row.remove();
            }

            const remainingItems = document.querySelectorAll('.cart-item').length;
            toggleEmptyState(remainingItems === 0);
        }

        function switchCurrency(currencyKey) {
            const normalized = currencyKey.toLowerCase();
            if (!currencyMeta[normalized]) {
                return;
            }

            activeCurrency = normalized;

            currencyTabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.currency === normalized);
            });

            summaryGroups.forEach(group => {
                group.style.display = group.dataset.summaryGroup === normalized ? 'grid' : 'none';
            });

            document.querySelectorAll('[data-currency-total]').forEach(el => {
                el.style.display = el.dataset.currencyTotal === normalized ? 'block' : 'none';
            });

            document.querySelectorAll('[data-currency-price]').forEach(el => {
                el.style.display = el.dataset.currencyPrice === normalized ? 'inline' : 'none';
            });
        }

        currencyTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                switchCurrency(tab.dataset.currency);
            });
        });

        function handleCartResponse(data, context = {}) {
            if (!data) {
                showMessage('Unexpected response from server.', 'error');
                return;
            }

            if (data.success) {
                if (data.item) {
                    updateItemRow(data.item.product_id || context.productId, data.item);
                }

                if (data.removed_product_id) {
                    removeItemRow(data.removed_product_id);
                }

                if (typeof data.cart_count !== 'undefined') {
                    updateCartCount(data.cart_count);
                }

                if (data.totals) {
                    updateCartSummary(data.totals);
                }

                const message = data.message || data.notice || '';
                if (message) {
                    const messageType = data.notice ? 'info' : 'success';
                    showMessage(message, messageType);
                } else {
                    showMessage('', 'info');
                }

                if (data.cart_count === 0) {
                    toggleEmptyState(true);
                }

                switchCurrency(activeCurrency);
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
                showMessage(errorMessage || 'Unable to update the cart.', 'error');
            }
        }

        function updateQuantity(productId, quantity) {
            const row = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
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
                qtyInput.value = previousQty;
                return;
            }

            qtyInput.value = normalizedQty;
            toggleItemLoading(productId, true);
            showMessage('', 'info');

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
                handleCartResponse(data, { productId, previousQty });
            })
            .catch(() => {
                toggleItemLoading(productId, false);
                qtyInput.value = previousQty;
                showMessage('Could not update the cart. Please try again.', 'error');
            });
        }

        function removeFromCart(productId) {
            toggleItemLoading(productId, true);
            showMessage('', 'info');

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
                handleCartResponse(data, { productId, action: 'remove' });
            })
            .catch(() => {
                toggleItemLoading(productId, false);
                showMessage('Could not remove the item. Please try again.', 'error');
            });
        }

        document.querySelectorAll('.qty-decrease').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
                if (!input) {
                    return;
                }
                const current = parseInt(input.value, 10) || 1;
                updateQuantity(productId, current - 1);
            });
        });

        document.querySelectorAll('.qty-increase').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
                if (!input) {
                    return;
                }
                const current = parseInt(input.value, 10) || 1;
                updateQuantity(productId, current + 1);
            });
        });

        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.dataset.productId;
                const requestedQty = parseInt(this.value, 10);
                updateQuantity(productId, isNaN(requestedQty) ? 1 : requestedQty);
            });
        });

        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                if (confirm('Remove this item from cart?')) {
                    removeFromCart(productId);
                }
            });
        });

        toggleEmptyState(document.querySelectorAll('.cart-item').length === 0);
        updateCartSummary(cartInitialData.totals);
        updateCartCount(cartInitialData.count);
        switchCurrency(activeCurrency);
    </script>
</body>
</html>
