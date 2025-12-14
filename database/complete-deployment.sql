-- ================================================================
-- JINKA PLOTTER WEBSITE - COMPLETE DEPLOYMENT DATABASE SCRIPT
-- Domain: ndosa.store
-- Created: December 15, 2025
-- Description: Complete database creation and initialization
-- ================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ================================================================
-- CREATE DATABASE
-- ================================================================

CREATE DATABASE IF NOT EXISTS `ndosa_store` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ndosa_store`;

-- ================================================================
-- CORE TABLES
-- ================================================================

-- Admin Users Table
CREATE TABLE IF NOT EXISTS `admin_users` (
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

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
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

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
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

-- Product Images Table
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `alt_text` varchar(255) NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Relationships Table
CREATE TABLE IF NOT EXISTS `product_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relationship_type` enum('related','upsell','cross_sell','accessory','bundle') DEFAULT 'related',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  KEY `idx_relationship_type` (`relationship_type`),
  UNIQUE KEY `unique_relationship` (`product_id`, `related_product_id`, `relationship_type`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- CUSTOMER TABLES
-- ================================================================

-- Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_type` enum('individual','business') DEFAULT 'individual',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NULL,
  `whatsapp` varchar(20) NULL,
  `business_name` varchar(100) NULL,
  `tax_number` varchar(50) NULL,
  `country` varchar(50) NOT NULL DEFAULT 'Kenya',
  `city` varchar(50) NULL,
  `address_line1` varchar(255) NULL,
  `address_line2` varchar(255) NULL,
  `postal_code` varchar(20) NULL,
  `preferred_currency` enum('KES','TZS','USD') DEFAULT 'KES',
  `customer_notes` text NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` datetime NULL,
  `phone_verified_at` datetime NULL,
  `email_verification_token` varchar(255) NULL,
  `firebase_uid` varchar(255) NULL,
  `firebase_token` text NULL,
  `verification_attempts` int(11) DEFAULT 0,
  `last_verification_attempt` datetime NULL,
  `last_login` datetime NULL,
  `last_firebase_sync` datetime NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_country` (`country`),
  KEY `idx_customer_type` (`customer_type`),
  KEY `idx_firebase_uid` (`firebase_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Addresses Table
CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing','both') NOT NULL DEFAULT 'shipping',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Kenya',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `is_default` (`is_default`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Wishlists Table
CREATE TABLE IF NOT EXISTS `customer_wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_product` (`customer_id`, `product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Reviews Table
CREATE TABLE IF NOT EXISTS `customer_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_title` varchar(200) NOT NULL,
  `review_text` text NOT NULL,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Notifications Table
CREATE TABLE IF NOT EXISTS `customer_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `type` enum('order','payment','shipping','review','system','promotion') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ORDER TABLES
-- ================================================================

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL UNIQUE,
  `customer_id` int(11) NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `currency` enum('KES','TZS','USD') NOT NULL DEFAULT 'KES',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid','refunded','failed') DEFAULT 'pending',
  `payment_method` varchar(50) NULL,
  `payment_reference` varchar(100) NULL,
  `shipping_method` varchar(50) NULL,
  `shipping_address` JSON NOT NULL,
  `billing_address` JSON NULL,
  `order_notes` text NULL,
  `admin_notes` text NULL,
  `estimated_delivery` date NULL,
  `shipped_at` datetime NULL,
  `delivered_at` datetime NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(500) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `product_options` JSON NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DELIVERY TABLES
-- ================================================================

-- Deliveries Table
CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `delivery_status` enum('pending','assigned','picked_up','in_transit','out_for_delivery','delivered','failed','returned') DEFAULT 'pending',
  `driver_name` varchar(100) NULL,
  `driver_phone` varchar(20) NULL,
  `vehicle_number` varchar(20) NULL,
  `pickup_address` text NULL,
  `delivery_address` text NOT NULL,
  `estimated_delivery_date` date NULL,
  `actual_delivery_date` datetime NULL,
  `current_location` varchar(255) NULL,
  `latitude` decimal(10,8) NULL,
  `longitude` decimal(11,8) NULL,
  `last_location_update` datetime NULL,
  `delivery_notes` text NULL,
  `delivery_instructions` text NULL,
  `signature_image` varchar(255) NULL,
  `proof_of_delivery` varchar(255) NULL,
  `recipient_name` varchar(100) NULL,
  `assigned_at` datetime NULL,
  `picked_up_at` datetime NULL,
  `in_transit_at` datetime NULL,
  `out_for_delivery_at` datetime NULL,
  `delivered_at` datetime NULL,
  `failed_at` datetime NULL,
  `failure_reason` text NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_delivery_status` (`delivery_status`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Status History Table
CREATE TABLE IF NOT EXISTS `delivery_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `location` varchar(255) NULL,
  `notes` text NULL,
  `updated_by` varchar(100) NULL,
  `latitude` decimal(10,8) NULL,
  `longitude` decimal(11,8) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_id` (`delivery_id`),
  FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- QUOTE TABLES
-- ================================================================

-- Quotes Table
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(20) NOT NULL UNIQUE,
  `customer_id` int(11) NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `business_name` varchar(100) NULL,
  `status` enum('pending','sent','accepted','declined','expired') DEFAULT 'pending',
  `currency` enum('KES','TZS','USD') NOT NULL DEFAULT 'KES',
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
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quote Items Table
CREATE TABLE IF NOT EXISTS `quote_items` (
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
  FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INQUIRY & SUPPORT TABLES
-- ================================================================

-- Inquiries Table
CREATE TABLE IF NOT EXISTS `inquiries` (
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
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Tickets Table
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) NOT NULL UNIQUE,
  `customer_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('technical','billing','product','shipping','other') DEFAULT 'other',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `satisfaction_score` tinyint(1) unsigned DEFAULT NULL,
  `satisfaction_note` text DEFAULT NULL,
  `satisfaction_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `last_response_at` timestamp NULL DEFAULT NULL,
  `last_response_by` enum('customer','agent') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Support Tickets Table (for customer portal)
CREATE TABLE IF NOT EXISTS `customer_support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `category` enum('order','payment','technical','product','general') NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `message` text NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Ticket Replies Table
CREATE TABLE IF NOT EXISTS `customer_support_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `customer_support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Messages Table
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('customer','agent','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `attachments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_by_customer` tinyint(1) DEFAULT 0,
  `read_by_agent` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live Chat Sessions Table
CREATE TABLE IF NOT EXISTS `live_chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL UNIQUE,
  `customer_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('active','waiting','ended','transferred') DEFAULT 'waiting',
  `assigned_to` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_customer_id` (`customer_id`),
  KEY `idx_chat_status` (`status`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live Chat Messages Table
CREATE TABLE IF NOT EXISTS `live_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('customer','agent','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','file','image','system') DEFAULT 'text',
  `attachment_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_session_id` (`session_id`),
  FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support FAQ Table
CREATE TABLE IF NOT EXISTS `support_faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canned Responses Table
CREATE TABLE IF NOT EXISTS `support_canned_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `shortcut` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shortcut` (`shortcut`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SYSTEM TABLES
-- ================================================================

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
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

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
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
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Firebase Auth Logs Table
CREATE TABLE IF NOT EXISTS `firebase_auth_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NULL,
  `firebase_uid` varchar(255) NULL,
  `auth_method` enum('email','phone','otp_login') NOT NULL,
  `phone_number` varchar(20) NULL,
  `email` varchar(255) NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `status` enum('success','failed','pending') NOT NULL,
  `error_message` text NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_firebase_uid` (`firebase_uid`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DEFAULT DATA INSERTION
-- ================================================================

-- Default Admin User (username: admin, password: Admin@2025!)
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@ndosa.store', '$2y$10$jZYuXEjKK3bKN8l8Q8S7W.5xN1KvH9FY6LS/QgDYf3xM8KF6W8.6a', 'System Administrator', 'super_admin', 1)
ON DUPLICATE KEY UPDATE username=username;

-- Default Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `is_active`) VALUES
('Cutting Plotters', 'cutting-plotters', 'Professional vinyl cutting plotters and machines', 1),
('Accessories', 'accessories', 'Cutting blades, mats, and other accessories', 1),
('Software', 'software', 'Design and cutting software solutions', 1),
('Materials', 'materials', 'Vinyl, transfer papers, and cutting materials', 1),
('Spare Parts', 'spare-parts', 'Original spare parts for JINKA plotters', 1)
ON DUPLICATE KEY UPDATE slug=slug;

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `group_name`, `is_public`) VALUES
('site_name', 'Ndosa Store', 'string', 'Website name', 'general', 1),
('site_tagline', 'Professional Cutting Plotters & Equipment', 'string', 'Website tagline', 'general', 1),
('site_url', 'https://ndosa.store', 'string', 'Website URL', 'general', 0),
('site_email', 'info@ndosa.store', 'string', 'Primary contact email', 'general', 1),
('support_email', 'support@ndosa.store', 'string', 'Support email', 'general', 1),
('contact_phone', '+255753098911', 'string', 'Main contact phone (Tanzania)', 'contact', 1),
('contact_phone_ke', '+254716522828', 'string', 'Kenya contact phone', 'contact', 1),
('whatsapp_number', '+255753098911', 'string', 'WhatsApp business number', 'contact', 1),
('business_address_tz', 'Dar es Salaam, Tanzania', 'string', 'Tanzania office address', 'contact', 1),
('business_address_ke', 'Nairobi, Kenya', 'string', 'Kenya office address', 'contact', 1),
('default_currency', 'KES', 'string', 'Default currency', 'ecommerce', 0),
('tax_rate_kes', '16', 'number', 'VAT rate for Kenya (16%)', 'ecommerce', 0),
('tax_rate_tzs', '18', 'number', 'VAT rate for Tanzania (18%)', 'ecommerce', 0),
('enable_stock_tracking', '1', 'boolean', 'Enable inventory tracking', 'ecommerce', 0),
('low_stock_threshold', '5', 'number', 'Low stock alert threshold', 'ecommerce', 0),
('enable_guest_checkout', '1', 'boolean', 'Allow guest checkout', 'ecommerce', 0),
('free_shipping_threshold_kes', '50000', 'number', 'Free shipping above KES', 'shipping', 0),
('free_shipping_threshold_tzs', '100000', 'number', 'Free shipping above TZS', 'shipping', 0),
('flutterwave_enabled', '1', 'boolean', 'Enable Flutterwave payments', 'payment', 0),
('mpesa_enabled', '1', 'boolean', 'Enable M-Pesa payments', 'payment', 0),
('firebase_enabled', '0', 'boolean', 'Enable Firebase authentication', 'integrations', 0),
('maintenance_mode', '0', 'boolean', 'Site maintenance mode', 'general', 0),
('allow_reviews', '1', 'boolean', 'Allow customer reviews', 'ecommerce', 0),
('auto_approve_reviews', '0', 'boolean', 'Auto-approve reviews', 'ecommerce', 0),
('email_order_confirmation', '1', 'boolean', 'Send order confirmation emails', 'email', 0),
('email_order_shipped', '1', 'boolean', 'Send shipping notification emails', 'email', 0)
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Default Canned Responses for Support
INSERT INTO `support_canned_responses` (`title`, `shortcut`, `message`, `category`, `is_active`) VALUES
('Welcome Greeting', '/hi', 'Hello! Thank you for contacting Ndosa Store. How can I help you today?', 'general', 1),
('Thank You', '/thanks', 'Thank you for reaching out! We appreciate your business with Ndosa Store.', 'general', 1),
('Product Information', '/product', 'I''d be happy to help you with product information. Which plotter model are you interested in learning about?', 'product', 1),
('Shipping Information', '/shipping', 'We offer FREE delivery and professional installation across Kenya, Tanzania, and Uganda. Standard delivery takes 2-3 business days.', 'shipping', 1),
('Warranty Information', '/warranty', 'All our JINKA plotters come with a comprehensive 12-month warranty covering parts and labor. Extended warranty options are available.', 'technical', 1),
('Technical Support', '/tech', 'I understand you''re experiencing a technical issue. Let me help you resolve this. Can you please describe what''s happening?', 'technical', 1),
('Payment Options', '/payment', 'We accept M-Pesa, bank transfers, credit/debit cards, and flexible payment plans for qualified businesses.', 'billing', 1),
('Closing Message', '/close', 'Is there anything else I can help you with today? We''re here 24/7 for your support.', 'general', 1)
ON DUPLICATE KEY UPDATE shortcut=shortcut;

-- Default FAQ Entries
INSERT INTO `support_faq` (`category`, `question`, `answer`, `display_order`, `is_active`) VALUES
('Products', 'What cutting plotters do you offer?', 'We specialize in JINKA cutting plotters ranging from 720mm to 1350mm cutting width. All models feature precision cutting, USB connectivity, and professional-grade performance.', 1, 1),
('Products', 'What materials can the plotter cut?', 'Our plotters can cut vinyl, heat transfer vinyl, reflective sheeting, sandblast stencils, cardstock, and various other materials with cutting force up to 600g.', 2, 1),
('Shipping', 'Do you offer delivery and installation?', 'Yes! We provide FREE delivery and professional installation across Kenya, Tanzania, and Uganda. Our certified technicians will set up your plotter and train your team.', 3, 1),
('Shipping', 'How long does delivery take?', 'Standard delivery takes 2-3 business days within major cities in Kenya and Tanzania. Remote locations may take 3-5 business days.', 4, 1),
('Payment', 'What payment methods do you accept?', 'We accept M-Pesa, bank transfers, credit/debit cards, and cash on delivery. Flexible payment plans are available for qualified businesses.', 5, 1),
('Technical', 'What software is compatible with JINKA plotters?', 'Our plotters work seamlessly with CorelDRAW, Adobe Illustrator, SignMaster, FlexiSign, and other industry-standard design software via USB or serial connection.', 6, 1),
('Warranty', 'What does the warranty cover?', 'Our 12-month warranty covers all mechanical parts, electronics, motors, and labor. This includes free repairs and genuine replacement parts during the warranty period.', 7, 1),
('Support', 'How can I get technical support?', 'We offer 24/7 technical support via phone (+255753098911), WhatsApp, email (support@ndosa.store), and live chat on our website.', 8, 1),
('Account', 'How do I create a customer account?', 'Click on "Sign Up" in the top menu, fill in your details, and verify your email address. You''ll get access to order tracking, wishlists, and exclusive deals.', 9, 1),
('Returns', 'What is your return policy?', 'We offer a 30-day return policy for unused products in original packaging. Defective items are replaced immediately under warranty terms.', 10, 1)
ON DUPLICATE KEY UPDATE question=question;

-- Performance Indexes
CREATE INDEX idx_products_active_featured ON products(is_active, is_featured);
CREATE INDEX idx_orders_customer_status ON orders(customer_id, status);
CREATE INDEX idx_orders_payment ON orders(payment_status, created_at);
CREATE INDEX idx_customer_email_verified ON customers(email_verified, is_active);
CREATE INDEX idx_support_tickets_status_priority ON support_tickets(status, priority, created_at);

COMMIT;

-- ================================================================
-- COMPLETION MESSAGE
-- ================================================================

SELECT 'Database ndosa_store created and initialized successfully!' AS 'Status';
SELECT 'Default admin credentials:' AS 'Important';
SELECT 'Username: admin' AS 'Admin Username';
SELECT 'Password: Admin@2025!' AS 'Admin Password';
SELECT 'Please change the admin password immediately after first login!' AS 'Security Warning';
SELECT CONCAT('Total Tables Created: ', COUNT(*)) AS 'Summary' 
FROM information_schema.tables 
WHERE table_schema = 'ndosa_store';
