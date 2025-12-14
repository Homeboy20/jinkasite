-- Migration: Add verification columns to customers table
-- Date: 2025-11-22
-- Description: Add phone_verified, email_verified, email_verification_token, and last_login columns

-- Add phone_verified column
ALTER TABLE customers 
ADD COLUMN phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Phone number verification status';

-- Add email_verified column  
ALTER TABLE customers 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 COMMENT 'Email verification status';

-- Add email_verification_token column
ALTER TABLE customers 
ADD COLUMN email_verification_token VARCHAR(64) NULL COMMENT 'Token for email verification';

-- Add last_login column
ALTER TABLE customers 
ADD COLUMN last_login TIMESTAMP NULL COMMENT 'Last login timestamp';

-- Add password column for regular login (in case it doesn't exist)
ALTER TABLE customers 
ADD COLUMN password VARCHAR(255) NULL COMMENT 'Hashed password for login';

-- Update existing customers to have phone_verified = 1 if they have a phone number
UPDATE customers 
SET phone_verified = 1, email_verified = 1
WHERE phone IS NOT NULL AND phone != '' AND is_active = 1;
