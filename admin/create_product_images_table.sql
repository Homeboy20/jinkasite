-- SQL to create product_images table for managing multiple product images
-- Run this in phpMyAdmin or MySQL console

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_featured` (`is_featured`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add video_url column to products table for YouTube embeds
ALTER TABLE `products` 
ADD COLUMN `video_url` varchar(500) DEFAULT NULL AFTER `image`;

-- Create index for better performance
CREATE INDEX idx_product_images_featured ON product_images(product_id, is_featured);
