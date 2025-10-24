-- Add password reset columns to users table
-- This script adds the necessary columns for password reset functionality

ALTER TABLE `users` 
ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL,
ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL,
ADD COLUMN `email` VARCHAR(100) NULL DEFAULT NULL;

-- Add indexes for better performance
CREATE INDEX `idx_users_reset_token` ON `users` (`reset_token`);
CREATE INDEX `idx_users_email` ON `users` (`email`);

-- Update the test user to have an email (you can change this to your actual email)
UPDATE `users` SET `email` = 'admin@bagovet.com' WHERE `username` = 'test';
