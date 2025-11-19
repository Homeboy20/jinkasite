-- JINKA Plotter Website Database Schema
-- Created: November 6, 2025
-- Description: Complete database schema for ecommerce functionality

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `jinka_plotter_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jinka_plotter`;

-- --------------------------------------------------------
-- Table structure for `admin_users`
-- --------------------------------------------------------

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','manager') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime NULL,
  `login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `categories`
-- --------------------------------------------------------

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text,
  `image` varchar(255) NULL,
  `parent_id` int(11) NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `seo_title` varchar(255) NULL,
  `seo_description` varchar(255) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_is_active` (`is_active`),
  FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `products`
-- --------------------------------------------------------

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `sku` varchar(50) NOT NULL UNIQUE,
  `category_id` int(11) NULL,
  `short_description` text,
  `description` longtext,
  `specifications` JSON,
  `features` JSON,
  `price_kes` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_tzs` decimal(15,2) NOT NULL DEFAULT 0.00,
  `compare_price_kes` decimal(12,2) NULL,
  `compare_price_tzs` decimal(15,2) NULL,
  `cost_price` decimal(12,2) NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 0,
  `track_stock` tinyint(1) DEFAULT 1,
  `allow_backorder` tinyint(1) DEFAULT 0,
  `weight` decimal(8,2) NULL COMMENT 'Weight in kg',
  `dimensions` JSON COMMENT 'Length, Width, Height in cm',
  `warranty_period` int(11) DEFAULT 12 COMMENT 'Warranty in months',
  `images` JSON COMMENT 'Array of image URLs',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `seo_title` varchar(255) NULL,
  `seo_description` varchar(255) NULL,
  `seo_keywords` varchar(255) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_price_kes` (`price_kes`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `customers`
-- --------------------------------------------------------

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_type` enum('individual','business') DEFAULT 'individual',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `whatsapp` varchar(20) NULL,
  `business_name` varchar(100) NULL,
  `tax_number` varchar(50) NULL,
  `country` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) NULL,
  `postal_code` varchar(20) NULL,
  `preferred_currency` enum('KES','TZS') DEFAULT 'KES',
  `customer_notes` text NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 0 COMMENT 'Payment terms in days',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_country` (`country`),
  KEY `idx_customer_type` (`customer_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `orders`
-- --------------------------------------------------------

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL UNIQUE,
  `customer_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `currency` enum('KES','TZS') NOT NULL DEFAULT 'KES',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) NULL,
  `payment_reference` varchar(100) NULL,
  `shipping_method` varchar(50) NULL,
  `shipping_address` JSON NOT NULL,
  `billing_address` JSON NOT NULL,
  `order_notes` text NULL,
  `admin_notes` text NULL,
  `estimated_delivery` date NULL,
  `shipped_at` datetime NULL,
  `delivered_at` datetime NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `order_items`
-- --------------------------------------------------------

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `product_options` JSON NULL COMMENT 'Selected product options',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `quotes`
-- --------------------------------------------------------

CREATE TABLE `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(20) NOT NULL UNIQUE,
  `customer_id` int(11) NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `business_name` varchar(100) NULL,
  `status` enum('pending','sent','accepted','declined','expired') DEFAULT 'pending',
  `currency` enum('KES','TZS') NOT NULL DEFAULT 'KES',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valid_until` date NOT NULL,
  `terms_conditions` text NULL,
  `notes` text NULL,
  `admin_notes` text NULL,
  `sent_at` datetime NULL,
  `accepted_at` datetime NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_number` (`quote_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_valid_until` (`valid_until`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `quote_items`
-- --------------------------------------------------------

CREATE TABLE `quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `product_id` int(11) NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(50) NULL,
  `description` text NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quote_id` (`quote_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `inquiries`
-- --------------------------------------------------------

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `business_name` varchar(100) NULL,
  `subject` varchar(255) NOT NULL DEFAULT 'General Inquiry',
  `message` text NOT NULL,
  `product_interest` varchar(255) NULL,
  `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `assigned_to` int(11) NULL,
  `admin_notes` text NULL,
  `source` varchar(50) DEFAULT 'website',
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(500) NULL,
  `resolved_at` datetime NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` longtext,
  `setting_type` enum('string','number','boolean','json','text') DEFAULT 'string',
  `description` varchar(255) NULL,
  `group_name` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `activity_logs`
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL,
  `user_type` enum('admin','customer') NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NULL,
  `record_id` int(11) NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(500) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default data
-- --------------------------------------------------------

-- Default admin user (password: Admin@123456)
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@procutsolutions.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Default categories
INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
('Cutting Plotters', 'cutting-plotters', 'Professional vinyl cutting plotters and machines'),
('Accessories', 'accessories', 'Cutting blades, mats, and other accessories'),
('Software', 'software', 'Design and cutting software solutions'),
('Materials', 'materials', 'Vinyl, transfer papers, and cutting materials');

-- Default product (JINKA XL-1351E)
INSERT INTO `products` (`name`, `slug`, `sku`, `category_id`, `short_description`, `description`, `specifications`, `features`, `price_kes`, `price_tzs`, `stock_quantity`, `images`) VALUES
('JINKA XL-1351E Cutting Plotter', 'jinka-xl-1351e-cutting-plotter', 'JINKA-XL-1351E', 1, 
'Professional 53-inch vinyl cutting plotter with high precision stepper motor and CE certification.',
'The JINKA XL-1351E is a professional-grade cutting plotter designed for commercial sign making, vehicle branding, and large format vinyl cutting applications. With its 53-inch cutting width and precision stepper motor, this machine delivers exceptional results for businesses across Kenya and Tanzania.',
JSON_OBJECT(
    'cutting_width', '1210mm (47.6 inches)',
    'feed_width', '1350mm (53.1 inches)',
    'cutting_length', '2000mm (6.5 feet)',
    'cutting_speed', '10-800mm/s',
    'cutting_pressure', '10-500g',
    'accuracy', '±0.1mm',
    'connectivity', 'USB 2.0, RS-232C',
    'power', 'AC 110-240V, ≤100W',
    'dimensions', '163 × 34 × 44 cm',
    'weight', '35 kg',
    'certification', 'CE Certified'
),
JSON_ARRAY(
    'High precision stepper motor',
    'Universal software support',
    'Adjustable cutting speed and pressure',
    'Dual connectivity (USB & RS-232)',
    'Large format cutting capability',
    'CE certified quality',
    'Low noise operation',
    'Memory function for repeat cutting'
),
120000.00, 2400000.00, 5,
JSON_ARRAY('images/plotter-hero.webp', 'images/plotter-main.jpg', 'images/plotter-action.jpg'));

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `group_name`) VALUES
('site_name', 'ProCut Solutions', 'string', 'Website name', 'general'),
('site_email', 'support@procutsolutions.com', 'string', 'Default contact email', 'general'),
('business_phone_tz', '+255753098911', 'string', 'Tanzania phone number', 'contact'),
('business_phone_ke', '+254716522828', 'string', 'Kenya phone number', 'contact'),
('whatsapp_number', '+255753098911', 'string', 'WhatsApp number', 'contact'),
('default_currency', 'KES', 'string', 'Default currency', 'ecommerce'),
('tax_rate_kes', '16', 'number', 'VAT rate for Kenya (16%)', 'ecommerce'),
('tax_rate_tzs', '18', 'number', 'VAT rate for Tanzania (18%)', 'ecommerce'),
('enable_stock_tracking', '1', 'boolean', 'Enable inventory tracking', 'ecommerce'),
('low_stock_threshold', '5', 'number', 'Low stock notification threshold', 'ecommerce');

COMMIT;