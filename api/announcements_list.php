<?php
/**
 * Announcements: List
 *
 * Endpoint: GET /api/announcements_list.php
 * Purpose: Return recent announcements (DB-first with session fallback).
 * Auth: Not required
 *
 * Query params:
 * - limit (int, optional; default 200, max 200)
 *
 * Response:
 * - 200: { success: true, data: [ { id, title, body, image, created_at } ] }
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 200;
    $check = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare('SELECT id, title, body, image_path, created_at FROM announcements ORDER BY created_at DESC LIMIT ?');
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'] ?? '',
                'body' => $r['body'] ?? '',
                'image' => $r['image_path'] ?? null,
                'created_at' => $r['created_at'] ?? null,
            ];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }
} catch (Throwable $e) {
    // fall back to session below
}

$rows = $_SESSION['announcements'] ?? [];
// Normalize to API shape
$rows = array_map(function($a){
    return [
        'id' => (int)($a['id'] ?? 0),
        'title' => (string)($a['title'] ?? ''),
        'body' => (string)($a['body'] ?? ''),
        'image' => $a['image'] ?? null,
        'created_at' => $a['created_at'] ?? null,
    ];
}, $rows);

echo json_encode(['success' => true, 'data' => array_values(array_reverse($rows))]);
