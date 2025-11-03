<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validate token on page load
if (empty($token)) {
    $error = 'Invalid reset link.';
} else {
    $stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetRecord = $result->fetch_assoc();
    $stmt->close();
    
    if (!$resetRecord) {
        $error = 'Invalid or expired reset link.';
    } elseif ($resetRecord['used']) {
        $error = 'This reset link has already been used.';
    } elseif (strtotime($resetRecord['expires_at']) < time()) {
        $error = 'This reset link has expired.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Verify token is still valid
        $stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $resetRecord = $result->fetch_assoc();
        $stmt->close();
        
        if (!$resetRecord || $resetRecord['used'] || strtotime($resetRecord['expires_at']) < time()) {
            $error = 'Invalid or expired reset link.';
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $resetRecord['email']);
            
            if ($updateStmt->execute()) {
                // Mark token as used
                $markUsedStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $markUsedStmt->bind_param("s", $token);
                $markUsedStmt->execute();
                $markUsedStmt->close();
                
                $success = 'Your password has been reset successfully. You can now log in with your new password.';
                
                // Clear demo session data
                unset($_SESSION['demo_reset_link']);
                unset($_SESSION['demo_reset_email']);
            } else {
                $error = 'Failed to update password. Please try again.';
            }
            $updateStmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password · GO! MARIKINA</title>
    <?php $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/'); ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        /* Page layout only; all UI inherits Auth theme classes */
        .auth-page-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* stack header above card */
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--surface);
        }
        .inline-spacer { height: 12px; }
    </style>
</head>
<body>
    <div class="auth-page-wrap">
        <header class="auth-header auth-header--centered" aria-label="Go Marikina branding">
            <img src="<?= $BASE ?>/uploads/blue_smallgomarikina.png" alt="GO! MARIKINA" class="auth-centered-logo" />
        </header>
        <section class="auth-content" aria-labelledby="reset-title">
            <div class="auth-card" role="form">
                <header class="auth-card-header">
                    <h2 id="reset-title" class="auth-card-title">Reset Password</h2>
                </header>

                <?php if ($error): ?>
                    <div class="inline-alert inline-alert--error" role="alert">
                        <span class="inline-alert__icon">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="inline-alert inline-alert--success" role="alert">
                        <span class="inline-alert__icon">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                    <p class="auth-footer-copy"><a class="auth-link" href="profile.php">← Back to Login</a></p>
                <?php elseif (!$error): ?>
                    <form class="auth-form" method="POST">
                        <label class="auth-field" for="password">
                            <span class="auth-field-label">New Password</span>
                            <div class="auth-field-input">
                                <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8" autocomplete="new-password" />
                                <button type="button" class="auth-field-toggle" aria-label="Show password" onclick="togglePassword('password')">
                                    <svg viewBox="0 0 28 18" width="20" height="14" aria-hidden="true">
                                        <rect x="1" y="1" rx="9" ry="9" width="26" height="16" fill="none" stroke="currentColor" stroke-width="2" />
                                        <circle cx="14" cy="9" r="3" fill="currentColor" />
                                    </svg>
                                </button>
                            </div>
                        </label>

                        <label class="auth-field" for="confirm_password">
                            <span class="auth-field-label">Confirm Password</span>
                            <div class="auth-field-input">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8" autocomplete="new-password" />
                                <button type="button" class="auth-field-toggle" aria-label="Show password" onclick="togglePassword('confirm_password')">
                                    <svg viewBox="0 0 28 18" width="20" height="14" aria-hidden="true">
                                        <rect x="1" y="1" rx="9" ry="9" width="26" height="16" fill="none" stroke="currentColor" stroke-width="2" />
                                        <circle cx="14" cy="9" r="3" fill="currentColor" />
                                    </svg>
                                </button>
                            </div>
                        </label>

                        <button type="submit" class="auth-submit">Reset Password</button>
                    </form>
                    <p class="auth-footer-copy"><a class="auth-link" href="profile.php">← Back to Login</a></p>
                <?php else: ?>
                    <p class="auth-footer-copy"><a class="auth-link" href="profile.php">← Back to Login</a></p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
