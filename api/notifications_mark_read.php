<?php
// Mark all notifications as read for the signed-in user.
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

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
