<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$redirect = $_POST['redirect'] ?? 'profile.php';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    header('Location: ' . $redirect);
    exit;
}

//  Admin login
if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
    $_SESSION['user'] = [
        'id' => 0,
        'email' => $email,
        'name' => 'Administrator',
        'role' => 'admin',
    ];
    unset($_SESSION['login_error']);
    header('Location: admin.php');
    exit;
}

//  Regular user login
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, mobile FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => 'user',
        ];
        unset($_SESSION['login_error']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $_SESSION['login_error'] = 'Invalid password.';
    }
} else {
    $_SESSION['login_error'] = 'Email not found.';
}

$stmt->close();
$conn->close();

header('Location: ' . $redirect);
exit;
