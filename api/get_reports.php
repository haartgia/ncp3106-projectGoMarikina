<?php
/**
 * Reports: Map Feed
 *
 * Endpoint: GET /api/get_reports.php
 * Purpose: Return reports with coordinates for map consumption (popups/modals).
 * Auth: Not required
 *
 * Optional query params (viewport bounding box):
 * - minLat, maxLat, minLng, maxLng (numbers) or south/north/west/east
 *
 * Response:
 * - 200: { success: true, data: [ { id, title, category, summary, location, image, latitude, longitude, status, submitted_at } ] }
 * - 500: { success: false, message }
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';

// Attempt to enable gzip output if available to reduce payload size for JSON
if (!in_array('ob_gzhandler', ob_list_handlers())) {
    @ob_start('ob_gzhandler');
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Optional bounding box parameters to restrict returned reports to viewport
    // Support both minLat/maxLat/minLng/maxLng and north/south/east/west param names.
    $minLat = isset($_GET['minLat']) ? $_GET['minLat'] : (isset($_GET['south']) ? $_GET['south'] : null);
    $maxLat = isset($_GET['maxLat']) ? $_GET['maxLat'] : (isset($_GET['north']) ? $_GET['north'] : null);
    $minLng = isset($_GET['minLng']) ? $_GET['minLng'] : (isset($_GET['west']) ? $_GET['west'] : null);
    $maxLng = isset($_GET['maxLng']) ? $_GET['maxLng'] : (isset($_GET['east']) ? $_GET['east'] : null);

    $where = ['latitude IS NOT NULL', 'longitude IS NOT NULL'];

    $params = [];
    // Validate numeric inputs before including them in SQL
    if ($minLat !== null && is_numeric($minLat)) { $where[] = 'latitude >= ' . floatval($minLat); }
    if ($maxLat !== null && is_numeric($maxLat)) { $where[] = 'latitude <= ' . floatval($maxLat); }
    if ($minLng !== null && is_numeric($minLng)) { $where[] = 'longitude >= ' . floatval($minLng); }
    if ($maxLng !== null && is_numeric($maxLng)) { $where[] = 'longitude <= ' . floatval($maxLng); }

    // Return extra fields useful for map popups/modal so the client can show
    // a richer preview without additional round-trips.
    $sql = 'SELECT id, title, category, description, location, image_path, latitude, longitude, status, created_at FROM reports';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    // Limit returned rows to a reasonable number to avoid huge payloads
    $sql .= ' ORDER BY created_at DESC LIMIT 1000';

    $res = $conn->query($sql);
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'],
                'category' => $r['category'],
                'summary' => $r['description'],
                'location' => $r['location'],
                'image' => $r['image_path'],
                'latitude' => $r['latitude'],
                'longitude' => $r['longitude'],
                'status' => $r['status'],
                'submitted_at' => $r['created_at']
            ];
        }
    }

    // Suggest database indexes for better performance (not applied here):
    // ALTER TABLE reports ADD INDEX idx_latitude (latitude), ADD INDEX idx_longitude (longitude);

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
