-- Smart Parking Slot Booking System Database Schema

CREATE DATABASE IF NOT EXISTS smart_parking;
USE smart_parking;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Parking zones table
CREATE TABLE parking_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    x_coordinate DECIMAL(10, 8) NOT NULL,
    y_coordinate DECIMAL(11, 8) NOT NULL,
    total_slots INT NOT NULL,
    hourly_rate DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Parking slots table
CREATE TABLE parking_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    zone_id INT NOT NULL,
    slot_number VARCHAR(10) NOT NULL,
    x_coordinate DECIMAL(10, 8) NOT NULL,
    y_coordinate DECIMAL(11, 8) NOT NULL,
    status ENUM('available', 'occupied', 'reserved', 'maintenance') DEFAULT 'available',
    slot_type ENUM('regular', 'disabled', 'electric') DEFAULT 'regular',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE CASCADE,
    UNIQUE KEY unique_zone_slot (zone_id, slot_number)
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    total_amount DECIMAL(10, 2),
    status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    qr_code VARCHAR(255),
    entry_time DATETIME NULL,
    exit_time DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE CASCADE
);

-- Parking history for AI prediction
CREATE TABLE parking_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    zone_id INT NOT NULL,
    date DATE NOT NULL,
    hour TINYINT NOT NULL,
    occupied_slots INT NOT NULL,
    total_slots INT NOT NULL,
    occupancy_rate DECIMAL(5, 2) NOT NULL,
    weather VARCHAR(50),
    is_weekend BOOLEAN DEFAULT FALSE,
    is_holiday BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE CASCADE,
    UNIQUE KEY unique_zone_date_hour (zone_id, date, hour)
);

-- QR codes table
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    qr_token VARCHAR(255) UNIQUE NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin', 'admin@smartparking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', '+1234567890', 'admin'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '+1234567891', 'user'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', '+1234567892', 'user');

INSERT INTO parking_zones (name, description, x_coordinate, y_coordinate, total_slots, hourly_rate) VALUES
('Zone A - Mall Entrance', 'Premium parking near mall entrance', 40.7128, -74.0060, 50, 5.00),
('Zone B - Office Complex', 'Business district parking', 40.7138, -74.0070, 75, 4.00),
('Zone C - Residential Area', 'Residential parking zone', 40.7118, -74.0050, 30, 3.00),
('Zone D - Shopping Center', 'Large shopping center parking', 40.7148, -74.0080, 100, 4.50);

-- Insert parking slots for each zone
INSERT INTO parking_slots (zone_id, slot_number, x_coordinate, y_coordinate, slot_type) VALUES
-- Zone A slots (50 slots)
(1, 'A01', 40.7128, -74.0060, 'regular'), (1, 'A02', 40.7129, -74.0060, 'regular'),
(1, 'A03', 40.7130, -74.0060, 'disabled'), (1, 'A04', 40.7131, -74.0060, 'regular'),
(1, 'A05', 40.7132, -74.0060, 'electric'), (1, 'A06', 40.7133, -74.0060, 'regular'),
-- Zone B slots (75 slots) - showing first few
(2, 'B01', 40.7138, -74.0070, 'regular'), (2, 'B02', 40.7139, -74.0070, 'regular'),
(2, 'B03', 40.7140, -74.0070, 'disabled'), (2, 'B04', 40.7141, -74.0070, 'electric'),
-- Zone C slots (30 slots) - showing first few
(3, 'C01', 40.7118, -74.0050, 'regular'), (3, 'C02', 40.7119, -74.0050, 'regular'),
-- Zone D slots (100 slots) - showing first few
(4, 'D01', 40.7148, -74.0080, 'regular'), (4, 'D02', 40.7149, -74.0080, 'disabled');

-- Insert sample parking history for AI training
INSERT INTO parking_history (zone_id, date, hour, occupied_slots, total_slots, occupancy_rate, weather, is_weekend, is_holiday) VALUES
(1, '2024-01-15', 8, 35, 50, 70.00, 'sunny', FALSE, FALSE),
(1, '2024-01-15', 9, 42, 50, 84.00, 'sunny', FALSE, FALSE),
(1, '2024-01-15', 10, 48, 50, 96.00, 'sunny', FALSE, FALSE),
(1, '2024-01-15', 11, 45, 50, 90.00, 'sunny', FALSE, FALSE),
(1, '2024-01-15', 12, 40, 50, 80.00, 'sunny', FALSE, FALSE),
(2, '2024-01-15', 8, 60, 75, 80.00, 'sunny', FALSE, FALSE),
(2, '2024-01-15', 9, 70, 75, 93.33, 'sunny', FALSE, FALSE),
(2, '2024-01-15', 10, 72, 75, 96.00, 'sunny', FALSE, FALSE);