<?php
/**
 * Notifications: List
 *
 * Endpoint: GET /api/notifications_list.php
 * Purpose: Return notifications for the signed-in user.
 * Auth: Requires user session
 *
 * Query params:
 * - limit (int, optional; default 20, max 100)
 *
 * Response:
 * - 200: { success: true, data: [ { id, title, meta, type, is_read, created_at } ], unreadCount }
 * - 4xx/5xx: { success: false, message }
 */

require_once __DIR__ . '/../includes/api_bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user = current_user();
$userId = (int)($user['id'] ?? 0);
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;

$rows = [];
$unread = 0;

// Try to read from DB if a `notifications` table exists; fallback to session samples.
try {
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare('SELECT id, user_id, title, meta, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'] ?? '',
                'meta' => $r['meta'] ?? '',
                'type' => $r['type'] ?? 'info',
                'is_read' => (int)($r['is_read'] ?? 0),
                'created_at' => $r['created_at'] ?? null,
            ];
        }
        $stmt->close();

        // Count unread
        $stmt2 = $conn->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $row2 = $res2->fetch_assoc();
        $unread = (int)($row2['c'] ?? 0);
        $stmt2->close();

        echo json_encode(['success' => true, 'data' => $rows, 'unreadCount' => $unread]);
        exit;
    }
} catch (Throwable $e) {
    // fall through to session fallback
}

// Session fallback for demo
if (!isset($_SESSION['user_notifications'])) {
    $_SESSION['user_notifications'] = [];
}
if (!isset($_SESSION['user_notifications'][$userId])) {
    // seed a friendly welcome once per user session for demo
    $_SESSION['user_notifications'][$userId] = [
        ['id' => time(), 'title' => 'Welcome to Go Marikina', 'meta' => 'You will see updates here', 'type' => 'info', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s')],
    ];
}
$all = $_SESSION['user_notifications'][$userId];
// sort newest first based on created_at or id
usort($all, function($a,$b){ return strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''); });
$rows = array_slice($all, 0, $limit);
$unread = 0;
foreach ($all as $n) { if ((int)($n['is_read'] ?? 0) === 0) { $unread++; } }

echo json_encode(['success' => true, 'data' => $rows, 'unreadCount' => $unread]);
exit;
