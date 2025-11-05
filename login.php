<?php
require_once __DIR__ . '/includes/bootstrap.php';

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

// Optional legacy admin login (disabled by default in prod). Enable by setting env ALLOW_LEGACY_ADMIN=1
$__ALLOW_LEGACY_ADMIN = getenv('ALLOW_LEGACY_ADMIN') === '1';
if ($__ALLOW_LEGACY_ADMIN && $email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
    if (session_status() === PHP_SESSION_ACTIVE) { @session_regenerate_id(true); }
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
$hasRole = false;
try {
    $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    $hasRole = ($chk && $chk->num_rows > 0);
    if ($chk) { $chk->close(); }
} catch (Throwable $e) { $hasRole = false; }

$cols = 'id, first_name, last_name, email, password, mobile' . ($hasRole ? ', role' : '');
$stmt = $conn->prepare("SELECT $cols FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $role = ($hasRole && isset($user['role']) && $user['role']) ? $user['role'] : 'user';
        if (session_status() === PHP_SESSION_ACTIVE) { @session_regenerate_id(true); }
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => $role,
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
