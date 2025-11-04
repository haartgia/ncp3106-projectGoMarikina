<?php
/**
 * Geocode Proxy (Nominatim + optional LocationIQ fallback)
 *
 * Endpoint: GET /api/geocode_proxy.php?action=search|reverse
 * Purpose: Server-side geocoding proxy with simple filesystem caching to avoid client rate limits.
 * Auth: Not required
 *
 * Notes:
 * - Does not include DB bootstrap on purpose (no DB dependency).
 * - Respects Nominatim usage policy with a proper User-Agent.
 */

// IMPORTANT: Do NOT include config/db.php here; this proxy must remain DB-free.

header('Content-Type: application/json; charset=utf-8');

// Basic configuration
$cacheDir = __DIR__ . '/.cache_geocode';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
// If we cannot create the cache directory, continue but warn in logs.
if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
    error_log('geocode_proxy: cache dir not writable: ' . $cacheDir);
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Quick diagnostics endpoint: ?debug=1
if (isset($_GET['debug']) && $_GET['debug']) {
    $host = 'nominatim.openstreetmap.org';
    $resolved = gethostbyname($host);
    $can_connect = false; $connect_err = null;
    $fp = @fsockopen($host, 443, $errno, $errstr, 5);
    if ($fp) { $can_connect = true; fclose($fp); } else { $connect_err = [$errno, $errstr]; }
    $curl_test = null;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://nominatim.openstreetmap.org/');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GoMarikina/1.0 (debug)');
        curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_errstr = curl_error($ch);
        $curl_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $curl_test = ['errno' => $curl_errno, 'errstr' => $curl_errstr, 'http_code' => $curl_http_code];
    }
    $info = [
        'ok' => true,
        'php_version' => phpversion(),
        'curl_enabled' => function_exists('curl_init'),
        'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
        'cache_dir' => $cacheDir,
        'cache_dir_exists' => is_dir($cacheDir),
        'cache_dir_writable' => is_writable($cacheDir),
        'nominatim_host' => $host,
        'nominatim_resolved_ip' => $resolved,
        'tcp_connect_443' => $can_connect,
        'tcp_connect_error' => $connect_err,
        'curl_test' => $curl_test,
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($info);
    exit;
}

// TTLs in seconds
$searchTTL = 60 * 60 * 6; // 6 hours for search results
$reverseTTL = 60 * 60 * 24; // 24 hours for reverse geocoding

// Optional LocationIQ fallback key: prefer environment variable, then optional config file
$locationiq_key = getenv('LOCATIONIQ_KEY') ?: null;
// Optional include: project config/geocode_keys.php can set $LOCATIONIQ_KEY variable
if (!$locationiq_key) {
    $possible = __DIR__ . '/../config/geocode_keys.php';
    if (file_exists($possible)) {
        try { include $possible; } catch (Throwable $e) {}
        if (isset($LOCATIONIQ_KEY) && $LOCATIONIQ_KEY) $locationiq_key = $LOCATIONIQ_KEY;
    }
}

// Helper: cache key -> filename
function cache_file_for_key($dir, $key) {
    $hash = preg_replace('/[^a-z0-9_\-]/i', '_', substr(md5($key),0,16));
    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $hash . '.json';
}

// Helper: fetch remote URL with timeout and proper UA/email (Nominatim policy)
function fetch_url($url) {
    // Prefer curl
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GoMarikina/1.0 (+mailto:noreply@gomarikina.local)');
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);
        if ($res === false || $errno) return [ 'ok' => false, 'code' => $code ?: 0, 'body' => null, 'error' => 'curl_error', 'error_msg' => $errstr ];
        return [ 'ok' => true, 'code' => $code, 'body' => $res ];
    }
    // Fallback to file_get_contents
    $opts = [ 'http' => [ 'method' => 'GET', 'timeout' => 8, 'header' => "User-Agent: GoMarikina/1.0 (mailto:noreply@gomarikina.local)\r\nAccept: application/json\r\n" ] ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    // Try to extract HTTP code from $http_response_header
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $h, $m)) { $code = (int)$m[1]; break; }
        }
    }
    if ($body === false) return [ 'ok' => false, 'code' => $code, 'body' => null, 'error' => 'fetch_error' ];
    return [ 'ok' => true, 'code' => $code, 'body' => $body ];
}

