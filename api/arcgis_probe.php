<?php
// Disabled: ArcGIS probe endpoint is no longer used.
header('Content-Type: application/json');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode([ 'error' => 'gone', 'message' => 'arcgis_probe disabled' ]);
exit;

// --- Original implementation below (retained for reference) ---
// Probes an ArcGIS MapServer for candidate layers that may contain
// water-level station data (Pasig–Marikina–Tullahan River Basin / PAGASA).
// Usage: /api/arcgis_probe.php?service=https://portal.georisk.gov.ph/arcgis/rest/services/PAGASA/PAGASA/MapServer
// Returns JSON with candidate layers, field samples, and any errors.

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$service = isset($_GET['service']) && $_GET['service'] !== ''
  ? $_GET['service']
  : 'https://portal.georisk.gov.ph/arcgis/rest/services/PAGASA/PAGASA/MapServer';

function http_get($url, $timeout = 12){
  $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari';
  $headers = [
    'User-Agent: ' . $ua,
    'Accept: application/json,text/plain,*/*',
  ];
  // Prefer cURL
  if (function_exists('curl_init')){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT => $ua,
      CURLOPT_CONNECTTIMEOUT => $timeout,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_HTTPHEADER => $headers,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($out === false || $code >= 400) return [null, $code ?: 0, $err ?: 'HTTP error'];
    return [$out, $code, null];
  }
  // Fallback
  $ctx = stream_context_create(['http' => [
    'method' => 'GET',
    'header' => implode("\r\n", $headers),
    'timeout' => $timeout,
  ]]);
  $out = @file_get_contents($url, false, $ctx);
  if ($out === false) return [null, 0, 'stream failed'];
  return [$out, 200, null];
}

function jget($url){
  [$txt, $code, $err] = http_get($url);
  if ($txt === null) return [null, $code, $err];
  $json = json_decode($txt, true);
  if ($json === null) return [null, $code, 'invalid json'];
  return [$json, $code, null];
}

$result = [
  'service' => $service,
  'timestamp' => gmdate('c'),
  'candidates' => [],
  'errors' => [],
];

// Get layers list
[$root, $code, $err] = jget($service . '?f=pjson');
if (!$root){
  $result['errors'][] = ['step' => 'root', 'code' => $code, 'message' => $err];
  echo json_encode($result);
  exit;
}

$layers = $root['layers'] ?? [];
$tables = $root['tables'] ?? [];

// heuristic matchers
$nameNeedles = ['water', 'level', 'station', 'river', 'pagasa', 'wl'];
$fieldNeedles = ['station', 'name', 'wl', 'water', 'level', 'el', 'elev', 'alert', 'alarm', 'critical'];

$maxProbe = isset($_GET['max']) ? max(1, (int)$_GET['max']) : 30;

$toProbe = [];
foreach ($layers as $layer) {
  $toProbe[] = ['type' => 'layer', 'id' => $layer['id'], 'name' => $layer['name'] ?? ''];
}
foreach ($tables as $t) {
  $toProbe[] = ['type' => 'table', 'id' => $t['id'], 'name' => $t['name'] ?? ''];
}

$toProbe = array_slice($toProbe, 0, $maxProbe);

foreach ($toProbe as $item){
  $id = $item['id'];
  $name = strtolower($item['name']);
  $score = 0;
  foreach ($nameNeedles as $n){ if (strpos($name, $n) !== false) $score++; }

  // describe layer/table
  [$info, $icode, $ierr] = jget($service . '/' . $id . '?f=pjson');
  if (!$info){
    $result['errors'][] = ['step' => 'describe', 'id' => $id, 'code' => $icode, 'message' => $ierr];
    continue;
  }
  $fields = $info['fields'] ?? [];
  $fieldNames = array_map(fn($f) => strtolower($f['name'] ?? ''), $fields);
  $hitFields = [];
  foreach ($fieldNames as $fn){ foreach ($fieldNeedles as $k){ if (strpos($fn, $k) !== false) { $hitFields[] = $fn; break; } } }
  $score += count($hitFields);

  // sample query (if supported)
  $sample = null;
  if (!empty($info['supportsAdvancedQueries']) || ($info['type'] ?? '') === 'Feature Layer' || isset($info['fields'])){
    $url = $service . '/' . $id . '/query?where=1%3D1&outFields=*&resultRecordCount=1&f=pjson';
    [$js, $qcode, $qerr] = jget($url);
    if ($js && isset($js['features'][0]['attributes'])){
      $sample = $js['features'][0]['attributes'];
      // improve score if obvious fields exist in sample
      foreach ($fieldNeedles as $k){ foreach ($sample as $key => $val){ if (stripos($key, $k) !== false) { $score++; break 2; } } }
    } else if ($qerr) {
      $result['errors'][] = ['step' => 'query', 'id' => $id, 'code' => $qcode, 'message' => $qerr];
    }
  }

  $result['candidates'][] = [
    'id' => $id,
    'name' => $item['name'],
    'score' => $score,
    'hitFields' => $hitFields,
    'sample' => $sample,
    'describeUrl' => $service . '/' . $id . '?f=pjson',
    'queryUrl' => $service . '/' . $id . '/query?where=1%3D1&outFields=*&resultRecordCount=1&f=pjson',
  ];
}

// sort by score desc
usort($result['candidates'], function($a, $b){ return $b['score'] <=> $a['score']; });

echo json_encode($result, JSON_PRETTY_PRINT);
