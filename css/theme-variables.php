<?php
/**
 * Dynamic Theme Variables CSS Generator
 * Generates CSS custom properties from database settings
 */

// Prevent direct access
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

// Set content type to CSS
header('Content-Type: text/css; charset=utf-8');

// Include config to get database connection
require_once __DIR__ . '/../includes/config.php';

$db = Database::getInstance()->getConnection();

// Function to get theme setting
function getThemeSetting($key, $default = '') {
    global $db;
    static $cache = [];
    
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $value = $result ? $result['setting_value'] : $default;
    $cache[$key] = $value;
    return $value;
}

// Get all theme settings with defaults
$theme = [
    'primary_color' => getThemeSetting('theme_primary_color', '#3b82f6'),
    'secondary_color' => getThemeSetting('theme_secondary_color', '#8b5cf6'),
    'accent_color' => getThemeSetting('theme_accent_color', '#06b6d4'),
    'success_color' => getThemeSetting('theme_success_color', '#10b981'),
    'warning_color' => getThemeSetting('theme_warning_color', '#f59e0b'),
    'error_color' => getThemeSetting('theme_error_color', '#ef4444'),
    'text_primary' => getThemeSetting('theme_text_primary', '#1e293b'),
    'text_secondary' => getThemeSetting('theme_text_secondary', '#64748b'),
    'background' => getThemeSetting('theme_background', '#ffffff'),
    'card_background' => getThemeSetting('theme_card_background', '#f8fafc'),
    'border_color' => getThemeSetting('theme_border_color', '#e2e8f0'),
    'link_color' => getThemeSetting('theme_link_color', '#3b82f6'),
    'button_radius' => getThemeSetting('theme_button_radius', '8') . 'px',
    'card_radius' => getThemeSetting('theme_card_radius', '12') . 'px',
    'font_family' => getThemeSetting('theme_font_family', 'system-ui, -apple-system, sans-serif'),
    'heading_font' => getThemeSetting('theme_heading_font', 'system-ui, -apple-system, sans-serif')
];

// Helper function to lighten color
function lightenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = min(255, $r + (255 - $r) * $percent / 100);
    $g = min(255, $g + (255 - $g) * $percent / 100);
    $b = min(255, $b + (255 - $b) * $percent / 100);
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Helper function to darken color
function darkenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, $r - $r * $percent / 100);
    $g = max(0, $g - $g * $percent / 100);
    $b = max(0, $b - $b * $percent / 100);
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Generate lighter and darker variants
$theme['primary_light'] = lightenColor($theme['primary_color'], 80);
$theme['primary_dark'] = darkenColor($theme['primary_color'], 15);
$theme['success_light'] = lightenColor($theme['success_color'], 80);
$theme['warning_light'] = lightenColor($theme['warning_color'], 80);
$theme['error_light'] = lightenColor($theme['error_color'], 80);

?>
/**
 * Theme Variables - Auto-generated from Admin Settings
 * Last Generated: <?= date('Y-m-d H:i:s') ?>
 */

:root {
    /* Brand Colors */
    --theme-primary: <?= $theme['primary_color'] ?>;
    --theme-primary-light: <?= $theme['primary_light'] ?>;
    --theme-primary-dark: <?= $theme['primary_dark'] ?>;
    --theme-secondary: <?= $theme['secondary_color'] ?>;
    --theme-accent: <?= $theme['accent_color'] ?>;
    
    /* Status Colors */
    --theme-success: <?= $theme['success_color'] ?>;
    --theme-success-light: <?= $theme['success_light'] ?>;
    --theme-warning: <?= $theme['warning_color'] ?>;
    --theme-warning-light: <?= $theme['warning_light'] ?>;
    --theme-error: <?= $theme['error_color'] ?>;
    --theme-error-light: <?= $theme['error_light'] ?>;
    
    /* Typography Colors */
    --theme-text-primary: <?= $theme['text_primary'] ?>;
    --theme-text-secondary: <?= $theme['text_secondary'] ?>;
    --theme-link: <?= $theme['link_color'] ?>;
    
    /* Background Colors */
    --theme-bg: <?= $theme['background'] ?>;
    --theme-card-bg: <?= $theme['card_background'] ?>;
    --theme-border: <?= $theme['border_color'] ?>;
    
    /* Border Radius */
    --theme-btn-radius: <?= $theme['button_radius'] ?>;
    --theme-card-radius: <?= $theme['card_radius'] ?>;
    
    /* Typography */
    --theme-font-family: <?= $theme['font_family'] ?>;
    --theme-heading-font: <?= $theme['heading_font'] ?>;
}

