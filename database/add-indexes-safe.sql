-- Quick Database Index Setup
-- Run this in phpMyAdmin SQL tab
-- Safe to run multiple times (will skip existing indexes)

USE jinka_plotter;

-- Check and add products indexes
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_slug');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_slug already exists''', 'ALTER TABLE products ADD INDEX idx_slug (slug)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_sku');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_sku already exists''', 'ALTER TABLE products ADD INDEX idx_sku (sku)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_category_active');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_category_active already exists''', 'ALTER TABLE products ADD INDEX idx_category_active (category_id, is_active)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_featured_active');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_featured_active already exists''', 'ALTER TABLE products ADD INDEX idx_featured_active (is_featured, is_active)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_price_range_kes');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_price_range_kes already exists''', 'ALTER TABLE products ADD INDEX idx_price_range_kes (price_kes, is_active)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Check and add categories indexes
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'categories' AND index_name = 'idx_cat_slug');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_cat_slug already exists''', 'ALTER TABLE categories ADD INDEX idx_cat_slug (slug)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'categories' AND index_name = 'idx_parent_active');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_parent_active already exists''', 'ALTER TABLE categories ADD INDEX idx_parent_active (parent_id, is_active)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Check and add admin_users indexes
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'admin_users' AND index_name = 'idx_admin_email');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_admin_email already exists''', 'ALTER TABLE admin_users ADD INDEX idx_admin_email (email)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'admin_users' AND index_name = 'idx_active_role');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index idx_active_role already exists''', 'ALTER TABLE admin_users ADD INDEX idx_active_role (is_active, role)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Show final status
SELECT 'Performance indexes setup complete!' AS Status;
SELECT 
    table_name AS 'Table',
    COUNT(*) AS 'Total Indexes'
FROM information_schema.statistics
WHERE table_schema = DATABASE()
    AND table_name IN ('products', 'categories', 'admin_users')
GROUP BY table_name;
