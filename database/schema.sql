-- Event Management Platform Database Schema
-- Complete database initialization script

-- Create database
CREATE DATABASE IF NOT EXISTS event_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_platform;

-- Drop existing tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS rsvps;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('organizer', 'attendee', 'admin') NOT NULL DEFAULT 'attendee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATETIME NOT NULL,
    image VARCHAR(255),
    location VARCHAR(255) NOT NULL,
    max_attendees INT NOT NULL DEFAULT 50,
    organizer_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_organizer (organizer_id),
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RSVPs table
CREATE TABLE rsvps (
    event_id INT NOT NULL,
    attendee_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, attendee_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (attendee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_attendee (attendee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: Admin@123 (change this after first login!)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@eventplatform.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample organizer (optional)
-- Password: Organizer@123
INSERT INTO users (name, email, password, role) VALUES
('John Organizer', 'organizer@example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'organizer');

-- Insert sample attendee (optional)
-- Password: Attendee@123
INSERT INTO users (name, email, password, role) VALUES
('Jane Attendee', 'attendee@example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'attendee');

-- Sample approved event (optional)
INSERT INTO events (title, description, date, location, max_attendees, organizer_id, status) VALUES
('Tech Conference 2026', 'Annual technology conference featuring latest innovations', '2026-03-15 09:00:00', 'Convention Center, Main Hall', 200, 2, 'approved');

-- Sample pending event (optional)
INSERT INTO events (title, description, date, location, max_attendees, organizer_id, status) VALUES
('Music Festival', 'Summer music festival with live performances', '2026-06-20 18:00:00', 'City Park Amphitheater', 500, 2, 'pending');

-- Sample RSVP (optional)
INSERT INTO rsvps (event_id, attendee_id) VALUES (1, 3);

-- Display success message
SELECT 'Database schema created successfully!' AS message;
SELECT 'Default admin credentials: admin@eventplatform.local / Admin@123' AS credentials;
SELECT 'IMPORTANT: Change the admin password after first login!' AS warning;
