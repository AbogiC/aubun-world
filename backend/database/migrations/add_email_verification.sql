-- Add email verification and shipping address columns to users table
-- Run this migration to enable email verification and address management

-- Add email verification columns
ALTER TABLE users
ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER password,
ADD COLUMN verification_token VARCHAR(255) NULL DEFAULT NULL AFTER email_verified_at,
ADD COLUMN verification_token_expires_at TIMESTAMP NULL DEFAULT NULL AFTER verification_token;

-- Add shipping address column (stores JSON)
ALTER TABLE users
ADD COLUMN shipping_address JSON NULL DEFAULT NULL AFTER verification_token_expires_at;

-- Create index on verification token for faster lookups
ALTER TABLE users ADD INDEX idx_verification_token (verification_token);
