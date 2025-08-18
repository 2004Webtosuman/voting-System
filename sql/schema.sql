-- Schema for Smart Parking

DROP TABLE IF EXISTS auth_tokens;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS graph_edges;
DROP TABLE IF EXISTS entrances;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS zones;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS zone_hourly_occupancy;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL
);

CREATE TABLE auth_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE zones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL
);

CREATE TABLE slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  label VARCHAR(50) NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  x INT NULL,
  y INT NULL,
  FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

CREATE TABLE entrances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

-- Graph edges connect entrances and slots inside zone
-- from_node_type/to_node_type in ('entrance','slot')
CREATE TABLE graph_edges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  from_node_type ENUM('entrance','slot') NOT NULL,
  from_node_id INT NOT NULL,
  to_node_type ENUM('entrance','slot') NOT NULL,
  to_node_id INT NOT NULL,
  weight DOUBLE NOT NULL DEFAULT 1,
  FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  slot_id INT NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  status ENUM('booked','cancelled','completed') NOT NULL DEFAULT 'booked',
  exit_qr TEXT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE
);

-- Hourly occupancy aggregation table (for predictions)
CREATE TABLE zone_hourly_occupancy (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  hh DATETIME NOT NULL,
  occupied INT NOT NULL,
  UNIQUE KEY uk_zone_hour (zone_id, hh),
  FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

