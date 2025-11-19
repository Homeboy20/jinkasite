-- Drop existing tables if they exist
DROP TABLE IF EXISTS `delivery_status_history`;
DROP TABLE IF EXISTS `deliveries`;

-- Create deliveries table for tracking shipments
CREATE TABLE `deliveries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `tracking_number` VARCHAR(50) NOT NULL,
    `delivery_status` ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned') DEFAULT 'pending',
    
    -- Driver Information
    `driver_name` VARCHAR(100) NULL,
    `driver_phone` VARCHAR(20) NULL,
    `vehicle_number` VARCHAR(20) NULL,
    
    -- Delivery Details
    `pickup_address` TEXT NULL,
    `delivery_address` TEXT NOT NULL,
    `estimated_delivery_date` DATE NULL,
    `actual_delivery_date` DATETIME NULL,
    
    -- Tracking Information
    `current_location` VARCHAR(255) NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `last_location_update` DATETIME NULL,
    
    -- Additional Information
    `delivery_notes` TEXT NULL,
    `delivery_instructions` TEXT NULL,
    `signature_image` VARCHAR(255) NULL,
    `proof_of_delivery` VARCHAR(255) NULL,
    `recipient_name` VARCHAR(100) NULL,
    
    -- Status tracking
    `assigned_at` DATETIME NULL,
    `picked_up_at` DATETIME NULL,
    `in_transit_at` DATETIME NULL,
    `out_for_delivery_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `failed_at` DATETIME NULL,
    `failure_reason` TEXT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `tracking_number` (`tracking_number`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_delivery_status` (`delivery_status`),
    KEY `idx_estimated_delivery` (`estimated_delivery_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create delivery status history table for audit trail
CREATE TABLE `delivery_status_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `delivery_id` INT(11) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `location` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `updated_by` VARCHAR(100) NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_delivery_id` (`delivery_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample deliveries for testing (assuming order IDs 1 and 2 exist)
INSERT INTO `deliveries` (`order_id`, `tracking_number`, `delivery_status`, `driver_name`, `driver_phone`, `vehicle_number`, `delivery_address`, `estimated_delivery_date`, `current_location`, `delivery_instructions`) VALUES
(1, 'JINKA-DEL-001', 'in_transit', 'John Kamau', '+254712345678', 'KBX 123A', '123 Main Street, Nairobi', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Mombasa Road, Nairobi', 'Call before delivery'),
(2, 'JINKA-DEL-002', 'out_for_delivery', 'Mary Wanjiru', '+254723456789', 'KCY 456B', '456 Oak Avenue, Mombasa', CURDATE(), 'Nyali, Mombasa', 'Leave at gate if not home')
ON DUPLICATE KEY UPDATE tracking_number=tracking_number;
