<?php
/**
 * Reports: Archive (admin-only)
 *
 * Endpoint: POST /api/report_archive.php
 * Purpose: Archive a report into reports_archive and delete from reports.
 * Auth: Requires admin session
 *
 * Params (POST):
 * - report_id (int, required)
 *
 * Response:
 * - 200: { success: true, message: string, id: int }
 * - 4xx/5xx: { success: false, message: string }
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
if ($reportId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid report id']);
    exit;
}

try {
    // Ensure both tables exist
    $checkReports = $conn->query("SHOW TABLES LIKE 'reports'");
    $checkArchive = $conn->query("SHOW TABLES LIKE 'reports_archive'");
    if (!$checkReports || $checkReports->num_rows === 0) {
        throw new Exception('reports table missing');
    }
    if (!$checkArchive || $checkArchive->num_rows === 0) {
        throw new Exception('reports_archive table missing');
    }
    if ($checkReports) $checkReports->close();
    if ($checkArchive) $checkArchive->close();

    // Determine if archive table supports latitude/longitude
    $archHasLat = false; $archHasLng = false;
    $repHasLat = false; $repHasLng = false;
    if ($res = $conn->query("SHOW COLUMNS FROM reports_archive LIKE 'latitude'")) { $archHasLat = $res->num_rows > 0; $res->close(); }
    if ($res = $conn->query("SHOW COLUMNS FROM reports_archive LIKE 'longitude'")) { $archHasLng = $res->num_rows > 0; $res->close(); }
    if ($res = $conn->query("SHOW COLUMNS FROM reports LIKE 'latitude'")) { $repHasLat = $res->num_rows > 0; $res->close(); }
    if ($res = $conn->query("SHOW COLUMNS FROM reports LIKE 'longitude'")) { $repHasLng = $res->num_rows > 0; $res->close(); }

    $useLatLng = $archHasLat && $archHasLng && $repHasLat && $repHasLng;

    // Archive first (copy row)
    if ($useLatLng) {
        $sql = 'INSERT INTO reports_archive (id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at, archived_at, archived_by) '
             . 'SELECT id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at, NOW(), ? '
             . 'FROM reports WHERE id = ?';
        $stmtA = $conn->prepare($sql);
        if (!$stmtA) { throw new Exception('SQL prepare failed: ' . $conn->error); }
        $archiver = (int)(current_user()['id'] ?? 0);
        $stmtA->bind_param('ii', $archiver, $reportId);
    } else {
        $sql = 'INSERT INTO reports_archive (id, user_id, title, category, description, location, image_path, status, created_at, updated_at, archived_at, archived_by) '
             . 'SELECT id, user_id, title, category, description, location, image_path, status, created_at, updated_at, NOW(), ? '
             . 'FROM reports WHERE id = ?';
        $stmtA = $conn->prepare($sql);
        if (!$stmtA) { throw new Exception('SQL prepare failed: ' . $conn->error); }
        $archiver = (int)(current_user()['id'] ?? 0);
        $stmtA->bind_param('ii', $archiver, $reportId);
    }

    if (!$stmtA->execute()) {
        $err = $stmtA->error ?: $conn->error;
        $stmtA->close();
        throw new Exception('Archive failed: ' . $err);
    }
    $stmtA->close();

    // Then delete original
    $stmtD = $conn->prepare('DELETE FROM reports WHERE id = ?');
    if (!$stmtD) { throw new Exception('SQL prepare failed: ' . $conn->error); }
    $stmtD->bind_param('i', $reportId);
    if (!$stmtD->execute()) {
        $err = $stmtD->error ?: $conn->error;
        $stmtD->close();
        throw new Exception('Delete failed: ' . $err);
    }
    $stmtD->close();

    echo json_encode(['success' => true, 'message' => 'Report archived', 'id' => $reportId]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
