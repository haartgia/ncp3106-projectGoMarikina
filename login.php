<?php
require __DIR__ . '/config/auth.php';

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

if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
    $_SESSION['user'] = [
        'email' => $email,
        'name' => 'Administrator',
        'role' => 'admin',
    ];
    unset($_SESSION['login_error']);
    header('Location: admin.php');
    exit;
}

// Basic non-admin login demo
$_SESSION['user'] = [
    'email' => $email,
    'name' => strtok($email, '@') ?: 'User',
    'role' => 'user',
];
unset($_SESSION['login_error']);

header('Location: ' . $redirect);
exit;
