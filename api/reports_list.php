<?php
/**
 * Reports: List
 *
 * Endpoint: GET /api/reports_list.php
 * Purpose: Return recent reports, optionally filtered by status/category or limited to current user (mine).
 * Auth: Optional (required when mine=1)
 *
 * Query params:
 * - status (string, optional)
 * - category (string, optional)
 * - mine (bool|1|true, optional)
 *
 * Response:
 * - 200: { success: true, data: [ { id, title, category, status, reporter, location, submitted_at, summary, image, tags } ] }
 * - 500: { success: false, message }
 */

require_once __DIR__ . '/../includes/api_bootstrap.php';

$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$mine = isset($_GET['mine']) ? (($_GET['mine'] === '1' || strtolower($_GET['mine']) === 'true') ? 1 : 0) : 0;

$userId = null;
if ($mine && is_logged_in()) {
    $user = current_user();
    $userId = $user['id'] ?? null;
}

$where = [];
$params = [];
$types = '';

if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; $types .= 's'; }
if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; $types .= 's'; }
if ($mine && $userId) { $where[] = 'user_id = ?'; $params[] = $userId; $types .= 'i'; }

$sql = 'SELECT id, user_id, title, category, description, location, image_path, status, created_at FROM reports';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id' => (int)$r['id'],
            'title' => $r['title'],
            'category' => $r['category'],
            'status' => $r['status'],
            'reporter' => 'Resident', // the UI currently shows a generic reporter; wire your users table if needed
            'location' => $r['location'],
            'submitted_at' => $r['created_at'],
            'summary' => $r['description'],
            'image' => $r['image_path'],
            'tags' => [],
        ];
    }

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error listing reports']);
    exit;
}
