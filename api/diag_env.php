<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';

// Only allow when debug is enabled to avoid leaking environment details
$debugOn = (getenv('DEBUG') === '1') || (($_GET['debug'] ?? '0') === '1') || (($_SERVER['HTTP_X_DEBUG'] ?? '0') === '1');
if (!$debugOn) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Diagnostics disabled. Append ?debug=1 to enable.']);
    exit;
}

$storage = getenv('STORAGE_METHOD') ?: 'local';
$cloudName = getenv('CLOUDINARY_CLOUD_NAME');
$apiKey = getenv('CLOUDINARY_API_KEY');
$apiSecret = getenv('CLOUDINARY_API_SECRET');
$debug = getenv('DEBUG') ?: '0';
$fallbackOnFail = getenv('UPLOAD_FALLBACK_ON_FAIL') ?: '0';

// Capabilities
$caps = [
    'curl' => function_exists('curl_init') && class_exists('CURLFile'),
    'fileinfo' => function_exists('finfo_open'),
    'openssl' => extension_loaded('openssl'),
];

// Temp/Upload dirs
$sysTmp = sys_get_temp_dir();
$uploadTmp = ini_get('upload_tmp_dir');

// DB check
$dbOk = false;
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $q = $conn->query('SELECT 1');
        if ($q) { $dbOk = true; $q->close(); }
    }
} catch (Throwable $e) { $dbOk = false; }


echo json_encode([
    'success' => true,
    'php_version' => PHP_VERSION,
    'storage_method' => $storage,
    'cloudinary_configured' => (bool)($cloudName && $apiKey && $apiSecret),
    'cloudinary' => [
        'cloud_name_set' => (bool)$cloudName,
        'api_key_set' => (bool)$apiKey,
        // Never expose secrets; show only lengths to help debug
        'masked' => [
            'cloud_name' => $cloudName ? (substr($cloudName, 0, 2) . '***' . substr($cloudName, -2)) : null,
            'api_key' => $apiKey ? (substr($apiKey, 0, 2) . '***' . substr($apiKey, -2)) : null,
        ],
    ],
    'capabilities' => $caps,
    'tmp_dirs' => [
        'sys_temp' => $sysTmp,
        'upload_tmp_dir' => $uploadTmp ?: null,
    ],
    'db_ok' => $dbOk,
    'debug' => $debug,
    'upload_fallback_on_fail' => $fallbackOnFail,
]);
?>
