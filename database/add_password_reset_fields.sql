-- SQL Migration for Password Reset Feature
-- Add reset_token and reset_expiry fields to clients table
-- Run this on your Hostinger MySQL database

-- Add reset_token column (stores the unique reset token)
ALTER TABLE `clients` 
ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Password reset token (64 char hex)' AFTER `password`;

-- Add reset_expiry column (stores when the token expires)
ALTER TABLE `clients` 
ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL COMMENT 'Token expiration timestamp' AFTER `reset_token`;

-- Add index on reset_token for faster lookups
ALTER TABLE `clients` 
ADD INDEX `idx_reset_token` (`reset_token`);

-- Optional: Add email column if it doesn't exist
-- Uncomment the following lines if your clients table doesn't have an email column
-- ALTER TABLE `clients` 
-- ADD COLUMN `email` VARCHAR(255) NOT NULL COMMENT 'Client email address' AFTER `contact_number`;
-- 
-- ALTER TABLE `clients` 
-- ADD UNIQUE INDEX `idx_email` (`email`);

-- Verify the changes
DESCRIBE `clients`;

