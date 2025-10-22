<?php
// Disabled: BantayBaha endpoint is no longer used by the app.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode(['error'=>'gone','message'=>'marikina_bantaybaha disabled']);
exit;

// --- Original implementation retained below for reference ---
// api/marikina_bantaybaha.php
// Scrape BantayBaha Marikina page to provide station data in a stable JSON shape for the dashboard.
// Returns: { source:'bantaybaha', stations:[{ name, current, alert, alarm, critical, time }], updated }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Simple file cache (120s) to avoid hammering the source
$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/bantaybaha_marikina.json';
$TTL = 120; // seconds

function out_json($arr){ echo json_encode($arr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

function http_get($url){
    // Try cURL with proper verification first
    if (function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'GoMarikinaBot/1.0 (+https://gomarikina.local)'
        ]);
        $body = curl_exec($ch);
        if ($body !== false) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code < 400 && $body) return [$body, null];
        } else {
            $err = curl_error($ch);
            curl_close($ch);
            // Fall through to relaxed SSL below
        }
        // Retry with relaxed SSL for local dev environments lacking CA bundle
        $ch2 = curl_init($url);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'GoMarikinaBot/1.0 (+https://gomarikina.local)'
        ]);
        $body2 = curl_exec($ch2);
        if ($body2 !== false){
            $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            if ($code2 < 400 && $body2) return [$body2, null];
        } else {
            $err2 = curl_error($ch2);
            curl_close($ch2);
        }
    }

    // Fallback to file_get_contents
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: GoMarikinaBot/1.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ],
            'timeout' => 15
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $body3 = @file_get_contents($url, false, $ctx);
    if ($body3 !== false) return [$body3, null];
    return [null, 'fetch_failed'];
}

function today_iso_manila_with_time($timeStr){
    // Input like "11:10 PM today" -> produce ISO 8601 string in Asia/Manila for today
    $tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $tz);
    // Extract HH:MM AM/PM
    if (!preg_match('/(\d{1,2}:\d{2})\s*([AP]M)/i', $timeStr, $m)) return null;
    $hhmm = $m[1]; $ampm = strtoupper($m[2]);
    [$h,$min] = array_map('intval', explode(':', $hhmm));
    if ($ampm === 'PM' && $h < 12) $h += 12; if ($ampm === 'AM' && $h === 12) $h = 0;
    $dateStr = $now->format('Y-m-d');
    $dt = DateTime::createFromFormat('Y-m-d H:i', sprintf('%s %02d:%02d', $dateStr, $h, $min), $tz);
    if (!$dt) return null;
    return $dt->format('Y-m-d H:i:s');
}

function parse_stations_from_html($html){
    $stations = [];

    // Main river water level and thresholds
    // Allow some HTML between number and the word meter/meters
    if (preg_match('/River\s+Water\s+Level[\s\S]{0,500}?(\d+(?:\.\d+)?)\s*(?:<[^>]+>\s*)*meter[s]?/i', $html, $m)){
        $level = (float)$m[1];
    } else { $level = null; }

    $timeText = null;
    if (preg_match('/as\s+of\s+([0-9:\s]+[AP]M)(?:\s*today)?/i', $html, $m)){
        $timeText = $m[1];
    }
    $ts = $timeText ? today_iso_manila_with_time($timeText) : null;

    $alert = $alarm = $critical = null;
    if (preg_match('/1st\s*:\s*(\d+(?:\.\d+)?)\s*m/i', $html, $m1)) $alert   = (float)$m1[1];
    if (preg_match('/2nd\s*:\s*(\d+(?:\.\d+)?)\s*m/i', $html, $m2)) $alarm   = (float)$m2[1];
    if (preg_match('/3rd\s*:\s*(\d+(?:\.\d+)?)\s*m/i', $html, $m3)) $critical= (float)$m3[1];

    // Name for main gauge from page context; common label on site: "Sto Ñino (main)" or similar
    $mainName = 'Sto Ñino (main)';
    if (preg_match('/Main\s+River\s+Gauging\s+Station:\s*([^<]+)/i', $html, $m)){
        $nm = trim(html_entity_decode($m[1]));
        if ($nm) $mainName = $nm;
    }

    // Pre-create main station (may fill current later if not in the first section)
    $stations[] = [
        'name' => $mainName,
        'current' => $level,
        'alert' => $alert,
        'alarm' => $alarm,
        'critical' => $critical,
        'time' => $ts
    ];

    // Upstream table: lines like "Rodriguez | 27 | Normal"
    // Try to find the Upstream section then extract rows
    if (preg_match('/Upstream\s+River\s+Water\s+Activity[\s\S]*?(<table[\s\S]*?<\/table>)/i', $html, $mt)){
        $table = $mt[1];
        if (preg_match_all('/<tr[^>]*>\s*<td[^>]*>\s*([^<]+?)\s*<\/td>\s*<td[^>]*>\s*([0-9]+(?:\.[0-9]+)?)\s*<\/td>/i', $table, $mm, PREG_SET_ORDER)){
            foreach ($mm as $row){
                $name = trim(html_entity_decode($row[1]));
                $val = (float)$row[2];
                $stations[] = [ 'name'=>$name, 'current'=>$val, 'alert'=>null, 'alarm'=>null, 'critical'=>null, 'time'=>$ts ];
            }
        }
    } else {
        // Fallback: scrape text rows
        if (preg_match_all('/\n\s*([A-Za-zÑñ()\-\s]+)\s*\|\s*([0-9]+(?:\.[0-9]+)?)\s*\|/u', $html, $mm, PREG_SET_ORDER)){
            foreach ($mm as $row){
                $name = trim($row[1]); $val = (float)$row[2];
                if (!$name) continue;
                // Avoid duplicating the main station if it appears again
                if (mb_stripos($name, $mainName) !== false) continue;
                $stations[] = [ 'name'=>$name, 'current'=>$val, 'alert'=>null, 'alarm'=>null, 'critical'=>null, 'time'=>$ts ];
            }
        }
    }

    // If we still don't have main current, try to fill from table rows that match the main station name
    if ($stations[0]['current'] === null){
        foreach ($stations as $s){
            if (key_lower_ascii($s['name']) === key_lower_ascii($mainName) && $s['current'] !== null){
                $stations[0]['current'] = $s['current'];
                break;
            }
        }
    }

    // Deduplicate by name
    $seen = [];
    $uniq = [];
    foreach ($stations as $s){
        $key = key_lower_ascii($s['name']);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $uniq[] = $s;
    }

    return $uniq;
}

