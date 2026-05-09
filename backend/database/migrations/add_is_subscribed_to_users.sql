-- Add newsletter subscription flag to users table
ALTER TABLE users
ADD COLUMN isSubscribed TINYINT(1) NOT NULL DEFAULT 0 AFTER email;
