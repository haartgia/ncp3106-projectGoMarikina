<?php
/**
 * Save Sensor Data API
 * 
 * Saves IoT sensor readings to the database
 * Can be called by ESP32 device or proxy script
 * 
 * Usage: POST /api/save_sensor_data.php
 * Body: JSON with sensor readings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../includes/api_bootstrap.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Invalid JSON input']);
    exit;
}

// Extract and validate required fields
$barangay = $data['barangay'] ?? '';
$temperature = $data['temperature'] ?? null;
$humidity = $data['humidity'] ?? null;
$water_percent = $data['waterPercent'] ?? $data['water_percent'] ?? 0;
$flood_level = $data['floodLevel'] ?? $data['flood_level'] ?? 'No Flood';
$air_quality = $data['airQuality'] ?? $data['air_quality'] ?? null;
$gas_analog = $data['gasAnalog'] ?? $data['gas_analog'] ?? null;
$gas_voltage = $data['gasVoltage'] ?? $data['gas_voltage'] ?? null;
$device_ip = $data['device_ip'] ?? $data['deviceIp'] ?? null;
$status = $data['status'] ?? 'online';
$source = $data['source'] ?? 'esp32';
$reading_timestamp = $data['timestamp'] ?? $data['reading_timestamp'] ?? date('Y-m-d H:i:s');

// Validate barangay
if (empty($barangay)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Barangay is required']);
    exit;
}

try {
    $db = get_db_connection();
    
    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO sensor_data 
        (barangay, device_ip, temperature, humidity, water_percent, flood_level, 
         air_quality, gas_analog, gas_voltage, status, source, reading_timestamp)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        'ssddisisdsss',
        $barangay,
        $device_ip,
        $temperature,
        $humidity,
        $water_percent,
        $flood_level,
        $air_quality,
        $gas_analog,
        $gas_voltage,
        $status,
        $source,
        $reading_timestamp
    );
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        // Optional: Clean up old data (keep last 1000 records per barangay)
        $cleanup_stmt = $db->prepare("
            DELETE FROM sensor_data 
            WHERE barangay = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM sensor_data 
                    WHERE barangay = ? 
                    ORDER BY reading_timestamp DESC 
                    LIMIT 1000
                ) tmp
            )
        ");
        $cleanup_stmt->bind_param('ss', $barangay, $barangay);
        $cleanup_stmt->execute();
        $cleanup_stmt->close();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Sensor data saved successfully',
            'id' => $insert_id,
            'barangay' => $barangay,
            'timestamp' => $reading_timestamp
        ]);
    } else {
        throw new Exception('Failed to insert sensor data: ' . $stmt->error);
    }
    
    $stmt->close();
    $db->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
