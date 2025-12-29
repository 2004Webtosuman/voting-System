CREATE DATABASE IF NOT EXISTS booking_system;
USE booking_system;

CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255),
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  role ENUM('customer', 'provider', 'admin') DEFAULT 'customer',
  status ENUM('active', 'suspended') DEFAULT 'active',
  kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  kyc_rejection_reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE providers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE NOT NULL,
  service_type ENUM('plumber', 'electrician') NOT NULL,
  bio TEXT,
  hourly_rate DECIMAL(10, 2),
  image_url VARCHAR(255),
  total_earnings DECIMAL(15, 2) DEFAULT 0,
  total_jobs INT DEFAULT 0,
  average_rating DECIMAL(3, 2) DEFAULT 0,
  years_experience INT,
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE kyc_documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  document_type ENUM('id', 'citizenship', 'certification') NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  provider_id INT NOT NULL,
  service_type VARCHAR(50),
  description TEXT,
  estimated_price DECIMAL(10, 2),
  actual_charge DECIMAL(10, 2),
  status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  service_date DATETIME,
  completed_date DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  type VARCHAR(50),
  title VARCHAR(100),
  message TEXT,
  booking_id INT,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

CREATE TABLE messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  booking_id INT,
  message TEXT,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  customer_id INT NOT NULL,
  provider_id INT NOT NULL,
  rating INT CHECK (rating >= 1 AND rating <= 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);
