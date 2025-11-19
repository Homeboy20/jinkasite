-- Add M-Pesa STK Push fields to orders table
-- Run this SQL to add support for M-Pesa payment tracking

ALTER TABLE `orders` 
ADD COLUMN `mpesa_checkout_request_id` VARCHAR(100) NULL AFTER `payment_notes`,
ADD COLUMN `mpesa_merchant_request_id` VARCHAR(100) NULL AFTER `mpesa_checkout_request_id`,
ADD COLUMN `mpesa_receipt_number` VARCHAR(50) NULL AFTER `mpesa_merchant_request_id`,
ADD INDEX `idx_mpesa_checkout` (`mpesa_checkout_request_id`),
ADD INDEX `idx_mpesa_merchant` (`mpesa_merchant_request_id`);
