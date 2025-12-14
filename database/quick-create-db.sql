-- Quick Database Setup Script for WAMP
-- Run this in phpMyAdmin or MySQL command line

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `jinka_plotter` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Switch to the database
USE `jinka_plotter`;

-- Create a message table to verify setup
CREATE TABLE IF NOT EXISTS `setup_check` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(255) DEFAULT 'Database setup successful!',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test record
INSERT INTO `setup_check` (`message`) VALUES ('✅ Database is ready!');

-- Show success message
SELECT 'Database "jinka_plotter" created successfully!' AS Status;
SELECT 'Now import the full schema from database/schema.sql' AS NextStep;
