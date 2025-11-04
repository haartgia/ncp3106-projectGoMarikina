<?php
/**
 * Reports: Update Status (admin)
 *
 * Endpoint: POST /api/reports_update_status.php
 * Purpose: Update a report's status and notify the owner.
 * Auth: Requires admin session
 *
 * Form params:
 * - report_id (int, required)
 * - status (enum: unresolved|in_progress|solved)
 *
 * Response:
 * - 200: { success: true, message }
 * - 4xx/5xx: { success: false, message }
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$status = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
$validStatuses = ['unresolved', 'in_progress', 'solved'];

if (!$reportId || !in_array($status, $validStatuses, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid report id or status']);
    exit;
}

try {
    // Fetch report owner and title for notifications
    $stmt0 = $conn->prepare('SELECT user_id, title FROM reports WHERE id = ?');
    $stmt0->bind_param('i', $reportId);
    $stmt0->execute();
    $res0 = $stmt0->get_result();
    $row0 = $res0->fetch_assoc();
    $stmt0->close();

    $stmt = $conn->prepare('UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $status, $reportId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        throw new Exception('Update failed');
    }

    // Notify the report owner if available
    try {
        $userId = (int)($row0['user_id'] ?? 0);
        if ($userId > 0) {
            $title = 'Report status updated';
            $meta = ($row0['title'] ?? 'Your report') . ' → ' . str_replace('_', ' ', $status);

            // DB-first notification, fallback to session
            $check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($check && $check->num_rows > 0) {
                $type = $status === 'solved' ? 'success' : ($status === 'in_progress' ? 'info' : 'warning');
                $stmtN = $conn->prepare('INSERT INTO notifications (user_id, title, meta, type, is_read, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())');
                $stmtN->bind_param('isss', $userId, $title, $meta, $type);
                $stmtN->execute();
                $stmtN->close();
            } else {
                if (!isset($_SESSION['user_notifications'])) $_SESSION['user_notifications'] = [];
                if (!isset($_SESSION['user_notifications'][$userId])) $_SESSION['user_notifications'][$userId] = [];
                $_SESSION['user_notifications'][$userId][] = [
                    'id' => time(),
                    'title' => $title,
                    'meta' => $meta,
                    'type' => ($status === 'solved' ? 'success' : ($status === 'in_progress' ? 'info' : 'warning')),
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
    } catch (Throwable $e) {
        // ignore notification errors
    }

    echo json_encode(['success' => true, 'message' => 'Status updated']);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error updating status']);
    exit;
}
