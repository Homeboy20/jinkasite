-- ============================================
-- Product Relationships Table
-- For managing related products, upsells, cross-sells
-- ============================================

CREATE TABLE IF NOT EXISTS `product_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'Main product',
  `related_product_id` int(11) NOT NULL COMMENT 'Related/upsell product',
  `relationship_type` enum('related','upsell','cross_sell','accessory','bundle') DEFAULT 'related' COMMENT 'Type of relationship',
  `display_order` int(11) DEFAULT 0 COMMENT 'Order for display',
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

-- ============================================
-- Sample Data (Optional)
-- ============================================

-- Example: If you have products with IDs 1, 2, 3
-- INSERT INTO `product_relationships` (`product_id`, `related_product_id`, `relationship_type`, `display_order`) VALUES
-- (1, 2, 'related', 1),
-- (1, 3, 'upsell', 1),
-- (2, 1, 'related', 1),
-- (2, 3, 'cross_sell', 1);

-- ============================================
-- Indexes for Performance
-- ============================================

CREATE INDEX `idx_active_relationships` ON `product_relationships` (`product_id`, `is_active`, `relationship_type`);
CREATE INDEX `idx_display` ON `product_relationships` (`product_id`, `display_order`);
