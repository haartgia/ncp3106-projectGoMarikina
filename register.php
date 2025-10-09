<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$mobile     = trim($_POST['mobile'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = trim($_POST['password'] ?? '');


if ($email === '' || $password === '' || $first_name === '' || $last_name === '' || $mobile === '') {
    $_SESSION['login_error'] = 'Please fill in all required fields.';
    header('Location: profile.php');
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['login_error'] = 'Email already exists.';
    header('Location: profile.php');
    exit;
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (first_name, last_name, mobile, email, password) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("sssss", $first_name, $last_name, $mobile, $email, $password_hash);

if ($stmt->execute()) {
    $new_user_id = $conn->insert_id;

    $_SESSION['user'] = [
        'id'    => $new_user_id,
        'email' => $email,
        'name'  => $first_name . ' ' . $last_name,
        'role'  => 'user'
    ];

    unset($_SESSION['login_error']);
    header('Location: profile.php');
    exit;
} else {
    $_SESSION['login_error'] = 'Error creating account. Please try again.';
    header('Location: profile.php');
    exit;
}
?>
