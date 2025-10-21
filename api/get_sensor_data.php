<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

date_default_timezone_set('Asia/Manila'); // ensure PHP timestamps are PH time

$default_ip = '172.20.10.2'; // ESP32 for Malanday

$barangay = $_GET['barangay'] ?? '';
$esp32_ip_override = $_GET['ip'] ?? null; // optional override for testing
$debug = isset($_GET['debug']);

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

    return [
        'temperature' => $temp,
        'humidity'    => $hum,
        'waterLevel'  => $wl,
        'airQuality'  => $aqi,
        'gasAnalog'   => $gasA,
        'gasVoltage'  => $gasV,
    ];
}

// If no device for this barangay, return dummy immediately
if (!$device_ip) {
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

    // all attempts failed for device -> fall back to dummy to keep UI alive
    $fallback = dummyData($barangay);
    $fallback['barangay']  = $barangay;
    $fallback['timestamp'] = date('Y-m-d H:i:s');
    $fallback['status']    = 'degraded'; // indicate fallback
    $fallback['source']    = 'dummy-fallback';
    $fallback['device_ip'] = $device_ip;

    if ($debug) {
        echo json_encode([
            'proxy_status' => 'error',
            'message'      => $lastErr ?: 'Unknown error',
            'url'          => $url,
            'fallback'     => $fallback
        ], JSON_PRETTY_PRINT);
        exit;
    }
    echo json_encode($fallback);
} catch (Exception $e) {
    $fallback = dummyData($barangay);
    $fallback['barangay']  = $barangay;
    $fallback['timestamp'] = date('Y-m-d H:i:s');
    $fallback['status']    = 'degraded';
    $fallback['source']    = 'dummy-fallback';
    $fallback['device_ip'] = $device_ip;

    if ($debug) {
        echo json_encode([
            'proxy_status' => 'error',
            'message'      => $e->getMessage(),
            'url'          => $url,
            'fallback'     => $fallback
        ], JSON_PRETTY_PRINT);
        exit;
    }
    echo json_encode($fallback);
}