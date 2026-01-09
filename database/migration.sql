-- Migration script for existing database
-- Run this if you already have data and want to keep it

USE event_platform;

-- Add admin role to users table (if not exists)
ALTER TABLE users 
MODIFY COLUMN role ENUM('organizer', 'attendee', 'admin') NOT NULL DEFAULT 'attendee';

-- Add status column to events table (if not exists)
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER organizer_id;

-- Add timestamps to users table (if not exists)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add timestamps to events table (if not exists)
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes for performance (if not exists)
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_role (role);
ALTER TABLE events ADD INDEX IF NOT EXISTS idx_organizer (organizer_id);
ALTER TABLE events ADD INDEX IF NOT EXISTS idx_date (date);
ALTER TABLE events ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE rsvps ADD INDEX IF NOT EXISTS idx_attendee (attendee_id);

-- Set all existing events to 'approved' status (so they remain visible)
UPDATE events SET status = 'approved' WHERE status IS NULL OR status = '';

-- Create default admin user (only if doesn't exist)
INSERT IGNORE INTO users (name, email, password, role) 
VALUES ('Admin User', 'admin@eventplatform.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

SELECT 'Migration completed successfully!' AS message;
SELECT 'Default admin: admin@eventplatform.local / Admin@123' AS credentials;
