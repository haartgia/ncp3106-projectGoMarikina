<?php
/**
 * Session keep-alive endpoint
 * GET/POST /api/ping.php
 * Touches the current PHP session so it doesn't expire while the user is idle on an open tab.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../includes/api_bootstrap.php';

// Ensure session is active (api_bootstrap loads config/auth.php which starts it)
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION['last_ping'] = time();
    // Flush session data promptly to update last access time on disk
    @session_write_close();
}

echo json_encode(['success' => true, 'ts' => date('c')]);
exit;
