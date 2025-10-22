<?php
// Create a new report with optional image upload
// Response: JSON { success: bool, message: string, report?: {...} }

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Basic validation helper
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

// Validate image (optional)
$imagePath = null;
$uploadDir = __DIR__ . '/../uploads/reports';
$publicBase = 'uploads/reports';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

if (!empty($_FILES['photo']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
    $file = $_FILES['photo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Photo upload failed';
    } else {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errors[] = 'Unsupported image type. Please upload JPG, PNG, or WEBP.';
        } else if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $errors[] = 'Image is too large (max 5MB).';
        } else {
            $ext = $allowed[$mime];
            $basename = bin2hex(random_bytes(8)) . '.' . $ext;
            $target = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                $errors[] = 'Failed to store uploaded image.';
            } else {
                $imagePath = $publicBase . '/' . $basename;
            }
        }
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode('\n', $errors)]);
    exit;
}

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

// Insert into DB
try {
    $hasUser = ($userId !== null);
    $hasImage = ($imagePath !== null);

    if ($hasUser && $hasImage) {
        $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
        $stmt->bind_param('isssss', $userId, $title, $category, $description, $location, $imagePath);
    } elseif ($hasUser && !$hasImage) {
        $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, "unresolved", NOW(), NOW())');
        $stmt->bind_param('issss', $userId, $title, $category, $description, $location);
    } elseif (!$hasUser && $hasImage) {
        $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, "unresolved", NOW(), NOW())');
        $stmt->bind_param('sssss', $title, $category, $description, $location, $imagePath);
    } else { // no user, no image
        $stmt = $conn->prepare('INSERT INTO reports (user_id, title, category, description, location, image_path, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, NULL, "unresolved", NOW(), NOW())');
        $stmt->bind_param('ssss', $title, $category, $description, $location);
    }
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    // Create a response-friendly structure
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
        'tags' => [],
    ];

    // Backward-compat: also push into session so existing UI shows it immediately
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
    echo json_encode(['success' => false, 'message' => 'Server error creating report']);
    exit;
}
