<?php
/**
 * Reports: Create
 *
 * Endpoint: POST /api/reports_create.php
 * Purpose: Create a new report with optional photo and optional coordinates.
 * Auth: Optional (uses current session if present)
 *
 * Request (multipart/form-data):
 * - title (string, required)
 * - category (string, required)
 * - description (string, required)
 * - location (string, required)
 * - location_lat (float, optional)
 * - location_lng (float, optional)
 * - photo (file, optional; jpg/png/webp, up to 5MB)
 *
 * Response:
 * - 200: { success: true, message: string, report: {...} }
 * - 4xx/5xx: { success: false, message: string }
 */

require_once __DIR__ . '/../includes/api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Input helpers
function field($key) { return isset($_POST[$key]) ? trim((string)$_POST[$key]) : ''; }

$title = field('title');
$category = field('category');
$description = field('description');
$location = field('location');

$errors = [];
if ($category === '') $errors[] = 'Category is required';
if ($title === '') $errors[] = 'Title is required';
if ($description === '') $errors[] = 'Description is required';
if ($location === '') $errors[] = 'Location is required';

// Parse optional coordinates
$latitude = null;
$longitude = null;
if (isset($_POST['location_lat']) && $_POST['location_lat'] !== '') {
    if (is_numeric($_POST['location_lat'])) {
        $latitude = (float) $_POST['location_lat'];
    } else {
        $errors[] = 'Invalid latitude value';
    }
}
if (isset($_POST['location_lng']) && $_POST['location_lng'] !== '') {
    if (is_numeric($_POST['location_lng'])) {
        $longitude = (float) $_POST['location_lng'];
    } else {
        $errors[] = 'Invalid longitude value';
    }
}

// Optional photo upload using storage helper
require_once __DIR__ . '/../includes/storage_helper.php';

$imagePath = null;

// Only validate/process if user actually attempted an upload
if (isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    // If no file was provided, ignore (optional)
    if (isset($file['error']) && $file['error'] === UPLOAD_ERR_NO_FILE) {
        // no-op; photo is optional
    } else {
        // If tmp_name exists and is uploaded, process normally
        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $result = store_image($file, 'reports');
            if (!$result['success']) {
                $errors[] = $result['error'];
            } else {
                $imagePath = $result['path'];
            }
        } else {
            // If tmp_name not present but error not NO_FILE, treat as upload failure
            if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Photo upload failed';
            }
        }
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
    exit;
}

// Photo is optional. If no image was uploaded we'll store NULL in image_path.

$user = current_user();
$userId = null;
$reporterName = 'Resident';
$reporterEmail = null;

if ($user) {
    // Try common keys
    $userId = $user['id'] ?? null;
    $first = $user['first_name'] ?? ($user['firstName'] ?? '');
    $last = $user['last_name'] ?? ($user['lastName'] ?? '');
    $name = trim(($first . ' ' . $last));
    $reporterName = $name !== '' ? $name : ($user['name'] ?? 'Resident');
    $reporterEmail = $user['email'] ?? null;
}

// Insert into DB (supports schemas with/without latitude/longitude)
try {
    $hasUser = ($userId !== null);
    $hasImage = ($imagePath !== null);

    // Detect presence of latitude/longitude columns to avoid fatal errors
    $hasLatLng = false;
    try {
        $chkLat = $conn->query("SHOW COLUMNS FROM reports LIKE 'latitude'");
        $chkLng = $conn->query("SHOW COLUMNS FROM reports LIKE 'longitude'");
        $hasLatLng = ($chkLat && $chkLat->num_rows > 0) && ($chkLng && $chkLng->num_rows > 0);
        if ($chkLat) { $chkLat->close(); }
        if ($chkLng) { $chkLng->close(); }
    } catch (Throwable $e) {
    // If SHOW COLUMNS fails, assume columns are missing and continue without them
        $hasLatLng = false;
    }

    // Build and run the appropriate INSERT
    if ($hasLatLng) {
        if ($hasUser && $hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('isssssdd', $userId, $title, $category, $description, $location, $imagePath, $latitude, $longitude);
        } elseif ($hasUser && !$hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('issssdd', $userId, $title, $category, $description, $location, $latitude, $longitude);
        } elseif (!$hasUser && $hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('sssssdd', $title, $category, $description, $location, $imagePath, $latitude, $longitude);
        } else { // no user, no image
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, NULL, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('ssssdd', $title, $category, $description, $location, $latitude, $longitude);
        }
    } else {
    // Fallback path for schemas without latitude/longitude columns
        if ($hasUser && $hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('isssss', $userId, $title, $category, $description, $location, $imagePath);
        } elseif ($hasUser && !$hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('issss', $userId, $title, $category, $description, $location);
        } elseif (!$hasUser && $hasImage) {
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('sssss', $title, $category, $description, $location, $imagePath);
        } else { // no user, no image
            $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, NULL, "unresolved", NOW(), NOW())');
            if (!$stmt) { throw new Exception('SQL prepare failed: ' . $conn->error); }
            $stmt->bind_param('ssss', $title, $category, $description, $location);
        }
    }

    if (!$stmt->execute()) {
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        throw new Exception($err);
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    // Response-friendly structure
    $now = date('Y-m-d H:i:s');
    $report = [
        'id' => (int)$newId,
        'title' => $title,
        'category' => $category,
        'status' => 'unresolved',
        'reporter' => $reporterName,
        'location' => $location,
        'submitted_at' => $now,
        'summary' => $description,
        'image' => $imagePath,
        'latitude' => $latitude !== null ? $latitude : null,
        'longitude' => $longitude !== null ? $longitude : null,
        'tags' => [],
    ];

    // Backward-compat: push into session so existing UI shows it immediately
    if (!isset($_SESSION['reports']) || !is_array($_SESSION['reports'])) {
        $_SESSION['reports'] = [];
    }
    $_SESSION['reports'][] = $report;

    echo json_encode(['success' => true, 'message' => 'Report created successfully', 'report' => $report]);
  
    // Create a notification for the user (if logged in)
        try {
            if ($userId) {
                $check = $conn->query("SHOW TABLES LIKE 'notifications'");
                $notifTitle = 'Report submitted';
                $notifMeta = $title . ' · awaiting review';
                if ($check && $check->num_rows > 0) {
                    $type = 'success';
                    $stmtN = $conn->prepare('INSERT INTO notifications (user_id, title, meta, type, is_read, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())');
                    $stmtN->bind_param('isss', $userId, $notifTitle, $notifMeta, $type);
                    $stmtN->execute();
                    $stmtN->close();
                } else {
                    if (!isset($_SESSION['user_notifications'])) $_SESSION['user_notifications'] = [];
                    if (!isset($_SESSION['user_notifications'][$userId])) $_SESSION['user_notifications'][$userId] = [];
                    $_SESSION['user_notifications'][$userId][] = [
                        'id' => time(),
                        'title' => $notifTitle,
                        'meta' => $notifMeta,
                        'type' => 'success',
                        'is_read' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        } catch (Throwable $e) { /* ignore */ }
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error creating report', 'error' => $e->getMessage()]);
    exit;
}