/* Apply theme colors to common elements */
body {
    font-family: var(--theme-font-family);
    color: var(--theme-text-primary);
    background-color: var(--theme-bg);
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--theme-heading-font);
    color: var(--theme-text-primary);
}

a {
    color: var(--theme-link);
}

a:hover {
    color: var(--theme-primary-dark);
}

/* Button Styles */
.btn-primary,
.btn.btn-primary {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-dark) 100%);
    border-radius: var(--theme-btn-radius);
    color: white;
}

.btn-primary:hover,
.btn.btn-primary:hover {
    background: linear-gradient(135deg, var(--theme-primary-dark) 0%, var(--theme-primary) 100%);
}

.btn-secondary {
    background-color: var(--theme-secondary);
    border-radius: var(--theme-btn-radius);
}

.btn-success {
    background-color: var(--theme-success);
    border-radius: var(--theme-btn-radius);
}

.btn-warning {
    background-color: var(--theme-warning);
    border-radius: var(--theme-btn-radius);
}

.btn-error,
.btn-danger {
    background-color: var(--theme-error);
    border-radius: var(--theme-btn-radius);
}

/* Card Styles */
.card,
.product-card,
.pricing-card {
    background-color: var(--theme-card-bg);
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-card-radius);
}

/* Alert Styles */
.alert-success {
    background-color: var(--theme-success-light);
    color: var(--theme-success);
    border: 1px solid var(--theme-success);
    border-radius: var(--theme-btn-radius);
}

.alert-warning {
    background-color: var(--theme-warning-light);
    color: var(--theme-warning);
    border: 1px solid var(--theme-warning);
    border-radius: var(--theme-btn-radius);
}

.alert-error,
.alert-danger {
    background-color: var(--theme-error-light);
    color: var(--theme-error);
    border: 1px solid var(--theme-error);
    border-radius: var(--theme-btn-radius);
}

/* Form Elements */
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="number"],
input[type="password"],
select,
textarea {
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-btn-radius);
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="tel"]:focus,
input[type="number"]:focus,
input[type="password"]:focus,
select:focus,
textarea:focus {
    border-color: var(--theme-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--theme-primary-rgb, 59, 130, 246), 0.1);
}

/* Navigation */
.header {
    background-color: var(--theme-bg);
    border-bottom: 1px solid var(--theme-border);
}

.nav-link {
    color: var(--theme-text-primary);
}

.nav-link:hover {
    color: var(--theme-primary);
}

/* Footer */
.footer {
    background-color: var(--theme-card-bg);
    border-top: 1px solid var(--theme-border);
}

/* Pricing Colors Override */
.pricing-box {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-dark) 100%);
    border-radius: var(--theme-card-radius);
}

/* WhatsApp Button - Keep green but use theme radius */
.btn-whatsapp {
    border-radius: var(--theme-btn-radius);
}

/* Badge Styles */
.badge {
    border-radius: calc(var(--theme-btn-radius) / 2);
}

.badge-primary {
    background-color: var(--theme-primary);
}

.badge-success {
    background-color: var(--theme-success);
}

.badge-warning {
    background-color: var(--theme-warning);
}

.badge-error,
.badge-danger {
    background-color: var(--theme-error);
}

/* Section Backgrounds */
.section {
    background-color: var(--theme-bg);
}

.section.alternate {
    background-color: var(--theme-card-bg);
}

/* Progress Bar */
.progress-bar {
    background-color: var(--theme-primary);
}

/* Tabs */
.tab-active {
    border-bottom: 2px solid var(--theme-primary);
    color: var(--theme-primary);
}

/* Loading Spinner */
.spinner {
    border-top-color: var(--theme-primary);
}
