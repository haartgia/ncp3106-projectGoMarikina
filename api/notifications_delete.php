<?php
/**
 * Notifications: Delete One
 *
 * Endpoint: POST /api/notifications_delete.php
 * Purpose: Delete a single notification for the signed-in user.
 * Auth: Requires user session
 *
 * Form params:
 * - notification_id (int, required)
 *
 * Response:
 * - 200: { success: true, deleted: number }
 * - 4xx/5xx: { success: false, message }
 */

require_once __DIR__ . '/../includes/api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)(current_user()['id'] ?? 0);
$id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification id']);
    exit;
}

try {
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(['success' => true, 'deleted' => (int)$affected]);
        exit;
    }
} catch (Throwable $e) {
    // fall back to session
}

// Session fallback
if (!isset($_SESSION['user_notifications'])) $_SESSION['user_notifications'] = [];
if (!isset($_SESSION['user_notifications'][$userId])) {
    echo json_encode(['success' => true, 'deleted' => 0]);
    exit;
}

$before = count($_SESSION['user_notifications'][$userId]);
$_SESSION['user_notifications'][$userId] = array_values(array_filter(
    $_SESSION['user_notifications'][$userId],
    function ($n) use ($id) { return ((int)($n['id'] ?? 0)) !== $id; }
));
$after = count($_SESSION['user_notifications'][$userId]);
$deleted = $before - $after;

echo json_encode(['success' => true, 'deleted' => $deleted]);
exit;
