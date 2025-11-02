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
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .reset-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .reset-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 8px 0;
            text-align: center;
        }
        .reset-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0 0 32px 0;
            text-align: center;
        }
        .reset-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
        .reset-message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .reset-message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .reset-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .reset-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .reset-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }
        .reset-input-wrapper {
            position: relative;
        }
        .reset-input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .reset-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .reset-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #64748b;
        }
        .reset-submit {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .reset-submit:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .reset-submit:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }
        .reset-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
        }
        .reset-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .reset-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <h1 class="reset-title">Reset Password</h1>
            <p class="reset-subtitle">Enter your new password below</p>
            
            <?php if ($error): ?>
                <div class="reset-message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="reset-message success"><?= htmlspecialchars($success) ?></div>
                <div class="reset-footer">
                    <a href="profile.php" class="reset-link">← Back to Login</a>
                </div>
            <?php elseif (!$error): ?>
                <form class="reset-form" method="POST">
                    <div class="reset-field">
                        <label class="reset-label" for="password">New Password</label>
                        <div class="reset-input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="reset-input" 
                                required 
                                minlength="8"
                                placeholder="Enter new password"
                                autocomplete="new-password"
                            >
                            <button type="button" class="reset-toggle" onclick="togglePassword('password')">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="reset-field">
                        <label class="reset-label" for="confirm_password">Confirm Password</label>
                        <div class="reset-input-wrapper">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="reset-input" 
                                required 
                                minlength="8"
                                placeholder="Confirm new password"
                                autocomplete="new-password"
                            >
                            <button type="button" class="reset-toggle" onclick="togglePassword('confirm_password')">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="reset-submit">Reset Password</button>
                </form>
                
                <div class="reset-footer">
                    <a href="profile.php" class="reset-link">← Back to Login</a>
                </div>
            <?php else: ?>
                <div class="reset-footer">
                    <a href="profile.php" class="reset-link">← Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
