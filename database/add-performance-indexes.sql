-- Database Performance Optimization
-- Add indexes to improve query performance
-- Note: Ignores errors if indexes already exist

USE jinka_plotter;

-- Products table indexes
ALTER TABLE `products` ADD INDEX `idx_slug` (`slug`);
ALTER TABLE `products` ADD INDEX `idx_sku` (`sku`);
ALTER TABLE `products` ADD INDEX `idx_category_active` (`category_id`, `is_active`);
ALTER TABLE `products` ADD INDEX `idx_featured_active` (`is_featured`, `is_active`);
ALTER TABLE `products` ADD INDEX `idx_price_range_kes` (`price_kes`, `is_active`);
ALTER TABLE `products` ADD INDEX `idx_stock` (`stock_quantity`, `is_active`);
ALTER TABLE `products` ADD INDEX `idx_created_date` (`created_at`);

-- Categories table indexes
ALTER TABLE `categories` ADD INDEX `idx_slug` (`slug`);
ALTER TABLE `categories` ADD INDEX `idx_parent_active` (`parent_id`, `is_active`);
ALTER TABLE `categories` ADD INDEX `idx_sort_order` (`sort_order`, `is_active`);

-- Admin users table indexes
ALTER TABLE `admin_users` ADD INDEX `idx_email` (`email`);
ALTER TABLE `admin_users` ADD INDEX `idx_active_role` (`is_active`, `role`);
ALTER TABLE `admin_users` ADD INDEX `idx_last_login` (`last_login`);

-- Show success message
SELECT 'Indexes added successfully!' AS Status;
