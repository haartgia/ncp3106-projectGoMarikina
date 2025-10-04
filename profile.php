<?php
// Profile login page renders the standalone sign-in experience.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Marikina · Profile</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="auth-body" id="top">
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main auth-main" id="main-content">
            <header class="auth-header" aria-label="Go Marikina branding">
                <img src="./uploads/go_marikina_logo.png" alt="Go Marikina" class="auth-logo">
                <h1 class="auth-section-title">Profile</h1>
                <span class="auth-header-spacer" aria-hidden="true"></span>
            </header>

            <?php include __DIR__ . '/includes/login.php'; ?>
        </main>
    </div>
    <script src="./assets/js/script.js" defer></script>
</body>
</html>
