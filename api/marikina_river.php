<?php
// Lightweight scraper to get station levels from PAGASA Water Level Table
// Returns JSON: { stations: [ { name, current, alert, alarm, critical, time } ] }

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$URL = 'https://pasig-marikina-tullahanffws.pagasa.dost.gov.ph/water/table.do';

function fetch_html($url){
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            'timeout' => 12,
        ]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data !== false) return $data;
    if (function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 12,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
        if ($out !== false) return $out;
    }
    return false;
}

$html = fetch_html($URL);
if ($html === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch upstream']);
    exit;
}

// Very naive parsing: look for a table row structure with station names and numbers.
// We'll focus on the seven Marikina-related stations we expect to see.
$targetStations = [
    'Montalban',
    'San Mateo-1',
    'Rodriguez',
    'Nangka',
    'Sto Nino',
    'Tumana Bridge',
    'Rosario Bridge',
];

$stations = [];

// Normalize whitespace to simplify regex
$norm = preg_replace('/\s+/', ' ', $html);

// Attempt to find rows that contain one of the station names, then capture the following numbers
foreach ($targetStations as $name) {
    $pattern = '/' . preg_quote($name, '/') . '[^\d\-]*([\d\.]+)\s*\(?\*?\)?[^\d\-]*([\d\.\-]+)?[^\d\-]*([\d\.\-]+)?[^\d\-]*([\d\.\-]+)?/i';
    if (preg_match($pattern, $norm, $m)) {
        $stations[] = [
            'name' => $name,
            'current' => isset($m[1]) && $m[1] !== '-' ? (float)$m[1] : null,
            'alert' => isset($m[2]) && $m[2] !== '-' ? (float)$m[2] : null,
            'alarm' => isset($m[3]) && $m[3] !== '-' ? (float)$m[3] : null,
            'critical' => isset($m[4]) && $m[4] !== '-' ? (float)$m[4] : null,
            'time' => null,
        ];
    } else {
        $stations[] = [ 'name'=>$name, 'current'=>null, 'alert'=>null, 'alarm'=>null, 'critical'=>null, 'time'=>null ];
    }
}

echo json_encode([ 'stations' => $stations, 'source' => $URL ]);
