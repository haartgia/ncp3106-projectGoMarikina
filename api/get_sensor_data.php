<?php
/**
 * Sensors: Current Reading
 *
 * Endpoint: GET /api/get_sensor_data.php?barangay=NAME[&ip=OVERRIDE][&debug=1]
 * Purpose: Return the latest sensor reading for a barangay. Uses real device for
 *          Malanday (with optional IP override), otherwise returns deterministic
 *          dummy data for demo/testing. Persists samples periodically.
 * Auth: Not required
 *
 * Response:
 * - 200: { success: true, data: {...} } or { status: 'unavailable' } for offline
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

date_default_timezone_set('Asia/Manila'); // ensure PHP timestamps are PH time

require_once __DIR__ . '/../includes/api_bootstrap.php';

$default_ip = '172.20.10.3'; // ESP32 for Malanday

$barangay = $_GET['barangay'] ?? '';
$esp32_ip_override = $_GET['ip'] ?? null; // optional override for testing
$debug = isset($_GET['debug']);
// Optional mode switch: mode=db forces serving latest reading from database (for cloud-hosted site)
$mode = $_GET['mode'] ?? '';

$normalize = function(string $s) {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return mb_strtolower($s, 'UTF-8');
};

$brgyKey = $normalize($barangay);

// Only Malanday uses the real device. Everything else gets dummy data.
$device_ip = null;
if ($brgyKey === 'malanday') {
    $device_ip = $esp32_ip_override ?: $default_ip;
}

// Helper: Fetch the latest stored reading from DB
function fetchLatestFromDb($barangay) {
    try {
        $db = get_db_connection();
    $stmt = $db->prepare("SELECT id, barangay, device_ip, temperature, humidity, water_percent, flood_level, air_quality, gas_analog, gas_voltage, status, source, reading_timestamp, created_at FROM sensor_data WHERE barangay = ? ORDER BY reading_timestamp DESC, created_at DESC, id DESC LIMIT 1");
        $stmt->bind_param('s', $barangay);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $db->close();
        if (!$row) return null;
        $wp = isset($row['water_percent']) ? max(0, min(100, (int)$row['water_percent'])) : null;
        return [
            'barangay'      => $row['barangay'],
            'timestamp'     => $row['reading_timestamp'],
            'status'        => $row['status'] ?: 'online',
            'source'        => $row['source'] ?: 'esp32',
            'device_ip'     => $row['device_ip'] ?: null,
            'temperature'   => isset($row['temperature']) ? (float)$row['temperature'] : null,
            'humidity'      => isset($row['humidity']) ? (float)$row['humidity'] : null,
            'waterPercent'  => $wp,
            'floodLevel'    => $row['flood_level'] ?: 'Unknown',
            'airQuality'    => isset($row['air_quality']) ? (int)$row['air_quality'] : null,
            'gasAnalog'     => isset($row['gas_analog']) ? (int)$row['gas_analog'] : null,
            'gasVoltage'    => isset($row['gas_voltage']) ? (float)$row['gas_voltage'] : null,
        ];
    } catch (Throwable $e) {
        error_log('fetchLatestFromDb failed: ' . $e->getMessage());
        return null;
    }
}

// Helper: Save sensor data to database (only if 10 minutes have passed)
function saveSensorData($data) {
    try {
        $db = get_db_connection();
        
        $barangay = $data['barangay'] ?? '';
        
        // Check if we should save (only every 10 minutes)
        // Get the last saved timestamp for this barangay
    $throttleSeconds = 30; // make near real-time but avoid write storms
        $check_stmt = $db->prepare("
            SELECT reading_timestamp 
            FROM sensor_data 
            WHERE barangay = ? 
            ORDER BY reading_timestamp DESC 
            LIMIT 1
        ");
        $check_stmt->bind_param('s', $barangay);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_timestamp = strtotime($row['reading_timestamp']);
            $current_time = time();
            
            // Only save if enough time has passed
            if (($current_time - $last_timestamp) < 30) {
                $check_stmt->close();
                $db->close();
                return false; // Skip saving, not enough time has passed
            }
        }
        $check_stmt->close();
        
        // Proceed to save data
        $device_ip = $data['device_ip'] ?? null;
        $temperature = $data['temperature'] ?? null;
        $humidity = $data['humidity'] ?? null;
        $water_percent = $data['waterPercent'] ?? $data['waterLevel'] ?? 0;
        $flood_level = $data['floodLevel'] ?? 'No Flood';
        $air_quality = $data['airQuality'] ?? null;
        $gas_analog = $data['gasAnalog'] ?? null;
        $gas_voltage = $data['gasVoltage'] ?? null;
        $status = $data['status'] ?? 'online';
        $source = $data['source'] ?? 'esp32';
        $reading_timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        
        $stmt = $db->prepare("
            INSERT INTO sensor_data 
            (barangay, device_ip, temperature, humidity, water_percent, flood_level, 
             air_quality, gas_analog, gas_voltage, status, source, reading_timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            'ssddisisdsss',
            $barangay, $device_ip, $temperature, $humidity, $water_percent, 
            $flood_level, $air_quality, $gas_analog, $gas_voltage, $status, 
            $source, $reading_timestamp
        );
        
        $stmt->execute();
        $stmt->close();
        $db->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to save sensor data: " . $e->getMessage());
        return false;
    }
}

// Helper: deterministic pseudo-random (changes every 30s) for dummy data
function pseudo($key) {
    $n = sprintf('%u', crc32($key)); // unsigned
    return fmod((float)$n, 10000.0) / 10000.0; // 0..1
}
function dummyData($barangay) {
    $tick = floor(time() / 30); // change every 30s
    $base = $barangay . '|' . $tick;

    $temp = round(27 + pseudo($base.'t') * 6, 1);     // 27–33 °C
    $hum  = round(55 + pseudo($base.'h') * 30, 1);    // 55–85 %
    $wl   = (pseudo($base.'w') > 0.7) ? 100 : 0;      // 30% chance HIGH
    $gasA = (int) round(500 + pseudo($base.'g') * 800); // 500–1300
    $gasV = round(($gasA / 4095) * 3.3, 2);
    $aqi  = (int) round(50 + pseudo($base.'a') * 150);  // 50–200

    // Determine flood level based on water percentage
    $floodLevel = 'No Flood';
    if ($wl >= 100) {
        $floodLevel = 'Level 3 (Waist Deep)';
    } elseif ($wl >= 66) {
        $floodLevel = 'Level 2 (Knee Deep)';
    } elseif ($wl >= 33) {
        $floodLevel = 'Level 1 (Gutter Deep)';
    }

    return [
        'temperature' => $temp,
        'humidity'    => $hum,
        'waterPercent' => $wl,  // Changed from waterLevel to waterPercent
        'floodLevel'  => $floodLevel,  // Added flood level
        'airQuality'  => $aqi,
        'gasAnalog'   => $gasA,
        'gasVoltage'  => $gasV,
    ];
}

// If explicitly requested DB mode (cloud) or no device for this barangay, serve DB or dummy
if ($mode === 'db' || !$device_ip) {
    if ($mode === 'db') {
        $latest = fetchLatestFromDb($barangay);
        if ($latest) {
            if ($debug) {
                echo json_encode(['proxy_status' => 'ok', 'http_status' => 200, 'data' => $latest], JSON_PRETTY_PRINT);
                exit;
            }
            echo json_encode($latest);
            exit;
        }
        // If no DB data yet, fall through to dummy for non-Malanday or offline for Malanday
    }
    $data = dummyData($barangay);
    $data['barangay']  = $barangay;
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['status']    = 'online';
    $data['source']    = 'dummy';

    if ($debug) {
        echo json_encode([
            'proxy_status' => 'ok',
            'http_status'  => 200,
            'data'         => $data,
            'raw'          => json_encode($data),
        ], JSON_PRETTY_PRINT);
        exit;
    }
    echo json_encode($data);
    exit;
}

// Real device path (Malanday)
if (!filter_var($device_ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['error' => true, 'message' => 'Invalid IP', 'status' => 'offline']);
    exit;
}

$url = "http://{$device_ip}/api/data";

try {
    $retries = 2;
    $lastErr = null;

    for ($i = 0; $i < $retries; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Connection: close',
                'User-Agent: XAMPP-ESP32-Proxy'
            ],
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $code !== 200 || $response === false) {
            $lastErr = $errno ? "cURL error: $err" : "HTTP status $code";
            usleep(200000);
            continue;
        }

        $raw = trim($response);
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $lastErr = 'Invalid JSON from ESP32: ' . json_last_error_msg();
            usleep(200000);
            continue;
        }

        // success
        $data['barangay']  = $barangay;
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['status']    = 'online';
        $data['source']    = 'esp32';
        $data['device_ip'] = $device_ip;

        // Save to database (non-blocking)
        saveSensorData($data);

        if ($debug) {
            echo json_encode([
                'proxy_status' => 'ok',
                'http_status'  => $code,
                'data'         => $data,
                'raw'          => $raw
            ], JSON_PRETTY_PRINT);
            exit;
        }
        echo json_encode($data);
        return;
    }

    // all attempts failed for device -> return unavailable status (no dummy data for Malanday)
    $unavailable = [
        'barangay'  => $barangay,
        'timestamp' => date('Y-m-d H:i:s'),
        'status'    => 'offline',
        'source'    => 'esp32',
        'device_ip' => $device_ip,
        'error'     => true,
        'message'   => 'ESP32 device unavailable',
        'temperature' => null,
        'humidity' => null,
        'waterPercent' => null,
        'floodLevel' => 'Unknown',
        'airQuality' => null,
        'gasAnalog' => null,
        'gasVoltage' => null
    ];

    if ($debug) {
        echo json_encode([
            'proxy_status' => 'error',
            'message'      => $lastErr ?: 'Unknown error',
            'url'          => $url,
            'data'         => $unavailable
        ], JSON_PRETTY_PRINT);
        exit;
    }
    echo json_encode($unavailable);
} catch (Exception $e) {
    // Exception caught -> return unavailable status (no dummy data for Malanday)
    $unavailable = [
        'barangay'  => $barangay,
        'timestamp' => date('Y-m-d H:i:s'),
        'status'    => 'offline',
        'source'    => 'esp32',
        'device_ip' => $device_ip,
        'error'     => true,
        'message'   => 'ESP32 device unavailable: ' . $e->getMessage(),
        'temperature' => null,
        'humidity' => null,
        'waterPercent' => null,
        'floodLevel' => 'Unknown',
        'airQuality' => null,
        'gasAnalog' => null,
        'gasVoltage' => null
    ];

    if ($debug) {
        echo json_encode([
            'proxy_status' => 'error',
            'message'      => $e->getMessage(),
            'url'          => $url,
            'data'         => $unavailable
        ], JSON_PRETTY_PRINT);
        exit;
    }
    echo json_encode($unavailable);
}