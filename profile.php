<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php';

if (is_logged_in()) {
    $user = current_user(); // get from session
    $user_id = $user['id'];

    // Fetch user info from database (optional — can skip if already in session)
    $stmt = $conn->prepare("SELECT first_name, last_name, email, mobile FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROFILE - GO! MARIKINA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-logo">
                    <img src="./uploads/go_marikina_logo.png" alt="GO! MARIKINA" class="header-logo-img">
                </div>
                <h1 class="profile-title">PROFILE</h1>
                <div class="dashboard-actions" aria-hidden="true"></div>
            </header>

            <?php
            $redirectTarget = 'profile.php';
            include __DIR__ . '/includes/login_card.php';
            ?>

            <?php if (is_logged_in() && $user): ?>
            <!-- Profile Content -->
            <div id="profileContent">
                <section class="profile-section">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3>User Details</h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-info">
                                <div class="profile-field">
                                    <label class="profile-label">Name</label>
                                    <input type="text" class="profile-input" 
                                        value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Email</label>
                                    <input type="email" class="profile-input" 
                                        value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Password</label>
                                    <div class="profile-input-group">
                                        <input type="password" class="profile-input" value="*************" readonly id="passwordField">
                                        <button type="button" class="profile-edit-btn" id="editPasswordBtn" data-field="password">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Mobile Number</label>
                                    <div class="profile-input-group">
                                        <input type="tel" class="profile-input" 
                                            value="<?php echo htmlspecialchars($user['mobile']); ?>" readonly id="mobileField">
                                        <button type="button" class="profile-edit-btn" id="editMobileBtn" data-field="mobile">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="reports-summary">
                    <h2>No. of Reports 7</h2>
                </div>

                <div class="dividing-line"></div>

                <section class="reports-section">
                    <div class="reports-header">
                        <h3>Your Reports</h3>
                        <div class="reports-filter">
                            <button type="button" class="filter-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="reportFilterMenu">
                                <span>Filter</span>
                                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                    <path d="M4 5h16M7 12h10m-6 7h2" />
                                </svg>
                            </button>
                            <div class="filter-menu" id="reportFilterMenu" role="menu" hidden>
                                <button type="button" class="filter-option active" data-status="all" role="menuitemradio" aria-checked="true">All Reports</button>
                                <button type="button" class="filter-option" data-status="unresolved" role="menuitemradio" aria-checked="false">Unresolved</button>
                                <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                            </div>
                        </div>
                    </div>

                    <div class="reports-list">
                        <p>No reports yet.</p>
                    </div>
                </section>
            </div>
            <?php endif; ?>
        </main>

        <button type="button" class="floating-action" aria-label="Create a new report" onclick="window.location.href='create-report.php'">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <script src="assets/js/script.js" defer></script>
</body>
</html>
