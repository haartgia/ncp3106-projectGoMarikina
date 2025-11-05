<?php
/**
 * API Bootstrap (JSON endpoints)
 *
 * Sets the JSON content-type header and includes session/auth + DB.
 * Provides small helper functions for consistent JSON responses.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

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
