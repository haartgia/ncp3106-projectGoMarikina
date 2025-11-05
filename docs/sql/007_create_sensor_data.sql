-- =====================================================
-- 007_create_sensor_data.sql
-- Create sensor_data table for IoT ESP32 devices
-- =====================================================
-- This table stores real-time sensor readings from ESP32 devices
-- deployed in different barangays (currently Malanday)
-- Data includes: temperature, humidity, flood level, air quality
-- Note: Data is saved every 10 minutes to prevent database bloat

CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Location information
    barangay VARCHAR(100) NOT NULL,
    device_ip VARCHAR(45) NULL COMMENT 'IP address of ESP32 device',
    
    -- Environmental sensors
    temperature DECIMAL(5,2) NULL COMMENT 'Temperature in Celsius',
    humidity DECIMAL(5,2) NULL COMMENT 'Relative humidity percentage',
    
    -- Flood detection (from float sensors)
    water_percent INT DEFAULT 0 COMMENT 'Water level percentage (0, 33, 66, 100)',
    flood_level VARCHAR(50) NULL COMMENT 'No Flood, Level 1 (Gutter Deep), Level 2 (Knee Deep), Level 3 (Waist Deep)',
    
    -- Air quality (from MQ135 sensor)
    air_quality INT NULL COMMENT 'Air quality index (0-500)',
    gas_analog INT NULL COMMENT 'Raw analog reading from MQ135 (0-4095)',
    gas_voltage DECIMAL(4,2) NULL COMMENT 'Voltage reading from gas sensor (0-3.3V)',
    
    -- Metadata
    status VARCHAR(20) DEFAULT 'online' COMMENT 'online, offline, degraded',
    source VARCHAR(20) DEFAULT 'esp32' COMMENT 'esp32, dummy, dummy-fallback',
    
    -- Timestamps
    reading_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When the reading was taken',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_barangay (barangay),
    INDEX idx_reading_timestamp (reading_timestamp),
    INDEX idx_barangay_timestamp (barangay, reading_timestamp),
    INDEX idx_flood_level (flood_level),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='IoT sensor data from ESP32 devices deployed in barangays';

-- =====================================================
-- Optional: Create view for latest sensor readings
-- =====================================================
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

-- =====================================================
-- Optional: Insert sample data for Malanday
-- =====================================================
-- Uncomment below to add initial test data
/*
INSERT INTO sensor_data 
(barangay, device_ip, temperature, humidity, water_percent, flood_level, 
 air_quality, gas_analog, gas_voltage, status, source, reading_timestamp)
VALUES 
('Malanday', '172.20.10.3', 28.5, 65.0, 0, 'No Flood', 120, 850, 0.68, 'online', 'esp32', NOW()),
('Malanday', '172.20.10.3', 29.0, 70.0, 33, 'Level 1 (Gutter Deep)', 135, 920, 0.74, 'online', 'esp32', NOW() - INTERVAL 5 MINUTE),
('Malanday', '172.20.10.3', 28.8, 68.0, 0, 'No Flood', 125, 880, 0.71, 'online', 'esp32', NOW() - INTERVAL 10 MINUTE);
*/
