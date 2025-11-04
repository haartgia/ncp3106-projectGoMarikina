<?php
/**
 * Get Sensor Data History API
 * 
 * Retrieves historical sensor data from the database
 * 
 * Query parameters:
 * - barangay: Filter by barangay (default: all)
 * - limit: Number of records to return (default: 100, max: 1000)
 * - latest: Get only the latest reading per barangay (true/false)
 * - from: Start date/time (YYYY-MM-DD HH:MM:SS)
 * - to: End date/time (YYYY-MM-DD HH:MM:SS)
 * 
 * Usage: GET /api/get_sensor_history.php?barangay=Malanday&limit=50
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/api_bootstrap.php';

try {
    $db = get_db_connection();
    
    // Get query parameters
    $barangay = $_GET['barangay'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 100), 1000); // Max 1000 records
    $latest = isset($_GET['latest']) && $_GET['latest'] === 'true';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    
    // Build query
    if ($latest) {
        // Get only the latest reading per barangay
        $sql = "SELECT * FROM sensor_data_latest";
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($barangay)) {
            $conditions[] = "barangay = ?";
            $params[] = $barangay;
            $types .= 's';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY reading_timestamp DESC";
        
    } else {
        // Get historical data
        $sql = "SELECT * FROM sensor_data WHERE 1=1";
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($barangay)) {
            $sql .= " AND barangay = ?";
            $params[] = $barangay;
            $types .= 's';
        }
        
        if (!empty($from)) {
            $sql .= " AND reading_timestamp >= ?";
            $params[] = $from;
            $types .= 's';
        }
        
        if (!empty($to)) {
            $sql .= " AND reading_timestamp <= ?";
            $params[] = $to;
            $types .= 's';
        }
        
        $sql .= " ORDER BY reading_timestamp DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
    }
    
    // Prepare and execute
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Format data for frontend
        $record = [
            'id' => (int)$row['id'],
            'barangay' => $row['barangay'],
            'deviceIp' => $row['device_ip'],
            'temperature' => (float)$row['temperature'],
            'humidity' => (float)$row['humidity'],
            'waterPercent' => (int)$row['water_percent'],
            'floodLevel' => $row['flood_level'],
            'airQuality' => (int)$row['air_quality'],
            'gasAnalog' => (int)$row['gas_analog'],
            'gasVoltage' => (float)$row['gas_voltage'],
            'status' => $row['status'],
            'source' => $row['source'],
            'timestamp' => $row['reading_timestamp'],
            'createdAt' => $row['created_at']
        ];
        $data[] = $record;
    }
    
    $stmt->close();
    $db->close();
    
    // Return response
    echo json_encode([
        'success' => true,
        'count' => count($data),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
