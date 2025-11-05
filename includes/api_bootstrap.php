<?php
/**
 * API Bootstrap (JSON endpoints)
 *
 * Sets the JSON content-type header and includes session/auth + DB.
 * Provides small helper functions for consistent JSON responses.
 */

header('Content-Type: application/json');

// Load .env if present (local dev convenience) before reading any env vars
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
    if (function_exists('load_app_env')) {
        load_app_env();
    }
}

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Optional debug mode: surface PHP errors as JSON instead of blank 500s
// Allow DEBUG via env or ad-hoc toggle (e.g., ?debug=1 during troubleshooting)
$__DEBUG = getenv('DEBUG') ?: '0';
if ($__DEBUG !== '1') {
    $q = $_GET['debug'] ?? $_SERVER['HTTP_X_DEBUG'] ?? '0';
    if ($q === '1') { $__DEBUG = '1'; }
}
if ($__DEBUG === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    set_exception_handler(function ($e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error',
            'error' => $e->getMessage(),
        ]);
        exit;
    });
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): void {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('json_error')) {
    function json_error(string $message, int $status = 400, array $extra = []): void {
        json_response(['success' => false, 'message' => $message] + $extra, $status);
    }
}

if (!function_exists('json_ok')) {
    function json_ok(array $data = []): void {
        json_response(['success' => true] + $data, 200);
    }
}
