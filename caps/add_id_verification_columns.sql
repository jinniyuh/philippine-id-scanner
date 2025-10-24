-- Add ID verification columns to clients table
-- Run this SQL script to add ID verification support

ALTER TABLE `clients` 
ADD COLUMN `id_verified` TINYINT(1) DEFAULT 0 COMMENT 'Whether ID has been verified (0=no, 1=yes)',
ADD COLUMN `scanned_id_data` TEXT NULL COMMENT 'JSON data from scanned ID card',
ADD COLUMN `id_verification_date` TIMESTAMP NULL COMMENT 'Date when ID was verified';

-- Update existing clients to have default values
UPDATE `clients` SET `id_verified` = 0 WHERE `id_verified` IS NULL;
