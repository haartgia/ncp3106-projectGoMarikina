-- =====================================================
-- COMPLETE DATABASE SETUP FOR GOMARIKINA
-- Run this on your hosted database: gomarikina
-- =====================================================

-- 1. Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    mobile VARCHAR(20) UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create reports table
CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('unresolved','in_progress','solved') NOT NULL DEFAULT 'unresolved',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  INDEX idx_status (status),
  INDEX idx_category (category),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  meta VARCHAR(200) NULL,
  type ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create announcements table
CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create sensor_data table for IoT ESP32 devices
CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay VARCHAR(100) NOT NULL,
    device_ip VARCHAR(45) NULL COMMENT 'IP address of ESP32 device',
    temperature DECIMAL(5,2) NULL COMMENT 'Temperature in Celsius',
    humidity DECIMAL(5,2) NULL COMMENT 'Relative humidity percentage',
    water_percent INT DEFAULT 0 COMMENT 'Water level percentage (0, 33, 66, 100)',
    flood_level VARCHAR(50) NULL COMMENT 'No Flood, Level 1 (Gutter Deep), Level 2 (Knee Deep), Level 3 (Waist Deep)',
    air_quality INT NULL COMMENT 'Air quality index (0-500)',
    gas_analog INT NULL COMMENT 'Raw analog reading from MQ135 (0-4095)',
    gas_voltage DECIMAL(4,2) NULL COMMENT 'Voltage reading from gas sensor (0-3.3V)',
    status VARCHAR(20) DEFAULT 'online' COMMENT 'online, offline, degraded',
    source VARCHAR(20) DEFAULT 'esp32' COMMENT 'esp32, dummy, dummy-fallback',
    reading_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When the reading was taken',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_barangay (barangay),
    INDEX idx_reading_timestamp (reading_timestamp),
    INDEX idx_barangay_timestamp (barangay, reading_timestamp),
    INDEX idx_flood_level (flood_level),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create view for latest sensor readings
CREATE OR REPLACE VIEW sensor_data_latest AS
SELECT 
    sd1.*
FROM sensor_data sd1
INNER JOIN (
    SELECT barangay, MAX(reading_timestamp) as max_timestamp
    FROM sensor_data
    GROUP BY barangay
) sd2 ON sd1.barangay = sd2.barangay 
    AND sd1.reading_timestamp = sd2.max_timestamp;

-- 8. Create archive tables
CREATE TABLE IF NOT EXISTS reports_archive (
  id INT UNSIGNED PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('unresolved','in_progress','solved') NOT NULL DEFAULT 'unresolved',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL,
  INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements_archive (
  id INT UNSIGNED PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL,
  INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users_archive (
  id INT UNSIGNED PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  email VARCHAR(100),
  password VARCHAR(255),
  mobile VARCHAR(20),
  created_at DATETIME NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Setup Complete!
-- =====================================================
