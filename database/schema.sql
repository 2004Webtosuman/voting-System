CREATE DATABASE IF NOT EXISTS smart_parking;
USE smart_parking;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parking zones (logical grouping of slots)
CREATE TABLE IF NOT EXISTS parking_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    total_slots INT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL
);

-- Individual parking slots within a zone
CREATE TABLE IF NOT EXISTS parking_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    slot_number VARCHAR(20) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE CASCADE,
    UNIQUE(zone_id, slot_number)
);

-- Bookings made by users for slots
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NOT NULL,
    user_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('booked','active','completed','cancelled') DEFAULT 'booked',
    qr_code VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Graph edges between parking slots for distance calculations (Dijkstra)
CREATE TABLE IF NOT EXISTS zone_graph (
    id INT AUTO_INCREMENT PRIMARY KEY,
    node_a INT NOT NULL,
    node_b INT NOT NULL,
    distance FLOAT NOT NULL,
    FOREIGN KEY (node_a) REFERENCES parking_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (node_b) REFERENCES parking_slots(id) ON DELETE CASCADE
);