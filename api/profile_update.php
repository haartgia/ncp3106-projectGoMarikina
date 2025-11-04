<?php
// Update current user's profile fields (mobile, password)
// Response: JSON { success: bool, message: string, field?: string, value?: string }

declare(strict_types=1);

require_once __DIR__ . '/../includes/api_bootstrap.php';

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
$passwordConfirm = isset($_POST['password_confirm']) ? trim((string)$_POST['password_confirm']) : null;

if ($field === '' || $value === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing field or value']);
    exit;
}

try {
    if ($field === 'mobile') {
        // Enforce PH mobile format: +63 followed by 10 digits, no spaces
        if (!preg_match('/^\+63\d{10}$/', $value)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Mobile must be +63 followed by 10 digits.']);
            exit;
        }

        $stmt = $conn->prepare('UPDATE users SET mobile = ? WHERE id = ?');
        $stmt->bind_param('si', $value, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) throw new Exception('Database error while updating mobile');

        echo json_encode(['success' => true, 'message' => 'Mobile number updated', 'field' => 'mobile', 'value' => $value]);
        exit;
    } elseif ($field === 'password') {
        // Password policy: at least 8 chars, 1 uppercase, 1 number, 1 special, no spaces
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$/', $value)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password must be 8+ characters, include an uppercase letter, a number, a special character, and no spaces.']);
            exit;
        }
        if ($passwordConfirm === null || $passwordConfirm === '' || $passwordConfirm !== $value) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
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
