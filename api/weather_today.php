<?php
/**
 * Weather: Today (Open‑Meteo proxy)
 *
 * Endpoint: GET /api/weather_today.php
 * Purpose: Lightweight weather snapshot for Marikina using Open‑Meteo (no API key).
 * Docs: https://open-meteo.com/en/docs
 * Auth: Not required
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, s-maxage=300');

$lat = 14.6507;  // Marikina City latitude
$lon = 121.1029; // Marikina City longitude
$tz  = 'Asia/Manila';

$endpoint = 'https://api.open-meteo.com/v1/forecast';
$params = http_build_query([
    'latitude' => $lat,
    'longitude' => $lon,
    'current' => 'temperature_2m,relative_humidity_2m,weather_code',
    'timezone' => $tz,
]);
$url = $endpoint . '?' . $params;

function fetch_json($url) {
    // Prefer cURL if available for better SSL and timeouts
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'GoMarikina/1.0 (+https://example.local)'
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code >= 400) return null;
        $json = json_decode($res, true);
        return is_array($json) ? $json : null;
    }

    // Fallback to file_get_contents
    $ctx = stream_context_create([
        'http' => [ 'timeout' => 8, 'ignore_errors' => true ],
        'https' => [ 'timeout' => 8 ]
    ]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) return null;
    $json = json_decode($res, true);
    return is_array($json) ? $json : null;
}

function weather_code_description($code) {
    // See: https://open-meteo.com/en/docs#weathervariables
    $map = [
        0 => 'Clear sky',
        1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Depositing rime fog',
        51 => 'Light drizzle', 53 => 'Moderate drizzle', 55 => 'Dense drizzle',
        56 => 'Light freezing drizzle', 57 => 'Dense freezing drizzle',
        61 => 'Slight rain', 63 => 'Moderate rain', 65 => 'Heavy rain',
        66 => 'Light freezing rain', 67 => 'Heavy freezing rain',
        71 => 'Slight snow fall', 73 => 'Moderate snow fall', 75 => 'Heavy snow fall',
        77 => 'Snow grains',
        80 => 'Slight rain showers', 81 => 'Moderate rain showers', 82 => 'Violent rain showers',
        85 => 'Slight snow showers', 86 => 'Heavy snow showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm with slight hail', 99 => 'Thunderstorm with heavy hail',
    ];
    return $map[$code] ?? '—';
}

try {
    $data = fetch_json($url);
    if (!$data || empty($data['current'])) {
        http_response_code(502);
        echo json_encode(['error' => 'Upstream unavailable']);
        exit;
    }

    $c = $data['current'];
    $payload = [
        'time' => $c['time'] ?? null,
        'temperature' => $c['temperature_2m'] ?? null,
        'humidity' => $c['relative_humidity_2m'] ?? null,
        'weather_code' => $c['weather_code'] ?? null,
        'description' => weather_code_description((int)($c['weather_code'] ?? -1)),
        'units' => [
            'temperature' => ($data['current_units']['temperature_2m'] ?? '°C'),
            'humidity' => ($data['current_units']['relative_humidity_2m'] ?? '%')
        ],
        'source' => 'open-meteo',
        'coords' => ['lat' => $data['latitude'] ?? null, 'lon' => $data['longitude'] ?? null],
    ];

    echo json_encode($payload);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unexpected error']);
}
