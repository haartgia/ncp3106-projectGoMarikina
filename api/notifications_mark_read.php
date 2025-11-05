<?php
/**
 * Notifications: Mark All Read
 *
 * Endpoint: POST /api/notifications_mark_read.php
 * Purpose: Mark all notifications as read for the current user.
 * Auth: Requires user session
 *
 * Response:
 * - 200: { success: true }
 * - 4xx/5xx: { success: false, message }
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)(current_user()['id'] ?? 0);

try {
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare('UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
} catch (Throwable $e) {
    // fall back
}

// Session fallback
if (isset($_SESSION['user_notifications'][$userId])) {
    foreach ($_SESSION['user_notifications'][$userId] as &$n) {
        $n['is_read'] = 1;
    }
}

echo json_encode(['success' => true]);
