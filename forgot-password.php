<?php
require_once __DIR__ . '/includes/api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, email, first_name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // For security, we still show success even if email doesn't exist
    // This prevents email enumeration attacks
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.'
    ]);
    exit;
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Delete any existing tokens for this email
$deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$deleteStmt->bind_param("s", $email);
$deleteStmt->execute();
$deleteStmt->close();

// Insert new token
$insertStmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$insertStmt->bind_param("sss", $email, $token, $expiresAt);

if ($insertStmt->execute()) {
    $insertStmt->close();
    
    // In a real application, you would send an email here
    // For now, we'll store the reset link in the session for demonstration
    $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;
    
    $_SESSION['demo_reset_link'] = $resetLink;
    $_SESSION['demo_reset_email'] = $email;
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset link generated. Check the console for demo purposes.',
        'demo_link' => $resetLink // Remove this in production
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate reset link. Please try again.']);
}

$conn->close();
?>
