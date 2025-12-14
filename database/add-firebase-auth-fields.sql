-- Add Firebase authentication fields to customers table

-- Add firebase_uid column for Firebase Authentication
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS firebase_uid VARCHAR(255) NULL AFTER email_verification_token,
ADD INDEX IF NOT EXISTS idx_firebase_uid (firebase_uid);

-- Add phone_verified column to track phone verification status
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_verified;

-- Add email_verified_at and phone_verified_at timestamps
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER email_verified,
ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL AFTER phone_verified;

-- Add firebase_token column for storing Firebase ID tokens (optional, for advanced features)
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS firebase_token TEXT NULL AFTER firebase_uid;

-- Add last_firebase_sync timestamp to track Firebase sync
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS last_firebase_sync DATETIME NULL AFTER last_login;

-- Update existing verified customers to have verification timestamps
UPDATE customers 
SET email_verified_at = created_at 
WHERE email_verified = 1 AND email_verified_at IS NULL;

-- Create index on phone for faster OTP login lookups
ALTER TABLE customers 
ADD INDEX IF NOT EXISTS idx_phone (phone);

-- Optional: Create firebase_auth_logs table for tracking Firebase authentication events
CREATE TABLE IF NOT EXISTS firebase_auth_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    firebase_uid VARCHAR(255) NULL,
    auth_method ENUM('email', 'phone', 'otp_login') NOT NULL,
    phone_number VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    status ENUM('success', 'failed', 'pending') NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_phone (phone_number),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add verification_attempts column to prevent spam
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS verification_attempts INT NOT NULL DEFAULT 0 AFTER phone_verified,
ADD COLUMN IF NOT EXISTS last_verification_attempt DATETIME NULL AFTER verification_attempts;

-- Comments for documentation
ALTER TABLE customers 
MODIFY COLUMN firebase_uid VARCHAR(255) NULL COMMENT 'Firebase Authentication UID',
MODIFY COLUMN phone_verified TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether phone number is verified via OTP',
MODIFY COLUMN firebase_token TEXT NULL COMMENT 'Firebase ID token for advanced features';

-- Display success message
SELECT 'Firebase authentication fields added successfully!' AS Status;
SELECT 'Run this file to add Firebase support to your customer authentication system.' AS Message;