try {
    if (!$action) throw new Exception('missing action');

    if ($action === 'search') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_POST['q']) ? trim($_POST['q']) : '');
        if ($q === '') throw new Exception('missing q');
        // Construct key and cache filename
        $viewbox = isset($_GET['viewbox']) ? $_GET['viewbox'] : (isset($_POST['viewbox']) ? $_POST['viewbox'] : '');
        $countrycodes = isset($_GET['countrycodes']) ? $_GET['countrycodes'] : 'ph';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $email = 'noreply@gomarikina.local';
        $key = "search|q={$q}|viewbox={$viewbox}|cc={$countrycodes}|limit={$limit}";
        $cacheFile = cache_file_for_key($cacheDir, $key);
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $searchTTL)) {
            $body = file_get_contents($cacheFile);
            echo $body; exit;
        }

        // If LocationIQ key is configured, prefer it as the primary geocoder
        if ($locationiq_key) {
            $li_url = 'https://us1.locationiq.com/v1/search.php?key=' . urlencode($locationiq_key) . '&format=json&limit=' . $limit . '&q=' . urlencode($q);
            if ($viewbox) $li_url .= '&viewbox=' . urlencode($viewbox) . '&bounded=1';
            $liResp = fetch_url($li_url);
            if ($liResp['ok'] && ($liResp['code'] < 400)) {
                @file_put_contents($cacheFile, $liResp['body']);
                error_log('geocode_proxy: search used LocationIQ primary');
                echo $liResp['body']; exit;
            }
            // If LocationIQ primary fails, fall back to Nominatim
            error_log('geocode_proxy: LocationIQ search primary failed: ' . json_encode($liResp ?? null));
        }

        // Nominatim as fallback/primary
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&q=' . urlencode($q) . '&addressdetails=1&limit=' . $limit . '&countrycodes=' . urlencode($countrycodes) . '&email=' . urlencode($email);
        if ($viewbox) $url .= '&viewbox=' . urlencode($viewbox) . '&bounded=1';
        $resp = fetch_url($url);
        if (!$resp['ok'] || ($resp['code'] >= 400)) {
            error_log('geocode_proxy: nominatim search failed: ' . json_encode($resp));
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'geocode_fetch_failed', 'error' => $resp['error'] ?? null, 'http_code' => $resp['code'] ?? null]);
            exit;
        }
        @file_put_contents($cacheFile, $resp['body']);
        echo $resp['body']; exit;

    } elseif ($action === 'reverse') {
        $lat = isset($_GET['lat']) ? $_GET['lat'] : (isset($_POST['lat']) ? $_POST['lat'] : null);
        $lon = isset($_GET['lon']) ? $_GET['lon'] : (isset($_POST['lon']) ? $_POST['lon'] : null);
        if ($lat === null || $lon === null) throw new Exception('missing lat/lon');
        $key = "reverse|{$lat},{$lon}";
        $cacheFile = cache_file_for_key($cacheDir, $key);
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $reverseTTL)) {
            $body = file_get_contents($cacheFile);
            echo $body; exit;
        }
        $email = 'noreply@gomarikina.local';
        // Prefer LocationIQ reverse if configured
        if ($locationiq_key) {
            $li_url = 'https://us1.locationiq.com/v1/reverse.php?key=' . urlencode($locationiq_key) . '&format=json&lat=' . urlencode($lat) . '&lon=' . urlencode($lon);
            $liResp = fetch_url($li_url);
            if ($liResp['ok'] && ($liResp['code'] < 400)) {
                @file_put_contents($cacheFile, $liResp['body']);
                error_log('geocode_proxy: reverse used LocationIQ primary');
                echo $liResp['body']; exit;
            }
            error_log('geocode_proxy: LocationIQ reverse primary failed: ' . json_encode($liResp ?? null));
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' . urlencode($lat) . '&lon=' . urlencode($lon) . '&addressdetails=1&email=' . urlencode($email);
        $resp = fetch_url($url);
        if (!$resp['ok'] || ($resp['code'] >= 400)) {
            error_log('geocode_proxy: nominatim reverse failed: ' . json_encode($resp));
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'geocode_fetch_failed', 'error' => $resp['error'] ?? null, 'http_code' => $resp['code'] ?? null]);
            exit;
        }
        @file_put_contents($cacheFile, $resp['body']);
        echo $resp['body']; exit;

    } else {
        throw new Exception('invalid action');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