function normalize_station_name($s){
    $s = trim($s);
    // fold diacritics: ñ -> n
    $s = strtr($s, [
        'Ñ' => 'N', 'ñ' => 'n', 'ó' => 'o', 'Ó' => 'O', 'í' => 'i', 'Í' => 'I'
    ]);
    $s = preg_replace('/\(.*?\)/', '', $s); // remove (main) etc
    $s = preg_replace('/\bbridge\b/i', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return strtolower(trim($s));
}

function key_lower_ascii($s){
    $s = normalize_station_name($s);
    // additionally try iconv transliteration if available
    if (function_exists('iconv')){
        $conv = @iconv('UTF-8','ASCII//TRANSLIT',$s);
        if ($conv !== false) $s = $conv;
    }
    return strtolower($s);
}

function fetch_pagasa_fallback(){
    $url = 'https://pasig-marikina-tullahanffws.pagasa.dost.gov.ph/water/table.do';
    [$html, $err] = http_get($url);
    if ($err || !$html) return [];
    $norm = preg_replace('/\s+/', ' ', $html);
    $target = [
        'Montalban','San Mateo-1','Rodriguez','Nangka','Sto Nino','Tumana Bridge','Rosario Bridge'
    ];
    $out = [];
    foreach ($target as $name){
        $pattern = '/' . preg_quote($name, '/') . '[^\d\-]*([\d\.]+)\s*\(?\*?\)?[^\d\-]*([\d\.-]+)?[^\d\-]*([\d\.-]+)?[^\d\-]*([\d\.-]+)?/i';
        if (preg_match($pattern, $norm, $m)){
            $out[] = [
                'name' => $name,
                'current' => isset($m[1]) && $m[1] !== '-' ? (float)$m[1] : null,
                'alert' => isset($m[2]) && $m[2] !== '-' ? (float)$m[2] : null,
                'alarm' => isset($m[3]) && $m[3] !== '-' ? (float)$m[3] : null,
                'critical' => isset($m[4]) && $m[4] !== '-' ? (float)$m[4] : null,
                'time' => null
            ];
        } else {
            $out[] = [ 'name'=>$name, 'current'=>null, 'alert'=>null, 'alarm'=>null, 'critical'=>null, 'time'=>null ];
        }
    }
    return $out;
}

// Serve from cache if fresh
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $TTL)){
    $buf = file_get_contents($cacheFile);
    if ($buf) { echo $buf; exit; }
}

[$html, $err] = http_get('https://bantaybaha.com/marikina');
if ($err || !$html){
    // Try PAGASA fallback instead of erroring out
    $stations = fetch_pagasa_fallback();
    $result = [ 'source' => 'pagasa-fallback', 'updated' => date('c'), 'stations' => $stations ];
    echo json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}

$stations = parse_stations_from_html($html);
if (!$stations || !is_array($stations)){
    // Try to recover with PAGASA fallback
    $stations = fetch_pagasa_fallback();
}

// If still empty or values missing, merge with PAGASA fallback per station name
$hasValue = false;
foreach ($stations as $s){ if ($s['current'] !== null) { $hasValue = true; break; } }
if (!$hasValue){
    $fallback = fetch_pagasa_fallback();
    if ($fallback){
        // Index fallback by normalized name
        $idx = [];
        foreach ($fallback as $f){ $idx[ key_lower_ascii($f['name']) ] = $f; }
        foreach ($stations as &$s){
            $key = key_lower_ascii($s['name']);
            if ($s['current'] === null && isset($idx[$key])){
                $f = $idx[$key];
                $s['current'] = $f['current'];
                if ($s['alert'] === null) $s['alert'] = $f['alert'];
                if ($s['alarm'] === null) $s['alarm'] = $f['alarm'];
                if ($s['critical'] === null) $s['critical'] = $f['critical'];
            }
        }
        unset($s);
    }
}

$result = [
    'source' => 'bantaybaha',
    'updated' => date('c'),
    'stations' => $stations
];

if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
@file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

echo json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
