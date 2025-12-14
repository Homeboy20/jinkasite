<?php
/**
 * Production Configuration Template
 * 
 * Copy this to includes/config.php and update with production values
 */

// CRITICAL: Set to production mode
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);

// Database Configuration - UPDATE THESE!
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_production_database_name');
define('DB_USER', 'your_production_db_user');
define('DB_PASS', 'your_production_db_password');

// Security Keys - GENERATE NEW RANDOM STRINGS!
// Use: openssl rand -base64 48
define('SECRET_KEY', 'GENERATE_UNIQUE_64_CHARACTER_RANDOM_STRING_HERE');
define('ENCRYPTION_KEY', 'GENERATE_UNIQUE_32_CHARACTER_MINIMUM_STRING_HERE');

// Site URLs - UPDATE TO YOUR DOMAIN!
define('SITE_URL', 'https://yourdomain.com');

// Payment Gateway - SET TO FALSE FOR PRODUCTION!
define('PAYMENT_USE_SANDBOX', false);

// Email Configuration - UPDATE WITH YOUR SMTP!
define('SMTP_HOST', 'mail.yourdomain.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'your_smtp_password_here');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');

// Business Contact Info
define('BUSINESS_EMAIL', 'info@yourdomain.com');
define('ADMIN_EMAIL', 'admin@yourdomain.com');

/*
 * IMPORTANT: After updating this file:
 * 1. Set restrictive file permissions: chmod 600 includes/config.php
 * 2. Never commit this file to version control
 * 3. Keep a secure backup of this configuration
 * 4. Test database connection after deployment
 * 5. Test email sending functionality
 * 6. Monitor error logs: logs/php_errors.log
 */
