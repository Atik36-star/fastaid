CREATE DATABASE IF NOT EXISTS fastaid CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fastaid;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('patient','volunteer','admin') DEFAULT 'patient',
  phone VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
);

CREATE TABLE volunteers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  certification_type VARCHAR(50),
  certificate_number VARCHAR(50),
  years_experience INT DEFAULT 0,
  verified_status ENUM('pending','approved','rejected') DEFAULT 'pending',
  is_available TINYINT(1) DEFAULT 1,
  latitude DECIMAL(10,8) DEFAULT NULL,
  longitude DECIMAL(11,8) DEFAULT NULL,
  dnd_start TIME DEFAULT '00:00',
  dnd_end TIME DEFAULT '00:00',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_verified (verified_status),
  INDEX idx_available (is_available),
  INDEX idx_coords (latitude, longitude)
);

CREATE TABLE requests (
  id VARCHAR(10) PRIMARY KEY,
  patient_id INT NOT NULL,
  emergency_type VARCHAR(50),
  description TEXT,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  address VARCHAR(255),
  conscious_breathing TINYINT(1) DEFAULT 1,
  status ENUM('requested','accepted','on_the_way','arrived','completed','cancelled') DEFAULT 'requested',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES users(id),
  INDEX idx_status (status),
  INDEX idx_patient (patient_id)
);

CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id VARCHAR(10) NOT NULL,
  volunteer_id INT NOT NULL,
  accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  arrived_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
  FOREIGN KEY (volunteer_id) REFERENCES volunteers(id) ON DELETE CASCADE
);

CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id VARCHAR(10) NOT NULL,
  patient_id INT NOT NULL,
  volunteer_id INT NOT NULL,
  stars TINYINT NOT NULL CHECK (stars BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_rating (request_id, patient_id),
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

CREATE TABLE debriefs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id VARCHAR(10) NOT NULL,
  volunteer_id INT NOT NULL,
  what_worked VARCHAR(500),
  what_was_hard VARCHAR(500),
  what_to_change VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_debrief (request_id, volunteer_id),
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200),
  message VARCHAR(500),
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id, is_read)
);
