<?php
// Update current user's profile fields (mobile, password)
// Response: JSON { success: bool, message: string, field?: string, value?: string }

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user = current_user();
$user_id = (int)($user['id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

$field = $_POST['field'] ?? '';
$value = trim((string)($_POST['value'] ?? ''));

if ($field === '' || $value === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing field or value']);
    exit;
}

try {
    if ($field === 'mobile') {
        // Basic format guard: allow "+", digits, spaces, hyphens. Keep reasonable length.
        $normalized = preg_replace('/[^+0-9]/', '', $value);
        if ($normalized === null) { $normalized = $value; }
        if (strlen($normalized) < 10) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please enter a valid mobile number']);
            exit;
        }

        $stmt = $conn->prepare('UPDATE users SET mobile = ? WHERE id = ?');
        $stmt->bind_param('si', $normalized, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) throw new Exception('Database error while updating mobile');

        echo json_encode(['success' => true, 'message' => 'Mobile number updated', 'field' => 'mobile', 'value' => $normalized]);
        exit;
    } elseif ($field === 'password') {
        if (strlen($value) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }
        $hash = password_hash($value, PASSWORD_DEFAULT);
        if ($hash === false) throw new Exception('Failed to hash password');

        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) throw new Exception('Database error while updating password');

        echo json_encode(['success' => true, 'message' => 'Password updated', 'field' => 'password']);
        exit;
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Unsupported field']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: '.$e->getMessage()]);
    exit;
}
