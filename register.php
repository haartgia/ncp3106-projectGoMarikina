<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$mobile     = trim($_POST['mobile'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');


if ($email === '' || $password === '' || $first_name === '' || $last_name === '' || $mobile === '') {
    $_SESSION['login_error'] = 'Please fill in all required fields.';
    header('Location: profile.php');
    exit;
}

// Server-side: enforce PH format +63XXXXXXXXXX (10 digits after +63) and no spaces
if (!preg_match('/^\+63\d{10}$/', $mobile)) {
    $_SESSION['login_error'] = 'Mobile must be +63 followed by 10 digits (no spaces).';
    header('Location: profile.php');
    exit;
}

// Server-side: validate email has domain and no spaces
if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
    $_SESSION['login_error'] = 'Please enter a valid email address (must include a domain, no spaces).';
    header('Location: profile.php');
    exit;
}

// Server-side: validate password: min 8, 1 uppercase, 1 number, 1 special, no spaces
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$/', $password)) {
    $_SESSION['login_error'] = 'Password must be 8+ characters, include an uppercase letter, a number, a special character, and no spaces.';
    header('Location: profile.php');
    exit;
}

// Server-side: confirm password must match
if ($confirm_password === '' || $confirm_password !== $password) {
    $_SESSION['login_error'] = 'Passwords do not match.';
    header('Location: profile.php');
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Provide a friendly, specific error. Run targeted checks to craft message.
    $dupeMsg = 'Account with that email or mobile already exists.';
    // Check email duplicate
    $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) { $dupeMsg = 'Email already exists.'; }
    $stmt2->close();
    // Check mobile duplicate
    $stmt3 = $conn->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
    $stmt3->bind_param("s", $mobile);
    $stmt3->execute();
    $stmt3->store_result();
    if ($stmt3->num_rows > 0) { $dupeMsg = 'Mobile number already exists.'; }
    $stmt3->close();
    $_SESSION['login_error'] = $dupeMsg;
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
    if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }
    header('Location: profile.php');
    exit;
} else {
    // More helpful error details when DEBUG is on
    $DBG = getenv('DEBUG') === '1' || (isset($_GET['debug']) && $_GET['debug'] === '1');
    $detail = $stmt->error ?: ($conn->error ?? '');
    $_SESSION['login_error'] = $DBG && $detail
        ? ('Error creating account: ' . $detail)
        : 'Error creating account. Please try again.';
    if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }
    header('Location: profile.php');
    exit;
}
?>
