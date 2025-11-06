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
$air_quality_raw = $data['airQuality'] ?? $data['air_quality'] ?? null;
$gas_analog = $data['gasAnalog'] ?? $data['gas_analog'] ?? null;
$gas_voltage = $data['gasVoltage'] ?? $data['gas_voltage'] ?? null;
$device_ip = $data['device_ip'] ?? $data['deviceIp'] ?? null;
$status = $data['status'] ?? 'online';
$source = $data['source'] ?? 'esp32';
$reading_timestamp = $data['timestamp'] ?? $data['reading_timestamp'] ?? date('Y-m-d H:i:s');

// Normalize/massage incoming values for compatibility
// 1) Map airQuality string (Good/Moderate/Bad) to numeric bands if needed
$mapAirQuality = function ($v) {
    if ($v === null) return null;
    if (is_numeric($v)) return (int)$v;
    $s = strtolower(trim((string)$v));
    switch ($s) {
        case 'good':
            return 50;   // representative AQI band center
        case 'moderate':
            return 100;  // representative AQI band center
        case 'bad':
        case 'unhealthy':
            return 200;  // representative AQI band center
        default:
            return null; // unknown label -> store null
    }
};
$air_quality = $mapAirQuality($air_quality_raw);

// 2) Derive waterPercent from floodLevel if not provided
if (empty($water_percent) && !empty($flood_level)) {
    $lvl = strtolower($flood_level);
    if (strpos($lvl, 'level 3') !== false || strpos($lvl, 'full') !== false || strpos($lvl, 'waist') !== false) {
        $water_percent = 100;
    } elseif (strpos($lvl, 'level 2') !== false || strpos($lvl, 'knee') !== false) {
        $water_percent = 66;
    } elseif (strpos($lvl, 'level 1') !== false || strpos($lvl, 'gutter') !== false || strpos($lvl, 'low') !== false) {
        $water_percent = 33;
    } else {
        $water_percent = 0;
    }
}

// 3) Type safety casts
$temperature = is_numeric($temperature) ? (float)$temperature : null;
$humidity    = is_numeric($humidity) ? (float)$humidity : null;
$water_percent = is_numeric($water_percent) ? (int)$water_percent : 0;
$gas_analog  = is_numeric($gas_analog) ? (int)$gas_analog : null;
$gas_voltage = is_numeric($gas_voltage) ? (float)$gas_voltage : null;

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
        'ssddisiidsss',
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
